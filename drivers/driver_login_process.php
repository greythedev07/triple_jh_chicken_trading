<?php
session_start();
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo "Please fill in all fields.";
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM drivers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driver && password_verify($password, $driver['password'])) {
            if (!$driver['is_active']) {
                echo "Your account is inactive. Please contact admin.";
                exit;
            }

            $_SESSION['driver_id'] = $driver['id'];
            $_SESSION['driver_code'] = $driver['driver_code'];
            $_SESSION['driver_name'] = $driver['name'];
            $_SESSION['driver_email'] = $driver['email'];

            echo "Login successful";
        } else {
            echo "Invalid email or password.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Invalid request.";
}
