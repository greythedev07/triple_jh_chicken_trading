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
    $payment_received = floatval($_POST['payment_received']);
    $change_given = floatval($_POST['change_given']);

    // Get current timestamp in MySQL format
    $current_timestamp = date('Y-m-d H:i:s');

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
        // Check if this delivery has already been completed to prevent duplicates
        $checkExisting = $db->prepare("SELECT id FROM history_of_delivery WHERE to_be_delivered_id = ?");
        $checkExisting->execute([$to_be_delivered_id]);
        if ($checkExisting->fetch()) {
            // Already exists in history, redirect back
            header("Location: ../driver_dashboard.php?delivery_completed=1");
            exit;
        }

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
            error_log("Attempting to insert into history_of_delivery with data: " . print_r([
                'to_be_delivered_id' => $to_be_delivered_id,
                'driver_id' => $driver_id,
                'user_id' => $delivery['user_id'],
                'order_number' => $orderInfo['order_number'],
                'payment_method' => $orderInfo['payment_method'],
                'delivery_address' => $delivery['delivery_address'],
                'payment_received' => $payment_received,
                'change_given' => $change_given,
                'delivery_time' => $current_timestamp,
                'proof_image' => $proofPath
            ], true));

            // Log the values being inserted for debugging
            error_log("Inserting into history_of_delivery with current time: " . $current_timestamp);

            // First, let's verify all required fields have values
            $requiredFields = [
                'to_be_delivered_id' => $to_be_delivered_id,
                'driver_id' => $driver_id,
                'user_id' => $delivery['user_id'],
                'order_number' => $orderInfo['order_number'],
                'payment_method' => $orderInfo['payment_method'],
                'delivery_address' => $delivery['delivery_address'],
                'payment_received' => $payment_received,
                'change_given' => $change_given,
                'delivery_time' => $current_timestamp,
                'proof_image' => $proofPath
            ];

            // Log all values for debugging
            error_log("Insert values: " . print_r($requiredFields, true));

            // Build the SQL query dynamically to only include provided fields
            $columns = [];
            $placeholders = [];
            $values = [];

            foreach ($requiredFields as $column => $value) {
                if ($value !== null) {
                    $columns[] = $column;
                    $placeholders[] = '?';
                    $values[] = $value;
                }
            }

            $sql = "INSERT INTO history_of_delivery (" . implode(', ', $columns) . ")
                    VALUES (" . implode(', ', $placeholders) . ")";

            error_log("Executing SQL: $sql");

            // Prepare and execute the query
            $insert = $db->prepare($sql);
            $result = $insert->execute($values);
            $historyId = $db->lastInsertId();

            if ($result && $historyId) {
                error_log("Successfully inserted into history_of_delivery. History ID: $historyId");
            } else {
                $errorInfo = $insert->errorInfo();
                error_log("Failed to insert into history_of_delivery. Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to insert into history_of_delivery: " . ($errorInfo[2] ?? 'Unknown error'));
            }

            // Step 6: Copy delivery items to history
            $itemsStmt = $db->prepare("SELECT * FROM to_be_delivered_items WHERE to_be_delivered_id = ?");
            $itemsStmt->execute([$to_be_delivered_id]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Found " . count($items) . " items to copy to history");

            if (count($items) == 0) {
                throw new Exception("No items found in to_be_delivered_items for delivery");
            }

            foreach ($items as $item) {
                error_log("Inserting item: product_id=" . $item['product_id'] . ", quantity=" . $item['quantity']);
                $insertItem = $db->prepare("INSERT INTO history_of_delivery_items (history_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $insertItem->execute([$historyId, $item['product_id'], $item['quantity'], $item['price']]);
            }

            error_log("Successfully copied all items to history");

            // Step 7: Clean up intermediate tables to prevent duplicate display
            // IMPORTANT: Only delete AFTER history records are successfully created

            error_log("Starting cleanup of intermediate tables");

            // Delete to_be_delivered_items (already copied to history)
            $deleteToBeDeliveredItems = $db->prepare("DELETE FROM to_be_delivered_items WHERE to_be_delivered_id = ?");
            $deleteToBeDeliveredItems->execute([$to_be_delivered_id]);
            error_log("Deleted to_be_delivered_items");

            // Mark the delivery records as completed instead of deleting them to preserve history references
            $updateToBeDelivered = $db->prepare("UPDATE to_be_delivered SET status = 'delivered' WHERE id = ?");
            $updateToBeDelivered->execute([$to_be_delivered_id]);
            error_log("Marked to_be_delivered as delivered");

            $updatePending = $db->prepare("UPDATE pending_delivery SET status = 'delivered' WHERE id = ?");
            $updatePending->execute([$delivery['pending_delivery_id']]);
            error_log("Marked pending_delivery as delivered");

            // Commit the transaction
            $db->commit();
            error_log("Transaction committed successfully!");

            header("Location: ../driver_dashboard.php?delivery_completed=1");
            exit;
        } catch (Exception $e) {
            // Rollback on error
            error_log("Delivery process error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            if ($db->inTransaction()) {
                $db->rollBack();
                error_log("Transaction rolled back");
            }

            // Better error reporting for debugging
            die("Delivery failed: " . htmlspecialchars($e->getMessage()) . "<br>Please check the error log for more details.");
        }
    } else {
        die("Invalid delivery.");
    }
}
