<?php
require_once('../config.php');

header('Content-Type: application/json'); // tell browser to expect JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstname       = trim($_POST['firstname'] ?? '');
    $lastname        = trim($_POST['lastname'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phonenumber     = trim($_POST['phonenumber'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmpassword = $_POST['confirmpassword'] ?? '';
    $address         = trim($_POST['address'] ?? '');
    $barangay        = trim($_POST['barangay'] ?? '');
    $city            = trim($_POST['city'] ?? '');
    $zipcode         = trim($_POST['zipcode'] ?? '');
    $landmark        = trim($_POST['landmark'] ?? '');

    if ($password !== $confirmpassword) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
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

    try {
        // Step 1: Check if email already exists
        $checkSql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$email]);
        $emailExists = $checkStmt->fetchColumn();

        if ($emailExists > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email already registered.']);
            exit;
        }

        // Step 2: Hash password and insert new user
        $sql = "INSERT INTO users
                (firstname, lastname, email, phonenumber, password, address, barangay, city, zipcode, landmark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtinsert = $db->prepare($sql);
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $result = $stmtinsert->execute([
            $firstname,
            $lastname,
            $email,
            $phonenumber,
            $hashed,
            $address,
            $barangay,
            $city,
            $zipcode,
            $landmark
        ]);

        if ($result) {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error encountered during registration.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data received.']);
}
