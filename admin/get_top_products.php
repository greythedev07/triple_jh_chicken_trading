<?php
session_start();
require_once('../config.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // First, check if required tables exist
    $tables = ['products', 'parent_products', 'history_of_delivery_items', 'history_of_delivery'];
    $missingTables = [];

    foreach ($tables as $table) {
        $check = $db->query("SHOW TABLES LIKE '$table'")->fetch();
        if (!$check) {
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        throw new Exception('Missing required tables: ' . implode(', ', $missingTables));
    }

    // Get the current date for reference
    $currentDate = date('Y-m-d');

    $query = "
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
        LIMIT 3";

    $stmt = $db->prepare($query);

    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . implode(' ', $db->errorInfo()));
    }

    $stmt->execute();

    if ($stmt->errorCode() != '00000') {
        $error = $stmt->errorInfo();
        throw new Exception('Query error: ' . $error[2]);
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log successful execution
    error_log('Successfully retrieved ' . count($products) . ' top products');

    echo json_encode([
        'status' => 'success',
        'data' => $products,
        'query' => $query,
        'current_date' => $currentDate
    ]);

} catch (PDOException $e) {
    error_log('PDO Error in get_top_products.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log('Error in get_top_products.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
