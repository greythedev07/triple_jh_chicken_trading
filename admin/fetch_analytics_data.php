<?php
require_once('../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get total sales for the current week
    $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));

    // Total sales amount for the week
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total_sales
        FROM history_of_delivery
        WHERE delivery_time BETWEEN ? AND ?
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    $totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total_sales'];

    // Total number of orders for the week
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_orders
        FROM history_of_delivery
        WHERE delivery_time BETWEEN ? AND ?
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

    // Top selling product for the week
    $stmt = $db->prepare("
        SELECT p.name as product_name, SUM(hdi.quantity) as total_quantity
        FROM history_of_delivery_items hdi
        JOIN products p ON hdi.product_id = p.id
        JOIN history_of_delivery hd ON hdi.history_id = hd.id
        WHERE hd.delivery_time BETWEEN ? AND ?
        GROUP BY p.name
        ORDER BY total_quantity DESC
        LIMIT 1
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    $topProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    // Total revenue (same as total sales in this case)
    $totalRevenue = $totalSales;

    // Prepare response
    $response = [
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'total_sales' => (float)$totalSales,
        'total_orders' => (int)$totalOrders,
        'top_product' => $topProduct ?: ['product_name' => 'No data', 'total_quantity' => 0],
        'total_revenue' => (float)$totalRevenue
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
