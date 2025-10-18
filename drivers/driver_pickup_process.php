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

        // 5) Copy all items from pending to to_be_delivered
        $items = $db->prepare("SELECT * FROM pending_delivery_items WHERE pending_delivery_id = ?");
        $items->execute([$pending_delivery_id]);
        foreach ($items as $item) {
            $insertItem = $db->prepare("INSERT INTO to_be_delivered_items (to_be_delivered_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $insertItem->execute([$toBeDeliveredId, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // 6) Mark the pending delivery as ready to deliver
        $update = $db->prepare("UPDATE pending_delivery SET status = 'to be delivered' WHERE id = ?");
        $update->execute([$pending_delivery_id]);

        // 7) Return to dashboard
        header("Location: ../driver_dashboard.php?pickup=1");
        exit;
    } else {
        die("Invalid pending delivery.");
    }
}
