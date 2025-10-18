<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key_id = (int)($_POST['key_id'] ?? 0);

    if ($key_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid key ID']);
        exit;
    }

    try {
        // Check if key exists and is not used
        $checkKey = $db->prepare("SELECT id, used FROM admin_keys WHERE id = ?");
        $checkKey->execute([$key_id]);
        $key = $checkKey->fetch(PDO::FETCH_ASSOC);

        if (!$key) {
            echo json_encode(['status' => 'error', 'message' => 'Admin key not found']);
            exit;
        }

        if ($key['used']) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete used admin key']);
            exit;
        }

        // Delete admin key
        $stmt = $db->prepare("DELETE FROM admin_keys WHERE id = ?");
        $stmt->execute([$key_id]);

        echo json_encode(['status' => 'success', 'message' => 'Admin key deleted successfully']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
