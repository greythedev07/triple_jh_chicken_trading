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
            COALESCE(SUM(od.quantity), 0) as total_sold,
            COALESCE(
                (SELECT AVG(rating)
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
            LEFT JOIN order_details od ON p.id = od.product_id
            LEFT JOIN orders o ON od.order_id = o.id
        WHERE
            o.status IN ('completed', 'delivered')
        GROUP BY
            COALESCE(pp.id, p.id), COALESCE(pp.name, p.name)
        ORDER BY
            total_sold DESC,
            average_rating DESC
        LIMIT 3
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
