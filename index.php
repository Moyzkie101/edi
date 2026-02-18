<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="assets/picture/MLW Logo.png" type="image/x-icon"/>
        <link rel="stylesheet" href="assets/css/style.css">
        <title>E D I</title>
    </head>
    <body>
        <div class="welcome-container">
            <img src="assets/picture/Diamante.png" alt="" class="welcome-logo">
            <div class="welcome-message">
                <h1>Electronic Data Interchange </br><span>System</span></h1>
                <!--<p>We are glad to have you here.</p>-->
            <button id="exploreButton">LOGIN</button>
            </div>
        </div>
    </body>
    <script>
        document.getElementById('exploreButton').addEventListener('click', function() {
            window.location.href = 'login.php';
        });
    </script>
</html>