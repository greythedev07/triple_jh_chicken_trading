<?php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order ID']);
    exit;
}

try {
    // Only allow delete when status is Cancelled
    $stmt = $db->prepare("SELECT status FROM pending_delivery WHERE id = ?");
    $stmt->execute([$order_id]);
    $status = $stmt->fetchColumn();
    if (!$status) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit;
    }
    if (strcasecmp($status, 'Cancelled') !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'Only cancelled deliveries can be deleted']);
        exit;
    }

    $db->beginTransaction();
    $delItems = $db->prepare("DELETE FROM pending_delivery_items WHERE pending_delivery_id = ?");
    $delItems->execute([$order_id]);
    $delOrder = $db->prepare("DELETE FROM pending_delivery WHERE id = ?");
    $delOrder->execute([$order_id]);
    $db->commit();

    echo json_encode(['status' => 'success', 'message' => 'Cancelled delivery deleted successfully']);
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
