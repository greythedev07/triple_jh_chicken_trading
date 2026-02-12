<?php
session_start();
require_once('../config.php');
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to update your cart.']);
    exit;
}

// Validate input
if (!isset($_POST['cart_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$cart_id = $_POST['cart_id'];
$quantity = (int)$_POST['quantity'];
$user_id = $_SESSION['user_id'];

if ($quantity < 1) {
    echo json_encode(['status' => 'error', 'message' => 'Quantity must be at least 1.']);
    exit;
}

try {
    // Begin transaction
    $db->beginTransaction();

    // 1. Get current cart item and product details
    $stmt = $db->prepare("
        SELECT c.*, p.stock, p.price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.user_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$cart_id, $user_id]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        throw new Exception('Cart item not found.');
    }

    // 2. Check if requested quantity exceeds available stock
    if ($quantity > $cartItem['stock']) {
        throw new Exception('Requested quantity exceeds available stock.');
    }

    // 3. Update the cart item
    $stmt = $db->prepare("
        UPDATE cart
        SET quantity = ?,
            price = ?,
            updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$quantity, $cartItem['price'], $cart_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Failed to update cart item.');
    }

    // Get updated cart count and subtotal
    $stmt = $db->prepare("SELECT SUM(quantity) as total_items FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cartCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_items'];

    // Get the updated item details for the response
    $stmt = $db->prepare("
        SELECT c.quantity, c.price, (c.quantity * c.price) as subtotal
        FROM cart c
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_id, $user_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get the cart total
    $stmt = $db->prepare("
        SELECT SUM(quantity * price) as cart_total
        FROM cart
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cartTotal = $stmt->fetch(PDO::FETCH_ASSOC)['cart_total'];

    // Commit transaction
    $db->commit();

    // Return success response with updated data
    echo json_encode([
        'status' => 'success',
        'message' => 'Cart updated successfully.',
        'cartCount' => $cartCount,
        'subtotal' => number_format($item['subtotal'], 2, '.', ''),
        'cartTotal' => number_format($cartTotal, 2, '.', '')
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Cart update error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred while updating your cart: ' . $e->getMessage()
    ]);
}
