<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_host = 'localhost';
$db_port = '3306';
$db_name = 'commissioned_app_database';
$db_user = 'root';
$db_pass = '';

// Function to check database connection
function checkDatabaseConnection() {
    global $db_host, $db_port, $db_name, $db_user, $db_pass;

    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $db = new PDO($dsn, $db_user, $db_pass, $options);
        return $db;

    } catch (PDOException $e) {
        $errorInfo = [
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
            'connection' => [
                'host' => $db_host,
                'port' => $db_port,
                'database' => $db_name,
                'user' => $db_user,
                'password' => $db_pass ? '***' : '(empty)'
            ],
            'pdo_available' => class_exists('PDO') ? 'Yes' : 'No',
            'pdo_drivers' => class_exists('PDO') ? PDO::getAvailableDrivers() : []
        ];

        error_log('Database connection failed: ' . print_r($errorInfo, true));

        if (php_sapi_name() === 'cli') {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }

        if (strpos($_SERVER['REQUEST_URI'], '/get_product_details.php') !== false) {
            header('Content-Type: application/json');
            die(json_encode([
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
                'debug' => $errorInfo
            ]));
        }

        die('Database connection failed. Please check the configuration.');
    }
}

// Initialize database connection
$db = checkDatabaseConnection();
