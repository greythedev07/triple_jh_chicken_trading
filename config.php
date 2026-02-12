<?php
/**
 * Triple JH Chicken Trading - Railway Configuration
 * Optimized for Railway's internal MySQL networking.
 */

// Get database credentials from Railway's standard environment variables
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: 'railway'; // Default Railway DB name
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';

try {
    // Construct DSN using Railway's specific environment variables
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

    // Initialize PDO instance
    $db = new PDO($dsn, $db_user, $db_pass);

    // Set error mode to exception to help Railway logs capture connection issues
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array for consistency
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error for Railway's deployment logs
    error_log("Database connection failed: " . $e->getMessage());

    // Return a 500 status code so the healthcheck knows the app is not ready
    header("HTTP/1.1 500 Internal Server Error");
    die("Database connection failed. Please ensure your Railway MySQL variables are set and the database is initialized.");
}

/**
 * Helper function to retrieve the DB instance
 */
function checkDatabaseConnection() {
    global $db;
    return $db;
}

// Define basic upload paths for the system
define('UPLOAD_PATH', __DIR__ . '/uploads/');
?>
