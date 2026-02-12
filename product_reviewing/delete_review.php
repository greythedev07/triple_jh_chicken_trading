<?php
// delete_review.php
require_once('../config.php');
session_start();

header('Content-Type: application/json');  // Set JSON header

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    // Check if review_id is provided
    if (!isset($_POST['review_id'])) {
        throw new Exception('Review ID is required', 400);
    }

    $review_id = (int)$_POST['review_id'];
    $user_id = (int)$_SESSION['user_id'];

    // Delete the review
    $stmt = $db->prepare("
        DELETE FROM product_reviews
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$review_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Review deleted successfully']);
    } else {
        throw new Exception('Review not found or access denied', 404);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
