<?php
session_start();
if (isset($_SESSION['error'])) {
    echo "<script>alert('" . $_SESSION['error'] . "');</script>";
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload Form</title>
</head>
<body>
    <form action="import-file.php" method="post" enctype="multipart/form-data">
        <div class="custom-select-wrapper">
            <label for="zone">Zone </label>
            <select name="zone" id="zone" required>
                <option value="">Select Zone</option>
                <option value="VISMIN">VISMIN</option>
                <option value="LNCR">LNCR</option>
            </select>
            <div class="custom-arrow"></div>
        </div>
        <div class="custom-select-wrapper">
            <label for="restricted-date">Payroll date </label>
            <input type="date" id="restricted-date" name="restricted-date" required>
        </div>
        
        <label for="file-upload" class="custom-file-upload">
            <span class="upload-text">Select a File</span>
        </label>
        <input id="file-upload" type="file" name="excelFile" accept=".xls,.xlsx" required/>
        
        <input type="submit" class="upload-btn" name="upload" value="Upload">
    </form>
</body>
</html>
