<?php
// logout.php — safely log out a user or admin or admin
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie (for extra safety)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to homepage (index.php)
header("Location: index.php");
exit;
