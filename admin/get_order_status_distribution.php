<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get order status distribution (combining active and completed orders)
    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN status IS NULL OR status = '' OR LOWER(status) IN ('pending','pending delivery') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN LOWER(status) IN ('to be delivered', 'out for delivery', 'assigned', 'picked_up') THEN 1 ELSE 0 END) as delivering,
            SUM(CASE WHEN LOWER(status) = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN LOWER(status) IN ('cancelled', 'canceled') THEN 1 ELSE 0 END) as cancelled
        FROM (
            SELECT status FROM pending_delivery WHERE LOWER(COALESCE(status,'')) <> 'delivered'
            UNION ALL
            SELECT 'delivered' as status
            FROM history_of_delivery hod
            WHERE hod.id IN (
                SELECT MIN(id) FROM history_of_delivery
                GROUP BY to_be_delivered_id
            )
        ) as combined_orders
    ");
    $stmt->execute();
    $distribution = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($distribution);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
