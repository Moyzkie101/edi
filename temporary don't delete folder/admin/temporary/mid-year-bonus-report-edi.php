<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    require '../vendor/autoload.php';
    include '../config/connection.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate);
        
    }
 
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
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
                    MAX(p.branch_name) AS branch_name,
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
                    AND p.payroll_date = '$restrictedDate'
                    AND p.ml_matic_region = '$zone'
                    AND p.zone LIKE '%$region%'
                    AND NOT (p.branch_code = 18 AND p.zone = 'VIS') -- to exclude Duljo branch
                    AND p.description = 'midYearBonus' 
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
                    MAX(p.branch_name) AS branch_name,
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
                    AND p.zone != 'JVIS' -- to exclude SM Seaside Showroom
                    AND p.region_code LIKE '%$region%'
                    AND p.payroll_date = '$restrictedDate'
                    AND p.ml_matic_region != 'LNCR Showroom'
                    AND p.ml_matic_region != 'VISMIN Showroom'
                    AND p.description = 'midYearBonus'
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
                    
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
                    
            if(mysqli_num_rows($dlresult) > 0) {
                
                $first_row = mysqli_fetch_assoc($dlresult);
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
                                
                // Reset the result pointer to the beginning
                mysqli_data_seek($dlresult, 0);
                    
                $headerRow1 = [
                    '', '', '', 'BASIC PAY', '', 'ALLOWANCES', '','OVERTIME', '', 'COLA', 'PB/', 
                    'Recievable', 'Salary', 'Graveyard', 'LATE',
                    '', 'LEAVE', '', 'Total', ''
                ];
                    
                $headerRow2 = [
                    '', '', '', 'Reg', 'Trainee', 'Allowance', 'BM Allowance',
                    'Reg', 'Trainee', '', 'EXCESS PB', 'Incentives', 'Adjustment', '', 'Reg',
                    'Trainee', 'Reg', 'Trainee', '', 'cost'
                ];
                    
                $headerRow3 = [
                    '', '', '', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                    'credit', 'credit', 'credit', 'credit', 'credit', 'center'
                ];
                    
                $headerRow4 = [
                    'Code', 'Region', 'Branches', $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
                    $gl_code_cola, $gl_code_excess_pb, $gl_code_other_income, $gl_code_salary_adjustment, $gl_code_graveyard, $gl_code_late_regular, $gl_code_late_trainee,
                    $gl_code_leave_regular, $gl_code_leave_trainee, $gl_code_total, '', '', '', ''
                ];
                    
                $sheet->fromArray([$headerRow1], null, 'A1');
                $sheet->fromArray([$headerRow2], null, 'A2');
                $sheet->fromArray([$headerRow3], null, 'A3');
                $sheet->fromArray([$headerRow4], null, 'A4');
                        
                foreach(range('A', 'Z') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
                                
                $rowIndex = 5;
                $total = 0;
                $totalDebit = 0;
                $totalCredit = 0;
                    
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    
                    $applyStyle = false; 
                                
                    if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                        $color = '4fc917';  
                        $bold = true;       
                        $applyStyle = true; 
                    } else {
                        $bold = false;      
                    }
                                        
                    $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                                    + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                    $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                    $total = $totalDebit - $totalCredit;
                        
                    $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                    $sheet->setCellValue('B' . $rowIndex, $row['region']);
                    $sheet->setCellValue('C' . $rowIndex, $row['branch_name']);
                        
                    // Use setCellValueExplicit for setting the value and format it as a number
                    $sheet->setCellValueExplicit('D' . $rowIndex, $row['basic_pay_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('E' . $rowIndex, $row['basic_pay_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('F' . $rowIndex, $row['allowances'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('G' . $rowIndex, $row['bm_allowance'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('G' . $rowIndex, $row['overtime_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('I' . $rowIndex, $row['overtime_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('J' . $rowIndex, $row['cola'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('K' . $rowIndex, $row['excess_pb'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('L' . $rowIndex, $row['other_income'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('M' . $rowIndex, $row['salary_adjustment'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('N' . $rowIndex, $row['graveyard'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    // convert to negative if positive value 
                    $sheet->setCellValueExplicit('O' . $rowIndex, ($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('P' . $rowIndex, ($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('Q' . $rowIndex, ($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('R' . $rowIndex, ($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                                        
                    $sheet->setCellValueExplicit('S' . $rowIndex, $total, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValue('T' . $rowIndex, $row['cost_center']);
                                        
                    if ($applyStyle) {
                        $sheet->getStyle('A' . $rowIndex . ':T' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($color);
                    }
                                    
                    // Apply bold style regardless of background color
                    $sheet->getStyle('A' . $rowIndex . ':T' . $rowIndex)->getFont()->setBold($bold);
                        
                    $rowIndex++;
        }           
                            
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filename = "EDI_MidYearBonus_Report_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
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
    <link rel="icon" href="../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../assets/css/admin/report-file/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>Mid Year Bonus <span style="font-size: 22px; color: red;">[EDI-Format]</span></h2></center>

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
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>

        <div id="showdl" style="display: none">
            <form id="exportForm" action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>
    </div>

    <script>
        //for fetching zone
        function updateZone() {
            var mainzone = document.getElementById("mainzone").value;
            var selectedZone = document.getElementById("zone").value; // Get the currently selected zone, if any
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "get_zone.php", true);
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
            xhr.open("POST", "get_regions.php", true);
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
    <script>
        // Attach click event listeners to group buttons
        document.querySelectorAll('.group-btn').forEach(button => {
            button.addEventListener('click', () => {
                const group = button.parentElement;

                // Toggle visibility of this group
                group.classList.toggle('show');

                // Close other groups in the dropdown
                document.querySelectorAll('.dropdown-group').forEach(otherGroup => {
                    if (otherGroup !== group) {
                        otherGroup.classList.remove('show');
                    }
                });
            });
        });

        // Close all groups when clicking outside the dropdown
        document.addEventListener('click', event => {
            if (!event.target.closest('.dropdown-content')) {
                document.querySelectorAll('.dropdown-group').forEach(group => {
                    group.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $zone = $_POST['zone'];
    $restrictedDate = $_POST['restricted-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
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
                    MAX(p.branch_name) AS branch_name,
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
                    AND p.payroll_date = '$restrictedDate'
                    AND p.ml_matic_region = '$zone'
                    AND p.zone LIKE '%$region%'
                    AND NOT (p.branch_code = 18 AND p.zone = 'VIS') -- to exclude Duljo branch
                    AND p.description = 'midYearBonus' 
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
                    MAX(p.branch_name) AS branch_name,
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
                    AND p.zone != 'JVIS' -- to exclude SM Seaside Showroom
                    AND p.region_code LIKE '%$region%'
                    AND p.payroll_date = '$restrictedDate'
                    AND p.ml_matic_region != 'LNCR Showroom'
                    AND p.ml_matic_region != 'VISMIN Showroom'
                    AND p.description = 'midYearBonus'
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
            echo "<th colspan='2'>Payroll Date - " . $payroll_date . "</th>";
            echo "<th>Basic Pay Regular</th>";
            echo "<th>Basic Pay Trainee</th>";
            echo "<th>Allowances</th>";
            echo "<th>BM Allowance</th>";
            echo "<th>Overtime Regular</th>";
            echo "<th>Overtime Trainee</th>";
            echo "<th>COLA</th>";
            echo "<th>Excess PB</th>";
            echo "<th>Other Income</th>";
            echo "<th>Salary Adjustment</th>";
            echo "<th>Graveyard</th>";
            echo "<th>Late Regular</th>";
            echo "<th>Late Trainee</th>";
            echo "<th>Leave Regular</th>";
            echo "<th>Leave Trainee</th>";
            echo "<th>Total</th>";
            echo "<th>Cost Center</th>";
            echo "<th style='width: 10px;'></th>";
            echo "<th>Region</th>";
            echo "<th>All Other Deductions</th>";
            echo "<th>No. of Branch Employees</th>";
            echo "<th>No. of Employees Allocated</th>";
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            //third row
            echo "<tr>";
            echo "<th style='white-space: nowrap'>BOS Code</th>";
            echo "<th>Branch Name</th>";
            echo "<th>". $gl_code_basic_pay_regular ."</th>";
            echo "<th>". $gl_code_basic_pay_trainee ."</th>";
            echo "<th>". $gl_code_allowances ."</th>";
            echo "<th>". $gl_code_bm_allowance ."</th>";
            echo "<th>". $gl_code_overtime_regular ."</th>";
            echo "<th>". $gl_code_overtime_trainee ."</th>";
            echo "<th>". $gl_code_cola ."</th>";
            echo "<th>". $gl_code_excess_pb ."</th>";
            echo "<th>". $gl_code_other_income ."</th>";
            echo "<th>". $gl_code_salary_adjustment ."</th>";
            echo "<th>". $gl_code_graveyard ."</th>";
            echo "<th>". $gl_code_late_regular ."</th>";
            echo "<th>". $gl_code_late_trainee ."</th>";
            echo "<th>". $gl_code_leave_regular ."</th>";
            echo "<th>". $gl_code_leave_trainee ."</th>";
            echo "<th>". $gl_code_total ."</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th>". $gl_code_all_other_deductions ."</th>";
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
                $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                            + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                $total = $totalDebit - $totalCredit;

                echo "<tr>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_code']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['allowances']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['bm_allowance']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cola']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['excess_pb']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['other_income']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['salary_adjustment']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['graveyard']) . "</td>";
                // convert to negative if positive value 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']) . "</td>";

                echo "<td style='background-color: $color; font-weight: $bold'> $total </td>"; 
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['all_other_deductions']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_branch_employee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_employees_allocated']) . "</td>";
                echo "</tr>";
            }
 
            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            echo "<script>
            
            var dlbutton = document.getElementById('showdl');
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