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
        <div class="logo-text">Payroll</div>
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
        <span class="hero-badge">Excel Import • Auto-Reconciliation</span>
        <h1>Accurate Payroll, <span>Reconciled Instantly</span></h1>
        <p>
            Import your Excel payroll reports and automatically reconcile every entry. Instantly identify variances, streamline payroll reviews, and reduce manual reconciliation effort.
        </p>
        <a href="login.php" class="btn">Get Started</a>
    </div>

    <div class="hero-box">
        <img src="./video/payroll.png" alt="Payroll animation" style="width:100%; border-radius:12px; margin-bottom:16px;">
    </div>
</section>

<!-- FEATURES -->
<section class="features" id="features">
    <h2>Why Choose Our System?</h2>
    <p class="features-subtitle">Everything you need to reconcile payroll data accurately and catch discrepancies before they cost you.</p>

    <div class="feature-grid">
        <div class="feature">
            <div class="feature-icon">⇄</div>
            <h3>Smart Reconciliation</h3>
            <p>Import Excel payroll reports and automatically match every payroll record, quickly identifying variances and reducing manual reconciliation.</p>
        </div>

        <div class="feature">
            <div class="feature-icon">🔒</div>
            <h3>Secure Payroll Data</h3>
            <p>Payroll files and sensitive payroll data are protected with enterprise-grade security, ensuring confidentiality and data integrity.</p>
        </div>

        <div class="feature">
            <div class="feature-icon">📊</div>
            <h3>Variance Reporting</h3>
            <p>View clear, real-time variance reports that highlight discrepancies, making them easy to review and resolve.</p>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <p>&copy; <?php echo date("Y"); ?> Payroll System. All rights reserved.</p>
</footer>

</body>
</html>