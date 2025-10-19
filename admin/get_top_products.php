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
        SELECT p.name, COALESCE(SUM(quantity_sold), 0) as total_sold
        FROM products p
        LEFT JOIN (
            SELECT product_id, quantity as quantity_sold
            FROM pending_delivery_items pdi
            JOIN pending_delivery pd ON pdi.pending_delivery_id = pd.id
            WHERE pd.status IN ('delivered', 'to be delivered', 'out for delivery', 'assigned', 'picked_up')
            UNION ALL
            SELECT product_id, quantity as quantity_sold
            FROM history_of_delivery_items hdi
        ) as all_items ON p.id = all_items.product_id
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
