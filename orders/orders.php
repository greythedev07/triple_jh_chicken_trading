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
    $cartCount = (int)$cartStmt->fetchColumn();
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
            d.name AS driver_name
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
            d.name AS driver_name
        FROM history_of_delivery h
        LEFT JOIN drivers d ON h.driver_id = d.id
        WHERE h.user_id = ?
        ORDER BY h.created_at DESC
    ");
    $completedStmt->execute([$user_id]);
    $completedOrders = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch order items for active orders
    $orderItems = [];
    $itemStmt = $db->prepare("
        SELECT p.name, p.image, p.price, p.id AS product_id, pdi.quantity
        FROM pending_delivery_items pdi
        JOIN products p ON pdi.product_id = p.id
        WHERE pdi.pending_delivery_id = ?
    ");

    foreach ($orders as $order) {
        $itemStmt->execute([$order['order_id']]);
        $orderItems[$order['order_id']] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch order items for completed orders
    $completedOrderItems = [];
    $completedItemStmt = $db->prepare("
        SELECT p.name, p.image, p.price, p.id AS product_id, hdi.quantity
        FROM history_of_delivery_items hdi
        JOIN products p ON hdi.product_id = p.id
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

    <style>
        html,
        body {
            height: 100%;
        }

        body {
            display: flex;
            flex-direction: column;
            background: #f5f6f8;
            font-family: 'Inter', sans-serif;
            color: #222;
        }

        main {
            flex: 1;
            display: flex;
        }

        .navbar {
            background: #000;
        }

        .navbar a {
            color: #fff !important;
        }

        .cart-link {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ff3b30;
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 999px;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }

        .sidebar {
            width: 250px;
            background: #fff;
            border-right: 1px solid #eee;
            padding: 2rem 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
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
            color: #666;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar-nav a:hover {
            background: #f8f9fa;
            color: #333;
        }

        .sidebar-nav a.active {
            background: #f8f9fa;
            color: #000;
            border-left-color: #000;
            font-weight: 600;
        }

        .content-area {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .order-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.75rem;
            margin-bottom: 1rem;
        }


        footer {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 1rem;
            margin-top: auto;
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
            border-top: 1px solid #eee;
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
            color: #333;
        }

        footer {
            background: #000;
            color: #fff;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: auto;
            flex-shrink: 0;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard.php">Triple JH</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link cart-link" href="../carts/cart.php">Cart <span id="cartBadge" class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount > 0 ? $cartCount : '' ?></span></a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="../useraccounts/settings.php">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
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
                            $oid = (int)$order['order_id']; ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="fw-bold">Order #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?></div>
                                        <small class="text-muted"><?= date("F j, Y, g:i a", strtotime($order['date_requested'])) ?></small>
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

                                <?php foreach ($orderItems[$oid] as $item): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="../<?= htmlspecialchars($item['image'] ?? 'img/no-image.png') ?>"
                                            width="60" height="60" class="rounded me-2" alt="">
                                        <div class="flex-grow-1">
                                            <?= htmlspecialchars($item['name']) ?><br>
                                            <small>Qty: <?= (int)$item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></small>
                                        </div>
                                        <strong>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></strong>
                                    </div>
                                <?php endforeach; ?>

                                <hr>
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
                                            <h5 class="modal-title">Order #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?> Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <strong>Status:</strong>
                                                <span class="badge <?= $badge ?>"><?= htmlspecialchars($label) ?></span>
                                            </div>
                                            <div class="mb-3"><strong>Delivery Address:</strong><br><?= htmlspecialchars($order['delivery_address']) ?></div>
                                            <div class="mb-3"><strong>Driver:</strong> <?= htmlspecialchars($order['driver_name'] ?? 'Not assigned yet') ?></div>
                                            <div class="mb-3"><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></div>
                                            <div class="mb-3"><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></div>

                                            <hr>
                                            <h6>Ordered Items:</h6>
                                            <?php foreach ($orderItems[$oid] as $item): ?>
                                                <div class="d-flex align-items-center mb-2">
                                                    <img src="../<?= htmlspecialchars($item['image'] ?? 'img/no-image.png') ?>"
                                                        width="60" height="60" class="me-2 rounded" alt="">
                                                    <div class="flex-grow-1">
                                                        <?= htmlspecialchars($item['name']) ?><br>
                                                        <small>Qty: <?= (int)$item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></small>
                                                    </div>
                                                    <strong>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></strong>
                                                </div>
                                            <?php endforeach; ?>
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

                <!-- Order History Tab -->
                <div id="history" class="tab-content">
                    <h2 class="section-title">Order History</h2>
                    <?php if (empty($completedOrders)): ?>
                        <p class="text-muted">You have no completed orders.</p>
                    <?php else: ?>
                        <?php foreach ($completedOrders as $order):
                            $oid = (int)$order['order_id']; ?>
                            <div class="order-card">
                                <div class="order-header">
                                    <div>
                                        <div class="fw-bold">Order #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?></div>
                                        <small class="text-muted"><?= date("F j, Y, g:i a", strtotime($order['date_requested'])) ?></small>
                                    </div>
                                    <div>
                                        <span class="badge bg-success">Delivered</span>
                                        <button type="button" class="btn btn-outline-dark btn-sm btn-details ms-2"
                                            data-bs-toggle="modal" data-bs-target="#completedOrderModal<?= $oid ?>">
                                            View Details
                                        </button>
                                    </div>
                                </div>

                                <?php foreach ($completedOrderItems[$oid] as $item): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="../<?= htmlspecialchars($item['image'] ?? 'img/no-image.png') ?>"
                                            width="60" height="60" class="rounded me-2" alt="">
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                            <small class="text-muted">Quantity: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></small>
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
                                            <h5 class="modal-title">Order #<?= htmlspecialchars($order['order_number'] ? formatOrderNumber($order['order_number']) : $oid) ?> Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6>Order Information</h6>
                                                    <p><strong>Order ID:</strong> #<?= $oid ?></p>
                                                    <p><strong>Date:</strong> <?= date("F j, Y, g:i a", strtotime($order['date_requested'])) ?></p>
                                                    <p><strong>Status:</strong> <span class="badge bg-success">Delivered</span></p>
                                                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method'] === 'COD' ? 'Cash on Delivery' : ($order['payment_method'] === 'GCash' ? 'GCash' : $order['payment_method'])) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6>Delivery Information</h6>
                                                    <p><strong>Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                                                    <?php if ($order['driver_name']): ?>
                                                        <p><strong>Driver:</strong> <?= htmlspecialchars($order['driver_name']) ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <hr>
                                            <h6>Order Items</h6>
                                            <?php foreach ($completedOrderItems[$oid] as $item): ?>
                                                <div class="d-flex align-items-center mb-3">
                                                    <img src="../<?= htmlspecialchars($item['image'] ?? 'img/no-image.png') ?>"
                                                        width="80" height="80" class="rounded me-3" alt="">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                                        <p class="text-muted mb-1">Quantity: <?= $item['quantity'] ?></p>
                                                        <p class="mb-0"><strong>₱<?= number_format($item['price'], 2) ?> each</strong></p>
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
        <div class="container">
            <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            const tabContents = document.querySelectorAll('.tab-content');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function(e) {
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