<?php
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $adminkey = trim($_POST['adminkey']);

    // Check if passwords match
    if ($password !== $confirmpassword) {
        echo "Passwords do not match.";
        exit;
    }

    try {
        // Step 1: Validate admin key
        $keyCheck = $db->prepare("SELECT * FROM admin_keys WHERE admin_key = ?");
        $keyCheck->execute([$adminkey]);

        if ($keyCheck->rowCount() === 0) {
            echo "Invalid Admin Key.";
            exit;
        }

        // Step 2: Check if username/email already exists
        $check = $db->prepare("SELECT * FROM admins WHERE email = ? OR username = ?");
        $check->execute([$email, $username]);

        if ($check->rowCount() > 0) {
            echo "An account with this email or username already exists.";
            exit;
        }

        // Step 3: Hash password and create admin account
        $insert = $db->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $result = $insert->execute([$username, $email, $hashed]);

        if ($result) {
            echo "Admin account created successfully!";
        } else {
            echo "Database error. Try again.";
        }
    } catch (PDOException $e) {
        echo "Database Error: " . $e->getMessage();
    }
}
