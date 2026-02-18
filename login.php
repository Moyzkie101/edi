<?php
include 'config/connection.php';

session_start();

if(isset($_SESSION['user_type'])){
   header('location: dashboard/');
}

if(isset($_POST['submit'])){
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   //$pass = $_POST['password'];
   $select = "SELECT * FROM " . $database[0] . ".user WHERE email = '$email' && password = '$pass'";
   $result = mysqli_query($conn, $select);
   // Set the time zone to Philippines time.
   // date_default_timezone_set('Asia/Manila');

   // Get the current day and time.
   $current_day_and_time = date('Y-m-d H:i:s');
   $loginquery = "UPDATE " . $database[0] . ".user SET last_online = '$current_day_and_time' WHERE email = '$email'";
   if(mysqli_num_rows($result) > 0){
      $row = mysqli_fetch_array($result);
      if($row['user_type'] == 'admin'){
         if($row['status'] == 'Inactive'){
            echo '<script>
                     window.onload = function() {
                        Swal.fire({
                           title: "End-User is Inactive",
                           text: "Please contact the system administrator.",
                           icon: "error",
                           timer: 2000,
                           allowOutsideClick: false
                        });
                     }
                  </script>';
         }else{
            $loginresult = mysqli_query($conn, $loginquery);
            $_SESSION['admin_name'] =  $row['first_name'].' '.$row['middle_name'].' '.$row['last_name'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['user_status'] = $row['status'];
            $_SESSION['user_roles'] = $row['roles'];
            echo "<script>
                  window.onload = function() {
                     const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        backdrop: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                          toast.addEventListener('mouseenter', Swal.stopTimer)
                          toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                      })
                      
                      Toast.fire({
                        icon: 'success',
                        title: 'Signed in successfully'
                      }).then(() => {
                        window.location.href = 'dashboard/';
                    });
                  }
               </script>";
         }
      }
      if($row['user_type'] == 'user'){
            if($row['status'] == 'Inactive'){
               echo '<script>
                     window.onload = function() {
                        Swal.fire({
                           title: "End-User is Inactive",
                           text: "Please contact the system administrator.",
                           icon: "error",
                           timer: 2000,
                           allowOutsideClick: false
                        });
                     }
                  </script>';
         }else{
            $loginresult = mysqli_query($conn, $loginquery);
            $_SESSION['user_name'] = $row['first_name'].' '.$row['middle_name'].' '.$row['last_name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['user_status'] = $row['status'];
            $_SESSION['user_roles'] = $row['roles'];

            // Check if the password is "Password1"
            if($pass == md5("Mlinc1234")){
               // Show a modal to prompt the user to create another password
               echo '<script>
               window.onload = function() {
                  Swal.fire({
                     title: "Change Password",
                     icon: "warning",
                     showCancelButton: true,
                     confirmButtonText: "OK",
                     cancelButtonText: "Cancel",
                     allowOutsideClick: false
                  }).then((result) => {
                     if (result.isConfirmed) {
                        var changePasswordModal = document.getElementById("changePasswordModal");
                        changePasswordModal.style.display = "block";
                     } else {
                        // Redirect to index page
                        window.location.href = "login.php";
                     }
                  });
               }
            </script>';
            } else {
               // Show a Sweetalert mixin with the success message
               echo "<script>
                  window.onload = function() {
                     const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        backdrop: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                          toast.addEventListener('mouseenter', Swal.stopTimer)
                          toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                      })
                      
                      Toast.fire({
                        icon: 'success',
                        title: 'Signed in successfully'
                      }).then(() => {
                        window.location.href = 'dashboard/';
                    });
                  }
               </script>";
            }
         }
      }
   }else{
      echo '<script>
               window.onload = function() {
                  Swal.fire({
                     title: "Incorrect Username or Password",
                     text: "Please check your username and password. Try again.",
                     icon: "error",
                     timer: 2000,
                     allowOutsideClick: false
                  });
               }
            </script>';
   }
}
   // Clear the session variables after displaying the modal
   unset($_SESSION['success_message']);
   unset($_SESSION['error_message']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>E D I </title>
   <!-- custom css file link  -->
   <link rel="stylesheet" href="assets/css/login/login-style.css?v=<?php echo time(); ?>">
   <link rel="icon" href="assets/picture/logo.png" type="image/x-icon"/>
   <script src="sweetalert2/dist/sweetalert2.all.min.js"></script>
   <link rel="stylesheet" href="sweetalert2/dist/sweetalert2.min.css">
   <script src="assets/js/login/jquery-3.7.1.js"></script>
   <!-- Include SweetAlert CSS -->

</head>
<body> 
   <!-- Modal for changing password -->
<div id="changePasswordModal" class="change-password-modal">
   <div class="change-password-modal-content">
      <center>
      <h3>Create a New Password</h3>
      <h6 style="font-style:italic; color:red;">(Press ESC to CLOSE)</h6>
      </center>
      <br>
      <form action="models/passworded/change_password.php" method="post">
         <div class="input-container">
            <input type="password" name="new_password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" required title="Password must contain at least one uppercase letter, one lowercase letter, one digit, and be at least 8 characters long.">
            <label>New Password</label>
         </div>
         <div class="input-container">
            <input type="password" name="confirm_password" required>
            <label>Confirm Password</label>
         </div>
         <center>
         <button type="submit" name="newPass">Change Password</button>
         </center>
      </form>
   </div>
</div>

   <div class="form-container">
      <!-- <form action="models/passworded/pass.php" method="post" id=""> -->
      <form action="" method="post" id="">
         <div class="logo">
            <img src="./assets/picture/MLW Logo.png" alt="logo">
         </div>
         <h3>login now</h3>
         <?php
         if(isset($error)){
            foreach($error as $error){
               echo '<span class="error-msg">'.$error.'</span>';
            };
         };
         ?>
         <input type="text" name="email" required placeholder="Enter your username" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
         <input type="password" name="password" required placeholder="Enter your password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : ''; ?>" autocomplete="off">
         <input type="submit" name="submit" value="login now" class="form-btn">
         
         <p onclick="window.location.href='index.php'" style="cursor: pointer;"><span style="color: red;">◀</span> Back to Homepage</p>
      </form>
   </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.all.min.js"></script>

   <script>

      // Function to handle button clicks and redirection
   function handleButtonClick(telco) {
      // Store the selected telco in a session variable
      sessionStorage.setItem('selectedTelco', telco);

      // Redirect to the universal login page
      window.location.href = 'login.html'; // Replace with your actual login page URL
   }

   // Add event listeners to the buttons
   const buttons = document.querySelectorAll('.menu-box button img');
   buttons.forEach(button => {
      button.addEventListener('click', () => {
         const telcoName = button.getAttribute('alt').toLowerCase();
         handleButtonClick(telcoName);
      });
   });
  // Get the modal element
  var modal = document.getElementById("changePasswordModal");

  // Function to close the modal
  function closeModal() {
    modal.style.display = "none";
  }

  // Listen for the ESC key press event
  document.addEventListener("keydown", function(event) {
    if (event.keyCode === 27) {
      closeModal();
    }
  });
</script>
</body>
</html>