<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Read actor and target IDs
    $driver_id = $_SESSION['driver_id'];
    $pending_delivery_id = (int)($_POST['pending_delivery_id'] ?? 0);

    // 2) Save pickup proof to uploads/pickups and remember relative path
    $proofPath = null;
    if (!empty($_FILES['pickup_proof']['name'])) {
        $uploadDir = '../uploads/pickups/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['pickup_proof']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['pickup_proof']['tmp_name'], $targetFile)) {
            $proofPath = 'uploads/pickups/' . $fileName;
        }
    }

    // 3) Load pending delivery and verify this driver is assigned
    $stmt = $db->prepare("SELECT * FROM pending_delivery WHERE id = ? AND driver_id = ?");
    $stmt->execute([$pending_delivery_id, $driver_id]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pending) {
        // 4) Create a to_be_delivered record
        $insert = $db->prepare("INSERT INTO to_be_delivered (pending_delivery_id, driver_id, user_id, delivery_address, pickup_proof, pickup_time) VALUES (?, ?, ?, ?, ?, NOW())");
        $insert->execute([$pending_delivery_id, $driver_id, $pending['user_id'], $pending['delivery_address'], $proofPath]);
        $toBeDeliveredId = $db->lastInsertId();

        // 5) Copy all items from pending_delivery_items to to_be_delivered_items
        $items = $db->prepare("SELECT * FROM pending_delivery_items WHERE pending_delivery_id = ?");
        $items->execute([$pending_delivery_id]);
        $items = $items->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            error_log("No items found in pending_delivery_items for pending_delivery_id: " . $pending_delivery_id);

            // Try to get items directly from the cart as a fallback
            $cartItems = $db->prepare("
                SELECT ci.product_id, ci.quantity, p.price
                FROM cart_items ci
                JOIN cart c ON ci.cart_id = c.id
                JOIN products p ON ci.product_id = p.id
                WHERE c.pending_delivery_id = ?
            ");
            $cartItems->execute([$pending_delivery_id]);
            $items = $cartItems->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                error_log("No items found in cart for pending_delivery_id: " . $pending_delivery_id);
                header("Location: ../driver_dashboard.php?error=no_items");
                exit;
            }
        }

        // Log the items being copied
        error_log("Copying " . count($items) . " items to to_be_delivered_items for delivery " . $toBeDeliveredId);

        // Insert items into to_be_delivered_items
        $insertItem = $db->prepare("INSERT INTO to_be_delivered_items (to_be_delivered_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $itemsInserted = 0;

        foreach ($items as $item) {
            try {
                $result = $insertItem->execute([
                    $toBeDeliveredId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);

                if ($result) {
                    $itemsInserted++;
                    error_log(sprintf(
                        "Inserted item - Product ID: %d, Quantity: %d, Price: %.2f",
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    ));
                } else {
                    $errorInfo = $insertItem->errorInfo();
                    error_log("Failed to insert item: " . print_r($errorInfo, true));
                }
            } catch (PDOException $e) {
                error_log("Error inserting item: " . $e->getMessage());
            }
        }

        if ($itemsInserted === 0) {
            error_log("No items were inserted into to_be_delivered_items");
            header("Location: ../driver_dashboard.php?error=insert_failed");
            exit;
        }

        error_log("Successfully inserted $itemsInserted items into to_be_delivered_items");

        // 6) Mark the pending delivery as ready to deliver
        $update = $db->prepare("UPDATE pending_delivery SET status = 'to be delivered' WHERE id = ?");
        $update->execute([$pending_delivery_id]);

        // Log successful pickup
        error_log("Successfully picked up order #$pending_delivery_id. Items copied: " . count($items));

        // 7) Return to dashboard
        header("Location: ../driver_dashboard.php?pickup=1");
        exit;
    } else {
        die("Invalid pending delivery.");
    }
}
