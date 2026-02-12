<?php
header('Content-Type: application/json');
require_once('../config.php');

try {
    // Validate input
    if (empty($_POST['name'])) {
        throw new Exception('Product name is required');
    }

    // Handle file upload
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/items/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExt, $allowedExts)) {
            throw new Exception('Only JPG, JPEG, PNG & GIF files are allowed');
        }

        $fileName = uniqid() . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/items/' . $fileName;
        }
    }

    // Insert into database
    $query = "INSERT INTO parent_products
              (name, description, image)
              VALUES (:name, :description, :image)";

    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        ':name' => trim($_POST['name']),
        ':description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
        ':image' => $imagePath
    ]);

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Product added successfully',
            'id' => $db->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to add product');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
