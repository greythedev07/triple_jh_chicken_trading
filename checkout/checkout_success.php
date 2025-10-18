<?php session_start();
require_once('../config.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$user_id = $_SESSION['user_id'];

try {
    $stmt = $db->prepare(" SELECT id, total_amount, delivery_address, payment_method, status, date_requested FROM pending_delivery WHERE user_id = ? ORDER BY id DESC LIMIT 1 ");
    $stmt->execute([$user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Order Success | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f5f7;
            font-family: "Inter", sans-serif;
            color: #111;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background-color: #000;
        }

        .navbar .nav-link,
        .navbar-brand {
            color: #fff !important;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 15px;
        }

        .success-card {
            background: #fff;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 550px;
            width: 100%;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }

        .order-summary {
            text-align: left;
            margin-top: 1.5rem;
            background: #f9fafb;
            border-radius: 10px;
            padding: 1.2rem;
        }

        .order-summary p {
            margin: 0.2rem 0;
        }

        .btn-dark {
            background: #000;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            margin-top: 1rem;
            border-radius: 6px;
        }

        .btn-dark:hover {
            background: #111;
        }

        footer {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
            margin-top: auto;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container"> <a class="navbar-brand fw-bold" href="../dashboard.php">Triple JH</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../carts/cart.php">Cart</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../orders/orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main>
        <div class="success-card">
            <div class="success-icon">✅</div>
            <h1>Order Placed Successfully!</h1>
            <p class="text-muted">Thank you for your purchase. Your order is now being processed.</p> <?php if ($order): ?> <div class="order-summary mt-4">
                    <p><strong>Order ID:</strong> <?= htmlspecialchars($order['id']) ?></p>
                    <p><strong>Total Amount:</strong> ₱<?= number_format($order['total_amount'], 2) ?></p>
                    <?php
                                                                                                            $pm = $order['payment_method'] ?? '';
                                                                                                            $pmLabel = $pm === 'COD' ? 'Cash on Delivery' : $pm;
                                                                                                            $status = trim($order['status'] ?? '');
                                                                                                            // Friendly label; default to Pending Delivery
                                                                                                            if ($status === '') {
                                                                                                                $statusLabel = 'Pending Delivery';
                                                                                                            } else {
                                                                                                                // Keep existing common variants readable
                                                                                                                $normalized = strtolower($status);
                                                                                                                if ($normalized === 'pending' || $normalized === 'pending delivery') {
                                                                                                                    $statusLabel = 'Pending';
                                                                                                                } else if ($normalized === 'assigned') {
                                                                                                                    $statusLabel = 'Assigned';
                                                                                                                } else if ($normalized === 'out for delivery') {
                                                                                                                    $statusLabel = 'Out for Delivery';
                                                                                                                } else if ($normalized === 'delivered') {
                                                                                                                    $statusLabel = 'Delivered';
                                                                                                                } else if ($normalized === 'cancelled' || $normalized === 'canceled') {
                                                                                                                    $statusLabel = 'Cancelled';
                                                                                                                } else {
                                                                                                                    $statusLabel = ucfirst($normalized);
                                                                                                                }
                                                                                                            }
                    ?>
                    <p><strong>Payment Method:</strong> <?= htmlspecialchars($pmLabel) ?></p>
                    <p><strong>Status:</strong> <?= htmlspecialchars($statusLabel) ?></p>
                    <p><strong>Delivery Address:</strong><br><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                    <p><strong>Date Requested:</strong> <?= htmlspecialchars($order['date_requested']) ?></p>
                </div> <?php else: ?> <p class="text-muted mt-4">No recent order found.</p> <?php endif; ?> <a href="../dashboard.php" class="btn btn-dark w-100 mt-3">Return to Dashboard</a>
        </div>
    </main>
    <footer> &copy; <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved. </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>