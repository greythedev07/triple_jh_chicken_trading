<?php
// Start session and include config
session_start();
require_once __DIR__ . '/config.php';

// Set JSON content type
header('Content-Type: application/json');

// Ensure DB is available
if (!isset($db) || !($db instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection is not available.'
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Please log in to view product details'
    ]));
}

// Get product ID or parent ID from request
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

if ($productId <= 0 && $parentId <= 0) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Invalid product or parent ID'
    ]));
}

try {
    // Use shared PDO connection from config.php
    $pdo = $db;

    // Prepare the query based on whether we have a product ID or parent ID
    if ($productId > 0) {
        // Get specific variant by ID
        $sql = "
            SELECT
                p.*,
                pp.name,
                pp.description,
                pp.image as parent_image,
                p.weight,
                (SELECT MIN(p2.price) FROM products p2 WHERE p2.parent_id = p.parent_id) as min_price,
                (SELECT MAX(p2.price) FROM products p2 WHERE p2.parent_id = p.parent_id) as max_price,
                (SELECT COALESCE(SUM(p2.stock), 0) FROM products p2 WHERE p2.parent_id = p.parent_id) as total_stock,
                pp.name as parent_name,
                pp.description as parent_description
            FROM products p
            LEFT JOIN parent_products pp ON p.parent_id = pp.id
            WHERE p.id = ? AND p.is_active = 1
            LIMIT 1
        ";
        $params = [$productId];
    } else {
        // Get first active variant of parent product
        $sql = "
            SELECT
                p.*,
                pp.name,
                pp.description,
                pp.image as parent_image,
                p.weight,
                (SELECT MIN(p2.price) FROM products p2 WHERE p2.parent_id = ?) as min_price,
                (SELECT MAX(p2.price) FROM products p2 WHERE p2.parent_id = ?) as max_price,
                (SELECT COALESCE(SUM(p2.stock), 0) FROM products p2 WHERE p2.parent_id = ?) as total_stock,
                pp.name as parent_name,
                pp.description as parent_description
            FROM products p
            LEFT JOIN parent_products pp ON p.parent_id = pp.id
            WHERE p.parent_id = ? AND p.is_active = 1
            ORDER BY p.price ASC
            LIMIT 1
        ";
        $params = [$parentId, $parentId, $parentId, $parentId];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Get all variants for the parent product
    $variantsStmt = $pdo->prepare("
        SELECT id, name, price, stock, weight, is_active
        FROM products
        WHERE parent_id = ? AND is_active = 1
        ORDER BY price ASC
    ");
    $variantsStmt->execute([$product['parent_id'] ?? 0]);
    $variants = $variantsStmt->fetchAll();

    // Debug: Log the variants data
    error_log('Variants data: ' . print_r($variants, true));

    // Get the parent ID if this is a variant
    $parentId = $product['parent_id'] ?? $productId;

    // Check if user has purchased this product (for review purposes)
    $hasPurchased = false;
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $purchaseCheckStmt = $pdo->prepare("
            SELECT COUNT(*) > 0 as has_purchased
            FROM (
                -- Check in pending_delivery_items
                SELECT pdi.product_id
                FROM pending_delivery pd
                JOIN pending_delivery_items pdi ON pd.id = pdi.pending_delivery_id
                WHERE pd.user_id = ? AND pd.status = 'delivered'

                UNION ALL

                -- Check in history_of_delivery_items
                SELECT hdi.product_id
                FROM history_of_delivery hd
                JOIN history_of_delivery_items hdi ON hd.id = hdi.history_id
                WHERE hd.user_id = ?
            ) as user_orders
            WHERE product_id IN (SELECT id FROM products WHERE id = ? OR parent_id = ? OR id = ?)
            LIMIT 1
        ");
        $purchaseCheckStmt->execute([$userId, $userId, $productId, $parentId, $parentId]);
        $hasPurchased = (bool)$purchaseCheckStmt->fetch(PDO::FETCH_ASSOC)['has_purchased'];
    }

    // Get reviews for the parent product
    $reviewStmt = $pdo->prepare("
        SELECT pr.*,
               u.firstname,
               u.lastname,
               CONCAT(u.firstname, ' ', u.lastname) AS user_name
        FROM product_reviews pr
        LEFT JOIN users u ON pr.user_id = u.id
        WHERE pr.product_id = ?
        ORDER BY pr.created_at DESC
    ");
    $reviewStmt->execute([$parentId]);
    $reviews = $reviewStmt->fetchAll();

    // Calculate average rating
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += (float)$review['rating'];
    }
    $averageRating = count($reviews) > 0 ? $totalRating / count($reviews) : 0;

    // Get related products (only if no variants)
    $relatedProducts = [];
    if (count($variants) <= 1) {
        $relatedStmt = $pdo->prepare("
            SELECT p.*, COALESCE(p.image, pp.image) AS display_image
            FROM products p
            LEFT JOIN parent_products pp ON p.parent_id = pp.id
            WHERE p.id != ? AND p.parent_id != ? AND p.is_active = 1
            ORDER BY RAND()
            LIMIT 4

        ");
        $relatedStmt->execute([$productId, $product['parent_id'] ?? 0]);
        $relatedProducts = $relatedStmt->fetchAll();
    }

    // Get related products
    $relatedStmt = $pdo->prepare("
        SELECT p.*,
               COALESCE(p.image, pp.image) AS display_image
        FROM products p
        LEFT JOIN parent_products pp ON p.parent_id = pp.id
        WHERE p.id != ? AND p.parent_id = ? AND p.is_active = 1
        ORDER BY RAND()
        LIMIT 4
    ");
    $relatedStmt->execute([$productId, $product['parent_id'] ?? 0]);
    $relatedProducts = $relatedStmt->fetchAll();

    // Prepare the response data
    $response = [
        'status' => 'success',
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'description' => $product['description'],
            'price' => $product['price'],
            'stock' => $product['stock'],
            'image' => $product['image'] ?? null,
            'parent_id' => $product['parent_id'] ?? null,
            'parent_name' => $product['parent_name'] ?? null,
            'parent_description' => $product['parent_description'] ?? null,
            'parent_image' => $product['parent_image'] ?? null,
            'min_price' => $product['min_price'] ?? $product['price'],
            'max_price' => $product['max_price'] ?? $product['price'],
            'total_stock' => $product['total_stock'] ?? $product['stock'],
            'has_purchased' => $hasPurchased,
        ],
        'variants' => [],
        'reviews' => $reviews,
        'average_rating' => $averageRating,
        'review_count' => count($reviews),
        'related_products' => $relatedProducts,
        'has_purchased' => $hasPurchased // Also include at root level for easy access
    ];

    // If we have variants, make sure we're using the selected variant
    if (count($variants) > 0) {
        $selectedVariant = null;

        // If a specific variant was requested, find it
        if ($productId > 0) {
            foreach ($variants as $variant) {
                if ($variant['id'] == $productId) {
                    $selectedVariant = $variant;
                    break;
                }
            }
        }

        // If no specific variant was requested or it wasn't found, use the first one
        if (!$selectedVariant) {
            $selectedVariant = $variants[0];
        }

        // Update the product with the selected variant's details
        $product = array_merge($product, $selectedVariant);
        $product['id'] = $selectedVariant['id'];
        $product['name'] = $selectedVariant['name'];
        $product['price'] = $selectedVariant['price'];
        $product['stock'] = $selectedVariant['stock'];
        $product['weight'] = $selectedVariant['weight'] ?? null;

        // Ensure parent name is included
        if (!empty($product['parent_id'])) {
            $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
            $stmt->execute([$product['parent_id']]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($parent) {
                $product['parent'] = ['name' => $parent['name']];
            }
        }
    }

    // Prepare response
    $response = [
        'status' => 'success',
        'has_purchased' => $hasPurchased,
        'product' => [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'description' => $product['parent_description'] ?? $product['description'] ?? '',
            'price' => (float)$product['price'],
            'min_price' => (float)$product['min_price'],
            'max_price' => (float)$product['max_price'],
            'stock' => (int)$product['stock'],
            'total_stock' => (int)$product['total_stock'],
            'parent' => $product['parent_id'] ? [
                'id' => (int)$product['parent_id'],
                'name' => $product['parent_name'] ?? 'Unknown',
                'image' => $product['parent_image'] ?? null,
                'description' => $product['parent_description'] ?? null
            ] : null,
            'image' => (function () use ($product) {
                $raw = $product['parent_image'] ?? $product['image'] ?? '';
                $raw = (string)$raw;
                if ($raw === '') {
                    return 'img/products/placeholder.jpg';
                }
                // If DB already stored a relative path like uploads/items/xxx.jpg, return as-is.
                if (preg_match('#^uploads/#', $raw)) {
                    return $raw;
                }
                // If DB stored only filename, assume it belongs to uploads/items/
                if (strpos($raw, '/') === false && strpos($raw, '\\') === false) {
                    return 'uploads/items/' . $raw;
                }
                return $raw;
            })(),
            'variants' => array_map(function($item) use ($product) {
                return [
                    'id' => (int)$item['id'],
                    'name' => $item['name'],
                    'price' => (float)$item['price'],
                    'stock' => (int)$item['stock'],
                    'weight' => $item['weight'] ?? null
                ];
            }, $variants)
        ],
        'reviews' => $reviews,
        'average_rating' => round($averageRating, 1),
        'review_count' => count($reviews),
        'related_products' => array_map(function($item) {
            return [
                'id' => (int)$item['id'],
                'name' => $item['name'],
                'price' => (float)$item['price'],
                'image' => $item['display_image'] ?? 'img/products/placeholder.jpg'
            ];
        }, $relatedProducts)
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
