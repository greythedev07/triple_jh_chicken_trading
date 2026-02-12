<?php
require_once('../config.php');

// Only allow admins to access this endpoint
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Verify required tables exist
    $tables = ['weekly_analytics', 'history_of_delivery', 'pending_delivery'];
    foreach ($tables as $table) {
        $tableCheck = $db->query("SHOW TABLES LIKE '$table'");
        if ($tableCheck->rowCount() === 0) {
            throw new Exception("Required table '$table' is missing");
        }
    }

    // Get current week's date range (Monday to Sunday)
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));

    // Start transaction
    $db->beginTransaction();

    try {
        // Aggregate weekly totals directly from history_of_delivery (completed deliveries)
        // Compute total_sales from history_of_delivery_items to match recorded delivered items.
        $sql = "
            SELECT
                COUNT(*) AS total_orders,
                COALESCE(SUM(t.order_total), 0) AS total_sales,
                COALESCE(SUM(t.total_products_sold), 0) AS total_products_sold
            FROM (
                SELECT
                    hod.id AS history_id,
                    COALESCE(SUM(hodi.price * hodi.quantity), 0) AS order_total,
                    COALESCE(SUM(hodi.quantity), 0) AS total_products_sold
                FROM history_of_delivery hod
                LEFT JOIN history_of_delivery_items hodi
                    ON hodi.history_id = hod.id
                WHERE DATE(hod.delivery_time) BETWEEN ? AND ?
                GROUP BY hod.id
            ) t
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$weekStart, $weekEnd]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            $data = [
                'total_orders' => 0,
                'total_sales' => 0,
                'total_products_sold' => 0,
            ];
        }

        // Check if record exists for this week
        $stmt = $db->prepare("
            SELECT id FROM weekly_analytics
            WHERE week_start_date = ? AND week_end_date = ?
            LIMIT 1
        ");
        $stmt->execute([$weekStart, $weekEnd]);
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRecord) {
            // Update existing record
            $stmt = $db->prepare("
                UPDATE weekly_analytics
                SET
                    total_sales = ?,
                    total_orders = ?,
                    total_products_sold = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['total_sales'],
                $data['total_orders'],
                $data['total_products_sold'],
                $existingRecord['id']
            ]);
            $message = 'Weekly analytics updated successfully';
        } else {
            // Insert new record
            $stmt = $db->prepare("
                INSERT INTO weekly_analytics
                (week_start_date, week_end_date, total_sales, total_orders, total_products_sold, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $weekStart,
                $weekEnd,
                $data['total_sales'],
                $data['total_orders'],
                $data['total_products_sold']
            ]);
            $message = 'New weekly analytics record created';
        }

        $db->commit();

        // Get the updated record
        $stmt = $db->prepare("
            SELECT * FROM weekly_analytics
            WHERE week_start_date = ? AND week_end_date = ?
            LIMIT 1
        ");
        $stmt->execute([$weekStart, $weekEnd]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'week_start' => $result['week_start_date'],
                'week_end' => $result['week_end_date'],
                'total_sales' => (float)$result['total_sales'],
                'total_orders' => (int)$result['total_orders'],
                'total_items_sold' => (int)$result['total_products_sold']
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update analytics: ' . $e->getMessage()
    ]);
}
