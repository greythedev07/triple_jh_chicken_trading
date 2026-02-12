<?php
require_once('../config.php');

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($phone) || empty($license_no) || empty($vehicle_type) || empty($address)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all fields.']);
        exit;
    }

    // Validate password strength
    if (strlen($password) < 8 || strlen($password) > 16) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be between 8 and 16 characters long.']);
        exit;
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[@$!%*#?&]/', $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Password must include at least one letter, one number, and one special character (@$!%*#?&).']);
        exit;
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM drivers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
            exit;
        }

        // Generate unique driver code (e.g., DRV-20251012-8394)
        $driver_code = 'DRV-' . date('Ymd') . '-' . rand(1000, 9999);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO drivers (driver_code, name, email, phone, password, vehicle_type, license_no, address, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)
        ");
        $stmt->execute([$driver_code, $name, $email, $phone, $hashedPassword, $vehicle_type, $license_no, $address]);

        echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'An error occurred during registration. Please try again.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
