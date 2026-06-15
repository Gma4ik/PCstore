<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// Extract optional user ID: /api/users/42  or  /api/users/me
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/users(?:/(\w+))?#', $uri, $matches);
$userSegment = $matches[1] ?? null;

switch ($method) {
    case 'GET':
        requireAdmin();
        getUsers();
        break;

    case 'PATCH':
        if ($userSegment === 'me') {
            // Any authenticated user can update their own profile
            updateMyProfile();
        } elseif (is_numeric($userSegment)) {
            requireSuperAdmin();
            updateUserRole((int) $userSegment);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function updateMyProfile(): void {
    $payload = requireAuth();
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];

    $allowed = ['name', 'phone', 'address'];
    $fields  = [];
    $values  = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $fields[] = "$field = ?";
            $values[] = $body[$field];
        }
    }

    // Handle password change separately
    if (!empty($body['new_password'])) {
        $currentPassword = $body['current_password'] ?? '';
        if (strlen($body['new_password']) < 6) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Новий пароль має містити мінімум 6 символів']);
            return;
        }
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$payload['sub']]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($currentPassword, $row['password'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Поточний пароль невірний']);
                return;
            }
            $fields[] = 'password = ?';
            $values[] = password_hash($body['new_password'], PASSWORD_BCRYPT);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
            return;
        }
    }

    if (empty($fields)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        return;
    }

    try {
        $db = getDB();
        $values[] = $payload['sub'];
        $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $stmt->execute($values);

        $stmt = $db->prepare('SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$payload['sub']]);
        $user = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $user]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function getUsers(): void {
    try {
        $db   = getDB();
        $stmt = $db->query(
            'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC'
        );
        $users = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $users]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function updateUserRole(int $id): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $role = $body['role'] ?? null;

    // superadmin can only assign/revoke 'admin' — cannot create another superadmin
    $allowed = ['user', 'admin'];
    if (!$role || !in_array($role, $allowed, true)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Role must be "user" or "admin"']);
        return;
    }

    try {
        $db = getDB();

        // Prevent changing a superadmin's role
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

        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);

        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $updated = $stmt->fetch();

        echo json_encode(['success' => true, 'data' => $updated]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
