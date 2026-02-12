<?php session_start();
require_once('../config.php');
require_once('../includes/order_helper.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$user_id = $_SESSION['user_id'];

try {
    $stmt = $db->prepare(" SELECT id, order_number, total_amount, delivery_address, payment_method, payment_status, gcash_reference, status, date_requested FROM pending_delivery WHERE user_id = ? ORDER BY id DESC LIMIT 1 ");
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
        :root {
            --sunset-gradient-start: #ffb347;
            --sunset-gradient-end: #ff6b26;
            --rich-amber: #f18f01;
            --buttered-sand: #ffe4c1;
            --deep-chestnut: #7a3a12;
            --spark-gold: #f9a219;
            --cream-panel: #fff5e2;
            --accent-light: #fff7e3;
            --accent-dark: #6d3209;
        }

        body {
            background: var(--buttered-sand);
            font-family: "Inter", sans-serif;
            color: var(--accent-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 15px;
        }

        .success-card {
            background: var(--cream-panel);
            border-radius: 18px;
            padding: 2.75rem 2.5rem;
            text-align: center;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.18);
            max-width: 560px;
            width: 100%;
        }

        .success-icon {
            font-size: 3.5rem;
            color: var(--rich-amber);
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
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            padding: 1.4rem 1.5rem;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.08);
        }

        .order-summary p {
            margin: 0.2rem 0;
        }

        .btn-dark {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            border: none;
            padding: 0.8rem 1.8rem;
            font-weight: 600;
            margin-top: 1.25rem;
            border-radius: 999px;
            color: var(--accent-dark);
            box-shadow: 0 14px 32px rgba(241, 143, 1, 0.4);
        }

        .btn-dark:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(241, 143, 1, 0.5);
        }
    </style>
</head>

<body>
    <main>
        <div class="success-card">
            <div class="success-icon">✅</div>
            <h1>Order Placed Successfully!</h1>
            <?php if ($order && $order['payment_method'] === 'GCash'): ?>
                <p class="text-muted">Thank you for your purchase! Your GCash payment is pending verification.</p>
                <div class="alert alert-warning">
                    <strong>Important:</strong> Your order will be processed after we verify your GCash payment.
                    You will receive a confirmation once payment is verified.
                </div>
            <?php else: ?>
                <p class="text-muted">Thank you for your purchase. Your order is now being processed.</p>
            <?php endif; ?> <?php if ($order): ?> <div class="order-summary mt-4">
                    <p><strong>Order Number:</strong> <?= htmlspecialchars(formatOrderNumber($order['order_number'])) ?></p>
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
                    <?php if ($order['payment_method'] === 'GCash' && $order['gcash_reference']): ?>
                        <p><strong>GCash Reference:</strong> <?= htmlspecialchars($order['gcash_reference']) ?></p>
                        <p><strong>Payment Status:</strong>
                            <span class="badge bg-warning"><?= ucfirst($order['payment_status']) ?></span>
                        </p>
                    <?php endif; ?>
                    <p><strong>Status:</strong> <?= htmlspecialchars($statusLabel) ?></p>
                    <p><strong>Delivery Address:</strong><br><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                    <p><strong>Date Requested:</strong> <?= htmlspecialchars($order['date_requested']) ?></p>
                </div> <?php else: ?> <p class="text-muted mt-4">No recent order found.</p> <?php endif; ?> <a href="../dashboard.php" class="btn btn-dark w-100 mt-3">Return to Dashboard</a>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
