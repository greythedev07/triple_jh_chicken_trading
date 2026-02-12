<?php
session_start();
require_once('../config.php');
require_once('../includes/order_helper.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart count
try {
    $cartStmt = $db->prepare("SELECT SUM(quantity) AS count FROM cart WHERE user_id = ?");
    $cartStmt->execute([$user_id]);
    $cartCount = (int) $cartStmt->fetchColumn();
} catch (PDOException $e) {
    $cartCount = 0;
}

try {
    // Fetch active orders (not delivered)
    $stmt = $db->prepare("
        SELECT
            pd.id AS order_id,
            pd.order_number,
            pd.payment_method,
            pd.status,
            pd.delivery_address,
            pd.total_amount,
            pd.date_requested,
            pd.driver_id,
            d.name AS driver_name,
            d.profile_picture AS driver_profile_picture
        FROM pending_delivery pd
        LEFT JOIN drivers d ON pd.driver_id = d.id
        WHERE pd.user_id = ?
          AND pd.status NOT IN ('Cancelled', 'cancelled', 'delivered')
        ORDER BY pd.date_requested DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch completed orders from history_of_delivery table
    $completedStmt = $db->prepare("
        SELECT
            h.id AS order_id,
            h.order_number,
            h.payment_method,
            'delivered' AS status,
            h.delivery_address,
            (SELECT SUM(hdi.quantity * hdi.price) FROM history_of_delivery_items hdi WHERE hdi.history_id = h.id) AS total_amount,
            h.created_at AS date_requested,
            h.driver_id,
            d.name AS driver_name,
            d.profile_picture AS driver_profile_picture
        FROM history_of_delivery h
        LEFT JOIN drivers d ON h.driver_id = d.id
        WHERE h.user_id = ?
        ORDER BY h.created_at DESC
    ");
    $completedStmt->execute([$user_id]);
    $completedOrders = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch order items for active orders with parent product info
    $orderItems = [];
    $itemStmt = $db->prepare("
        SELECT
            p.name,
            p.image,
            p.price,
            p.id AS product_id,
            pdi.quantity,
            pp.id AS parent_id,
            pp.name AS parent_name,
            pp.image AS parent_image,
            CASE
                WHEN p.image IS NOT NULL AND p.image != '' THEN CONCAT('items/', p.image)
                WHEN pp.image IS NOT NULL AND pp.image != '' THEN CONCAT('parent_products/', pp.image)
                ELSE NULL
            END AS display_image
        FROM pending_delivery_items pdi
        JOIN products p ON pdi.product_id = p.id
        LEFT JOIN parent_products pp ON p.parent_id = pp.id
        WHERE pdi.pending_delivery_id = ?
    ");

    foreach ($orders as $order) {
        $itemStmt->execute([$order['order_id']]);
        $orderItems[$order['order_id']] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch order items for completed orders with parent product info
    $completedOrderItems = [];
    $completedItemStmt = $db->prepare("
        SELECT
            p.name,
            p.image,
            p.price,
            p.id AS product_id,
            hdi.quantity,
            pp.id AS parent_id,
            pp.name AS parent_name,
            pp.image AS parent_image,
            CASE
                WHEN p.image IS NOT NULL AND p.image != '' THEN CONCAT('items/', p.image)
                WHEN pp.image IS NOT NULL AND pp.image != '' THEN CONCAT('parent_products/', pp.image)
                ELSE NULL
            END AS display_image
        FROM history_of_delivery_items hdi
        JOIN products p ON hdi.product_id = p.id
        LEFT JOIN parent_products pp ON p.parent_id = pp.id
        WHERE hdi.history_id = ?
    ");

    foreach ($completedOrders as $order) {
        $completedItemStmt->execute([$order['order_id']]);
        $completedOrderItems[$order['order_id']] = $completedItemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Orders | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/footer_header.css">

    <style>
        :root {
            --sunset-gradient-start: #ffb347;
            --sunset-gradient-end: #ff6b26;
            --rich-amber: #f18f01;
            --buttered-sand: #ffe4c1;
            --deep-chestnut: #7a3a12;
            --spark-gold: #f9a219;
            --cream-panel: #fff5e2;
            --accent-light: #fff7e3;
            --accent-lighter: #ffe9cf;
            --accent-dark: #6d3209;
            --navbar-text: #4e1e06;
            --shadow-soft: rgba(0, 0, 0, 0.12);
            --text: var(--accent-dark);
            --text-muted: rgba(109, 50, 9, 0.65);
            --bg-card: rgba(255, 255, 255, 0.8);
            --panel-shadow: rgba(0, 0, 0, 0.08);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            background: var(--buttered-sand);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }

        body {
            padding-top: 70px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            padding-bottom: 80px;
            width: 100%;
            max-width: 1100px;
            margin: 2rem auto 0;
            align-items: flex-start;
            justify-content: center;
            gap: 2rem;
        }

        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--rich-amber);
            color: var(--accent-light);
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }

        .sidebar {
            margin-top: 50px;
            flex: 0 0 250px;
            width: 100%;
            max-width: 250px;
            background: var(--cream-panel);
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            padding: 1.5rem 0;
            box-shadow: 0 10px 30px var(--shadow-soft);
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-nav li {
            margin: 0;
        }

        .sidebar-nav a {
            display: block;
            padding: 1rem 2rem;
            color: var(--text-muted);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.8);
            color: var(--text);
        }

        .sidebar-nav a.active {
            background: var(--buttered-sand);
            color: var(--accent-dark);
            border-left-color: var(--rich-amber);
            font-weight: 600;
        }

        .content-area {
            flex: 1 1 0;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: 0 20px 60px var(--shadow-soft);
            min-width: 320px;
        }

        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .order-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 12px 30px var(--shadow-soft);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }

        .btn-details {
            font-size: 0.85rem;
        }

        .order-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        .btn-amber-primary,
        .btn-amber-secondary {
            border: none;
            border-radius: 30px;
            padding: 0.55rem 1.5rem;
            font-weight: 600;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-amber-primary {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: var(--accent-dark);
            box-shadow: 0 8px 20px rgba(255, 153, 0, 0.35);
        }

        .btn-amber-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(255, 153, 0, 0.45);
        }

        .btn-amber-secondary {
            background: var(--rich-amber);
            color: var(--accent-light);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
        }

        .btn-amber-secondary:hover {
            background: var(--spark-gold);
            color: var(--accent-light);
        }

        .btn-outline-dark {
            border-color: rgba(109, 50, 9, 0.4);
            color: var(--accent-dark);
            background: transparent;
        }

        .btn-outline-dark:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--accent-dark);
            border-color: var(--accent-dark);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--accent-dark);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../dashboard.php">
                <img src="../img/logo.jpg" alt="Triple JH Chicken Trading"
                    style="height: 40px; width: auto; margin-right: 10px;">
                <span class="d-none d-md-inline">Triple JH Chicken Trading</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home"></i> Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../about.php">
                            <i class="fas fa-info-circle"></i> About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../orders/orders.php">
                            <i class="fas fa-shopping-bag"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a class="nav-link position-relative" href="../carts/cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php if ($cartCount > 0): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../useraccounts/settings.php">
                            <i class="fas fa-user"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <div class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="#" class="sidebar-link active" data-tab="orders">Orders</a></li>
                <li><a href="#" class="sidebar-link" data-tab="history">Order History</a></li>
            </ul>
        </div>

        <div class="content-area">
            <div class="orders-container">
                <!-- Active Orders Tab -->
                <div id="orders" class="tab-content active">
                    <h2 class="section-title">My Orders</h2>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
                    <?php elseif (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                    <?php endif; ?>

                    <?php if (empty($orders)): ?>
                        <p class="text-muted">You have no active orders.</p>
                    <?php else: ?>
                        <?php foreach ($orders as $order):
                            $oid = (int) $order['order_id']; ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="fw-bold">Order
                                            #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?>
                                        </div>
                                        <small
                                            class="text-muted"><?= date("F j, Y, g:i a", strtotime($order['date_requested'])) ?></small>
                                    </div>
                                    <div>
                                        <?php
                                        $rs = strtolower($order['status'] ?? '');
                                        $label = $rs === '' ? 'Pending' : ($rs === 'pending' ? 'Pending' : (($rs === 'to be delivered' || $rs === 'out for delivery' || $rs === 'assigned' || $rs === 'picked_up') ? 'Delivering' : ($rs === 'delivered' ? 'Delivered' : ($rs === 'cancelled' || $rs === 'canceled' ? 'Cancelled' : ucfirst($rs)))));
                                        $badge = $label === 'Pending' ? 'bg-warning' : ($label === 'Delivering' ? 'bg-info' : ($label === 'Delivered' ? 'bg-success' : 'bg-secondary'));
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= htmlspecialchars($label) ?></span>
                                        <button type="button" class="btn btn-outline-dark btn-sm btn-details ms-2"
                                            data-bs-toggle="modal" data-bs-target="#orderModal<?= $oid ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>

                                <?php foreach ($orderItems[$oid] as $item):
                                    $imagePath = !empty($item['parent_image']) ? '../' . $item['parent_image'] : '../img/no-image.png';
                                    $altText = htmlspecialchars($item['name'] ?? 'Product image');
                                ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 8px;" class="me-2">
                                            <img src="<?= $imagePath ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;"
                                                alt="<?= $altText ?>"
                                                onerror="this.src='../img/products/placeholder.jpg';">
                                        </div>
                                        <div class="flex-grow-1">
                                            <?= htmlspecialchars($item['name']) ?><br>
                                            <small>Qty: <?= (int) $item['quantity'] ?> ×
                                                ₱<?= number_format($item['price'], 2) ?></small>
                                        </div>
                                        <strong>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></strong>
                                    </div>
                                <?php endforeach; ?>

                                <hr>
                                <?php if (!empty($order['driver_name'])): ?>
                                <div class="driver-info d-flex align-items-center mb-2">
                                    <div class="driver-avatar me-2" style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden;">
                                        <?php
                                        $driverImage = !empty($order['driver_profile_picture'])
                                            ? '../' . $order['driver_profile_picture']
                                            : '../img/default-avatar.png';
                                        ?>
                                        <img src="<?= $driverImage ?>" alt="Driver" style="width: 100%; height: 100%; object-fit: cover;"
                                             onerror="this.src='../img/default-avatar.png';">
                                    </div>
                                    <div class="driver-details">
                                        <small class="text-muted">Driver: </small>
                                        <span class="fw-medium"><?= htmlspecialchars($order['driver_name']) ?></span>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between small align-items-center">
                                    <span>Payment: <?= htmlspecialchars($order['payment_method']) ?></span>
                                    <div>
                                        <strong>₱<?= number_format($order['total_amount'], 2) ?></strong>
                                        <?php if (strtolower($order['status']) === 'pending'): ?>
                                            <form method="POST" action="cancel_order.php" style="display:inline-block"
                                                onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                <input type="hidden" name="order_id" value="<?= $oid ?>">
                                                <button type="submit" class="btn btn-danger btn-sm ms-2">Cancel Order</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Details Modal -->
                            <div class="modal fade" id="orderModal<?= $oid ?>" tabindex="-1"
                                aria-labelledby="orderModalLabel<?= $oid ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Order
                                                #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?>
                                                Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>Status:</strong>
                                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($label) ?></span>
                                            </div>
                                            <div class="mb-3"><strong>Delivery
                                                    Address:</strong><br><?= htmlspecialchars($order['delivery_address']) ?>
                                            </div>
                                            <div class="mb-3"><strong>Driver:</strong>
                                                <?= htmlspecialchars($order['driver_name'] ?? 'Not assigned yet') ?></div>
                                            <div class="mb-3"><strong>Payment Method:</strong>
                                                <?= htmlspecialchars($order['payment_method']) ?></div>
                                            <div class="mb-3"><strong>Total Amount:</strong>
                                                ₱<?= number_format($order['total_amount'], 2) ?></div>

                                            <hr>
                                            <h6>Ordered Items:</h6>
                                            <?php foreach ($orderItems[$oid] as $item): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <?php
                                                    $imagePath = !empty($item['parent_image']) ? '../' . $item['parent_image'] : '../img/products/placeholder.jpg';
                                                    $altText = htmlspecialchars($item['parent_name'] ?? $item['name'] ?? 'Product image');
                                                    ?>
                                                    <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 8px;" class="me-2">
                                                        <img src="<?= $imagePath ?>"
                                                            style="width: 100%; height: 100%; object-fit: cover;"
                                                            alt="<?= $altText ?>"
                                                            onerror="this.src='../img/products/placeholder.jpg'">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <?= htmlspecialchars($item['name']) ?><br>
                                                        <small>Qty: <?= (int) $item['quantity'] ?> ×
                                                            ₱<?= number_format($item['price'], 2) ?></small>
                                                    </div>
                                                    <strong>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="modal-footer">
                                            <button class="btn btn-amber-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Order History Tab -->
                <div id="history" class="tab-content">
                    <h2 class="section-title">Order History</h2>
                    <?php if (empty($completedOrders)): ?>
                        <p class="text-muted">You have no completed orders.</p>
                    <?php else: ?>
                        <?php foreach ($completedOrders as $order):
                            $oid = (int) $order['order_id']; ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="fw-bold">Order
                                            #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?>
                                        </div>
                                        <small
                                            class="text-muted"><?= date("F j, Y, g:i a", strtotime($order['date_requested'])) ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-success">Delivered</span>
                                        <button type="button" class="btn btn-outline-dark btn-sm btn-details ms-2"
                                            data-bs-toggle="modal" data-bs-target="#completedOrderModal<?= $oid ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>

                                <?php foreach ($completedOrderItems[$oid] as $item):
                                    $imagePath = !empty($item['parent_image']) ? $item['parent_image'] : 'img/no-image.png';
                                    $altText = htmlspecialchars($item['name']);
                                ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 8px;" class="me-2">
                                            <img src="../<?= $imagePath ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;"
                                                alt="<?= $altText ?>"
                                                onerror="this.src='../img/no-image.png'">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                            <small class="text-muted">Quantity: <?= $item['quantity'] ?> ×
                                                ₱<?= number_format($item['price'], 2) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="order-footer">
                                    <div>
                                        <strong>₱<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Completed Order Details Modal -->
                            <div class="modal fade" id="completedOrderModal<?= $oid ?>" tabindex="-1"
                                aria-labelledby="completedOrderModalLabel<?= $oid ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Order
                                                #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?>
                                                Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Order Information</h6>
                                                    <?php if (!empty($order['driver_name'])): ?>
                                                    <div class="driver-info d-flex align-items-center mb-3">
                                                        <div class="driver-avatar me-3" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; border: 2px solid var(--rich-amber);">
                                                            <?php
                                                            $driverImage = !empty($order['driver_profile_picture'])
                                                                ? '../' . $order['driver_profile_picture']
                                                                : '../img/default-avatar.png';
                                                            ?>
                                                            <img src="<?= $driverImage ?>" alt="Driver" style="width: 100%; height: 100%; object-fit: cover;"
                                                                 onerror="this.src='../img/default-avatar.png';">
                                                        </div>
                                                        <div class="driver-details">
                                                            <p class="mb-0"><strong>Driver:</strong></p>
                                                            <h5 class="mb-0"><?= htmlspecialchars($order['driver_name']) ?></h5>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    <p><strong>Order ID:</strong> #<?= $oid ?></p>
                                                    <p><strong>Date:</strong>
                                                        <?= date("F j, Y, g:i a", strtotime($order['date_requested'])) ?></p>
                                                    <p><strong>Status:</strong> <span class="badge bg-success">Delivered</span>
                                                    </p>
                                                    <p><strong>Payment Method:</strong>
                                                        <?= htmlspecialchars($order['payment_method'] === 'COD' ? 'Cash on Delivery' : ($order['payment_method'] === 'GCash' ? 'GCash' : $order['payment_method'])) ?>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Delivery Information</h6>
                                                    <p><strong>Address:</strong>
                                                        <?= htmlspecialchars($order['delivery_address']) ?></p>
                                                    <?php if ($order['driver_name']): ?>
                                                        <p><strong>Driver:</strong> <?= htmlspecialchars($order['driver_name']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6>Order Items</h6>
                                            <?php foreach ($completedOrderItems[$oid] as $item):
                                                $imagePath = !empty($item['parent_image']) ? '../' . $item['parent_image'] : '../img/products/placeholder.jpg';
                                                $altText = htmlspecialchars($item['name'] ?? 'Product image');
                                                $displayName = htmlspecialchars($item['name']);
                                            ?>
                                                <div class="d-flex align-items-center mb-3">
                                                    <div style="width: 80px; height: 80px; overflow: hidden; border-radius: 8px;" class="me-3">
                                                        <img src="<?= $imagePath ?>"
                                                            style="width: 100%; height: 100%; object-fit: cover;"
                                                            alt="<?= $altText ?>"
                                                            onerror="this.src='../img/products/placeholder.jpg'">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= $displayName ?></h6>
                                                        <p class="text-muted mb-1">Quantity: <?= $item['quantity'] ?></p>
                                                        <p class="mb-0"><strong>₱<?= number_format($item['price'], 2) ?>
                                                                each</strong></p>
                                                    </div>
                                                    <div class="text-end">
                                                        <strong>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <h5>Total Amount:</h5>
                                                <h5 class="text-primary">₱<?= number_format($order['total_amount'], 2) ?></h5>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>


    <footer>
    <div class="container text-center">
      <div class="footer-links">
        <a href="../dashboard.php">Shop</a>
        <a href="../about.php">About</a>
        <a href="../about.php">Terms</a>
        <a href="../about.php">Privacy</a>
      </div>
      <p class="copyright">&copy; <?= date('Y') ?> Triple JH Chicken Trading. All rights reserved.</p>
    </div>
  </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar tab functionality
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            const tabContents = document.querySelectorAll('.tab-content');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Remove active class from all links and tabs
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(tab => tab.classList.remove('active'));

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Show corresponding tab
                    const targetTab = this.getAttribute('data-tab');
                    const tab = document.getElementById(targetTab);
                    if (tab) {
                        tab.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>

</html>
