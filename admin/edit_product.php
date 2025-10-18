<?php
require_once('../config.php');

$id = $_POST['id'];
$name = isset($_POST['name']) ? $_POST['name'] : null;
$price = isset($_POST['price']) ? $_POST['price'] : null;
$stock = isset($_POST['stock']) ? $_POST['stock'] : null;
$imagePath = '';

if (!empty($_FILES['image']['name'])) {
    $targetDir = "../uploads/items/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $targetFile = $targetDir . $fileName;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        $imagePath = "uploads/items/" . $fileName;
    }
    // Step 1: Update with new image
    $stmt = $db->prepare("UPDATE products SET name=COALESCE(?, name), price=COALESCE(?, price), stock=COALESCE(?, stock), image=? WHERE id=?");
    $ok = $stmt->execute([$name, $price, $stock, $imagePath, $id]);
} else {
    // Step 2: Update without changing image
    $stmt = $db->prepare("UPDATE products SET name=COALESCE(?, name), price=COALESCE(?, price), stock=COALESCE(?, stock) WHERE id=?");
    $ok = $stmt->execute([$name, $price, $stock, $id]);
}

echo json_encode(['status' => $ok ? 'success' : 'error']);
