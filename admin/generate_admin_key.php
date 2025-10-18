<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // Generate a unique admin key (8 characters, alphanumeric)
        $admin_key = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));

        // Ensure key is unique
        $checkKey = $db->prepare("SELECT id FROM admin_keys WHERE admin_key = ?");
        $checkKey->execute([$admin_key]);
        while ($checkKey->fetch()) {
            $admin_key = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            $checkKey->execute([$admin_key]);
        }

        // Insert new admin key
        $stmt = $db->prepare("
            INSERT INTO admin_keys (admin_key, used, created_at)
            VALUES (?, 0, NOW())
        ");
        $stmt->execute([$admin_key]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Admin key generated successfully',
            'key_code' => $admin_key
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
