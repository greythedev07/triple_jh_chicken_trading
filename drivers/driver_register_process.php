<?php
require_once('../config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $license_no = trim($_POST['license_no'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($license_no) || empty($vehicle_type) || empty($address)) {
        echo "Please fill in all fields.";
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM drivers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "Email already registered.";
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

        echo "Registration successful";
    } catch (PDOException $e) {
        echo "Database error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "Invalid request.";
}
