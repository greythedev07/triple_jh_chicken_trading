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
    $stmt = $db->prepare("UPDATE pending_delivery SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$order_id]);

    echo json_encode(['status' => 'success', 'message' => 'Order cancelled successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
