<?php
session_start();
require_once('config.php');
require_once('includes/order_helper.php');

if (!isset($_SESSION['driver_id'])) {
    header('Location: drivers/driver_login.php');
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Pagination settings
$items_per_page = 10;
$current_page_pickup = isset($_GET['pickup_page']) ? max(1, (int)$_GET['pickup_page']) : 1;
$current_page_delivering = isset($_GET['delivering_page']) ? max(1, (int)$_GET['delivering_page']) : 1;
$current_page_history = isset($_GET['history_page']) ? max(1, (int)$_GET['history_page']) : 1;

$offset_pickup = ($current_page_pickup - 1) * $items_per_page;
$offset_delivering = ($current_page_delivering - 1) * $items_per_page;
$offset_history = ($current_page_history - 1) * $items_per_page;

// Fetch driver info
$stmt = $db->prepare("SELECT name, vehicle_type, phone FROM drivers WHERE id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

// 🟢 Pending pickups = orders assigned to this driver that have NOT been picked up yet
// Keep this logic consistent with the "Pending Pickups" table below.
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM pending_delivery pd
    WHERE pd.driver_id = ?
      AND pd.status IN ('assigned', 'pending')
      AND pd.id NOT IN (SELECT pending_delivery_id FROM to_be_delivered)
");
$stmt->execute([$driver_id]);
$pending_pickups = (int)$stmt->fetchColumn();

$ongoing_deliveries = $db->query("
    SELECT COUNT(*) FROM to_be_delivered tbd
    LEFT JOIN pending_delivery pd ON pd.id = tbd.pending_delivery_id
    WHERE tbd.driver_id = $driver_id
    AND tbd.status != 'delivered'
    AND (pd.status IS NULL OR pd.status NOT IN ('cancelled', 'canceled'))
    AND NOT EXISTS (
        SELECT 1 FROM history_of_delivery hod
        WHERE hod.to_be_delivered_id = tbd.id
    )
")->fetchColumn();

$completed_deliveries = $db->query("
    SELECT COUNT(*) FROM history_of_delivery
    WHERE driver_id = $driver_id
")->fetchColumn();

// 🟢 Fetch pickup list (exclude cancelled, picked up, and delivered orders) with pagination
$stmt = $db->prepare("
    SELECT pd.*, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, u.phonenumber AS customer_phone
    FROM pending_delivery pd
    JOIN users u ON pd.user_id = u.id
    WHERE pd.driver_id = ?
    AND pd.status IN ('assigned', 'pending')  -- Include both 'assigned' and 'pending' statuses
    AND pd.id NOT IN (SELECT pending_delivery_id FROM to_be_delivered)  -- Exclude already picked up orders
    ORDER BY pd.id DESC
    LIMIT $items_per_page OFFSET $offset_pickup
");
$stmt->execute([$driver_id]);
$pickups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pickup pagination
$total_pickups = $db->prepare("
    SELECT COUNT(*) FROM pending_delivery pd
    WHERE pd.driver_id = ?
    AND pd.status IN ('assigned', 'pending')  -- Include both 'assigned' and 'pending' statuses
    AND pd.id NOT IN (SELECT pending_delivery_id FROM to_be_delivered)  -- Exclude already picked up orders
");
$total_pickups->execute([$driver_id]);
$total_pickups_count = $total_pickups->fetchColumn();
$total_pickup_pages = ceil($total_pickups_count / $items_per_page);

// Fetch ongoing deliveries (exclude completed ones) with pagination
// Exclude deliveries that are already in history and cancelled orders
$stmt = $db->prepare("
    SELECT tbd.*, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, u.phonenumber AS customer_phone,
           pd.total_amount, pd.order_number, pd.payment_method
    FROM to_be_delivered tbd
    JOIN users u ON tbd.user_id = u.id
    LEFT JOIN pending_delivery pd ON pd.id = tbd.pending_delivery_id
    WHERE tbd.driver_id = ?
    AND tbd.status != 'delivered'
    AND (pd.status IS NULL OR pd.status NOT IN ('cancelled', 'canceled'))
    AND NOT EXISTS (
        SELECT 1 FROM history_of_delivery hod
        WHERE hod.to_be_delivered_id = tbd.id
    )
    ORDER BY tbd.id DESC
    LIMIT $items_per_page OFFSET $offset_delivering
");
$stmt->execute([$driver_id]);
$ongoing = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for delivering pagination
$total_delivering = $db->prepare("
    SELECT COUNT(*) FROM to_be_delivered tbd
    WHERE tbd.driver_id = ?
    AND tbd.status != 'delivered'
    AND NOT EXISTS (
        SELECT 1 FROM history_of_delivery hod
        WHERE hod.to_be_delivered_id = tbd.id
    )
");
$total_delivering->execute([$driver_id]);
$total_delivering_count = $total_delivering->fetchColumn();
$total_delivering_pages = ceil($total_delivering_count / $items_per_page);

// Fetch delivery history with pagination
$stmt = $db->prepare("
    SELECT hod.*, tbd.pending_delivery_id, CONCAT(u.firstname, ' ', u.lastname) AS customer_name, pd.order_number
    FROM history_of_delivery hod
    JOIN users u ON hod.user_id = u.id
    LEFT JOIN to_be_delivered tbd ON tbd.id = hod.to_be_delivered_id
    LEFT JOIN pending_delivery pd ON pd.id = tbd.pending_delivery_id
    WHERE hod.driver_id = ?
    ORDER BY hod.id DESC
    LIMIT $items_per_page OFFSET $offset_history
");
$stmt->execute([$driver_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for history pagination
$total_history = $db->prepare("
    SELECT COUNT(*) FROM history_of_delivery hod
    WHERE hod.driver_id = ?
");
$total_history->execute([$driver_id]);
$total_history_count = $total_history->fetchColumn();
$total_history_pages = ceil($total_history_count / $items_per_page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --accent-dark: #6d3209;
        }

        body {
            background: linear-gradient(180deg, var(--buttered-sand), #ffd58b);
            font-family: "Inter", "Segoe UI", sans-serif;
            color: var(--accent-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .btn-outline-light {
            color: white;
            border-color: white;
        }

        .btn-outline-light:hover {
            background-color: white;
            color: var(--spark-gold);
        }

        .dashboard-container {
            display: flex;
            flex-grow: 1;
            gap: 20px;
            padding: 30px;
        }

        .sidebar {
            background: var(--cream-panel);
            border: 1px solid rgba(241, 143, 1, 0.35);
            border-radius: 16px;
            width: 250px;
            height: fit-content;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
        }

        .sidebar h5 {
            font-weight: 700;
            margin-bottom: 15px;
        }

        .sidebar a {
            display: block;
            color: rgba(109, 50, 9, 0.85);
            text-decoration: none;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.7);
            transition: 0.2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            font-weight: 600;
            color: var(--rich-amber);
        }

        .content {
            flex-grow: 1;
        }

        section {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        section.active {
            display: block;
            opacity: 1;
        }

        .card {
            border: 1px solid rgba(241, 143, 1, 0.35);
            border-radius: 16px;
            background: var(--cream-panel);
            padding: 25px;
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.16);
        }

        .stats-card {
            text-align: center;
            transition: 0.2s;
            cursor: pointer;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
        }

        .stats-card .display-6 {
            color: var(--rich-amber);
            font-weight: 700;
        }

        footer {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: var(--accent-light);
            padding: 2rem 0;
            text-align: center;
            margin-top: auto;
        }

        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        .pagination .page-link {
            color: var(--accent-dark);
            border-color: rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }

        .pagination .page-link:hover {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: var(--rich-amber);
        }

        .pagination .page-item.active .page-link {
            background-color: #000;
            border-color: #000;
        }

        .valid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #198754;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .form-control.is-valid {
            border-color: #198754;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 1.44-1.44'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4 1.4-1.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* Modal theming */
        .modal-content {
            border-radius: 18px;
            border: 1px solid rgba(241, 143, 1, 0.3);
            background: var(--cream-panel);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            border-bottom: none;
        }

        .modal-header .modal-title {
            color: var(--accent-dark);
            font-weight: 700;
        }

        .modal-footer {
            border-top: none;
        }

        .btn-secondary {
            border-radius: 999px;
        }

        .modal .btn.btn-dark {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            border: none;
            border-radius: 999px;
            font-weight: 600;
            color: var(--accent-dark);
            box-shadow: 0 10px 26px rgba(241, 143, 1, 0.45);
        }

        .modal .btn.btn-dark:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 34px rgba(241, 143, 1, 0.55);
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="logo">Triple JH — Driver Panel</div>
        <div class="d-flex align-items-center gap-2">
            <a href="drivers/driver_profile.php" class="btn btn-outline-light">
                <i class="fas fa-user-edit me-2"></i> Your Profile
            </a>
            <a href="logout.php" class="btn btn-outline-light" onclick="return confirm('Are you sure you want to log out?')">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="sidebar">
            <h5>Navigation</h5>
            <a href="#overview" class="active" onclick="showSection('overview')">Overview</a>
            <a href="#pickup" onclick="showSection('pickup')">Pickups</a>
            <a href="#delivering" onclick="showSection('delivering')">Delivering</a>
            <a href="#history" onclick="showSection('history')">Delivery History</a>
        </div>

        <div class="content">
            <section id="overview" class="active">
                <h3 class="fw-bold mb-4">Dashboard Overview</h3>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card stats-card" onclick="showSection('pickup')">
                            <h5>Pending Pickups</h5>
                            <p class="display-6"><?= $pending_pickups ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card" onclick="showSection('delivering')">
                            <h5>Ongoing Deliveries</h5>
                            <p class="display-6"><?= $ongoing_deliveries ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stats-card" onclick="showSection('history')">
                            <h5>Completed Deliveries</h5>
                            <p class="display-6"><?= $completed_deliveries ?></p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="pickup">
                <h3 class="fw-bold mb-4">Pending Pickups</h3>
                <div class="card">
                    <?php if (count($pickups) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Delivery Address</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pickups as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['order_number'] ? formatOrderNumber($p['order_number']) : '#' . $p['id']) ?></td>
                                        <td><?= htmlspecialchars($p['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($p['customer_phone'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($p['delivery_address'] ?? 'N/A') ?></td>
                                        <td>₱<?= number_format((float)($p['total_amount'] ?? 0), 2) ?></td>
                                        <?php
                                        $rs = strtolower($p['status'] ?? '');
                                        $label = match($rs) {
                                            '', 'pending' => 'Pending',
                                            'assigned' => 'Assigned',
                                            'to be delivered', 'out for delivery' => 'Delivering',
                                            'delivered' => 'Delivered',
                                            'cancelled', 'canceled' => 'Cancelled',
                                            default => ucfirst($rs)
                                        };
                                        $badge = match($label) {
                                            'Pending' => 'bg-warning',
                                            'Assigned' => 'bg-info',
                                            'Delivering' => 'bg-primary',
                                            'Delivered' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($label) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-dark" data-bs-toggle="modal" data-bs-target="#pickupModal" data-order-id="<?= (int)$p['id'] ?>">Pick up</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($total_pickup_pages > 1): ?>
                            <nav aria-label="Pickup pagination">
                                <ul class="pagination">
                                    <?php if ($current_page_pickup > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?pickup_page=<?= $current_page_pickup - 1 ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $current_page_pickup - 2); $i <= min($total_pickup_pages, $current_page_pickup + 2); $i++): ?>
                                        <li class="page-item <?= $i == $current_page_pickup ? 'active' : '' ?>">
                                            <a class="page-link" href="?pickup_page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($current_page_pickup < $total_pickup_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?pickup_page=<?= $current_page_pickup + 1 ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?><p class="text-secondary mb-0">No pending pickups at the moment.</p><?php endif; ?>
                </div>
            </section>

            <section id="delivering">
                <h3 class="fw-bold mb-4">Ongoing Deliveries</h3>
                <div class="card">
                    <?php if (count($ongoing) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Delivery Address</th>
                                    <th>To Pay</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ongoing as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['order_number'] ? formatOrderNumber($d['order_number']) : '#' . $d['id']) ?></td>
                                        <td><?= htmlspecialchars($d['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($d['customer_phone'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($d['delivery_address'] ?? 'N/A') ?></td>
                                        <td>₱<?= number_format((float)($d['total_amount'] ?? 0), 2) ?></td>
                                        <?php
                                        $rs2 = strtolower($d['status'] ?? '');
                                        $label2 = $rs2 === '' ? 'Delivering' : (($rs2 === 'to be delivered' || $rs2 === 'out for delivery' || $rs2 === 'assigned' || $rs2 === 'pending' || $rs2 === 'picked_up') ? 'Delivering' : ($rs2 === 'delivered' ? 'Delivered' : ($rs2 === 'cancelled' || $rs2 === 'canceled' ? 'Cancelled' : ucfirst($rs2))));
                                        $badge2 = $label2 === 'Delivering' ? 'bg-info' : ($label2 === 'Delivered' ? 'bg-success' : ($label2 === 'Cancelled' ? 'bg-secondary' : 'bg-warning'));
                                        ?>
                                        <td><span class="badge <?= $badge2 ?>"><?= htmlspecialchars($label2) ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-dark"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#completeModal"
                                                    data-delivery-id="<?= (int)$d['id'] ?>"
                                                    data-total-amount="<?= (float)($d['total_amount'] ?? 0) ?>"
                                                    data-payment-method="<?= htmlspecialchars($d['payment_method'] ?? '') ?>">
                                                Complete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($total_delivering_pages > 1): ?>
                            <nav aria-label="Delivering pagination">
                                <ul class="pagination">
                                    <?php if ($current_page_delivering > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?delivering_page=<?= $current_page_delivering - 1 ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $current_page_delivering - 2); $i <= min($total_delivering_pages, $current_page_delivering + 2); $i++): ?>
                                        <li class="page-item <?= $i == $current_page_delivering ? 'active' : '' ?>">
                                            <a class="page-link" href="?delivering_page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($current_page_delivering < $total_delivering_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?delivering_page=<?= $current_page_delivering + 1 ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?><p class="text-secondary mb-0">No ongoing deliveries currently.</p><?php endif; ?>
                </div>
            </section>

            <section id="history">
                <h3 class="fw-bold mb-4">Delivery History</h3>
                <div class="card">
                    <?php if (count($history) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Destination</th>
                                    <th>Date Delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($h['order_number'] ? formatOrderNumber($h['order_number']) : '#' . ($h['pending_delivery_id'] ?? $h['to_be_delivered_id'])) ?></td>
                                        <td><?= htmlspecialchars($h['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($h['delivery_address'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($h['delivery_time'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($total_history_pages > 1): ?>
                            <nav aria-label="History pagination">
                                <ul class="pagination">
                                    <?php if ($current_page_history > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?history_page=<?= $current_page_history - 1 ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $current_page_history - 2); $i <= min($total_history_pages, $current_page_history + 2); $i++): ?>
                                        <li class="page-item <?= $i == $current_page_history ? 'active' : '' ?>">
                                            <a class="page-link" href="?history_page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($current_page_history < $total_history_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?history_page=<?= $current_page_history + 1 ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?><p class="text-secondary mb-0">No completed deliveries yet.</p><?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <footer>
        <div class="container">
            <small>© <?= date('Y') ?> Triple JH Chicken Trading — All rights reserved.</small>
        </div>
    </footer>

    <!-- Pickup Modal -->
    <div class="modal fade" id="pickupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="drivers/driver_pickup_process.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Pickup</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pending_delivery_id" id="pickup_pending_id">
                        <div class="mb-3">
                            <label class="form-label">Driver Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($driver['name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver ID</label>
                            <input type="text" class="form-control" value="<?= (int)$driver_id ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Driver Phone Number</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($driver['phone'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pickup Proof Photo</label>
                            <input type="file" name="pickup_proof" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-dark">Confirm Pickup</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="drivers/driver_delivery_process.php" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Complete Delivery</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="to_be_delivered_id" id="complete_delivery_id">
                        <input type="hidden" id="required_amount" value="0">

                        <div class="mb-3">
                            <div class="alert alert-info">
                                <strong>Amount to Collect:</strong> ₱<span id="display_required_amount">0.00</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Received (₱)</label>
                            <input type="number" step="0.01" min="0" name="payment_received" id="payment_received" class="form-control" required>
                            <div class="invalid-feedback" id="payment_error"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Change Given (₱)</label>
                            <input type="number" step="0.01" min="0" name="change_given" id="change_given" class="form-control" required readonly>
                            <small class="form-text text-muted">This will be calculated automatically</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Proof of Delivery Photo</label>
                            <input type="file" name="proof_image" class="form-control" accept="image/*" required>
                        </div>
                        <input type="hidden" name="delivery_time" value="<?= date('Y-m-d H:i:s') ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-dark">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showSection(id) {
            document.querySelectorAll('.content section').forEach(s => s.classList.remove('active'));
            const target = document.getElementById(id);
            if (target) target.classList.add('active');
            document.querySelectorAll('.sidebar a').forEach(a => a.classList.remove('active'));
            const activeLink = document.querySelector(`.sidebar a[href="#${id}"]`);
            if (activeLink) activeLink.classList.add('active');
        }

        // Populate pickup modal with order id
        const pickupModal = document.getElementById('pickupModal');
        if (pickupModal) {
            pickupModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button?.getAttribute('data-order-id');
                const input = document.getElementById('pickup_pending_id');
                if (input && orderId) input.value = orderId;
            });
        }

        // Populate complete modal with delivery id and total amount
        const completeModal = document.getElementById('completeModal');
        if (completeModal) {
            completeModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const deliveryId = button?.getAttribute('data-delivery-id');
                const totalAmount = parseFloat(button?.getAttribute('data-total-amount') || 0);
                const paymentMethod = button?.getAttribute('data-payment-method')?.toLowerCase() || '';

                const deliveryInput = document.getElementById('complete_delivery_id');
                const requiredAmountInput = document.getElementById('required_amount');
                const displayAmount = document.getElementById('display_required_amount');
                const paymentReceivedInput = document.getElementById('payment_received');
                const changeGivenInput = document.getElementById('change_given');

                if (deliveryInput && deliveryId) deliveryInput.value = deliveryId;
                if (requiredAmountInput) requiredAmountInput.value = totalAmount;
                if (displayAmount) displayAmount.textContent = totalAmount.toFixed(2);

                // Reset form fields
                if (paymentReceivedInput) {
                    // Auto-fill payment received if payment method is GCASH
                    if (paymentMethod === 'gcash') {
                        paymentReceivedInput.value = totalAmount.toFixed(2);
                        paymentReceivedInput.readOnly = true;
                        paymentReceivedInput.classList.add('is-valid');

                        // Trigger change event to calculate change
                        const event = new Event('input', { bubbles: true });
                        paymentReceivedInput.dispatchEvent(event);
                    } else {
                        paymentReceivedInput.value = '';
                        paymentReceivedInput.readOnly = false;
                        paymentReceivedInput.classList.remove('is-invalid', 'is-valid');
                    }
                }

                if (changeGivenInput) changeGivenInput.value = '0.00';

                // Clear any previous error messages
                const errorDiv = document.getElementById('payment_error');
                if (errorDiv) {
                    if (paymentMethod === 'gcash') {
                        errorDiv.textContent = 'GCASH payment - amount auto-filled';
                        errorDiv.className = 'valid-feedback';
                    } else {
                        errorDiv.textContent = '';
                    }
                }
            });
        }

        // Payment validation and automatic change calculation
        const paymentReceivedInput = document.getElementById('payment_received');
        const changeGivenInput = document.getElementById('change_given');

        if (paymentReceivedInput && changeGivenInput) {
            paymentReceivedInput.addEventListener('input', function() {
                const requiredAmount = parseFloat(document.getElementById('required_amount').value || 0);
                const paymentReceived = parseFloat(this.value || 0);
                const errorDiv = document.getElementById('payment_error');

                // Clear previous validation states
                this.classList.remove('is-invalid', 'is-valid');
                if (errorDiv) errorDiv.textContent = '';

                if (paymentReceived > 0) {
                    if (paymentReceived < requiredAmount) {
                        // Payment insufficient
                        this.classList.add('is-invalid');
                        if (errorDiv) errorDiv.textContent = `Payment insufficient. Need ₱${(requiredAmount - paymentReceived).toFixed(2)} more.`;
                        changeGivenInput.value = '0.00';
                    } else {
                        // Payment sufficient - calculate change
                        this.classList.add('is-valid');
                        const change = paymentReceived - requiredAmount;
                        changeGivenInput.value = change.toFixed(2);

                        if (change > 0) {
                            if (errorDiv) errorDiv.textContent = `Change to give: ₱${change.toFixed(2)}`;
                            errorDiv.className = 'valid-feedback';
                        } else {
                            if (errorDiv) errorDiv.textContent = 'Exact payment received.';
                            errorDiv.className = 'valid-feedback';
                        }
                    }
                } else {
                    changeGivenInput.value = '0.00';
                }
            });
        }

        // Prevent form submission if payment is insufficient
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const paymentReceivedInput = document.getElementById('payment_received');
                const requiredAmount = parseFloat(document.getElementById('required_amount')?.value || 0);

                if (paymentReceivedInput) {
                    const paymentReceived = parseFloat(paymentReceivedInput.value || 0);

                    if (paymentReceived < requiredAmount) {
                        e.preventDefault();

                        // Show error message
                        const errorDiv = document.getElementById('payment_error') || document.createElement('div');
                        errorDiv.textContent = `Payment insufficient. Need ₱${(requiredAmount - paymentReceived).toFixed(2)} more.`;
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.id = 'payment_error';

                        if (!paymentReceivedInput.nextElementSibling || !paymentReceivedInput.nextElementSibling.matches('.invalid-feedback')) {
                            paymentReceivedInput.insertAdjacentElement('afterend', errorDiv);
                        }

                        paymentReceivedInput.classList.add('is-invalid');
                        paymentReceivedInput.focus();

                        // Re-enable submit button after a short delay
                        const submitBtn = this.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Submit';
                        }

                        // Show error alert
                        Swal.fire({
                            icon: 'error',
                            title: 'Insufficient Payment',
                            text: `Payment received (₱${paymentReceived.toFixed(2)}) is less than the required amount (₱${requiredAmount.toFixed(2)}).`,
                            confirmButtonColor: '#f18f01'
                        });

                        return false;
                    }
                }

                // If payment is sufficient, proceed with form submission
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processing...';
                }
            });
        });
    </script>
</body>

</html>
