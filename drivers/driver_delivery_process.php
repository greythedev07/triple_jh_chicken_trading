<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Read form data
    $driver_id = $_SESSION['driver_id'];
    $to_be_delivered_id = $_POST['to_be_delivered_id'];
    $payment_received = $_POST['payment_received'];
    $change_given = $_POST['change_given'];
    $delivery_time = $_POST['delivery_time'];

    // Step 2: Save delivery proof image
    $proofPath = null;
    if (!empty($_FILES['proof_image']['name'])) {
        $uploadDir = '../uploads/deliveries/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['proof_image']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $targetFile)) {
            $proofPath = 'uploads/deliveries/' . $fileName;
        }
    }

    // Step 3: Load delivery details
    $stmt = $db->prepare("SELECT * FROM to_be_delivered WHERE id = ?");
    $stmt->execute([$to_be_delivered_id]);
    $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($delivery) {
        // Step 4: Move to delivery history
        $insert = $db->prepare("INSERT INTO history_of_delivery (to_be_delivered_id, driver_id, user_id, delivery_address, payment_received, change_given, delivery_time, proof_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$to_be_delivered_id, $driver_id, $delivery['user_id'], $delivery['delivery_address'], $payment_received, $change_given, $delivery_time, $proofPath]);
        $historyId = $db->lastInsertId();

        // Step 5: Copy delivery items to history
        $items = $db->prepare("SELECT * FROM to_be_delivered_items WHERE to_be_delivered_id = ?");
        $items->execute([$to_be_delivered_id]);
        foreach ($items as $item) {
            $insertItem = $db->prepare("INSERT INTO history_of_delivery_items (history_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $insertItem->execute([$historyId, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Step 6: Mark delivery as completed and move to history
        $update = $db->prepare("UPDATE to_be_delivered SET status = 'delivered' WHERE id = ?");
        $update->execute([$to_be_delivered_id]);

        // Step 7: Update pending_delivery status to 'delivered'
        $updatePending = $db->prepare("UPDATE pending_delivery SET status = 'delivered' WHERE id = ?");
        $updatePending->execute([$delivery['pending_delivery_id']]);

        // Step 8: Clean up - Remove from active tables
        // Remove from pending_delivery_items first (foreign key constraint)
        $deleteItems = $db->prepare("DELETE FROM pending_delivery_items WHERE pending_delivery_id = ?");
        $deleteItems->execute([$delivery['pending_delivery_id']]);

        // Remove from pending_delivery
        $deletePending = $db->prepare("DELETE FROM pending_delivery WHERE id = ?");
        $deletePending->execute([$delivery['pending_delivery_id']]);

        // Remove from to_be_delivered_items first (foreign key constraint)
        $deleteTbdItems = $db->prepare("DELETE FROM to_be_delivered_items WHERE to_be_delivered_id = ?");
        $deleteTbdItems->execute([$to_be_delivered_id]);

        // Remove from to_be_delivered (now safe since we removed the problematic foreign key constraint)
        $deleteTbd = $db->prepare("DELETE FROM to_be_delivered WHERE id = ?");
        $deleteTbd->execute([$to_be_delivered_id]);

        header("Location: ../driver_dashboard.php");
        exit;
    } else {
        die("Invalid delivery.");
    }
}
