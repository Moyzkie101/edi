<?php
    include '../config/connection.php';
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include '../templates/header.php' ?>
        <link rel="stylesheet" href="../assets/css/admin/admin-style.css?v=<?php echo time(); ?>">
        
    </head>
    <body>
        <div class="top-content">
            <div class="usernav">
                <img src="../assets/picture/logo.png" alt="Logo" class="navLogo"> 
            </div>
            <?php include '../templates/menu.php' ?>
        </div>
        <div class="home-logo">
            <img src="../assets/picture/weblogo.png" alt="MLhuillier" width="850px" height="250px">
        </div>
        <script src="../assets/js/admin/admin.js"></script>
    </body>

</html>