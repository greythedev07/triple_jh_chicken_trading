<?php
// admin/verify_gcash_payment.php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

$pending_id = isset($_POST['pending_id']) ? (int)$_POST['pending_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if (!$pending_id || !in_array($action, ['verify', 'reject'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db->beginTransaction();

    // Get the pending delivery details
    $stmt = $db->prepare("
        SELECT id, user_id, payment_method, gcash_reference, total_amount, delivery_address
        FROM pending_delivery
        WHERE id = ? AND payment_method = 'GCash' AND payment_status = 'pending'
    ");
    $stmt->execute([$pending_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found or not eligible for verification');
    }

    if ($action === 'verify') {
        // Ensure there is at least one active driver
        $driverCountStmt = $db->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1");
        $activeDriverCount = (int) $driverCountStmt->fetchColumn();
        if ($activeDriverCount === 0) {
            throw new Exception('Cannot verify payment: no active drivers are available. Please create or activate a driver first.');
        }

        // Update payment status to verified
        $updateStmt = $db->prepare("UPDATE pending_delivery SET payment_status = 'verified' WHERE id = ?");
        $updateStmt->execute([$pending_id]);

        // Choose driver with lightest load among active drivers
        $driverStmt = $db->query("
            SELECT id FROM drivers
            WHERE is_active = TRUE
            ORDER BY (
                SELECT COUNT(*) FROM pending_delivery
                WHERE driver_id = drivers.id AND status = 'pending'
            ) ASC
            LIMIT 1
        ");
        $assignedDriver = $driverStmt->fetchColumn();

        if ($assignedDriver) {
            // Assign driver to the order and update status to 'assigned'
            $assignStmt = $db->prepare("UPDATE pending_delivery SET driver_id = ?, status = 'assigned' WHERE id = ?");
            $assignStmt->execute([$assignedDriver, $pending_id]);

            // Create to_be_delivered record
            $check = $db->prepare("SELECT id FROM to_be_delivered WHERE pending_delivery_id = ?");
            $check->execute([$pending_id]);
            $exists = $check->fetch(PDO::FETCH_COLUMN);

            if (!$exists) {
                $ins = $db->prepare("
                    INSERT INTO to_be_delivered (pending_delivery_id, driver_id, user_id, delivery_address, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $ins->execute([$pending_id, $assignedDriver, $order['user_id'], $order['delivery_address']]);
                $tbd_id = $db->lastInsertId();

                // Copy items to to_be_delivered_items
                $items = $db->prepare("
                    SELECT product_id, quantity, price
                    FROM pending_delivery_items
                    WHERE pending_delivery_id = ?
                ");
                $items->execute([$pending_id]);
                $copy = $db->prepare("
                    INSERT INTO to_be_delivered_items (to_be_delivered_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
                    $copy->execute([$tbd_id, $item['product_id'], $item['quantity'], $item['price']]);
                }
            }
        }

        $message = 'GCash payment verified and driver assigned successfully';
    } else { // reject
        // Restore product stock before deleting
        $itemsStmt = $db->prepare("
            SELECT product_id, quantity
            FROM pending_delivery_items
            WHERE pending_delivery_id = ?
        ");
        $itemsStmt->execute([$pending_id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            $restoreStmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $restoreStmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Delete the order items
        $deleteItemsStmt = $db->prepare("DELETE FROM pending_delivery_items WHERE pending_delivery_id = ?");
        $deleteItemsStmt->execute([$pending_id]);

        // Delete the order
        $deleteOrderStmt = $db->prepare("DELETE FROM pending_delivery WHERE id = ?");
        $deleteOrderStmt->execute([$pending_id]);

        $message = 'GCash payment rejected and order deleted with stock restored';
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => $message]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
