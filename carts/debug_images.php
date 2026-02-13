<?php
// carts/debug_images.php - Debugging script to check image paths
session_start();
require_once('../config.php');

if (!isset($_SESSION['user_id'])) {
    die("Please log in first");
}

$user_id = $_SESSION['user_id'];

echo "<h2>Cart Items Image Paths Debug</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    .exists { color: green; font-weight: bold; }
    .missing { color: red; font-weight: bold; }
    img { max-width: 100px; max-height: 100px; }
</style>";

// Query cart items
$query = "
    SELECT
        c.id as cart_id,
        p.id as product_id,
        p.name as product_name,
        p.image as product_image,
        p.parent_id,
        pp.id as parent_product_id,
        pp.name as parent_name,
        pp.image as parent_image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN parent_products pp ON p.parent_id = pp.id
    WHERE c.user_id = ?
";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr>
    <th>Product Name</th>
    <th>Product Image (DB)</th>
    <th>Parent Image (DB)</th>
    <th>Final Path</th>
    <th>File Exists?</th>
    <th>Preview</th>
</tr>";

function getImagePath($imagePath) {
    if (empty($imagePath)) {
        return '../img/products/placeholder.jpg';
    }
    
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    if (strpos($imagePath, 'uploads/') === 0 || strpos($imagePath, 'img/') === 0) {
        return '../' . $imagePath;
    }
    
    if (strpos($imagePath, '../') === 0) {
        return $imagePath;
    }
    
    return '../' . $imagePath;
}

foreach ($items as $item) {
    $productImageDb = $item['product_image'] ?? 'NULL';
    $parentImageDb = $item['parent_image'] ?? 'NULL';
    
    // Determine final image path (same logic as cart.php)
    $finalPath = '../img/products/placeholder.jpg';
    
    if (!empty($item['product_image'])) {
        $finalPath = getImagePath($item['product_image']);
    } elseif (!empty($item['parent_image'])) {
        $finalPath = getImagePath($item['parent_image']);
    }
    
    // Check if file exists
    $fileExists = false;
    $checkPath = str_replace('../', '', $finalPath);
    
    if (file_exists('../' . $checkPath)) {
        $fileExists = true;
    } elseif (file_exists($checkPath)) {
        $fileExists = true;
    } elseif (file_exists($finalPath)) {
        $fileExists = true;
    }
    
    $statusClass = $fileExists ? 'exists' : 'missing';
    $statusText = $fileExists ? '✓ EXISTS' : '✗ MISSING';
    
    echo "<tr>";
    echo "<td><strong>" . htmlspecialchars($item['product_name']) . "</strong></td>";
    echo "<td><code>" . htmlspecialchars($productImageDb) . "</code></td>";
    echo "<td><code>" . htmlspecialchars($parentImageDb) . "</code></td>";
    echo "<td><code>" . htmlspecialchars($finalPath) . "</code></td>";
    echo "<td class='{$statusClass}'>{$statusText}</td>";
    echo "<td><img src='" . htmlspecialchars($finalPath) . "' onerror=\"this.src='../img/products/placeholder.jpg'; this.style.border='2px solid red';\" style='border: 2px solid " . ($fileExists ? 'green' : 'red') . ";'></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Quick Fixes:</h3>";
echo "<ol>";
echo "<li>Check if image files exist in the paths shown above</li>";
echo "<li>Verify database has correct image paths (should be like 'img/products/...' or 'uploads/items/...')</li>";
echo "<li>Make sure placeholder image exists at: <code>../img/products/placeholder.jpg</code></li>";
echo "<li>Check file permissions on image directories</li>";
echo "</ol>";

echo "<p><a href='cart.php'>← Back to Cart</a></p>";
?>
