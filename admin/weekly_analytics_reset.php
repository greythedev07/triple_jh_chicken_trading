<?php
require_once('../config.php');

/**
 * Updates the weekly analytics with data from completed deliveries
 *
 * @param PDO $db Database connection
 * @return bool True on success, false on failure
 */
function updateWeeklyAnalytics($db) {
    $db->beginTransaction();

    try {
        // Get current week's start and end dates
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));

        // Check if we already have analytics for this week
        $stmt = $db->prepare("SELECT id FROM weekly_analytics WHERE week_start_date = ? AND week_end_date = ?");
        $stmt->execute([$weekStart, $weekEnd]);
        $existingAnalytics = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get this week's sales data from history_of_delivery
        $sql = "
            SELECT
                COUNT(DISTINCT hod.id) as total_orders,
                SUM(hod.total_amount) as total_sales,
                SUM(hodi.quantity) as total_items_sold
            FROM history_of_delivery hod
            JOIN history_of_delivery_items hodi ON hod.id = hodi.history_id
            WHERE hod.delivery_time BETWEEN ? AND ?
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$weekStart . ' 00:00:00', $weekEnd . ' 23:59:59']);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAnalytics) {
            // Update existing record
            $stmt = $db->prepare("
                UPDATE weekly_analytics
                SET total_sales = ?,
                    total_orders = ?,
                    total_products_sold = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['total_sales'] ?? 0,
                $data['total_orders'] ?? 0,
                $data['total_items_sold'] ?? 0,
                $existingAnalytics['id']
            ]);
        } else {
            // Insert new record
            $stmt = $db->prepare("
                INSERT INTO weekly_analytics
                (week_start_date, week_end_date, total_sales, total_orders, total_products_sold)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $weekStart,
                $weekEnd,
                $data['total_sales'] ?? 0,
                $data['total_orders'] ?? 0,
                $data['total_items_sold'] ?? 0
            ]);
        }

        $db->commit();
        return true;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating weekly analytics: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Resets the weekly analytics data
 *
 * @param PDO $db Database connection
 * @return bool True on success, false on failure
 */
function resetSalesData($db) {
    try {
        // Truncate the weekly_analytics table
        $db->exec("TRUNCATE TABLE weekly_analytics");

        // Log the reset
        error_log("Weekly analytics data has been reset");

        // Update with current data
        return updateWeeklyAnalytics($db);

    } catch (Exception $e) {
        error_log("Error during weekly reset: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    // This script is being run from the command line (e.g., via cron)
    try {
        // Reset data
        if (resetSalesData($db)) {
            echo "Weekly analytics reset completed successfully.\n";
        } else {
            echo "Error occurred during reset. Check error log for details.\n";
            exit(1);
        }

    } catch (Exception $e) {
        error_log("Error in weekly analytics reset: " . $e->getMessage());
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    // This script is being accessed via web
    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied. This script can only be run from the command line.';
    exit;
}
