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
        SELECT pd.id, pd.status, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, pd.date_requested
        FROM pending_delivery pd
        JOIN users u ON pd.user_id = u.id
        ORDER BY pd.date_requested DESC
        LIMIT 5
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($orders);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
