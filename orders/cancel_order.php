<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);

    if ($order_id <= 0) {
        header("Location: orders.php?error=" . urlencode("Invalid order ID."));
        exit;
    }

    try {
        $db->beginTransaction();

        // Verify that the order belongs to the user
        $stmt = $db->prepare("
            SELECT status, driver_id 
            FROM pending_delivery 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Order not found.");
        }

        // Ensure order is still pending
        if (strtolower($order['status']) !== 'pending') {
            throw new Exception("Only pending orders can be cancelled.");
        }

        // Fetch all ordered items to restore stock
        $itemStmt = $db->prepare("
            SELECT product_id, quantity 
            FROM pending_delivery_items 
            WHERE pending_delivery_id = ?
        ");
        $itemStmt->execute([$order_id]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        // Restore inventory
        $updateStock = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        foreach ($items as $item) {
            $updateStock->execute([$item['quantity'], $item['product_id']]);
        }


        // Mark order as cancelled
        $cancelStmt = $db->prepare("
            UPDATE pending_delivery 
            SET status = 'Cancelled' 
            WHERE id = ? AND user_id = ?
        ");
        $cancelStmt->execute([$order_id, $user_id]);

        $db->commit();

        header("Location: orders.php?success=" . urlencode("Order #$order_id has been cancelled successfully. Stock has been restored."));
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        header("Location: orders.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: orders.php");
    exit;
}
