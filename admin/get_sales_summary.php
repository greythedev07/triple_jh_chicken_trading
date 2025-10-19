<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get total revenue from completed deliveries
    $totalRevenue = $db->query("
        SELECT COALESCE(SUM(hdi.quantity * hdi.price), 0) as total
        FROM history_of_delivery hod
        JOIN history_of_delivery_items hdi ON hod.id = hdi.history_id
    ")->fetchColumn();

    // Get total orders (both active and completed)
    $totalOrders = $db->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM pending_delivery
            UNION
            SELECT to_be_delivered_id as id FROM history_of_delivery
        ) as combined_orders
    ")->fetchColumn();

    // Calculate average order value
    $averageOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

    echo json_encode([
        'totalRevenue' => number_format($totalRevenue, 2),
        'totalOrders' => (int)$totalOrders,
        'averageOrder' => number_format($averageOrder, 2)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
