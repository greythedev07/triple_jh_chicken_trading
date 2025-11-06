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
        SELECT id, order_number, status, customer_name, date_requested FROM (
            SELECT pd.id, pd.order_number, pd.status, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, pd.date_requested
            FROM pending_delivery pd
            JOIN users u ON pd.user_id = u.id
            WHERE pd.status != 'delivered'
            UNION
            SELECT hod.to_be_delivered_id as id, pd.order_number, 'delivered' as status, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, hod.created_at as date_requested
            FROM history_of_delivery hod
            JOIN users u ON hod.user_id = u.id
            JOIN to_be_delivered tbd ON hod.to_be_delivered_id = tbd.id
            JOIN pending_delivery pd ON tbd.pending_delivery_id = pd.id
            WHERE hod.id IN (
                SELECT MIN(id) FROM history_of_delivery
                GROUP BY to_be_delivered_id
            )
        ) as combined_orders
        ORDER BY date_requested DESC
        LIMIT 5
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
