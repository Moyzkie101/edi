<?php
    include '../../../config/connection.php';
    require '../../../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
                    // Handle ML FUND role - allow access to this page
                    $hasRequiredRole = true;
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

    

    if (isset($_POST['download'])) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
		
		$payrollDay = $_SESSION['payroll_day'] ?? '';
        $payrollMonth = $_SESSION['payroll_month'] ?? '';
        $payrollYear = $_SESSION['payroll_year'] ?? '';

        $grand_total_number_per_employees = $_SESSION['grand_total_number_per_employees'] ?? 0;
        $grand_total_amount_per_region = $_SESSION['grand_total_amount_per_region'] ?? 0;

        $dlsql = "SELECT * FROM " . $database[0] . ".mlfund_payroll
                    WHERE mainzone = '$mainzone'
                    AND payroll_date = '$restrictedDate'";

            if ($zone === 'VISMIN-SUPPORT') {
                    $dlsql .= " AND zone = 'VISMIN-SUPPORT'";
            }elseif ($zone === 'LNCR-SUPPORT') {
                $dlsql .= " AND zone = 'LNCR-SUPPORT'";

            }elseif ($zone === 'VISMIN-MANCOMM') {
                $dlsql .= " AND zone = 'VISMIN-MANCOMM'";

            }elseif ($zone === 'LNCR-MANCOMM') {
                $dlsql .= " AND zone = 'LNCR-MANCOMM'";
            }else{
                if(!empty($region)) {
                    $dlsql .= " AND zone = '$zone' AND region_code = '$region'";
                }else {
                    $dlsql .= " AND zone = '$zone'";
                }
            } 

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Fetch the first row to get header data
        $first_row = mysqli_fetch_assoc($dlresult);

        // Set the first header row
        $sheet->setCellValue('A1', 'ML Fund Deduction Report');

        // Set the second header row
        $sheet->setCellValue('A2', 'Date : ' . $payrollMonth . ' ' . $payrollDay . ', ' . $payrollYear);
        
        // Set the fourth header row
        $sheet->setCellValue('A4', 'Mainzone : ' . $mainzone)->mergeCells('A4:C4')->getStyle('A4:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('D4', 'Amount')->mergeCells('D4:D5')->getStyle('D4:D5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        if ($zone === 'VISMIN-SUPPORT' || $zone === 'LNCR-SUPPORT' || $zone === 'VISMIN-MANCOMM' || $zone === 'LNCR-MANCOMM') {
            $sheet->setCellValue('E4', 'Zone')->mergeCells('E4:E5')->getStyle('E4:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        } else {
            $sheet->setCellValue('E4', 'Region')->mergeCells('E4:E5')->getStyle('E4:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('F4', 'Zone')->mergeCells('F4:F5')->getStyle('F4:F5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }

        $sheet->setCellValue('A5', 'No.');
        $sheet->setCellValue('B5', 'Employee ID');    
        $sheet->setCellValue('C5', 'Employee Name'); 
        
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);
        $sheet->getStyle('A4:F5')->getFont()->setBold(true);

        for ($col = 'B'; $col <= 'F'; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        
        
        $rowNumber = 6; // Start from the 6th row for data
        mysqli_data_seek($dlresult, 0); // Reset the result pointer to the beginning
        while ($row = mysqli_fetch_assoc($dlresult)) {
            $sheet->setCellValue('A' . $rowNumber, $rowNumber - 5); // No.
            $sheet->setCellValue('B' . $rowNumber, $row['employee_id_no']);
            $sheet->setCellValue('C' . $rowNumber, $row['employee_name']); // Name
            $sheet->setCellValue('D' . $rowNumber, $row['ml_fund_amount']); // ML Fund Amount
            if ($zone === 'VISMIN-SUPPORT' || $zone === 'LNCR-SUPPORT' || $zone === 'VISMIN-MANCOMM' || $zone === 'LNCR-MANCOMM') {
                $sheet->setCellValue('E' . $rowNumber, $row['zone']); // Zone
            } else {
                $sheet->setCellValue('E' . $rowNumber, $row['region']); // Region
                $sheet->setCellValue('F' . $rowNumber, $row['zone']); // Zone
            }

            // Format the cell to show 2 decimal places
            $sheet->getStyle('D' . $rowNumber)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('B' . $rowNumber)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Employee ID alignment
            $sheet->getStyle("A4:" . ($zone === 'VISMIN-SUPPORT' || $zone === 'LNCR-SUPPORT' || $zone === 'VISMIN-MANCOMM' || $zone === 'LNCR-MANCOMM' ? "E{$rowNumber}" : "F{$rowNumber}") . "")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $rowNumber++;
        }
        // Set the grand total row
        $sheet->setCellValue('C'. ($rowNumber+1),'GRAND TOTAL : ')->getStyle('C'.($rowNumber+1)); // Grand total for ML Fund Amount
        $sheet->setCellValue('D'. ($rowNumber+1), $grand_total_amount_per_region); // Grand total amount
        $sheet->getStyle('D' . ($rowNumber+1))->getNumberFormat()->setFormatCode('#,##0.00'); // Format the grand total cell
        $sheet->getStyle("C". ($rowNumber+1).":D".($rowNumber+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("C". ($rowNumber+1).":D".($rowNumber+1))->getFont()->setBold(true);

        // Set the filename and headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        if ($zone === 'VISMIN-SUPPORT') {
            $filename = 'ML_Fund_Report_' . $mainzone . '_VISMIN-SUPPORT_' . $restrictedDate . '.xls';
        }elseif ($zone === 'LNCR-SUPPORT'){
            $filename = 'ML_Fund_Report_' . $mainzone . '_LNCR-SUPPORT_' . $restrictedDate . '.xls';
        }elseif ($zone === 'VISMIN-MANCOMM'){
            $filename = 'ML_Fund_Report_' . $mainzone . '_VISMIN-MANCOMM_' . $restrictedDate . '.xls';
        }elseif ($zone === 'LNCR-MANCOMM'){
            $filename = 'ML_Fund_Report_' . $mainzone . '_LNCR-MANCOMM_' . $restrictedDate . '.xls';
        }else{
            if (!empty($region)) {
                $filename = 'ML_Fund_Report_' . $mainzone . '_' . $zone . '_' . $region . '_' . $restrictedDate . '.xls';
            }else{
                $filename = 'ML_Fund_Report_' . $mainzone . '_' . $zone . '_' . $restrictedDate . '.xls';
            }
        }

        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
    
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        exit;

    }

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .print-btn {
            background-color: #d70c0c;
            border: none;
            color: white;
            padding: 13px 20px;
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
            font-size: 15px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            margin-right: 20px;
            color: #F14A51;
        }
        .generate-btn {
            background-color: #db120b; 
            border: none;
            color: white;
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .generate-btn:hover{
            background-color:rgb(180, 31, 31);
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
        .post-btn {
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
            font-size: 15px;
            color: #000000;
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
    </style>
</head>

<body>
    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>
    <center>
        <h2>ML FUND REPORT<span>[DEDUCTION]</span></h2>
    </center>

    <div class="import-file">

        <form action="" method="post">

            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <option value="VISMIN" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
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
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : ''; ?>" required>
            </div>

            <input type="submit" class="generate-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>

    </div>
    <?php 
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
            $mainzone = $_POST['mainzone'];
            $zone = $_POST['zone'];
            $region = $_POST['region'];
            $restrictedDate = $_POST['restricted-date'];

            $_SESSION['mainzone'] = $mainzone;
            $_SESSION['zone'] = $zone;
            $_SESSION['region'] = $region;
            $_SESSION['restrictedDate'] = $restrictedDate;
			
			// Determine payroll day, month and year
			if (!empty($_POST['restricted-date'])) {
				$payrollDay = date('j', strtotime($_POST['restricted-date']));
				$_SESSION['payroll_day'] = $payrollDay;
			}
			if (!empty($_POST['restricted-date'])) {
				$payrollMonth = date('F', strtotime($_POST['restricted-date']));
				$_SESSION['payroll_month'] = $payrollMonth;
			}
			if (!empty($_POST['restricted-date'])) {
				$payrollYear = date('Y', strtotime($_POST['restricted-date']));
				$_SESSION['payroll_year'] = $payrollYear;
			}

            $sql =" SELECT * FROM " . $database[0] . ".mlfund_payroll
                    WHERE mainzone = '$mainzone'
                    AND payroll_date = '$restrictedDate'";

            if ($zone === 'VISMIN-SUPPORT') {
                    $sql .= " AND zone = 'VISMIN-SUPPORT'";
            }elseif ($zone === 'LNCR-SUPPORT') {
                $sql .= " AND zone = 'LNCR-SUPPORT'";

            }elseif ($zone === 'VISMIN-MANCOMM') {
                $sql .= " AND zone = 'VISMIN-MANCOMM'";

            }elseif ($zone === 'LNCR-MANCOMM') {
                $sql .= " AND zone = 'LNCR-MANCOMM'";
            }else{
                if(!empty($region)) {
                    $sql .= " AND zone = '$zone' AND region_code = '$region'";
                }else {
                    $sql .= " AND zone = '$zone'";
                }
            }
            $result = mysqli_query($conn, $sql);
    ?>
    <div class="table-container">
        <div class="display_data">
            <div class="showEP" style="display: none">
                <button type="submit" class="export-btn" onclick="exportToPDF()">
                    Export to PDF
                </button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <?php
                    echo '<th'; if($zone === 'VISMIN-SUPPORT' || $zone === 'LNCR-SUPPORT' || $zone === 'VISMIN-MANCOMM' || $zone === 'LNCR-MANCOMM'){
                        echo ' colspan="5" ';
                    }else{
                        echo ' colspan="6" ';
                    } echo '>(' . $mainzone . ')</th>';
                    ?>
                </tr>
                <tr>
                    <th colspan="3">DATE : <?php echo $payrollMonth . " " . $payrollDay . ", " . $payrollYear; ?></th>
                    <th rowspan="2">Amount</th>
                    <?php if ($zone === 'VISMIN-SUPPORT' || $zone === 'LNCR-SUPPORT' || $zone === 'VISMIN-MANCOMM' || $zone === 'LNCR-MANCOMM') {
                        echo '<th rowspan="2">Zone</th>';
                    } else { 
                        echo '<th rowspan="2">Region</th>
                        <th rowspan="2">Zone</th>'; 
                    }?>
                </tr>
                <tr>
                    <th>No.</th>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    $count = 1;
                    $grand_total_amount_per_region = 0; // Initialize
                    $grand_total_number_per_employees = 0; // Initialize

                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $count . "</td>";
                        echo "<td>" . htmlspecialchars($row['employee_id_no']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['employee_name']) . "</td>";
                        echo "<td style='text-align: right'>" . htmlspecialchars(number_format($row['ml_fund_amount'], 2)) . "</td>";
                        if ($zone === 'VISMIN-SUPPORT' || $zone === 'LNCR-SUPPORT' || $zone === 'VISMIN-MANCOMM' || $zone === 'LNCR-MANCOMM') {
                            echo "<td>" . htmlspecialchars($row['zone']) . "</td>";
                        } else {
                            echo "<td>" . htmlspecialchars($row['region']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['zone']) . "</td>";
                        }
                        echo "</tr>";

                        // Accumulate totals inside the loop
                        $grand_total_amount_per_region += (float)$row['ml_fund_amount'];
                        $grand_total_number_per_employees++;
                        $count++;
                    }

                    $_SESSION['grand_total_amount_per_region'] = $grand_total_amount_per_region;
                    $_SESSION['grand_total_number_per_employees'] = $grand_total_number_per_employees;
                    echo"<script>
                            var dlbtn = document.getElementById('showdl');
                            dlbtn.style.display = 'block';  
                        </script>";
                } else {
                    echo "<tr><td colspan='6'>No records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>

    </div>
    <?php } // <-- Close the PHP if block ?>
    <script src="<?php echo $relative_path; ?>assets/js/admin/report-file/deduction-format/script1.js"></script>
</body>

</html>