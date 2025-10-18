<?php
require_once('../config.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration | Triple JH Chicken Trading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card shadow-sm border-0 rounded-3 p-4">
                    <div class="card-body">
                        <h3 class="fw-semibold mb-1">Driver Registration</h3>
                        <p class="text-secondary mb-4">Create your driver account</p>

                        <form action="driver_register_process.php" method="post" id="driverRegisterForm">
                            <div class="mb-3">
                                <input type="text" class="form-control" name="name" placeholder="Full Name" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Password" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="phone" placeholder="Phone Number" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="license_no" placeholder="License Number" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="vehicle_type" placeholder="Vehicle Type (e.g., Motorcycle, Van)" required>
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" name="address" placeholder="Home Address" rows="2" required></textarea>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-dark btn-lg">Register</button>
                            </div>

                            <p class="text-center text-secondary mb-0">
                                Already have an account? <a href="driver_login.php" class="text-decoration-none fw-semibold">Login</a>
                            </p>
                            <br>
                            <p class="text-center text-secondary mb-0">
                                <a href="../index.php" class="text-decoration-none fw-semibold">Back to home</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            $('#driverRegisterForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: 'driver_register_process.php',
                    data: $(this).serialize(),
                    success: function(data) {
                        if (data.trim() === 'Registration successful') {
                            Swal.fire('Success', 'Driver account created successfully!', 'success').then(() => {
                                window.location.href = 'driver_login.php';
                            });
                        } else {
                            Swal.fire('Error', data, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Something went wrong.', 'error');
                    }
                });
            });
        });
    </script>
</body>

</html>