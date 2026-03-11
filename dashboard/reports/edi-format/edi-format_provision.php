<?php
    include '../../../config/connection.php';
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
                    // Handle CAD role - no access to this page
                    break;
                case 'ML FUND':
                    // Handle ML FUND role - no access to this page
                    break;
                case 'KP DOMESTIC':
                    // Handle KP DOMESTIC role - allow access to this page
                    $hasRequiredRole = true;
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

    
    require '../../../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        // Fetch all rows matching the filters (not just for ALL)
        $dlsql = "SELECT 
            p.zone, p.region, p.payroll_date, p.branch_code, p.branch_name, 
            p.basic_pay_regular, p.basic_pay_trainee, p.zone, p.cost_center,  
            p.ml_matic_region,
            COUNT(DISTINCT p.branch_code) AS branch_count, 
            SUM(p.basic_pay_regular) AS total_basic_pay_regular,
            SUM(p.basic_pay_trainee) AS total_basic_pay_trainee
        FROM 
            " . $database[0] . ".payroll_edi_report p
        WHERE p.payroll_date = '$restrictedDate'
        AND p.description = 'payroll'";
        if ($mainzone === 'ALL'){
            $dlsql .= " AND p.mainzone IN ('LNCR','VISMIN') ";
            if ($zone === 'ALL'){
                $dlsql .= " AND p.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN')";
            }
        }else{
            $dlsql .= " AND p.mainzone = '$mainzone' ";
            if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                $dlsql .= " AND p.ml_matic_region = '$zone' AND p.zone LIKE '%$region%' ";
            }else{
                $dlsql .= " AND p.zone = '$zone' AND p.region_code LIKE '%$region%' AND NOT ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
            }
        }
        $dlsql .= " AND (
                CASE 
                    WHEN p.ml_matic_status = 'Active' THEN 'Active'
                    WHEN p.ml_matic_status IN ('Pending', 'Inactive', 'TBO') THEN 'Inactive'
                END
            ) = '$status' 
                GROUP BY
                p.zone, p.region, p.payroll_date, p.branch_code, p.region, p.cost_center,
                p.branch_name, p.basic_pay_regular, p.basic_pay_trainee, p.ml_matic_region
            ORDER BY p.branch_name asc";

        $dlresult = mysqli_query($conn, $dlsql);

        if (!$dlresult) {
            die("Query failed: " . mysqli_error($conn));
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- Begin: Multi-zone Excel and ZIP logic ---
        if($mainzone === 'ALL' || $zone === 'ALL') {
            if(mysqli_num_rows($dlresult) > 0) {
                // Prepare arrays for each group
                $groups = [
                    'EDI_Provision_Report_LZN_' => [],
                    'EDI_Provision_Report_NCR_' => [],
                    'EDI_Provision_Report_VIS_' => [],
                    'EDI_Provision_Report_MIN_' => [],
                    'EDI_Provision_Report_NATIONWIDE-SHOWROOM_' => [],
                ];

                // Categorize each row
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    if (($row['zone'] === 'LZN') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Provision_Report_LZN_'][] = $row;
                    } elseif (($row['zone'] === 'NCR') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Provision_Report_NCR_'][] = $row;
                    } elseif (($row['zone'] === 'VIS') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Provision_Report_VIS_'][] = $row;
                    } elseif (($row['zone'] === 'MIN') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Provision_Report_MIN_'][] = $row;
                    } elseif ($row['ml_matic_region'] === 'LNCR Showroom' || $row['ml_matic_region'] === 'VISMIN Showroom') {
                        $groups['EDI_Provision_Report_NATIONWIDE-SHOWROOM_'][] = $row;
                    }
                }

                // Prepare temp dir for files
                $tmpDir = sys_get_temp_dir() . '/provision_report_temp';
                if (!is_dir($tmpDir)) {
                    mkdir($tmpDir);
                }

                // For each group, create spreadsheet and save
                $filePaths = [];
                foreach ($groups as $groupName => $rows) {
                    if (empty($rows)) continue; // Skip empty groups

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    // Header rows
                    $headerRow1 = [
                        'Provision for Bonus', '', '', '', '',
                    ];
                    $headerRow2 = [
                        'Code', 'Branch Name', 'mid', '13th', 'total',
                    ];
                    $sheet->fromArray($headerRow1, null, 'A1');
                    $sheet->fromArray($headerRow2, null, 'A2');
                    foreach (range('A', 'E') as $columnID) {
                        $sheet->getColumnDimension($columnID)->setAutoSize(true);
                    }
                    $sheet->getStyle('A1:E1')->getFont()->setBold(true);

                    $rowIndex = 3;
                    foreach ($rows as $row) {
                        $applyStyle = false;
                        if (strpos($row['cost_center'], '0001') === 0 && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                            $color = '4fc917';
                            $bold = true;
                            $applyStyle = true;
                        } else {
                            $bold = false;
                        }
                        $total = $row['basic_pay_regular'] + $row['basic_pay_trainee'];
                        $mid = $total * 2 / 9;
                        $thirteenth = $total * 2 / 12;
                        $overall = $mid + $thirteenth;

                        $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                        $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
                        $sheet->setCellValueExplicit('C' . $rowIndex, $mid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('D' . $rowIndex, $thirteenth, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('E' . $rowIndex, $overall, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                        if ($applyStyle) {
                            $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB($color);
                        }
                        $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFont()->setBold($bold);

                        $rowIndex++;
                    }

                    // Save file
                    $filename = $groupName . $restrictedDate. '.xls';
                    $filePath = $tmpDir . '/' . $filename;
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
                    $writer->save($filePath);
                    $filePaths[] = $filePath;
                }

                // ZIP the files with the desired filename
                $zipFilename = 'EDI_Provision_Report_' . $restrictedDate . '.zip';
                $zipPath = $tmpDir . '/' . $zipFilename;
                $zip = new ZipArchive();
                $zip->open($zipPath, ZipArchive::CREATE);
                foreach ($filePaths as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                // Output ZIP with correct filename
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);

                // Cleanup
                foreach ($filePaths as $file) unlink($file);
                unlink($zipPath);
                rmdir($tmpDir);
                exit();
            }
        }else{
            if (mysqli_num_rows($dlresult) > 0) {
                $headerRow1 = [
                    'Provision for Bonus', '', '', '', '',
                ];
                $headerRow2 = [
                    'Code', 'Branch Name', 'mid', '13th', 'total',
                ];
                
                $sheet->fromArray($headerRow1, null, 'A1');
                $sheet->fromArray($headerRow2, null, 'A2');

                foreach (range('A', 'E') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
        
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        
                $rowIndex = 3;
                $totalcount = 0;
        
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    $applyStyle = false; 
            
                    if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                        $color = '4fc917';  
                        $bold = true;       
                        $applyStyle = true; 
                    } else {
                        $bold = false;      
                    }

                    $total = $row['basic_pay_regular'] + $row['basic_pay_trainee'];
                    $mid = $total * 2 / 9;
                    $thirteenth = $total * 2 / 12;
                    $overall = $mid + $thirteenth;
        
                    $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                    $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);

                    // Use setCellValueExplicit for setting the value and format it as a number
                    $sheet->setCellValueExplicit('C' . $rowIndex, $mid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('D' . $rowIndex, $thirteenth, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('E' . $rowIndex, $overall, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    if ($applyStyle) {
                        $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                    }
                
                    // Apply bold style regardless of background color
                    $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFont()->setBold($bold);

                    $rowIndex++;
                    $totalcount++;
                }
        
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                if($status==='Active'){
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        $filename = "EDI_Provision_Report_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
                    }else{
                        $filename = "EDI_Provision_Report_" . $zone . "_" . $region . "_" . $restrictedDate . ".xls";
                    }
                    
                }else{
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        $filename = "EDI_Provision_Report_" . $mainzone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
                    }else{
                        $filename = "EDI_Provision_Report_" . $zone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
                    }
                }
                
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
        
                $writer = IOFactory::createWriter($spreadsheet, 'Xls');
                $writer->save('php://output');
                exit;
            }
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

        .import-file {
            height: 100px;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
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
            color: #F14A51;
        }
        .custom-arrow {
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
        input[type="month"] {
            width: 200px;
            padding: 10px;
            font-size: 14px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            margin-right: 20px;
        }
        .proceed-btn {
            background-color: #db120b; 
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
        }

        .download-btn {
            background-color: #4fc917; 
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
        }

        /* for table */
        .table-wrapper {
            position: relative;
            margin-top: 20px; 
        }

        .total-info {
            position: absolute;
            top: 0; 
            right: 5%; 
            text-align: left; 
            width: auto;
            font-size: 18px;
        }

        .table-container {
            /* max-width: calc(131vh - 168px); */
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(99vh - 200px);
            margin: 0px 40vh;
            border: 1px solid #ccc;
            /* margin-left: auto; */
            /* margin-right: auto; */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc; 
            white-space: nowrap;
            margin-left: auto; 
            margin-right: auto; 
        }

        th, td {
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: center; 
        }

        th {
            background-color: #f2f2f2; 
            font-weight: bold; 
        }

        tr:nth-child(even) {
            background-color: #f9f9f9; 
        }

        tr:hover {
            background-color: #e0e0e0;
        }
        .left {
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: left; 
        }
        .right {
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: right; 
        }
    </style>
</head>
<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>Provision Report <span>[EDI-Format]</span></h2></center>

    <div class="import-file">
        
        <form action="" method="post">

        <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <option value="VISMIN" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
                    <option value="ALL" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL') ? 'selected' : ''; ?>>ALL Mainzone</option>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="zone">Zone</label>
                <select name="zone" id="zone" autocomplete="off" required onchange="updateRegions()">
                    <option value="">Select Zone</option>
                    <!-- Zones will be populated dynamically by JavaScript -->
                    <?php
                        // If a zone is selected, display it after the page reloads
                        if (isset($_POST['zone'])) {
                            echo '<option value="' . htmlspecialchars($_POST['zone']) . '" selected>' . htmlspecialchars($_POST['zone']) . '</option>';
                        }
                    ?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="region">Region</label>
                <select name="region" id="region" autocomplete="off">
                    <option value="">Select Region</option>
                    <!-- Regions will be populated dynamically by JavaScript -->
                    <?php
                        // If a region is selected, display it after the page reloads
                        if (isset($_POST['region'])) {
                            echo '<option value="' . htmlspecialchars($_POST['region']) . '" selected>' . htmlspecialchars($_POST['region']) . '</option>';
                        }
                    ?>
                </select>
                <div class="custom-arrow"></div>
            </div>
			<div class="custom-select-wrapper">
                <label for="status">Status</label>
                <select name="status" id="status" autocomplete="off" required>
                    <option value="">Select Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">TBO, Pending & Inactive</option>?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="proceed-btn" name="proceed" value="Proceed">
        </form>

        <div id="showdl" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>

    </div>

    <script src="<?php echo $relative_path; ?>assets/js/admin/provision-report/edi-format/script.js"></script>
</body>
</html>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proceed'])) {

    $mainzone = $_POST['mainzone'];
    $zone = $_POST['zone'];
    $region = $_POST['region'];
	$status = $_POST['status'];
	$_SESSION['status'] = $status;
    $restrictedDate = $_POST['restricted-date']; 

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;


    $sql = "SELECT 
            p.zone, p.region, p.payroll_date, p.branch_code, p.branch_name, 
            p.basic_pay_regular, p.basic_pay_trainee, p.zone, p.cost_center, 
            p.ml_matic_status, 
            COUNT(DISTINCT p.branch_code) AS branch_count, 
            SUM(p.basic_pay_regular) AS total_basic_pay_regular,
            SUM(p.basic_pay_trainee) AS total_basic_pay_trainee
        FROM 
            " . $database[0] . ".payroll_edi_report p
        WHERE p.payroll_date = '$restrictedDate'
        AND p.description = 'payroll'";
        if ($mainzone === 'ALL'){
            // Adjust the SQL query based on the selected all mainzone
            $sql .= " AND p.mainzone IN ('LNCR','VISMIN') ";
            if ($zone === 'ALL'){
                // Adjust the SQL query based on the selected all zone
                $sql .= " AND p.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN')";
            }
        }else{
            // Adjust the SQL query based on the selected mainzone
            $sql .= " AND p.mainzone = '$mainzone' ";
            if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                // Adjust the SQL query based on the selected zone
                $sql .= " AND p.ml_matic_region = '$zone' AND p.zone LIKE '%$region%' ";
            }else{
                // Adjust the SQL query based on the selected zone
                $sql .= " AND p.zone = '$zone' AND p.region_code LIKE '%$region%' AND NOT ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
            }
        }
        $sql .= " AND (
                CASE 
                    WHEN p.ml_matic_status = 'Active' THEN 'Active'
                    WHEN p.ml_matic_status IN ('Pending', 'Inactive', 'TBO') THEN 'Inactive'
                END
            ) = '$status' 
                GROUP BY
                p.zone, p.region, p.payroll_date, p.branch_code, p.region, p.cost_center, p.ml_matic_status,
                p.branch_name, p.basic_pay_regular, p.basic_pay_trainee
            ORDER BY p.branch_name asc";

    // if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') { 
    //     $sql = "SELECT 
    //             p.zone, p.region, p.payroll_date, p.branch_code, p.branch_name, 
    //             p.basic_pay_regular, p.basic_pay_trainee, p.zone, p.cost_center,  
    //             COUNT(DISTINCT p.branch_code) AS branch_count, 
    //             SUM(p.basic_pay_regular) AS total_basic_pay_regular,
    //             SUM(p.basic_pay_trainee) AS total_basic_pay_trainee
    //         FROM 
    //             " . $database[0] . ".payroll_edi_report p
    //         WHERE 
    //             p.payroll_date = '$restrictedDate'
    //         AND p.mainzone = '$mainzone'
    //         AND NOT (p.branch_code = 18 AND p.zone = 'VIS')
    //         AND p.ml_matic_region = '$zone'
    //         AND p.zone like '%$region%'
	// 		AND (
	// 				CASE 
	// 					WHEN p.ml_matic_status = 'Active' THEN 'Active'
	// 					WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
	// 				END
	// 			) = '".$status."'
    //          GROUP BY
    //          GROUP BY    p.zone, p.region, p.payroll_date, p.branch_code, p.region, p.cost_center,
    //          GROUP BY    p.branch_name, p.basic_pay_regular, p.basic_pay_trainee
    //          GROUP BYORDER BY p.branch_name asc";
    // }else{
    //         $sql = "SELECT 
    //             p.zone, p.region, p.payroll_date, p.branch_code, p.branch_name, 
    //             p.basic_pay_regular, p.basic_pay_trainee, p.zone, p.cost_center,
    //             COUNT(DISTINCT p.branch_code) AS branch_count, 
    //             SUM(p.basic_pay_regular) AS total_basic_pay_regular,
    //             SUM(p.basic_pay_trainee) AS total_basic_pay_trainee
    //         FROM 
    //             " . $database[0] . ".payroll_edi_report p
    //         WHERE 
    //             p.region_code LIKE '%$region%' 
    //         AND p.payroll_date = '$restrictedDate'
    //         AND p.mainzone = '$mainzone'
    //         AND p.zone != 'JVIS' 
    //         AND p.zone = '$zone'
    //         AND p.ml_matic_region != 'LNCR Showroom'
    //         AND p.ml_matic_region != 'VISMIN Showroom'
	// 		AND (
	// 				CASE 
	// 					WHEN p.ml_matic_status = 'Active' THEN 'Active'
	// 					WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
	// 				END
	// 			) = '".$status."'
    //         GROUP BY
    //             p.zone, p.region, p.payroll_date, p.branch_code, p.region, p.cost_center,
    //             p.branch_name, p.basic_pay_regular, p.basic_pay_trainee
    //         ORDER BY p.branch_name asc"; 
    // } 

        //echo $sql; 
        // Get the result
        $result = mysqli_query($conn, $sql);

        echo "<div class='table-wrapper'>"; // Start wrapper div
        
        // Check if there are results
        if (mysqli_num_rows($result) > 0) {
            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            // first row
            echo "<tr>";
            echo "<th colspan='7'>Payroll Date : ".$restrictedDate."</th>";
            echo "</tr>";

            // second row
            echo "<tr>";
            echo "<th>BOS CODE</th>";
            echo "<th>Branch Name</th>";
            echo "<th>Mid - Year</th>";
            echo "<th>13th Month</th>";
            echo "<th>TOTAL</th>";
            echo "<th>Region</th>";
            echo "<th>Branch Status</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalthirteen = 0;
            $totalmid = 0;
            $totalcount = 0;

            // Output the data rows
            mysqli_data_seek($result, 0); // Reset result pointer to the beginning
            while ($row = mysqli_fetch_assoc($result)) {
                
                if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                    $color = '#4fc917';
                    $bold = 'bold';
                } else {
                    $color = 'none';
                    $bold = 'normal';
                }

                $total = $row['basic_pay_regular'] + $row['basic_pay_trainee'];
                $mid = $total * 2 / 9;
                $thirteenth = $total * 2 / 12;
                $overall = $mid + $thirteenth;
                
                if ($row['ml_matic_status'] === 'TBO') {
                    $statusText = 'TO BE OPEN';
                } else {
                    $statusText = $row['ml_matic_status'];
                }
                
                echo "<tr>";
                echo "<td style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_code']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold; text-align: right'>" . number_format($mid, 2) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold; text-align: right'>" . number_format($thirteenth, 2) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold; text-align: right'>" . number_format($overall, 2) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td class='center' style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($statusText) . "</td>";
                echo "</tr>";

                $totalmid += $mid;
                $totalthirteen += $thirteenth;
                $totalcount++;
                
            }

            if (empty($region)) {
                $region = "All Region";
            }

            echo "</tbody>";
            echo "</table>";

            echo "<div class='total-info'>";
            echo "<p><b>Main Zone: </b>$mainzone</p>";
            echo "<p><b>Zone: </b>$zone</p>";
            echo "<p><b>Region: </b>$region</p>";
            echo "<p><b>Payroll Date: </b>".date('F j, Y', strtotime($restrictedDate))."</p>";
            echo "<p><b>Number of Branches: </b>$totalcount</p>";
            echo "<p><b>Total MID - Year: </b>" . number_format($totalmid, 2) . "</p>";
            echo "<p><b>Total 13TH Month: </b>" . number_format($totalthirteen, 2) . "</p>";
            echo "</div>";

            echo "</div>
            
            <script>
                var dlbtn = document.getElementById('showdl');
                dlbtn.style.display = 'block';  
            </script>";

        } else {
            echo "No results found.";
        }
    }
    echo "</div>"; // End wrapper div

?>