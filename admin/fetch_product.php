<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['admin_id'])) {
    die(json_encode([]));
}

try {
    $stmt = $db->query("
        SELECT id, name, description, price, stock, image 
        FROM products 
        ORDER BY name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    echo json_encode([]);
}