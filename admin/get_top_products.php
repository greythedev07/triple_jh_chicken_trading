<?php
// Start output buffering to catch any unexpected output
ob_start();

// Set error reporting and display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Set JSON content type header
header('Content-Type: application/json');

// Start session and include config
session_start();
require_once('../config.php');

// Function to send JSON response and exit
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    sendResponse(['status' => 'error', 'message' => 'Unauthorized access'], 401);
}

try {
    // Verify database connection
    if (!isset($db) || !($db instanceof PDO)) {
        throw new Exception('Database connection failed');
    }

    // Test the connection
    $db->query('SELECT 1')->fetch();

    // Check if required tables exist
    $tables = ['products', 'parent_products', 'history_of_delivery_items', 'history_of_delivery', 'product_reviews'];
    $existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $missingTables = array_diff($tables, $existingTables);

    if (!empty($missingTables)) {
        throw new Exception('Missing required tables: ' . implode(', ', $missingTables));
    }

    // Get the current date for reference
    $currentDate = date('Y-m-d H:i:s');

    // Build the query step by step to make it easier to debug
    $query = [
        'base' => "
            SELECT
                COALESCE(pp.id, p.id) as display_id,
                COALESCE(pp.name, p.name) as name,
                COALESCE(SUM(hdi.quantity), 0) AS total_sold,
                COALESCE(r.avg_rating, 0) AS average_rating,
                COALESCE(r.review_count, 0) AS review_count
            FROM products p
            LEFT JOIN parent_products pp ON p.parent_id = pp.id
            LEFT JOIN history_of_delivery_items hdi ON p.id = hdi.product_id
            LEFT JOIN history_of_delivery hod ON hdi.history_id = hod.id
            LEFT JOIN (
                SELECT
                    COALESCE(p.parent_id, pr.product_id) as display_product_id,
                    AVG(pr.rating) AS avg_rating,
                    COUNT(*) AS review_count
                FROM product_reviews pr
                LEFT JOIN products p ON pr.product_id = p.id
                GROUP BY COALESCE(p.parent_id, pr.product_id)
            ) AS r ON COALESCE(p.parent_id, p.id) = r.display_product_id
            WHERE hod.id IS NOT NULL
            GROUP BY COALESCE(pp.id, p.id), COALESCE(pp.name, p.name)
            ORDER BY total_sold DESC, average_rating DESC
            LIMIT 3"
    ];

    // Prepare and execute the query
    $stmt = $db->prepare($query['base']);

    if (!$stmt) {
        $error = $db->errorInfo();
        throw new Exception('Failed to prepare query: ' . ($error[2] ?? 'Unknown error'));
    }

    $executed = $stmt->execute();

    if (!$executed) {
        $error = $stmt->errorInfo();
        throw new Exception('Query execution failed: ' . ($error[2] ?? 'Unknown error'));
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log successful execution
    error_log('[' . date('Y-m-d H:i:s') . '] Successfully retrieved ' . count($products) . ' top products');

    // Send successful response
    sendResponse([
        'status' => 'success',
        'data' => $products,
        'meta' => [
            'count' => count($products),
            'timestamp' => $currentDate
        ]
    ]);

} catch (PDOException $e) {
    $errorInfo = $e->errorInfo ?? [];
    error_log('[' . date('Y-m-d H:i:s') . '] PDO Error in get_top_products.php: ' . $e->getMessage() . ' | ' . json_encode($errorInfo));
    sendResponse([
        'status' => 'error',
        'message' => 'Database error occurred',
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'pdo_code' => $errorInfo[1] ?? null,
        'pdo_message' => $errorInfo[2] ?? null
    ], 500);

} catch (Exception $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] Error in get_top_products.php: ' . $e->getMessage());
    sendResponse([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);

} finally {
    // Clean any output buffers and flush
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}
