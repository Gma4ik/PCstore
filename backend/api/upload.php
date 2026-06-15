<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../middleware/auth.php';

requireAdmin();

if (empty($_FILES['image'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'No file uploaded. FILES: ' . json_encode($_FILES)]);
    exit;
}

$file       = $_FILES['image'];
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($file['error'] !== UPLOAD_ERR_OK || !in_array($ext, $allowedExt, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => "Upload error: {$file['error']}, ext: $ext"]);
    exit;
}

$uploadDir = __DIR__ . '/../../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$filename = uniqid('img_', true) . '.' . $ext;
$dest     = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file to: ' . $dest]);
    exit;
}

echo json_encode(['success' => true, 'data' => ['path' => 'http://localhost/pc_store/uploads/' . $filename]]);
