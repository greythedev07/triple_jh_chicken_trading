<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

function sendJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    sendJson(['status' => 'error', 'message' => 'Please log in to submit a review.'], 401);
}

$userId    = (int) $_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating    = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$comment   = trim($_POST['comment'] ?? '');

if ($productId <= 0 || $rating < 1 || $rating > 5) {
    sendJson(['status' => 'error', 'message' => 'Invalid product or rating.'], 400);
}

try {
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Database not initialized');
    }

    // Ensure product exists and get parent ID if it's a variant
    $productStmt = $db->prepare('SELECT id, COALESCE(parent_id, id) as review_product_id FROM products WHERE id = ? LIMIT 1');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        sendJson(['status' => 'error', 'message' => 'Product not found.'], 404);
    }

    // Use parent product ID for reviews if this is a variant
    $reviewProductId = $product['review_product_id'];

    // Insert or update review (one per user per product)
    $stmt = $db->prepare("
        INSERT INTO product_reviews (product_id, user_id, rating, comment)
        VALUES (:product_id, :user_id, :rating, :comment)
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            comment = VALUES(comment),
            updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':product_id' => $reviewProductId,
        ':user_id'    => $userId,
        ':rating'     => $rating,
        ':comment'    => $comment,
    ]);

    sendJson(['status' => 'success']);
} catch (Exception $e) {
    error_log('submit_product_review error: ' . $e->getMessage());
    sendJson(['status' => 'error', 'message' => 'Failed to save review.'], 500);
}
