<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = (int)($_POST['driver_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    if ($driver_id <= 0 || !in_array($action, ['activate', 'deactivate'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
        exit;
    }

    try {
        $is_active = ($action === 'activate') ? 1 : 0;
        $stmt = $db->prepare("UPDATE drivers SET is_active = ? WHERE id = ?");
        $stmt->execute([$is_active, $driver_id]);

        echo json_encode(['status' => 'success', 'message' => 'Driver status updated']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
