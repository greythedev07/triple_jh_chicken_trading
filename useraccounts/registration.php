<?php
require_once('../config.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card shadow-sm border-0 rounded-3 p-4">
          <div class="card-body">
            <h4 class="mb-1 fw-semibold">Create an account</h4>
            <hr>

            <form action="registration.php" method="post" id="registerForm">

              <!-- Customer Account Section -->
              <h6 class="text-secondary mb-3">Customer Account</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <input type="text" class="form-control" name="firstname" id="firstname" placeholder="First Name" required>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name" required>
                </div>
                <div class="col-12">
                  <input type="email" class="form-control" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="col-12">
                  <input type="text" class="form-control" name="phonenumber" id="phonenumber" placeholder="Mobile Number" required>
                </div>
                <div class="col-12">
                  <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                </div>
                <div class="col-12">
                  <input type="password" class="form-control" name="confirmpassword" id="confirmpassword" placeholder="Confirm Password" required>
                </div>
              </div>

              <!-- Address Section -->
              <h6 class="text-secondary mt-4 mb-3">Address</h6>
              <div class="row g-3">
                <div class="col-12">
                  <input type="text" class="form-control" name="address" id="address" placeholder="Street Address / House No." required>
                </div>
                <div class="col-md-6">
                  <select class="form-select" name="barangay" id="barangay"  required>
                    <option value="">Barangay</option>
                    <option>Sample Barangay 1</option>
                    <option>Sample Barangay 2</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <select class="form-select" name="city" id="city" required>
                    <option value="">City / Municipality</option>
                    <option>Sample City 1</option>
                    <option>Sample City 2</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="zipcode" id="zipcode" placeholder="ZIP Code" required>
                </div>
                <div class="col-md-6">
                  <input type="text" class="form-control" name="landmark" id="landmark" placeholder="Landmark">
                </div>
              </div>

              <!-- Terms and Submit -->
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">
                  I agree to the Terms and Conditions
                </label>
              </div>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-dark btn-lg" id="register">Create Account</button>
              </div>

              <p class="text-center text-secondary mb-0">
                Or login to an existing <a href="login.php" class="text-decoration-none fw-semibold">account</a>
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
  $('#register').click(function(e){
    e.preventDefault();

    var form = document.getElementById('registerForm');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    $.ajax({
      type: 'POST',
      url: 'register_process.php',
      data: $('#registerForm').serialize(),
      dataType: 'json',
      success: function(response){
        if (response.status === 'success') {
          Swal.fire('Success', response.message, 'success').then(() => {
            window.location.href = "login.php";
          });
        } else {
          Swal.fire('Error', response.message, 'error');
        }
      },
      error: function(){
        Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
      }
    });
  });
});
  </script>
</body>
</html>
