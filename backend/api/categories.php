<?php

header('Access-Control-Allow-Origin: *');
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
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
preg_match('#/api/categories(?:/([^/]+))?#', $uri, $matches);
$categoryId = $matches[1] ?? null;

switch ($method) {
    case 'GET':
        getCategories();
        break;
    case 'POST':
        requireAdmin();
        createCategory();
        break;
    case 'PUT':
        requireAdmin();
        if ($categoryId) updateCategory($categoryId);
        else { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID required']); }
        break;
    case 'DELETE':
        requireAdmin();
        if ($categoryId) deleteCategory($categoryId);
        else { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID required']); }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getCategories(): void {
    try {
        $db   = getDB();
        $rows = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
        echo json_encode(['success' => true, 'data' => $rows]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function createCategory(): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = trim($body['id']   ?? '');
    $name = trim($body['name'] ?? '');
    $icon = trim($body['icon'] ?? '📦');

    if (!$id || !$name) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'id and name are required']);
        return;
    }

    try {
        $db   = getDB();
        $stmt = $db->prepare('INSERT INTO categories (id, name, icon) VALUES (?, ?, ?)');
        $stmt->execute([$id, $name, $icon]);
        echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $name, 'icon' => $icon]]);
    } catch (PDOException $e) {
        http_response_code($e->getCode() == 23000 ? 422 : 500);
        echo json_encode(['success' => false, 'message' => $e->getCode() == 23000 ? 'Category ID already exists' : 'Server error']);
    }
}

function updateCategory(string $id): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($body['name'] ?? '');
    $icon = trim($body['icon'] ?? '');

    if (!$name) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'name is required']);
        return;
    }

    try {
        $db   = getDB();
        $sets = ['name = ?'];
        $vals = [$name];
        if ($icon) { $sets[] = 'icon = ?'; $vals[] = $icon; }
        $vals[] = $id;
        $db->prepare('UPDATE categories SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
        echo json_encode(['success' => true, 'data' => ['id' => $id, 'name' => $name, 'icon' => $icon]]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}

function deleteCategory(string $id): void {
    try {
        $db = getDB();
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'data' => ['message' => 'Category deleted']]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
