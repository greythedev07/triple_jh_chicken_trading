<?php
require_once('../config.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  try {
    // Step 1: Find admin by username
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Step 2: Verify password and create session
    if ($admin && password_verify($password, $admin['password'])) {
      $_SESSION['admin_id'] = $admin['id'];
      $_SESSION['admin_username'] = $admin['username'];
      echo "success";
    } else {
      echo "Invalid username or password.";
    }
  } catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
  }
} else {
  echo "No data received.";
}
