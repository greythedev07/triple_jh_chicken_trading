<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get total products
    $totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // Get pending orders
    $pendingOrders = $db->query("SELECT COUNT(*) FROM pending_delivery WHERE status = 'pending'")->fetchColumn();

    // Get active drivers
    $activeDrivers = $db->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1")->fetchColumn();

    // Get total users
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    echo json_encode([
        'totalProducts' => (int)$totalProducts,
        'pendingOrders' => (int)$pendingOrders,
        'activeDrivers' => (int)$activeDrivers,
        'totalUsers' => (int)$totalUsers
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
