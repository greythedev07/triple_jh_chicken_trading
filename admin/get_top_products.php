<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT p.name, COALESCE(SUM(pdi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN pending_delivery_items pdi ON p.id = pdi.product_id
        LEFT JOIN pending_delivery pd ON pdi.pending_delivery_id = pd.id
        WHERE pd.status IN ('delivered', 'to be delivered', 'out for delivery', 'assigned', 'picked_up')
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
