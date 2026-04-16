<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/picture/logo.png" type="image/x-icon"/>
    <title>Payroll System</title>

    <!-- LINK CSS -->
    <link rel="stylesheet" href="landingpage.css">
</head>
<body>

<!-- HEADER -->
<header>
    <div class="logo-container">
        <img src="assets/picture/Diamante.png" alt="" class="welcome-logo">
        <div class="logo-text">Payroll System</div>
    </div>

    <nav>
        <a href="#hero">Home</a>
        <a href="#features">Features</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<!-- HERO -->
<section class="hero" id="hero">
    <div class="hero-text">
        <h1>Smart & Efficient <span>Payroll Management</span></h1>
        <p>
            Simplify employee salary processing, automate deductions,
            and generate reports with ease using our modern payroll system.
        </p>
        <a href="login.php" class="btn">Get Started</a>
    </div>

   
    <div class="hero-box">
         <img src="./video/payroll.gif" alt="Payroll animation" style="width:100%; border-radius:12px; margin-bottom:16px;">
            <h3>Welcome Back!</h3>
            <p>Login to manage payroll</p>
            <a href="login.php" class="btn">Login</a>
    </div>
</section>

<!-- FEATURES -->
<section class="features" id="features">
    <h2>Why Choose Our System?</h2>

    <div class="feature-grid">
        <div class="feature">
            <h3>Automated Payroll</h3>
            <p>Quick and accurate salary computation.</p>
        </div>

        <div class="feature">
            <h3>Secure Data</h3>
            <p>Advanced security for employee records.</p>
        </div>

        <div class="feature">
            <h3>Reports & Insights</h3>
            <p>Generate detailed payroll reports instantly.</p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <p>&copy; <?php echo date("Y"); ?> Payroll System. All rights reserved.</p>
</footer>

</body>
</html>