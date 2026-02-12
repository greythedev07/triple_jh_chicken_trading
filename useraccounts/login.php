<?php
require_once('../config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login to account</title>
    <link rel="icon" href="../img/logo.ico" type="image/x-icon">
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
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .auth-card-wrapper {
      max-width: 420px;
      margin: 2rem auto;
    }

    .auth-card {
      background: var(--cream-panel);
      border-radius: 18px;
      border: 1px solid rgba(241, 143, 1, 0.3);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    .auth-card h3 {
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

<body class="d-flex align-items-center" style="min-height: 100vh;">
  <div class="container auth-card-wrapper">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="card auth-card border-0 rounded-3 p-4">
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
