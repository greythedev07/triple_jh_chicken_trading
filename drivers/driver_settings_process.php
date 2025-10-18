<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['driver_id'])) {
    echo "Unauthorized access";
    exit;
}

$driver_id = $_SESSION['driver_id'];

// Fetch current driver data
$stmt = $db->prepare("SELECT password FROM drivers WHERE id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$driver) {
    echo "Driver not found.";
    exit;
}

// Collect form data
$name         = trim($_POST['name'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$vehicle_type = trim($_POST['vehicle_type'] ?? '');
$license_no   = trim($_POST['license_no'] ?? '');
$address      = trim($_POST['address'] ?? '');
$password     = $_POST['password'] ?? '';
$current_pass = $_POST['current_password'] ?? ''; // new hidden field we’ll add soon

// Validate input
if ($name === '' || $phone === '') {
    echo "Please fill in all required fields.";
    exit;
}

// Start update query
try {
    if ($password !== '') {
        // Require current password verification
        if (!isset($_POST['current_password']) || empty($_POST['current_password'])) {
            echo "Please enter your current password to change it.";
            exit;
        }

        if (!password_verify($current_pass, $driver['password'])) {
            echo "Current password is incorrect.";
            exit;
        }

        // Hash new password
        $new_hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("UPDATE drivers 
                              SET name = ?, phone = ?, vehicle_type = ?, license_no = ?, address = ?, password = ?
                              WHERE id = ?");
        $stmt->execute([$name, $phone, $vehicle_type, $license_no, $address, $new_hashed, $driver_id]);
    } else {
        // No password change
        $stmt = $db->prepare("UPDATE drivers 
                              SET name = ?, phone = ?, vehicle_type = ?, license_no = ?, address = ?
                              WHERE id = ?");
        $stmt->execute([$name, $phone, $vehicle_type, $license_no, $address, $driver_id]);
    }

    echo "Update successful";
} catch (Exception $e) {
    echo "Error updating: " . $e->getMessage();
}
