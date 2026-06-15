<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../middleware/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

getStats();

/**
 * GET /api/stats
 * Returns sales_by_month, top_products, and orders_by_status.
 * Admin only.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.8
 */
function getStats(): void {
    requireAdmin();

    try {
        $db = getDB();

        // Sales by month — last 12 months (Requirements 4.1, 4.2)
        $salesStmt = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                    SUM(total)  AS total,
                    COUNT(*)    AS count
             FROM orders
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY month
             ORDER BY month ASC"
        );
        $salesByMonth = array_map(function (array $row): array {
            return [
                'month' => $row['month'],
                'total' => (float) $row['total'],
                'count' => (int)   $row['count'],
            ];
        }, $salesStmt->fetchAll());

        // Top 5 products by quantity sold (Requirements 4.3, 4.4)
        $topStmt = $db->query(
            "SELECT oi.product_id,
                    oi.name,
                    SUM(oi.quantity)            AS sold,
                    SUM(oi.quantity * oi.price) AS revenue
             FROM order_items oi
             GROUP BY oi.product_id, oi.name
             ORDER BY sold DESC
             LIMIT 5"
        );
        $topProducts = array_map(function (array $row): array {
            return [
                'product_id' => (int)   $row['product_id'],
                'name'       => $row['name'],
                'sold'       => (int)   $row['sold'],
                'revenue'    => (float) $row['revenue'],
            ];
        }, $topStmt->fetchAll());

        // Orders by status with percentage (Requirements 4.5, 4.6)
        $statusStmt = $db->query(
            "SELECT status,
                    COUNT(*) AS count,
                    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 1) AS percent
             FROM orders
             GROUP BY status
             ORDER BY count DESC"
        );
        $ordersByStatus = array_map(function (array $row): array {
            return [
                'status'  => $row['status'],
                'count'   => (int)   $row['count'],
                'percent' => (float) $row['percent'],
            ];
        }, $statusStmt->fetchAll());

        echo json_encode([
            'success' => true,
            'data'    => [
                'sales_by_month'   => $salesByMonth,
                'top_products'     => $topProducts,
                'orders_by_status' => $ordersByStatus,
            ],
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
}
