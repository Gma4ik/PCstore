<?php
require_once __DIR__ . '/../config/jwt.php';
function requireAuth(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';
    if (!$authHeader) {
        $allHeaders = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $authHeader = $allHeaders['Authorization'] ?? $allHeaders['authorization'] ?? '';
    }
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $token = substr($authHeader, 7);
    $payload = jwtDecode($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    return $payload;
}
function requireAdmin(): array {
    $payload = requireAuth();

    $role = $payload['role'] ?? '';
    if ($role !== 'admin' && $role !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    return $payload;
}
function requireSuperAdmin(): array {
    $payload = requireAuth();

    if (($payload['role'] ?? '') !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden: superadmin only']);
        exit;
    }
    return $payload;
}
