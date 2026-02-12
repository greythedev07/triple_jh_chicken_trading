<?php
require_once('../config.php');
session_start();

http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'This endpoint has been removed.']);
exit;

// Only allow admins to access this endpoint
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $tableCheck = $db->query("SHOW TABLES LIKE 'weekly_analytics'");
    if ($tableCheck->rowCount() === 0) {
        throw new Exception("weekly_analytics table does not exist. Please run the database migration first.");
    }

    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));

    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id FROM weekly_analytics WHERE week_start_date = ? AND week_end_date = ? LIMIT 1");
    $stmt->execute([$weekStart, $weekEnd]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $db->prepare("
            UPDATE weekly_analytics
            SET total_sales = 0,
                total_orders = 0,
                total_products_sold = 0,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$existing['id']]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO weekly_analytics
                (week_start_date, week_end_date, total_sales, total_orders, total_products_sold, created_at, updated_at)
            VALUES
                (?, ?, 0, 0, 0, NOW(), NOW())
        ");
        $stmt->execute([$weekStart, $weekEnd]);
    }

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Weekly analytics reset successfully',
        'data' => [
            'week_start_date' => $weekStart,
            'week_end_date' => $weekEnd,
            'total_sales' => 0,
            'total_orders' => 0,
            'total_products_sold' => 0,
        ],
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error during weekly reset: ' . $e->getMessage(),
    ]);
}
