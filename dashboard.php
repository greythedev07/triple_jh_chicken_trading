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
$current_page = max(1, (int) ($_GET['page'] ?? 1));
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
  // Build the base query for parent products with sold counts, images, price range, and total stock
  $baseQuery = "
    SELECT
      pp.id,
      pp.name,
      pp.description as parent_description,
      pp.id as parent_id,
      pp.name as parent_name,
      pp.image as parent_image,
      pp.description as parent_description,
      (SELECT MIN(p2.price) FROM products p2 WHERE p2.parent_id = pp.id) as min_price,
      (SELECT MAX(p2.price) FROM products p2 WHERE p2.parent_id = pp.id) as max_price,
      (SELECT COALESCE(SUM(p2.stock), 0) FROM products p2 WHERE p2.parent_id = pp.id) as total_stock,
      (
        SELECT COALESCE(SUM(pdi.quantity), 0)
        FROM pending_delivery_items pdi
        JOIN pending_delivery pd ON pdi.pending_delivery_id = pd.id
        JOIN products p ON pdi.product_id = p.id
        WHERE pd.status IN ('to be delivered', 'out for delivery', 'assigned', 'picked_up')
        AND p.parent_id = pp.id
      ) + (
        SELECT COALESCE(SUM(hdi.quantity), 0)
        FROM history_of_delivery_items hdi
        JOIN history_of_delivery hod ON hdi.history_id = hod.id
        JOIN products p ON hdi.product_id = p.id
        WHERE hod.id IN (
          SELECT MIN(id) FROM history_of_delivery
          GROUP BY to_be_delivered_id
        )
        AND p.parent_id = pp.id
      ) AS total_sold
    FROM parent_products pp";

  $countQuery = "SELECT COUNT(*) FROM parent_products pp";
  $whereClause = "";
  $params = [];

  if ($search !== '') {
    $whereClause = " AND (pp.name LIKE ? OR pp.description LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
  }

  // Get total count for pagination
  $countStmt = $db->prepare($countQuery . $whereClause);
  $countStmt->execute($params);
  $total_products = (int) $countStmt->fetchColumn();
  $total_pages = ceil($total_products / $items_per_page);

  // Get products for current page
  $productsQuery = $baseQuery . $whereClause . " ORDER BY " . $orderBy . "
                  LIMIT " . $items_per_page . " OFFSET " . $offset;

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
  $cartCount = (int) $stmt->fetchColumn();
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
    <link rel="icon" href="img/logo.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/footer_header.css">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
        --bg-light: rgba(255, 255, 255, 0.6);
        --bg-lighter: rgba(255, 255, 255, 0.35);
        --accent: var(--rich-amber);
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
      padding-top: 76px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    main {
      flex: 1;
      padding-bottom: 80px;
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

    .hero-section {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      padding: 3.5rem 1rem;
    }

    .hero-section h1 {
      font-weight: 700;
      font-size: 2rem;
    }

    .btn-amber-primary,
    .btn-amber-secondary {
      border: none;
      border-radius: 30px;
      padding: 0.65rem 1.5rem;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .btn-amber-primary {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      box-shadow: 0 8px 20px rgba(255, 153, 0, 0.35);
    }

    .btn-amber-primary:disabled {
      background: rgba(255, 255, 255, 0.7);
      color: var(--buttered-sand);
      border: 2px dashed rgba(109, 50, 9, 0.35);
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

    .btn-outline-secondary {
      border-color: rgba(109, 50, 9, 0.4);
      color: var(--accent-dark);
      background: transparent;
    }

    .btn-outline-secondary:hover {
      background: rgba(255, 255, 255, 0.2);
      color: var(--accent-dark);
      border-color: var(--accent-dark);
    }

    .product-card {
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid rgba(0, 0, 0, 0.08);
      background: var(--cream-panel);
      transition: 0.15s;
      height: 100%;
      box-shadow: 0 2px 16px var(--shadow-soft);
      position: relative;
    }

    .product-badge-container {
      position: absolute;
      top: 0.6rem;
      left: 0.6rem;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      z-index: 2;
    }

    .product-badge {
      display: inline-block;
      padding: 0.15rem 0.55rem;
      border-radius: 999px;
      font-size: 0.7rem;
      font-weight: 600;
      letter-spacing: 0.02em;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .product-badge.out-of-stock {
      background: #dc3545;
      color: #fff;
    }

    .product-badge.sold-count {
      background: rgba(255, 255, 255, 0.9);
      color: var(--accent-dark);
      border: 1px solid rgba(241, 143, 1, 0.4);
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      border-color: var(--accent-dark);
    }

    .product-image {
      object-fit: cover;
      width: 100%;
      height: 200px;
    }

    .modal .product-image {
        height: 100%;
    }

    .modal.fade .modal-dialog {
      transform: translateY(0);
    }

    .modal-content {
      background: var(--cream-panel);
      border: none;
      border-radius: 20px;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
      border-bottom: none;
      background: linear-gradient(90deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      border-top-left-radius: 20px;
      border-top-right-radius: 20px;
    }

    .modal-body {
      background: var(--cream-panel);
    }

    .modal-backdrop.show {
      background-color: rgba(255, 153, 0, 0.25);
      backdrop-filter: blur(2px);
    }

    /* --- LIST VIEW --- */
    .list-view .product-item {
      flex: 0 0 100%;
      max-width: 100%;
      margin-bottom: 0.25rem;
    }

    .list-view .product-card {
      display: grid;
      grid-template-columns: 160px 1fr auto;
      gap: 1.25rem;
      padding: 1.25rem;
      background: linear-gradient(120deg, rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.95));
      border-radius: 18px;
      border: 1px solid rgba(241, 143, 1, 0.35);
      box-shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
      align-items: center;
    }

    .list-view .product-card img {
      width: 150px;
      height: 150px;
      border-radius: 14px;
      object-fit: cover;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    }

    .list-view .product-card .p-3 {
      padding: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .list-view .product-card .p-3:last-child {
      padding: 0;
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

    .controls-bar form {
      gap: 0.5rem;
    }

    .controls-bar .form-control,
    .controls-bar select {
      max-width: 260px;
      background: rgba(255, 255, 255, 0.85);
      border: 1px solid rgba(255, 153, 0, 0.35);
      border-radius: 999px;
      color: var(--accent-dark);
      box-shadow: inset 0 1px 6px rgba(0, 0, 0, 0.07);
    }

    .controls-bar .form-control:focus,
    .controls-bar select:focus {
      outline: none;
      border-color: var(--rich-amber);
      box-shadow: 0 0 0 3px rgba(241, 143, 1, 0.25);
    }

    .controls-bar select {
      padding-right: 2.5rem;
      background-image: linear-gradient(45deg, transparent 50%, var(--accent-dark) 50%),
        linear-gradient(135deg, var(--accent-dark) 50%, transparent 50%);
      background-position: calc(100% - 1rem) calc(50% - 0.15rem), calc(100% - 0.75rem) calc(50% - 0.15rem);
      background-size: 0.35rem 0.35rem;
      background-repeat: no-repeat;
    }

    .controls-bar .form-control::placeholder {
      color: rgba(109, 50, 9, 0.55);
    }

    .controls-bar button {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      border: none;
      padding: 0.45rem 1.5rem;
      border-radius: 999px;
      font-weight: 600;
      box-shadow: 0 10px 20px rgba(255, 153, 0, 0.35);
    }

    .controls-bar button:hover {
      transform: translateY(-1px);
      box-shadow: 0 15px 30px rgba(255, 153, 0, 0.4);
    }

    .controls-bar button:disabled {
      background: rgba(255, 255, 255, 0.65);
      color: var(--deep-chestnut);
      box-shadow: none;
    }

    .view-toggle button {
      border: none;
      background: transparent;
      font-size: 1.3rem;
      cursor: pointer;
    }

    .view-toggle .active {
      color: var(--text);
      font-weight: bold;
    }

    .view-toggle button:not(.active) {
      color: var(--text-muted);
    }

    /* Pagination styling */
    .pagination {
      margin: 0;
    }

    .pagination .page-link {
      color: var(--text);
      border: 1px solid rgba(0, 0, 0, 0.1);
      padding: 0.5rem 0.75rem;
      margin: 0 2px;
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .pagination .page-link:hover {
      background-color: var(--bg-light);
      border-color: var(--text);
      color: var(--text);
    }

    .pagination .page-item.active .page-link {
      background-color: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }

    .pagination .page-item.disabled .page-link {
      color: var(--text-muted);
      background-color: var(--bg-lighter);
      border-color: rgba(0, 0, 0, 0.1);
      cursor: not-allowed;
    }

    .pagination .page-item.disabled .page-link:hover {
      background-color: var(--bg-lighter);
      border-color: rgba(0, 0, 0, 0.1);
      color: var(--text-muted);
    }

    /* Toast notifications */
    .toast {
      margin-bottom: 1rem;
      opacity: 1;
    }
  </style>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
        <img src="img/logo.jpg" alt="Triple JH Chicken Trading" style="height: 40px; width: auto; margin-right: 10px;">
        <span class="d-none d-md-inline">Triple JH Chicken Trading</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-center">
          <li class="nav-item">
            <a class="nav-link active" href="dashboard.php">
              <i class="fas fa-home"></i> Shop
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="about.php">
              <i class="fas fa-info-circle"></i> About
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="orders/orders.php">
              <i class="fas fa-shopping-bag"></i> Orders
            </a>
          </li>
          <li class="nav-item me-3">
            <a class="nav-link position-relative" href="carts/cart.php">
              <i class="fas fa-shopping-cart"></i> Cart
              <?php if ($cartCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">
                  <?= $cartCount ?>
                </span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="useraccounts/settings.php">
              <i class="fas fa-user"></i>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">
              <i class="fas fa-sign-out-alt"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <section class="hero-section">
    <div class="container px-4">
      <h1>Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Customer') ?>!</h1>
      <p>Fresh, quality chicken — ready for your next meal. Browse our latest cuts and deals below.</p>
    </div>
  </section>

  <main class="container px-4" id="products">
    <?php if (!empty($dbError)): ?>
      <div class="alert alert-danger">Database error: <?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <div class="controls-bar">
      <form class="d-flex w-100" method="GET" action="">
        <select name="sort" class="form-select me-2" onchange="this.form.submit()" style="width: auto; min-width: 180px;">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name (A–Z)</option>
          <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price (Low → High)</option>
          <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price (High → Low)</option>
        </select>
        <div class="d-flex flex-grow-1">
          <input type="text" class="form-control me-2" name="search" placeholder="Search products..."
            value="<?= htmlspecialchars($search) ?>">
          <button class="btn-amber-secondary">Search</button>
        </div>
      </form>
    </div>

    <div class="row g-4 mt-2" id="productContainer">
      <?php if (count($products) === 0): ?>
        <div class="col-12">
          <div class="card p-4 text-center">No products found.</div>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $product):
        // Check for product image first, then parent image, then use default
        $imgUrl = null;
        $checkedPaths = [];

        // Check product image first
        if (!empty($product['image'])) {
            $potentialPaths = [
                $product['image'],
                'uploads/items/' . $product['image'],
                'uploads/items/' . basename($product['image']),
                'uploads/parent_products/' . $product['image'],
                'uploads/parent_products/' . basename($product['image'])
            ];

            foreach ($potentialPaths as $path) {
                $checkedPaths[] = $path;
                if (file_exists($path)) {
                    $imgUrl = $path;
                    break;
                }
            }
        }

        // If no product image found, check parent image
        if (empty($imgUrl) && !empty($product['parent_image'])) {
            $potentialPaths = [
                $product['parent_image'],
                'uploads/parent_products/' . $product['parent_image'],
                'uploads/parent_products/' . basename($product['parent_image']),
                'uploads/items/' . $product['parent_image'],
                'uploads/items/' . basename($product['parent_image'])
            ];

            foreach ($potentialPaths as $path) {
                $checkedPaths[] = $path;
                if (file_exists($path)) {
                    $imgUrl = $path;
                    break;
                }
            }
        }

        // If still no image found, use default
        if (empty($imgUrl)) {
            $imgUrl = 'img/products/placeholder.jpg';
            // echo "<!-- Debug: Using default image -->";
        } else {
            // echo "<!-- Debug: Using image at: " . htmlspecialchars($imgUrl) . " -->";
        }

        // Debug: Show all checked paths
        // echo "<!-- Debug: Checked paths: " . implode(", ", array_map('htmlspecialchars', $checkedPaths)) . " -->";
        $totalStock = (int) ($product['total_stock'] ?? 0);
        $totalSold = (int) ($product['total_sold'] ?? 0);
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6 product-item mb-4">
          <div class="card h-100 product-card"
               data-product-id="<?= (int) $product['id'] ?>"
               data-parent-id="<?= (int) ($product['parent_id'] ?? 0) ?>">
            <?php if ($totalStock <= 0): ?>
              <div class="product-badge-container">
                <span class="product-badge out-of-stock">Out of Stock</span>
              </div>
            <?php endif; ?>
            <div class="product-image-container">
              <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
                class="product-image w-100" onerror="this.onerror=null; this.src='img/products/placeholder.jpg';">
            </div>
            <div class="p-3 flex-grow-1 d-flex flex-column">
              <?php if (!empty($product['parent_id'])): ?>
                <div class="small text-muted mb-1"><?= htmlspecialchars($product['parent_name']) ?></div>
              <?php endif; ?>
              <h5 class="fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h5>
              <div class="text-danger fw-bold mb-2">
                <?php
                $minPrice = (float)($product['min_price'] ?? 0);
                $maxPrice = (float)($product['max_price'] ?? 0);

                if ($minPrice > 0 && $maxPrice > 0) {
                    if ($minPrice === $maxPrice) {
                        echo '₱' . number_format($minPrice, 2);
                    } else {
                        echo '₱' . number_format($minPrice, 0) . ' - ₱' . number_format($maxPrice, 0);
                    }
                } else {
                    echo 'Price not available';
                }
                ?>
              </div>
              <small class="text-muted d-block mb-1">
                <i class="fas fa-box-open me-1"></i>
                <?php if ($totalStock > 0): ?>
                  <?= $totalStock ?> in stock
                <?php else: ?>
                  <span class="text-danger">Out of stock</span>
                <?php endif; ?>
              </small>
              <small class="text-muted">
                <i class="fas fa-chart-line me-1"></i> Sold: <?= $totalSold ?>
              </small>
            </div>
            <div class="p-3 pt-0">
              <button type="button" class="btn-amber-secondary w-100"
                onclick="event.stopPropagation(); showProductModal(<?= (int) $product['id'] ?>, true)">
                <i class="fas fa-eye me-2"></i>View Details
              </button>
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
              <a class="page-link"
                href="?page=<?= $current_page - 1 ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">Previous</a>
            </li>
          <?php endif; ?>

          <?php
          $start_page = max(1, $current_page - 2);
          $end_page = min($total_pages, $current_page + 2);

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
              <a class="page-link" href="?page=<?= $i ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">
                <?= $i ?>
              </a>
            </li>
          <?php endfor; ?>

          <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item">
              <a class="page-link"
                href="?page=<?= $total_pages ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">
                <?= $total_pages ?>
              </a>
            </li>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link"
                href="?page=<?= $current_page + 1 ?>&sort=<?= urlencode($sort) ?>&search=<?= urlencode($search) ?>">Next</a>
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

  <!-- Product Details Modal -->
  <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="productModalTitle">Product Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="productModalBody">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Notifications -->
  <!-- Toast notifications have been replaced with SweetAlert -->

  <footer>
    <div class="container text-center">
      <div class="footer-links">
        <a href="dashboard.php">Shop</a>
        <a href="about.php">About</a>
        <a href="about.php">Terms</a>
        <a href="about.php">Privacy</a>
      </div>
      <p class="copyright">&copy; <?= date('Y') ?> Triple JH Chicken Trading. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Cart badge update function (kept for backward compatibility but no longer auto-updates)
    function updateCartBadge() {
      // This function is intentionally left empty as we're removing real-time updates
      // The cart count will now only update on page load
    }

    // Image error handler with fallback
    function handleImageError(img, fallbackSrc) {
      if (img.src !== fallbackSrc) {
        img.onerror = null;
        img.src = fallbackSrc;
      } else {
        img.src = 'img/products/placeholder.jpg';
      }
    }

    // Initialize Bootstrap components
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));

    // Show success message using SweetAlert
    function showSuccess(message, callback = null) {
      Swal.fire({
        title: 'Success',
        text: message,
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3000,
        timerProgressBar: true,
        showClass: {
          popup: 'swal2-show',
          backdrop: 'swal2-backdrop-show',
          icon: 'swal2-icon-show'
        },
        hideClass: {
          popup: 'swal2-hide',
          backdrop: 'swal2-backdrop-hide',
          icon: 'swal2-icon-hide'
        },
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      }).then((result) => {
        if (typeof callback === 'function') {
          callback();
        }
      });
    }

    // Show error message using SweetAlert
    function showError(message) {
      Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: 5000,
        timerProgressBar: true,
        showClass: {
          popup: 'swal2-show',
          backdrop: 'swal2-backdrop-show',
          icon: 'swal2-icon-show'
        },
        hideClass: {
          popup: 'swal2-hide',
          backdrop: 'swal2-backdrop-hide',
          icon: 'swal2-icon-hide'
        },
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });
    }

    // Update variant details when a new variant is selected
    function updateVariantDetails(variantId, parentId) {
      if (!variantId || !parentId) return;

      console.log('Updating variant details:', { variantId, parentId });

      fetch(`get_product_details.php?id=${variantId}&parent_id=${parentId}`)
        .then(response => response.json())
        .then(data => {
          console.log('Variant data received:', data);

          if (data.status === 'success') {
            const variant = data.product;
            const variantSelector = document.getElementById('variantSelector');
            const addToCartBtn = document.querySelector('.add-to-cart-btn');
            const qtyInput = document.getElementById('productQty');
            const stockInfo = document.getElementById('stockInfo');
            const priceElement = document.querySelector('.modal-price');

            // Update variant details
            if (variantSelector) {
              variantSelector.value = variant.id;

              // Update the selected option's attributes
              const selectedOption = variantSelector.options[variantSelector.selectedIndex];
              if (selectedOption) {
                selectedOption.setAttribute('data-price', variant.price);
                selectedOption.setAttribute('data-stock', variant.stock);
                selectedOption.setAttribute('data-parent-id', variant.parent_id || '');

                // Update the display text to include stock and price
                const weightText = variant.weight ? ` (${variant.weight})` : '';
                selectedOption.text = `${variant.name}${weightText} - ${new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(variant.price)} (${variant.stock} available)`;

                // Update product name in the modal
                const productNameElement = document.querySelector('.product-name');
                if (productNameElement) {
                  productNameElement.textContent = `${variant.name}${weightText}`;
                }

                // Update product description with variant's description if available, otherwise use parent's description
                const descriptionElement = document.querySelector('.product-description');
                if (descriptionElement) {
                  // Always use variant's description if it exists, otherwise fall back to parent's description
                  descriptionElement.innerHTML = variant.description ||
                    (variant.parent && variant.parent.description ? variant.parent.description : 'No description available.');
                }
              }
            }

            // Update price
            if (priceElement) {
              priceElement.textContent = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
              }).format(variant.price);
            }

            // Update stock display and quantity input
            if (stockInfo) {
              stockInfo.textContent = `${variant.stock} available`;
            }

            if (qtyInput) {
              const maxQty = Math.max(1, variant.stock);
              qtyInput.max = maxQty;
              if (qtyInput.value > maxQty) {
                qtyInput.value = maxQty;
              }
            }

            // Update add to cart button
            if (addToCartBtn) {
              addToCartBtn.setAttribute('data-product-id', variant.id);
              addToCartBtn.setAttribute('data-parent-id', variant.parent_id || '');
              addToCartBtn.disabled = variant.stock <= 0;
            }
            const stockElement = document.getElementById('stockInfo');
            if (stockElement) {
              const stockText = variant.stock > 0
                ? `${variant.stock} in stock`
                : 'Out of stock';
              stockElement.textContent = stockText;
              stockElement.className = variant.stock > 0 ? 'text-success' : 'text-danger';

              // Update quantity input
              if (qtyInput) {
                qtyInput.max = variant.stock;
                if (parseInt(qtyInput.value) > variant.stock) {
                  qtyInput.value = variant.stock > 0 ? '1' : '0';
                }
              }

              // Update add to cart button
              if (addToCartBtn) {
                addToCartBtn.disabled = variant.stock <= 0;
                addToCartBtn.dataset.productId = variant.id;
                addToCartBtn.dataset.parentId = parentId;

                // Update button text and icon based on stock
                if (variant.stock <= 0) {
                  addToCartBtn.innerHTML = '<i class="fas fa-times-circle me-1"></i>Out of Stock';
                  addToCartBtn.classList.add('btn-secondary');
                  addToCartBtn.classList.remove('btn-amber-primary');
                } else {
                  addToCartBtn.innerHTML = '<i class="fas fa-cart-plus me-1"></i>Add to Cart';
                  addToCartBtn.classList.add('btn-amber-primary');
                  addToCartBtn.classList.remove('btn-secondary');
                }

                // Update click handler
                addToCartBtn.onclick = function() {
                  const quantity = parseInt(document.getElementById('productQty').value) || 1;
                  addToCart(variant.id, quantity, parentId, this);
                };
              }
              quantityInput.oninput = () => {
                quantityInput.value = Math.min(
                  Math.max(1, parseInt(quantityInput.value) || 1),
                  variant.stock
                );
              };
            }

            // Update add to cart button
            // Update variant image if available
            const productImage = document.querySelector('#productModal .product-image');
            if (productImage && variant.image) {
              productImage.src = variant.image.startsWith('http') || variant.image.startsWith('/')
                ? variant.image
                : `img/products/${variant.image}`;
              productImage.alt = variant.name;
            }
          }
        });
    }

    // Show product modal with details
    // isParentProduct: boolean indicating if the ID is a parent product ID
    function showProductModal(productId, isParentProduct = false) {
      const modalBody = document.getElementById('productModalBody');
      modalBody.innerHTML = `
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;

      productModal.show();

      // If it's a parent product, we need to get its first variant
      const url = isParentProduct
        ? `get_product_details.php?parent_id=${productId}`
        : `get_product_details.php?id=${productId}`;

      // Fetch product details
      fetch(url)
        .then(response => {
          if (!response.ok) {
            return response.text().then(text => {
              try {
                return Promise.reject(JSON.parse(text));
              } catch (e) {
                return Promise.reject({
                  message: 'Failed to parse server response',
                  status: response.status,
                  statusText: response.statusText,
                  responseText: text
                });
              }
            });
          }
          return response.json();
        })
        .then(data => {
          if (data.status === 'success') {
            const product = data.product;
            console.log('Product data:', product); // Debug log to check the product object
            console.log('Parent data:', product.parent); // Debug log to check parent data
            console.log('Product has parent_id:', 'parent_id' in product, product.parent_id);
            console.log('All product keys:', Object.keys(product));
            // First, try to get parent image if this is a variant
            let imgUrl = 'img/products/placeholder.jpg';
            const parentId = product.parent?.id || product.parent_id;

            // If we have a parent ID, try to get its image
            if (parentId) {
                // Try different possible locations for parent image
                imgUrl = product.parent?.image;
            }

            // If parent image not found, try the product's own image
            if (imgUrl === 'img/products/placeholder.jpg' && product.image) {
                imgUrl = product.image;
            }

            const avgRating = data.average_rating || 0;
            const reviewCount = data.review_count || 0;
            const reviews = data.reviews || [];
            const userId = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;
            const userReview = reviews.find(r => r.user_id == userId) || null;
            const hasPurchasedProduct = true; // Temporarily set to true to allow all reviews

            // Debug log
            console.log('Product image URL:', imgUrl);

            const renderStars = (rating) => {
              const fullStars = Math.floor(rating);
              const halfStar = rating - fullStars >= 0.5;
              let starsHtml = '';
              for (let i = 1; /* le  */i <= 5; i++) {
                if (i <= fullStars) starsHtml += '<i class="fas fa-star text-warning"></i>';
                else if (halfStar && i === fullStars + 1) starsHtml += '<i class="fas fa-star-half-alt text-warning"></i>';
                else starsHtml += '<i class="far fa-star text-warning"></i>';
              }
              return starsHtml;
            };

            const reviewsHtml = reviews.length === 0
              ? '<p class="text-muted mb-0">No reviews yet. Be the first to rate this product.</p>'
              : reviews.map(r => `
                  <div class="mb-3 review-item" data-review-id="${r.id}">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <strong>${r.firstname || 'User'} ${r.lastname || ''}</strong>
                        ${r.user_id == userId ? '<span class="badge bg-secondary ms-2">You</span>' : ''}
                      </div>
                      <div class="d-flex align-items-center">
                        <div class="small text-warning me-2">
                          ${renderStars(r.rating)}
                        </div>
                        ${r.user_id == userId ?
                          `<button class="btn btn-sm btn-outline-danger delete-review" data-review-id="${r.id}" title="Delete review">
                            <i class="fas fa-trash"></i>
                          </button>` : ''}
                      </div>
                    </div>
                    ${r.comment ? `<p class="mb-1 small">${r.comment}</p>` : ''}
                    <div class="d-flex justify-content-between align-items-center">
                      <small class="text-muted">${new Date(r.created_at).toLocaleDateString()}</small>
                      <div></div> <!-- Empty div to maintain flex layout -->
                    </div>
                  </div>
                `).join('');

            const existingRating = userReview?.rating || 0;
            const existingComment = userReview?.comment || '';
            const hasUserReviewed = userReview !== null;
            const submitButtonText = hasUserReviewed ? 'Update Review' : 'Submit Review';

            // Determine stock status
            const stockStatus = product.stock > 0
              ? `<span class="text-success"><i class="fas fa-check-circle me-1"></i> In Stock (${product.stock} available)</span>`
              : '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Out of Stock</span>';

            // Format price range
            let priceDisplay;
            if (product.min_price && product.max_price) {
              if (product.min_price === product.max_price) {
                priceDisplay = new Intl.NumberFormat('en-PH', {
                  style: 'currency',
                  currency: 'PHP',
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
                }).format(product.min_price);
              } else {
                const min = new Intl.NumberFormat('en-PH', {
                  style: 'currency',
                  currency: 'PHP',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0
                }).format(product.min_price);
                const max = new Intl.NumberFormat('en-PH', {
                  style: 'currency',
                  currency: 'PHP',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0
                }).format(product.max_price);
                priceDisplay = `${min} - ${max}`;
              }
            } else {
              priceDisplay = 'Price not available';
            }

            modalBody.innerHTML = `
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="position-relative d-flex justify-content-center align-items-center" style="min-height: 300px; background: #f8f9fa;">
                    <img src="${imgUrl}" class="product-image"
                         alt="${product.parent?.name}"
                         class="img-fluid rounded"
                         style="max-height: 400px; max-width: 100%; object-fit: contain;"
                         onerror="handleImageError(this, '${product.parent?.image || 'img/products/placeholder.jpg'}')">
                    ${product.stock <= 0 ? '<span class="position-absolute top-0 start-0 m-2 badge bg-danger">Out of Stock</span>' : ''}
                  </div>
                </div>
                <div class="col-md-6">
                  <h3 class="mb-2 product-name">${product.name}</h3>
                  <div class="d-flex align-items-center mb-3">
                    <div class="text-warning me-2">
                      ${renderStars(avgRating)}
                    </div>
                    <small class="text-muted">
                      ${reviewCount > 0
                        ? `(${reviewCount} ${reviewCount === 1 ? 'review' : 'reviews'})`
                        : 'No reviews yet'}
                    </small>
                  </div>
                  <div class="h3 text-primary mb-3 modal-price">${new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(product.price)}</div>
                  ${product.min_price !== product.max_price
                    ? `<div class="text-muted mb-2">Price range: ${new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(product.min_price)} - ${new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(product.max_price)}</div>`
                    : ''
                  }
                  <div class="d-flex align-items-center mb-4">
                    <div class="me-3">
                      <span class="stock-status">
                        ${product.stock > 0
                          ? `<span class="text-success"><i class="fas fa-check-circle me-1"></i> ${product.stock} in stock</span>`
                          : '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Out of Stock</span>'
                        }
                      </span>
                    </div>
                  </div>
                  ${(product.parent && product.parent.description) || product.description
                ? `<div class="mb-4">
                    <h5 class="mb-2">Description</h5>
                    <p class="mb-0">${(product.parent && product.parent.description) || product.description || 'No description available.'}</p>
                  </div>`
                : ''}

                  <!-- Variant Selector -->
                  <div class="mb-4">
                    <label for="variantSelector" class="form-label">Select Variant</label>
                    <select class="form-select" id="variantSelector" onchange="updateVariantDetails(this.value, ${product.parent ? product.parent.id : 'null'})">
                      ${product.variants && product.variants.length > 0
                        ? product.variants.map(v =>
                            `<option value="${v.id}"
                                    data-price="${v.price}"
                                    data-stock="${v.stock}"
                                    data-parent-id="${product.parent ? product.parent.id : ''}"
                                    ${v.id == product.id ? 'selected' : ''}>
                              ${v.name}${v.weight ? ` (${v.weight})` : ''} -
                              ${new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(v.price)}
                              (${v.stock} available)
                            </option>`
                          ).join('')
                        : `<option value="${product.id}" selected>
                            ${product.name} - ${new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(product.price)}
                          </option>`
                      }
                    </select>
                  </div>
                  <div class="d-flex align-items-center mb-4">
                    <div class="input-group me-3" style="width: 160px;">
                      <button class="btn btn-outline-secondary" type="button" id="decrementQty">-</button>
                      <input type="number" class="form-control text-center" value="1" min="1"
                             max="${product.stock}"
                             id="productQty"
                             oninput="this.value = Math.min(Math.max(1, parseInt(this.value) || 1), ${product.stock})">
                      <button class="btn btn-outline-secondary" type="button" id="incrementQty">+</button>
                    </div>
                    <div>
                      <span class="text-muted" id="stockInfo">
                        ${product.stock} available
                      </span>
                    </div>
                  </div>

                  <button class="btn-amber-primary btn-lg w-100 mb-2 add-to-cart-btn"
                          data-product-id="${product.id}"
                          data-parent-id="${product.parent ? product.parent.id : ''}"
                          ${product.stock <= 0 ? 'disabled' : ''}
                          onclick="event.preventDefault(); event.stopPropagation(); addToCart('${product.id}', 1, '${product.parent ? product.parent.id : ''}', this);">
                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                  </button>
                </div>
              </div>

              <hr class="my-3" />

              <div class="mt-2">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <strong>Ratings &amp; Reviews</strong>
                    <div class="small text-warning">${renderStars(avgRating)} <span class="text-muted ms-1">${avgRating.toFixed(1)} / 5</span></div>
                  </div>
                  <small class="text-muted">${reviewCount} review${reviewCount === 1 ? '' : 's'}</small>
                </div>
                <div class="border rounded p-2 mb-3" style="max-height: 220px; overflow-y: auto; background: rgba(255,255,255,0.6);">
                  ${reviewsHtml}
                </div>

                <div class="mb-2">
                  <label class="form-label mb-1">Your rating</label>
                  <div id="userRatingStars" class="text-warning" data-current-rating="${existingRating}">
                    ${[1,2,3,4,5].map(i => `
                      <i class="${i <= existingRating ? 'fas' : 'far'} fa-star rating-star" data-value="${i}" style="cursor:pointer;"></i>
                    `).join('')}
                  </div>
                </div>
                <div class="mb-2">
                  <label class="form-label mb-1">Your comment</label>
                  <textarea id="userReviewComment" class="form-control" rows="2" placeholder="Share your experience...">${existingComment}</textarea>
                </div>
                <div class="d-flex justify-content-center">
                  <button type="button" class="btn btn-sm btn-amber-secondary" id="submitReviewBtn" style="max-width: 200px;">
                    ${submitButtonText}
                  </button>
                </div>
              </div>
            `;

            // Prefill existing comment if available
            const commentEl = document.getElementById('userReviewComment');
            if (commentEl && existingComment) {
              commentEl.value = existingComment;
            }

            // Handle review deletion
            document.querySelectorAll('.delete-review').forEach(button => {
              button.addEventListener('click', async function() {
                const reviewId = this.dataset.reviewId;
                if (!reviewId) return;

                if (!confirm('Are you sure you want to delete your review?')) {
                  return;
                }

                try {
                  const response = await fetch('product_reviewing/delete_review.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `review_id=${encodeURIComponent(reviewId)}`
                  });

                  const result = await response.json();

                  if (result.status === 'success') {
                    // Remove the review from the UI
                    const reviewElement = document.querySelector(`.review-item[data-review-id="${reviewId}"]`);
                    if (reviewElement) {
                      reviewElement.remove();
                    }

                    // Show success message
                    showSuccess('Review deleted successfully');

                    // Reload the product details to update the UI
                    if (typeof updateVariantDetails === 'function') {
                      updateVariantDetails(product.id);
                    }
                  } else {
                    throw new Error(result.message || 'Failed to delete review');
                  }
                } catch (error) {
                  console.error('Error deleting review:', error);
                  showError('Failed to delete review: ' + (error.message || 'Unknown error'));
                }
              });
            });


            // Rating star interactions
            const ratingStars = document.querySelectorAll('#userRatingStars .rating-star');
            ratingStars.forEach(star => {
              star.addEventListener('click', () => {
                const value = parseInt(star.dataset.value, 10) || 0;
                document.getElementById('userRatingStars').dataset.currentRating = value;
                ratingStars.forEach(s => {
                  const v = parseInt(s.dataset.value, 10) || 0;
                  s.classList.toggle('fas', v <= value);
                  s.classList.toggle('far', v > value);
                });
              });
            });

            const submitBtn = document.getElementById('submitReviewBtn');
            if (submitBtn) {
              submitBtn.addEventListener('click', () => {
                const ratingContainer = document.getElementById('userRatingStars');
                const rating = parseInt(ratingContainer?.dataset.currentRating || '0', 10);
                const comment = document.getElementById('userReviewComment')?.value || '';

                if (!rating || rating < 1 || rating > 5) {
                  showError('Please select a rating between 1 and 5 stars.');
                  return;
                }

                fetch('product_reviewing/submit_product_review.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: new URLSearchParams({
                    product_id: String(product.id),
                    rating: String(rating),
                    comment: comment,
                  }),
                })
                  .then(r => r.json())
                  .then(res => {
                    if (res.status === 'success') {
                      showSuccess('Review submitted successfully.');
                      // Reload product modal to refresh reviews
                      showProductModal(product.id);
                    } else {
                      showError(res.message || 'Failed to submit review.');
                    }
                  })
                  .catch(() => showError('An error occurred while submitting your review.'));
              });
            }

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Quantity controls
            const qtyInput = document.getElementById('productQty');
            document.getElementById('decrementQty').addEventListener('click', () => {
              if (qtyInput.value > 1) {
                qtyInput.value = parseInt(qtyInput.value) - 1;
              }
            });

            document.getElementById('incrementQty').addEventListener('click', () => {
              const maxStock = product.stock;
              if (parseInt(qtyInput.value) < maxStock) {
                qtyInput.value = parseInt(qtyInput.value) + 1;
              }
            });
          } else {
            const errorDetails = data.error ? `
              <div class="mt-2 p-2 bg-light border rounded small text-start">
                <div><strong>Error Code:</strong> ${data.code || 'N/A'}</div>
                ${data.error ? `<div><strong>Details:</strong> ${data.error}</div>` : ''}
                ${data.debug ? `
                  <div class="mt-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#errorDetails" aria-expanded="false">
                      Show Technical Details
                    </button>
                    <div class="collapse mt-2" id="errorDetails">
                      <pre class="bg-dark text-light p-2 rounded small">${JSON.stringify(data.debug, null, 2)}</pre>
                    </div>
                  </div>
                ` : ''}
              </div>
            ` : '';

            modalBody.innerHTML = `
              <div class="alert alert-danger">
                <div class="d-flex align-items-center">
                  <i class="fas fa-exclamation-triangle me-2"></i>
                  <div>
                    <strong>Error:</strong> ${data.message || 'Failed to load product details.'}
                  </div>
                </div>
                ${errorDetails}
                <div class="text-center mt-3">
                  <button class="btn btn-primary btn-sm mt-2 w-100 add-to-cart-btn"
                        data-product-id="<?= $product['id'] ?>"
                        data-parent-id="<?= $product['parent_id'] ?? '' ?>"
                        onclick="addToCart(<?= $product['id'] ?>, 1, '<?= $product['parent_id'] ?? '' ?>', this)">
                  <i class="fas fa-cart-plus me-1"></i> Add to Cart
                </button>
                  <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                    <i class="fas fa-redo me-1"></i> Refresh Page
                  </button>
                </div>
              </div>
            `;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          const errorMessage = error.message || 'An unknown error occurred';
          const errorDetails = error.responseText ? `
            <div class="mt-2 p-2 bg-light border rounded small text-start">
              <div><strong>Status:</strong> ${error.status || 'N/A'} ${error.statusText || ''}</div>
              <div><strong>Message:</strong> ${errorMessage}</div>
              ${error.responseText ? `<div class="mt-2"><strong>Response:</strong> ${error.responseText}</div>` : ''}
              <div class="mt-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#errorDetails" aria-expanded="false">
                  Show Technical Details
                </button>
                <div class="collapse mt-2" id="errorDetails">
                  <pre class="bg-dark text-light p-2 rounded small">${JSON.stringify(error, null, 2)}</pre>
                </div>
              </div>
            </div>
          ` : '';

          modalBody.innerHTML = `
            <div class="alert alert-danger">
              <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div>
                  <strong>Error:</strong> Failed to load product details.
                </div>
              </div>
              ${errorDetails}
              <div class="text-center mt-3">
                <button class="btn btn-primary add-to-cart-btn w-100 py-2"
                        data-product-id="<?= $product['id'] ?>"
                        data-parent-id="<?= $product['parent_id'] ?? '' ?>"
                        onclick="addToCart(<?= $product['id'] ?>, 1, '<?= $product['parent_id'] ?? '' ?>', this);">
                  <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                </button>
                <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                  <i class="fas fa-redo me-1"></i> Refresh Page
                </button>
              </div>
            </div>
          `;
        });
    }


    // Add to cart from modal
    function addToCartFromModal(button, event) {
      // Prevent any default behavior and stop event propagation
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }

      const variantSelector = document.getElementById('variantSelector');
      if (!variantSelector) {
        showError('Variant selector not found');
        return;
      }

      // Get all selected options (for multiple select) or just the selected one
      const selectedOptions = [];
      for (let i = 0; i < variantSelector.options.length; i++) {
        const option = variantSelector.options[i];
        if (option.selected) {
          const quantity = parseInt(document.getElementById('productQty').value) || 1;
          const productName = option.text.split('(')[0].trim();
          const productWeight = option.text.match(/\(([^)]+)\)/)?.[1] || '';

          selectedOptions.push({
            productId: option.value,
            parentId: option.getAttribute('data-parent-id') || null,
            quantity: quantity,
            name: productName,
            weight: productWeight,
            displayText: `${quantity}x ${productName} ${productWeight ? `(${productWeight})` : ''}`
          });
        }
      }

      if (selectedOptions.length === 0) {
        showError('Please select at least one variant');
        return;
      }

      // Prepare confirmation message
      const confirmationMessage = `
        <div class="text-start">
          <p>Add the following items to your cart:</p>
          <ul class="mb-0">
            ${selectedOptions.map(item => `<li><strong>${item.displayText}</strong></li>`).join('')}
          </ul>
        </div>
      `;

      // Show confirmation dialog
      Swal.fire({
        title: 'Add to Cart',
        html: confirmationMessage,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: `Add ${selectedOptions.length} Item${selectedOptions.length > 1 ? 's' : ''}`,
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        allowEnterKey: true
      }).then(async (result) => {
        if (result.isConfirmed) {
          // Disable the button during processing
          if (button) {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...';

            try {
              // Process each selected variant
              const results = [];
              for (const item of selectedOptions) {
                // Update button data attributes to match current variant
                button.setAttribute('data-product-id', item.productId);
                if (item.parentId) {
                  button.setAttribute('data-parent-id', item.parentId);
                } else if (button.hasAttribute('data-parent-id')) {
                  button.removeAttribute('data-parent-id');
                }

                // Add item to cart
                const result = await addToCartToServer(item.productId, item.quantity, item.parentId);
                results.push({
                  success: result.status === 'success',
                  message: result.message || 'Item added to cart',
                  item: item
                });
              }

              // Show summary of results
              const successCount = results.filter(r => r.success).length;
              const failedCount = results.length - successCount;

              let resultMessage = '';
              if (successCount > 0) {
                resultMessage += `<div class="text-success mb-2">
                  <i class="fas fa-check-circle me-1"></i>
                  Successfully added ${successCount} item${successCount > 1 ? 's' : ''} to your cart.
                </div>`;
              }

              if (failedCount > 0) {
                resultMessage += `<div class="text-danger mb-2">
                  <i class="fas fa-exclamation-circle me-1"></i>
                  Failed to add ${failedCount} item${failedCount > 1 ? 's' : ''}.
                </div>`;
              }

              // Show detailed results if there are any failures
              if (failedCount > 0) {
                resultMessage += '<div class="small mt-2">';
                results.forEach((result, index) => {
                  if (!result.success) {
                    resultMessage += `
                      <div class="text-muted">
                        ${index + 1}. ${result.item.displayText}:
                        <span class="text-danger">${result.message || 'Failed to add to cart'}</span>
                      </div>`;
                  }
                });
                resultMessage += '</div>';
              }

              Swal.fire({
                title: successCount > 0 ? 'Items Added to Cart' : 'Some Items Not Added',
                html: resultMessage,
                icon: successCount > 0 ? 'success' : 'warning',
                confirmButtonText: 'Continue Shopping',
                confirmButtonColor: '#198754',
                allowOutsideClick: false
              }).then(() => {
                // Reload the page to update the UI
                window.location.reload();
              });

            } catch (error) {
              console.error('Error adding items to cart:', error);
              showError('An error occurred while adding items to cart. Please try again.');
            } finally {
              // Re-enable the button
              button.disabled = false;
              button.innerHTML = originalText;
            }
          }
        }
      });
    }

    // Helper function to add item to cart (returns a promise)
    function addToCartToServer(productId, quantity = 1, parentId = null) {
      return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        if (parentId !== null && parentId !== undefined && parentId !== '') {
          formData.append('parent_id', parentId);
        }

        fetch('carts/add_to_cart.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => resolve(data))
        .catch(error => {
          console.error('Error adding to cart:', error);
          resolve({ status: 'error', message: 'Failed to add to cart' });
        });
      });
    }

    // Add to cart function with SweetAlert confirmation
    function addToCart(productId, quantity = 1, parentId = null, button = null) {
      // Get product name from the button's data attribute or use a default
      const productCard = button ? button.closest('.product-card') : null;
      const productName = productCard ?
        (productCard.querySelector('.product-title')?.textContent || 'this item') :
        'this item';

      // Show confirmation dialog
      Swal.fire({
        title: 'Add to Cart',
        html: `Are you sure you want to add <strong>${productName}</strong> to your cart?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, add to cart',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        showCloseButton: true,
        showClass: {
          popup: 'swal2-show',
          backdrop: 'swal2-backdrop-show',
          icon: 'swal2-icon-show'
        },
        hideClass: {
          popup: 'swal2-hide',
          backdrop: 'swal2-backdrop-hide',
          icon: 'swal2-icon-hide'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          // If button is provided, disable it and show loading state
          const originalButtonText = button ? button.innerHTML : '';
          if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Adding...';
          }

          const formData = new FormData();
          formData.append('product_id', productId);
          formData.append('quantity', quantity);

          if (parentId) {
            formData.append('parent_id', parentId);
          }

          fetch('carts/add_to_cart.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              // Update cart badge
              updateCartBadge();

              // Show success message
              Swal.fire({
                title: 'Added to Cart',
                text: data.message || 'Item has been added to your cart!',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                showCloseButton: true,
                timer: 3000,
                timerProgressBar: true,
                showClass: {
                  popup: 'swal2-show',
                  backdrop: 'swal2-backdrop-show',
                  icon: 'swal2-icon-show'
                },
                hideClass: {
                  popup: 'swal2-hide',
                  backdrop: 'swal2-backdrop-hide',
                  icon: 'swal2-icon-hide'
                }
              }).then(() => {
                window.location.reload();
              });
            } else {
              // Show error message
              Swal.fire({
                title: 'Error',
                text: data.message || 'Failed to add item to cart',
                icon: 'error',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                showCloseButton: true,
                timer: 5000,
                timerProgressBar: true
              });

              // Re-enable the button if it exists
              if (button) {
                button.disabled = false;
                button.innerHTML = originalButtonText;
              }
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              title: 'Error',
              text: 'An error occurred while adding item to cart',
              icon: 'error',
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              showCloseButton: true,
              timer: 5000,
              timerProgressBar: true
            });

            // Re-enable the button if it exists
            if (button) {
              button.disabled = false;
              button.innerHTML = originalButtonText;
            }
          });
        } else if (button) {
          // Re-enable the button if user cancels
          button.disabled = false;
        }
      });
    }

    // Update variant details when a variant is selected
    document.addEventListener('change', function(e) {
      if (e.target && e.target.id === 'variantSelector') {
        const variantSelector = e.target;
        const selectedOption = variantSelector.options[variantSelector.selectedIndex];
        const productId = variantSelector.value;
        const parentId = selectedOption.getAttribute('data-parent-id') || null;
        const price = selectedOption.getAttribute('data-price');
        const stock = selectedOption.getAttribute('data-stock');
        const weight = selectedOption.textContent.match(/\(([^)]+)\)/)?.[1] || '';
        const variantName = selectedOption.textContent.split(' - ')[0].trim();

        console.log('Variant changed:', { productId, parentId, price, stock, weight });

        // Update product name to show only variant name and weight
        const productNameElement = document.querySelector('.product-name');
        if (productNameElement) {
          // Extract just the variant name without price/stock info
          const variantNameOnly = selectedOption.textContent.split('-')[0].trim();
          productNameElement.textContent = variantNameOnly;
        }

        // Update price display
        const priceElement = document.querySelector('.modal-price');
        if (priceElement && price) {
          const formattedPrice = new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
          }).format(price);
          priceElement.textContent = formattedPrice;

          // Update any price range display if it exists
          const priceRangeElement = document.querySelector('.price-range');
          if (priceRangeElement) {
            priceRangeElement.textContent = `Price range: ${formattedPrice}`;
          }
        }

        // Update stock display
        const stockInfo = document.querySelector('.stock-status');
        if (stockInfo) {
          const stockStatus = parseInt(stock) > 0
            ? `<span class="text-success"><i class="fas fa-check-circle me-1"></i> ${stock} in stock</span>`
            : '<span class="text-danger"><i class="fas fa-times-circle me-1"></i> Out of Stock</span>';
          stockInfo.innerHTML = stockStatus;

          // Update quantity input max value
          const qtyInput = document.getElementById('productQty');
          if (qtyInput) {
            const maxQty = parseInt(stock) || 1;
            qtyInput.max = maxQty;
            if (parseInt(qtyInput.value) > maxQty) {
              qtyInput.value = maxQty > 0 ? '1' : '0';
            }
          }
        }

        // Update weight display
        const weightElement = document.querySelector('.product-weight');
        const priceContainer = document.querySelector('.modal-price').parentElement;

        if (weight) {
          if (!weightElement && priceContainer) {
            const weightEl = document.createElement('div');
            weightEl.className = 'product-weight text-muted small';
            weightEl.textContent = weight;
            priceContainer.appendChild(weightEl);
          } else if (weightElement) {
            weightElement.textContent = weight;
          }
        } else if (weightElement) {
          weightElement.remove();
        }

        // Update out of stock badge
        const outOfStockBadge = document.querySelector('.position-absolute.badge.bg-danger');
        if (outOfStockBadge) {
          outOfStockBadge.style.display = stock > 0 ? 'none' : 'block';
        }

        // Update the add to cart button's data attributes and state
        const addToCartBtn = document.querySelector('.add-to-cart-btn');
        if (addToCartBtn) {
          // Remove any existing click handlers to prevent duplicates
          const newAddToCartBtn = addToCartBtn.cloneNode(true);
          addToCartBtn.parentNode.replaceChild(newAddToCartBtn, addToCartBtn);

          // Set the new button's attributes
          newAddToCartBtn.setAttribute('data-product-id', productId);
          if (parentId) {
            newAddToCartBtn.setAttribute('data-parent-id', parentId);
          } else if (newAddToCartBtn.hasAttribute('data-parent-id')) {
            newAddToCartBtn.removeAttribute('data-parent-id');
          }

          // Update button state based on stock
          if (stock <= 0) {
            newAddToCartBtn.disabled = true;
            newAddToCartBtn.innerHTML = '<i class="fas fa-times-circle me-2"></i>Out of Stock';
            newAddToCartBtn.classList.add('btn-secondary');
            newAddToCartBtn.classList.remove('btn-amber-primary');
          } else {
            newAddToCartBtn.disabled = false;
            newAddToCartBtn.innerHTML = '<i class="fas fa-cart-plus me-2"></i>Add to Cart';
            newAddToCartBtn.classList.add('btn-amber-primary');
            newAddToCartBtn.classList.remove('btn-secondary');

            // Set up the click handler to directly add to cart
            newAddToCartBtn.onclick = function(e) {
              e.preventDefault();
              e.stopPropagation();
              const productId = this.getAttribute('data-product-id');
              const parentId = this.getAttribute('data-parent-id') || null;
              addToCart(productId, 1, parentId, this);
            };
          }

          console.log('Updated add to cart button:', {
            productId: addToCartBtn.getAttribute('data-product-id'),
            parentId: addToCartBtn.getAttribute('data-parent-id'),
            stock: stock
          });
        }
      }
    });

    // Toggle between grid and list view
    const gridViewBtn = document.getElementById('gridViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    const productContainer = document.getElementById('productContainer');

    if (gridViewBtn && listViewBtn && productContainer) {
      gridViewBtn.addEventListener('click', () => {
        productContainer.classList.remove('list-view');
        gridViewBtn.classList.add('active');
        listViewBtn.classList.remove('active');
      });

      listViewBtn.addEventListener('click', () => {
        productContainer.classList.add('list-view');
        listViewBtn.classList.add('active');
        gridViewBtn.classList.remove('active');
      });
    }

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    // Add click handler for product cards
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.product-card').forEach(card => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', (e) => {
      // Don't trigger if clicking on buttons or links
      if (!e.target.closest('button') && !e.target.closest('a')) {
        const productId = card.getAttribute('data-product-id');
        if (productId) {
          // Always treat as parent product
          showProductModal(parseInt(productId), true);
        }
      }
    });
  });
});
// Handle review deletion with event delegation
document.addEventListener('click', async function(event) {
    const deleteBtn = event.target.closest('.delete-review') ||
                     (event.target.matches('i.fa-trash') && event.target.closest('button'));

    if (!deleteBtn) return;

    // Prevent default and stop propagation immediately
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    const reviewId = deleteBtn.dataset.reviewId;
    if (!reviewId) return;

    // Show SweetAlert confirmation
    const result = await Swal.fire({
        title: 'Delete Review',
        text: 'Are you sure you want to delete this review?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    // Rest of your code remains the same...
    if (!result.isConfirmed) return;

    const reviewItem = deleteBtn.closest('.review-item');
    const originalHTML = deleteBtn.innerHTML;

    try {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const formData = new FormData();
        formData.append('review_id', reviewId);

        const response = await fetch('product_reviewing/delete_review.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            if (reviewItem) {
                reviewItem.style.transition = 'opacity 0.3s';
                reviewItem.style.opacity = '0';
                setTimeout(() => {
                    reviewItem.remove();
                    Swal.fire({
    title: 'Deleted!',
    text: 'Your review has been deleted.',
    icon: 'success',
    timer: 2000,
    showConfirmButton: false
}).then(() => {
    // Refresh the page after the success message is shown
    location.reload();
});

                    const variantSelector = document.getElementById('variantSelector');
                    if (variantSelector) {
                        const productId = variantSelector.value;
                        const parentId = variantSelector.options[variantSelector.selectedIndex]?.getAttribute('data-parent-id');
                        if (productId && parentId && window.updateVariantDetails) {
                            updateVariantDetails(productId, parentId);
                        }
                    }
                }, 300);
            }
        } else {
            throw new Error(result.message || 'Failed to delete review');
        }
    } catch (error) {
        console.error('Delete error:', error);
        if (deleteBtn) {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = originalHTML;
        }
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to delete review',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
    }
}, true); // Use capture phase to ensure we catch the event first
  </script>
</body>

</html>
