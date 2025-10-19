<?php
session_start();
require_once('../config.php');

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
  // Fetch all items in user's cart
  $stmt = $db->prepare("
    SELECT 
      c.id AS cart_id,
      c.quantity,
      p.id AS product_id,
      p.name,
      p.price,
      p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
  ");
  $stmt->execute([$user_id]);
  $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Database error: " . $e->getMessage());
}

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
  $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Your Cart | Triple JH Chicken Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
      background: #f3f5f7;
      font-family: "Inter", sans-serif;
      color: #111;
    }

    main {
      flex: 1;
    }

    .navbar {
      background-color: #000;
    }

    .navbar .nav-link,
    .navbar-brand {
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

    .cart-container {
      max-width: 1100px;
      margin: 60px auto;
      background: transparent;
    }

    h1 {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }

    .cart-left {
      background: #fff;
      border-radius: 10px;
      padding: 1.5rem;
    }

    .cart-item {
      display: flex;
      align-items: center;
      gap: 15px;
      border-bottom: 1px solid #e7e7e7;
      padding-bottom: 1rem;
      margin-bottom: 1rem;
    }

    .cart-item:last-child {
      border-bottom: none;
    }

    .cart-item img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 6px;
      background: #f0f0f0;
    }

    .item-info h5 {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .item-info small {
      color: #666;
    }

    .item-info .price {
      font-weight: 700;
      font-size: 1.1rem;
      margin-top: 5px;
    }

    .remove-btn {
      background: none;
      border: none;
      color: #db2323ff;
      font-size: 0.9rem;
      margin-top: 5px;
    }

    .remove-btn:hover {
      color: #000;
      text-decoration: underline;
    }

    .summary-box {
      background: #fff;
      padding: 1.5rem;
      border-radius: 10px;
      height: fit-content;
    }

    .summary-box h4 {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 1.2rem;
    }

    .summary-box .line {
      border-top: 1px solid #ddd;
      margin: 10px 0;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.95rem;
      margin-bottom: 6px;
    }

    .checkout-btn {
      background: #000;
      color: #fff;
      border: none;
      width: 100%;
      padding: 0.75rem;
      border-radius: 6px;
      margin-top: 1rem;
      font-weight: 600;
    }

    .checkout-btn:hover {
      background: #111;
    }

    footer {
      background: #000;
      color: #fff;
      padding: 1.5rem 0;
      text-align: center;
      margin-top: auto;
      flex-shrink: 0;
    }

    @media(max-width: 768px) {
      .cart-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .cart-item img {
        width: 100%;
        height: 180px;
      }
    }
  </style>
</head>

<body>
  <!-- Header -->
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand fw-bold" href="../dashboard.php">Triple JH</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="../dashboard.php">Shop</a></li>
          <li class="nav-item"><a class="nav-link cart-link active" href="../carts/cart.php">Cart <span id="cartBadge" class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount > 0 ? $cartCount : '' ?></span></a></li>
          <li class="nav-item"><a class="nav-link" href="../orders/orders.php">Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="../useraccounts/settings.php">Settings</a></li>
          <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <main>
    <div class="container cart-container">
      <h1>Your cart</h1>
      <p><a href="../dashboard.php" class="text-decoration-none text-dark small">Not ready to checkout? <strong>Continue Shopping</strong></a></p>
      <div class="row mt-4 g-4">
        <div class="col-lg-8">
          <div class="cart-left">
            <?php if (empty($cartItems)): ?>
              <p class="text-muted">Your cart is empty.</p>
            <?php else: ?>
              <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                  <img src="../<?= htmlspecialchars($item['image'] ?? 'img/no-image.png') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                  <div class="item-info flex-grow-1">
                    <h5><?= htmlspecialchars($item['name']) ?></h5>
                    <form method="POST" action="update_cart.php" class="d-inline">
                      <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                      <div class="d-flex align-items-center mb-2">
                        <label for="qty-<?= $item['cart_id'] ?>" class="me-2 small">Quantity:</label>
                        <input
                          type="number"
                          id="qty-<?= $item['cart_id'] ?>"
                          name="quantity"
                          value="<?= (int)$item['quantity'] ?>"
                          min="1"
                          class="form-control form-control-sm"
                          style="width: 70px; display:inline-block;">
                        <button type="submit" class="btn btn-sm btn-outline-dark ms-2">Update</button>
                      </div>
                    </form>
                    <div class="price">₱<?= number_format($item['price'], 2) ?></div>
                    <form method="POST" action="remove_from_cart.php" class="d-inline">
                      <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                      <button class="remove-btn" type="submit">Remove</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>

            <?php endif; ?>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="summary-box">
            <h4>Order Summary</h4>
            <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($total, 2) ?></span></div>
            <div class="summary-row"><span>Shipping</span><span>Calculated at next step</span></div>
            <div class="line"></div>
            <div class="summary-row fw-bold"><span>Total</span><span>₱<?= number_format($total, 2) ?></span></div>
            <form action="../checkout/checkout.php" method="POST">
              <input type="hidden" name="total" value="<?= htmlspecialchars($total) ?>">
              <button
                type="submit"
                class="checkout-btn"
                <?= empty($cartItems) ? 'disabled style="background:#ccc;cursor:not-allowed;"' : '' ?>>
                Continue to checkout
              </button>
            </form>

          </div>
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
</body>

</html>