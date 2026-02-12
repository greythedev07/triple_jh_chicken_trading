<?php
// admin/fetch_gcash_orders.php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Verify admin session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Fetch GCash orders that need verification
    $stmt = $db->prepare("
        SELECT
            pd.id,
            pd.order_number,
            pd.user_id,
            pd.gcash_reference,
            pd.gcash_payment_screenshot,
            pd.total_amount,
            pd.delivery_address,
            pd.payment_status,
            pd.date_requested,
            CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
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

    // Debug: Log the query and results
    error_log("GCash Orders Query: " . $stmt->queryString);
    error_log("GCash Orders Found: " . count($orders));

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
    error_log("GCash Orders Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
