<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>E D I | <?php if($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'user') echo ucfirst($_SESSION['user_type']); else echo "Guest";?></title>
<!-- <link rel="icon" href="<?php //echo $base_path; ?>assets/picture/MLW Logo.png" type="image/x-icon"/> -->
<!-- <link rel="icon" href="../assets/picture/MLW Logo.png" type="image/x-icon"/> -->
<link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon"/>
<!-- custom CSS file link  -->
<link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css">