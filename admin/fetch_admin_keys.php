<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT 
            id,
            admin_key,
            used,
            created_at
        FROM admin_keys
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($keys);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
