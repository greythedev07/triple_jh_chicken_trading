<?php
session_start();
require_once('../config.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get products from both completed and pending deliveries, even without reviews
    $stmt = $db->prepare("
        SELECT
            COALESCE(pp.id, p.id) as display_id,
            COALESCE(pp.name, p.name) as name,
            COALESCE(SUM(CASE
                WHEN hdi.quantity IS NOT NULL THEN hdi.quantity
                WHEN pdi.quantity IS NOT NULL THEN pdi.quantity
                ELSE 0
            END), 0) as total_sold,
            COALESCE(
                (SELECT AVG(pr.rating)
                 FROM product_reviews pr
                 WHERE pr.product_id = p.id OR (p.parent_id IS NOT NULL AND pr.product_id = p.parent_id)),
                0
            ) as average_rating,
            COALESCE(
                (SELECT COUNT(*)
                 FROM product_reviews pr
                 WHERE pr.product_id = p.id OR (p.parent_id IS NOT NULL AND pr.product_id = p.parent_id)),
                0
            ) as review_count
        FROM
            products p
            LEFT JOIN parent_products pp ON p.parent_id = pp.id

            -- Get quantities from history of delivered items
            LEFT JOIN (
                SELECT hdi.product_id, hdi.quantity
                FROM history_of_delivery_items hdi
                JOIN history_of_delivery hod ON hdi.history_id = hod.id
                WHERE hod.status = 'delivered'
            ) hdi ON p.id = hdi.product_id

            -- Get quantities from pending deliveries that are completed
            LEFT JOIN (
                SELECT pdi.product_id, pdi.quantity
                FROM pending_delivery_items pdi
                JOIN pending_delivery pd ON pdi.pending_delivery_id = pd.id
                WHERE pd.status = 'completed'
            ) pdi ON p.id = pdi.product_id

        GROUP BY
            COALESCE(pp.id, p.id), COALESCE(pp.name, p.name)
        HAVING
            total_sold > 0
        ORDER BY
            total_sold DESC
        LIMIT 3
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug output
    if (empty($products)) {
        // Try a simpler query to see if we can get any products at all
        $testStmt = $db->query("SELECT id, name FROM products LIMIT 5");
        $testProducts = $testStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($testProducts)) {
            error_log("No products found in the database");
            echo json_encode([
                'error' => 'No products found',
                'debug' => [
                    'test_products_query' => $testProducts,
                    'db_name' => $db->query('SELECT DATABASE() as dbname')->fetch()['dbname']
                ]
            ]);
            exit;
        }

        // If we get here, there are products but none with sales
        echo json_encode([
            'message' => 'No products with sales found',
            'debug' => [
                'available_products' => $testProducts,
                'db_name' => $db->query('SELECT DATABASE() as dbname')->fetch()['dbname']
            ]
        ]);
        exit;
    }

    echo json_encode($products);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error in get_top_products.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in get_top_products.php: " . $e->getMessage());
    echo json_encode([
        'error' => 'An error occurred',
        'message' => $e->getMessage()
    ]);
}
