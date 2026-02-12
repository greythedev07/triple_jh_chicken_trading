<?php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Optional: verify admin session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$order_id = $_POST['order_id'] ?? 0;
if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order ID']);
    exit;
}

try {
    // Start transaction for data integrity
    $db->beginTransaction();

    // Get the order items to restore stock
    $stmt = $db->prepare("
        SELECT pdi.product_id, pdi.quantity
        FROM pending_delivery_items pdi
        WHERE pdi.pending_delivery_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($order_items)) {
        $db->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Order not found or has no items']);
        exit;
    }

    // Restore stock for each item
    foreach ($order_items as $item) {
        $update_stock = $db->prepare("
            UPDATE products
            SET stock = stock + ?
            WHERE id = ?
        ");
        $update_stock->execute([$item['quantity'], $item['product_id']]);
    }

    // Update order status to cancelled
    $stmt = $db->prepare("UPDATE pending_delivery SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$order_id]);

    // Commit the transaction
    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Order cancelled and stock restored successfully'
    ]);

} catch (PDOException $e) {
    // Roll back transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
