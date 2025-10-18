<?php
// dashboard.php — User-facing dashboard (requires login)
session_start();
require_once('config.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: useraccounts/login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

// Sorting logic
$sortOptions = [
  'newest' => 'id DESC',
  'price_low' => 'price ASC',
  'price_high' => 'price DESC',
  'name' => 'name ASC'
];

$sort = $_GET['sort'] ?? 'newest';
$orderBy = $sortOptions[$sort] ?? 'id DESC';

// Searching logic
$search = trim($_GET['search'] ?? '');

try {
  if ($search !== '') {
    $stmt = $db->prepare("SELECT id, name, price, stock, image FROM products WHERE name LIKE ? ORDER BY $orderBy");
    $stmt->execute(["%$search%"]);
  } else {
    $stmt = $db->query("SELECT id, name, price, stock, image FROM products ORDER BY $orderBy");
  }
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $products = [];
  $dbError = $e->getMessage();
}

// Fetch cart count
try {
  $stmt = $db->prepare("SELECT SUM(quantity) AS count FROM cart WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $cartCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
  $cartCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard | Triple JH Chicken Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    html,
    body {
      height: 100%;
      margin: 0;
      font-family: "Inter", "Segoe UI", sans-serif;
      background-color: #f8f9fb;
      color: #222;
      display: flex;
      flex-direction: column;
    }

    main {
      flex: 1;
      padding-bottom: 80px;
      /* adds space before footer */
    }

    .navbar {
      background-color: #000;
    }

    .navbar .nav-link,
    .navbar-brand {
      color: #fff !important;
    }

    .hero-section {
      background: #000;
      color: #fff;
      padding: 3.5rem 1rem;
    }

    .hero-section h1 {
      font-weight: 700;
      font-size: 2rem;
    }

    .product-card {
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid #e7e7e7;
      background: #fff;
      transition: 0.15s;
      height: 100%;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 22px rgba(0, 0, 0, 0.07);
    }

    .product-image {
      object-fit: cover;
      width: 100%;
      height: 220px;
      background: #f0f0f0;
    }

    /* --- LIST VIEW --- */
    .list-view .product-item {
      flex: 0 0 100%;
      max-width: 100%;
      margin-bottom: 0.25rem;
      /* space between items */
    }

    .list-view .product-card {
      display: flex;
      flex-direction: row;
      align-items: stretch;
      height: auto;
      min-height: 160px;
      padding: 0;
    }

    .list-view .product-card img {
      width: 160px;
      height: 160px;
      border-right: 1px solid #e7e7e7;
      object-fit: cover;
    }

    .list-view .product-card .p-3 {
      flex-grow: 1;
      padding: 1rem 1.25rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .list-view .product-card .fw-bold {
      font-size: 1.05rem;
      margin-bottom: 0.2rem;
    }

    .list-view .product-card small {
      margin-bottom: 0.3rem;
    }

    .list-view .product-card button {
      align-self: flex-start;
      width: auto !important;
      /* fixes full width issue */
      display: inline-block;
      padding: 0.4rem 1rem;
      font-size: 0.9rem;
      border-radius: 6px;
      margin-top: 0.5rem;
    }

    /* --- END LIST VIEW --- */

    .controls-bar {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      margin-top: 2rem;
      margin-bottom: 1rem;
    }

    .controls-bar .form-control,
    .controls-bar select {
      max-width: 260px;
    }

    .view-toggle button {
      border: none;
      background: transparent;
      font-size: 1.3rem;
      cursor: pointer;
    }

    .view-toggle .active {
      color: #000;
      font-weight: bold;
    }

    .view-toggle button:not(.active) {
      color: #888;
    }

    footer {
      background: #111;
      color: #ddd;
      padding: 2rem 0;
      text-align: center;
      margin-top: auto;
    }

    .floating-cart {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: #fff;
      color: #222;
      border-radius: 50%;
      width: 56px;
      height: 56px;
      display: flex;
      justify-content: center;
      align-items: center;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
      cursor: pointer;
      transition: 0.2s;
      z-index: 999;
    }

    .floating-cart:hover {
      transform: scale(1.07);
    }

    .cart-badge {
      position: absolute;
      top: -6px;
      right: -6px;
      background: #ff3b30;
      color: #fff;
      font-size: 0.7rem;
      padding: 3px 6px;
      border-radius: 999px;
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand fw-bold" href="../dashboard.php">Triple JH</a>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="dashboard.php">Shop</a></li>
          <li class="nav-item"><a class="nav-link" href="carts/cart.php">Cart</a></li>
          <li class="nav-item"><a class="nav-link active" href="orders/orders.php">Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="container px-4">
      <h1>Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?>!</h1>
      <p>Fresh, quality chicken — ready for your next meal. Browse our latest cuts and deals below.</p>
      <a class="btn btn-light mt-3" href="#products">View Available Products</a>
    </div>
  </section>

  <main class="container px-4" id="products">
    <?php if (!empty($dbError)): ?>
      <div class="alert alert-danger">Database error: <?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <div class="controls-bar">
      <form class="d-flex" method="GET" action="">
        <input type="text" class="form-control me-2" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
        <select name="sort" class="form-select me-2" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A–Z)</option>
          <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price (Low → High)</option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price (High → Low)</option>
        </select>
        <button class="btn btn-dark">Search</button>
      </form>
      <div class="view-toggle">
        <button id="gridViewBtn" class="active">☷</button>
        <button id="listViewBtn">☰</button>
      </div>
    </div>

    <div class="row g-4 mt-2" id="productContainer">
      <?php if (count($products) === 0): ?>
        <div class="col-12">
          <div class="card p-4 text-center">No products found.</div>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $prod):
        $imgUrl = !empty($prod['image']) ? $prod['image'] : 'uploads/items/no-image.png';
      ?>
        <div class="col-lg-3 col-md-4 col-sm-6 product-item">
          <div class="product-card h-100">
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-image">
            <div class="p-3 flex-grow-1">
              <div class="fw-bold"><?= htmlspecialchars($prod['name']) ?></div>
              <div>₱<?= number_format($prod['price'], 2) ?></div>
              <small class="text-muted">Stock: <?= (int)$prod['stock'] ?></small>
              <button class="btn btn-dark w-100 mt-2" onclick="addToCart(<?= (int)$prod['id'] ?>)">Add to Cart</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <footer>
    <div class="container">
      <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small>
    </div>
  </footer>

  <div class="floating-cart" onclick="window.location='carts/cart.php'">
    🛒
    <span id="floatingBadge" class="cart-badge" style="<?= $cartCount ? '' : 'display:none' ?>"><?= $cartCount ?: '' ?></span>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function addToCart(productId) {
      fetch('carts/add_to_cart.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'product_id=' + encodeURIComponent(productId)
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            document.getElementById('floatingBadge').innerText = data.cart_count;
            document.getElementById('floatingBadge').style.display = '';
            Swal.fire({
              icon: 'success',
              title: 'Added!',
              text: data.message,
              timer: 1200,
              showConfirmButton: false
            });
          } else {
            Swal.fire('Error', data.message, 'error');
          }
        })
        .catch(() => Swal.fire('Error', 'Server error', 'error'));
    }

    // --- View Toggle ---
    const container = document.getElementById('productContainer');
    const gridBtn = document.getElementById('gridViewBtn');
    const listBtn = document.getElementById('listViewBtn');

    gridBtn.addEventListener('click', () => {
      container.classList.remove('list-view');
      gridBtn.classList.add('active');
      listBtn.classList.remove('active');
    });

    listBtn.addEventListener('click', () => {
      container.classList.add('list-view');
      listBtn.classList.add('active');
      gridBtn.classList.remove('active');
    });
  </script>
</body>

</html>