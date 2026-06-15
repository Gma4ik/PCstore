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
preg_match('#/api/admin/users(?:/(\w+))?#', $uri, $matches);
$userSegment = $matches[1] ?? null;

switch ($method) {
    case 'GET':
        requireAdmin();
        getUsers();
        break;
    case 'PATCH':
        if (is_numeric($userSegment)) {
            requireSuperAdmin();
            updateUserRole((int) $userSegment);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * GET /api/admin/users
 * Returns all users. Admin only.
 */
function getUsers(): void {
    try {
        $db   = getDB();
        $stmt = $db->query(
            'SELECT id, name, email, role, phone, created_at FROM users ORDER BY created_at DESC'
        );
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

/**
 * PATCH /api/admin/users/{id}
 * Updates user role. Superadmin only.
 */
function updateUserRole(int $id): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $role = $body['role'] ?? null;

    $allowed = ['user', 'admin'];
    if (!$role || !in_array($role, $allowed, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Role must be "user" or "admin"']);
        return;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        if ($user['role'] === 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Cannot change superadmin role']);
            return;
        }

        $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);

        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
