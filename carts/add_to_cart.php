<?php
session_start();
require_once('../config.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$variant_id = isset($_POST['variant_id']) ? (int) $_POST['variant_id'] : null;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    // Verify that the user exists
    $user_check = $db->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->execute([$user_id]);
    if (!$user_check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'User not found. Please login again.']);
        exit;
    }

    // Inspect cart table schema so we only reference supported columns
    $cartColumnsData = $db->query('DESCRIBE cart')->fetchAll(PDO::FETCH_ASSOC);
    $cartColumns = array_column($cartColumnsData, 'Field');
    $supportsVariant = in_array('variant_id', $cartColumns, true);
    $supportsPrice = in_array('price', $cartColumns, true);
    $supportsVariantInfo = in_array('variant_info', $cartColumns, true);
    $supportsCreatedAt = in_array('created_at', $cartColumns, true);
    $supportsUpdatedAt = in_array('updated_at', $cartColumns, true);

    // If the table does not support variants, ensure we don't attempt to store an ID
    if (!$supportsVariant) {
        $variant_id = null;
    }

    // Start transaction
    $db->beginTransaction();

    if ($variant_id) {
        // Check product with variant
        $product_check = $db->prepare("
            SELECT p.id, p.name, p.price,
                   v.id as variant_id, v.weight, v.price_adjustment,
                   v.stock as variant_stock, p.image
            FROM products p
            JOIN product_weight_variants v ON p.id = v.product_id
            WHERE p.id = ? AND v.id = ? AND v.stock > 0
        ");
        $product_check->execute([$product_id, $variant_id]);
        $product = $product_check->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Selected variant is not available.']);
            exit;
        }

        $final_price = $product['price'] + $product['price_adjustment'];
        $stock = (int) $product['variant_stock'];
        $variant_info = [
            'variant_id' => $product['variant_id'],
            'weight' => $product['weight'],
            'price_adjustment' => $product['price_adjustment']
        ];
    } else {
        // Check product without variant
        $product_check = $db->prepare("
            SELECT id, name, price, stock, image
            FROM products
            WHERE id = ? AND stock > 0
        ");
        $product_check->execute([$product_id]);
        $product = $product_check->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Product is out of stock.']);
            exit;
        }

        $final_price = $product['price'];
        $stock = (int) $product['stock'];
        $variant_info = null;
    }

    // Check if adding this quantity would exceed available stock
    $cartQuery = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $cartParams = [$user_id, $product_id];

    if ($supportsVariant) {
        if ($variant_id) {
            $cartQuery .= " AND variant_id = ?";
            $cartParams[] = $variant_id;
        } else {
            $cartQuery .= " AND variant_id IS NULL";
        }
    }

    $cart_check = $db->prepare($cartQuery);
    $cart_check->execute($cartParams);
    $cart_item = $cart_check->fetch(PDO::FETCH_ASSOC);

    $new_quantity = $quantity;
    if ($cart_item) {
        $new_quantity += (int) $cart_item['quantity'];
    }

    if ($new_quantity > $stock) {
        $db->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'Not enough stock available. Only ' . $stock . ' left in stock.'
        ]);
        exit;
    }

    // Add or update cart item
    if ($cart_item) {
        $setParts = ['quantity = ?'];
        $updateParams = [$new_quantity];

        if ($supportsUpdatedAt) {
            $setParts[] = 'updated_at = NOW()';
        }

        $updateParams[] = $cart_item['id'];
        $stmt = $db->prepare('UPDATE cart SET ' . implode(', ', $setParts) . ' WHERE id = ?');
        $stmt->execute($updateParams);
    } else {
        $columns = ['user_id', 'product_id', 'quantity'];
        $placeholders = ['?', '?', '?'];
        $insertParams = [$user_id, $product_id, $quantity];

        if ($supportsVariant) {
            $columns[] = 'variant_id';
            $placeholders[] = '?';
            $insertParams[] = $variant_id;
        }

        if ($supportsPrice) {
            $columns[] = 'price';
            $placeholders[] = '?';
            $insertParams[] = $final_price;
        }

        if ($supportsVariantInfo) {
            $columns[] = 'variant_info';
            $placeholders[] = '?';
            $insertParams[] = $variant_info ? json_encode($variant_info) : null;
        }

        if ($supportsCreatedAt) {
            $columns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }

        if ($supportsUpdatedAt) {
            $columns[] = 'updated_at';
            $placeholders[] = 'NOW()';
        }

        $stmt = $db->prepare(
            'INSERT INTO cart (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($insertParams);
    }

    // Update cart count
    $count = $db->prepare("
        SELECT COALESCE(SUM(quantity), 0) as count
        FROM cart
        WHERE user_id = ?
    ");
    $count->execute([$user_id]);
    $cart_count = (int) $count->fetchColumn();

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to cart!',
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    $db->rollBack();
    error_log("Cart Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
}