<?php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Verify admin session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $db->query("
        SELECT 
            pd.id,
            pd.order_number,
            pd.user_id,
            pd.delivery_address,
            pd.payment_method,
            pd.status,
            pd.total_amount,
            pd.date_requested,
            CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
            d.name AS driver_name
        FROM pending_delivery pd
        JOIN users u ON pd.user_id = u.id
        LEFT JOIN drivers d ON pd.driver_id = d.id
        WHERE pd.status != 'delivered'
        ORDER BY pd.id DESC
    ");
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($deliveries);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
