<?php session_start();
require_once('../config.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT firstname, lastname, email, phonenumber, address, barangay, city, zipcode, landmark FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$cartStmt = $db->prepare(" SELECT p.name, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ");
$cartStmt->execute([$user_id]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f6f8;
            font-family: 'Inter', sans-serif;
            color: #222;
        }

        .navbar {
            background: #000;
        }

        .navbar a {
            color: #fff !important;
        }

        .checkout-container {
            max-width: 1000px;
            margin: 60px auto;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .form-select {
            border-radius: 8px;
        }

        .checkout-btn {
            background: #000;
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .checkout-btn:hover {
            background: #111;
        }

        footer {
            background: #111;
            color: #ddd;
            padding: 2rem 0;
            text-align: center;
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
    <div class="checkout-container">
        <h2 class="fw-bold mb-4">Checkout</h2>
        <form method="POST" action="checkout_process.php">
            <div class="row g-4"> <!-- Delivery Info -->
                <div class="col-lg-7">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Delivery Information</h5>
                        <div class="row g-3">
                            <div class="col-md-6"> <label class="form-label">First Name</label> <input type="text" class="form-control" name="firstname" value="<?= htmlspecialchars($user['firstname']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Last Name</label> <input type="text" class="form-control" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Phone Number</label> <input type="text" class="form-control" name="phonenumber" value="<?= htmlspecialchars($user['phonenumber']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Email</label> <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly> </div>
                            <div class="col-12"> <label class="form-label">Street Address</label> <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($user['address']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Barangay</label> <input type="text" class="form-control" name="barangay" value="<?= htmlspecialchars($user['barangay']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">City</label> <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($user['city']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Zip Code</label> <input type="text" class="form-control" name="zipcode" value="<?= htmlspecialchars($user['zipcode']) ?>" required> </div>
                            <div class="col-md-6"> <label class="form-label">Landmark</label> <input type="text" class="form-control" name="landmark" value="<?= htmlspecialchars($user['landmark']) ?>"> </div>
                        </div>
                    </div>
                    <div class="card p-4 mt-4">
                        <h5 class="fw-bold mb-3">Payment Method</h5>
                        <div class="form-check"> <input class="form-check-input" type="radio" name="payment_method" id="cod" value="Cash on Delivery" checked> <label class="form-check-label" for="cod">Cash on Delivery</label> </div>
                    </div>
                </div> <!-- Order Summary -->
                <div class="col-lg-5">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Order Summary</h5> <?php foreach ($cartItems as $item): ?> <div class="d-flex justify-content-between small mb-2"> <span><?= htmlspecialchars($item['name']) ?> × <?= (int)$item['quantity'] ?></span> <span>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></span> </div> <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold"> <span>Total</span> <span>₱<?= number_format($total, 2) ?></span> </div> <input type="hidden" name="total" value="<?= $total ?>"> <button type="submit" class="checkout-btn mt-3">Place Order</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <footer>
        <div class="container"> <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small> </div>
    </footer>
</body>

</html>