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
        // Start transaction to ensure data integrity
        $db->beginTransaction();

        try {
            // Step 4: Get order details from pending_delivery
            $orderDetails = $db->prepare("SELECT order_number, payment_method FROM pending_delivery WHERE id = ?");
            $orderDetails->execute([$delivery['pending_delivery_id']]);
            $orderInfo = $orderDetails->fetch(PDO::FETCH_ASSOC);

            if (!$orderInfo) {
                throw new Exception("Order details not found in pending_delivery table");
            }

            // Step 5: Move to delivery history with preserved order details
            $insert = $db->prepare("INSERT INTO history_of_delivery (to_be_delivered_id, driver_id, user_id, order_number, payment_method, delivery_address, payment_received, change_given, delivery_time, proof_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([$to_be_delivered_id, $driver_id, $delivery['user_id'], $orderInfo['order_number'], $orderInfo['payment_method'], $delivery['delivery_address'], $payment_received, $change_given, $delivery_time, $proofPath]);
            $historyId = $db->lastInsertId();

            // Step 6: Copy delivery items to history
            $items = $db->prepare("SELECT * FROM to_be_delivered_items WHERE to_be_delivered_id = ?");
            $items->execute([$to_be_delivered_id]);
            foreach ($items as $item) {
                $insertItem = $db->prepare("INSERT INTO history_of_delivery_items (history_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $insertItem->execute([$historyId, $item['product_id'], $item['quantity'], $item['price']]);
            }

            // Step 7: Mark delivery as completed
            $update = $db->prepare("UPDATE to_be_delivered SET status = 'delivered' WHERE id = ?");
            $update->execute([$to_be_delivered_id]);

            // Step 8: Update pending_delivery status to 'delivered' (DO NOT DELETE)
            $updatePending = $db->prepare("UPDATE pending_delivery SET status = 'delivered' WHERE id = ?");
            $updatePending->execute([$delivery['pending_delivery_id']]);

            // Commit the transaction
            $db->commit();

            header("Location: ../driver_dashboard.php?delivery_completed=1");
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $db->rollBack();
            die("Delivery failed: " . $e->getMessage());
        }
    } else {
        die("Invalid delivery.");
    }
}
