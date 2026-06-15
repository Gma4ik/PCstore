<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Extract optional order ID from URI: /api/orders/42
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/orders(?:/(\d+))?#', $uri, $matches);
$orderId = isset($matches[1]) ? (int) $matches[1] : null;

switch ($method) {
    case 'GET':
        $orderId ? getOrder($orderId) : getOrders();
        break;

    case 'POST':
        createOrder();
        break;

    case 'PATCH':
    case 'PUT':
        if ($orderId) updateOrderStatus($orderId);
        else { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Order ID required']); }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// ---------------------------------------------------------------------------

/**
 * GET /api/orders
 * Always returns only the authenticated user's own orders.
 * For admin access to all orders, use /api/admin/orders.
 */
function getOrders(): void {
    $payload = requireAuth();

    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$payload['sub']]);

        $orders = array_map('formatOrder', $stmt->fetchAll());

        $itemStmt = $db->prepare(
            'SELECT oi.*, p.image FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?'
        );
        foreach ($orders as &$order) {
            $itemStmt->execute([$order['id']]);
            $order['items'] = array_map('formatOrderItem', $itemStmt->fetchAll());
        }
        unset($order);

        echo json_encode(['success' => true, 'data' => $orders]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * GET /api/orders/{id}
 * Returns a single order with its items.
 * Requirements: 5.4, 5.5
 */
function getOrder(int $id): void {
    $payload = requireAuth();

    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Non-admins can only view their own orders
        if ($payload['role'] !== 'admin' && (int) $order['user_id'] !== (int) $payload['sub']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return;
        }

        // Fetch order items
        $stmt = $db->prepare(
            'SELECT oi.*, p.image FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$id]);
        $items = $stmt->fetchAll();

        $order         = formatOrder($order);
        $order['items'] = array_map('formatOrderItem', $items);

        echo json_encode(['success' => true, 'data' => $order]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * POST /api/orders
 * Creates an order and its items inside a transaction.
 * Requirements: 5.1, 5.5
 */
function createOrder(): void {
    $payload = requireAuth();
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];

    $items         = $body['items']         ?? [];
    $total         = $body['total']         ?? null;
    $customerInfo  = $body['customer_info'] ?? $body['customerInfo'] ?? null;
    $paymentMethod = $body['payment_method'] ?? 'cash_on_delivery';
    // Derive payment_status from method if not explicitly provided
    $paymentStatus = $body['payment_status'] ?? ($paymentMethod === 'card' ? 'paid' : 'pending');

    $allowedMethods  = ['card', 'cash_on_delivery'];
    $allowedStatuses = ['paid', 'pending'];
    if (!in_array($paymentMethod, $allowedMethods, true)) $paymentMethod = 'cash_on_delivery';
    if (!in_array($paymentStatus, $allowedStatuses, true)) $paymentStatus = 'pending';

    if (empty($items) || $total === null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'items and total are required']);
        return;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Insert the order
        $stmt = $db->prepare(
            'INSERT INTO orders (user_id, total, customer_info, status, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $payload['sub'],
            (float) $total,
            $customerInfo ? json_encode($customerInfo) : null,
            'Processing',
            $paymentMethod,
            $paymentStatus,
        ]);
        $orderId = (int) $db->lastInsertId();

        // Insert each order item
        $itemStmt  = $db->prepare(
            'INSERT INTO order_items (order_id, product_id, name, quantity, price) VALUES (?, ?, ?, ?, ?)'
        );
        $stockCheck = $db->prepare(
            'SELECT stock_quantity, name FROM products WHERE id = ? FOR UPDATE'
        );
        $stockDecr  = $db->prepare(
            'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?'
        );
        $stockZero  = $db->prepare(
            'UPDATE products SET in_stock = 0 WHERE id = ? AND stock_quantity <= 0'
        );

        foreach ($items as $item) {
            $productId = $item['id'] ?? $item['product_id'] ?? null;
            $name      = $item['name']     ?? '';
            $quantity  = (int)   ($item['quantity'] ?? 1);
            $price     = (float) ($item['price']    ?? 0);

            if (!$productId || !$name || $quantity < 1) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Each item requires id, name, quantity and price']);
                return;
            }

            // Check stock availability (Requirement 2.1, 2.2)
            $stockCheck->execute([$productId]);
            $product = $stockCheck->fetch();
            if ($product && (int) $product['stock_quantity'] < $quantity) {
                $db->rollBack();
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Insufficient stock for ' . $product['name']]);
                return;
            }

            $itemStmt->execute([$orderId, $productId, $name, $quantity, $price]);

            // Decrement stock (Requirement 2.3)
            $stockDecr->execute([$quantity, $productId]);

            // Set in_stock = 0 if stock reached 0 (Requirement 2.5)
            $stockZero->execute([$productId]);
        }

        $db->commit();

        // Return the created order with items
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        $stmt = $db->prepare(
            'SELECT oi.*, p.image FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll();

        $order          = formatOrder($order);
        $order['items'] = array_map('formatOrderItem', $orderItems);

        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $order]);
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

// ---------------------------------------------------------------------------
// Helpers

function formatOrder(array $order): array {
    $order['id']             = (int)   $order['id'];
    $order['user_id']        = (int)   $order['user_id'];
    $order['total']          = (float) $order['total'];
    $order['customer_info']  = isset($order['customer_info'])
        ? json_decode($order['customer_info'], true)
        : null;
    $order['payment_method'] = $order['payment_method'] ?? 'cash_on_delivery';
    $order['payment_status'] = $order['payment_status'] ?? 'pending';
    return $order;
}

function formatOrderItem(array $item): array {
    $item['id']         = (int)   $item['id'];
    $item['order_id']   = (int)   $item['order_id'];
    $item['product_id'] = (int)   $item['product_id'];
    $item['quantity']   = (int)   $item['quantity'];
    $item['price']      = (float) $item['price'];
    return $item;
}

/**
 * PATCH /api/orders/{id}
 * Updates order status. Admin only.
 */
function updateOrderStatus(int $id): void {
    requireAdmin();
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $status = $body['status'] ?? null;

    $allowed = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!$status || !in_array($status, $allowed, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        // Fetch current order to check previous status
        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Restore stock when cancelling (Requirement 2.4)
        if ($status === 'Cancelled' && $order['status'] !== 'Cancelled') {
            $itemsStmt = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$id]);
            $orderItems = $itemsStmt->fetchAll();

            $restoreStmt = $db->prepare(
                'UPDATE products SET stock_quantity = stock_quantity + ?, in_stock = 1 WHERE id = ?'
            );
            foreach ($orderItems as $oi) {
                $restoreStmt->execute([(int) $oi['quantity'], (int) $oi['product_id']]);
            }
        }

        $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);

        $db->commit();

        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => formatOrder($order)]);
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
