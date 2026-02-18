<?php

    include '../config/connection.php'; // Correct path

    // Get the id and status from the AJAX request
    $id = intval($_POST['id']);
    $status = $_POST['status'];

    // Update the status in the database
    $query = "UPDATE " . $database[0] . ".user SET status = '$status' WHERE id = $id";
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "success";
    } else {
        echo "error";
    }

?>