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
    body {
      background-color: #f5f6f7;
      font-family: "Inter", "Segoe UI", sans-serif;
      color: #222;
    }

    .topbar {
      background: #111;
      color: #fff;
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
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      width: 250px;
      height: fit-content;
      padding: 20px;
    }

    .sidebar a {
      display: block;
      color: #333;
      text-decoration: none;
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }

    .sidebar a.active,
    .sidebar a:hover {
      font-weight: 600;
      color: #000;
    }

    .content {
      flex-grow: 1;
    }

    .card {
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fff;
      padding: 25px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .btn-black {
      background: #111;
      color: #fff;
      border: none;
    }

    .btn-black:hover {
      background: #000;
    }

    table img {
      border-radius: 6px;
    }

    .table th {
      background-color: #f8f9fa;
      font-weight: 600;
      border-bottom: 2px solid #dee2e6;
    }

    .table td {
      vertical-align: middle;
      padding: 12px 8px;
    }

    .table tbody tr:hover {
      background-color: #f8f9fa;
    }

    .badge {
      font-size: 0.75em;
      padding: 0.5em 0.75em;
    }

    .card-header {
      background-color: #f8f9fa;
      border-bottom: 1px solid #dee2e6;
    }

    .display-6 {
      font-size: 2rem;
      font-weight: 700;
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
            <div class="card text-center">
              <div class="card-body">
                <h5 class="card-title text-primary">Total Products</h5>
                <h2 class="display-6" id="totalProducts">-</h2>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h5 class="card-title text-warning">Pending Orders</h5>
                <h2 class="display-6" id="pendingOrders">-</h2>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h5 class="card-title text-info">Active Drivers</h5>
                <h2 class="display-6" id="activeDrivers">-</h2>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card text-center">
              <div class="card-body">
                <h5 class="card-title text-success">Total Users</h5>
                <h2 class="display-6" id="totalUsers">-</h2>
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

        <div class="card mb-4">
          <form id="addProductForm" class="row g-3" enctype="multipart/form-data">
            <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Product Name" required></div>
            <div class="col-md-2"><input type="number" name="price" class="form-control" placeholder="Price (₱)" required></div>
            <div class="col-md-2"><input type="number" name="stock" class="form-control" placeholder="Stock" required></div>
            <div class="col-md-3"><input type="file" name="image" accept="image/*" class="form-control"></div>
            <div class="col-md-2 d-grid"><button type="submit" class="btn btn-black">Add</button></div>
          </form>
        </div>

        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Image</th>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="productTable"></tbody>
          </table>
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
          <strong>Instructions:</strong> Review GCash payments and verify them using the reference numbers provided by customers.
          Only verify payments after confirming the transaction with the customer.
        </div>
        <div class="card">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Reference No.</th>
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
        <p class="text-muted mb-4">Manage driver accounts and activation status. Drivers register themselves through the driver registration page.</p>


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
        <h3 class="fw-bold mb-4">Analytics & Reports</h3>

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
      </section>

      <!-- ADMIN KEY MANAGEMENT SECTION -->
      <section id="admin-management" style="display:none;">
        <h3 class="fw-bold mb-4">Admin Key Management</h3>
        <p class="text-muted mb-4">Create and manage admin registration keys. These keys are required for new admin account registration.</p>

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

  <script>
    // Show section
    function showSection(id) {
      document.querySelectorAll('section').forEach(s => s.style.display = 'none');
      document.getElementById(id).style.display = 'block';
      document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
      document.querySelector(`.sidebar a[href="#${id}"]`).classList.add('active');

      // Load data based on section
      if (id === 'deliveries') loadDeliveries();
      else if (id === 'gcash-verification') loadGCashOrders();
      else if (id === 'drivers') loadDrivers();
      else if (id === 'users') loadUsers();
      else if (id === 'analytics') loadAnalytics();
      else if (id === 'admin-management') loadAdminKeys();
      else if (id === 'overview') loadOverview();
    }

    // Load Products
    function loadProducts() {
      fetch('admin/fetch_product.php')
        .then(res => res.json())
        .then(data => {
          const table = document.getElementById('productTable');
          table.innerHTML = '';
          data.forEach((p, i) => {
            const img = p.image ? `<img src="${p.image}" width="50" height="50">` : 'No image';
            table.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td>${img}</td>
                <td>${p.name}</td>
                <td>₱${p.price}</td>
                <td>${p.stock}</td>
                <td>
                  <button class="btn btn-sm btn-outline-dark" onclick="editProduct(${p.id}, '${p.name}', ${p.price}, ${p.stock})">Edit</button>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${p.id})">Delete</button>
                </td>
              </tr>`;
          });
        });
    }

    // Add Product
    document.getElementById('addProductForm').addEventListener('submit', e => {
      e.preventDefault();
      const formData = new FormData(e.target);
      fetch('admin/add_product.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(d => {
          if (d.status === 'success') {
            Swal.fire('Added!', 'Product added successfully.', 'success');
            e.target.reset();
            loadProducts();
          } else Swal.fire('Error', d.message, 'error');
        });
    });

    // Edit/Delete Product
    function editProduct(id, name, price, stock) {
      Swal.fire({
        title: 'Edit Product',
        html: `
          <div class="text-start">
            <label class="form-label">Product Name</label>
            <input id="pname" class="form-control mb-3" placeholder="Enter product name">
            <label class="form-label">Price (₱)</label>
            <input id="pprice" class="form-control mb-3" type="number" min="0" step="0.01" placeholder="Enter price">
            <label class="form-label">Stock</label>
            <input id="pstock" class="form-control mb-3" type="number" min="0" step="1" placeholder="Enter stock">
            <label class="form-label">Product Image</label>
            <input id="pimage" class="form-control" type="file" accept="image/*">
          </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
          const nameVal = document.getElementById('pname').value.trim();
          const priceVal = document.getElementById('pprice').value;
          const stockVal = document.getElementById('pstock').value;
          const fileInput = document.getElementById('pimage');
          const fd = new FormData();
          fd.append('id', id);
          if (nameVal !== '') fd.append('name', nameVal);
          if (priceVal !== '') fd.append('price', priceVal);
          if (stockVal !== '') fd.append('stock', stockVal);
          if (fileInput.files && fileInput.files[0]) fd.append('image', fileInput.files[0]);
          return fetch('admin/edit_product.php', {
            method: 'POST',
            body: fd
          }).then(r => r.json());
        }
      }).then(r => {
        if (r.value?.status === 'success') {
          Swal.fire('Updated!', '', 'success');
          loadProducts();
        }
      });
    }

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

    // Load Deliveries
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
            const actions = isCancelled ?
              `<button class="btn btn-sm btn-outline-secondary" onclick="deleteOrder(${d.id})">Delete</button>` :
              `<button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(${d.id})">Cancel</button>`;
            table.innerHTML += `
              <tr>
                <td>${i + 1}</td>
                <td>${d.order_number ? d.order_number.replace(/^(TJH-)(\d{4})(\d{2})(\d{2})-(\d{4})$/, '$1$2-$3-$4-$5') : `
            #$ {
              d.id
            }
            `}</td>
                <td>${d.customer_name || 'N/A'}</td>
                <td>${d.delivery_address ? d.delivery_address.substring(0, 50) + '...' : 'N/A'}</td>
                <td>${pm}</td>
                <td><span class="badge ${badge}">${statusLabel}</span></td>
                <td>${driver}</td>
                <td>₱${d.total_amount || '0.00'}</td>
                <td>${actions}</td>
              </tr>`;
          });
        });
    }

    // Assign driver
    function assignDriver(pending_id) {
      fetch('admin/fetch_drivers.php')
        .then(r => r.json())
        .then(drivers => {
          const options = drivers.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
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
          if (data.orders && data.orders.length > 0) {
            data.orders.forEach((order, i) => {
              const statusBadge = order.payment_status === 'pending' ?
                '<span class="badge bg-warning">Pending</span>' :
                '<span class="badge bg-danger">Failed</span>';

              const actions = order.payment_status === 'pending' ?
                `<button class="btn btn-success btn-sm me-2" onclick="verifyGCashPayment(${order.id}, 'verify')">
                  <i class="bi bi-check-circle"></i> Verify
                </button>
                <button class="btn btn-danger btn-sm" onclick="verifyGCashPayment(${order.id}, 'reject')">
                  <i class="bi bi-x-circle"></i> Reject
                </button>` :
                `<button class="btn btn-success btn-sm" onclick="verifyGCashPayment(${order.id}, 'verify')">
                  <i class="bi bi-check-circle"></i> Verify
                </button>`;

              table.innerHTML += `
                 <tr>
                   <td>${i + 1}</td>
                   <td>${order.order_number ? order.order_number.replace(/^(TJH-)(\d{4})(\d{2})(\d{2})-(\d{4})$/, '$1$2-$3-$4-$5') : `
              #$ {
                order.id
              }
              `}</td>
                  <td>${order.firstname} ${order.lastname}</td>
                  <td>
                    <small>${order.email}<br>${order.phonenumber}</small>
                  </td>
                  <td><code>${order.gcash_reference}</code></td>
                  <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                  <td>${new Date(order.date_requested).toLocaleDateString()}</td>
                  <td>${statusBadge}</td>
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
      const actionColor = action === 'verify' ? 'success' : 'danger';

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

    // Load Overview Data
    function loadOverview() {
      // Load statistics
      fetch('admin/get_stats.php')
        .then(res => res.json())
        .then(data => {
          document.getElementById('totalProducts').textContent = data.totalProducts || 0;
          document.getElementById('pendingOrders').textContent = data.pendingOrders || 0;
          document.getElementById('activeDrivers').textContent = data.activeDrivers || 0;
          document.getElementById('totalUsers').textContent = data.totalUsers || 0;
        });

      // Load recent orders
      fetch('admin/get_recent_orders.php')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('recentOrders');
          if (data.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent orders</p>';
            return;
          }
          container.innerHTML = data.map(order => {
            // Format order number for display
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

      // Load low stock alerts
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
                <td>
                  <button class="btn btn-sm btn-outline-${d.is_active ? 'warning' : 'success'}" onclick="toggleDriverStatus(${d.id}, ${d.is_active})" title="${d.is_active ? 'Deactivate' : 'Activate'} Driver">
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

    // Load Analytics
    function loadAnalytics() {
      // Sales Summary
      fetch('admin/get_sales_summary.php')
        .then(res => res.json())
        .then(data => {
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
          `;
        });

      // Top Products
      fetch('admin/get_top_products.php')
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById('topProducts');
          if (data.length === 0) {
            container.innerHTML = '<p class="text-muted">No data available</p>';
            return;
          }
          container.innerHTML = data.map((product, i) => `
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span>${i + 1}. ${product.name}</span>
              <span class="badge bg-primary">${product.total_sold} sold</span>
            </div>
          `).join('');
        });

      // Order Status Chart
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
            }
          });
        }
      });
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

    // Generate Admin Key Modal
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