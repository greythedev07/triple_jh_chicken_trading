<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? 0;
$stock = (int) ($_POST['stock'] ?? 0);
$description = $_POST['description'] ?? '';

if (empty($name) || $price <= 0) {
    die(json_encode(['status' => 'error', 'message' => 'Name and price are required']));
}

try {
    // Handle file upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/items/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = "uploads/items/" . $fileName;
        }
    }

    // Insert into database
    $stmt = $db->prepare("
        INSERT INTO products (name, description, price, stock, image, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $description, $price, $stock, $imagePath]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    error_log("Error adding product: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}