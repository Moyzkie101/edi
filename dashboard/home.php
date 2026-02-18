<?php
    include '../config/connection.php';
    session_start();

    if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'user')) {
        header('location: ' . $auth_url . 'logout.php');
    }else{
        // Check if user_roles session exists
        $roles = array_map('trim', explode(',', $_SESSION['user_roles'])); // Convert roles into an array and trim whitespace
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include $relative_path . 'templates/header.php' ?>
        <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/admin-style.css?v=<?php echo time(); ?>">
        
    </head>
    <body>
        <div class="top-content">
            <div class="usernav">
                <img src="<?php echo $relative_path; ?>assets/picture/logo.png" alt="Logo" class="navLogo"> 
            </div>
            <?php include $relative_path . 'templates/menu.php' ?>
        </div>
        <div class="home-logo">
            <img src="<?php echo $relative_path; ?>assets/picture/weblogo.png" alt="MLhuillier" width="850px" height="250px">
        </div>
        
    </body>

</html>