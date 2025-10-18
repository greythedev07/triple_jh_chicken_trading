<?php
require_once('../config.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if ($email === '' || $password === '') {
        echo 'Please fill in both fields.';
        exit;
    }

    try {
        // Step 1: Find user by email
        $sql = "SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo 'Invalid email or password.';
            exit;
        }

        // Step 2: Verify password and create session
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];

            // Step 3: Handle remember me cookie
            if ($remember) {
                setcookie('remember_email', $email, time() + (7 * 24 * 60 * 60), "/");
            } else {
                setcookie('remember_email', '', time() - 3600, "/");
            }

            echo 'Login successful';
        } else {
            echo 'Invalid email or password.';
        }
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    }
} else {
    echo 'No data received.';
}
