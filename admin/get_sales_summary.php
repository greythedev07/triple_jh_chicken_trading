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
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM history_of_delivery hod
        JOIN to_be_delivered tbd ON hod.to_be_delivered_id = tbd.id
        JOIN pending_delivery pd ON tbd.pending_delivery_id = pd.id
    ")->fetchColumn();

    // Get total orders
    $totalOrders = $db->query("SELECT COUNT(*) FROM pending_delivery")->fetchColumn();

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
