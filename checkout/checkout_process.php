<?php session_start();
require_once('../config.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = trim($_POST['payment_method'] ?? '');
    // Step 1: Normalize payment method to stored code
    if (strcasecmp($payment_method, 'Cash on Delivery') === 0 || strtoupper($payment_method) === 'COD') {
        $payment_method = 'COD';
    } else {
        $payment_method = 'COD';
    }
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $total = floatval($_POST['total'] ?? 0);
    $full_address = "$address, Brgy. $barangay, $city, $zipcode";

    if (!empty($landmark)) {
        $full_address .= " (Landmark: $landmark)";
    }

    if (empty($address) || empty($city) || empty($barangay)) {
        header("Location: checkout_failed.php?error=" . urlencode("Incomplete delivery address."));
        exit;
    }

    if ($total <= 0) {
        header("Location: checkout_failed.php?error=" . urlencode("Invalid total amount."));
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: checkout_failed.php?error=" . urlencode("Invalid email address."));
        exit;
    }

    try {
        // Step 2: Begin atomic checkout
        $db->beginTransaction();

        // Step 3: Read cart items with current stock
        $stmt = $db->prepare(" SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ");
        $stmt->execute([$user_id]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cartItems)) {
            throw new Exception("Your cart is empty.");
        }
        // Step 4: Validate quantities against available stock
        foreach ($cartItems as $ci) {
            $available = (int)($ci['stock'] ?? 0);
            if ($available < (int)$ci['quantity']) {
                throw new Exception('One or more items exceed available stock. Please review your cart.');
            }
        }

        // Step 5: Choose driver with lightest load
        $driverStmt = $db->query(" SELECT id FROM drivers ORDER BY ( SELECT COUNT(*) FROM pending_delivery WHERE driver_id = drivers.id AND status = 'pending' ) ASC LIMIT 1 ");
        $assignedDriver = $driverStmt->fetchColumn() ?: NULL;

        // Step 6: Create pending delivery header
        $stmt = $db->prepare(" INSERT INTO pending_delivery (user_id, driver_id, payment_method, status, delivery_address, total_amount, date_requested) VALUES (?, ?, ?, 'pending', ?, ?, NOW()) ");
        $stmt->execute([$user_id, $assignedDriver, $payment_method, $full_address, $total]);
        $pending_delivery_id = $db->lastInsertId();
        // Step 7: Copy cart items to delivery items
        $stmt = $db->prepare(" INSERT INTO pending_delivery_items (pending_delivery_id, product_id, quantity, price) VALUES (?, ?, ?, ?) ");

        foreach ($cartItems as $item) {
            $stmt->execute([$pending_delivery_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // Step 8: Decrement inventory for each item
        $decrement = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        foreach ($cartItems as $item) {
            $affected = $decrement->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            // Verify stock didn't go negative
            $check = $db->prepare("SELECT stock FROM products WHERE id = ?");
            $check->execute([$item['product_id']]);
            $remaining = (int)$check->fetchColumn();
            if ($remaining < 0) {
                throw new Exception('Inventory update failed due to insufficient stock.');
            }
        }
        // Step 9: Clear cart and update user profile
        $stmt = $db->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stmt = $db->prepare(" UPDATE users SET address = ?, barangay = ?, city = ?, zipcode = ?, landmark = ?, phonenumber = ?, email = ? WHERE id = ? ");
        $stmt->execute([$address, $barangay, $city, $zipcode, $landmark, $phonenumber, $email, $user_id]);
        // Step 10: Commit and redirect to success
        $db->commit();

        header("Location: checkout_success.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        header("Location: checkout_failed.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ../cart/cart.php");
    exit;
}
