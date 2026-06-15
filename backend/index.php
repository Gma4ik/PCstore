<?php

// CORS headers — allow Vite dev server origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parse the request URI, strip query string and leading slash
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize: remove base path prefix if running under /pc_store/backend
$basePath = '/pc_store/backend';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$requestUri = trim($requestUri, '/');

// Route dispatcher
if (preg_match('#^api/admin/orders#', $requestUri)) {
    require __DIR__ . '/api/admin/orders.php';
} elseif (preg_match('#^api/admin/users#', $requestUri)) {
    require __DIR__ . '/api/admin/users.php';
} elseif (preg_match('#^api/admin/stats#', $requestUri)) {
    require __DIR__ . '/api/admin/stats.php';
} elseif (preg_match('#^api/auth#', $requestUri)) {
    require __DIR__ . '/api/auth.php';
} elseif (preg_match('#^api/products#', $requestUri)) {
    require __DIR__ . '/api/products.php';
} elseif (preg_match('#^api/categories#', $requestUri)) {
    require __DIR__ . '/api/categories.php';
} elseif (preg_match('#^api/orders#', $requestUri)) {
    require __DIR__ . '/api/orders.php';
} elseif (preg_match('#^api/users#', $requestUri)) {
    require __DIR__ . '/api/users.php';
} elseif (preg_match('#^api/reviews#', $requestUri)) {
    require __DIR__ . '/api/reviews.php';
} elseif (preg_match('#^api/wishlist#', $requestUri)) {
    require __DIR__ . '/api/wishlist.php';
} elseif (preg_match('#^api/stats#', $requestUri)) {
    require __DIR__ . '/api/stats.php';
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
}
