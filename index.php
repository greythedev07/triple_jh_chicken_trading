<?php
session_start();

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit;
}

require_once('config.php');

// Check for account deletion success message
$account_deleted = isset($_GET['account_deleted']) && $_GET['account_deleted'] == '1';

// Pagination settings
$items_per_page = 12; // Number of products per page
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

try {
  // Get total count of parent products with active variants
  $countStmt = $db->query("SELECT COUNT(DISTINCT pp.id) FROM parent_products pp INNER JOIN products p ON pp.id = p.parent_id WHERE p.is_active = 1");
  $total_products = (int) $countStmt->fetchColumn();
  $total_pages = $items_per_page > 0 ? ceil($total_products / $items_per_page) : 0;

  // Get parent products with active variants, price ranges, and total stock for current page
  $stmt = $db->query("\n    SELECT \n      pp.id,\n      pp.name,\n      pp.description,\n      pp.image,\n      MIN(p.price) as min_price,\n      MAX(p.price) as max_price,\n      SUM(p.stock) as total_stock,\n      (\n        SELECT COALESCE(SUM(pdi.quantity), 0)\n        FROM products child\n        JOIN pending_delivery_items pdi ON pdi.product_id = child.id\n        JOIN pending_delivery pd ON pdi.pending_delivery_id = pd.id\n        WHERE child.parent_id = pp.id\n        AND child.is_active = 1\n        AND pd.status IN ('to be delivered', 'out for delivery', 'assigned', 'picked_up')\n      ) + (\n        SELECT COALESCE(SUM(hdi.quantity), 0)\n        FROM products child\n        JOIN history_of_delivery_items hdi ON hdi.product_id = child.id\n        JOIN history_of_delivery hod ON hdi.history_id = hod.id\n        WHERE child.parent_id = pp.id\n        AND child.is_active = 1\n        AND hod.id IN (\n          SELECT MIN(id) FROM history_of_delivery\n          GROUP BY to_be_delivered_id\n        )\n      ) as total_sold\n    FROM parent_products pp\n    INNER JOIN products p ON pp.id = p.parent_id AND p.is_active = 1\n    GROUP BY pp.id, pp.name, pp.description, pp.image\n    HAVING COUNT(p.id) > 0\n    ORDER BY pp.id DESC\n    LIMIT $items_per_page OFFSET $offset\n  ");
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $products = [];
  $total_products = 0;
  $total_pages = 0;
  $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Welcome | Triple JH Chicken Trading</title>
  <link rel="icon" href="img/logo.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/footer_header.css">
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

    html,
    body {
      height: 100%;
      margin: 0;
      font-family: "Inter", "Segoe UI", sans-serif;
      background: var(--buttered-sand);
      color: var(--accent-dark);
      display: flex;
      flex-direction: column;
    }

    body {
      padding-top: 50px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    main {
      flex: 1;
      padding-bottom: 80px;
    }

    .hero-section {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      padding: 3.5rem 1rem;
      text-align: center;
      box-shadow: inset 0 0 40px rgba(255, 255, 255, 0.4);
    }

    .hero-section h1 {
      font-weight: 700;
      font-size: 2rem;
    }

    .hero-section p {
      max-width: 640px;
      color: rgba(109, 50, 9, 0.85);
      margin: 0 auto;
    }

    .btn-white {
      background: #fff;
      color: var(--accent-dark);
      font-weight: 600;
      border: none;
      border-radius: 999px;
      box-shadow: 0 8px 20px rgba(241, 143, 1, 0.25);
    }

    .btn-white:hover {
      background: #fff7e3;
      color: var(--accent-dark);
      box-shadow: 0 12px 28px rgba(241, 143, 1, 0.35);
    }

    .btn-outline-light {
      border-color: rgba(255, 255, 255, 0.9);
      color: var(--accent-light);
      border-radius: 999px;
    }

    .btn-outline-light:hover {
      background: rgba(255, 255, 255, 0.9);
      color: var(--accent-dark);
    }

    main.container {
      flex-grow: 1;
      padding-top: 2rem;
      padding-bottom: 3rem;
    }

    #productGrid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .product-card {
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid rgba(241, 143, 1, 0.35);
      background: var(--cream-panel);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
      box-shadow: 0 10px 26px rgba(0, 0, 0, 0.12);
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
      box-shadow: 0 16px 38px rgba(0, 0, 0, 0.16);
    }

    .product-image {
      object-fit: cover;
      width: 100%;
      height: 220px;
      background: #fff7e3;
    }

    .card-body {
      padding: 1rem;
      text-align: left;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .product-title {
      font-size: 0.95rem;
      font-weight: 700;
      text-transform: uppercase;
    }

    .product-price {
      font-size: 0.95rem;
      color: #c0392b;
      margin-top: 0.5rem;
      font-weight: 600;
    }

    .product-stock {
      font-size: 0.85rem;
      color: rgba(109, 50, 9, 0.75);
    }

    .product-card .btn-dark {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      border: none;
      border-radius: 999px;
      font-weight: 600;
      color: var(--accent-dark);
      box-shadow: 0 10px 24px rgba(241, 143, 1, 0.35);
    }

    .product-card .btn-dark:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 32px rgba(241, 143, 1, 0.45);
    }

    @media (max-width: 767px) {
      .hero-section h1 {
        font-size: 1.4rem;
      }

      .product-image {
        height: 160px;
      }
    }

    /* Pagination styling */
    .pagination {
      margin: 0;
    }

    .pagination .page-link {
      color: var(--accent-dark);
      border: 1px solid rgba(0, 0, 0, 0.12);
      padding: 0.5rem 0.75rem;
      margin: 0 2px;
      border-radius: 6px;
      text-decoration: none;
      transition: all 0.2s ease;
      background: rgba(255, 255, 255, 0.9);
    }

    .pagination .page-link:hover {
      background-color: rgba(255, 255, 255, 0.95);
      border-color: var(--rich-amber);
      color: var(--accent-dark);
    }

    .pagination .page-item.active .page-link {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      border-color: var(--rich-amber);
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
            <a class="nav-link" href="useraccounts/login.php">
              <i class="fa-solid fa-right-to-bracket"></i> Login
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="useraccounts/registration.php">
              <i class="fa-solid fa-user-plus"></i> Register
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <?php if ($account_deleted): ?>
    <div class="container mt-4">
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Account Deleted Successfully!</strong> Your account and all associated data have been permanently removed.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Hero -->
  <section class="hero-section">
    <div class="container">
      <h1>Freshness You Can Taste, Quality You Can Trust.</h1>
      <p>Welcome to Triple JH Chicken Trading — your trusted partner for fresh poultry products, directly from our farms
        to your kitchen.</p>
      <a href="useraccounts/login.php" class="btn btn-white mt-3 me-2 px-4">Login to Order</a>
      <a href="useraccounts/registration.php" class="btn btn-outline-light mt-3 px-4">Create Account</a>
    </div>
  </section>

  <!-- Products -->
  <main class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 mb-3">
      <h4 class="fw-bold mb-0">Featured Products</h4>
    </div>

    <?php if (!empty($dbError)): ?>
      <div class="alert alert-danger">Database error: <?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <div id="productGrid">
      <?php if (count($products) === 0): ?>
        <div class="text-center col-12">
          <div class="card p-4">No products found.</div>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $prod):
        $imgUrl = !empty($prod['image']) ? $prod['image'] : 'img/no-image.png';
        if (!preg_match('#^(.+/).+#', $imgUrl)) {
          $imgUrl = 'img/' . ltrim($imgUrl, '/');
        }

        $totalStock = (int) ($prod['total_stock'] ?? 0);
        $totalSold = (int) ($prod['total_sold'] ?? 0);
        $minPrice = (float) ($prod['min_price'] ?? 0);
        $maxPrice = (float) ($prod['max_price'] ?? 0);
        $hasVariants = $minPrice !== $maxPrice;
        ?>
        <div class="product-card">
          <?php if ($totalStock <= 0): ?>
            <div class="product-badge-container">
              <span class="product-badge out-of-stock">Out of Stock</span>
            </div>
          <?php endif; ?>
          <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-image" onerror="this.onerror=null; this.src='img/products/placeholder.jpg';">
          <div class="card-body">
            <div>
              <div class="product-title"><?= htmlspecialchars($prod['name']) ?></div>
              <div class="product-price">
                <?php if ($hasVariants): ?>
                  ₱<?= number_format($minPrice, 2) ?> - ₱<?= number_format($maxPrice, 2) ?>
                <?php else: ?>
                  ₱<?= number_format($minPrice, 2) ?>
                <?php endif; ?>
              </div>
              <div class="product-stock">
                <?php if ($totalStock > 0): ?>
                  Stock: <?= $totalStock ?> &bull; Sold: <?= $totalSold ?>
                <?php else: ?>
                  <span class="text-danger">Out of Stock</span>
                <?php endif; ?>
              </div>
            </div>
            <a href="useraccounts/login.php" class="btn btn-dark mt-3 w-100">View Details</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
      <nav aria-label="Product pagination" class="mt-5">
        <ul class="pagination justify-content-center">
          <?php if ($current_page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $current_page - 1 ?>">Previous</a>
            </li>
          <?php endif; ?>

          <?php
          // Calculate page range to display
          $start_page = max(1, $current_page - 2);
          $end_page = min($total_pages, $current_page + 2);

          // Show first page if not in range
          if ($start_page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=1">1</a>
            </li>
            <?php if ($start_page > 2): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
          <?php endif; ?>

          <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
              <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>

          <?php
          // Show last page if not in range
          if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
              <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
            </li>
          <?php endif; ?>

          <?php if ($current_page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?= $current_page + 1 ?>">Next</a>
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
  </div>

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
</body>

</html>
