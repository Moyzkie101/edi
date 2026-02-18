<div class="usernav">
        <h4><i style="margin-right: 10px;" class="fa-solid fa-user-shield"></i><?php if (isset($_SESSION['admin_name'])){ echo "Fullname: ".$_SESSION['admin_name']; }else{ echo "Fullname: ".$_SESSION['user_name']; } ?></h4>
        <h4 style="padding-left:50px;"><?php if (isset($_SESSION['admin_email'])){ echo "Username: ".$_SESSION['admin_email']; }else{ echo "Username: ".$_SESSION['user_email']; } ?></h4>
</div>
<?php include 'menu.php'; ?>
