<?php
// Enable output buffering to prevent any accidental output
ob_start();

session_start();
require_once('../config.php');

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'newCartCount' => 0
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to add items to cart');
    }
    $user_id = $_SESSION['user_id'];

    // Validate input
    if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
        throw new Exception('Invalid product');
    }

    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    // Start transaction
    $db->beginTransaction();

    // 1. Verify product exists and is active
    $product_query = "
        SELECT p.*,
               COALESCE(pp.id, p.id) as display_id,
               COALESCE(pp.name, p.name) as display_name
        FROM products p
        LEFT JOIN parent_products pp ON p.parent_id = pp.id
        WHERE p.id = ? AND p.is_active = 1
    ";
    $stmt = $db->prepare($product_query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found or not available');
    }

    // 2. If parent_id was provided, verify it matches the product's parent
    if ($parent_id && $product['parent_id'] && $product['parent_id'] != $parent_id) {
        throw new Exception('Invalid product variant');
    }

    // 3. Check stock
    if ($product['stock'] < $quantity) {
        throw new Exception('Not enough stock available');
    }

    // 4. Check if this exact product is already in the cart
    $cart_check = $db->prepare("
        SELECT id, quantity
        FROM cart
        WHERE user_id = ? AND product_id = ?
    ");
    $cart_check->execute([$user_id, $product_id]);
    $cart_item = $cart_check->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // Update existing cart item
        $new_quantity = $cart_item['quantity'] + $quantity;

        // Verify stock allows this quantity
        if ($new_quantity > $product['stock']) {
            throw new Exception('Cannot add more items than available in stock');
        }

        $update = $db->prepare("
            UPDATE cart
            SET quantity = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$new_quantity, $cart_item['id']]);

        $message = 'Cart updated successfully';
    } else {
        // Add new item to cart
        $insert = $db->prepare("
            INSERT INTO cart (
                user_id,
                product_id,
                parent_id,
                quantity,
                price,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        // Use the provided parent_id from the form, fallback to product's parent_id
        $insert_parent_id = $parent_id ?? $product['parent_id'] ?? null;

        $insert->execute([
            $user_id,
            $product_id,
            $insert_parent_id,
            $quantity,
            $product['price']
        ]);

        $message = 'Item added to cart';
    }

    // Get updated cart count
    $count = $db->query("SELECT COUNT(*) FROM cart WHERE user_id = $user_id")->fetchColumn();

    $db->commit();

    $response = [
        'status' => 'success',
        'message' => $message,
        'newCartCount' => (int)$count
    ];

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = $e->getMessage();
}

// Clean any output before sending JSON
ob_end_clean();
echo json_encode($response);
