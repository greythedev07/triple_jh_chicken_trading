<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
  header('Location: adminaccounts/admin_login.php');
  exit;
}
require_once('config.php');

// Fetch admin name
$stmt = $db->prepare("SELECT username FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | Triple JH Chicken Trading</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    /* Clickable card styles */
    .clickable-card {
      cursor: pointer;
      transition: all 0.2s ease-in-out;
      border: 1px solid rgba(0,0,0,.125);
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .clickable-card .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 1.5rem;
    }

    .clickable-card .card-title {
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
      transition: color 0.2s ease-in-out;
    }

    .clickable-card .display-6 {
      font-size: 2rem;
      font-weight: 600;
      margin: 0.75rem 0;
    }

    .clickable-card small.text-muted {
      font-size: 0.8rem;
      opacity: 0.8;
    }

    .clickable-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      border-color: var(--bs-primary);
    }

    .clickable-card .bi-arrow-right-circle {
      opacity: 0;
      transition: all 0.2s ease-in-out;
      font-size: 1.1rem;
      margin-left: 0.5rem;
    }

    .clickable-card:hover .bi-arrow-right-circle {
      opacity: 1;
      transform: translateX(3px);
    }

    .clickable-card:hover .card-title {
      color: var(--bs-primary) !important;
    }

    .clickable-card.text-warning:hover .card-title {
      color: var(--bs-warning) !important;
    }

    .clickable-card.text-info:hover .card-title {
      color: var(--bs-info) !important;
    }

    .clickable-card.text-success:hover .card-title {
      color: var(--bs-success) !important;
    }

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
      background: var(--buttered-sand);
      font-family: "Inter", "Segoe UI", sans-serif;
      color: var(--accent-dark);
    }

    .topbar {
      background: linear-gradient(90deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .topbar .logo {
      font-weight: 700;
    }

    .dashboard-container {
      display: flex;
      gap: 20px;
      padding: 30px;
    }

    .sidebar {
      background: var(--cream-panel);
      border: 1px solid rgba(241, 143, 1, 0.35);
      border-radius: 14px;
      width: 250px;
      height: fit-content;
      padding: 20px;
    }

    .sidebar a {
      display: block;
      color: rgba(109, 50, 9, 0.85);
      text-decoration: none;
      padding: 10px 0;
    }

    .sidebar a.active,
    .sidebar a:hover {
      font-weight: 600;
      color: var(--rich-amber);
    }

    .content {
      flex-grow: 1;
    }

    .card {
      border: 1px solid rgba(241, 143, 1, 0.35);
      border-radius: 16px;
      background: var(--cream-panel);
      padding: 25px;
      box-shadow: 0 14px 34px rgba(0, 0, 0, 0.16);
    }

    .btn-black {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
      color: var(--accent-dark);
      border: none;
      border-radius: 999px;
      font-weight: 600;
      box-shadow: 0 10px 26px rgba(241, 143, 1, 0.45);
    }

    .btn-black:hover {
      transform: translateY(-1px);
      box-shadow: 0 14px 34px rgba(241, 143, 1, 0.55);
    }

    table img {
      border-radius: 6px;
    }

    .table th {
      background-color: rgba(255, 255, 255, 0.9);
      font-weight: 600;
      border-bottom: 2px solid rgba(241, 143, 1, 0.45);
    }

    .table td {
      vertical-align: middle;
      padding: 12px 8px;
    }

    .table tbody tr:hover {
      background-color: rgba(255, 247, 227, 0.9);
    }

    .badge {
      font-size: 0.75em;
      padding: 0.5em 0.75em;
    }

    .card-header {
      background-color: rgba(255, 247, 227, 0.95);
      border-bottom: 1px solid rgba(241, 143, 1, 0.35);
    }

    .display-6 {
      font-size: 2rem;
      font-weight: 700;
    }

    /* SweetAlert2 modal theming */
    .swal2-popup {
      border-radius: 18px !important;
      background: var(--cream-panel) !important;
      color: var(--accent-dark) !important;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25) !important;
      border: 1px solid rgba(241, 143, 1, 0.45) !important;
    }

    .swal2-title {
      color: var(--accent-dark) !important;
      font-weight: 700 !important;
    }

    .swal2-html-container {
      color: rgba(109, 50, 9, 0.9) !important;
    }

    .swal2-confirm {
      background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end)) !important;
      color: var(--accent-dark) !important;
      border-radius: 999px !important;
      border: none !important;
      font-weight: 600 !important;
      box-shadow: 0 10px 26px rgba(241, 143, 1, 0.55) !important;
    }

    .swal2-confirm:hover {
      box-shadow: 0 14px 34px rgba(241, 143, 1, 0.65) !important;
    }

    .swal2-cancel,
    .swal2-deny {
      border-radius: 999px !important;
      border: 1px solid rgba(109, 50, 9, 0.3) !important;
      background: rgba(255, 255, 255, 0.9) !important;
      color: var(--accent-dark) !important;
    }

    .swal2-actions {
      gap: 0.5rem !important;
    }

    .swal2-icon.swal2-warning,
    .swal2-icon.swal2-error,
    .swal2-icon.swal2-question,
    .swal2-icon.swal2-success,
    .swal2-icon.swal2-info {
      border-color: var(--sunset-gradient-start) !important;
      color: var(--sunset-gradient-end) !important;
    }
  </style>
</head>

<body>
  <div class="topbar">
    <div class="logo">Triple JH — Admin</div>
    <div>
      <span>Welcome, <?php echo htmlspecialchars($admin['username']); ?></span>
      <a href="logout.php" class="text-light ms-3">Logout</a>
    </div>
  </div>

  <div class="dashboard-container">
    <div class="sidebar">
      <h5>Menu</h5>
      <a href="#overview" class="active" onclick="showSection('overview')">Dashboard Overview</a>
      <a href="#inventory" onclick="showSection('inventory')">Inventory Management</a>
      <a href="#deliveries" onclick="showSection('deliveries')">Order Management</a>
      <a href="#gcash-verification" onclick="showSection('gcash-verification')">GCash Verification</a>
      <a href="#drivers" onclick="showSection('drivers')">Driver Management</a>
      <a href="#users" onclick="showSection('users')">User Management</a>
      <a href="#analytics" onclick="showSection('analytics')">Analytics & Reports</a>
      <a href="#admin-management" onclick="showSection('admin-management')">Admin Key Management</a>
    </div>

    <div class="content">
      <!-- DASHBOARD OVERVIEW SECTION -->
      <section id="overview">
        <h3 class="fw-bold mb-4">Dashboard Overview</h3>

        <div class="row g-4 mb-4">
          <div class="col-md-3">
            <div class="card text-center clickable-card" onclick="navigateToSection('inventory')">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="card-title text-primary mb-0">Products</h5>
                  <i class="bi bi-arrow-right-circle text-primary"></i>
                </div>
                <h2 class="display-6" id="totalProducts">-</h2>
                <small class="text-muted">Products | Variants</small>
                <small class="text-muted">Click to manage</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center clickable-card" onclick="navigateToSection('deliveries')">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="card-title text-warning mb-0">Pending Orders</h5>
                  <i class="bi bi-arrow-right-circle text-warning"></i>
                </div>
                <h2 class="display-6" id="pendingOrders">-</h2>
                <small class="text-muted">Click to view</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center clickable-card" onclick="navigateToSection('drivers')">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="card-title text-info mb-0">Active Drivers</h5>
                  <i class="bi bi-arrow-right-circle text-info"></i>
                </div>
                <h2 class="display-6" id="activeDrivers">-</h2>
                <small class="text-muted">Click to manage</small>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center clickable-card" onclick="navigateToSection('users')">
              <div class="card-body">
                <h5 class="card-title text-success">Total Users</h5>
                <h2 class="display-6" id="totalUsers">-</h2>
                <small class="text-muted">Click to view</small>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Recent Orders</h5>
              </div>
              <div class="card-body">
                <div id="recentOrders"></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Low Stock Alert</h5>
              </div>
              <div class="card-body">
                <div id="lowStockAlert"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- INVENTORY SECTION -->
      <section id="inventory" style="display:none;">
        <h3 class="fw-bold mb-4">Inventory Management</h3>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div style="width: 300px;">
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input type="text" id="productSearch" class="form-control" placeholder="Search products..." onkeyup="searchProducts()">
            </div>
          </div>
          <div>
            <button class="btn text-white" onclick="showAddParentForm()" style="background-color: var(--rich-amber); border-color: var(--deep-chestnut);">
              <i class="bi bi-plus-lg me-1"></i> Add Parent Product
            </button>
          </div>
        </div>
        <!-- Add Child Product Form (Initially Hidden) -->
        <div class="card mb-4" id="addChildProductCard" style="display: none;">
          <div class="card-header bg-light">
            <h5 class="mb-0">Add Variant to <span id="parentProductName"></span></h5>
          </div>
          <div class="card-body">
            <form id="addChildProductForm" class="row g-3">
              <input type="hidden" name="parent_id" id="parentId">
              <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Variant Name" required>
              </div>
              <div class="col-md-2">
                <input type="text" name="weight" class="form-control" placeholder="Weight (e.g., 1kg)(input unit manually)" required>
              </div>
              <div class="col-md-2">
                <input type="number" name="price" class="form-control" placeholder="Price (₱)" step="0.01" min="0" required>
              </div>
              <div class="col-md-2">
                <input type="number" name="stock" class="form-control" placeholder="Stock" min="0" required>
              </div>
              <div class="col-md-3">
                <button type="submit" class="btn btn-primary me-2">Add Variant</button>
                <button type="button" class="btn btn-outline-secondary" onclick="hideAddChildForm()">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <!-- Products List -->
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0">Products List</h5>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush" id="productList">
              <!-- Products will be loaded here -->
            </div>
          </div>
        </div>
      </section>

      <!-- ORDER MANAGEMENT SECTION -->
      <section id="deliveries" style="display:none;">
        <h3 class="fw-bold mb-4">Order Management</h3>
        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Payment Mode</th>
                <th>Status</th>
                <th>Driver</th>
                <th>Total</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="deliveryTable"></tbody>
          </table>
        </div>
      </section>

      <!-- GCASH VERIFICATION SECTION -->
      <section id="gcash-verification" style="display:none;">
        <h3 class="fw-bold mb-4">GCash Payment Verification</h3>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i>
          <strong>Instructions:</strong> Review GCash payments by viewing the payment screenshots uploaded by customers.
          Verify payments only after confirming the screenshot shows a successful transaction.
        </div>
        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Payment Proof</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="gcashTable"></tbody>
          </table>
        </div>
      </section>

      <!-- DRIVER MANAGEMENT SECTION -->
      <section id="drivers" style="display:none;">
        <h3 class="fw-bold mb-4">Driver Management</h3>
        <p class="text-muted mb-4">Manage driver accounts and activation status. Drivers register themselves through the
          driver registration page.</p>

        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Driver Code</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="driverTable"></tbody>
          </table>
        </div>
      </section>

      <!-- USER MANAGEMENT SECTION -->
      <section id="users" style="display:none;">
        <h3 class="fw-bold mb-4">User Management</h3>
        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Join Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="userTable"></tbody>
          </table>
        </div>
      </section>

      <!-- ANALYTICS SECTION -->
      <section id="analytics" style="display:none;">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h3 class="fw-bold mb-0">Analytics & Reports</h3>
          <div class="btn-group" role="group">
            <button class="btn btn-primary me-2" onclick="updateAnalytics()" id="updateAnalyticsBtn">
              <i class="bi bi-arrow-repeat"></i> Update Analytics
              <span id="updateSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Sales Summary</h5>
              </div>
              <div class="card-body">
                <div id="salesSummary"></div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Top Products</h5>
              </div>
              <div class="card-body">
                <div id="topProducts"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Order Status Distribution</h5>
          </div>
          <div class="card-body">
            <div id="orderStatusChart"></div>
          </div>
        </div>

        <div class="card mt-4">
          <div class="card-header">
            <h5 class="mb-0">Export Weekly Analytics</h5>
          </div>
          <div class="card-body">
            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label for="exportWeekDate" class="form-label">Week Start/End Date</label>
                <input type="date" id="exportWeekDate" class="form-control">
              </div>
              <div class="col-md-3">
                <label for="exportMatch" class="form-label">Match By</label>
                <select id="exportMatch" class="form-select">
                  <option value="start">Week Start Date</option>
                  <option value="end">Week End Date</option>
                </select>
              </div>
              <div class="col-md-3">
                <label for="exportFormat" class="form-label">Format</label>
                <select id="exportFormat" class="form-select">
                  <option value="csv">CSV</option>
                  <option value="pdf">PDF</option>
                </select>
              </div>
              <div class="col-md-2 d-grid">
                <button class="btn btn-dark" onclick="exportWeeklyAnalytics()" id="exportWeeklyBtn">
                  Download
                </button>
              </div>
            </div>
            <div class="text-muted small mt-2">
              Search by the week start or end date and download the exact weekly analytics row.
            </div>
          </div>
        </div>
      </section>

      <!-- ADMIN KEY MANAGEMENT SECTION -->
      <section id="admin-management" style="display:none;">
        <h3 class="fw-bold mb-4">Admin Key Management</h3>
        <p class="text-muted mb-4">Create and manage admin registration keys. These keys are required for new admin
          account registration.</p>

        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Generate New Admin Key</h5>
            <button class="btn btn-black btn-sm" onclick="showGenerateKeyModal()">
              <i class="bi bi-key"></i> Generate Key
            </button>
          </div>
        </div>

        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Admin Key</th>
                <th>Created Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="adminKeyTable"></tbody>
          </table>
        </div>
      </section>
    </div>
  </div>

  <!-- Edit Product Modal -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editProductForm" enctype="multipart/form-data">
          <input type="hidden" name="id" id="editProductId">
          <input type="hidden" name="is_parent" id="editIsParent">
          <input type="hidden" name="parent_id" id="editParentId">
          <div class="modal-body">
            <div class="mb-3">
              <label for="editName" class="form-label">Product Name</label>
              <input type="text" class="form-control" id="editName" name="name" required>
            </div>
            <div class="mb-3" id="editDescriptionGroup">
              <label for="editDescription" class="form-label">Description</label>
              <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
            </div>
            <div class="row" id="editVariantFields">
              <div class="col-md-6 mb-3">
                <label for="editPrice" class="form-label">Price (₱)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="editPrice" name="price">
              </div>
              <div class="col-md-6 mb-3">
                <label for="editStock" class="form-label">Stock</label>
                <input type="number" min="0" class="form-control" id="editStock" name="stock">
              </div>
              <div class="col-md-6 mb-3">
                <label for="editWeight" class="form-label">Weight (kg) (input unit manually)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="editWeight" name="weight">
              </div>
            </div>
            <div class="mb-3" id="editImageGroup">
              <label for="editImage" class="form-label">Product Image</label>
              <input class="form-control" type="file" id="editImage" name="image" accept="image/*">
              <small class="text-muted">Leave empty to keep current image</small>
              <div id="currentImageContainer" class="mt-2"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Parent Product Modal -->
  <div class="modal fade" id="addParentProductModal" tabindex="-1" aria-labelledby="addParentProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addParentProductModalLabel">Add New Parent Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="addParentProductForm" enctype="multipart/form-data">
          <div class="modal-body">
            <div class="mb-3">
              <label for="parentName" class="form-label">Product Name</label>
              <input type="text" class="form-control" id="parentName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="parentDescription" class="form-label">Description (Optional)</label>
              <textarea class="form-control" id="parentDescription" name="description" rows="2"></textarea>
            </div>
            <div class="mb-3">
              <label for="parentImage" class="form-label">Product Image</label>
              <input type="file" class="form-control" id="parentImage" name="image" accept="image/*">
              <div class="form-text">Recommended size: 500x500px. Max 2MB.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Product</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Variant Modal -->
  <div class="modal fade" id="addVariantModal" tabindex="-1" aria-labelledby="addVariantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addVariantModalLabel">Add New Variant</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="addVariantForm">
          <div class="modal-body">
            <input type="hidden" name="parent_id" id="modalParentId">
            <div class="mb-3">
              <label for="variantName" class="form-label">Variant Name</label>
              <input type="text" class="form-control" id="variantName" name="name" required>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="variantWeight" class="form-label">Weight</label>
                <input type="text" class="form-control" id="variantWeight" name="weight" required>
                <div class="form-text">e.g., 1kg, 500g, 2.5kg</div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="variantPrice" class="form-label">Price (₱)</label>
                <input type="number" step="0.01" min="0" class="form-control" id="variantPrice" name="price" required>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="variantStock" class="form-label">Initial Stock</label>
                <input type="number" min="0" class="form-control" id="variantStock" name="stock" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Add Variant</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Order Details Modal -->
  <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fs-5" id="orderDetailsModalLabel">Order Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-2 p-md-3" id="orderDetailsContent">
          <div class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading order details...</p>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Show section and handle section-specific loading
    function showSection(id) {
        // Hide all sections
        document.querySelectorAll('section').forEach(s => s.style.display = 'none');

        // Show the selected section
        const section = document.getElementById(id);
        if (section) {
            section.style.display = 'block';

            // Update active state in sidebar
            document.querySelectorAll('.sidebar a').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${id}`) {
                    link.classList.add('active');
                }
            });

            // Load section data if needed
            if (id === 'inventory') {
                loadProducts();
            } else if (id === 'deliveries') {
                if (typeof loadDeliveries === 'function') loadDeliveries();
            } else if (id === 'drivers') {
                if (typeof loadDrivers === 'function') loadDrivers();
            } else if (id === 'gcash-verification') {
                if (typeof loadGCashOrders === 'function') loadGCashOrders();
            } else if (id === 'users') {
                if (typeof loadUsers === 'function') loadUsers();
            } else if (id === 'analytics') {
                if (typeof loadAnalytics === 'function') loadAnalytics();
            } else if (id === 'admin-management') {
                if (typeof loadAdminKeys === 'function') loadAdminKeys();
            } else if (id === 'overview') {
                if (typeof loadOverview === 'function') loadOverview();
            }
        }
    }

    // Navigate to section with smooth scrolling
    function navigateToSection(sectionId) {
        showSection(sectionId);
        // Smooth scroll to the section
        const element = document.getElementById(sectionId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Load Products
    // Load Products with hierarchical structure
   function loadProducts() {
    fetch('admin/fetch_products_hierarchical.php')
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            console.log('Fetched products:', data);
            const container = document.getElementById('productList');
            if (!container) {
                console.error('Container #productList not found');
                return;
            }
            container.innerHTML = '';

            if (!Array.isArray(data)) {
                throw new Error('Invalid data format received from server');
            }

            if (data.length === 0) {
                container.innerHTML = '<div class="p-3 text-muted">No products found. Add a new product to get started.</div>';
                return;
            }

            data.forEach((parent, pIndex) => {
                const parentId = `parent-${parent.id}`;
                const parentImg = parent.image ?
                    `<img src="${parent.image}" width="40" height="40" class="rounded me-2">` :
                    '<div class="bg-light rounded d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;"><i class="bi bi-box-seam"></i></div>';

                const hasVariants = parent.children && parent.children.length > 0;
                const variantsCount = hasVariants ? parent.children.length : 0;
                const variantsText = variantsCount === 1 ? '1 variant' : `${variantsCount} variants`;

                let parentHtml = `
                    <div class="list-group-item p-0 overflow-hidden" id="${parentId}">
                        <div class="d-flex justify-content-between align-items-center p-3 ${hasVariants ? 'cursor-pointer' : ''}"
                             onclick="${hasVariants ? `toggleVariants('${parentId}')` : ''}">
                            <div class="d-flex align-items-center">
                                ${hasVariants ? `
                                <span class="me-2">
                                    <i class="bi bi-chevron-right" id="chevron-${parentId}"></i>
                                </span>` : '<span style="width: 24px; display: inline-block;"></span>'
                                }
                                ${parentImg}
                                <div>
                                    <h6 class="mb-0 d-flex align-items-center">
                                        ${parent.name}
                                        ${hasVariants ? `<span class="badge bg-light text-dark ms-2">${variantsText}</span>` : ''}
                                    </h6>
                                    ${parent.description ? `<small class="text-muted d-block">${parent.description}</small>` : ''}
                                </div>
                            </div>
                            <div class="d-flex">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                        onclick="event.stopPropagation(); showAddChildForm(${parent.id}, '${parent.name.replace(/'/g, "\\'")}')">
                                    <i class="bi bi-plus-lg"></i> Add Variant
                                </button>
                                <button class="btn btn-sm btn-outline-secondary me-1"
                                        onclick="event.stopPropagation(); editProduct(${parent.id}, '${parent.name.replace(/'/g, "\\'")}', null, null,
                                        '${parent.description ? parent.description.replace(/'/g, "\\'") : ''}', true)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="event.stopPropagation(); deleteProduct(${parent.id}, true)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>`;

                // Add variants container (initially hidden)
                if (hasVariants) {
                    parentHtml += `
                        <div class="variants-container" id="variants-${parentId}" style="display: none; background-color: #f8f9fa;">
                            <div class="p-3 border-top">
                                <div class="small text-muted mb-2">VARIANTS</div>`;

                    parent.children.forEach((child, index) => {
                        parentHtml += `
                            <div class="d-flex justify-content-between align-items-center py-2 ${index > 0 ? 'border-top' : ''}">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 20px;">
                                        <i class="bi bi-dot text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2">${child.name}</span>
                                            <span class="badge bg-light text-dark">${child.weight || 'N/A'}</span>
                                        </div>
                                        <small class="text-muted">₱${parseFloat(child.price || 0).toFixed(2)} • ${child.stock || 0} in stock</small>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-secondary me-1"
                                            onclick="event.stopPropagation(); editProduct(${child.id}, '${child.name.replace(/'/g, "\\'")}',
                                            ${child.price || 0}, ${child.stock || 0},
                                            '${child.description ? child.description.replace(/'/g, "\\'") : ''}',
                                            false, '${child.weight || ''}')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="event.stopPropagation(); deleteProduct(${child.id}, false)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>`;
                    });

                    parentHtml += `
                            </div>
                        </div>`;
                }

                parentHtml += '</div>'; // Close parent div
                container.innerHTML += parentHtml;
            });
        })
        .catch(error => {
            console.error('Error loading products:', error);
            const container = document.getElementById('productList') || document.body;
            container.innerHTML = `
                <div class="alert alert-danger">
                    <h5>Error loading products</h5>
                    <p>${error.message}</p>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadProducts()">Retry</button>
                </div>`;
        });
}

    // Search products and variants
    function searchProducts() {
        const searchTerm = document.getElementById('productSearch').value.toLowerCase();
        const productItems = document.querySelectorAll('#productList > .list-group-item');

        if (!searchTerm.trim()) {
            // If search is empty, show all products
            productItems.forEach(item => {
                item.style.display = '';
                // Show all variants if they were previously hidden
                const variants = item.querySelector('.variants-container');
                if (variants) {
                    variants.style.display = 'none';
                    const chevron = item.querySelector('.bi-chevron-down');
                    if (chevron) {
                        chevron.classList.remove('bi-chevron-down');
                        chevron.classList.add('bi-chevron-right');
                    }
                }
            });
            return;
        }

        productItems.forEach(item => {
            const parentName = item.querySelector('h6')?.textContent?.toLowerCase() || '';
            const parentDescription = item.querySelector('small')?.textContent?.toLowerCase() || '';
            const variantsContainer = item.querySelector('.variants-container');
            let hasMatchingVariant = false;

            // Check variants if they exist
            if (variantsContainer) {
                const variants = variantsContainer.querySelectorAll('.py-2');
                variants.forEach(variant => {
                    const variantName = variant.textContent.toLowerCase();
                    if (variantName.includes(searchTerm)) {
                        variant.style.display = '';
                        hasMatchingVariant = true;
                        // Expand parent if a variant matches
                        variantsContainer.style.display = 'block';
                        const chevron = item.querySelector('.bi');
                        if (chevron) {
                            chevron.classList.remove('bi-chevron-right');
                            chevron.classList.add('bi-chevron-down');
                        }
                    } else {
                        variant.style.display = 'none';
                    }
                });
            }

            // Show/hide parent based on search term
            if (parentName.includes(searchTerm) ||
                parentDescription.includes(searchTerm) ||
                hasMatchingVariant) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function exportWeeklyAnalytics() {
      const dateEl = document.getElementById('exportWeekDate');
      const matchEl = document.getElementById('exportMatch');
      const formatEl = document.getElementById('exportFormat');
      const btn = document.getElementById('exportWeeklyBtn');

      const date = dateEl?.value;
      const match = matchEl?.value || 'start';
      const format = formatEl?.value || 'csv';

      if (!date) {
        Swal.fire({
          icon: 'warning',
          title: 'Missing Date',
          text: 'Please select a date to export.',
          confirmButtonText: 'OK'
        });
        return;
      }

      btn.disabled = true;

      const params = new URLSearchParams({
        date,
        match,
        format,
        check: '1'
      });

      fetch('admin/export_weekly_analytics.php?' + params.toString())
        .then(r => r.json())
        .then(res => {
          if (!res || res.status !== 'success') {
            throw new Error(res?.message || 'Export failed');
          }

          const dlParams = new URLSearchParams({ date, match, format });
          window.location.href = 'admin/export_weekly_analytics.php?' + dlParams.toString();
        })
        .catch(err => {
          Swal.fire({
            icon: 'error',
            title: 'Export Failed',
            text: err.message || 'Unable to export weekly analytics.',
            confirmButtonText: 'OK'
          });
        })
        .finally(() => {
          btn.disabled = false;
        });
    }

    // Toggle product variants visibility
    function toggleVariants(parentId) {
        const variantsContainer = document.getElementById(`variants-${parentId}`);
        const chevron = document.getElementById(`chevron-${parentId}`);

        if (variantsContainer.style.display === 'none' || !variantsContainer.style.display) {
            variantsContainer.style.display = 'block';
            chevron.classList.remove('bi-chevron-right');
            chevron.classList.add('bi-chevron-down');
        } else {
            variantsContainer.style.display = 'none';
            chevron.classList.remove('bi-chevron-down');
            chevron.classList.add('bi-chevron-right');
        }
    }

    // Show add parent product modal
    function showAddParentForm() {
      const modal = new bootstrap.Modal(document.getElementById('addParentProductModal'));
      const form = document.getElementById('addParentProductForm');
      form.reset();
      modal.show();
    }

    // Handle parent product form submission
    document.getElementById('addParentProductForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerHTML;

      // Show loading state
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

      // Send the request
      fetch('admin/add_parent_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.status === 'success') {
          // Close the modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('addParentProductModal'));
          modal.hide();

          // Show success message
          showSuccess('Parent product added successfully!');

          // Refresh the product list
          loadProducts();
        } else {
          throw new Error(data.message || 'Failed to add parent product');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'An error occurred while adding the product');
      })
      .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Add Product';
      });
    });

    // Show add variant modal
    function showAddChildForm(parentId, parentName) {
      // Set the parent ID and update the modal title
      document.getElementById('modalParentId').value = parentId;
      document.getElementById('addVariantModalLabel').textContent = `Add Variant to ${parentName}`;

      // Reset the form
      const form = document.getElementById('addVariantForm');
      form.reset();

      // Show the modal
      const modal = new bootstrap.Modal(document.getElementById('addVariantModal'));
      modal.show();
    }

    // Handle variant form submission
    document.getElementById('addVariantForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerHTML;

      // Show loading state
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

      // Send the request
      fetch('admin/add_child_product.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.status === 'success') {
          // Close the modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('addVariantModal'));
          modal.hide();

          // Show success message
          showSuccess('Variant added successfully!');

          // Refresh the product list
          loadProducts();
        } else {
          throw new Error(data.message || 'Failed to add variant');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'An error occurred while adding the variant');
      })
      .finally(() => {
        // Reset button state
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      });
    });

    function deleteProduct(id) {
      Swal.fire({
        title: 'Delete this product?',
        showCancelButton: true
      }).then(res => {
        if (res.isConfirmed) {
          fetch('admin/delete_product.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + id
          }).then(r => r.json()).then(d => {
            if (d.status === 'success') loadProducts();
          });
        }
      });
    }

    // Load Deliveries (Clean - No Payment Proof)
    function loadDeliveries() {
      fetch('admin/fetch_pending_deliveries.php')
        .then(res => res.json())
        .then(data => {
          const table = document.getElementById('deliveryTable');
          table.innerHTML = '';
          data.forEach((d, i) => {
            const driver = d.driver_name || '<span class="text-muted">Unassigned</span>';
            const pm = d.payment_method === 'COD' ? 'Cash on Delivery' : (d.payment_method || '');
            const rawStatus = (d.status || '').toString();
            const s = rawStatus.toLowerCase();
            const statusLabel = s === '' ? 'Pending' :
              (s === 'pending' ? 'Pending' :
                (s === 'to be delivered' || s === 'out for delivery' || s === 'picked_up' ? 'Delivering' :
                  (s === 'delivered' ? 'Delivered' :
                    (s === 'cancelled' || s === 'canceled' ? 'Cancelled' : rawStatus))));
            const badge = statusLabel === 'Pending' ? 'bg-warning' :
              (statusLabel === 'Delivering' ? 'bg-info' :
                (statusLabel === 'Delivered' ? 'bg-success' : 'bg-secondary'));
            const isCancelled = (s === 'cancelled' || s === 'canceled');
            const actions = `
              <button class="btn btn-sm btn-outline-primary me-1" onclick="viewOrderDetails(${d.id}, event)">
                <i class="bi bi-eye"></i> View
              </button>
              ${isCancelled ?
                `<button class="btn btn-sm btn-outline-secondary" onclick="deleteOrder(${d.id}, event)">
                  <i class="bi bi-trash"></i> Delete
                </button>` :
                `<button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(${d.id}, event)">
                  <i class="bi bi-x-circle"></i> Cancel
                </button>`
              }
            `;

            table.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td>${d.order_number ? d.order_number.replace(/^(TJH-)(\d{4})(\d{2})(\d{2})-(\d{4})$/, '$1$2-$3-$4-$5') : `#${d.id}`}</td>
                <td>${d.customer_name || 'N/A'}</td>
                <td>${d.delivery_address ? d.delivery_address.substring(0, 50) + '...' : 'N/A'}</td>
                <td>${pm}</td>
                <td><span class="badge ${badge}">${statusLabel}</span></td>
                <td>${driver}</td>
                <td>₱${parseFloat(d.total_amount).toFixed(2)}</td>
                <td>${actions}</td>
              </tr>`;
          });
        });
    }

    // View order details
    function viewOrderDetails(orderId, event) {
      if (event) event.stopPropagation();

      const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
      const modalElement = document.getElementById('orderDetailsModal');
      const modalTitle = modalElement.querySelector('.modal-title');
      const modalBody = modalElement.querySelector('.modal-body');

      // Show loading state
      modalBody.innerHTML = `
        <div class="text-center">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p>Loading order details...</p>
        </div>`;

      // Show the modal
      modal.show();

      // Fetch order details
      fetch(`admin/fetch_order_details.php?order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
          if (data.status === 'error') {
            throw new Error(data.message);
          }

          const order = data.order;
          const items = data.items || [];

          // Format order date
          const orderDate = new Date(order.date_requested);
          const formattedDate = orderDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });

          // Format payment method
          const paymentMethod = order.payment_method === 'COD' ? 'Cash on Delivery' :
                              (order.payment_method === 'GCASH' ? 'GCash' : order.payment_method || 'N/A');

          // Format status
          const statusMap = {
            'pending': 'Pending',
            'to be delivered': 'To be Delivered',
            'out for delivery': 'Out for Delivery',
            'picked_up': 'Picked Up',
            'delivered': 'Delivered',
            'cancelled': 'Cancelled',
            'canceled': 'Cancelled'
          };
          const status = statusMap[order.status?.toLowerCase()] || order.status || 'Pending';

          // Create order details HTML
          let html = `
            <div class="row mb-4">
              <div class="col-md-6">
                <h5>Order #${order.order_number || order.id}</h5>
                <p class="text-muted">Placed on ${formattedDate}</p>
              </div>
              <div class="col-md-6 text-md-end">
                <span class="badge bg-${status === 'Delivered' ? 'success' :
                                       status === 'Cancelled' ? 'danger' :
                                       status === 'Pending' ? 'warning' : 'info'} p-2">
                  ${status}
                </span>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">Customer Details</h6>
                  </div>
                  <div class="card-body">
                    <p class="mb-1"><strong>Name:</strong> ${order.customer_name || 'N/A'}</p>
                    <p class="mb-1"><strong>Email:</strong> ${order.email || 'N/A'}</p>
                    <p class="mb-1"><strong>Phone:</strong> ${order.phone || 'N/A'}</p>
                    <p class="mb-0"><strong>Delivery Address:</strong> ${order.delivery_address || 'N/A'}</p>
                  </div>
                </div>
              </div>

              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-header bg-light">
                    <h6 class="mb-0">Delivery & Payment</h6>
                  </div>
                  <div class="card-body">
                    <p class="mb-1"><strong>Payment Method:</strong> ${paymentMethod}</p>
                    <p class="mb-1"><strong>Assigned Driver:</strong> ${order.driver_name || 'Not assigned'}</p>
                    ${order.driver_phone ? `<p class="mb-1"><strong>Driver Contact:</strong> ${order.driver_phone}</p>` : ''}
                    ${order.gcash_payment_screenshot ? `
                      <p class="mb-1"><strong>GCash Screenshot:</strong></p>
                      <a href="${order.gcash_payment_screenshot.replace('../', '')}" target="_blank" class="d-block mb-2">
                        <img src="${order.gcash_payment_screenshot.replace('../', '')}" alt="Payment Proof" style="max-width: 100px; max-height: 100px;" class="img-thumbnail">
                      </a>
                    ` : ''}
                  </div>
                </div>
              </div>
            </div>

            <div class="card mb-4">
              <div class="card-header bg-light">
                <h6 class="mb-0">Order Items</h6>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>Item</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Total</th>
                      </tr>
                    </thead>
                    <tbody>`;

          // Add order items
          items.forEach(item => {
            const itemName = item.parent_name ?
              `${item.parent_name} - ${item.product_name || item.name}` :
              (item.product_name || item.name);

            html += `
              <tr>
                <td>
                  <div>${itemName}</div>
                  ${item.weight ? `<small class="text-muted">${item.weight}</small>` : ''}
                </td>
                <td class="text-end">₱${parseFloat(item.price || 0).toFixed(2)}</td>
                <td class="text-center">${item.quantity || 1}</td>
                <td class="text-end">₱${(parseFloat(item.price || 0) * parseInt(item.quantity || 1)).toFixed(2)}</td>
              </tr>`;
          });

          // Add order summary
          html += `
                    </tbody>
                    <tfoot class="table-light">
                      <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end">₱${parseFloat(order.total_amount || 0).toFixed(2)}</td>
                      </tr>
                      <tr>
                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                        <td class="text-end"><strong>₱${parseFloat(order.total_amount || 0).toFixed(2)}</strong></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>`;

          // Add action buttons
          if (order.status.toLowerCase() !== 'delivered' && order.status.toLowerCase() !== 'cancelled' && order.status.toLowerCase() !== 'canceled') {
            html += `
            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2">
              <button class="btn btn-sm btn-outline-secondary order-2 order-sm-1" data-bs-dismiss="modal">
                <i class="bi bi-arrow-left"></i> Back
              </button>
              <div class="d-flex flex-wrap justify-content-end gap-2 order-1 order-sm-2">
                ${order.status.toLowerCase() === 'pending' ? `
                  <button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(${order.id}, event)">
                    <i class="bi bi-x-circle"></i> Cancel Order
                  </button>
                ` : ''}
                ${!order.driver_id ? `
                  <button class="btn btn-sm btn-primary" onclick="assignDriver(${order.id}, event)">
                    <i class="bi bi-truck"></i> Assign Driver
                  </button>
                ` : ''}
              </div>
            </div>`;
          } else {
            html += `
            <div class="d-flex justify-content-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">
                <i class="bi bi-arrow-left"></i> Back to Orders
              </button>
            </div>`;
          }

          // Update modal content
          modalBody.innerHTML = html;
          modalTitle.textContent = `Order #${order.order_number || order.id}`;

          // Reinitialize tooltips if any
          if (window.bootstrap && window.bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(modalElement.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
              return new bootstrap.Tooltip(tooltipTriggerEl);
            });
          }
        })
        .catch(error => {
          console.error('Error loading order details:', error);
          modalBody.innerHTML = `
            <div class="alert alert-danger">
              <h5>Error loading order details</h5>
              <p>${error.message || 'An error occurred while loading the order details. Please try again.'}</p>
              <button class="btn btn-sm btn-outline-secondary" onclick="viewOrderDetails(${orderId})">
                <i class="bi bi-arrow-clockwise"></i> Retry
              </button>
            </div>`;
        });
    }

    // Assign driver
    function assignDriver(pending_id, event) {
      fetch('admin/fetch_drivers.php?active_only=true')
        .then(r => r.json())
        .then(drivers => {
          const options = drivers.map(d => `<option value="${d.id}">${d.name} (${d.vehicle_type || 'No vehicle'})</option>`).join('');
          Swal.fire({
            title: 'Assign Driver',
            html: `<select id="driverSelect" class="form-select">${options}</select>`,
            confirmButtonText: 'Assign'
          }).then(r => {
            if (r.isConfirmed) {
              const driver_id = document.getElementById('driverSelect').value;
              fetch('admin/assign_driver.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `pending_id=${pending_id}&driver_id=${driver_id}`
              })
                .then(r => r.json())
                .then(res => {
                  if (res.status === 'success') {
                    Swal.fire('Assigned!', '', 'success');
                    loadDeliveries();
                  } else Swal.fire('Error', res.message, 'error');
                });
            }
          });
        });
    }

    // Load GCash Orders
    function loadGCashOrders() {
      fetch('admin/fetch_gcash_orders.php')
        .then(res => res.json())
        .then(data => {
          const table = document.getElementById('gcashTable');
          table.innerHTML = '';

          // Get orders from the dedicated GCash orders endpoint
          const gcashOrders = data.status === 'success' ? data.orders.filter(order => order.payment_status === 'pending') : [];

          if (gcashOrders.length > 0) {
            gcashOrders.forEach((order, i) => {
              const paymentProof = order.gcash_payment_screenshot ?
                `<button class="btn btn-sm btn-outline-primary" onclick="viewGCashScreenshot(${order.id}, '${order.gcash_payment_screenshot}', '${order.order_number}')">
                  <i class="bi bi-image"></i> View
                </button>` :
                '<span class="text-muted">No screenshot</span>';

              const actions = `
                <button class="btn btn-success btn-sm me-2" onclick="verifyGCashPayment(${order.id}, 'verify')">
                  <i class="bi bi-check-circle"></i> Verify
                </button>
                <button class="btn btn-danger btn-sm" onclick="verifyGCashPayment(${order.id}, 'reject')">
                  <i class="bi bi-x-circle"></i> Reject
                </button>`;

              const orderDisplay = order.order_number ?
                order.order_number.replace(/^(TJH-)(\d{4})(\d{2})(\d{2})-(\d{4})$/, '$1$2-$3-$4-$5') :
                `#${order.id}`;

              table.innerHTML += `
                <tr>
                  <td>${i + 1}</td>
                  <td>${orderDisplay}</td>
                  <td>${order.customer_name || 'N/A'}</td>
                  <td>
                    <small class="text-muted">
                      ${order.phonenumber || 'No phone'}<br>
                      ${order.email || 'No email'}
                    </small>
                  </td>
                  <td>${paymentProof}</td>
                  <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                  <td>${new Date(order.date_requested).toLocaleDateString()}</td>
                  <td><span class="badge bg-warning">Pending</span></td>
                  <td>${actions}</td>
                </tr>
              `;
            });
          } else {
            table.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No GCash orders pending verification</td></tr>';
          }
        })
        .catch(err => {
          console.error('Error loading GCash orders:', err);
          document.getElementById('gcashTable').innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading data</td></tr>';
        });
    }

    // Verify GCash Payment
    function verifyGCashPayment(orderId, action) {
      const actionText = action === 'verify' ? 'verify' : 'reject';

      Swal.fire({
        title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Payment?`,
        text: `Are you sure you want to ${actionText} this GCash payment?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: action === 'verify' ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${actionText}!`,
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('admin/verify_gcash_payment.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `pending_id=${orderId}&action=${action}`
          })
            .then(r => r.json())
            .then(res => {
              if (res.status === 'success') {
                Swal.fire('Success!', res.message, 'success');
                loadGCashOrders();
                // Also refresh Order Management tab if it's visible
                if (document.getElementById('deliveries').style.display !== 'none') {
                  loadDeliveries();
                }
              } else {
                Swal.fire('Error', res.message, 'error');
              }
            })
            .catch(err => {
              console.error('Error:', err);
              Swal.fire('Error', 'An error occurred while processing the request', 'error');
            });
        }
      });
    }

    // Cancel Order
    function cancelOrder(orderId) {
      Swal.fire({
        title: 'Cancel this order?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, cancel it'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('admin/cancel_order_admin.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'order_id=' + orderId
          })
            .then(r => r.json())
            .then(res => {
              if (res.status === 'success') {
                Swal.fire('Cancelled!', res.message, 'success');
                loadDeliveries();
              } else {
                Swal.fire('Error', res.message, 'error');
              }
            });
        }
      });
    }

    // Delete Order (only for Cancelled)
    function deleteOrder(orderId) {
      Swal.fire({
        title: 'Delete this cancelled delivery?',
        text: 'This will permanently remove the record.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('admin/delete_pending_delivery.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'order_id=' + orderId
          })
            .then(r => r.json())
            .then(res => {
              if (res.status === 'success') {
                Swal.fire('Deleted!', res.message, 'success');
                loadDeliveries();
              } else {
                Swal.fire('Error', res.message, 'error');
              }
            });
        }
      });
    }

    // View GCash Screenshot (for GCash Verification tab)
function viewGCashScreenshot(orderId, screenshotPath, orderNumber) {
  // Remove any '../' from the start of the path
  let imagePath = screenshotPath.replace(/^\.\.\//g, '');

  // If the path doesn't start with 'checkout/uploads/', add it
  if (!imagePath.startsWith('checkout/uploads/')) {
    imagePath = imagePath;
  }

  // Ensure the path is relative to the site root
  imagePath = './' + imagePath.replace(/^\/+/, '');

  console.log('Loading image from:', imagePath);

  Swal.fire({
    title: 'Payment Screenshot',
    html: `
      <div class="text-center">
        <img src="${imagePath}" alt="Payment Screenshot"
             style="max-width: 100%; max-height: 400px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
             onerror="this.style.display='none'; document.getElementById('noImageMsg').style.display='block';">
        <div id="noImageMsg" style="display:none; padding: 40px; background: #f8f9fa; border-radius: 8px; color: #6c757d;">
          <i class="bi bi-image" style="font-size: 3rem;"></i><br>
          <small>Image not found or path incorrect</small><br>
          <small style="color: #dc3545;">Path: ${imagePath}</small>
        </div>
        <div class="mt-3">
          <small class="text-muted">Order ID: #${orderNumber}</small>
        </div>
      </div>
    `,
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: '<i class="bi bi-check-circle"></i> Verify & Approve',
    denyButtonText: '<i class="bi bi-x-circle"></i> Reject Payment',
    cancelButtonText: 'Close',
    confirmButtonColor: '#198754',
    denyButtonColor: '#dc3545',
    width: '600px',
    didOpen: () => {
      console.log('Image path being loaded:', imagePath);
    }
  }).then((result) => {
    if (result.isConfirmed) {
      verifyGCashPayment(orderId, 'verify');
    } else if (result.isDenied) {
      verifyGCashPayment(orderId, 'reject');
    }
  });
}

    // Function to open edit modal for a product
    function editProduct(id, name, price = null, stock = null, description = '', isParent = false, weight = null) {
        // Reset form and show loading state
        const form = document.getElementById('editProductForm');
        form.reset();

        // Set form values
        document.getElementById('editProductId').value = id;
        document.getElementById('editIsParent').value = isParent ? '1' : '0';
        document.getElementById('editProductModalLabel').textContent = isParent ? 'Edit Product' : 'Edit Variant';

        // Show/hide fields based on product type
        const variantFields = document.getElementById('editVariantFields');
        const descriptionGroup = document.getElementById('editDescriptionGroup');
        const imageGroup = document.getElementById('editImageGroup');

        if (isParent) {
            variantFields.style.display = 'none';
            descriptionGroup.style.display = 'block';
            imageGroup.style.display = 'block';
        } else {
            variantFields.style.display = 'flex';
            descriptionGroup.style.display = 'none';
            imageGroup.style.display = 'none';

            // Clear any existing image preview for variants
            document.getElementById('currentImageContainer').innerHTML = '';
        }

        // Set common fields
        document.getElementById('editName').value = name || '';
        document.getElementById('editDescription').value = description || '';

        // Set variant-specific fields
        if (!isParent) {
            document.getElementById('editPrice').value = price || '0';
            document.getElementById('editStock').value = stock || '0';
            document.getElementById('editWeight').value = weight || '0';
        }

        // Show loading state for image fetch
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';

        // Fetch the current product to get the image
        fetch(`admin/fetch_products_hierarchical.php`)
            .then(response => response.json())
            .then(allProducts => {
                let product = null;

                // Find the product in the hierarchy
                if (isParent) {
                    product = allProducts.find(p => p.id == id);
                } else {
                    for (const parent of allProducts) {
                        if (parent.children) {
                            const variant = parent.children.find(v => v.id == id);
                            if (variant) {
                                product = variant;
                                document.getElementById('editParentId').value = parent.id;
                                break;
                            }
                        }
                    }
                }

                // Update image preview if product was found and it's a parent product
                const imageContainer = document.getElementById('currentImageContainer');
                if (isParent) {
                    if (product && product.image) {
                        const imagePath = product.image.startsWith('http') ? product.image : `../${product.image}`;
                        imageContainer.innerHTML = `
                            <div class="current-image-preview">
                                <p class="mb-1">Current Image:</p>
                                <img src="${imagePath}"
                                     class="img-thumbnail"
                                     style="max-height: 100px;">
                            </div>
                        `;
                    } else {
                        imageContainer.innerHTML = '<p class="text-muted">No image available</p>';
                    }
                }

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error loading product image:', error);
                document.getElementById('currentImageContainer').innerHTML = '<p class="text-muted">No image available</p>';
                const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
                modal.show();
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Save Changes';
            });
    }

    // Handle edit form submission
    document.getElementById('editProductForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        const fileInput = form.querySelector('input[type="file"]');

        // Validate file type if a file is selected
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    title: 'Invalid File Type',
                    text: 'Please upload an image file (JPEG, PNG, GIF, or WebP)',
                    icon: 'error'
                });
                return;
            }

            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    title: 'File Too Large',
                    text: 'Maximum file size is 5MB',
                    icon: 'error'
                });
                return;
            }
        }

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        // Debug: Log form data (remove in production)
        for (let [key, value] of formData.entries()) {
            if (key !== 'image') {
                console.log(key, value);
            } else {
                console.log(key, 'File selected:', value.name);
            }
        }

        // Send the request
        fetch('admin/edit_product.php', {
            method: 'POST',
            body: formData,
            // Don't set Content-Type header, let the browser set it with boundary
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server returned an invalid response');
                    }
                }).then(json => {
                    // If we got JSON, but it's an error
                    if (json && json.status === 'error') {
                        throw new Error(json.message || 'Error updating product');
                    }
                    throw new Error('Failed to update product');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editProductModal'));
                modal.hide();

                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Product updated successfully',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Refresh the product list after a short delay
                setTimeout(loadProducts, 500);
            } else {
                throw new Error(data.message || 'Failed to update product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'An error occurred while updating the product. Please try again.',
                icon: 'error'
            });
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Save Changes';
        });
    });

    // Function to handle product deletion
    function deleteProduct(id, isParent) {
        if (isParent) {
            // For parent products, show a confirmation dialog with options
            Swal.fire({
                title: 'Delete Product',
                text: 'Do you want to delete just this parent product or all its variants?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                showDenyButton: true,
                denyButtonText: 'Delete Parent & All Variants',
                denyButtonColor: '#dc3545',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmDelete(id, true, false);
                } else if (result.isDenied) {
                    confirmDelete(id, true, true);
                }
            });
        } else {
            // For child products, show a simple confirmation
            Swal.fire({
                title: 'Delete Variant',
                text: 'Are you sure you want to delete this variant?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmDelete(id, false, false);
                }
            });
        }
    }

    // Function to handle the actual delete request
    function confirmDelete(id, isParent, deleteChildren) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_parent', isParent);
        if (isParent) {
            formData.append('delete_children', deleteChildren);
        }

        fetch('admin/delete_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showSuccess(data.message);
                loadProducts(); // Refresh the product list
            } else {
                showError(data.message || 'Failed to delete product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while deleting the product');
        });
    }

    // Show success message
    function showSuccess(message) {
        Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }

    // Show error message
    function showError(message) {
        Swal.fire({
            title: 'Error!',
            text: message,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }

    // Load Overview Data
    function loadOverview() {
      fetch('admin/get_stats.php')
        .then(res => res.json())
        .then(data => {
          const totalProducts = data.totalProducts || 0;
          const parentProducts = data.parentProducts || 0;
          const variantProducts = data.variantProducts || 0;

          // Update the products card with detailed counts
          const productsCard = document.getElementById('totalProducts');
          if (productsCard) {
            productsCard.innerHTML = `
              <div class="d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="me-2">${parentProducts} ${parentProducts !== 1 ? '' : ''}</span>
                  <span class="text-muted">•</span>
                  <span class="ms-2">${variantProducts} ${variantProducts !== 1 ? '' : ''}</span>
                </div>
              </div>
            `;
          } else {
            console.error('Products card element not found');
          }

          // Update other stats
          document.getElementById('pendingOrders').textContent = data.pendingOrders || 0;
          document.getElementById('activeDrivers').textContent = data.activeDrivers || 0;
          document.getElementById('totalUsers').textContent = data.totalUsers || 0;
        })
        .catch(error => {
          console.error('Error loading overview:', error);
        });

      fetch('admin/get_recent_orders.php')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('recentOrders');
          if (data.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent orders</p>';
            return;
          }
          container.innerHTML = data.map(order => {
            const orderDisplay = order.order_number ?
              order.order_number.replace(/^(TJH-)(\d{4})(\d{2})(\d{2})-(\d{4})$/, '$1$2-$3-$4-$5') :
              `#${order.id}`;

            return `
            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
              <div>
                <strong>Order ${orderDisplay}</strong>
                <small class="text-muted d-block">${order.customer_name}</small>
                <small class="text-muted">${new Date(order.date_requested).toLocaleDateString()}</small>
              </div>
              <span class="badge ${order.status === 'pending' ? 'bg-warning' : 'bg-info'}">${order.status}</span>
            </div>
          `;
          }).join('');
        });

      fetch('admin/get_low_stock.php')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('lowStockAlert');
          if (data.length === 0) {
            container.innerHTML = '<p class="text-success">All products have sufficient stock</p>';
            return;
          }
          container.innerHTML = data.map(product => `
            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
              <div>
                <strong>${product.name}</strong>
                <small class="text-muted d-block">Low stock alert</small>
              </div>
              <span class="badge bg-danger">${product.stock} left</span>
            </div>
          `).join('');
        });
    }

    // Load Drivers
    function loadDrivers() {
      fetch('admin/fetch_drivers.php')
        .then(res => res.json())
        .then(data => {
          const table = document.getElementById('driverTable');
          table.innerHTML = '';
          data.forEach((d, i) => {
            const statusBadge = d.is_active ? 'bg-success' : 'bg-secondary';
            const statusText = d.is_active ? 'Active' : 'Inactive';
            table.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td><code>${d.driver_code}</code></td>
                <td><strong>${d.name}</strong></td>
                <td>${d.email}</td>
                <td>${d.phone}</td>
                <td>${d.vehicle_type || '<span class="text-muted">N/A</span>'}</td>
                <td><span class="badge ${statusBadge}">${statusText}</span></td>
                <td class="d-flex gap-2">
                  <button class="btn btn-sm btn-outline-primary" onclick="viewDriverProfile(${d.id})" title="View Driver Profile">
                    <i class="bi bi-person-lines-fill"></i> Profile
                  </button>
                  <button class="btn btn-sm btn-outline-${d.is_active ? 'warning' : 'success'}" onclick="event.stopPropagation(); toggleDriverStatus(${d.id}, ${d.is_active})" title="${d.is_active ? 'Deactivate' : 'Activate'} Driver">
                    ${d.is_active ? 'Deactivate' : 'Activate'}
                  </button>
                </td>
              </tr>`;
          });
        });
    }

    // Load Users
    function loadUsers() {
      fetch('admin/fetch_users.php')
        .then(res => res.json())
        .then(data => {
          const table = document.getElementById('userTable');
          table.innerHTML = '';
          data.forEach((u, i) => {
            table.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td><strong>${u.firstname} ${u.lastname}</strong></td>
                <td>${u.email}</td>
                <td>${u.phonenumber || '<span class="text-muted">N/A</span>'}</td>
                <td>${u.address ? u.address.substring(0, 30) + '...' : '<span class="text-muted">N/A</span>'}</td>
                <td><small class="text-muted">${new Date(u.created_at).toLocaleDateString()}</small></td>
                <td>
                  <button class="btn btn-sm btn-outline-info" onclick="viewUserDetails(${u.id})" title="View User Details">
                    <i class="bi bi-eye"></i> View
                  </button>
                </td>
              </tr>`;
          });
        });
    }

    // Function to load top products
    function loadTopProducts() {
      fetch('admin/get_top_products.php')
        .then(res => {
          if (!res.ok) {
            throw new Error('Network response was not ok');
          }
          return res.json();
        })
        .then(products => {
          const container = document.getElementById('topProducts');
          if (!container) return;

          if (products.error) {
            container.innerHTML = `<div class="alert alert-warning">${products.error}</div>`;
            return;
          }

          if (!products.length) {
            container.innerHTML = '<p class="text-muted">No product data available yet.</p>';
            return;
          }

          let html = '<div class="list-group">';
          products.forEach((product, index) => {
            const stars = Math.round(product.average_rating || 0);
            const starHtml = '<i class="bi bi-star-fill text-warning"></i>'.repeat(stars) +
                            '<i class="bi bi-star text-muted"></i>'.repeat(5 - stars);

            html += `
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1">${product.name}</h6>
                    <div class="small text-muted">
                      ${product.total_sold} sold • ${starHtml} (${product.review_count || 0})
                    </div>
                  </div>
                  <span class="badge bg-primary rounded-pill">#${index + 1}</span>
                </div>
              </div>
            `;
          });
          html += '</div>';
          container.innerHTML = html;
        })
        .catch(error => {
          console.error('Error loading top products:', error);
          const container = document.getElementById('topProducts');
          if (container) {
            container.innerHTML = '<div class="alert alert-warning">Failed to load top products. Please try again later.</div>';
          }
        });
    }

    // Load Analytics
    function loadAnalytics() {
      fetch('admin/get_sales_summary.php')
        .then(res => {
          if (!res.ok) {
            throw new Error('Network response was not ok');
          }
          return res.json();
        })
        .then(response => {
          if (response.status !== 'success') {
            throw new Error(response.message || 'Failed to load analytics data');
          }

          // Load top products after loading sales summary
          loadTopProducts();

          const data = response.data;
          document.getElementById('salesSummary').innerHTML = `
            <div class="row text-center">
              <div class="col-4">
                <div class="p-3">
                  <h3 class="text-primary mb-1">₱${data.totalRevenue || '0.00'}</h3>
                  <small class="text-muted">Total Revenue</small>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3">
                  <h3 class="text-success mb-1">${data.totalOrders || 0}</h3>
                  <small class="text-muted">Total Orders</small>
                </div>
              </div>
              <div class="col-4">
                <div class="p-3">
                  <h3 class="text-info mb-1">₱${data.averageOrder || '0.00'}</h3>
                  <small class="text-muted">Avg Order Value</small>
                </div>
              </div>
            </div>
            <div class="text-center mt-2 text-muted small">
              ${data.weekStart} to ${data.weekEnd}
            </div>
          `;
        });

      fetch('admin/get_top_products.php')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('topProducts');
          if (!Array.isArray(data) || data.length === 0) {
            container.innerHTML = '<p class="text-muted">No data available</p>';
            return;
          }

          const topThree = data.slice(0, 3);
          container.innerHTML = topThree.map((product, i) => {
            const totalSold = product.total_sold ?? 0;
            const avg = Number(product.average_rating ?? 0).toFixed(1);
            const reviewCount = Number(product.review_count ?? 0);
            const reviewLabel = `${reviewCount} review${reviewCount === 1 ? '' : 's'}`;

            return `
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <span class="fw-semibold">${i + 1}. ${product.name}</span><br>
                  <small class="text-muted">Avg rating: ${avg} / 5 (${reviewLabel})</small>
                </div>
                <span class="badge bg-primary">${totalSold} sold</span>
              </div>
            `;
          }).join('');
        });

      fetch('admin/get_order_status_distribution.php')
        .then(res => res.json())
        .then(data => {
          document.getElementById('orderStatusChart').innerHTML = `
            <div class="row text-center">
              <div class="col-3">
                <div class="p-3">
                  <h3 class="text-warning mb-1">${data.pending || 0}</h3>
                  <small class="text-muted">Pending</small>
                </div>
              </div>
              <div class="col-3">
                <div class="p-3">
                  <h3 class="text-info mb-1">${data.delivering || 0}</h3>
                  <small class="text-muted">Delivering</small>
                </div>
              </div>
              <div class="col-3">
                <div class="p-3">
                  <h3 class="text-success mb-1">${data.delivered || 0}</h3>
                  <small class="text-muted">Delivered</small>
                </div>
              </div>
              <div class="col-3">
                <div class="p-3">
                  <h3 class="text-secondary mb-1">${data.cancelled || 0}</h3>
                  <small class="text-muted">Cancelled</small>
                </div>
              </div>
            </div>
          `;
        });
    }

    // Driver Management Functions
    function toggleDriverStatus(driverId, currentStatus) {
      const action = currentStatus ? 'deactivate' : 'activate';
      Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} this driver?`,
        showCancelButton: true,
        confirmButtonText: `Yes, ${action}`
      }).then(res => {
        if (res.isConfirmed) {
          fetch('admin/toggle_driver_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `driver_id=${driverId}&action=${action}`
          }).then(r => r.json()).then(d => {
            if (d.status === 'success') {
              Swal.fire('Updated!', '', 'success');
              loadDrivers();
            } else {
              Swal.fire('Error', d.message || 'Failed to update driver status', 'error');
            }
          });
        }
      });
    }

    // View Driver Profile
    function viewDriverProfile(driverId) {
      // Open a new tab with the driver's profile page
      window.open(`admin/view_driver_profile.php?id=${driverId}`, '_blank');
    }

    function viewUserDetails(userId) {
      fetch(`admin/get_user_details.php?id=${userId}`)
        .then(res => res.json())
        .then(data => {
          Swal.fire({
            title: 'User Details',
            html: `
              <div class="text-start">
                <p><strong>Name:</strong> ${data.firstname} ${data.lastname}</p>
                <p><strong>Email:</strong> ${data.email}</p>
                <p><strong>Phone:</strong> ${data.phonenumber || 'N/A'}</p>
                <p><strong>Address:</strong> ${data.address || 'N/A'}</p>
                <p><strong>Barangay:</strong> ${data.barangay || 'N/A'}</p>
                <p><strong>City:</strong> ${data.city || 'N/A'}</p>
                <p><strong>Join Date:</strong> ${new Date(data.created_at).toLocaleDateString()}</p>
              </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Close'
          });
        });
    }

    // Load Admin Keys
    function loadAdminKeys() {
      fetch('admin/fetch_admin_keys.php')
        .then(res => res.json())
        .then(data => {
          const table = document.getElementById('adminKeyTable');
          table.innerHTML = '';
          data.forEach((key, i) => {
            const statusBadge = key.used ? 'bg-secondary' : 'bg-success';
            const statusText = key.used ? 'Used' : 'Available';
            const actions = key.used ?
              '<span class="text-muted">Key Used</span>' :
              `<button class="btn btn-sm btn-outline-danger" onclick="deleteAdminKey(${key.id})" title="Delete Admin Key">
                <i class="bi bi-trash"></i> Delete
              </button>`;

            table.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td><code class="bg-light p-2 rounded">${key.admin_key}</code></td>
                <td><small class="text-muted">${new Date(key.created_at).toLocaleDateString()}</small></td>
                <td><span class="badge ${statusBadge}">${statusText}</span></td>
                <td>${actions}</td>
              </tr>`;
          });
        });
    }

    // Function to update analytics data
    function updateAnalytics() {
      const btn = document.getElementById('updateAnalyticsBtn');
      const spinner = document.getElementById('updateSpinner');

      // Show loading state
      btn.disabled = true;
      spinner.classList.remove('d-none');

      fetch('admin/update_analytics.php')
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Analytics data has been updated successfully.',
              confirmButtonText: 'OK'
            }).then(() => {
              // Reload the page to show updated data
              location.reload();
            });
          } else {
            throw new Error(data.message || 'Failed to update analytics');
          }
        })
        .catch(error => {
          console.error('Error updating analytics:', error);
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to update analytics: ' + error.message,
            confirmButtonText: 'OK'
          });
        })
        .finally(() => {
          // Reset button state
          btn.disabled = false;
          spinner.classList.add('d-none');
        });
    }

    function showGenerateKeyModal() {
      Swal.fire({
        title: 'Generate New Admin Key',
        html: `
          <div class="text-start">
            <p class="text-muted mb-3">This will create a new admin registration key that can be used to register admin accounts.</p>
          </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Generate Key',
        preConfirm: () => {
          return fetch('admin/generate_admin_key.php', {
            method: 'POST',
            body: new FormData()
          }).then(r => r.json());
        }
      }).then(r => {
        if (r.value?.status === 'success') {
          Swal.fire({
            title: 'Admin Key Generated!',
            html: `
              <div class="text-start">
                <p>Your new admin key is:</p>
                <div class="bg-light p-3 rounded mb-3">
                  <code class="fs-5">${r.value.key_code}</code>
                </div>
                <p class="text-muted small">Save this key securely. It can only be used once for admin registration.</p>
              </div>
            `,
            confirmButtonText: 'Copy Key',
            showCancelButton: true,
            cancelButtonText: 'Close'
          }).then(result => {
            if (result.isConfirmed) {
              navigator.clipboard.writeText(r.value.key_code);
              Swal.fire('Copied!', 'Admin key copied to clipboard.', 'success');
            }
          });
          loadAdminKeys();
        } else if (r.value?.message) {
          Swal.fire('Error', r.value.message, 'error');
        }
      });
    }

    // Delete Admin Key
    function deleteAdminKey(keyId) {
      Swal.fire({
        title: 'Delete this admin key?',
        text: 'This action cannot be undone. The key will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('admin/delete_admin_key.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'key_id=' + keyId
          })
            .then(r => r.json())
            .then(res => {
              if (res.status === 'success') {
                Swal.fire('Deleted!', res.message, 'success');
                loadAdminKeys();
              } else {
                Swal.fire('Error', res.message, 'error');
              }
            });
        }
      });
    }

    // Initialize
    loadProducts();
    loadOverview();
  </script>
</body>

</html>
