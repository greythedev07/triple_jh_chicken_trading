<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get parent products count
    $parentProducts = $db->query("SELECT COUNT(*) FROM parent_products")->fetchColumn();

    // Get variants count (all products that are not in parent_products)
    $variantProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // Calculate total products (sum of parents and variants)
    $totalProducts = $parentProducts + $variantProducts;

    // Get pending orders (treat blank and common variants as pending)
    $pendingOrders = $db->query("SELECT COUNT(*) FROM pending_delivery WHERE status IS NULL OR status = '' OR LOWER(status) IN ('pending','pending delivery')")->fetchColumn();

    // Get active drivers
    $activeDrivers = $db->query("SELECT COUNT(*) FROM drivers WHERE is_active = 1")->fetchColumn();

    // Get total users
    $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    echo json_encode([
        'totalProducts' => (int)$totalProducts,
        'parentProducts' => (int)$parentProducts,
        'variantProducts' => (int)$variantProducts,
        'pendingOrders' => (int)$pendingOrders,
        'activeDrivers' => (int)$activeDrivers,
        'totalUsers' => (int)$totalUsers
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
