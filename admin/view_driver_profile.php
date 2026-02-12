<?php
session_start();
require_once('../config.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Get driver ID from URL
$driver_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($driver_id <= 0) {
    header('Location: ../admin_dashboard.php');
    exit;
}

// Fetch driver details
try {
    // Get driver information
    $stmt = $db->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        throw new Exception("Driver not found");
    }

    // Pagination settings
    $items_per_page = 10;
    $current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($current_page - 1) * $items_per_page;

    // Get driver's ongoing and completed deliveries with pagination
    $deliveryStmt = $db->prepare(
        "(
            -- Completed deliveries from history
            SELECT
                hod.id,
                hod.order_number,
                hod.payment_method,
                'delivered' AS status,
                hod.delivery_address,
                (SELECT SUM(hdi.price * hdi.quantity) FROM history_of_delivery_items hdi WHERE hdi.history_id = hod.id) as total_amount,
                hod.delivery_time AS date,
                hod.driver_id,
                CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
                u.phonenumber as customer_phone,
                hod.to_be_delivered_id,
                tbd.pending_delivery_id,
                pd.order_number AS original_order_number
            FROM history_of_delivery hod
            JOIN users u ON hod.user_id = u.id
            LEFT JOIN to_be_delivered tbd ON tbd.id = hod.to_be_delivered_id
            LEFT JOIN pending_delivery pd ON pd.id = tbd.pending_delivery_id
            WHERE hod.driver_id = ?
        )
        UNION ALL
        (
            -- Ongoing deliveries from to_be_delivered
            SELECT
                tbd.id,
                pd.order_number,
                pd.payment_method,
                tbd.status,
                tbd.delivery_address,
                pd.total_amount,
                pd.date_requested AS date,
                tbd.driver_id,
                CONCAT(u.firstname, ' ', u.lastname) AS customer_name,
                u.phonenumber as customer_phone,
                tbd.id AS to_be_delivered_id,
                tbd.pending_delivery_id,
                pd.order_number AS original_order_number
            FROM to_be_delivered tbd
            JOIN pending_delivery pd ON pd.id = tbd.pending_delivery_id
            JOIN users u ON pd.user_id = u.id
            WHERE tbd.driver_id = ?
            AND tbd.status != 'delivered'
            AND NOT EXISTS (
                SELECT 1 FROM history_of_delivery hod
                WHERE hod.to_be_delivered_id = tbd.id
            )
        )
        ORDER BY date DESC
        LIMIT $items_per_page OFFSET $offset"
    );
    $deliveryStmt->execute([$driver_id, $driver_id]);
    $deliveries = $deliveryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count for pagination (both completed and ongoing)
    $totalStmt = $db->prepare(
        "SELECT
            (SELECT COUNT(*) FROM history_of_delivery WHERE driver_id = ?) +
            (SELECT COUNT(*) FROM to_be_delivered WHERE driver_id = ? AND status != 'delivered'
             AND NOT EXISTS (SELECT 1 FROM history_of_delivery WHERE to_be_delivered_id = to_be_delivered.id))
         AS total"
    );
    $totalStmt->execute([$driver_id, $driver_id]);
    $total_deliveries = $totalStmt->fetchColumn();
    $total_pages = ceil($total_deliveries / $items_per_page);

    // Calculate delivery stats
    $stats = [
        'total_deliveries' => 0,
        'completed_deliveries' => 0,
        'pending_deliveries' => 0,
        'cancelled_deliveries' => 0,
        'in_progress_deliveries' => 0
    ];

    // Get counts from database for accurate stats
    $statsStmt = $db->prepare("
        SELECT
            (SELECT COUNT(*) FROM history_of_delivery WHERE driver_id = ?) as completed,
            (SELECT COUNT(*) FROM to_be_delivered WHERE driver_id = ? AND status NOT IN ('cancelled', 'delivered')) as ongoing,
            (SELECT COUNT(*) FROM to_be_delivered WHERE driver_id = ? AND status = 'cancelled') as cancelled

    ");
    $statsStmt->execute([$driver_id, $driver_id, $driver_id]);
    $dbStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $stats['completed_deliveries'] = (int)$dbStats['completed'];
    $stats['in_progress_deliveries'] = (int)$dbStats['ongoing'];
    $stats['cancelled_deliveries'] = (int)$dbStats['cancelled'];
    $stats['total_deliveries'] = $stats['completed_deliveries'] + $stats['in_progress_deliveries'] + $stats['cancelled_deliveries'];

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Profile - <?php echo htmlspecialchars($driver['name']); ?> | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
        }

        body {
            background-color: var(--buttered-sand);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            background: var(--cream-panel);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: white;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-info h2 {
            margin: 0;
            font-weight: 600;
        }

        .profile-meta {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .profile-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stats-card h3 {
            margin: 0;
            font-size: 1.8rem;
            color: var(--deep-chestnut);
        }

        .stats-card p {
            margin: 0.5rem 0 0;
            color: var(--deep-chestnut);
            font-weight: 500;
        }

        .section-title {
            color: var(--deep-chestnut);
            font-weight: 600;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--buttered-sand);
        }

        .delivery-card {
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .delivery-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .delivery-id {
            font-weight: 600;
            color: var(--deep-chestnut);
        }

        .delivery-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-delivering {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .delivery-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .detail-item h5 {
            font-size: 0.8rem;
            color: #6c757d;
            margin: 0 0 0.25rem;
        }

        .detail-item p {
            margin: 0;
            font-weight: 500;
            color: var(--deep-chestnut);
        }

        .btn-back {
            background: white;
            color: var(--rich-amber);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.9);
            color: var(--deep-chestnut);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="logo">Triple JH — Admin Dashboard</div>
        <a href="../admin_dashboard.php#drivers" class="btn-back">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger m-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <img src="../<?php echo !empty($driver['profile_picture']) ? htmlspecialchars($driver['profile_picture']) : 'img/profile_pic/default.png'; ?>"
                     alt="Profile Picture" class="profile-avatar"
                     onerror="this.src='../img/profile_pic/default.png'">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($driver['name']); ?></h2>
                    <div class="profile-meta">
                        <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($driver['email']); ?></span>
                        <span><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($driver['phone']); ?></span>
                        <span><i class="bi bi-truck"></i> <?php echo !empty($driver['vehicle_type']) ? htmlspecialchars($driver['vehicle_type']) : 'N/A'; ?></span>
                        <span><i class="bi bi-card-text"></i> <?php echo !empty($driver['license_no']) ? htmlspecialchars($driver['license_no']) : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-0">
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['total_deliveries']; ?></h3>
                        <p>Total Deliveries</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['completed_deliveries']; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['in_progress_deliveries']; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['cancelled_deliveries']; ?></h3>
                        <p>Cancelled</p>
                    </div>
                </div>
            </div>

            <!-- Delivery History -->
            <div class="p-4">
                <h4 class="section-title">Delivery History</h4>
                <div class="card">
                    <?php if (!empty($deliveries)): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Destination</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($delivery['order_number'] ?: '#' . ($delivery['pending_delivery_id'] ?? $delivery['id'])) ?></td>
                                        <td><?= htmlspecialchars($delivery['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($delivery['delivery_address'] ?? 'N/A') ?></td>
                                        <td>₱<?= number_format((float)$delivery['total_amount'], 2) ?></td>
                                        <td>
                                            <?php
                                            $status = strtolower($delivery['status']);
                                            $badgeClass = 'bg-secondary';

                                            if ($status === 'delivered') {
                                                $badgeClass = 'bg-success';
                                            } elseif (in_array($status, ['assigned', 'to be delivered', 'out for delivery', 'picked_up'])) {
                                                $badgeClass = 'bg-primary';
                                                $status = 'In Progress';
                                            } elseif (in_array($status, ['pending', 'processing'])) {
                                                $badgeClass = 'bg-warning';
                                            } elseif ($status === 'cancelled' || $status === 'canceled') {
                                                $badgeClass = 'bg-danger';
                                            }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $dateField = isset($delivery['delivery_time']) ? 'delivery_time' : 'date';
                                            echo date('M d, Y h:i A', strtotime($delivery[$dateField]));
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="History pagination">
                                <ul class="pagination">
                                    <?php if ($current_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?= $driver_id ?>&page=<?= $current_page - 1 ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?id=<?= $driver_id ?>&page=<?= $i ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($current_page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?id=<?= $driver_id ?>&page=<?= $current_page + 1 ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-truck" style="font-size: 3rem; color: #dee2e6;"></i>
                            <p class="mt-3 text-muted">No delivery history found for this driver.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
