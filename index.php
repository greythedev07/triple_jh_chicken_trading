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
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

try {
  // Get total count for pagination
  $countStmt = $db->query("SELECT COUNT(*) FROM products");
  $total_products = (int)$countStmt->fetchColumn();
  $total_pages = ceil($total_products / $items_per_page);

  // Get products for current page
  $stmt = $db->query("SELECT id, name, price, stock, image FROM products ORDER BY id DESC LIMIT $items_per_page OFFSET $offset");
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html,
    body {
      height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
      background-color: #f8f9fb;
      font-family: "Inter", "Segoe UI", sans-serif;
      color: #222;
    }

    .content-wrapper {
      flex: 1 0 auto;
      display: flex;
      flex-direction: column;
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
      padding: 3rem 1rem;
      text-align: center;
    }

    .hero-section h1 {
      font-weight: 700;
      font-size: 2rem;
    }

    .hero-section p {
      max-width: 640px;
      color: #dcdcdc;
      margin: 0 auto;
    }

    .btn-white {
      background: #fff;
      color: #000;
      font-weight: 600;
      border: none;
      border-radius: 6px;
    }

    .btn-white:hover {
      background: #f0f0f0;
      color: #000;
    }

    .btn-outline-light {
      border-color: #fff;
      color: #fff;
      border-radius: 6px;
    }

    .btn-outline-light:hover {
      background: #fff;
      color: #000;
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
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid #e7e7e7;
      background: #fff;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 22px rgba(0, 0, 0, .07);
    }

    .product-image {
      object-fit: cover;
      width: 100%;
      height: 220px;
      background: #f0f0f0;
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
      color: #111;
      margin-top: 0.5rem;
    }

    .product-stock {
      font-size: 0.85rem;
      color: #666;
    }

    footer {
      flex-shrink: 0;
      background: #000;
      color: #fff;
      padding: 1.5rem 0;
      text-align: center;
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
  <div class="content-wrapper">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
      <div class="container px-4">
        <a class="navbar-brand fw-bold" href="index.php">Triple JH</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
          <span class="navbar-toggler-icon" style="color:#fff">☰</span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
          <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item"><a class="nav-link" href="useraccounts/login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="useraccounts/registration.php">Register</a></li>
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
        <p>Welcome to Triple JH Chicken Trading — your trusted partner for fresh poultry products, directly from our farms to your kitchen.</p>
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
          $imgUrl = '';
          if (!empty($prod['image'])) {
            $imgUrl = $prod['image'];
            if (!preg_match('#^(.+/).+#', $imgUrl)) {
              $imgUrl = 'img/' . ltrim($imgUrl, '/');
            }
          } else {
            $imgUrl = 'img/no-image.png';
          }
        ?>
          <div class="product-card">
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="product-image">
            <div class="card-body">
              <div>
                <div class="product-title"><?= htmlspecialchars($prod['name']) ?></div>
                <div class="product-price">₱<?= number_format($prod['price'], 2) ?></div>
                <div class="product-stock">Stock: <?= (int)$prod['stock'] ?></div>
              </div>
              <a href="useraccounts/login.php" class="btn btn-dark mt-3 w-100">Login to Add to Cart</a>
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
    <div class="container">
      <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>