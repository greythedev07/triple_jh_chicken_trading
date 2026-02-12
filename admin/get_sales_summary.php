<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get current week's start and end dates
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));

    // First, ensure weekly_analytics table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'weekly_analytics'");
    if ($tableCheck->rowCount() === 0) {
        throw new Exception("weekly_analytics table does not exist. Please run the migration first.");
    }

    // Ensure a row exists for the current week (so a new week shows up immediately)
    $checkStmt = $db->prepare("SELECT id FROM weekly_analytics WHERE week_start_date = ? AND week_end_date = ? LIMIT 1");
    $checkStmt->execute([$weekStart, $weekEnd]);
    $currentWeekRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentWeekRow) {
        $insertStmt = $db->prepare("
            INSERT INTO weekly_analytics
                (week_start_date, week_end_date, total_sales, total_orders, total_products_sold, created_at, updated_at)
            VALUES
                (?, ?, 0, 0, 0, NOW(), NOW())
        ");
        $insertStmt->execute([$weekStart, $weekEnd]);
    }

    // Always display the latest (most recent) weekly_analytics row
    $stmt = $db->prepare("
        SELECT week_start_date, week_end_date, total_sales, total_orders, total_products_sold
        FROM weekly_analytics
        ORDER BY week_start_date DESC, week_end_date DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute();
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$latest) {
        $latest = [
            'week_start_date' => $weekStart,
            'week_end_date' => $weekEnd,
            'total_sales' => 0,
            'total_orders' => 0,
            'total_products_sold' => 0,
        ];
    }

    // Calculate average order value
    $averageOrder = ((int)$latest['total_orders']) > 0
        ? ((float)$latest['total_sales']) / ((int)$latest['total_orders'])
        : 0;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'totalRevenue' => number_format((float)$latest['total_sales'], 2),
            'totalOrders' => (int)$latest['total_orders'],
            'totalItems' => (int)$latest['total_products_sold'],
            'averageOrder' => number_format($averageOrder, 2),
            'weekStart' => $latest['week_start_date'],
            'weekEnd' => $latest['week_end_date']
        ]
    ]);

} catch (Exception $e) {
    error_log('Error in get_sales_summary.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch sales summary: ' . $e->getMessage()
    ]);
}
