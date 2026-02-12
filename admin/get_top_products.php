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
        LIMIT 3
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
