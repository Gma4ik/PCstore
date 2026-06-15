<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Extract product_id from URI: /api/wishlist/{product_id}
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/wishlist(?:/(\d+))?#', $uri, $matches);
$productId = isset($matches[1]) ? (int) $matches[1] : null;

// All endpoints require authentication (Requirements: 3.1, 3.3, 3.6)
$user = requireAuth();

switch ($method) {
    case 'GET':
        getWishlist($user);
        break;

    case 'POST':
        addToWishlist($user);
        break;

    case 'DELETE':
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'product_id is required']);
            exit;
        }
        removeFromWishlist($user, $productId);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// ---------------------------------------------------------------------------

/**
 * GET /api/wishlist
 * Returns all wishlist items for the current user, joined with product data.
 * Requirements: 3.1, 3.6
 */
function getWishlist(array $user): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT w.id AS wishlist_id, w.created_at AS added_at,
                    p.id, p.name, p.price, p.image, p.rating, p.reviews,
                    p.in_stock, p.stock_quantity, p.category
             FROM wishlists w
             JOIN products p ON p.id = w.product_id
             WHERE w.user_id = ?
             ORDER BY w.created_at DESC'
        );
        $stmt->execute([$user['sub']]);
        $items = $stmt->fetchAll();

        // Cast numeric fields
        foreach ($items as &$item) {
            $item['id']             = (int) $item['id'];
            $item['wishlist_id']    = (int) $item['wishlist_id'];
            $item['price']          = (float) $item['price'];
            $item['rating']         = (float) $item['rating'];
            $item['reviews']        = (int) $item['reviews'];
            $item['in_stock']       = (bool) $item['in_stock'];
            $item['stock_quantity'] = (int) $item['stock_quantity'];
        }

        echo json_encode(['success' => true, 'data' => $items]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * POST /api/wishlist
 * Adds a product to the wishlist. Silently ignores duplicates via INSERT IGNORE.
 * Requirements: 3.1, 3.6
 */
function addToWishlist(array $user): void {
    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $productId = isset($body['product_id']) ? (int) $body['product_id'] : null;

    if (!$productId) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'product_id is required']);
        return;
    }

    try {
        $db = getDB();

        // Verify product exists
        $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        // INSERT IGNORE silently skips if UNIQUE KEY (user_id, product_id) already exists
        $stmt = $db->prepare('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)');
        $stmt->execute([$user['sub'], $productId]);

        http_response_code(201);
        echo json_encode(['success' => true, 'data' => ['user_id' => $user['sub'], 'product_id' => $productId]]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * DELETE /api/wishlist/{product_id}
 * Removes a product from the current user's wishlist.
 * Requirements: 3.3
 */
function removeFromWishlist(array $user, int $productId): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$user['sub'], $productId]);

        echo json_encode(['success' => true, 'data' => ['message' => 'Removed from wishlist']]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
