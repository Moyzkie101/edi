<?php
include 'config/connection.php';

session_start();

$alertType = null;
$alertTitle = '';
$alertText = '';
$redirectTo = '';
$showToast = false;
$passwordPrompt = '';
$showForcePasswordPrompt = false;

$isLoggedIn = isset($_SESSION['user_type']) && (isset($_SESSION['user_email']) || isset($_SESSION['admin_email']));

if ($isLoggedIn) {
    if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === true) {
        // Stay on login page and show change password modal
    } else {
        header('location: dashboard/');
        exit();
    }
}

if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = md5($_POST['password']);

    $select = "SELECT * FROM " . $database[0] . ".user WHERE email = '$email' AND password = '$pass'";
    $result = mysqli_query($conn, $select);

    $current_day_and_time = date('Y-m-d H:i:s');
    $loginquery = "UPDATE " . $database[0] . ".user SET last_online = '$current_day_and_time' WHERE email = '$email'";

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);

        if ($row['status'] === 'Inactive') {
            $alertType = 'error';
            $alertTitle = 'End-User is Inactive';
            $alertText = 'Please contact the system administrator.';
        } else {
            mysqli_query($conn, $loginquery);

            session_regenerate_id(true);

            $_SESSION['user_type'] = $row['user_type'];
            $_SESSION['user_status'] = $row['status'];
            $_SESSION['user_roles'] = $row['roles'];

            if ($row['user_type'] === 'admin') {
                $_SESSION['admin_name'] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                $_SESSION['admin_email'] = $row['email'];
                unset($_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['force_password_change']);

                $showToast = true;
                $alertTitle = 'Signed in successfully';
                $redirectTo = 'dashboard/';
            } else {
                $_SESSION['user_name'] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                $_SESSION['user_email'] = $row['email'];
                unset($_SESSION['admin_name'], $_SESSION['admin_email']);

                if ($pass === md5('Mlinc1234')) {
                    $_SESSION['force_password_change'] = true;
                    $showForcePasswordPrompt = true;
                } else {
                    unset($_SESSION['force_password_change']);
                    $showToast = true;
                    $alertTitle = 'Signed in successfully';
                    $redirectTo = 'dashboard/';
                }
            }
        }
    } else {
        $alertType = 'error';
        $alertTitle = 'Incorrect Username or Password';
        $alertText = 'Please check your username and password. Try again.';
    }
}

if (isset($_SESSION['success_message'])) {
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['password_error'])) {
    $passwordPrompt = $_SESSION['password_error'];
    unset($_SESSION['password_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>E D I</title>
   <link rel="stylesheet" href="assets/css/login/login-style.css?v=<?php echo time(); ?>">
   <link rel="icon" href="assets/picture/logo.png" type="image/x-icon"/>
   <script src="sweetalert2/dist/sweetalert2.all.min.js"></script>
   <link rel="stylesheet" href="sweetalert2/dist/sweetalert2.min.css">
   <script src="assets/js/login/jquery-3.7.1.js"></script>
</head>
<body>
   <div id="changePasswordModal" class="change-password-modal">
   <div class="change-password-modal-content">
      <button type="button" class="modal-close" onclick="closeModal()" aria-label="Cancel password change">&times;</button>
      <center>
         <h3>Create a New Password</h3>
         <?php if (!empty($passwordPrompt)) { ?>
            <p class="password-prompt"><?php echo htmlspecialchars($passwordPrompt); ?></p>
         <?php } ?>
      </center>
      <br>
      <form action="models/passworded/change_password.php" method="post">
         <div class="modal-input-container">
            <input
               type="password"
               name="new_password"
               id="new-password"
               required
               placeholder=" "
               autocomplete="new-password"
               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
               title="Password must contain at least one uppercase letter, one lowercase letter, one digit, and be at least 8 characters long."
            >
            <label for="new-password">New Password</label>
         </div>

         <div class="modal-input-container">
            <input
               type="password"
               name="confirm_password"
               id="confirm-password"
               required
               placeholder=" "
               autocomplete="new-password"
            >
            <label for="confirm-password">Confirm Password</label>
         </div>

         <center>
            <button type="submit" name="newPass">Change Password</button>
         </center>
      </form>
      </div>
   </div>

   <div class="login-card">
      <img src="./assets/picture/MLW Logo.png" alt="logo" class="login-logo">
      <h3>Login Now</h3>

      <form action="" method="post">
         <div class="login-input-container">
            <input
               type="text"
               name="email"
               id="login-email"
               required
               placeholder=" "
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
               autocomplete="username"
               oninput="this.value = this.value.toUpperCase()"
            >
            <label for="login-email">Enter your username</label>
         </div>

         <div class="login-input-container">
            <input
               type="password"
               name="password"
               id="login-password"
               required
               placeholder=" "
               autocomplete="current-password"
            >
            <label for="login-password">Enter your password</label>
         </div>

         <button type="submit" name="submit" class="btn">Login Now</button>
         <p onclick="window.location.href='index.php'" style="cursor: pointer;"><span style="color: red;">◀</span> Back to Homepage</p>
      </form>
   </div>

   <div class="login-wave"></div>

   <script>
      const forcePasswordChange = <?php echo isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === true ? 'true' : 'false'; ?>;
      const showForcePasswordPrompt = <?php echo $showForcePasswordPrompt ? 'true' : 'false'; ?>;
      const showToast = <?php echo $showToast ? 'true' : 'false'; ?>;
      const alertType = <?php echo json_encode($alertType); ?>;
      const alertTitle = <?php echo json_encode($alertTitle); ?>;
      const alertText = <?php echo json_encode($alertText); ?>;
      const redirectTo = <?php echo json_encode($redirectTo); ?>;

      const modal = document.getElementById('changePasswordModal');

      function openModal() {
         modal.style.display = 'block';
      }

      function CloseModal() {
         Swal.fire({
            title: 'Are you sure?',
            text: "You haven't finished changing your password. Do you want to cancel and log out?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, log out',
            cancelButtonText: 'Continue',
            allowOutsideClick: false
         }).then((result) => {
            if (result.isConfirmed) {
               window.location.href = 'logout.php';
            }
            // else do nothing, keep modal open
         });
      }

      // Replace all calls to closeModal() with confirmCloseModal()
      document.querySelector('.modal-close').onclick = CloseModal;

      document.addEventListener('keydown', function (event) {
         if (event.key === 'Escape' && modal.style.display === 'block') {
            CloseModal();
         }
      });

      document.addEventListener('DOMContentLoaded', function () {
         if (showForcePasswordPrompt) {
            Swal.fire({
               title: 'Change Password',
               icon: 'warning',
               showCancelButton: true,
               confirmButtonText: 'OK',
               cancelButtonText: 'Cancel',
               allowOutsideClick: false
            }).then((result) => {
               if (result.isConfirmed) {
                  openModal();
               } else {
                  closeModal();
               }
            });
            return;
         }

         if (showToast) {
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
                  toast.addEventListener('mouseenter', Swal.stopTimer);
                  toast.addEventListener('mouseleave', Swal.resumeTimer);
               }
            });

            Toast.fire({
               icon: 'success',
               title: alertTitle
            }).then(() => {
               if (redirectTo) {
                  window.location.href = redirectTo;
               }
            });
            return;
         }

         if (alertType) {
            Swal.fire({
               title: alertTitle,
               text: alertText,
               icon: alertType,
               timer: 2000,
               allowOutsideClick: false
            });
         }

         if (forcePasswordChange) {
            openModal();
         }
      });

      document.addEventListener('DOMContentLoaded', function () {
      const loginInputs = document.querySelectorAll('.login-card .login-input-container input');

      function syncLoginLabels() {
         loginInputs.forEach((input) => {
            input.classList.toggle('has-value', input.value.trim() !== '');
         });
      }

      syncLoginLabels();
      setTimeout(syncLoginLabels, 300);
      setTimeout(syncLoginLabels, 800);

      loginInputs.forEach((input) => {
         input.addEventListener('input', syncLoginLabels);
         input.addEventListener('change', syncLoginLabels);
         input.addEventListener('blur', syncLoginLabels);
      });

      // keep your existing SweetAlert/modal logic below this
   });

      document.addEventListener('keydown', function (event) {
         if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
         }
      });
      
   </script>
</body>
</html>