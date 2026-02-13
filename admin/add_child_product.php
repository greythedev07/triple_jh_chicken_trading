<?php
header('Content-Type: application/json');
require_once('../config.php');

try {
    // Validate required fields
    $required = ['parent_id', 'name', 'price', 'stock', 'weight'];
    foreach ($required as $field) {
        if (empty($_POST[$field]) && $_POST[$field] !== '0') {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Sanitize and validate input
    $parentId = (int)$_POST['parent_id'];
    $name = trim($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $weight = trim($_POST['weight']);

    // Remove any non-numeric characters except decimal point for validation
    $numericWeight = (float) preg_replace('/[^0-9.]/', '', $weight);

    // Get parent's image path
    $parent = $db->prepare("SELECT image FROM parent_products WHERE id = ?");
    $parent->execute([$parentId]);
    $parentImage = $parent->fetchColumn();

    if ($price < 0) {
        throw new Exception('Price cannot be negative');
    }

    if ($stock < 0) {
        throw new Exception('Stock cannot be negative');
    }

    if ($numericWeight <= 0) {
        throw new Exception('Weight must be greater than zero');
    }

    // Check if parent exists
    $parentCheck = $db->prepare("SELECT id FROM parent_products WHERE id = ?");
    $parentCheck->execute([$parentId]);
    if (!$parentCheck->fetch()) {
        throw new Exception('Invalid parent product');
    }

    // Insert the child product
    $query = "INSERT INTO products
              (parent_id, name, price, weight, stock, image)
              VALUES (:parent_id, :name, :price, :weight, :stock, :image)";

    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':parent_id' => $parentId,
        ':name' => $name,
        ':price' => $price,
        ':weight' => $weight,
        ':stock' => $stock,
        ':image' => $parentImage
    ]);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Product variant added successfully',
            'id' => $db->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to add product variant');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
