<?php
header('Content-Type: application/json');
require_once('../config.php');

try {
    // Fetch all parent products
    $parentQuery = "SELECT * FROM parent_products ORDER BY name";
    $parentStmt = $db->query($parentQuery);
    $parents = $parentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the response array
    $response = [];

    foreach ($parents as $parent) {
        // Fetch all active child products for this parent
        $childQuery = "SELECT p.*
                      FROM products p
                      WHERE p.parent_id = :parent_id
                      ORDER BY p.name";
        $childStmt = $db->prepare($childQuery);
        $childStmt->execute([':parent_id' => $parent['id']]);
        $children = $childStmt->fetchAll(PDO::FETCH_ASSOC);

        // Add children to parent
        $parent['children'] = $children;
        $response[] = $parent;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString() // Added for debugging
    ]);
}
