<?php
/**
 * Triple JH Chicken Trading - Railway Production Configuration
 */

// 1. Database Credentials
// We use getenv() to pull variables you referenced in your Railway Web Service
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

try {
    // 2. PDO Connection
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $db = new PDO($dsn, $db_user, $db_pass);

    // Set error mode to exception so issues show up in Railway logs
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    die("Database connection failed. Check Railway service variables.");
}

// 3. File Upload Paths
// Pointing to your Railway Volume mounted at /app/uploads
define('UPLOAD_PATH', '/app/uploads/');

/**
 * Helper function to retrieve the DB instance
 */
function checkDatabaseConnection() {
    global $db;
    return $db;
}
?>
