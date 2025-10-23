<?php
// admin/fetch_gcash_orders.php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// TODO: Add admin session verification here

try {
    // Fetch GCash orders that need verification
    $stmt = $db->prepare("
        SELECT 
            pd.id,
            pd.order_number,
            pd.user_id,
            pd.gcash_reference,
            pd.total_amount,
            pd.delivery_address,
            pd.payment_status,
            pd.date_requested,
            u.firstname,
            u.lastname,
            u.phonenumber,
            u.email
        FROM pending_delivery pd
        JOIN users u ON pd.user_id = u.id
        WHERE pd.payment_method = 'GCash' 
        AND pd.payment_status IN ('pending', 'failed')
        ORDER BY pd.date_requested ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch order items for each order
    foreach ($orders as &$order) {
        $itemsStmt = $db->prepare("
            SELECT 
                pdi.quantity,
                pdi.price,
                p.name
            FROM pending_delivery_items pdi
            JOIN products p ON pdi.product_id = p.id
            WHERE pdi.pending_delivery_id = ?
        ");
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['status' => 'success', 'orders' => $orders]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
