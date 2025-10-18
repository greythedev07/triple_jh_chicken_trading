<?php require_once('../config.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-6">
        <div class="card shadow-sm border-0 p-4">
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
