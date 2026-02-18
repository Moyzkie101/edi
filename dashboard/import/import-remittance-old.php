<?php
    include '../../config/connection.php';
    session_start();

    if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'user')) {
        header('location: ' . $auth_url . 'logout.php');
        session_destroy();
        exit();
    }else{
        // Check if user_roles session exists and user has HRMD role
        if (!isset($_SESSION['user_roles']) || empty($_SESSION['user_roles'])) {
            header('location: ' . $auth_url . 'logout.php');
            session_destroy();
            exit();
        }
        
        $roles = array_map('trim', explode(',', $_SESSION['user_roles'])); // Convert roles into an array and trim whitespace
        $hasRequiredRole = false;
        
        foreach($roles as $role) {
            switch($role) {
                case 'SYSTEM':
                    // Handle SYSTEM role - no access to this page
                    break;
                case 'ML WALLET':
                    // Handle ML WALLET role - no access to this page
                    break;
                case 'HRMD':
                    // Handle HRMD role - no access to this page
                    break;
                case 'CAD':
                    // Handle CAD role - allow access to this page
                    $hasRequiredRole = true;
                    break;
                case 'ML FUND':
                    // Handle ML FUND role - no access to this page
                    break;
                case 'KP DOMESTIC':
                    // Handle KP DOMESTIC role - no access to this page
                    break;
                case 'FINANCE':
                    // Handle FINANCE role - no access to this page
                    break;
                case 'HO RFP':
                    // Handle HO RFP role - no access to this page
                    break;
                case 'TELECOMS':
                    // Handle TELECOMS role - no access to this page
                    break;
                default:
                    // Handle unknown role - no access
                    break;
            }
        }
        
        // If user doesn't have required role, redirect to logout
        if (!$hasRequiredRole) {
            header('location: ' . $auth_url . 'logout.php');
            session_destroy();
            exit();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        .display_data {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #user:hover{
            background-color: #db120b;
            color: #fff;
            padding: 10px;
        }
        .opt-group {
            display: flex;
            background-color: #3262e6;
            color: white;
            width: 100%;
            align-items: center;
            height: 35px;
        }
        .card{
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form{
            display: flex;
            align-items: center;
            width: 100%;
            height: auto;
            padding: 10px;
        }

        .cancel_date label {
            font-size: 14px;
            margin-right: 15px;
            /* color: #333; */
        }

        div .cancel_date{
            margin-right: 15px;
            color: #000000;
        }

        .cdate {
            border: 1px solid #db120b;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 500;
            /* color: #333; */
        }
        .import-file {
            display: flex;
        }
        select {
            width: 200px;
            padding: 10px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            -webkit-appearance: none; /* Remove default arrow in WebKit browsers */
            -moz-appearance: none; /* Remove default arrow in Firefox */
            appearance: none; /* Remove default arrow in most modern browsers */
            color: #F14A51;
        }
        .custom-select-wrapper {
            position: relative;
            display: inline-block;
            margin-left: 20px;
        }

        input[type="date"] {
            width: 200px;
            padding: 10px;
            font-size: 14px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            margin-right: 20px;
            color: #F14A51;
        }
        .upload-btn {
            background-color: #d70c0c;
            color: #fff;
            padding: 5px 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #fff;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            width: 100px;
            margin-right: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .upload-btn:hover {
            background-color:rgb(180, 31, 31);
        }

        .choose-file input[type="file"] {
            display: block;
            padding: 5px;
            cursor: pointer;
            border: 1px solid  #ccc;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
            margin-left: 25px;
            background-color: #fff;
            font-weight: 500;
            color: #F14A51;
        }

        input[type="file"]::file-selector-button {
            border-radius: 15px;
            padding: 0 16px;
            height: 46px;
            cursor: pointer;
            background-color: white;
            border: 1px solid rgba(0, 0, 0, 0.16);
            box-shadow: 0px 1px 0px rgba(0, 0, 0, 0.05);
            margin-right: 16px;
            transition: background-color 200ms;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #f3f4f6;
        }
        input[type="file"]::file-selector-button:active {
            background-color: #e5e7eb;
        }

        /* loading screen */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            z-index: 9999;
        }

        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* empty cells or not empty cells */
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .export-btn {
            background-color: #d70c0c;
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .export-btn:hover {
            background-color: #8f2c16;
        }
        .print-btn {
            background-color: #d70c0c;
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .print-btn:hover {
            background-color: #423e3d; 
        }
        .override-btn {
            background-color: #db120b; 
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            display: none;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .override-btn:hover {
            background-color: #F15A24;
        }
    </style>

</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>
    <center><h2>REMITTANCE OLD<span>[IMPORT]</span></h2></center>
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="form">
                <div class="cancel_date">
                    <label for="mainzone">Mainzone </label>
                    <select name="mainzone" id="mainzone" required>
                        <option value="">Select Mainzone</option>
                        <option value="VISMIN">VISMIN</option>
                        <option value="LNCR">LNCR</option>
                    </select>
                    <!-- <div class="custom-arrow"></div> -->
                </div>
                <div class="cancel_date">
                    <label for="restricted-date">Payroll date </label>
                    <input type="date" id="restricted-date" name="restricted-date" required>
                </div>
                <div class="choose-file">
                    <div class="import-file">
                        <input type="file" name="excelFile" accept=".xls,.xlsx" class="form-control" required />
                        <input type="submit" class="upload-btn" name="upload" value="Upload">
                    </div>
                </div>
            </form>
            <div class="display_data">
                <div class="showEP" style="display: none">
                    <button type="submit" class="export-btn" onclick="exportToPDF()">
                        Export to PDF
                    </button>
                </div>
                <div class="showEP" style="display: none">
                    <button type="submit" class="print-btn" onclick="printTable()">
                        <i style="margin-right: 7px;" class="fa-solid fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo $relative_path; ?>assets/js/admin/import-remittance/script.js"></script>
</body>
</html>

<?php


require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

function insertData($spreadsheet, $conn, $database, $restrictedDate, $mainzone) {

    $allInsertionsSuccessful = true;

    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        $highestRow = $worksheet->getHighestRow();

        $glCodes = [];
        for ($col = 'C'; $col <= 'F'; $col++) {
            $glCodes[] = $worksheet->getCell($col . '3')->getValue();
        }

        for ($row = 5; $row <= $highestRow; ++$row) {

            // Check for blank rows by verifying key cells are not empty
            if (empty($worksheet->getCell('A' . $row)->getValue()) && empty($worksheet->getCell('B' . $row)->getValue())) {
                break;
            }

            $column1 = $conn->real_escape_string(intval($worksheet->getCell('A' . $row)->getValue()));
            $column2 = $conn->real_escape_string(strval($worksheet->getCell('B' . $row)->getValue()));
            $column3 = $conn->real_escape_string(floatval($worksheet->getCell('C' . $row)->getValue()));
            $column4 = $conn->real_escape_string(floatval($worksheet->getCell('D' . $row)->getValue()));
            $column5 = $conn->real_escape_string(floatval($worksheet->getCell('E' . $row)->getValue()));
            $column6 = $conn->real_escape_string(floatval($worksheet->getCell('F' . $row)->getValue()));
            $column7 = $conn->real_escape_string(strval($worksheet->getCell('G' . $row)->getValue()));
            $column8 = $conn->real_escape_string(strval($worksheet->getCell('H' . $row)->getValue()));
            $column9 = $conn->real_escape_string(strval($worksheet->getCell('I' . $row)->getValue()));

            // Set the time zone to Philippines time.
            // date_default_timezone_set('Asia/Manila');

            $uploaded_date = date('Y-m-d H:i:s');
            $uploaded_by = $conn->real_escape_string($_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown user');

            $select_desc = "SELECT region_description, zone_code FROM " . $database[1] . ".region_masterfile WHERE region_code = '$column8'";
            $desc_result = mysqli_query($conn, $select_desc);
            
            if ($desc_result) {
                $desc_row = mysqli_fetch_assoc($desc_result);
                $region_desc = $desc_row['region_description'];
            }else{
                $region_desc = "Unknown Region";
            }
            
            // $sql = "INSERT INTO ".$database[0].".remitance (
            //     remitance_date, mainzone, zone, region, region_code, bos_code, branch_name, dr1, gl_code_dr1, dr2, gl_code_dr2, 
            //     dr3, gl_code_dr3, dr4, gl_code_dr4, cost_center, uploaded_date, uploaded_by, post_edi
            // ) VALUES ('$restrictedDate', '$mainzone', '$column9', '$region_desc', '$column8', $column1, '$column2', $column3, $glCodes[0], $column4, 
            // $glCodes[1], $column5, $glCodes[2], $column6, $glCodes[3], '$column7', '$uploaded_date', '$uploaded_by', 'pending')";

            $sql = "INSERT INTO ".$database[0].".remitance (
                                remitance_date,
                                mainzone, 
                                `zone`, 
                                region, 
                                region_code, 
                                bos_code, 
                                branch_name,
                                
                                dr1, 
                                gl_code_dr1, 
                                dr2, 
                                gl_code_dr2, 
                                dr3, 
                                gl_code_dr3,

                                dr4, 
                                gl_code_dr4, 

                                cost_center, 
                                uploaded_date, 
                                uploaded_by, 
                                post_edi
                    ) VALUES (
                            '$restrictedDate', 
                            '$mainzone', 
                            '$column9', 
                            '$region_desc', 
                            '$column8', 
                            $column1, 
                            '$column2', 

                            $column3, 
                            $glCodes[0], 
                            $column5, 
                            $glCodes[2], 
                            $column4, 
                            $glCodes[1], 
                            $column6, 
                            $glCodes[3], 
                            '$column7', 
                            '$uploaded_date', 
                            '$uploaded_by', 
                            'pending'
                    )";

            if (!$conn->query($sql)) {
                $allInsertionsSuccessful = false; 
                error_log('Query failed: ' . htmlspecialchars($conn->error));
                echo $sql; 
            }
        }

    }

    return $allInsertionsSuccessful; 
}

if (isset($_POST['upload'])) {

    
    $check_mainzone = $_POST['mainzone'];
    $filePath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $spreadsheet = IOFactory::load($filePath);
    $restrictedDate = $_POST['restricted-date'];

    $destination = '../../uploaded_excel_files/' . $fileName; 

    if (move_uploaded_file($filePath, $destination)) {

        $_SESSION['existingFile'] = $destination;
        $_SESSION['existingDate'] =  $restrictedDate;
        $_SESSION['existingMainzone'] =  $check_mainzone;

    } else {

        echo "<script>alert('File upload failed.'); window.location.href='import-file.php';</script>";
        exit;

    }

    // Array to store messages
    $messages = [];

    // Check headers only in the first sheet
    $firstSheet = $spreadsheet->getSheet(0);
    $sheetName = $firstSheet->getTitle(); 

    // Function to display messages in a table
    function displayMessages($messages) {
    
        echo '<style>
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 3px; text-align: left; }
            .success { background-color: #d4edda; }
            .error { background-color: #ffffff; }
            /* Print styles */
            @media print {
                body * {
                    visibility: hidden;
                }
                #messages-table, #messages-table * {
                    visibility: visible;
                }
                #messages-table {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                }
            }
        </style>';
    
        echo '<div id="messages-table">';
        // Check if any message has withButton 'true' and none with 'false'
        $displayOverrideButton = false;
        $hasFalseButton = false;
        foreach ($messages as $msg) {
            if ($msg['withButton'] === 'true') {
                $displayOverrideButton = true;
            }
            if ($msg['withButton'] === 'false') {
                $hasFalseButton = true;
            }
        }

        // Only display the button if at least one 'true' and no 'false'
        if ($displayOverrideButton && !$hasFalseButton) {

            echo '
                <form method="post" action="">

                    <center>
                        <button type="submit" name="overrideData" id="overrideBtn" class="override-btn">Override Data</button>
                    </center>

                </form>
                ';
        }

        echo '<table>';
        echo "<thead><tr><center><th colspan='6' style='border: none;'>Remitance Report</th></center></tr>";
        echo "<tr><th colspan='6' style='border: none;'>Date : " . $_POST['restricted-date'] . "</th></tr>";
        echo "<tr><th colspan='6' style='border: none;'>Filename : " . $_FILES['excelFile']['name'] . "</th></tr>";
        echo '<tr><th>Status</th><th>Sheet Name</th><th>Branch Code</th><th>Branch Name</th><th>Region</th><th>Remarks</th></tr></thead>';
        echo '<tbody>';
        foreach ($messages as $msg) {
            if ($msg['type'] === 'error') {
                $class = $msg['type'] === 'success' ? 'success' : 'error';
                echo "<tr class='$class'>
                    <td>" . ucfirst($msg['type']) . "</td>
                    <td>{$msg['sheet']}</td>
                    <td>{$msg['A']}</td>
                    <td>{$msg['B']}</td>
                    <td>{$msg['H']}</td>
                    <td>{$msg['message']}</td>";
        
                if ($msg['withButton'] === 'true') {
                    echo "<script> document.getElementById('overrideBtn').style.display = 'flex'; </script>";
                }
        
                echo "</tr>";
            }
        }
        
        echo '</tbody></table>';
        echo '</div>';
    
        // After processing, hide loading overlay using JavaScript
        echo '<script>
            document.getElementById("loading-overlay").style.display = "none";
    
            var elements = document.getElementsByClassName("showEP");
    
            // Loop through each element and set its display style to "block"
            for (var i = 0; i < elements.length; i++) {
                elements[i].style.display = "block";
            }
    
            function printTable() {
                window.print();
            }
    
            function exportToPDF() {
                var form = document.createElement("form");
                form.method = "post";
                form.action = "../../models/generate/pdf/remitance_export_pdf.php";

                var messagesInput = document.createElement("input");
                messagesInput.type = "hidden";
                messagesInput.name = "messages";
                messagesInput.value = JSON.stringify(' . json_encode($messages) . ');

                var payrollDateInput = document.createElement("input");
                payrollDateInput.type = "hidden";
                payrollDateInput.name = "remitance_date";
                payrollDateInput.value = "' . $_POST['restricted-date'] . '";

                var filenameInput = document.createElement("input");
                filenameInput.type = "hidden";
                filenameInput.name = "filename";
                filenameInput.value = "' . $_FILES['excelFile']['name'] . '";

                form.appendChild(messagesInput);
                form.appendChild(payrollDateInput);
                form.appendChild(filenameInput);
                document.body.appendChild(form);
                form.submit();
            }   
        </script>';
    }
    
    // Check if the region_code, date, and zone already exist
    function checkExistingRecords($conn,$database, $regionCode, $date, $mainzone) {
        $sql = "SELECT COUNT(*) as count FROM ".$database[0].".remitance WHERE region_code = '" . $conn->real_escape_string($regionCode) . "' 
                AND remitance_date = '" . $conn->real_escape_string($date) . "' AND mainzone = '" . $conn->real_escape_string($mainzone) . "'";
        $result = $conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }
        return false;
    }

    // Function to get data from the database
    function getDatabaseData($conn,$database, $columnAValue, $columnHValue) {
        // Check if columnAValue is numeric, and if so, convert it to an integer to remove leading zeros
        if (ctype_digit($columnAValue)) {
            // Convert the string to a number, stripping leading zeros
            $columnAValue = intval($columnAValue);
        }

        $sql = "SELECT region_code, code FROM " . $database[1] . ".branch_profile WHERE code = '" . $conn->real_escape_string($columnAValue) . "' AND region_code = '" . $conn->real_escape_string($columnHValue) . "'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    }

    // function to check Inactive status
    function checkStatus($conn, $database, $columnAValue, $columnHValue) {
        // Check if columnAValue is numeric, and if so, convert it to an integer to remove leading zeros
        if (ctype_digit($columnAValue)) {
            // Convert the string to a number, stripping leading zeros
            $columnAValue = intval($columnAValue);
        }

        $sql = "SELECT ml_matic_status, region_code, code FROM " . $database[1] . ".branch_profile WHERE code = '" . $conn->real_escape_string($columnAValue) . "' AND region_code = '" . $conn->real_escape_string($columnHValue) . "'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                if($row['ml_matic_status'] === 'Inactive') {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    // Fetch distinct region codes for the given zone
    $zoneQuery = "SELECT DISTINCT region_code FROM " . $database[1] . ".branch_profile WHERE mainzone = '" . $conn->real_escape_string($check_mainzone) . "'";
    $zoneResult = $conn->query($zoneQuery);
    $validRegionCodes = [];
    while ($row = $zoneResult->fetch_assoc()) {
        $validRegionCodes[] = $row['region_code'];
    }

    $codeDetails = []; // Store branch code details for duplicate detection
    $regionDescriptions = []; // Store region descriptions for reference

    // Fetch all region descriptions once
    $select_all_desc = "SELECT region_code, region_description FROM " . $database[1] . ".region_masterfile";
    $result_all_desc = $conn->query($select_all_desc);
    if ($result_all_desc && $result_all_desc->num_rows > 0) {
        while ($row = $result_all_desc->fetch_assoc()) {
            $regionDescriptions[$row['region_code']] = $row['region_description'];
        }
    }

    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $sheetName = $sheet->getTitle();
        $startRow = 5;
        $endRow = $sheet->getHighestRow();
        $columns = ['A', 'B', 'H', 'I'];

        for ($row = $startRow; $row <= $endRow; $row++) {
            $isRowEmpty = true;
            $cellValues = [];
            $emptyCells = [];

            foreach ($columns as $column) {
                $cellValue = $sheet->getCell($column . $row)->getValue();
                $cellValues[$column] = $cellValue;

                if ($cellValue !== null && $cellValue !== '') {
                    $isRowEmpty = false;
                } else {
                    $emptyCells[] = $column . $row;
                }
            }

            if ($isRowEmpty) {
                break;
            }

            // Check for existing records
            if (checkExistingRecords($conn, $database, $cellValues['H'], $_POST['restricted-date'], $check_mainzone)) {
                $region_description = $regionDescriptions[$cellValues['H']] ?? 'Unknown region';

                $messages[] = [
                    'type' => 'error',
                    'withButton' => 'true',
                    'sheet' => $sheetName,
                    'A' => $cellValues['A'],
                    'B' => $cellValues['B'],
                    'H' => $region_description,
                    'message' => "Region '$region_description', date '{$_POST['restricted-date']}', and mainzone '$check_mainzone' already exists."
                ];
            }

            // Compare values with database
            $dbData = getDatabaseData($conn, $database, $cellValues['A'], $cellValues['H']);
            if (!$dbData) {

                $region_description = $regionDescriptions[$cellValues['H']] ?? 'Unknown region';
                if(empty($cellValues['A'])) {
                    $errorMessage = 'Branch code is empty';
                }else {
                    if(!in_array($cellValues['H'], $validRegionCodes)){
                        $errorMessage = 'Wrong Branch Code';
                    }
                }
                // Either region_code or code does not exist in the database
                $messages[] = [
                    'type' => 'error',
                    'withButton' => 'false',
                    'sheet' => $sheetName,
                    'A' => $cellValues['A'],
                    'B' => $cellValues['B'],
                    'H' => $region_description,
                    'message' => 'Branch code is empty / Maybe not belong to this Region.'
                ];
            }
 
            // Check if the selected zone/mainzone matches the region code in column H
            if (!in_array($cellValues['H'], $validRegionCodes)) {

                $region_description = $regionDescriptions[$cellValues['H']] ?? 'Unknown region';

                $messages[] = [
                    'type' => 'error',
                    'withButton' => 'false',
                    'sheet' => $sheetName,
                    'A' => $cellValues['A'],
                    'B' => $cellValues['B'],
                    'H' => $region_description,
                    'message' => "Region '{$region_description}' does not match the selected zone/mainzone '$check_mainzone'."
                ];
            }

            // Check Inactive status 
            if (checkStatus($conn, $database, $cellValues['A'], $cellValues['H'])) {
                $region_description = $regionDescriptions[$cellValues['H']] ?? 'Unknown region';
                $messages[] = [
                    'type' => 'error',
                    'withButton' => 'false',
                    'sheet' => $sheetName,
                    'A' => $cellValues['A'],
                    'B' => $cellValues['B'],
                    'H' => $region_description,
                    'message' => "Inactive branch. Region: '$region_description', Branch code: '{$cellValues['A']}', Branch name: '{$cellValues['B']}'"
                ];
            }

            // Collect branch code details for duplicate detection
            $ExpectedZone = $cellValues['I'];
            if (!empty($cellValues['A'])) {
                if (ctype_digit($cellValues['A'])) {
                    $cellValues['A'] = intval($cellValues['A']);
                }

                // Initialize array if it doesn't exist
                if (!isset($codeDetails[$ExpectedZone])) {
                    $codeDetails[$ExpectedZone] = [];
                }

                if (!isset($codeDetails[$ExpectedZone][$cellValues['A']])) {
                    $codeDetails[$ExpectedZone][$cellValues['A']] = [];
                }

                $codeDetails[$ExpectedZone][$cellValues['A']][] = [
                    'sheet' => $sheetName,
                    'row' => $row,
                    'A' => $cellValues['A'],
                    'B' => $cellValues['B'],
                    'H' => $cellValues['H'],
                    'I' => $cellValues['I']
                ];
            }
 
        }
    }

    // After processing all rows, check for duplicates
    foreach ($codeDetails as $zone => $branchCodes) {
        foreach ($branchCodes as $branchCode => $details) {
            if (count($details) > 1) {  // Duplicate found
                $region_description = $regionDescriptions[$details[0]['H']] ?? 'Unknown region';
                
                foreach ($details as $detail) {
                    // Display a message for each duplicate row separately
                    $messages[] = [
                        'type' => 'error',
                        'withButton' => 'false',
                        'sheet' => $detail['sheet'],
                        'A' => $branchCode,
                        'B' => $detail['B'],
                        'H' => $region_description,
                        'message' => "Duplicate value '{$branchCode}' found in column A, Row {$detail['row']}."
                    ];
                }
            }
        }
    }

    // Check if there were any errors in the uploaded file
    if (empty($messages)) {
        
        echo "<script>
                if (confirm('File is ready to upload. Do you want to continue?')) {
                    window.location.href = 'import-remittance-old.php?proceed=true';
                } else {
                    window.location.href = 'import-remittance-old.php';
                }
            </script>";

    }

    // Display messages
    displayMessages($messages);

}

if (isset($_GET['proceed']) && $_GET['proceed'] === 'true') {

    $filePath = $_SESSION['existingFile'];
    $date = $_SESSION['existingDate'];
    $mainzone = $_SESSION['existingMainzone'];
    $spreadsheet = IOFactory::load($filePath);

    $insertSuccess = insertData($spreadsheet, $conn, $database, $date, $mainzone);

    if ($insertSuccess) {
        echo "<script>alert('Data successfully loaded.'); window.location.href='import-remittance-old.php';</script>";
    } else {
        echo "<script>alert('Failed to upload.'); window.location.href='import-remittance-old.php';</script>";
    }
}

if (isset($_POST['overrideData'])) {

    $filePath = $_SESSION['existingFile'];
    $date = $_SESSION['existingDate'];
    $mainzone = $_SESSION['existingMainzone'];



    $spreadsheet = IOFactory::load($filePath);

    // Your existing logic to get region codes and delete records
    $regionCodesToDelete = [];
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $startRow = 5;
        $endRow = $sheet->getHighestRow();
        for ($row = $startRow; $row <= $endRow; $row++) {
            $regionCode = $sheet->getCell('H' . $row)->getValue();
            if (!empty($regionCode) && !in_array($regionCode, $regionCodesToDelete)) {
                $regionCodesToDelete[] = $regionCode;
            }
        }
    }
    // for fecthing if posted or pending
    foreach ($regionCodesToDelete as $regionCode) {

        $sql = "SELECT DISTINCT post_edi FROM ".$database[0].".remitance WHERE region_code = '" . $conn->real_escape_string($regionCode) . "' 
                    AND remitance_date = '" . $conn->real_escape_string($date) . "' AND mainzone = '" . $conn->real_escape_string($mainzone) . "'";
        $resultPost = $conn->query($sql);
        $row_resultPost = $resultPost->fetch_assoc();

        if ($row_resultPost['post_edi'] === 'pending') {

            // Delete existing records
            foreach ($regionCodesToDelete as $regionCode) {
            
                $sql = "DELETE FROM ".$database[0].".remitance WHERE region_code = '" . $conn->real_escape_string($regionCode) . "' 
                        AND remitance_date = '" . $conn->real_escape_string($date) . "' AND mainzone = '" . $conn->real_escape_string($mainzone) . "'";
                $conn->query($sql);
            }

            // Re-insert data
            $insertSuccess = insertData($spreadsheet, $conn, $database, $date, $mainzone);

            if ($insertSuccess) {
                echo "<script>alert('Data successfully loaded.'); window.location.href='import-remittance-old.php';</script>";
            } else {
                echo "<script>alert('Insertion Failed.'); window.location.href='import-remittance-old.php';</script>";
            }

            // Display messages
            displayMessages($messages);

        }else{
            echo "<script>alert('Opps! Unable to Override. Data Already Posted.'); window.location.href='import-remittance-old.php';</script>";
        }
    }

}

?> 