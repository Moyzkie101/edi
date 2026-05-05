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

    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate);
        
    }
	
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        $restrictedDate_raw = date('F-d-Y', strtotime($restrictedDate));

        $dlsql = " WITH filtered_branch_data AS (
            SELECT
                CASE 
                    WHEN branch_id = '2162' THEN 'JVIS' 
                    ELSE zone 
                END AS zone,
                branch_id,
                region_code,
                gl_region,
                ml_matic_region,
                code,
                ml_matic_branch_name
            FROM masterdata.branch_profile
        ),
        final_data AS (
            SELECT
                CASE WHEN nd.zone IN ('JVIS', 'VIS') THEN 'VISAYAS'
                    WHEN nd.zone = 'MIN' THEN 'MINDANAO'
                    WHEN nd.zone = 'LZN' THEN 'LUZON'
                    WHEN nd.zone = 'NCR' THEN 'NCR'
                ELSE nd.zone END AS new_zone,
                nd.gl_region AS new_region,
                nd.branch_id,
                nd.ml_matic_branch_name AS new_branch_name,

                p.payroll_date,
                p.basic_pay_regular,
                p.basic_pay_trainee,
                p.allowances,
                p.bm_allowance,
                p.overtime_regular,
                p.overtime_trainee,
                p.cola,
                p.excess_pb,
                p.other_income,
                p.salary_adjustment,
                p.graveyard,
                p.late_regular,
                p.late_trainee,
                p.leave_regular,
                p.leave_trainee,
                p.all_other_deductions,
                p.total,
                p.no_of_branch_employee,
                p.no_of_employees_allocated,
                p.cost_center,
                COUNT(DISTINCT p.branch_code) AS branch_count 

            FROM 
                " . $database[0] . ".payroll_edi_report p
            JOIN filtered_branch_data nd 
                ON p.branch_code = nd.code 
                AND (
                    (nd.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom') 
                        AND p.ml_matic_region = nd.ml_matic_region)
                    OR 
                    (p.zone = nd.zone AND p.region_code = nd.region_code)
                )
            WHERE p.payroll_date = '$restrictedDate'
                AND p.description = 'payroll'
                AND NOT p.description IN ('Sick-Leave', '13thMonth', 'midYearBonus')
                AND p.status is null
                AND p.remarks is null";
                if ($mainzone !== 'ALL' || $zone !== 'ALL'){
                    $dlsql .= " AND p.mainzone = '$mainzone' ";
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        $dlsql .= " AND p.ml_matic_region = '$zone' AND p.zone LIKE '%$region%' ";
                    }else{
                        $dlsql .= " AND p.zone = '$zone' AND p.region_code LIKE '%$region%' AND NOT p.ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
                    }
                    
                $dlsql .= " AND (
                        CASE 
                            WHEN p.ml_matic_status = 'Active' THEN 'Active'
                            WHEN p.ml_matic_status IN ('Pending', 'Inactive', 'TBO') THEN 'Inactive'
                        END
                    ) = '$status'";
                }else{
                $dlsql .= " AND (
                        CASE 
                            WHEN p.ml_matic_status = 'Active' THEN 'Active'
                            WHEN p.ml_matic_status IN ('Pending', 'Inactive', 'TBO') THEN 'Inactive'
                        END
                    ) = '$status'";
                }
            $dlsql .= " GROUP BY
                new_zone,
                new_region,
                nd.ml_matic_branch_name,
                nd.branch_id,
                p.payroll_date,

                p.payroll_date,
                p.basic_pay_regular,
                p.basic_pay_trainee,
                p.allowances,
                p.bm_allowance,
                p.overtime_regular,
                p.overtime_trainee,
                p.cola,
                p.excess_pb,
                p.other_income,
                p.salary_adjustment,
                p.graveyard,
                p.late_regular,
                p.late_trainee,
                p.leave_regular,
                p.leave_trainee,
                p.all_other_deductions,
                p.total,
                p.no_of_branch_employee,
                p.no_of_employees_allocated,
                p.cost_center
        )
        SELECT *
        FROM final_data
        ORDER BY new_branch_name ASC";

                    
        $dlresult = mysqli_query($conn, $dlsql);

        if (!$dlresult) {
            die("Query failed: " . mysqli_error($conn));
        }

        // Fetch all result rows into an array so we can iterate multiple times
        $dlRows = [];
        while ($r = mysqli_fetch_assoc($dlresult)) {
            $dlRows[] = $r;
        }
                
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        if(mysqli_num_rows($dlresult) > 0) {
            $headerRow1 = ['Payroll for '. date('F d, Y', strtotime($restrictedDate)), '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''];
            $headerRow2 = ['', '', '', '', 'Salary - Regular', 'Salary - Trainee', 'BM\'s Allowance', 'Officer\'s Allowance', 'Overtime - Regular Employee', 'Overtime - Trainee', 'COLA', 'Productivity Bonus', 'Employee Refund', 'Salary Adjustment', 'Graveyard Allowance', 'Late - Regular Employee', 'Late - Trainee', 'LWOP - Regular Employee', 'LWOP - Trainee', 'Employees Accountability (Fake, Coated, etc.)', 'SSS', 'SSS Loan', 'PhilHealth', 'PagIBIG', 'PagIBIG Loan', 'HMO', 'SAKO', 'OPEC', 'Other Payroll Deductions', 'Payroll '. date('F Y', strtotime($restrictedDate))];
            $headerRow3 = ['Zone Name', 'Region Name','BranchID', 'Branch', '521101', '521102', '522203', '522202', '521103', '521103', '522201', '522303', '219102', '521101', '522203', '521101', '521102', '521101', '521102', '118303', '216201', '216204', '216202', '216203', '216205', '216206', '219203', '219202', '219208', '211202'];

            $sheet->fromArray($headerRow1, null, 'A1');
            $sheet->fromArray($headerRow2, null, 'A2');
            $sheet->fromArray($headerRow3, null, 'A3');

            // Keep original autosize for A through AD
            foreach (range('A', 'AD') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            $sheet->getStyle('A1:AD1')->getFont()->setBold(true);
            $sheet->getStyle('A3:AD3')->getFont()->setBold(true);

            $rowIndex = 4;

            // Iterate once and write branch details (A-C) and provisions (D-G) on the same row
            foreach ($dlRows as $row) {
                $bold = false;

                $sheet->setCellValue('A' . $rowIndex, $row['new_zone'] ?? $row['zone']);
                $sheet->setCellValue('B' . $rowIndex, $row['new_region'] ?? $row['region']);
                $sheet->setCellValue('C' . $rowIndex, $row['branch_id'] ?? '');
                $sheet->setCellValue('D' . $rowIndex, $row['new_branch_name'] ?? $row['branch_name'] ?? '');

                $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                                    + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                    $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                    $total = $totalDebit - $totalCredit;

                    // Use setCellValueExplicit for setting the value and format it as a number DEBIT
                    $sheet->setCellValueExplicit('E' . $rowIndex, $row['basic_pay_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('F' . $rowIndex, $row['basic_pay_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                                        
                    $sheet->setCellValueExplicit('G' . $rowIndex, $row['bm_allowance'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                    $sheet->setCellValueExplicit('H' . $rowIndex, $row['allowances'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                                        
                    $sheet->setCellValueExplicit('I' . $rowIndex, $row['overtime_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('J' . $rowIndex, $row['overtime_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('K' . $rowIndex, $row['cola'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('L' . $rowIndex, $row['excess_pb'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                        
                    $sheet->setCellValueExplicit('M' . $rowIndex, $row['other_income'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('N' . $rowIndex, $row['salary_adjustment'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('O' . $rowIndex, $row['graveyard'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    // convert to negative if positive value CREDIT
                    $sheet->setCellValueExplicit('P' . $rowIndex, ($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('Q' . $rowIndex, ($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('R' . $rowIndex, ($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        
                    $sheet->setCellValueExplicit('S' . $rowIndex, ($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('T' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('T' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('U' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('U' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('V' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('V' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('W' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('W' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('X' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('X' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('Y' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('Y' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('Z' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('Z' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('AA' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('AA' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('AB' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('AB' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
					$sheet->setCellValueExplicit('AC' . $rowIndex, 0, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('AC' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('AD' . $rowIndex, $total, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('AD' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $rowIndex++;
            }

            // Ensure columns E through AD are sized based on content starting from row 2
            // (calculate max string length from row 2 to last written row and set width)
            $lastDataRow = $rowIndex - 1;
            foreach (range('E', 'AD') as $columnID) {
                $maxLen = 0;
                for ($r = 2; $r <= $lastDataRow; $r++) {
                    $cell = $sheet->getCell($columnID . $r);
                    // use formatted value so numbers and formatted strings are considered
                    $val = method_exists($cell, 'getFormattedValue') ? $cell->getFormattedValue() : $cell->getValue();
                    if ($val === null || $val === '') continue;
                    $len = mb_strlen((string)$val);
                    if ($len > $maxLen) $maxLen = $len;
                }
                // approximate width: characters * factor, enforce min/max
                $width = max(8, min(120, round($maxLen * 1.2)));
                // ensure autosize is disabled to avoid writer inconsistencies, then set explicit width
                $sheet->getColumnDimension($columnID)->setAutoSize(false);
                $sheet->getColumnDimension($columnID)->setWidth($width);
            }

            // Output single workbook (one sheet)
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if($status === 'Active'){
                if($mainzone !== 'ALL' || $zone !== 'ALL'){
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        if(empty($region)){
                            $filename = "EDI_Payroll_Report_" . $mainzone . "_SHOWROOM_" . $restrictedDate_raw . "_NEW-FORMAT.xls";
                        }else{
                            $filename = "EDI_Payroll_Report_" . $mainzone . "_SHOWROOM_" . $region . "_" . $restrictedDate_raw . "_NEW-FORMAT.xls";
                        }
                    }else{
                        if(empty($region)){
                            $filename = "EDI_Payroll_Report_" . $zone . "_" . $restrictedDate_raw . "_NEW-FORMAT.xls";
                        }else{
                            $filename = "EDI_Payroll_Report_" . $zone . "_" . $region . "_" . $restrictedDate_raw . "_NEW-FORMAT.xls";
                        }
                    }
                }else{
                    $filename = "EDI_Payroll_Report_NATIONWIDE_" . $restrictedDate_raw . "_NEW-FORMAT.xls";
                }
            } else {
                if($mainzone !== 'ALL' || $zone !== 'ALL'){
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        if(empty($region)){
                            $filename = "EDI_Payroll_Report_" . $mainzone . "_SHOWROOM_" . $restrictedDate_raw . "_NEW-FORMAT-(Inactive, Pending and TO BE OPEN).xls";
                        }else{
                            $filename = "EDI_Payroll_Report_" . $mainzone . "_SHOWROOM_" . $region . "_" . $restrictedDate_raw . "_NEW-FORMAT-(Inactive, Pending and TO BE OPEN)";
                        }
                    }else{
                        if(empty($region)){
                            $filename = "EDI_Payroll_Report_" . $zone . "_" . $restrictedDate_raw . "_NEW-FORMAT-(Inactive, Pending and TO BE OPEN).xls";
                        }else{
                            $filename = "EDI_Payroll_Report_" . $zone . "_" . $region . "_" . $restrictedDate_raw . "_NEW-FORMAT-(Inactive, Pending and TO BE OPEN).xls";
                        }
                    }
                }else{
                    $filename = "EDI_Payroll_Report_NATIONWIDE_" . $restrictedDate_raw . "_NEW-FORMAT-(Inactive, Pending and TO BE OPEN).xls";
                }
            }
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
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

    <center><h2>Payroll Report <span>[EDI-NEW Format]</span></h2></center>

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

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
                    <option value="Inactive">Pending & Inactive</option>?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>
        <?php if(isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL' || isset($_POST['zone']) && $_POST['zone'] == 'ALL') :?>
            <div id="showdl" style="display: none">
                <form id="exportForm" action="" method="post">
                    <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
                </form>
            </div>
        <?php else: ?>
            <div id="showdl" style="display: none">
                <form id="exportForm" action="" method="post">
                    <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="<?php echo $relative_path; ?>assets/js/admin/report-file/edi-format/script1.js"></script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $zone = $_POST['zone'];
	$status = $_POST['status'];
    $restrictedDate = $_POST['restricted-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
	$_SESSION['status'] = $status;
    $_SESSION['restrictedDate'] = $restrictedDate;

    $sql = " SELECT 
                p.branch_code,
                p.cost_center,
                p.ml_matic_status,
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
                        WHEN p.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                    END
                ) = '$status' 
                    GROUP BY 
                        p.branch_code, 
                        p.cost_center, 
                        p.ml_matic_status,
                        p.region, 
                        p.zone, 
                        p.payroll_date
                    ORDER BY 
                        p.region ";

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
            echo "<th>Branch Status</th>";
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
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['basic_pay_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['basic_pay_trainee'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['allowances'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['bm_allowance'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['overtime_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['overtime_trainee'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['cola'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['excess_pb'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['other_income'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['salary_adjustment'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['graveyard'], 2)) . "</td>";
                // convert to negative if positive value 
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee'], 2)) . "</td>";

                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'> ".htmlspecialchars(number_format($total, 2))." </td>"; 
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['ml_matic_status']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['all_other_deductions'], 2)) . "</td>"; 
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