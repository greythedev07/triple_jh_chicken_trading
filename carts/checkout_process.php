<?php
// checkout/checkout_process.php - HANDLES ONLY SELECTED CART ITEMS
session_start();
require_once('../config.php');

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An error occurred'];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to place an order');
    }

    $user_id = $_SESSION['user_id'];

    // Validate required fields
    if (empty($_POST['delivery_address']) || empty($_POST['payment_method'])) {
        throw new Exception('Please provide all required information');
    }

    $delivery_address = trim($_POST['delivery_address']);
    $landmark = trim($_POST['landmark'] ?? '');
    $payment_method = $_POST['payment_method'];
    $gcash_reference = null;
    $gcash_phone = null;

    // Get selected cart items from POST data
    $selected_cart_ids = [];
    if (isset($_POST['selected_items']) && !empty($_POST['selected_items'])) {
        // Handle both string and array formats
        if (is_string($_POST['selected_items'])) {
            $selected_cart_ids = array_filter(array_map('intval', explode(',', $_POST['selected_items'])));
        } else if (is_array($_POST['selected_items'])) {
            $selected_cart_ids = array_filter(array_map('intval', $_POST['selected_items']));
        }
    }

    // Validate that items were selected
    if (empty($selected_cart_ids)) {
        throw new Exception('Please select at least one item to checkout');
    }

    // Validate payment method
    if (!in_array($payment_method, ['cod', 'gcash'])) {
        throw new Exception('Invalid payment method');
    }

    // If GCash, validate reference and phone
    if ($payment_method === 'gcash') {
        if (empty($_POST['gcash_reference']) || empty($_POST['gcash_phone'])) {
            throw new Exception('Please provide GCash reference number and phone number');
        }
        $gcash_reference = trim($_POST['gcash_reference']);
        $gcash_phone = trim($_POST['gcash_phone']);

        // Validate phone format (09XXXXXXXXX)
        if (!preg_match('/^09\d{9}$/', $gcash_phone)) {
            throw new Exception('Invalid phone number. Use format: 09XXXXXXXXX');
        }
    }

    // Start transaction
    $db->beginTransaction();

    // 1. Fetch ONLY selected cart items
    $placeholders = str_repeat('?,', count($selected_cart_ids) - 1) . '?';
    $cart_query = "
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.parent_id,
            c.quantity,
            c.price as cart_price,
            p.name as product_name,
            p.price as current_price,
            p.stock,
            p.is_active
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.id IN ($placeholders)
    ";

    $params = array_merge([$user_id], $selected_cart_ids);
    $stmt = $db->prepare($cart_query);
    $stmt->execute($params);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('Selected cart items not found');
    }

    // Verify we got all selected items
    if (count($cart_items) !== count($selected_cart_ids)) {
        throw new Exception('Some selected items are no longer available');
    }

    // 2. Validate stock and calculate total for selected items ONLY
    $total_amount = 0;
    foreach ($cart_items as $item) {
        if (!$item['is_active']) {
            throw new Exception("Product '{$item['product_name']}' is no longer available");
        }
        if ($item['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for '{$item['product_name']}'. Only {$item['stock']} available");
        }
        $total_amount += $item['cart_price'] * $item['quantity'];
    }

    if ($total_amount <= 0) {
        throw new Exception('Invalid order total');
    }

    // 3. Generate unique order number
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

    // 4. Determine initial order status
    $order_status = ($payment_method === 'gcash') ? 'pending_payment_verification' : 'pending';

    // 5. Create pending_delivery record
    $insert_order = "
        INSERT INTO pending_delivery (
            user_id,
            order_number,
            delivery_address,
            landmark,
            total_amount,
            payment_method,
            gcash_reference,
            status,
            date_requested
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

    $stmt = $db->prepare($insert_order);
    $stmt->execute([
        $user_id,
        $order_number,
        $delivery_address,
        $landmark,
        $total_amount,
        $payment_method,
        $gcash_reference,
        $order_status
    ]);

    $order_id = $db->lastInsertId();

    // 6. Insert order items from selected cart items
    $insert_item = "
        INSERT INTO pending_delivery_items (
            pending_delivery_id,
            product_id,
            quantity,
            price
        ) VALUES (?, ?, ?, ?)
    ";

    $stmt_item = $db->prepare($insert_item);

    // 7. Deduct stock for each selected item
    $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?";
    $stmt_stock = $db->prepare($update_stock);

    foreach ($cart_items as $item) {
        // Insert order item
        $stmt_item->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['cart_price']
        ]);

        // Deduct stock
        $stmt_stock->execute([
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        ]);

        // Verify stock was updated
        if ($stmt_stock->rowCount() === 0) {
            throw new Exception("Failed to update stock for '{$item['product_name']}'");
        }
    }

    // 8. Remove ONLY selected items from cart
    $delete_cart = "DELETE FROM cart WHERE user_id = ? AND id IN ($placeholders)";
    $stmt = $db->prepare($delete_cart);
    $stmt->execute($params);

    // Commit transaction
    $db->commit();

    // Success response
    $response = [
        'status' => 'success',
        'message' => 'Order placed successfully!',
        'order_number' => $order_number,
        'order_id' => $order_id,
        'total_amount' => $total_amount,
        'payment_method' => $payment_method,
        'requires_verification' => ($payment_method === 'gcash')
    ];

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Checkout error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
