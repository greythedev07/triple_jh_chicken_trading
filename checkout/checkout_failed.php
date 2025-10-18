<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$error_message = $_GET['error'] ?? 'An unknown error occurred during checkout.';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Failed | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f6f7;
            font-family: "Inter", "Segoe UI", sans-serif;
        }

        .navbar {
            background: #111;
            color: #fff;
            padding: 15px 0;
        }

        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .btn-retry {
            background: #111;
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn-retry:hover {
            background: #000;
            color: #fff;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard.php">Triple JH</a>
            <div class="navbar-nav ms-auto d-flex flex-row">
                <li class="nav-item"><a class="nav-link" href="../dashboard.php">Shop</a></li>
                <li class="nav-item"><a class="nav-link active" href="../carts/cart.php">Cart</a></li>
                <li class="nav-item"><a class="nav-link active" href="../orders/orders.php">Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
            </div>
        </div>
    </nav>

    <div class="error-container">
        <div class="error-icon">❌</div>
        <h2 class="text-danger mb-3">Checkout Failed</h2>
        <p class="text-muted mb-4"><?php echo htmlspecialchars($error_message); ?></p>
        <p class="text-muted small">Please review your information and try again.</p>

        <div class="mt-4">
            <a href="../carts/cart.php" class="btn-retry">Return to Cart</a>
            <a href="../dashboard.php" class="btn-retry ms-2">Continue Shopping</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>