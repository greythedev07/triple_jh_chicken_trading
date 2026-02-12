<?php
/**
 * Triple JH Chicken Trading - Railway Configuration
 * Optimized for Railway's internal MySQL networking.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

$railwayUploads = '/app/uploads';
$projectUploads = __DIR__ . '/uploads';

if (is_dir($railwayUploads)) {
    // Prefer Railway volume for persistent uploads.
    if (!is_dir($projectUploads) && !is_link($projectUploads)) {
        @symlink($railwayUploads, $projectUploads);
    }
}

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
    $db = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Set error mode to exception to help Railway logs capture connection issues
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Set default fetch mode to associative array for consistency
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error for Railway's deployment logs
    error_log("Database connection failed: " . $e->getMessage());

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xrw = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $isAjaxOrJson = (stripos($accept, 'application/json') !== false) || (strtolower($xrw) === 'xmlhttprequest');
    $isApiLike = $isAjaxOrJson || (strpos($requestUri, '/admin/') !== false) || (strpos($requestUri, 'get_product_details.php') !== false);

    if ($isApiLike) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed. Please ensure your Railway MySQL variables are set and the database is initialized.'
        ]);
        exit;
    }

    // Return a 500 status code so the platform knows the app is not ready
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

$requiredUploadDirs = [
    __DIR__ . '/uploads/items/',
    __DIR__ . '/uploads/deliveries/',
    __DIR__ . '/uploads/pickups/',
    __DIR__ . '/uploads/qr_codes/',
    __DIR__ . '/uploads/gcash_screenshots/',
];

foreach ($requiredUploadDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
}
?>
