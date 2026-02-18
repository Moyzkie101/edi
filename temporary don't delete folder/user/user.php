<?php  
    include '../config/connection.php';
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'admin') {
        header('location: ../login.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php include '../templates/header.php' ?>
        <link rel="stylesheet" href="../assets/css/user/user-style.css?v=<?php echo time(); ?>">
    </head>
    <body>
        <div class="top-content">
            <div class="usernav">
                <img src="../assets/picture/logo.png" alt="Logo" class="navLogo"> 
            </div>
            <div class="btn-nav">
                <ul class="nav-list">
                    <li><a href="user.php">HOME</a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user'): ?>
                        <?php $roles = explode(', ', $_SESSION['user_roles']); // Convert roles into an array ?>
						<?php if (in_array('ML FUND', $roles)): ?>
							<?php include '../templates/mlfund-dropdown.php' ?>
						<?php endif; ?>
                        <?php if (in_array('ML WALLET', $roles)): ?>
                            <?php include '../templates/mlwallet-dropdown.php' ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <li><a href="../logout.php">LOGOUT</a></li>
                </ul>
            </div>
        </div>
        <div class="home-logo">
            <img src="../assets/picture/weblogo.png" alt="MLhuillier" width="850px" height="250px">
        </div>
        <script src="../assets/js/user/user.js"></script>
    </body>

</html>