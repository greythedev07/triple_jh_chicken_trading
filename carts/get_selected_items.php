<?php
// checkout/get_selected_items.php - Fetch only selected cart items
session_start();
require_once('../config.php');

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An error occurred'];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in');
    }

    $user_id = $_SESSION['user_id'];

    // Get selected cart IDs
    if (!isset($_POST['cart_ids']) || empty($_POST['cart_ids'])) {
        throw new Exception('No items selected');
    }

    $cart_ids = array_filter(array_map('intval', explode(',', $_POST['cart_ids'])));

    if (empty($cart_ids)) {
        throw new Exception('Invalid cart items');
    }

    // Build query with placeholders
    $placeholders = str_repeat('?,', count($cart_ids) - 1) . '?';
    
    $query = "
        SELECT 
            c.id as cart_id,
            c.product_id,
            c.quantity,
            c.price,
            p.name as product_name,
            p.stock,
            p.is_active
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND c.id IN ($placeholders)
    ";

    $params = array_merge([$user_id], $cart_ids);
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception('Selected items not found');
    }

    // Calculate total
    $total = 0;
    foreach ($items as $item) {
        if (!$item['is_active']) {
            throw new Exception("Product '{$item['product_name']}' is no longer available");
        }
        if ($item['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for '{$item['product_name']}'");
        }
        $total += $item['price'] * $item['quantity'];
    }

    $response = [
        'status' => 'success',
        'items' => $items,
        'total' => $total,
        'count' => count($items)
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
