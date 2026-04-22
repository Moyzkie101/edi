<?php

include '../../config/connection.php';
session_start();

echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../../assets/login/js/jquery-3.7.1.js"></script>';

if (isset($_POST['newPass'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Support both user and admin session keys
    if (isset($_SESSION['user_email'])) {
        $email = $_SESSION['user_email'];
    } elseif (isset($_SESSION['admin_email'])) {
        $email = $_SESSION['admin_email'];
    } else {
        $email = null;
    }

    // Handle missing email in session
    if ($email === null) {
        echo '<html>
        <head>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                body { font-family: "Poppins", sans-serif; }
            </style>
        </head>
        <body>
            <script>
                window.onload = function() {
                    Swal.fire({
                        title: "Error",
                        text: "Session expired. Please log in again.",
                        icon: "error",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "../../login.php";
                    });
                }
            </script>
        </body>
        </html>';
        exit();
    }

    // Prevent empty passwords
    if (empty($newPassword) || empty($confirmPassword)) {
        echo '<html>
        <head>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                body { font-family: "Poppins", sans-serif; }
            </style>
        </head>
        <body>
            <script>
                window.onload = function() {
                    Swal.fire({
                        title: "Error",
                        text: "Password fields cannot be empty.",
                        icon: "error",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "../../login.php";
                    });
                }
            </script>
        </body>
        </html>';
        exit();
    }

    if ($newPassword === $confirmPassword) {
        $hashedPassword = md5($newPassword);
        $updateQuery = "UPDATE " . $database[0] . ".user SET password = '$hashedPassword' WHERE email = '$email'";
        $result = mysqli_query($conn, $updateQuery);
        if ($result) {
            // Password successfully changed, destroy session
            session_destroy();
            echo '<html>
            <head>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <style>
                    body { font-family: "Poppins", sans-serif; }
                </style>
            </head>
            <body>
                <script>
                    window.onload = function() {
                        Swal.fire({
                            title: "Success",
                            text: "Password successfully changed! Please log in again.",
                            icon: "success",
                            confirmButtonText: "OK"
                        }).then(() => {
                            window.location.href = "../../login.php";
                        });
                    }
                </script>
            </body>
            </html>';
            exit();
        } else {
            echo '<html>
            <head>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <style>
                    body { font-family: "Poppins", sans-serif; }
                </style>
            </head>
            <body>
                <script>
                    window.onload = function() {
                        Swal.fire({
                            title: "Error",
                            text: "Failed to change the password.",
                            icon: "error",
                            confirmButtonText: "OK"
                        }).then(() => {
                            window.location.href = "../../login.php";
                        });
                    }
                </script>
            </body>
            </html>';
            exit();
        }
    } else {
        $_SESSION['password_error'] = 'Password mismatch. Please try again.';
        echo '<html>
        <head>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                body { font-family: "Poppins", sans-serif; }
            </style>
        </head>
        <body>
            <script>
                window.onload = function() {
                    Swal.fire({
                        title: "Error",
                        text: "Password mismatch.",
                        icon: "error",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "../../login.php";
                    });
                }
            </script>
        </body>
        </html>';
        exit();
    }
}
?>