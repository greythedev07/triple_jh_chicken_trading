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

// Pagination settings
$items_per_page = 12; // Number of products per page
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

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
  // Build the base query
  $baseQuery = "SELECT id, name, price, stock, image FROM products";
  $countQuery = "SELECT COUNT(*) FROM products";
  $whereClause = "";
  $params = [];

  if ($search !== '') {
    $whereClause = " WHERE name LIKE ?";
    $params[] = "%$search%";
  }

  // Get total count for pagination
  $countStmt = $db->prepare($countQuery . $whereClause);
  $countStmt->execute($params);
  $total_products = (int)$countStmt->fetchColumn();
  $total_pages = ceil($total_products / $items_per_page);

  // Get products for current page
  $productsQuery = $baseQuery . $whereClause . " ORDER BY $orderBy LIMIT $items_per_page OFFSET $offset";
  $stmt = $db->prepare($productsQuery);
  $stmt->execute($params);
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $products = [];
  $total_products = 0;
  $total_pages = 0;
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

    footer {
      background: #000;
      color: #fff;
      padding: 1.5rem 0;
      text-align: center;
      margin-top: auto;
      flex-shrink: 0;
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


    /* Pagination styling */
    .pagination {
      margin: 0;
    }

    .pagination .page-link {
      color: #000;
      border: 1px solid #dee2e6;
      padding: 0.5rem 0.75rem;
      margin: 0 2px;
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .pagination .page-link:hover {
      background-color: #f8f9fa;
      border-color: #000;
      color: #000;
    }

    .pagination .page-item.active .page-link {
      background-color: #000;
      border-color: #000;
      color: #fff;
    }

    .pagination .page-item.disabled .page-link {
      color: #6c757d;
      background-color: #fff;
      border-color: #dee2e6;
      cursor: not-allowed;
    }

    .pagination .page-item.disabled .page-link:hover {
      background-color: #fff;
      border-color: #dee2e6;
      color: #6c757d;
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
          <li class="nav-item"><a class="nav-link cart-link" href="carts/cart.php">Cart <span id="cartBadge" class="cart-badge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount > 0 ? $cartCount : '' ?></span></a></li>
          <li class="nav-item"><a class="nav-link" href="orders/orders.php">Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="useraccounts/settings.php">Settings</a></li>
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

    <?php if ($total_pages > 1): ?>
      <nav aria-label="Product pagination" class="mt-5">
        <ul class="pagination justify-content-center">
          <?php if ($current_page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $current_page - 1 ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">Previous</a>
            </li>
          <?php endif; ?>

          <?php
          // Calculate page range to display
          $start_page = max(1, $current_page - 2);
          $end_page = min($total_pages, $current_page + 2);

          // Show first page if not in range
          if ($start_page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=1&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">1</a>
            </li>
            <?php if ($start_page > 2): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php
          // Show last page if not in range
          if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $total_pages ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>"><?= $total_pages ?></a>
            </li>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $current_page + 1 ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">Next</a>
            </li>
          <?php endif; ?>
        </ul>

        <div class="text-center mt-3">
          <small class="text-muted">
            Showing <?= count($products) ?> of <?= $total_products ?> products
            (Page <?= $current_page ?> of <?= $total_pages ?>)
          </small>
        </div>
      </nav>
    <?php endif; ?>
  </main>

  <footer>
    <div class="container">
      <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small>
    </div>
  </footer>


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
            // Update cart badge
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
              cartBadge.innerText = data.cart_count;
              cartBadge.style.display = data.cart_count > 0 ? '' : 'none';
            }

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