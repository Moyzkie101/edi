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

    require_once '../../vendor/autoload.php'; // Adjust path if needed

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
        $fileTmpPath = $_FILES['excelFile']['tmp_name'];
        $fileName = $_FILES['excelFile']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension === 'pdf') {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($fileTmpPath);

            // Read all pages for multi-page support
            $pages = $pdf->getPages();
            $lines = [];
            foreach ($pages as $page) {
                $pageLines = explode("\n", $page->getText());
                foreach ($pageLines as $line) {
                    $lines[] = trim($line);
                }
            }

            $dataRows = [];
            foreach ($lines as $line) {
                // Skip summary/footer lines
                if (stripos($line, 'Total Employees') !== false || stripos($line, 'Total:') !== false) {
                    continue;
                }
                // Parse the line (IDNO, Name, Fund)
                if (preg_match('/^(\d{8})\s+([\p{L} ,]+)\s+([\d,]+\.\d{2})$/u', $line, $matches)) {
                    $idno = $matches[1];
                    $name = trim($matches[2]);
                    $fund = str_replace(',', '', $matches[3]);
                    $dataRows[] = [
                        'idno' => $idno,
                        'name' => $name,
                        'fund' => $fund
                    ];
                }
            }

            if (!empty($dataRows)) {
                $zone = '';
                $region = '';
                $region_code = '';

                $uploaded_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown user';
                $uploaded_date = date('Y-m-d H:i:s');
                $post_edi = 'pending';

                $alreadyExistRows = [];
                $successRows = [];

                // First, check for any duplicates
                $hasDuplicate = false;
                foreach ($dataRows as $row) {
                    $checkStmt = $conn->prepare("SELECT 1 FROM " . $database[0] . ".mlfund_payroll WHERE payroll_date = ? AND employee_id_no = ? AND mainzone = ?");
                    $checkStmt->bind_param("sss", $_POST['restricted-date'], $row['idno'], $_POST['mainzone']);
                    $checkStmt->execute();
                    $checkStmt->store_result();

                    if ($checkStmt->num_rows > 0) {
                        $hasDuplicate = true;
                        $alreadyExistRows[] = [
                            'idno' => $row['idno'],
                            'name' => $row['name'],
                            'fund' => $row['fund'],
                            'extension_file_type' => $fileExtension,
                            'region' => $region,
                            'remarks' => 'already exist'
                        ];
                    }
                    $checkStmt->close();
                }

                if ($hasDuplicate) {
                    // If any duplicate found, do not insert anything
                    echo "<script>alert('Import failed: One or more records already exist. No data was imported.');</script>";
                } else {
                    // No duplicates, proceed to insert all
                    foreach ($dataRows as $row) {
                        $stmt = $conn->prepare("INSERT INTO " . $database[0] . ".mlfund_payroll 
                            (payroll_date, mainzone, zone, region, region_code, employee_id_no, employee_name, ml_fund_amount, extension_file_type, uploaded_by, uploaded_date, post_edi) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param(
                            "sssssssdssss",
                            $_POST['restricted-date'],
                            $_POST['mainzone'],
                            $zone,
                            $region,
                            $region_code,
                            $row['idno'],
                            $row['name'],
                            $row['fund'],
                            $fileExtension,
                            $uploaded_by,
                            $uploaded_date,
                            $post_edi
                        );
                        $stmt->execute();
                        $stmt->close();

                        $successRows[] = [
                            'idno' => $row['idno'],
                            'name' => $row['name'],
                            'fund' => $row['fund'],
                            'extension_file_type' => $fileExtension,
                            'region' => $region,
                            'remarks' => 'imported'
                        ];
                    }
                    echo "<script>alert('Payroll data imported successfully!');</script>";
                }
            } else {
                echo "<script>alert('No valid payroll data found in PDF.');</script>";
            }
        } elseif ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
            // Handle XLSX and XLS file upload
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
            $dataRows = [];
            $unknownRegionRows = []; // Add array to track unknown region rows
            
            foreach ($spreadsheet->getAllSheets() as $worksheet) {
                $sheetName = $worksheet->getTitle(); // Get sheet name for unknown regions
                
                foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
                    // Start reading from row 4 (PhpSpreadsheet is 1-based)
                    if ($rowIndex < 4) continue;

                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $cells = [];

                    foreach ($cellIterator as $cell) {
                        $cells[] = trim((string)$cell->getValue());
                    }

                    // If the first cell is empty, break the loop
                    if ($cells[0] === 'TOTAL EMPLOYEES:') break;

                    // Check if region code is '0' (unknown region)
                    if (isset($cells[0]) && $cells[0] === '0') {
                        // Handle unknown region case
                        if (
                            isset($cells[3], $cells[4], $cells[5]) &&
                            preg_match('/^\d{8}$/', $cells[3]) && // IDNO is 8 digits
                            is_numeric(str_replace(',', '', $cells[5])) // Fund is numeric
                        ) {
                            $idno = $conn->real_escape_string(strval($cells[3]));
                            $name = $cells[4];
                            $fund = $conn->real_escape_string(floatval(str_replace(',', '', $cells[5])));
                            
                            $unknownRegionRows[] = [
                                'sheet_name' => $sheetName, // Add sheet name
                                'zone' => '',
                                'region' => 'Unknown Region',
                                'region_code' => '0',
                                'idno' => $idno,
                                'name' => $name,
                                'fund' => $fund,
                                'remarks' => 'unknown region'
                            ];
                        }
                        continue; // Skip to next row
                    }

                    // Expecting: region/zone | IDNO | Name | Fund
                    if (
                        isset($cells[0], $cells[3], $cells[4], $cells[5]) &&
                        preg_match('/^\d{8}$/', $cells[3]) && // IDNO is 8 digits
                        is_numeric(str_replace(',', '', $cells[5])) // Fund is numeric
                    ) {
                        $sql = "SELECT
                                    rm.zone_code,
                                    rm.region_code,
                                    zm.zone_code AS zm_zone_code,
                                    zm.zone_description,
                                    rm.region_description
                                FROM
                                    " . $database[1] . ".zone_masterfile AS zm
                                JOIN
                                    " . $database[1] . ".region_masterfile AS rm
                                ON
                                    rm.zone_code = zm.zone_code";
                        if (
                            $cells[0] === 'HEADOFFICE1' ||
                            $cells[0] === 'HEADOFFICE2' ||
                            $cells[0] === 'VISMIN-MANCOMM' ||
                            $cells[0] === 'LNCR-MANCOMM'
                        ) {
                            
                            if($cells[0] === 'HEADOFFICE1' || $cells[0] === 'HEADOFFICE2'){
                                $sql .= " WHERE rm.region_code ='{$cells[0]}' AND rm.region_description IN ('HO VISMIN SUPPORT', 'HO LNCR SUPPORT')";
                            }else{
                                $sql .= " WHERE zm.zone_code = '{$cells[0]}'";
                            }
                        } else {
                            $sql .= " WHERE rm.region_code = '{$cells[0]}'
                                        AND rm.region_description
                                        NOT IN ('VISMIN-SUPPORT', 'LNCR-SUPPORT', 'VISMIN-MANCOMM', 'LNCR-MANCOMM')
                                        ORDER BY rm.region_description ASC";
                        }

                        $result = $conn1->query($sql);
                        $rowDb = $result ? $result->fetch_assoc() : null;

                        if ($rowDb) {
                            if (
                                $cells[0] === 'HEADOFFICE1' ||
                                $cells[0] === 'HEADOFFICE2' ||
                                $cells[0] === 'VISMIN-MANCOMM' ||
                                $cells[0] === 'LNCR-MANCOMM'
                            ) {
                                if($cells[0] === 'HEADOFFICE1'){
                                    $zone = 'VISMIN-SUPPORT';
                                    $region = 'HO VISMIN SUPPORT';
                                    // $region_code = 'HEADOFFICE1';
                                    $region_code =  $cells[0];

                                }if($cells[0] === 'HEADOFFICE2'){
                                    $zone = 'LNCR-SUPPORT';
                                    $region = 'HO LNCR SUPPORT';
                                    // $region_code = 'HEADOFFICE2';
                                    $region_code =  $cells[0];

                                }elseif($cells[0] !== 'HEADOFFICE1' && $cells[0] !== 'HEADOFFICE2'){
                                    $zone = $rowDb['zone_code'];
                                    $region = $rowDb['zone_description'];
                                    $region_code = $rowDb['zone_code'];
                                }
                                
                            } else {
                                $zone = $rowDb['zone_code'];
                                $region = $rowDb['region_description'];
                                $region_code = $rowDb['region_code'];
                            }
                            $idno = $conn->real_escape_string(strval($cells[3]));
                            $name = $cells[4];
                            $fund = $conn->real_escape_string(floatval(str_replace(',', '', $cells[5])));
                            $dataRows[] = [
                                'zone' => $zone,
                                'region' => $region,
                                'region_code' => $region_code,
                                'region_zone' => $region_zone ?? '',
                                'idno' => $idno,
                                'name' => $name,
                                'fund' => $fund
                            ];
                        }
                    }
                }
            }

            // Check if there are unknown region rows first
            if (!empty($unknownRegionRows)) {
                // If unknown regions found, show error and don't proceed with database operations
                echo "<script>alert('Import failed: Unknown region codes (0) detected. Please fix the region codes in your file and try again.');</script>";
                
                // Initialize empty arrays for other results since we're not processing them
                $alreadyExistRows = [];
                $successRows = [];
                // Don't reset $unknownRegionRows to empty array here!
            } 
            else {
                // Only proceed with database operations if no unknown regions
                if (!empty($dataRows)) {
                    $uploaded_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown user';
                    $uploaded_date = date('Y-m-d H:i:s');
                    $post_edi = 'pending';

                    $alreadyExistRows = [];
                    $successRows = [];

                    foreach ($dataRows as $row) {
                        $checkStmt = $conn->prepare("SELECT 1 FROM " . $database[0] . ".mlfund_payroll WHERE payroll_date = ? AND employee_id_no = ? AND mainzone = ?");
                        $checkStmt->bind_param("sss", $_POST['restricted-date'], $row['idno'], $_POST['mainzone']);
                        $checkStmt->execute();
                        $checkStmt->store_result();

                        if ($checkStmt->num_rows > 0) {
                            $alreadyExistRows[] = [
                                'idno' => $row['idno'],
                                'name' => $row['name'],
                                'fund' => $row['fund'],
                                'extension_file_type' => $fileExtension,
                                'region' => $row['region'],
                                'remarks' => 'already exist'
                            ];
                        } else {
                            $stmt = $conn->prepare("INSERT INTO " . $database[0] . ".mlfund_payroll
                                (payroll_date, mainzone, zone, region, region_code, employee_id_no, employee_name, ml_fund_amount, extension_file_type, uploaded_by, uploaded_date, post_edi) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param(
                                "sssssssdssss",
                                $_POST['restricted-date'],
                                $_POST['mainzone'],
                                $row['zone'],
                                $row['region'],
                                $row['region_code'],
                                $row['idno'],
                                $row['name'],
                                $row['fund'],
                                $fileExtension,
                                $uploaded_by,
                                $uploaded_date,
                                $post_edi
                            );
                            $stmt->execute();
                            $stmt->close();

                            $successRows[] = [
                                'idno' => $row['idno'],
                                'name' => $row['name'],
                                'fund' => $row['fund'],
                                'extension_file_type' => $fileExtension,
                                'region' => $row['region'],
                                'remarks' => 'imported'
                            ];
                        }
                        $checkStmt->close();
                    }

                    if (count($successRows) > 0) {
                        echo "<script>alert('Payroll data imported successfully! Duplicate entries were skipped.');</script>";
                    } else {
                        echo "<script>alert('No new payroll data imported. All entries already exist.');</script>";
                    }
                } else {
                    echo "<script>alert('No valid payroll data found in Excel file.');</script>";
                }
            }
        } else {
            echo "<script>alert('Please upload a PDF, XLSX, or XLS file.');</script>";
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
        /* .custom-arrow {
            position: absolute;
            top: 50%;
            right: 10px;
            width: 0;
            height: 0;
            padding: 0;
            margin-top: -2px;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #333;
            pointer-events: none;
        } */
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

        .table-container {
            top: 35px;
            position: relative;
            max-width: 100%;
            overflow-x: auto; /* Enable horizontal scrolling */
            overflow-y: auto; /* Enable vertical scrolling */
            max-height: calc(100vh - 200px); /* Adjust max-height as needed based on your layout */
            margin: 20px; /* Adjust margin as needed */
            border: 1px solid #ccc; /* Optional: Add border around the table container */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc; /* Border around the table */
            /* white-space: nowrap; */
            font-size: 20px;
        }

        th, td {
            border: 1px solid #ccc; /* Borders for table cells */
            padding: 5px; /* Padding inside cells */
            text-align: center; /* Center-align text in cells */
        }

        th {
            background-color: #f2f2f2; /* Light gray background for headers */
            font-weight: bold; /* Bold font for headers */
        }

        tr:nth-child(even) {
            background-color: #f9f9f9; /* Alternating row colors */
        }

        tr:hover {
            background-color: #e0e0e0;
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
        
        @media print {
            body * {
                visibility: hidden;
            }
            #printableTable, #printableTable * {
                visibility: visible;
            }
            #printableTable {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
        .unknown-region {
            background-color: #fff3cd !important;
            color: #856404;
        }
    </style>

</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>
    <center><h2>ML FUND<span>[IMPORT]</span></h2></center>
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
                        <input type="file" name="excelFile" accept=".pdf,.xls,.xlsx" class="form-control" required />
                        <input type="submit" class="upload-btn" name="upload" value="Upload">
                    </div>
                </div>
            </form>
            <div class="display_data">
                <div class="showEP" style="display: none">
                    <button type="submit" class="export-btn" onclick="exportToPDF()">Export to PDF</button>
                </div>
                <div class="showEP" style="display: none">
                    <button type="submit" class="print-btn" onclick="printTable()">
                        <i style="margin-right: 7px;" class="fa-solid fa-print"></i> Print
                    </button>
                </div>
                <div class="showEP" style="display: none">
                    <button type="submit" class="print-btn">
                        <i style="margin-right: 7px;" class="fa-solid fa fa-floppy-disk"></i> Save to Database
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php 
        // Ensure variables are always defined, even if the import is not run
        if (!isset($alreadyExistRows)) $alreadyExistRows = [];
        if (!isset($successRows)) $successRows = []; 
        if (!isset($unknownRegionRows)) $unknownRegionRows = [];

        // Display the results in a table format
        if (!empty($alreadyExistRows) || !empty($successRows) || !empty($unknownRegionRows)) {
    ?>

    <h3 class="display_data">Import Results</h3>
    <div class="table-container">
        <table border="1" cellpadding="5" style="border-collapse:collapse;" id="printableTable">
            <thead>
                <tr>
                    <th>IDNO</th>
                    <th>Name</th>
                    <th>Fund</th>
                    <th>Extension File Type</th>
                    <?php if (!empty($unknownRegionRows)) { ?><th>Sheet Name from Excel</th><?php }else{
                        echo '<th>Region</th>';
                    } ?>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alreadyExistRows as $row) {?>
                    <tr>
                        <td><?php echo $row['idno']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['fund']; ?></td>
                        <td><?php echo $row['extension_file_type']; ?></td>
                        <?php if (!empty($unknownRegionRows)) { ?><td>-</td><?php }else {
                            echo '<td>'.$row['region'].'</td>';
                        } ?>
                        <td><?php echo $row['remarks']; ?></td>
                    </tr>
                <?php } ?>
                <?php foreach ($successRows as $row) {?>
                    <tr>
                        <td><?php echo $row['idno']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['fund']; ?></td>
                        <td><?php echo $row['extension_file_type']; ?></td>
                        <?php if (!empty($unknownRegionRows)) { ?><td>-</td><?php }else {
                            echo '<td>'.$row['region'].'</td>';
                        } ?>
                        <td><?php echo $row['remarks']; ?></td>
                    </tr>
                <?php } ?>
                <?php foreach ($unknownRegionRows as $row) {?>
                    <tr class="unknown-region">
                        <td><?php echo $row['idno']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['fund']; ?></td>
                        <td><?php echo $fileExtension ?? 'N/A'; ?></td>
                        <td><?php echo $row['sheet_name']; ?></td>
                        <td><?php echo $row['remarks']; ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>

    <script src="<?php echo $relative_path; ?>assets/js/admin/import-file/script1.js"></script>
</body>
</html>