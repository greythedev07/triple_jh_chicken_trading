<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if we should only return active drivers (for assignment)
$activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

try {
    $sql = "
        SELECT id, driver_code, name, email, phone, vehicle_type, is_active
        FROM drivers
    ";

    // Add WHERE clause if only active drivers are requested
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($drivers);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
