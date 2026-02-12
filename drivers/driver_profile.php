<?php
session_start();
require_once('../config.php');

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    header('Location: driver_login.php');
    exit;
}

$driver_id = $_SESSION['driver_id'];
$error = '';
$success = '';

try {
    // Check if profile_picture column exists
    $stmt = $db->query("SHOW COLUMNS FROM drivers LIKE 'profile_picture'");
    $profile_pic_column_exists = $stmt->rowCount() > 0;

    // Get driver data
    $query = "SELECT * FROM drivers WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$driver_id]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        throw new Exception('Driver not found');
    }

    // Set default profile picture if needed
    if ($profile_pic_column_exists && empty($driver['profile_picture'])) {
        $driver['profile_picture'] = 'img/profile_pic/default.png';
    } elseif (!$profile_pic_column_exists) {
        $driver['profile_picture'] = 'img/profile_pic/default.png';
    }

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get and sanitize form data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $vehicle_type = trim($_POST['vehicle_type'] ?? '');
        $license_no = trim($_POST['license_no'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $current_password = trim($_POST['current_password'] ?? '');

        // Validate required fields
        if (empty($name) || empty($email) || empty($phone)) {
            throw new Exception('Please fill in all required fields.');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // Handle password change if requested
        if (!empty($password)) {
            if (empty($current_password)) {
                throw new Exception('Please enter your current password to change it.');
            }

            // Verify current password
            if (!password_verify($current_password, $driver['password'])) {
                throw new Exception('Current password is incorrect.');
            }

            // Validate new password
            if (strlen($password) < 8) {
                throw new Exception('New password must be at least 8 characters long.');
            }
        }

        // Handle profile picture upload
        $profile_picture = $driver['profile_picture'];
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception('Invalid file type. Please upload a JPG, JPEG, PNG, or GIF image.');
            }

            // Use absolute path for upload directory
            $upload_dir = __DIR__ . '/../img/profile_pic/';
            if (!file_exists($upload_dir)) {
                if (!@mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
                    throw new Exception('Failed to create upload directory. Please check permissions.');
                }
                @chmod($upload_dir, 0777);
            }

            $filename = 'driver_' . $driver_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                $error = error_get_last();
                throw new Exception('Failed to upload profile picture: ' . ($error['message'] ?? 'Unknown error'));
            }

            // Set proper permissions on the uploaded file
            @chmod($target_path, 0644);

            // Delete old profile picture if it's not the default one
            $old_file_path = __DIR__ . '/../' . ltrim($profile_picture, '/');
            if ($profile_picture && $profile_picture !== 'img/profile_pic/default.png' && file_exists($old_file_path)) {
                @unlink('../' . $profile_picture);
            }

            $profile_picture = 'img/profile_pic/' . $filename;
        }

        // Start transaction
        $db->beginTransaction();

        try {
            // Build the update query
            $update_fields = [
                'name = :name',
                'email = :email',
                'phone = :phone',
                'vehicle_type = :vehicle_type',
                'license_no = :license_no',
                'address = :address',
                'profile_picture = :profile_picture'
            ];

            $params = [
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':vehicle_type' => $vehicle_type,
                ':license_no' => $license_no,
                ':address' => $address,
                ':profile_picture' => $profile_picture,
                ':id' => $driver_id
            ];

            // Add password to update if changing
            if (!empty($password)) {
                $update_fields[] = 'password = :password';
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            // Build and execute the query
            $sql = 'UPDATE drivers SET ' . implode(', ', $update_fields) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);

            if (!$stmt->execute($params)) {
                throw new Exception('Failed to update profile in database.');
            }

            // Update session data
            $_SESSION['driver_name'] = $name;
            $_SESSION['driver_email'] = $email;

            // Refresh driver data
            $stmt = $db->prepare("SELECT * FROM drivers WHERE id = ?");
            $stmt->execute([$driver_id]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            $db->commit();
            $success = 'Profile updated successfully!';

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log('Driver Profile Error: ' . $error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Driver Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sunset-gradient-start: #ffb347;
            --sunset-gradient-end: #ff6b26;
            --rich-amber: #f18f01;
            --buttered-sand: #ffe4c1;
            --deep-chestnut: #7a3a12;
            --spark-gold: #f9a219;
            --cream-panel: #fff5e2;
            --accent-light: #fff7e3;
        }

        body {
            background-color: var(--buttered-sand);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .topbar {
            background: linear-gradient(135deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
        }

        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        .sidebar {
            width: 250px;
            background: var(--cream-panel);
            padding: 1.5rem;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .content {
            flex: 1;
            padding: 2rem;
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--cream-panel);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .profile-header h2 {
            color: var(--deep-chestnut);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-picture {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid var(--buttered-sand);
            margin: 0 auto 1.5rem;
            display: block;
            transition: all 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: var(--deep-chestnut);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--rich-amber);
            box-shadow: 0 0 0 0.25rem rgba(241, 143, 1, 0.25);
        }

        .btn {
            padding: 0.65rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--rich-amber);
            border-color: var(--rich-amber);
        }

        .btn-primary:hover {
            background-color: var(--spark-gold);
            border-color: var(--spark-gold);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(241, 143, 1, 0.3);
        }

        .btn-outline-light {
            color: white;
            border-color: white;
        }

        .btn-outline-light:hover {
            background-color: white;
            color: var(--spark-gold);
        }

        .btn-outline-secondary {
            border-color: var(--deep-chestnut);
            color: var(--deep-chestnut);
        }

        .btn-outline-secondary:hover {
            border-color: var(--deep-chestnut);
            background-color: var(--deep-chestnut);
            color: var(--cream-panel);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #0f5132;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="logo">Triple JH — Driver Panel</div>
        <div>
            <a href="../driver_dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="content">
        <div class="profile-container">
            <div class="profile-header">
                <h2>My Profile</h2>
                <p class="text-muted">Update your personal information and profile picture</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="profileForm">
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <img src="../<?php echo htmlspecialchars($driver['profile_picture'] ?? 'img/profile_pic/default.png'); ?>"
                             alt="Profile Picture"
                             class="profile-picture mb-3"
                             onerror="this.src='../img/profile_pic/default.png'">
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Change Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            <div class="form-text">Max size: 2MB. Allowed: JPG, PNG, GIF</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo htmlspecialchars($driver['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($driver['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($driver['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="vehicle_type" class="form-label">Vehicle Type</label>
                                <select class="form-select" id="vehicle_type" name="vehicle_type">
                                    <option value="">Select Vehicle Type</option>
                                    <option value="Motorcycle" <?php echo ($driver['vehicle_type'] ?? '') === 'Motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
                                    <option value="Bicycle" <?php echo ($driver['vehicle_type'] ?? '') === 'Bicycle' ? 'selected' : ''; ?>>Bicycle</option>
                                    <option value="Car" <?php echo ($driver['vehicle_type'] ?? '') === 'Car' ? 'selected' : ''; ?>>Car</option>
                                    <option value="Tricycle" <?php echo ($driver['vehicle_type'] ?? '') === 'Tricycle' ? 'selected' : ''; ?>>Tricycle</option>
                                    <option value="Van" <?php echo ($driver['vehicle_type'] ?? '') === 'Van' ? 'selected' : ''; ?>>Van</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="license_no" class="form-label">License Number</label>
                                <input type="text" class="form-control" id="license_no" name="license_no"
                                       value="<?php echo htmlspecialchars($driver['license_no'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php
                                    echo htmlspecialchars($driver['address'] ?? '');
                                ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <h5 class="mt-4 mb-3">Change Password</h5>
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Leave blank to keep current password">
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                                <a href="../driver_dashboard.php" class="btn btn-outline-secondary ms-2">Back to Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Preview image before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-picture').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Toggle current password visibility when password field is filled
        document.getElementById('password').addEventListener('input', function() {
            const currentPassField = document.getElementById('current_password');
            if (this.value.trim() !== '') {
                currentPassField.required = true;
            } else {
                currentPassField.required = false;
                currentPassField.value = '';
            }
        });

        // Form submission with validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value.trim();
            const currentPassword = document.getElementById('current_password');
            let isValid = true;

            // Only validate password fields if a new password is being set
            if (password !== '') {
                if (currentPassword.value.trim() === '') {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter your current password to change it.',
                        confirmButtonColor: '#f18f01'
                    });
                    currentPassword.focus();
                    isValid = false;
                } else if (password.length < 8) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'New password must be at least 8 characters long.',
                        confirmButtonColor: '#f18f01'
                    });
                    document.getElementById('password').focus();
                    isValid = false;
                }
            }

            // If form is valid, show loading state
            if (isValid) {
                const submitBtn = document.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            }
        });

        // Show success message if there's a success flash from form submission
        <?php if (isset($success) && !empty($success) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?= addslashes($success) ?>',
                confirmButtonColor: '#f18f01'
            });
        });
        <?php endif; ?>

        // Show error message if there's an error from form submission
        <?php if (isset($error) && !empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= addslashes($error) ?>',
                confirmButtonColor: '#f18f01'
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
