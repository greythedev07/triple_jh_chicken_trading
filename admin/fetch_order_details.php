<?php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Verify admin session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

try {
    // Get order details
    $stmt = $db->prepare("
        SELECT
            pd.*,
            CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
            u.phonenumber AS phone,
            u.email,
            d.name AS driver_name,
            d.phone AS driver_phone
        FROM pending_delivery pd
        JOIN users u ON pd.user_id = u.id
        LEFT JOIN drivers d ON pd.driver_id = d.id
        WHERE pd.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get order items
    $stmt = $db->prepare("
        SELECT
            pdi.*,
            p.name as product_name,
            pp.name as parent_name
        FROM pending_delivery_items pdi
        LEFT JOIN products p ON pdi.product_id = p.id
        LEFT JOIN parent_products pp ON p.parent_id = pp.id
        WHERE pdi.pending_delivery_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the response
    $response = [
        'status' => 'success',
        'order' => $order,
        'items' => $items
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
