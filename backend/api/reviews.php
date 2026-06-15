<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Extract review ID from URI: /api/reviews/{id}
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/reviews(?:/(\d+))?#', $uri, $matches);
$reviewId = isset($matches[1]) ? (int) $matches[1] : null;

switch ($method) {
    case 'GET':
        getReviews();
        break;

    case 'POST':
        $user = requireAuth();
        createReview($user);
        break;

    case 'PUT':
        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Review ID required']);
            exit;
        }
        $user = requireAuth();
        updateReview($reviewId, $user);
        break;

    case 'DELETE':
        if (!$reviewId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Review ID required']);
            exit;
        }
        $user = requireAuth();
        deleteReview($reviewId, $user);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// ---------------------------------------------------------------------------

/**
 * GET /api/reviews?product_id={id}
 * Returns all reviews for a product with author name.
 * Requirements: 1.1
 */
function getReviews(): void {
    $productId = isset($_GET['product_id']) ? (int) $_GET['product_id'] : null;

    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'product_id is required']);
        return;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'SELECT r.id, r.product_id, r.user_id, r.rating, r.comment,
                    r.created_at, r.updated_at,
                    u.name AS author_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$productId]);
        $reviews = $stmt->fetchAll();

        foreach ($reviews as &$r) {
            $r['rating'] = (int) $r['rating'];
        }

        echo json_encode(['success' => true, 'data' => $reviews]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * POST /api/reviews
 * Creates a review. One review per user per product (UNIQUE constraint → 409).
 * Updates products.rating and products.reviews after insert.
 * Requirements: 1.2, 1.3, 1.4
 */
function createReview(array $user): void {
    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $productId = isset($body['product_id']) ? (int) $body['product_id'] : null;
    $rating    = isset($body['rating'])     ? (int) $body['rating']     : null;
    $comment   = trim($body['comment'] ?? '');

    if (!$productId || !$rating) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'product_id and rating are required']);
        return;
    }

    if ($rating < 1 || $rating > 5) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
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

        $stmt = $db->prepare(
            'INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$productId, $user['sub'], $rating, $comment ?: null]);

        $newId = (int) $db->lastInsertId();

        // Update product rating and review count
        updateProductRating($db, $productId);

        // Return the created review with author name
        $stmt = $db->prepare(
            'SELECT r.id, r.product_id, r.user_id, r.rating, r.comment,
                    r.created_at, r.updated_at, u.name AS author_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.id = ?'
        );
        $stmt->execute([$newId]);
        $review = $stmt->fetch();
        $review['rating'] = (int) $review['rating'];

        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $review]);

    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
            return;
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * PUT /api/reviews/{id}
 * Edit own review. Updates product rating after change.
 * Requirements: 1.5
 */
function updateReview(int $id, array $user): void {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $rating  = isset($body['rating']) ? (int) $body['rating'] : null;
    $comment = array_key_exists('comment', $body) ? trim($body['comment']) : null;

    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        return;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        $review = $stmt->fetch();

        if (!$review) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Review not found']);
            return;
        }

        // Only the owner can edit
        if ((int) $review['user_id'] !== (int) $user['sub']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return;
        }

        $sets   = [];
        $values = [];

        if ($rating !== null) {
            $sets[]   = 'rating = ?';
            $values[] = $rating;
        }
        if ($comment !== null) {
            $sets[]   = 'comment = ?';
            $values[] = $comment ?: null;
        }

        if (empty($sets)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $values[] = $id;
        $db->prepare('UPDATE reviews SET ' . implode(', ', $sets) . ' WHERE id = ?')
           ->execute($values);

        updateProductRating($db, (int) $review['product_id']);

        $stmt = $db->prepare(
            'SELECT r.id, r.product_id, r.user_id, r.rating, r.comment,
                    r.created_at, r.updated_at, u.name AS author_name
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        $updated = $stmt->fetch();
        $updated['rating'] = (int) $updated['rating'];

        echo json_encode(['success' => true, 'data' => $updated]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * DELETE /api/reviews/{id}
 * Owner or admin can delete. Updates product rating after deletion.
 * Requirements: 1.6
 */
function deleteReview(int $id, array $user): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM reviews WHERE id = ?');
        $stmt->execute([$id]);
        $review = $stmt->fetch();

        if (!$review) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Review not found']);
            return;
        }

        $isOwner = (int) $review['user_id'] === (int) $user['sub'];
        $isAdmin = in_array($user['role'] ?? '', ['admin', 'superadmin'], true);

        if (!$isOwner && !$isAdmin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return;
        }

        $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);

        updateProductRating($db, (int) $review['product_id']);

        echo json_encode(['success' => true, 'data' => ['message' => 'Review deleted']]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * Recalculates and updates products.rating and products.reviews
 * based on current reviews in the DB.
 */
function updateProductRating(PDO $db, int $productId): void {
    $db->prepare(
        'UPDATE products
         SET rating  = COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = ?), 0),
             reviews = (SELECT COUNT(*)  FROM reviews WHERE product_id = ?)
         WHERE id = ?'
    )->execute([$productId, $productId, $productId]);
}
