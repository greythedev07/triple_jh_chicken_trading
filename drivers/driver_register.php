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
            --accent-dark: #6d3209;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #fff7e3 0, #ffe4c1 40%, #fbd0a1 70%, #ffb347 100%);
            color: var(--accent-dark);
        }

        .card {
            background: var(--cream-panel);
            border-radius: 18px;
            border: 1px solid rgba(241, 143, 1, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
        }

        .card h3 {
            color: var(--accent-dark);
        }

        .form-control {
            border-radius: 10px;
            border-color: rgba(109, 50, 9, 0.25);
        }

        .form-control:focus {
            border-color: var(--rich-amber);
            box-shadow: 0 0 0 0.15rem rgba(241, 143, 1, 0.35);
        }

        .btn-dark {
            background: linear-gradient(180deg, var(--sunset-gradient-start), var(--sunset-gradient-end));
            border: none;
            border-radius: 999px;
            font-weight: 600;
            color: var(--accent-dark);
            box-shadow: 0 12px 30px rgba(241, 143, 1, 0.45);
        }

        .btn-dark:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 40px rgba(241, 143, 1, 0.55);
        }

        a.fw-semibold {
            color: var(--deep-chestnut);
        }

        a.fw-semibold:hover {
            color: var(--rich-amber);
        }
    </style>
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
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <div class="form-text">Password must be 8-16 characters long and include at least one letter, one number, and one special character (@$!%*#?&)</div>
                                <div id="passwordError" class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                <div id="confirmPasswordError" class="invalid-feedback"></div>
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
            // Add password validation pattern
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/;

            // Set pattern and title for HTML5 validation
            passwordInput.pattern = '^(?=.*[A-Za-z])(?=.*\\d)(?=.*[@$!%*#?&])[A-Za-z\\d@$!%*#?&]{8,16}$';
            passwordInput.title = 'Password must be 8-16 characters long and include at least one letter, one number, and one special character (@$!%*#?&)';

            // Validate password on input
            passwordInput.addEventListener('input', function() {
                if (this.value) {
                    if (passwordPattern.test(this.value)) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        document.getElementById('passwordError').textContent = '';
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                        document.getElementById('passwordError').textContent = 'Password must be 8-16 characters long and include at least one letter, one number, and one special character (@$!%*#?&)';
                    }
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                    document.getElementById('passwordError').textContent = '';
                }
            });

            // Validate password confirmation
            confirmPasswordInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('Passwords do not match');
                    this.classList.add('is-invalid');
                    document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    document.getElementById('confirmPasswordError').textContent = '';
                }
            });

            $('#driverRegisterForm').on('submit', function(e) {
                // Check if password meets requirements
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();

                if (!passwordPattern.test(password)) {
                    e.preventDefault();
                    Swal.fire('Error', 'Password must be 8-16 characters long and include at least one letter, one number, and one special character (@$!%*#?&)', 'error');
                    return;
                }

                if (password !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire('Error', 'Passwords do not match', 'error');
                    return;
                }
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: 'driver_register_process.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Success',
                                text: response.message || 'Driver account created successfully!',
                                icon: 'success'
                            }).then(() => {
                                window.location.href = 'driver_login.php';
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'An error occurred during registration.',
                                icon: 'error'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'Something went wrong. Please try again.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                        }
                        Swal.fire('Error', errorMessage, 'error');
                    }
                });
            });
        });
    </script>
</body>

</html>
