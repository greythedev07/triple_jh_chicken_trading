<?php
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Match the input names from your admin_dashboard.php form
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $imagePath = null;

    // Step 1: Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $targetDir = '../uploads/items/';
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        // Step 2: Validate file type and upload
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/items/' . $fileName;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid image format.']);
            exit;
        }
    }

    try {
        // Step 3: Insert new product
        $stmt = $db->prepare("INSERT INTO products (name, price, stock, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $price, $stock, $imagePath]);

        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
