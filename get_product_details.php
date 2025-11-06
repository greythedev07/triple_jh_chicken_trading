<?php
// get_product_details.php - API endpoint to fetch product details

// Enable error reporting at the very top
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include config
session_start();
require_once(__DIR__ . '/config.php');

// Set JSON content type
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Please log in to view product details'
    ], 401);
}

// Get product ID from request
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($product_id <= 0) {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Invalid product ID'
    ], 400);
}

try {
    // Verify database connection
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Database connection not properly initialized');
    }

    // Get product details (removed category join)
    $stmt = $db->prepare("
        SELECT * 
        FROM products 
        WHERE id = ?
    ");

    if (!$stmt->execute([$product_id])) {
        throw new Exception('Failed to execute product query');
    }

    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Product not found'
        ], 404);
    }

    // Get all products except the current one (removed category filter)
    $relatedStmt = $db->prepare("
        SELECT id, name, price, image 
        FROM products 
        WHERE id != ? AND stock > 0 
        ORDER BY RAND() 
        LIMIT 4
    ");

    if (!$relatedStmt->execute([$product_id])) {
        throw new Exception('Failed to execute related products query');
    }

    $related_products = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response
    $response = [
        'status' => 'success',
        'product' => $product,
        'related_products' => $related_products
    ];

    sendJsonResponse($response);

} catch (PDOException $e) {
    error_log('Database error in get_product_details.php: ' . $e->getMessage());
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ], 500);
} catch (Exception $e) {
    error_log('Error in get_product_details.php: ' . $e->getMessage());
    sendJsonResponse([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 500);
}