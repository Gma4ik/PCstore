<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/admin/orders(?:/(\d+))?#', $uri, $matches);
$orderId = isset($matches[1]) ? (int) $matches[1] : null;

switch ($method) {
    case 'GET':
        $orderId ? getAdminOrder($orderId) : getAllOrders();
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

/**
 * GET /api/admin/orders
 * Returns all orders. Admin only.
 */
function getAllOrders(): void {
    requireAdmin();
    try {
        $db   = getDB();
        $stmt = $db->query(
            'SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             ORDER BY o.created_at DESC'
        );
        $orders = array_map('formatAdminOrder', $stmt->fetchAll());

        $itemStmt = $db->prepare(
            'SELECT oi.*, p.image FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?'
        );
        foreach ($orders as &$order) {
            $itemStmt->execute([$order['id']]);
            $order['items'] = array_map('formatAdminOrderItem', $itemStmt->fetchAll());
        }
        unset($order);

        echo json_encode(['success' => true, 'data' => $orders]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * GET /api/admin/orders/{id}
 */
function getAdminOrder(int $id): void {
    requireAdmin();
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o JOIN users u ON u.id = o.user_id
             WHERE o.id = ?'
        );
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        $stmt = $db->prepare(
            'SELECT oi.*, p.image FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?'
        );
        $stmt->execute([$id]);
        $order          = formatAdminOrder($order);
        $order['items'] = array_map('formatAdminOrderItem', $stmt->fetchAll());

        echo json_encode(['success' => true, 'data' => $order]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * PATCH /api/admin/orders/{id}
 * Updates order status and/or payment_status. Admin only.
 */
function updateOrderStatus(int $id): void {
    requireAdmin();
    $body          = json_decode(file_get_contents('php://input'), true) ?? [];
    $status        = $body['status'] ?? null;
    $paymentStatus = $body['payment_status'] ?? null;

    $allowedStatus        = ['Processing', 'Shipped', 'Delivered', 'Cancelled'];
    $allowedPaymentStatus = ['paid', 'pending'];

    // At least one of status or payment_status must be provided
    if ($status === null && $paymentStatus === null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
        return;
    }
    if ($status !== null && !in_array($status, $allowedStatus, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }
    if ($paymentStatus !== null && !in_array($paymentStatus, $allowedPaymentStatus, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid payment_status']);
        return;
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        // Restore stock when cancelling
        if ($status === 'Cancelled' && $order['status'] !== 'Cancelled') {
            $itemsStmt = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$id]);
            $restoreStmt = $db->prepare(
                'UPDATE products SET stock_quantity = stock_quantity + ?, in_stock = 1 WHERE id = ?'
            );
            foreach ($itemsStmt->fetchAll() as $oi) {
                $restoreStmt->execute([(int) $oi['quantity'], (int) $oi['product_id']]);
            }
        }

        // Build dynamic UPDATE based on provided fields
        $setClauses = [];
        $params     = [];
        if ($status !== null) {
            $setClauses[] = 'status = ?';
            $params[]     = $status;
        }
        if ($paymentStatus !== null) {
            $setClauses[] = 'payment_status = ?';
            $params[]     = $paymentStatus;
        }
        $params[] = $id;

        $db->prepare('UPDATE orders SET ' . implode(', ', $setClauses) . ' WHERE id = ?')
           ->execute($params);
        $db->commit();

        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'data' => formatAdminOrder($stmt->fetch())]);
    } catch (PDOException $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function formatAdminOrder(array $order): array {
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

function formatAdminOrderItem(array $item): array {
    $item['id']         = (int)   $item['id'];
    $item['order_id']   = (int)   $item['order_id'];
    $item['product_id'] = (int)   $item['product_id'];
    $item['quantity']   = (int)   $item['quantity'];
    $item['price']      = (float) $item['price'];
    return $item;
}
