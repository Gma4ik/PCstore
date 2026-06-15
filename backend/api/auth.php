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
require_once __DIR__ . '/../config/jwt.php';

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($method === 'POST') {
    if (str_ends_with($uri, '/register')) {
        handleRegister($body);
    } elseif (str_ends_with($uri, '/login')) {
        handleLogin($body);
    } elseif (str_ends_with($uri, '/reset-password')) {
        handleResetPassword($body);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// ---------------------------------------------------------------------------

function handleRegister(array $body): void {
    $name     = trim($body['name'] ?? '');
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$name || !$email || !$password) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Name, email and password are required']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        return;
    }
    if (strlen($password) < 6) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, 'user']);
        $userId = (int) $db->lastInsertId();

        $token = jwtEncode(['sub' => $userId, 'email' => $email, 'role' => 'user']);

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data'    => [
                'token' => $token,
                'user'  => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'user'],
            ],
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function handleLogin(array $body): void {
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        return;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, email, password, role, phone, address, created_at FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            return;
        }

        $token = jwtEncode([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'],
            'name'  => $user['name'],
        ]);

        echo json_encode([
            'success' => true,
            'data'    => [
                'token' => $token,
                'user'  => [
                    'id'         => $user['id'],
                    'name'       => $user['name'],
                    'email'      => $user['email'],
                    'role'       => $user['role'],
                    'phone'      => $user['phone'],
                    'address'    => $user['address'],
                    'created_at' => $user['created_at'],
                ],
            ],
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function handleResetPassword(array $body): void {
    $email       = trim($body['email'] ?? '');
    $newPassword = $body['new_password'] ?? '';

    if (!$email || !$newPassword) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Email та новий пароль обовʼязкові']);
        return;
    }
    if (strlen($newPassword) < 6) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Пароль має містити мінімум 6 символів']);
        return;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Користувача з таким email не знайдено']);
            return;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);

        echo json_encode(['success' => true, 'message' => 'Пароль успішно змінено']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
