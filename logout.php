<?php
    // Include connection only when we need to update the database
    include 'config/connection.php';

    session_start();

    // Set the time zone to Philippines time.
    // date_default_timezone_set('Asia/Manila');

    // Get the current day and time.
    $current_day_and_time = date('Y-m-d H:i:s');

    if (isset($_SESSION['admin_name']) || isset($_SESSION['user_name'])) {
        
        
        
        if (isset($_SESSION['admin_name'])) {
            $email = mysqli_real_escape_string($conn, $_SESSION['admin_email']);
            $loginquery = "UPDATE `$database[0]`.user SET last_online = ? WHERE email = ?";
            $stmt = $conn->prepare($loginquery);
            $stmt->bind_param("ss", $current_day_and_time, $email);
            $stmt->execute();
            $stmt->close();
        } elseif (isset($_SESSION['user_name'])) {
            $email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
            $loginquery = "UPDATE `$database[0]`.user SET last_online = ? WHERE email = ?";
            $stmt = $conn->prepare($loginquery);
            $stmt->bind_param("ss", $current_day_and_time, $email);
            $stmt->execute();
            $stmt->close();
        }
    }

    session_destroy(); // Unset all session variables
    header('location: login.php');
    exit();

?>