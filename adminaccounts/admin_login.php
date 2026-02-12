<?php require_once('../config.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    a {
      color: var(--deep-chestnut);
    }

    a:hover {
      color: var(--rich-amber);
    }
  </style>
</head>
<body class="d-flex align-items-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-6">
        <div class="card border-0 p-4">
          <div class="card-body">
            <h4 class="fw-semibold mb-3">Admin Login</h4>
            <hr>
            <form id="adminLoginForm" method="POST">
              <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
              </div>
              <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
              </div>
              <button type="submit" id="adminLogin" class="btn btn-dark w-100">Login</button>
            </form>
            <p class="text-center mt-3">
              Don't have an account? <a href="admin_register.php">Register</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

<script>
$(function(){
  $('#adminLogin').click(function(e){
    e.preventDefault();

    if(!document.getElementById('adminLoginForm').checkValidity()) return;

    $.ajax({
      type: 'POST',
      url: 'admin_login_process.php',
      data: $('#adminLoginForm').serialize(),
      success: function(response){
        response = response.trim();

        if (response.includes('success')) {
          Swal.fire({
            title: 'Welcome!',
            text: 'Login successful.',
            icon: 'success'
          }).then(() => {
            window.location.href = "../admin_dashboard.php";
          });
        } else {
          Swal.fire({
            title: 'Login Failed',
            text: response,
            icon: 'error'
          });
        }
      },
      error: function(){
        Swal.fire('Error', 'Unable to login at the moment.', 'error');
      }
    });
  });
});
</script>
</body>
</html>
