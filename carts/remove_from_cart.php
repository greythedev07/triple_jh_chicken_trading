<?php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../useraccounts/login.php');
    exit;
}

// Validate input
if (!isset($_POST['cart_id'])) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: cart.php');
    exit;
}

$cart_id = $_POST['cart_id'];
$user_id = $_SESSION['user_id'];

try {
    // Begin transaction
    $db->beginTransaction();

    // Delete the cart item
    $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Cart item not found or you do not have permission to remove it.');
    }

    // Get updated cart count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Update session cart count
    $_SESSION['cart_count'] = $cartCount;

    // Commit transaction
    $db->commit();

    $_SESSION['success'] = 'Item removed from cart.';

    // If this is an AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'status' => 'success',
            'message' => 'Item removed from cart.',
            'newCartCount' => $cartCount
        ]);
        exit;
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Remove from cart error: " . $e->getMessage());

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode([
            'status' => 'error',
            'message' => 'An error occurred while removing the item: ' . $e->getMessage()
        ]);
        exit;
    }

    $_SESSION['error'] = 'An error occurred: ' . $e->getMessage();
}

// Redirect back to cart page for non-AJAX requests
header('Location: cart.php');
exit;
