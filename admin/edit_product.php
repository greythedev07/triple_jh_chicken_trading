<?php
header('Content-Type: application/json');
require_once('../config.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the incoming request for debugging
error_log('Edit Product Request: ' . print_r($_POST, true));
error_log('Files received: ' . print_r($_FILES, true));

try {
    // Validate required fields
    if (empty($_POST['id']) || !isset($_POST['is_parent'])) {
        throw new Exception('Missing required parameters');
    }

    $id = (int)$_POST['id'];
    $isParent = $_POST['is_parent'] === '1'; // Changed from 'true' string to '1' to match form data

    if ($isParent) {
        // Handle parent product update
        if (empty($_POST['name'])) {
            throw new Exception('Product name is required');
        }

        $updateFields = [
            'name' => trim($_POST['name']),
            'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Handle file upload if new image is provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];

            // Additional file validation
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($fileExt, $allowedExts)) {
                throw new Exception('Only JPG, JPEG, PNG, GIF, and WebP files are allowed');
            }

            if ($file['size'] > $maxFileSize) {
                throw new Exception('File size exceeds maximum limit of 5MB');
            }

            // Check if the file is a valid image
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception('Uploaded file is not a valid image');
            }

            // Delete old image if exists
            $oldImg = $db->prepare("SELECT image FROM parent_products WHERE id = ?");
            $oldImg->execute([$id]);
            $oldImage = $oldImg->fetchColumn();

            if ($oldImage && file_exists('../' . $oldImage)) {
                @unlink('../' . $oldImage);
            }

            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/items/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception('Failed to create upload directory');
                }
            }

            // Generate unique filename
            $fileName = uniqid() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;

            // Move the uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file');
            }

            $updateFields['image'] = 'uploads/items/' . $fileName;
            error_log('File uploaded successfully: ' . $updateFields['image']);
        }

        // Build and execute update query
        $setParts = [];
        $params = [];
        foreach ($updateFields as $field => $value) {
            $setParts[] = "`$field` = :$field";
            $params[":$field"] = $value;
        }
        $params[':id'] = $id;

        $query = "UPDATE parent_products SET " . implode(', ', $setParts) . " WHERE id = :id";
        error_log('Executing query: ' . $query);
        error_log('With params: ' . print_r($params, true));

        $stmt = $db->prepare($query);
        $result = $stmt->execute($params);

        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Database error: ' . ($errorInfo[2] ?? 'Unknown error'));
        }

        $message = 'Product updated successfully';
    } else {
        // Handle child product update
        $required = ['name', 'price', 'stock', 'weight'];
        foreach ($required as $field) {
            if (!isset($_POST[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
            }
        }

        $name = trim($_POST['name']);
        $price = (float)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $weight = trim($_POST['weight']);

        // Get parent's image path
        $parent = $db->prepare("SELECT p.image FROM products child
                              JOIN parent_products p ON child.parent_id = p.id
                              WHERE child.id = ?");
        $parent->execute([$id]);
        $parentImage = $parent->fetchColumn();

        if ($price < 0) {
            throw new Exception('Price cannot be negative');
        }

        if ($stock < 0) {
            throw new Exception('Stock cannot be negative');
        }

        $query = "UPDATE products
                 SET name = :name,
                     price = :price,
                     weight = :weight,
                     stock = :stock,
                     image = :image,
                     updated_at = NOW()
                 WHERE id = :id";

        $params = [
            ':name' => $name,
            ':price' => $price,
            ':weight' => $weight,
            ':stock' => $stock,
            ':image' => $parentImage,
            ':id' => $id
        ];

        error_log('Updating variant with query: ' . $query);
        error_log('Params: ' . print_r($params, true));

        $stmt = $db->prepare($query);
        $result = $stmt->execute($params);

        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('Database error: ' . ($errorInfo[2] ?? 'Unknown error'));
        }

        $message = 'Product variant updated successfully';
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(400);
    $errorMessage = $e->getMessage();
    error_log('Error in edit_product.php: ' . $errorMessage);

    echo json_encode([
        'status' => 'error',
        'message' => $errorMessage
    ]);
}
