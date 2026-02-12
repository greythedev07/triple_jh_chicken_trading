<?php session_start();
require_once('../config.php');
require_once('../includes/order_helper.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$user_id = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = trim($_POST['payment_method'] ?? '');
    $gcash_reference = trim($_POST['gcash_reference'] ?? '');

    // Step 1: Normalize payment method to stored code
    if (strcasecmp($payment_method, 'Cash on Delivery') === 0 || strtoupper($payment_method) === 'COD') {
        $payment_method = 'COD';
        $payment_status = 'verified'; // COD payments are automatically verified
    } elseif (strcasecmp($payment_method, 'GCash') === 0) {
        $payment_method = 'GCash';
        $payment_status = 'pending'; // GCash payments need admin verification

        // Handle GCash screenshot upload
        if (!isset($_FILES['gcash_screenshot']) || $_FILES['gcash_screenshot']['error'] !== UPLOAD_ERR_OK) {
            header("Location: checkout_failed.php?error=" . urlencode("Payment screenshot is required for GCash payments."));
            exit;
        }

        $screenshot_file = $_FILES['gcash_screenshot'];

        // Validate file size (5MB max)
        if ($screenshot_file['size'] > 5 * 1024 * 1024) {
            header("Location: checkout_failed.php?error=" . urlencode("File size must be less than 5MB."));
            exit;
        }

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($screenshot_file['type'], $allowed_types)) {
            header("Location: checkout_failed.php?error=" . urlencode("Invalid file type. Please upload JPG, PNG, or WebP image."));
            exit;
        }

        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/gcash_screenshots/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($screenshot_file['name'], PATHINFO_EXTENSION);
        $unique_filename = 'gcash_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

        // Upload file
        if (!move_uploaded_file($screenshot_file['tmp_name'], $upload_path)) {
            header("Location: checkout_failed.php?error=" . urlencode("Failed to upload payment screenshot. Please try again."));
            exit;
        }

        $gcash_screenshot_path = $upload_path;
    } else {
        $payment_method = 'COD';
        $payment_status = 'verified';
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

    // Ensure there is at least one active driver before allowing checkout
    try {
        $driverCountStmt = $db->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1");
        $activeDriverCount = (int) $driverCountStmt->fetchColumn();
        if ($activeDriverCount === 0) {
            header("Location: checkout_failed.php?error=" . urlencode("We are currently unable to accept orders because no delivery drivers are available. Please try again later."));
            exit;
        }
    } catch (Exception $e) {
        header("Location: checkout_failed.php?error=" . urlencode("Unable to verify driver availability. Please try again later."));
        exit;
    }

    try {
        // Step 2: Begin atomic checkout
        $db->beginTransaction();

        // Step 3: Read cart items with current stock and parent product info
        $stmt = $db->prepare("
            SELECT
                c.id AS cart_id,
                c.quantity,
                p.id AS product_id,
                p.name AS variant_name,
                p.price,
                p.stock,
                p.parent_id,
                pp.name AS parent_name,
                pp.image AS parent_image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            LEFT JOIN parent_products pp ON p.parent_id = pp.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cartItems)) {
            throw new Exception("Your cart is empty.");
        }

        // Step 4: Validate quantities against available stock
        foreach ($cartItems as $item) {
            $available = (int)($item['stock'] ?? 0);
            if ($available < (int)$item['quantity']) {
                $productName = !empty($item['variant_name']) ?
                    htmlspecialchars($item['parent_name'] . ' - ' . $item['variant_name']) :
                    htmlspecialchars($item['parent_name']);
                throw new Exception("Sorry, we only have $available of $productName in stock. Please update your cart.");
            }
        }

        // Step 5: Choose driver with no active deliveries (only for COD payments)
        $assignedDriver = NULL;
        if ($payment_method === 'COD') {
            $driverStmt = $db->query("
                SELECT d.id
                FROM drivers d
                LEFT JOIN (
                    SELECT driver_id, COUNT(*) as active_deliveries
                    FROM pending_delivery
                    WHERE status IN ('pending', 'assigned', 'to be delivered', 'out for delivery')
                    GROUP BY driver_id
                ) pd ON d.id = pd.driver_id
                WHERE d.is_active = 1
                AND (pd.driver_id IS NULL OR pd.active_deliveries = 0)
                ORDER BY (
                    SELECT COUNT(*)
                    FROM pending_delivery
                    WHERE driver_id = d.id
                    AND status = 'completed'
                ) ASC
                LIMIT 1

            ");
            $assignedDriver = $driverStmt->fetchColumn() ?: NULL;

            // If no available drivers, set a flag to show a message to the user
            if (!$assignedDriver) {
                $noDriversAvailable = true;
            }
        }

        // Step 6: If no drivers available for COD, throw an error
        if (isset($noDriversAvailable) && $noDriversAvailable) {
            throw new Exception("We're sorry, but all our drivers are currently busy with other deliveries. Please try again later or choose a different delivery time.");
        }

        // Step 7: Generate order number and create pending delivery header
        $orderNumber = generateOrderNumber($db);
        $status = 'pending'; // Always start as pending, will be updated by driver pickup
        $stmt = $db->prepare("INSERT INTO pending_delivery (order_number, user_id, driver_id, payment_method, payment_status, gcash_reference, gcash_payment_screenshot, status, delivery_address, total_amount, date_requested) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$orderNumber, $user_id, $assignedDriver, $payment_method, $payment_status, $gcash_reference, $gcash_screenshot_path, $status, $full_address, $total]);
        $pending_delivery_id = $db->lastInsertId();

        // Step 8: Copy cart items to pending_delivery_items
        $stmt = $db->prepare("INSERT INTO pending_delivery_items (pending_delivery_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cartItems as $item) {
            $stmt->execute([$pending_delivery_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        // If driver is assigned, update status to assigned but don't create to_be_delivered yet
        if ($assignedDriver) {
            $update = $db->prepare("UPDATE pending_delivery SET status = 'assigned' WHERE id = ?");
            $update->execute([$pending_delivery_id]);
        }

        // Step 8: Decrement inventory for each item with transaction safety
        $decrement = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $check = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");

        foreach ($cartItems as $item) {
            // First check stock with row lock to prevent race conditions
            $check->execute([$item['product_id']]);
            $currentStock = (int)$check->fetchColumn();

            if ($currentStock < $item['quantity']) {
                $productName = !empty($item['variant_name']) ?
                    htmlspecialchars($item['parent_name'] . ' - ' . $item['variant_name']) :
                    htmlspecialchars($item['parent_name']);
                throw new Exception("Insufficient stock for $productName. Only $currentStock available.");
            }

            // Update stock
            $decrement->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
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
