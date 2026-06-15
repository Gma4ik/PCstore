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

// Extract the product ID from the URI if present
// e.g. /api/products/42  →  $productId = 42
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/products(?:/(\d+))?#', $uri, $matches);
$productId = isset($matches[1]) ? (int) $matches[1] : null;

switch ($method) {
    case 'GET':
        $productId ? getProduct($productId) : getProducts();
        break;

    case 'POST':
        requireAdmin();
        // POST to /api/products/{id} = update (FormData doesn't support PUT)
        if ($productId) {
            updateProduct($productId);
        } else {
            createProduct();
        }
        break;

    case 'PUT':
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }
        requireAdmin();
        updateProduct($productId);
        break;

    case 'DELETE':
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }
        requireAdmin();
        deleteProduct($productId);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// ---------------------------------------------------------------------------

function formatImageUrl(?string $image): ?string {
    if (!$image) return null;
    // Already a full URL
    if (str_starts_with($image, 'http')) return $image;
    // Relative path — prepend base
    return 'http://localhost' . $image;
}
function getProducts(): void {
    try {
        $db = getDB();
        $category = $_GET['category'] ?? null;

        if ($category) {
            $stmt = $db->prepare('SELECT * FROM products WHERE category = ?');
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query('SELECT * FROM products');
        }

        $products = $stmt->fetchAll();

        // Decode JSON specifications field for each product
        foreach ($products as &$p) {
            $p['specifications'] = $p['specifications'] ? json_decode($p['specifications'], true) : null;
            $p['in_stock']       = (bool) $p['in_stock'];
            $p['price']          = (float) $p['price'];
            $p['rating']         = (float) $p['rating'];
            $p['reviews']        = (int) $p['reviews'];
            $p['image']          = formatImageUrl($p['image'] ?? null);
        }

        echo json_encode(['success' => true, 'data' => $products]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * GET /api/products/{id}
 * Requirement 4.3, 4.4
 */
function getProduct(int $id): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        $product['specifications'] = $product['specifications'] ? json_decode($product['specifications'], true) : null;
        $product['in_stock']       = (bool) $product['in_stock'];
        $product['price']          = (float) $product['price'];
        $product['rating']         = (float) $product['rating'];
        $product['reviews']        = (int) $product['reviews'];
        $product['image']          = formatImageUrl($product['image'] ?? null);

        echo json_encode(['success' => true, 'data' => $product]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * POST /api/products
 * Requirement 4.5, 4.8
 * Supports multipart/form-data for image upload
 */
function createProduct(): void {
    // Support both JSON and multipart/form-data
    $isMultipart = isset($_FILES['image']) || !empty($_POST);
    $body = $isMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    // Debug
    error_log('createProduct: isMultipart=' . ($isMultipart ? 'yes' : 'no') . ' FILES=' . json_encode(array_keys($_FILES)) . ' POST_keys=' . json_encode(array_keys($_POST)));

    $name     = trim($body['name'] ?? '');
    $price    = $body['price'] ?? null;
    $category = trim($body['category'] ?? '');

    if (!$name || $price === null || !$category) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'name, price and category are required']);
        return;
    }

    $imagePath = null;
    if ($isMultipart && !empty($_FILES['image']['tmp_name'])) {
        $imagePath = handleImageUpload($_FILES['image']);
        if (!$imagePath) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid image file. Allowed: jpg, jpeg, png, webp, gif']);
            return;
        }
    } elseif (!empty($body['image'])) {
        $imagePath = $body['image'];
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO products (name, price, category, image, description, specifications, in_stock, stock_quantity, rating, reviews)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            (float) $price,
            $category,
            $imagePath,
            $body['description']    ?? null,
            isset($body['specifications']) ? (function($s) {
                if (is_string($s)) { $d = json_decode($s, true); return json_encode($d ?? $s); }
                return json_encode($s);
            })($body['specifications']) : null,
            isset($body['in_stock']) ? (($body['in_stock'] === true || $body['in_stock'] === 1 || $body['in_stock'] === '1' || $body['in_stock'] === 'true') ? 1 : 0) : 1,
            (int) ($body['stock_quantity'] ?? 0),
            (float) ($body['rating']  ?? 0),
            (int)   ($body['reviews'] ?? 0),
        ]);

        $newId = (int) $db->lastInsertId();

        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$newId]);
        $product = $stmt->fetch();

        $product['specifications'] = $product['specifications'] ? json_decode($product['specifications'], true) : null;
        $product['in_stock']       = (bool) $product['in_stock'];
        $product['price']          = (float) $product['price'];
        $product['rating']         = (float) $product['rating'];
        $product['reviews']        = (int) $product['reviews'];

        http_response_code(201);
        echo json_encode(['success' => true, 'data' => $product]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * PUT /api/products/{id}
 * Requirement 4.6, 4.8
 * Supports multipart/form-data for image upload
 */
function updateProduct(int $id): void {
    $isMultipart = isset($_FILES['image']) || !empty($_POST);
    $body = $isMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

    try {
        $db = getDB();

        $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        $allowed = ['name', 'price', 'category', 'description', 'in_stock', 'stock_quantity', 'rating', 'reviews'];
        $sets    = [];
        $values  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $val = $body[$field];
                if ($field === 'in_stock') {
                    $val = ($val === true || $val === 1 || $val === '1' || $val === 'true') ? 1 : 0;
                }
                $sets[]   = "$field = ?";
                $values[] = $val;
            }
        }

        if (array_key_exists('specifications', $body)) {
            $specs = $body['specifications'];
            // May arrive as JSON string from FormData
            if (is_string($specs)) {
                $decoded = json_decode($specs, true);
                $specs   = $decoded ?? $specs;
            }
            $sets[]   = 'specifications = ?';
            $values[] = json_encode($specs);
        }

        // Handle image upload
        if (!empty($_FILES['image']['tmp_name'])) {
            $imagePath = handleImageUpload($_FILES['image']);
            if ($imagePath) {
                $sets[]   = 'image = ?';
                $values[] = $imagePath;
            }
        } elseif (array_key_exists('image', $body) && $body['image']) {
            $sets[]   = 'image = ?';
            $values[] = $body['image'];
        }

        if (empty($sets)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $values[] = $id;
        $db->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = ?')
           ->execute($values);

        $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        $product['specifications'] = $product['specifications'] ? json_decode($product['specifications'], true) : null;
        $product['in_stock']       = (bool) $product['in_stock'];
        $product['price']          = (float) $product['price'];
        $product['rating']         = (float) $product['rating'];
        $product['reviews']        = (int) $product['reviews'];

        echo json_encode(['success' => true, 'data' => $product]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Saves uploaded image to /uploads/ and returns the public path.
 */
function handleImageUpload(array $file): ?string {
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    error_log("handleImageUpload: name={$file['name']}, ext=$ext, error={$file['error']}, size={$file['size']}");

    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error code: {$file['error']}");
        return null;
    }

    if (!in_array($ext, $allowedExt, true)) {
        error_log("Extension not allowed: $ext");
        return null;
    }

    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = uniqid('img_', true) . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        error_log("move_uploaded_file failed to: $dest");
        return null;
    }

    return '/pc_store/uploads/' . $filename;
}

/**
 * DELETE /api/products/{id}
 * Requirement 4.7, 4.8
 */
function deleteProduct(int $id): void {
    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$id]);

        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        $db->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);

        echo json_encode(['success' => true, 'data' => ['message' => 'Product deleted']]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
