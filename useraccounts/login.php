<?php
require_once('../config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login to account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-4 col-md-6">
        <div class="card shadow-sm border-0 rounded-3 p-4">
          <div class="card-body">
            <h3 class="fw-semibold mb-1">Welcome Back</h3>
            <p class="text-secondary mb-4">Login with email</p>

            <form action="login_process.php" method="post" id="loginForm">
              <div class="mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
              </div>
              <div class="mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                  </div>
                </div>
                <a href="#" class="text-secondary text-decoration-none small">Forgot Password?</a>
              </div>

              <div class="d-grid mb-3">
                <button type="submit" class="btn btn-dark btn-lg">Login</button>
              </div>

              <p class="text-center text-secondary mb-0">
                Or create an <a href="registration.php" class="text-decoration-none fw-semibold">account</a>
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
  $(function(){
    $('#loginForm').on('submit', function(e){
      e.preventDefault();
      $.ajax({
        type: 'POST',
        url: 'login_process.php',
        data: $(this).serialize(),
        success: function(data){
          if (data.trim() === 'Login successful') {
            Swal.fire('Success', 'Welcome back!', 'success').then(() => {
              window.location.href = '../dashboard.php'; // Redirect after login
            });
          } else {
            Swal.fire('Error', data, 'error');
          }
        },
        error: function(){
          Swal.fire('Error', 'Something went wrong.', 'error');
        }
      });
    });
  });
  </script>
</body>
</html>
