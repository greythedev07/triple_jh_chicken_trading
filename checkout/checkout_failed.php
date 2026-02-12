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
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Inter", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #fff7e3 0, #ffe4c1 35%, #fbd0a1 70%, #ffb347 100%);
            color: var(--accent-dark);
        }

        .error-container {
            max-width: 520px;
            width: 100%;
            background: var(--cream-panel);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            box-shadow: 0 26px 70px rgba(0, 0, 0, 0.28);
            text-align: center;
            border: 1px solid rgba(241, 143, 1, 0.45);
        }

        .error-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .error-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #c0392b;
        }

        .error-message {
            font-size: 0.95rem;
            color: rgba(109, 50, 9, 0.8);
        }

        .error-hint {
            font-size: 0.8rem;
            color: rgba(109, 50, 9, 0.7);
        }

        .btn-amber {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.7rem 1.6rem;
            border-radius: 999px;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: var(--accent-dark);
            box-shadow: 0 12px 30px rgba(241, 143, 1, 0.55);
            text-decoration: none;
        }

        .btn-amber:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 40px rgba(241, 143, 1, 0.65);
            color: var(--accent-dark);
        }

        .btn-outline-amber {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.7rem 1.6rem;
            border-radius: 999px;
            border: 1px solid rgba(109, 50, 9, 0.35);
            font-weight: 600;
            font-size: 0.9rem;
            background: rgba(255, 255, 255, 0.85);
            color: var(--accent-dark);
            text-decoration: none;
        }

        .btn-outline-amber:hover {
            background: rgba(255, 255, 255, 1);
            color: var(--accent-dark);
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">❌</div>
        <h2 class="error-title">Checkout Failed</h2>
        <p class="error-message mb-3"><?php echo htmlspecialchars($error_message); ?></p>
        <p class="error-hint mb-4">Please review your information or try again in a few moments.</p>

        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 mt-2">
            <a href="../carts/cart.php" class="btn-amber">
                ← Return to Cart
            </a>
            <a href="../dashboard.php" class="btn-outline-amber">
                Continue Shopping
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
