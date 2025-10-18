<?php
session_start();
require_once('../config.php');

if (!isset($_SESSION['driver_id'])) {
    header('Location: driver_login.php');
    exit;
}

$driver_id = $_SESSION['driver_id'];
$stmt = $db->prepare("SELECT name, email, phone, vehicle_type, license_no, address FROM drivers WHERE id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Driver Settings | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fb;
            font-family: "Inter", "Segoe UI", sans-serif;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .btn-dark {
            background-color: #111;
            border: none;
        }

        .btn-dark:hover {
            background-color: #000;
        }

        input[readonly] {
            background-color: #eee !important;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card p-4">
                    <h3 class="fw-bold mb-3 text-center">Driver Settings</h3>
                    <p class="text-secondary text-center mb-4">Update your profile information below</p>

                    <form id="settingsForm" method="POST" action="driver_settings_process.php">
                        <div class="mb-1">
                            <label class="form-label fw-bold">Driver Profile</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($driver['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($driver['email']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($driver['phone']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vehicle Type</label>
                            <input type="text" name="vehicle_type" class="form-control" value="<?= htmlspecialchars($driver['vehicle_type']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">License No.</label>
                            <input type="text" name="license_no" class="form-control" value="<?= htmlspecialchars($driver['license_no']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($driver['address']) ?></textarea>
                        </div>

                        <div class="mb-1">
                            <label class="form-label fw-bold">Change Password</label>
                        </div>

                        <div id="currentPassContainer" class="mb-3" style="display:none;">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" placeholder="Enter current password">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark btn-lg">Save Changes</button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="../driver_dashboard.php" class="text-decoration-none text-secondary">← Back to Dashboard</a>
                        </div>
                    </form>

                    <hr class="my-4">
                    <div class="text-center text-secondary small">
                        <p class="mb-0">
                            To delete or deactivate your driver account, please contact your system administrator.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        // Toggle current password visibility
        const newPass = document.getElementById('password');
        const currentPassContainer = document.getElementById('currentPassContainer');

        newPass.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                currentPassContainer.style.display = 'block';
            } else {
                currentPassContainer.style.display = 'none';
                document.getElementById('current_password').value = '';
            }
        });

        // AJAX form submission
        $('#settingsForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: 'driver_settings_process.php',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.trim() === 'Update successful') {
                        Swal.fire('Success', 'Your settings have been updated.', 'success').then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Something went wrong.', 'error');
                }
            });
        });
    </script>
</body>

</html>