<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireAdmin();

try {
    $db = getDB();

    // Sales by month — last 12 months
    $salesStmt = $db->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                SUM(total) AS total,
                COUNT(*)   AS count
         FROM orders
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY month
         ORDER BY month ASC"
    );
    $salesByMonth = array_map(fn($r) => [
        'month' => $r['month'],
        'total' => (float) $r['total'],
        'count' => (int)   $r['count'],
    ], $salesStmt->fetchAll());

    // Top 5 products by quantity sold
    $topStmt = $db->query(
        "SELECT oi.product_id, oi.name,
                SUM(oi.quantity)            AS sold,
                SUM(oi.quantity * oi.price) AS revenue
         FROM order_items oi
         GROUP BY oi.product_id, oi.name
         ORDER BY sold DESC
         LIMIT 5"
    );
    $topProducts = array_map(fn($r) => [
        'product_id' => (int)   $r['product_id'],
        'name'       => $r['name'],
        'sold'       => (int)   $r['sold'],
        'revenue'    => (float) $r['revenue'],
    ], $topStmt->fetchAll());

    // Orders by status with percentage
    $statusStmt = $db->query(
        "SELECT status,
                COUNT(*) AS count,
                ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 1) AS percent
         FROM orders
         GROUP BY status
         ORDER BY count DESC"
    );
    $ordersByStatus = array_map(fn($r) => [
        'status'  => $r['status'],
        'count'   => (int)   $r['count'],
        'percent' => (float) $r['percent'],
    ], $statusStmt->fetchAll());

    // Total counts for dashboard cards
    $totalUsers    = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalProducts = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $totalOrders   = (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $totalRevenue  = (float) $db->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn();

    echo json_encode([
        'success' => true,
        'data'    => [
            'sales_by_month'   => $salesByMonth,
            'top_products'     => $topProducts,
            'orders_by_status' => $ordersByStatus,
            'totals'           => [
                'users'    => $totalUsers,
                'products' => $totalProducts,
                'orders'   => $totalOrders,
                'revenue'  => $totalRevenue,
            ],
        ],
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
