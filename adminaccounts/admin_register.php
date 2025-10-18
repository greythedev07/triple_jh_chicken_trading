<?php require_once('../config.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Registration</title>
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
            <h4 class="fw-semibold mb-3">Admin Registration</h4>
            <hr>
            <form id="adminRegisterForm" method="POST">
              <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
              </div>
              <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
              </div>
              <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
              </div>
              <div class="mb-3">
                <input type="password" name="confirmpassword" class="form-control" placeholder="Confirm Password" required>
              </div>
              <div class="mb-3">
                <input type="text" class="form-control" name="adminkey" id="adminkey" placeholder="Enter Admin Key" required>
            </div>
              <button type="submit" id="adminRegister" class="btn btn-dark w-100">Register</button>
            </form>
            <p class="text-center mt-3">
              Already have an account? <a href="admin_login.php">Login</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

<script>
$(function(){
  $('#adminRegister').click(function(e){
    e.preventDefault();

    if(!document.getElementById('adminRegisterForm').checkValidity()) return;

    $.ajax({
      type: 'POST',
      url: 'admin_register_process.php',
      data: $('#adminRegisterForm').serialize(),
      success: function(response){
        // Trim whitespace just in case
        response = response.trim();

        if (
          response.includes('Invalid') || 
          response.includes('match') || 
          response.includes('exists') || 
          response.includes('error') ||
          response.includes('Database Error')
        ) {
          // ❌ Error or warning case
          Swal.fire({
            title: 'Error',
            text: response,
            icon: 'error'
          });
        } else if (response.includes('successfully')) {
          // ✅ Success case
          Swal.fire({
            title: 'Success',
            text: response,
            icon: 'success'
          }).then(() => {
            window.location.href = "admin_login.php";
          });
        } else {
          // 🟡 Fallback unknown message
          Swal.fire({
            title: 'Notice',
            text: response,
            icon: 'info'
          });
        }
      },
      error: function(){
        Swal.fire('Error', 'Registration failed.', 'error');
      }
    });
  });
});
</script>

</body>
</html>
