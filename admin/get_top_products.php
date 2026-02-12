<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

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

    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
