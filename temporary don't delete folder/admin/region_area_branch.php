<?php

session_start();

if (!isset($_SESSION['admin_name'])) {
  header('location:../login.php');
}

include '../config/connection.php'; 
include '../fetch/fetch-branch-masterfile-data.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <?php include '../templates/header.php' ?>

</head>
<body>
    <?php include '../templates/main-header.php' ?>
    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>








    
    <?php include '../templates/main-footer.php' ?>
    <?php include '../templates/footer.php' ?>
</body>
</html>