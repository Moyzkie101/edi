<?php

include '../../config/connection.php';
session_start();

// $conn = mysqli_connect($host, $username, $password, $database);

echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../../assets/login/js/jquery-3.7.1.js"></script>';

if (isset($_POST['newPass'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = $_SESSION['user_email'];
    if ($newPassword === $confirmPassword) {
        $hashedPassword = md5($newPassword);
        $updateQuery = "UPDATE " . $database[0] . ".user SET password = '$hashedPassword' WHERE email = '$email'";
        // Execute the update query using your database connection
        $result = mysqli_query($conn, $updateQuery);
        if ($result) {
            // Password successfully changed
            echo '<html>
            <head>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <style>
                    body {
                        font-family: "Poppins", sans-serif;
                    }
                </style>
            </head>
            <body>
                <script>
                    window.onload = function() {
                        Swal.fire({
                            title: "Success",
                            text: "Password successfully changed!",
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
            // Handle the case where the update query fails
            echo '<html>
            <head>
                <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <style>
                    body {
                        font-family: "Poppins", sans-serif;
                    }
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
        // Passwords do not match
        echo '<html>
        <head>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <style>
                body {
                    font-family: "Poppins", sans-serif;
                }
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