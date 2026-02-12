<?php
// admin/assign_driver.php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Verify admin session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$pending_id = isset($_POST['pending_id']) ? (int)$_POST['pending_id'] : 0;
$driver_id = isset($_POST['driver_id']) ? (int)$_POST['driver_id'] : 0;

if (!$pending_id || !$driver_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

try {
    // First, verify the driver is active
    $driverCheck = $db->prepare("SELECT is_active FROM drivers WHERE id = ?");
    $driverCheck->execute([$driver_id]);
    $driver = $driverCheck->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        throw new Exception('Driver not found');
    }

    if (!$driver['is_active']) {
        throw new Exception('Cannot assign delivery to an inactive driver');
    }

    $db->beginTransaction();

    // Attach driver and update status to 'assigned' to remove from pickup tab
    $upd = $db->prepare("UPDATE pending_delivery SET driver_id = ?, status = 'assigned' WHERE id = ?");
    $upd->execute([$driver_id, $pending_id]);

    // Create to_be_delivered row (if not already created)
    $check = $db->prepare("SELECT id FROM to_be_delivered WHERE pending_delivery_id = ?");
    $check->execute([$pending_id]);
    $exists = $check->fetch(PDO::FETCH_COLUMN);

    if (!$exists) {
        // fetch pending_delivery base info
        $pd = $db->prepare("SELECT user_id, delivery_address FROM pending_delivery WHERE id = ?");
        $pd->execute([$pending_id]);
        $row = $pd->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception('Pending delivery not found');
        }

        $ins = $db->prepare("INSERT INTO to_be_delivered (pending_delivery_id, driver_id, user_id, delivery_address, status) VALUES (?, ?, ?, ?, 'pending')");
        $ins->execute([$pending_id, $driver_id, $row['user_id'], $row['delivery_address']]);
        $tbd_id = $db->lastInsertId();

        // copy items
        $items = $db->prepare("SELECT product_id, quantity, price FROM pending_delivery_items WHERE pending_delivery_id = ?");
        $items->execute([$pending_id]);
        $copy = $db->prepare("INSERT INTO to_be_delivered_items (to_be_delivered_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        while ($it = $items->fetch(PDO::FETCH_ASSOC)) {
            $copy->execute([$tbd_id, $it['product_id'], $it['quantity'], $it['price']]);
        }
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => 'Driver assigned successfully']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
