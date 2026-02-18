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
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
	use PhpOffice\PhpSpreadsheet\Shared\Date;

    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
		$endDate = $_SESSION['endDate'] ?? '';

        generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate);

    }
 
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
		$endDate = $_SESSION['endDate'] ?? '';

        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $dlsql = "SELECT 
                    p.branch_code,
                    p.cost_center,
                    max(p.ml_matic_region) as region,
					max(p.mlmatic_zone) as zone,
                    p.payroll_date,
					
                    MAX(p.gl_code_basic_pay_regular) AS gl_code_basic_pay_regular,
                    MAX(p.gl_code_basic_pay_trainee) AS gl_code_basic_pay_trainee,
                    MAX(p.gl_code_allowances) AS gl_code_allowances,
                    MAX(p.gl_code_bm_allowance) AS gl_code_bm_allowance,
                    MAX(p.gl_code_overtime_regular) AS gl_code_overtime_regular,
                    MAX(p.gl_code_overtime_trainee) AS gl_code_overtime_trainee,
                    MAX(p.gl_code_cola) AS gl_code_cola,
                    MAX(p.gl_code_excess_pb) AS gl_code_excess_pb,
                    MAX(p.gl_code_other_income) AS gl_code_other_income,
                    MAX(p.gl_code_salary_adjustment) AS gl_code_salary_adjustment,
                    MAX(p.gl_code_graveyard) AS gl_code_graveyard,
                    MAX(p.gl_code_late_regular) AS gl_code_late_regular,
                    MAX(p.gl_code_late_trainee) AS gl_code_late_trainee,
                    MAX(p.gl_code_leave_regular) AS gl_code_leave_regular,
                    MAX(p.gl_code_leave_trainee) AS gl_code_leave_trainee,
                    MAX(p.gl_code_all_other_deductions) AS gl_code_all_other_deductions,
                    MAX(p.gl_code_total) AS gl_code_total,
					
                    MAX(p.branch_name) AS branch_name_hr,
					MAX(p.mlmatic_branch_name) AS branch_name_mlmatic,
					
                    MAX(p.basic_pay_regular) AS basic_pay_regular,
                    MAX(p.basic_pay_trainee) AS basic_pay_trainee,
                    MAX(p.allowances) AS allowances,
                    MAX(p.bm_allowance) AS bm_allowance,
                    MAX(p.overtime_regular) AS overtime_regular,
                    MAX(p.overtime_trainee) AS overtime_trainee,
                    MAX(p.cola) AS cola,
                    MAX(p.excess_pb) AS excess_pb,
                    MAX(p.other_income) AS other_income,
                    MAX(p.salary_adjustment) AS salary_adjustment,
                    MAX(p.graveyard) AS graveyard,
                    MAX(p.late_regular) AS late_regular,
                    MAX(p.late_trainee) AS late_trainee,
                    MAX(p.leave_regular) AS leave_regular,
                    MAX(p.leave_trainee) AS leave_trainee,
                    MAX(p.all_other_deductions) AS all_other_deductions,
                    MAX(p.total) AS total,
                    MAX(p.no_of_branch_employee) AS no_of_branch_employee,
                    MAX(p.no_of_employees_allocated) AS no_of_employees_allocated,
                    COUNT(DISTINCT p.branch_code) AS branch_count 
                FROM 
                    " . $database[0] . ".payroll_edi_report p 
                LEFT JOIN " . $database[0] . ".mlmatic_profile as mp
                ON mp.code = p.branch_code
                AND mp.kpcode=p.kp_code
                AND mp.uploaded_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                WHERE 
                    p.mainzone = '$mainzone'
                    AND p.description = 'Sick-Leave'";
					if($restrictedDate === $endDate){
                            $dlsql .= "AND p.payroll_date = '$restrictedDate'";
                        }else{
                            $dlsql .= "AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                        }
					$dlsql .= "AND p.ml_matic_region = '$zone'
                    AND p.zone LIKE '%$region%'
                    AND NOT (p.branch_code = 18 AND p.zone = 'VIS') -- to exclude Duljo branch
                    AND p.description = 'Sick-Leave'
					AND (
                            CASE 
                                WHEN p.ml_matic_status = 'Active' THEN 'Active'
                                WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                            END
                        ) = '".$status."'
                GROUP BY 
                    p.branch_code, 
                    p.cost_center, 
                    p.region, 
                    p.zone, 
                    p.payroll_date
                ORDER BY 
                    p.region;";
            }else{
                $dlsql = "SELECT 
                    p.branch_code,
                    p.cost_center,
                    p.region,
                    p.zone,
                    p.payroll_date,
                    MAX(p.gl_code_basic_pay_regular) AS gl_code_basic_pay_regular,
                    MAX(p.gl_code_basic_pay_trainee) AS gl_code_basic_pay_trainee,
                    MAX(p.gl_code_allowances) AS gl_code_allowances,
                    MAX(p.gl_code_bm_allowance) AS gl_code_bm_allowance,
                    MAX(p.gl_code_overtime_regular) AS gl_code_overtime_regular,
                    MAX(p.gl_code_overtime_trainee) AS gl_code_overtime_trainee,
                    MAX(p.gl_code_cola) AS gl_code_cola,
                    MAX(p.gl_code_excess_pb) AS gl_code_excess_pb,
                    MAX(p.gl_code_other_income) AS gl_code_other_income,
                    MAX(p.gl_code_salary_adjustment) AS gl_code_salary_adjustment,
                    MAX(p.gl_code_graveyard) AS gl_code_graveyard,
                    MAX(p.gl_code_late_regular) AS gl_code_late_regular,
                    MAX(p.gl_code_late_trainee) AS gl_code_late_trainee,
                    MAX(p.gl_code_leave_regular) AS gl_code_leave_regular,
                    MAX(p.gl_code_leave_trainee) AS gl_code_leave_trainee,
                    MAX(p.gl_code_all_other_deductions) AS gl_code_all_other_deductions,
                    MAX(p.gl_code_total) AS gl_code_total,
					
                    MAX(p.branch_name) AS branch_name_hr,
					MAX(p.mlmatic_branch_name) AS branch_name_mlmatic,
					
                    MAX(p.basic_pay_regular) AS basic_pay_regular,
                    MAX(p.basic_pay_trainee) AS basic_pay_trainee,
                    MAX(p.allowances) AS allowances,
                    MAX(p.bm_allowance) AS bm_allowance,
                    MAX(p.overtime_regular) AS overtime_regular,
                    MAX(p.overtime_trainee) AS overtime_trainee,
                    MAX(p.cola) AS cola,
                    MAX(p.excess_pb) AS excess_pb,
                    MAX(p.other_income) AS other_income,
                    MAX(p.salary_adjustment) AS salary_adjustment,
                    MAX(p.graveyard) AS graveyard,
                    MAX(p.late_regular) AS late_regular,
                    MAX(p.late_trainee) AS late_trainee,
                    MAX(p.leave_regular) AS leave_regular,
                    MAX(p.leave_trainee) AS leave_trainee,
                    MAX(p.all_other_deductions) AS all_other_deductions,
                    MAX(p.total) AS total,
                    MAX(p.no_of_branch_employee) AS no_of_branch_employee,
                    MAX(p.no_of_employees_allocated) AS no_of_employees_allocated,
                    COUNT(DISTINCT p.branch_code) AS branch_count 
                FROM 
                    " . $database[0] . ".payroll_edi_report p
                LEFT JOIN " . $database[0] . ".mlmatic_profile as mp
                ON mp.code = p.branch_code
                AND mp.kpcode=p.kp_code
                AND mp.uploaded_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                WHERE
                    p.mainzone = '$mainzone'
                    AND p.zone = '$zone'
                    AND p.zone != 'JVIS' -- to exclude SM Seaside Showroom
                    AND p.region_code LIKE '%$region%'
                    AND p.description = 'Sick-Leave'";
					if($restrictedDate === $endDate){
                            $dlsql .= "AND p.payroll_date = '$restrictedDate'";
                        }else{
                            $dlsql .= "AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                        }
					$dlsql .= "AND p.ml_matic_region != 'LNCR Showroom'
                    AND p.ml_matic_region != 'VISMIN Showroom'
                    AND p.description = 'Sick-Leave'
					AND (
                            CASE 
                                WHEN p.ml_matic_status = 'Active' THEN 'Active'
                                WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                            END
                        ) = '".$status."'
                GROUP BY 
                    p.branch_code, 
                    p.cost_center, 
                    p.region, 
                    p.zone, 
                    p.payroll_date
                ORDER BY 
                    p.region;"; 
            } 
                    
            $dlresult = mysqli_query($conn, $dlsql);
			
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
                    
            if(mysqli_num_rows($dlresult) > 0) {
                
                $first_row = mysqli_fetch_assoc($dlresult);
                $payroll_date = htmlspecialchars($first_row['payroll_date']);
				$gl_code_excess_pb = htmlspecialchars($first_row['gl_code_excess_pb']);
				$gl_code_total = htmlspecialchars($first_row['gl_code_total']);
                                
                // Reset the result pointer to the beginning
                mysqli_data_seek($dlresult, 0);
                    
                $headerRow1 = [
                    'Date', 'GL Code', 'Zone', 'Region', 'Branch Name','Description', 'Amount', 'Category'
                ];
                    
                $sheet->fromArray([$headerRow1], null, 'A1');
                        
                foreach(range('A', 'Z') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
                                
                $rowIndex = 3;
				
				                    
                while ($row = mysqli_fetch_assoc($dlresult)) {
					$zone = $row['zone'];
					$Month = date('Y', strtotime($row['payroll_date']));
					$dateValue = new \DateTime($row['payroll_date']);
					$description = ucwords("Sick Leave Conversion Year $Month");

					// Prepare GL Codes array - adjust field names if different
					$glCodes = [$row['gl_code_excess_pb'], $row['gl_code_total']];

					foreach ($glCodes as $glCode) {
						// Determine style
						$applyStyle = false;
						if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
							//$color = '4fc917';
							//$bold = true;
							$applyStyle = true;
						} else {
							$bold = false;
						}

						// Fill in the Excel sheet
						$sheet->setCellValue('A' . $rowIndex, Date::PHPToExcel($dateValue));
						$sheet->getStyle('A' . $rowIndex)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
						$sheet->setCellValue('B' . $rowIndex, $glCode);
						$sheet->setCellValue('C' . $rowIndex, $zone);
						$sheet->setCellValue('D' . $rowIndex, $row['region']);
						$sheet->setCellValue('E' . $rowIndex, $row['branch_name_mlmatic']);
						$sheet->setCellValue('F' . $rowIndex, $description);
						$sheet->setCellValueExplicit('G' . $rowIndex, $row['excess_pb'], DataType::TYPE_NUMERIC);
						$sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
						$sheet->setCellValue('H' . $rowIndex, 'Adjustment');

						// Apply styling
						/*if ($applyStyle) {
							$sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFill()->setFillType(Fill::FILL_SOLID)
								->getStartColor()->setARGB($color);
						}
						$sheet->getStyle('A' . $rowIndex . ':I' . $rowIndex)->getFont()->setBold($bold);*/

						$rowIndex++;
					}
				}

				
                            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			
			if($status==='Active'){
				if($zone === 'JEW'){
					$filename = "EDI_Sick-Leave_conversion_Report_" . $mainzone . "_" . $restrictedDate . ".xls";
				}else{
					$filename = "EDI_Sick-Leave_conversion_Report_" . $zone . "_" . $region . "_" . $restrictedDate . ".xls";
				}
			}else{
				if($zone === 'JEW'){
					$filename = "EDI_Sick-Leave_conversion_Report_" . $mainzone . "_" . $restrictedDate . "(Inactive or Pending).xls";
				}else{
					$filename = "EDI_Sick-Leave_conversion_Report_" . $zone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
				}
			}
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
                        
            $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
            exit();        
            // csv format
            // header('Content-Type: text/csv');
            // $filename = "EDI_Payroll_Report_" . $zone . "_" . $region . "_" . $restrictedDate . ".csv";
            // header('Content-Disposition: attachment; filename="' . $filename . '"');
            // header('Cache-Control: max-age=0');
                    
            // $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Csv');
            // $writer->save('php://output');
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
            font-size: 12px;
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

    <center><h2>Sick Leave Conversion Report <span>[EDI-Format]</span></h2></center>

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

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
                <label for="status">Status</label>
                <select name="status" id="status" autocomplete="off" required>
                    <option value="">Select Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Pending & Inactive</option>?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
			
			<div class="custom-select-wrapper">
                <label for="end-date">End date </label>
                <input type="date" id="end-date" name="end-date" value="<?php echo isset($_POST['end-date']) ? $_POST['end-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>

		 <div id="showdl" style="display: none">
            <form id="exportForm" action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
            </form>
        </div>
    </div>

    <script>
        //for fetching zone
        function updateZone() {
            var mainzone = document.getElementById("mainzone").value;
            var selectedZone = document.getElementById("zone").value; // Get the currently selected zone, if any
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../../../fetch/get_zone.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("zone").innerHTML = xhr.responseText;
                }
            };
            // Pass the current zone as well to preserve the selection
            xhr.send("mainzone=" + mainzone + "&selected_zone=" + selectedZone);
        }

        // Ensure the zones are updated automatically on page load based on the current mainzone
        window.onload = function() {
            var mainzone = document.getElementById("mainzone").value;
            if (mainzone !== "") {
                updateZone(); // Fetch and set the zones automatically if a mainzone is already selected
            }
        };
        
        // Function to fetch regions based on the selected zone
        function updateRegions() {
            var zone = document.getElementById("zone").value;
            var selectedRegion = document.getElementById("region").value; // Get the currently selected region, if any

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../../../fetch/get_regions.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("region").innerHTML = xhr.responseText;
                }
            };
            // Pass the current region as well to preserve the selection
            xhr.send("zone=" + zone + "&selected_region=" + selectedRegion);
        }

        // Ensure the regions are updated automatically when a zone is selected or when the page reloads
        document.getElementById("zone").addEventListener('change', updateRegions);

        window.onload = function() {
            var zone = document.getElementById("zone").value;
            if (zone !== "") {
                updateRegions(); // Fetch and set the regions automatically if a zone is already selected
            }
        };
    </script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $zone = $_POST['zone'];
	$status = $_POST['status'];
    $restrictedDate = $_POST['restricted-date'];
	$endDate = $_POST['end-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
	$_SESSION['status'] = $status;
    $_SESSION['restrictedDate'] = $restrictedDate;
	$_SESSION['endDate'] = $endDate;

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
        $sql = "SELECT 
                    p.branch_code,
                    p.cost_center,
                    max(p.ml_matic_region) as region,
					max(p.mlmatic_zone) as zone,
                    p.payroll_date,
                    MAX(p.gl_code_basic_pay_regular) AS gl_code_basic_pay_regular,
                    MAX(p.gl_code_basic_pay_trainee) AS gl_code_basic_pay_trainee,
                    MAX(p.gl_code_allowances) AS gl_code_allowances,
                    MAX(p.gl_code_bm_allowance) AS gl_code_bm_allowance,
                    MAX(p.gl_code_overtime_regular) AS gl_code_overtime_regular,
                    MAX(p.gl_code_overtime_trainee) AS gl_code_overtime_trainee,
                    MAX(p.gl_code_cola) AS gl_code_cola,
                    MAX(p.gl_code_excess_pb) AS gl_code_excess_pb,
                    MAX(p.gl_code_other_income) AS gl_code_other_income,
                    MAX(p.gl_code_salary_adjustment) AS gl_code_salary_adjustment,
                    MAX(p.gl_code_graveyard) AS gl_code_graveyard,
                    MAX(p.gl_code_late_regular) AS gl_code_late_regular,
                    MAX(p.gl_code_late_trainee) AS gl_code_late_trainee,
                    MAX(p.gl_code_leave_regular) AS gl_code_leave_regular,
                    MAX(p.gl_code_leave_trainee) AS gl_code_leave_trainee,
                    MAX(p.gl_code_all_other_deductions) AS gl_code_all_other_deductions,
                    MAX(p.gl_code_total) AS gl_code_total,
                    MAX(p.branch_name) AS branch_name_hr,
					MAX(p.mlmatic_branch_name) AS branch_name_mlmatic,
                    MAX(p.basic_pay_regular) AS basic_pay_regular,
                    MAX(p.basic_pay_trainee) AS basic_pay_trainee,
                    MAX(p.allowances) AS allowances,
                    MAX(p.bm_allowance) AS bm_allowance,
                    MAX(p.overtime_regular) AS overtime_regular,
                    MAX(p.overtime_trainee) AS overtime_trainee,
                    MAX(p.cola) AS cola,
                    MAX(p.excess_pb) AS excess_pb,
                    MAX(p.other_income) AS other_income,
                    MAX(p.salary_adjustment) AS salary_adjustment,
                    MAX(p.graveyard) AS graveyard,
                    MAX(p.late_regular) AS late_regular,
                    MAX(p.late_trainee) AS late_trainee,
                    MAX(p.leave_regular) AS leave_regular,
                    MAX(p.leave_trainee) AS leave_trainee,
                    MAX(p.all_other_deductions) AS all_other_deductions,
                    MAX(p.total) AS total,
                    MAX(p.no_of_branch_employee) AS no_of_branch_employee,
                    MAX(p.no_of_employees_allocated) AS no_of_employees_allocated,
                    COUNT(DISTINCT p.branch_code) AS branch_count 
                FROM 
                    " . $database[0] . ".payroll_edi_report p 
                WHERE 
                    p.mainzone = '$mainzone'
                    AND p.description = 'Sick-Leave'"; 
                    if($restrictedDate === $endDate){
                        $sql .="AND p.payroll_date = '$restrictedDate'";
                    }else{
                        $sql .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                    }
                    $sql .="AND p.ml_matic_region = '$zone'
                    AND p.zone LIKE '%$region%'
                    AND NOT (p.branch_code = 18 AND p.zone = 'VIS') 
                    AND p.description = 'Sick-Leave'
					AND (
							CASE 
								WHEN p.ml_matic_status = 'Active' THEN 'Active'
								WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
							END
						) = '".$status."'
                GROUP BY 
                    p.branch_code, 
                    p.cost_center, 
                    p.region, 
                    p.zone, 
                    p.payroll_date
                ORDER BY 
                    p.region;";
    }else{
                $sql = "SELECT 
                    p.branch_code,
                    p.cost_center,
                    p.region,
                    p.zone,
                    p.payroll_date,
                    MAX(p.gl_code_basic_pay_regular) AS gl_code_basic_pay_regular,
                    MAX(p.gl_code_basic_pay_trainee) AS gl_code_basic_pay_trainee,
                    MAX(p.gl_code_allowances) AS gl_code_allowances,
                    MAX(p.gl_code_bm_allowance) AS gl_code_bm_allowance,
                    MAX(p.gl_code_overtime_regular) AS gl_code_overtime_regular,
                    MAX(p.gl_code_overtime_trainee) AS gl_code_overtime_trainee,
                    MAX(p.gl_code_cola) AS gl_code_cola,
                    MAX(p.gl_code_excess_pb) AS gl_code_excess_pb,
                    MAX(p.gl_code_other_income) AS gl_code_other_income,
                    MAX(p.gl_code_salary_adjustment) AS gl_code_salary_adjustment,
                    MAX(p.gl_code_graveyard) AS gl_code_graveyard,
                    MAX(p.gl_code_late_regular) AS gl_code_late_regular,
                    MAX(p.gl_code_late_trainee) AS gl_code_late_trainee,
                    MAX(p.gl_code_leave_regular) AS gl_code_leave_regular,
                    MAX(p.gl_code_leave_trainee) AS gl_code_leave_trainee,
                    MAX(p.gl_code_all_other_deductions) AS gl_code_all_other_deductions,
                    MAX(p.gl_code_total) AS gl_code_total,
                    MAX(p.branch_name) AS branch_name_hr,
					MAX(p.mlmatic_branch_name) AS branch_name_mlmatic,
                    MAX(p.basic_pay_regular) AS basic_pay_regular,
                    MAX(p.basic_pay_trainee) AS basic_pay_trainee,
                    MAX(p.allowances) AS allowances,
                    MAX(p.bm_allowance) AS bm_allowance,
                    MAX(p.overtime_regular) AS overtime_regular,
                    MAX(p.overtime_trainee) AS overtime_trainee,
                    MAX(p.cola) AS cola,
                    MAX(p.excess_pb) AS excess_pb,
                    MAX(p.other_income) AS other_income,
                    MAX(p.salary_adjustment) AS salary_adjustment,
                    MAX(p.graveyard) AS graveyard,
                    MAX(p.late_regular) AS late_regular,
                    MAX(p.late_trainee) AS late_trainee,
                    MAX(p.leave_regular) AS leave_regular,
                    MAX(p.leave_trainee) AS leave_trainee,
                    MAX(p.all_other_deductions) AS all_other_deductions,
                    MAX(p.total) AS total,
                    MAX(p.no_of_branch_employee) AS no_of_branch_employee,
                    MAX(p.no_of_employees_allocated) AS no_of_employees_allocated,
                    COUNT(DISTINCT p.branch_code) AS branch_count 
                FROM 
                    " . $database[0] . ".payroll_edi_report p
                WHERE
                    p.mainzone = '$mainzone'
                    AND p.zone = '$zone'
                    AND p.zone != 'JVIS' 
                    AND p.region_code LIKE '%$region%'
                    AND p.description = 'Sick-Leave'";
                    if($restrictedDate === $endDate){
                        $sql .="AND p.payroll_date = '$restrictedDate'";
                    }else{
                        $sql .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                    }
                    $sql .="AND p.ml_matic_region != 'LNCR Showroom'
                    AND p.ml_matic_region != 'VISMIN Showroom'
                    AND p.description = 'Sick-Leave'
					AND (
							CASE 
								WHEN p.ml_matic_status = 'Active' THEN 'Active'
								WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
							END
						) = '".$status."'
                GROUP BY 
                    p.branch_code, 
                    p.cost_center, 
                    p.region, 
                    p.zone, 
                    p.payroll_date
                ORDER BY 
                    p.region;"; 
    }  
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

         // Check if there are results
         if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            $first_row = mysqli_fetch_assoc($result);

            $payroll_date = htmlspecialchars($first_row['payroll_date']);
            $gl_code_basic_pay_regular = htmlspecialchars($first_row['gl_code_basic_pay_regular']);
            $gl_code_basic_pay_trainee = htmlspecialchars($first_row['gl_code_basic_pay_trainee']);
            $gl_code_allowances = htmlspecialchars($first_row['gl_code_allowances']);
            $gl_code_bm_allowance = htmlspecialchars($first_row['gl_code_bm_allowance']);
            $gl_code_overtime_regular = htmlspecialchars($first_row['gl_code_overtime_regular']);
            $gl_code_overtime_trainee = htmlspecialchars($first_row['gl_code_overtime_trainee']);
            $gl_code_cola = htmlspecialchars($first_row['gl_code_cola']);
            $gl_code_excess_pb = htmlspecialchars($first_row['gl_code_excess_pb']);
            $gl_code_other_income = htmlspecialchars($first_row['gl_code_other_income']);
            $gl_code_salary_adjustment = htmlspecialchars($first_row['gl_code_salary_adjustment']);
            $gl_code_graveyard = htmlspecialchars($first_row['gl_code_graveyard']);
            $gl_code_late_regular = htmlspecialchars($first_row['gl_code_late_regular']);
            $gl_code_late_trainee = htmlspecialchars($first_row['gl_code_late_trainee']);
            $gl_code_leave_regular = htmlspecialchars($first_row['gl_code_leave_regular']);
            $gl_code_leave_trainee = htmlspecialchars($first_row['gl_code_leave_trainee']);
            $gl_code_all_other_deductions = htmlspecialchars($first_row['gl_code_all_other_deductions']);
            $gl_code_total = htmlspecialchars($first_row['gl_code_total']);


            //  first row
            echo "<tr>";
            echo "<th>Date</th>";
            echo "<th>GL Code</th>";
            echo "<th>Zone</th>";
            echo "<th>Region</th>";
            echo "<th>Branch Name from HRMD Data</th>";
			echo "<th>Branch Name from MLMATIC Data</th>";
            echo "<th>Description</th>";
            echo "<th>Amount</th>";
            echo "<th>Category</th>";
            
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th></th>";
			echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            
            
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalNumberOfBranches = 0;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;
 
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

                $totalNumberOfBranches++;
                $Month = date('Y', strtotime($row['payroll_date']));
                echo "<tr>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['payroll_date']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['gl_code_excess_pb']) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['zone']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name_hr']) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name_mlmatic']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(ucwords('Sick Leave Conversion Year' . " $Month")) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['excess_pb'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars('Adjustment') . "</td>";
                
                echo "</tr>";
				
				echo "<tr>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['payroll_date']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['gl_code_total']) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['zone']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name_hr']) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name_mlmatic']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(ucwords('Sick Leave Conversion Year' . " $Month")) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['excess_pb'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars('Adjustment') . "</td>";
                
                echo "</tr>";
            }
 
            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            echo "<script>
            
            var dlbutton = document.getElementById('showdl');
            dlbutton.style.display = 'block';
            
            </script>";
			
			echo "<script>
            
            var dlbutton = document.getElementById('showdl1');
            dlbutton.style.display = 'block';
            
            </script>";

            echo "<div id='showBranches' style='display: block; position: absolute; top: 190px; color: red; left: 20px;'>";
            echo "Total Number of Branches : $totalNumberOfBranches";
            echo "</div>";
        } else {
            echo "No results found.";
        }

    // Close the connection
    mysqli_close($conn);
}

?>