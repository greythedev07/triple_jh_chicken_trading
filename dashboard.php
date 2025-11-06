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
  $total_products = (int) $countStmt->fetchColumn();
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="css/footer_header.css">

  <style>
    :root {
      --primary: #000000;
      --secondary: #ff3b30;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
    }

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

    /* Toast notifications */
    .toast {
      margin-bottom: 1rem;
      opacity: 1;
    }

    /* Variant selector */
    .variant-option {
      padding: 8px 12px;
      margin-bottom: 8px;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s;
    }

    .variant-option:hover {
      border-color: #000;
    }

    .variant-option.selected {
      border-color: var(--primary);
      background-color: #f8f9fa;
    }

    .variant-option.out-of-stock {
      opacity: 0.6;
      text-decoration: line-through;
      cursor: not-allowed;
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
      <a class="btn btn-light mt-3" href="#products">View Available Products</a>
    </div>
  </section>

  <main class="container px-4" id="products">
    <?php if (!empty($dbError)): ?>
      <div class="alert alert-danger">Database error: <?= htmlspecialchars($dbError) ?></div>
    <?php endif; ?>

    <div class="controls-bar">
      <form class="d-flex" method="GET" action="">
        <input type="text" class="form-control me-2" name="search" placeholder="Search products..."
          value="<?= htmlspecialchars($search) ?>">
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

      <?php foreach ($products as $product):
        $imgUrl = !empty($product['image']) ? $product['image'] : 'uploads/items/no-image.png';
        $hasVariants = !empty($product['variants']);
        $minPrice = $product['price'];
        $maxPrice = $product['price'];

        if ($hasVariants) {
          $prices = array_column($product['variants'], 'price');
          $minPrice = min($prices);
          $maxPrice = max($prices);
        }
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6 product-item mb-4">
          <div class="product-card h-100 d-flex flex-column" data-product-id="<?= (int) $product['id'] ?>">
            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?>"
              class="product-image w-100">
            <div class="p-3 flex-grow-1 d-flex flex-column">
              <h5 class="fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h5>
              <div class="text-danger fw-bold mb-2">
                <?php if ($hasVariants && $minPrice != $maxPrice): ?>
                  ₱<?= number_format($minPrice, 2) ?> - ₱<?= number_format($maxPrice, 2) ?>
                <?php else: ?>
                  ₱<?= number_format($product['price'], 2) ?>
                <?php endif; ?>
                <?php if ($hasVariants): ?>
                  <small class="text-muted d-block">Multiple options</small>
                <?php endif; ?>
              </div>
              <small class="text-muted">
                <i class="fas fa-box-open me-1"></i>
                <?= (int) $product['stock'] > 0 ? (int) $product['stock'] . ' in stock' : 'Out of stock' ?>
              </small>
            </div>
            <div class="p-3 pt-0">
              <button type="button" class="btn btn-dark w-100"
                onclick="event.stopPropagation(); showProductModal(<?= (int) $product['id'] ?>)">
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
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-success text-white">
        <strong class="me-auto">Success</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="successToastMessage">
        Operation completed successfully!
      </div>
    </div>
  </div>

  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header bg-danger text-white">
        <strong class="me-auto">Error</strong>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="errorToastMessage">
        An error occurred. Please try again.
      </div>
    </div>
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
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Initialize Bootstrap components
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const successToast = new bootstrap.Toast(document.getElementById('successToast'));
    const errorToast = new bootstrap.Toast(document.getElementById('errorToast'));

    // Show success message
    function showSuccess(message) {
      document.getElementById('successToastMessage').textContent = message;
      successToast.show();
    }

    // Show error message
    function showError(message) {
      document.getElementById('errorToastMessage').textContent = message;
      errorToast.show();
    }

    // Show product modal with details
    function showProductModal(productId) {
      const modalBody = document.getElementById('productModalBody');
      modalBody.innerHTML = `
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      `;

      productModal.show();

      // Fetch product details
      fetch(`get_product_details.php?id=${productId}`)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            const product = data.product;
            const imgUrl = product.image || 'uploads/items/no-image.png';
            const hasVariants = product.variants && product.variants.length > 0;
            const variantOptions = hasVariants ? product.variants.map(variant => `
              <div class="variant-option ${variant.stock <= 0 ? 'out-of-stock' : ''}" 
                   data-variant-id="${variant.id}" 
                   data-price="${variant.price}" 
                   data-stock="${variant.stock}"
                   onclick="selectVariant(this, ${variant.id}, ${variant.price}, ${variant.stock})">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong>${variant.name || 'Standard'}</strong>
                    ${variant.weight ? `<span class="text-muted ms-2">${variant.weight}kg</span>` : ''}
                  </div>
                  <div>
                    <span class="text-danger fw-bold">₱${parseFloat(variant.price).toFixed(2)}</span>
                    <span class="text-muted ms-2">${variant.stock} in stock</span>
                  </div>
                </div>
              </div>
            `).join('') : '';

            modalBody.innerHTML = `
              <div class="row">
                <div class="col-md-6">
                  <div class="position-relative">
                    <img src="${imgUrl}" alt="${product.name}" class="img-fluid rounded w-100" style="max-height: 400px; object-fit: contain;">
                    ${product.stock <= 0 ? '<span class="position-absolute top-0 start-0 m-2 badge bg-danger">Out of Stock</span>' : ''}
                  </div>
                </div>
                <div class="col-md-6">
                  <h3 class="mb-2">${product.name}</h3>
                  <div class="d-flex align-items-center mb-3">
                    <h4 class="text-danger mb-0" id="productPrice">₱${parseFloat(product.price).toFixed(2)}</h4>
                    ${product.original_price > product.price ?
                `<small class="text-muted ms-2 text-decoration-line-through">₱${parseFloat(product.original_price).toFixed(2)}</small>` :
                ''}
                  </div>

                  ${product.description
                ? `<div class="mb-4">${product.description}</div>`
                : ''}

                  ${hasVariants ? `
                    <div class="mb-4">
                      <label class="form-label">Select Option</label>
                      <div id="variantOptions">${variantOptions}</div>
                    </div>
                  ` : ''}

                  <div class="d-flex align-items-center mb-4">
                    <div class="input-group me-3" style="width: 160px;">
                      <button class="btn btn-outline-secondary" type="button" id="decrementQty">-</button>
                      <input type="number" class="form-control text-center" value="1" min="1" 
                             max="${hasVariants ? product.variants[0].stock : product.stock}" 
                             id="productQty">
                      <button class="btn btn-outline-secondary" type="button" id="incrementQty">+</button>
                    </div>
                    <div>
                      <span class="text-muted" id="stockInfo">
                        ${hasVariants ? product.variants[0].stock : product.stock} in stock
                      </span>
                    </div>
                  </div>

                  <button class="btn btn-dark btn-lg w-100 mb-3" 
                          onclick="addToCartFromModal(this, ${product.id}, ${hasVariants ? 'true' : 'false'})"
                          ${(hasVariants ? product.variants[0].stock : product.stock) <= 0 ? 'disabled' : ''}>
                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                  </button>

                </div>
              </div>
            `;

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
              const maxStock = hasVariants
                ? document.querySelector('.variant-option.selected')?.dataset.stock || product.variants[0].stock
                : product.stock;
              if (parseInt(qtyInput.value) < maxStock) {
                qtyInput.value = parseInt(qtyInput.value) + 1;
              }
            });

            // Select first variant by default
            if (hasVariants) {
              const firstVariant = document.querySelector('.variant-option:not(.out-of-stock)') ||
                document.querySelector('.variant-option');
              if (firstVariant) {
                firstVariant.classList.add('selected');
              }
            }
          } else {
            modalBody.innerHTML = `
              <div class="alert alert-danger">
                ${data.message || 'Failed to load product details.'}
              </div>
              <div class="text-center mt-3">
                <button class="btn btn-secondary" onclick="productModal.hide()">Close</button>
              </div>
            `;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          modalBody.innerHTML = `
            <div class="alert alert-danger">
              An error occurred while loading product details. Please try again.
            </div>
            <div class="text-center mt-3">
              <button class="btn btn-secondary" onclick="productModal.hide()">Close</button>
            </div>
          `;
        });
    }

    // Select variant
    function selectVariant(element, variantId, price, stock) {
      if (element.classList.contains('out-of-stock')) return;

      // Update selected state
      document.querySelectorAll('.variant-option').forEach(opt => {
        opt.classList.remove('selected', 'border-primary');
      });
      element.classList.add('selected', 'border-primary');

      // Update price and stock
      document.getElementById('productPrice').textContent = `₱${parseFloat(price).toFixed(2)}`;
      document.getElementById('stockInfo').textContent = `${stock} in stock`;
      document.getElementById('productQty').max = stock;

      // Update add to cart button
      const addToCartBtn = document.querySelector('#productModal .btn-dark');
      if (addToCartBtn) {
        addToCartBtn.disabled = stock <= 0;
      }
    }

    // Add to cart from modal
    function addToCartFromModal(button, productId, hasVariants = false) {
      const quantity = parseInt(document.getElementById('productQty').value) || 1;
      let variantId = null;

      if (hasVariants) {
        const selectedVariant = document.querySelector('.variant-option.selected');
        if (!selectedVariant) {
          showError('Please select an option');
          return;
        }
        variantId = selectedVariant.dataset.variantId;
      }

      addToCart(productId, quantity, variantId, button);
    }

    // Add to cart function
    function addToCart(productId, quantity = 1, variantId = null, triggerButton = null) {
      const candidateButton = triggerButton instanceof HTMLElement ? triggerButton : (event?.target instanceof HTMLElement ? event.target : null);
      const addButton = candidateButton instanceof HTMLButtonElement ? candidateButton : null;
      const originalText = addButton ? addButton.innerHTML : '';

      if (addButton) {
        addButton.disabled = true;
        addButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Adding...';
      }

      const formData = new FormData();
      formData.append('product_id', productId);
      formData.append('quantity', quantity);
      if (variantId) {
        formData.append('variant_id', variantId);
      }

      fetch('carts/add_to_cart.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Update or create cart badge
            const cartLink = document.querySelector('a.nav-link[href="carts/cart.php"]');
            if (cartLink) {
              let cartBadge = cartLink.querySelector('.cart-badge');
              if (!cartBadge) {
                cartBadge = document.createElement('span');
                cartBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge';
                cartLink.appendChild(cartBadge);
              }
              const cartCountValue = typeof data.cart_count === 'number' ? data.cart_count : 0;
              cartBadge.textContent = cartCountValue;
            }

            showSuccess(data.message || 'Item added to cart!');
            setTimeout(() => productModal.hide(), 1500);
          } else {
            showError(data.message || 'Failed to add item to cart');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showError('An error occurred. Please try again.');
        })
        .finally(() => {
          if (addButton) {
            addButton.disabled = false;
            addButton.innerHTML = originalText;
          }
        });
    }

    // Add to wishlist
    function addToWishlist(productId) {
      const addButton = event.target.closest('button');
      const originalText = addButton.innerHTML;
      addButton.disabled = true;
      addButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

      fetch('wishlist/add_to_wishlist.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${encodeURIComponent(productId)}`
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            showSuccess(data.message || 'Added to wishlist!');
          } else {
            showError(data.message || 'Failed to add to wishlist');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showError('An error occurred. Please try again.');
        })
        .finally(() => {
          addButton.disabled = false;
          addButton.innerHTML = '<i class="far fa-heart me-1"></i> Wishlist';
        });
    }

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
          if (!e.target.closest('button')) {
            const productId = card.getAttribute('data-product-id');
            if (productId) {
              showProductModal(parseInt(productId));
            }
          }
        });
      });
    });
  </script>
</body>

</html>