<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid product ID']));
}

try {
    // Get current product data
    $stmt = $db->prepare("SELECT id, name, description, price, stock, image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die(json_encode(['status' => 'error', 'message' => 'Product not found']));
    }

    // Handle file upload if new image is provided
    $imagePath = $product['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "../uploads/items/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Delete old image if exists
        if ($imagePath && file_exists('../' . $imagePath)) {
            @unlink('../' . $imagePath);
        }

        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = "uploads/items/" . $fileName;
        }
    }

    // Update product
    $name = $_POST['name'] ?? $product['name'];
    $description = $_POST['description'] ?? $product['description'];
    $price = isset($_POST['price']) ? (float) $_POST['price'] : $product['price'];
    $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : $product['stock'];

    $stmt = $db->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, stock = ?, image = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $description, $price, $stock, $imagePath, $id]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    error_log("Error updating product: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}