<?php
session_start();
require_once('../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
    exit;
}

try {
    // First, verify that the user exists in the database
    $user_check = $db->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->execute([$user_id]);
    if (!$user_check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'User not found. Please login again.']);
        exit;
    }

    // Verify that the product exists
    $product_check = $db->prepare("SELECT id, stock FROM products WHERE id = ?");
    $product_check->execute([$product_id]);
    $product = $product_check->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit;
    }

    // Check if product is in stock
    if ($product['stock'] <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Product is out of stock.']);
        exit;
    }

    // check if already in cart
    $check = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $check->execute([$user_id, $product_id]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $newQty = $row['quantity'] + 1;
        // Check if adding one more would exceed stock
        if ($newQty > $product['stock']) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot add more items. Only ' . $product['stock'] . ' available in stock.']);
            exit;
        }
        $upd = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $upd->execute([$newQty, $row['id']]);
    } else {
        $ins = $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $ins->execute([$user_id, $product_id]);
    }

    // fetch total count
    $count = $db->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $count->execute([$user_id]);
    $cart_count = (int)$count->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'message' => 'Item added to cart!',
        'cart_count' => $cart_count
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
