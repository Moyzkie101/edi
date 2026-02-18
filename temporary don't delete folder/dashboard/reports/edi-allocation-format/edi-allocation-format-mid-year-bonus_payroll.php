<?php
    session_start();

    if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'user')) {
        header('location: ../../../logout.php');
        session_destroy();
        exit();
    }else{
        // Check if user_roles session exists
        $roles = array_map('trim', explode(',', $_SESSION['user_roles'])); // Convert roles into an array and trim whitespace
        if (!in_array('KP DOMESTIC', $roles)){
            header('location: ../../../logout.php');
            session_destroy();
            exit();
        }
    }

    require '../../../vendor/autoload.php';
    include '../../../config/connection.php';

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
		// $status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate);

    }
	
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
		// $status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        $payroll_date_format = date('F Y', strtotime($restrictedDate));
        $getYearMonth_date_format = date('Y-m', strtotime($restrictedDate));
        $getLastDay_date_format = date('t', strtotime($restrictedDate));

        $dlsql="WITH closed_branch_sums AS (
            SELECT
                region_code,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN basic_pay_regular ELSE 0 END) AS sum_basic_pay_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN basic_pay_trainee ELSE 0 END) AS sum_basic_pay_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN allowances ELSE 0 END) AS sum_allowances,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN bm_allowance ELSE 0 END) AS sum_bm_allowance,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN overtime_regular ELSE 0 END) AS sum_overtime_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN overtime_trainee ELSE 0 END) AS sum_overtime_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN cola ELSE 0 END) AS sum_cola,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN excess_pb ELSE 0 END) AS sum_excess_pb,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN other_income ELSE 0 END) AS sum_other_income,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN salary_adjustment ELSE 0 END) AS sum_salary_adjustment,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN graveyard ELSE 0 END) AS sum_graveyard,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN late_regular ELSE 0 END) AS sum_late_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN late_trainee ELSE 0 END) AS sum_late_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN leave_regular ELSE 0 END) AS sum_leave_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN leave_trainee ELSE 0 END) AS sum_leave_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN all_other_deductions ELSE 0 END) AS sum_all_other_deductions,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total ELSE 0 END) AS sum_total
            FROM " . $database[0] . ".payroll_edi_report
            WHERE payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
            AND description = 'midYearBonus'
            AND NOT payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
            AND NOT description = 'payroll'
            AND ml_matic_status IN ('Pending', 'Inactive')
            GROUP BY region_code
        ),
        active_branch_count AS (
            SELECT
                region_code,
                COUNT(*) AS active_count
            FROM " . $database[0] . ".payroll_edi_report
            WHERE payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
            AND description = 'midYearBonus'
            AND NOT payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
            AND NOT description = 'payroll'
            AND ml_matic_status = 'Active'
            GROUP BY region_code
        )

        SELECT 
            epr.payroll_date,
            epr.mainzone,
            epr.zone,
            epr.region,
            epr.ml_matic_region,
            epr.region_code,
            epr.branch_code,
            epr.branch_name,
            
            cbs.sum_basic_pay_regular / NULLIF(abc.active_count, 0) AS basic_pay_regular,
            epr.gl_code_basic_pay_regular,
            cbs.sum_basic_pay_trainee / NULLIF(abc.active_count, 0) AS basic_pay_trainee,
            epr.gl_code_basic_pay_trainee,
            cbs.sum_allowances / NULLIF(abc.active_count, 0) AS allowances,
            epr.gl_code_allowances,
            cbs.sum_bm_allowance / NULLIF(abc.active_count, 0) AS bm_allowance,
            epr.gl_code_bm_allowance,
            cbs.sum_overtime_regular / NULLIF(abc.active_count, 0) AS overtime_regular,
            epr.gl_code_overtime_regular,
            cbs.sum_overtime_trainee / NULLIF(abc.active_count, 0) AS overtime_trainee,
            epr.gl_code_overtime_trainee,
            cbs.sum_cola / NULLIF(abc.active_count, 0) AS cola,
            epr.gl_code_cola,
            cbs.sum_excess_pb / NULLIF(abc.active_count, 0) AS excess_pb,
            epr.gl_code_excess_pb,
            cbs.sum_other_income / NULLIF(abc.active_count, 0) AS other_income,
            epr.gl_code_other_income,
            cbs.sum_salary_adjustment / NULLIF(abc.active_count, 0) AS salary_adjustment,
            epr.gl_code_salary_adjustment,
            cbs.sum_graveyard / NULLIF(abc.active_count, 0) AS graveyard,
            epr.gl_code_graveyard,
            
            cbs.sum_late_regular / NULLIF(abc.active_count, 0) AS late_regular,
            epr.gl_code_late_regular,
            cbs.sum_late_trainee / NULLIF(abc.active_count, 0) AS late_trainee,
            epr.gl_code_late_trainee,
            cbs.sum_leave_regular / NULLIF(abc.active_count, 0) AS leave_regular,
            epr.gl_code_leave_regular,
            cbs.sum_leave_trainee / NULLIF(abc.active_count, 0) AS leave_trainee,
            epr.gl_code_leave_trainee,
            cbs.sum_all_other_deductions / NULLIF(abc.active_count, 0) AS all_other_deductions,
            epr.gl_code_all_other_deductions,
            epr.no_of_branch_employee,
            epr.no_of_employees_allocated,

            cbs.sum_total / NULLIF(abc.active_count, 0) AS total,

            epr.gl_code_total,
            epr.cost_center,
            epr.sheetname
            
        FROM " . $database[0] . ".payroll_edi_report AS epr
        INNER JOIN closed_branch_sums cbs ON cbs.region_code = epr.region_code
        INNER JOIN active_branch_count abc ON abc.region_code = epr.region_code
        WHERE epr.ml_matic_status = 'Active'
        AND epr.payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
        AND epr.description = 'midYearBonus'
        AND NOT epr.payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
        AND NOT epr.description = 'payroll'";
        if($mainzone === 'ALL') {
            $dlsql .= " AND epr.mainzone IN ('LNCR','VISMIN') ";
            if($zone === 'ALL') {
                $dlsql .= " AND epr.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN') ";
            }
        } else {
            $dlsql .= " AND epr.mainzone = '$mainzone'";
            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                $dlsql .= " AND epr.ml_matic_region = '$zone' AND epr.zone LIKE '%$region%' ";
            }else{
                $dlsql .= " AND epr.zone = '$zone' AND epr.region_code LIKE '%$region%' AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom') ";
            }
        }
        // $dlsql .= " AND epr.mainzone = '$mainzone'";
        // if($zone==='VIS'){
        //     $dlsql .= " AND epr.zone = 'VIS'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $dlsql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone==='MIN'){
        //     $dlsql .= " AND epr.zone = 'MIN'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $dlsql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone==='LZN'){
        //     $dlsql .= " AND epr.zone = 'LZN'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $dlsql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone==='NCR'){
        //     $dlsql .= " AND epr.zone = 'NCR'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $dlsql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone ==='VISMIN Showroom'){
        //     $dlsql .= " AND epr.ml_matic_region = 'VISMIN Showroom'";
        // }elseif($zone ==='LNCR Showroom'){
        //     $dlsql .= " AND epr.ml_matic_region = 'LNCR Showroom'";
        // }
                    
            $dlresult = mysqli_query($conn, $dlsql);
                    
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // --- Begin: Multi-zone Excel and ZIP logic ---
            if($mainzone === 'ALL' || $zone === 'ALL'){
                if(mysqli_num_rows($dlresult) > 0) {
                    // 1. Group data into 5 arrays
                    $groups = [
                        'EDI_MidYearBonus_Allocation_Payroll_Report_LZN_' => [],
                        'EDI_MidYearBonus_Allocation_Payroll_Report_NCR_' => [],
                        'EDI_MidYearBonus_Allocation_Payroll_Report_VIS_' => [],
                        'EDI_MidYearBonus_Allocation_Payroll_Report_MIN_' => [],
                        'EDI_MidYearBonus_Allocation_Payroll_Report_NATIONWIDE-SHOWROOM_' => []
                    ];
                    mysqli_data_seek($dlresult, 0);
                    while ($row = mysqli_fetch_assoc($dlresult)) {
                        if ($row['zone'] == 'LZN' && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                            $groups['EDI_MidYearBonus_Allocation_Payroll_Report_LZN_'][] = $row;
                        } elseif ($row['zone'] == 'NCR' && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                            $groups['EDI_MidYearBonus_Allocation_Payroll_Report_NCR_'][] = $row;
                        } elseif ($row['zone'] == 'VIS' && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                            $groups['EDI_MidYearBonus_Allocation_Payroll_Report_VIS_'][] = $row;
                        } elseif ($row['zone'] == 'MIN' && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                            $groups['EDI_MidYearBonus_Allocation_Payroll_Report_MIN_'][] = $row;
                        } elseif (in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                            $groups['EDI_MidYearBonus_Allocation_Payroll_Report_NATIONWIDE-SHOWROOM_'][] = $row;
                        }
                    }

                    // 2. Prepare temp dir for Excel files
                    $tmpDir = sys_get_temp_dir() . '/edi_excel_' . uniqid();
                    mkdir($tmpDir);

                    $filePaths = [];
                    foreach ($groups as $groupName => $rows) {
                        if (empty($rows)) continue;

                        // Use your header logic
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();

                        // Use the first row for GL codes
                        $first_row = $rows[0];
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

                        $headerRow1 = [
                            '', '', 'BASIC PAY', '', 'ALLOWANCES', '','OVERTIME', '', 'COLA', 'PB/', 
                            'Recievable', 'Salary', 'Graveyard', 'LATE',
                            '', 'LEAVE', '', 'Total', ''
                        ];
                        $headerRow2 = [
                            '', '', 'Reg', 'Trainee', 'Allowance', 'BM Allowance',
                            'Reg', 'Trainee', '', 'EXCESS PB', 'Incentives', 'Adjustment', '', 'Reg',
                            'Trainee', 'Reg', 'Trainee', '', 'cost'
                        ];
                        $headerRow3 = [
                            '', '', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                            'credit', 'credit', 'credit', 'credit', 'credit', 'center'
                        ];
                        $headerRow4 = [
                            'Code', 'Branches', $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
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
                        foreach ($rows as $row) {
                            $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                                + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                            $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                            $total = $totalDebit - $totalCredit;

                            $applyStyle = false;
                            if (strpos($row['cost_center'], '0001') === 0 && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                                $color = '4fc917';
                                $bold = true;
                                $applyStyle = true;
                            } else {
                                $bold = false;
                            }

                            $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                            $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
                            $sheet->setCellValueExplicit('C' . $rowIndex, $row['basic_pay_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('D' . $rowIndex, $row['basic_pay_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('E' . $rowIndex, $row['allowances'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('F' . $rowIndex, $row['bm_allowance'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('G' . $rowIndex, $row['overtime_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('H' . $rowIndex, $row['overtime_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('I' . $rowIndex, $row['cola'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('J' . $rowIndex, $row['excess_pb'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('K' . $rowIndex, $row['other_income'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('L' . $rowIndex, $row['salary_adjustment'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('M' . $rowIndex, $row['graveyard'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('N' . $rowIndex, ($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('O' . $rowIndex, ($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('P' . $rowIndex, ($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('Q' . $rowIndex, ($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValueExplicit('R' . $rowIndex, $total, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                            $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            $sheet->setCellValue('S' . $rowIndex, $row['cost_center']);

                            if ($applyStyle) {
                                $sheet->getStyle('A' . $rowIndex . ':S' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                    ->getStartColor()->setARGB($color);
                            }
                            $sheet->getStyle('A' . $rowIndex . ':S' . $rowIndex)->getFont()->setBold($bold);

                            $rowIndex++;
                        }

                        // Save file
                        $filename = $groupName . $payroll_date_format.'.xls';
                        $filePath = $tmpDir . '/' . $filename;
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
                        $writer->save($filePath);
                        $filePaths[] = $filePath;
                    }

                    // 3. Create ZIP
                    $zipPath = $tmpDir . '/EDI_MidYearBonus_Allocation_Payroll_Report_' . $payroll_date_format . '.zip';
                    $zip = new ZipArchive();
                    $zip->open($zipPath, ZipArchive::CREATE);
                    foreach ($filePaths as $file) {
                        $zip->addFile($file, basename($file));
                    }
                    $zip->close();

                    // 4. Output ZIP for download
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
                    header('Content-Length: ' . filesize($zipPath));
                    readfile($zipPath);

                    // 5. Cleanup
                    foreach ($filePaths as $file) unlink($file);
                    unlink($zipPath);
                    rmdir($tmpDir);
                    exit();
                }
            }else{
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
                        '', '', 'BASIC PAY', '', 'ALLOWANCES', '','OVERTIME', '', 'COLA', 'PB/', 
                        'Recievable', 'Salary', 'Graveyard', 'LATE',
                        '', 'LEAVE', '', 'Total', ''
                    ];
                        
                    $headerRow2 = [
                        '', '', 'Reg', 'Trainee', 'Allowance', 'BM Allowance',
                        'Reg', 'Trainee', '', 'EXCESS PB', 'Incentives', 'Adjustment', '', 'Reg',
                        'Trainee', 'Reg', 'Trainee', '', 'cost'
                    ];
                        
                    $headerRow3 = [
                        '', '', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                        'credit', 'credit', 'credit', 'credit', 'credit', 'center'
                    ];
                        
                    $headerRow4 = [
                        'Code', 'Branches', $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
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
                        $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
                            
                        // Use setCellValueExplicit for setting the value and format it as a number
                        $sheet->setCellValueExplicit('C' . $rowIndex, $row['basic_pay_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('D' . $rowIndex, $row['basic_pay_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('E' . $rowIndex, $row['allowances'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            
                        $sheet->setCellValueExplicit('F' . $rowIndex, $row['bm_allowance'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('G' . $rowIndex, $row['overtime_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('H' . $rowIndex, $row['overtime_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('I' . $rowIndex, $row['cola'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('J' . $rowIndex, $row['excess_pb'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('K' . $rowIndex, $row['other_income'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValueExplicit('L' . $rowIndex, $row['salary_adjustment'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            
                        $sheet->setCellValueExplicit('M' . $rowIndex, $row['graveyard'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        // convert to negative if positive value 
                        $sheet->setCellValueExplicit('N' . $rowIndex, ($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            
                        $sheet->setCellValueExplicit('O' . $rowIndex, ($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            
                        $sheet->setCellValueExplicit('P' . $rowIndex, ($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            
                        $sheet->setCellValueExplicit('Q' . $rowIndex, ($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                            
                                            
                        $sheet->setCellValueExplicit('R' . $rowIndex, $total, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                            
                        $sheet->setCellValue('S' . $rowIndex, $row['cost_center']);
                                            
                        if ($applyStyle) {
                            $sheet->getStyle('A' . $rowIndex . ':S' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                        }
                                        
                        // Apply bold style regardless of background color
                        $sheet->getStyle('A' . $rowIndex . ':S' . $rowIndex)->getFont()->setBold($bold);
                            
                        $rowIndex++;
                    }           
                                    
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        if(!empty($region)){
                            $filename = "EDI_MidYearBonus_Allocation_Payroll_Report_" . $mainzone . "_" . $region . "_" . $payroll_date_format . ".xls";
                        }else{
                            $filename = "EDI_MidYearBonus_Allocation_Payroll_Report_" . $mainzone . "_" . $payroll_date_format . ".xls";
                        }
                    }else{
                        if(!empty($region)){
                            $filename = "EDI_MidYearBonus_Allocation_Payroll_Report_" . $zone .  "_" . $region . "_" . $payroll_date_format . ".xls";
                        } else {
                            $filename = "EDI_MidYearBonus_Allocation_Payroll_Report_" . $zone . "_" . $payroll_date_format . ".xls";
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
            
        
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="../../../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../../../assets/css/admin/report-file/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
</head>

<body>

    <div class="top-content">
        <?php include '../../../templates/sidebar.php' ?>
    </div>

    <center><h2>Mid Year Bonus Payroll Allocation Report <span style="font-size: 22px; color: red;">[EDI-Format]</span></h2></center>

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
                <label for="restricted-date">Payroll date </label>
                <input type="month" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>
        <div id="showdl" style="display: none">
            <form id="exportForm" action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
            </form>
        </div>
    </div>

    <!-- <script src="../../../assets/js/admin/report-file/edi-allocation-format/script1.js"></script> -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthInput = document.getElementById('restricted-date');
            const dateDisplay = document.getElementById('date-display');
            
            function updateDateDisplay() {
                if (monthInput.value) {
                    const date = new Date(monthInput.value + '-01');
                    const options = { year: 'numeric', month: 'long' };
                    const formattedDate = date.toLocaleDateString('en-US', options);
                    dateDisplay.textContent = 'Selected: ' + formattedDate;
                } else {
                    dateDisplay.textContent = '';
                }
            }
            
            // Update display when value changes
            monthInput.addEventListener('change', updateDateDisplay);
            
            // Update display on page load if there's a value
            updateDateDisplay();
        });
    </script>
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
	// $status = $_POST['status'];
    $restrictedDate = $_POST['restricted-date'];

    $payroll_date_format = date('F Y', strtotime($restrictedDate));
    $getYearMonth_date_format = date('Y-m', strtotime($restrictedDate));
    $getLastDay_date_format = date('t', strtotime($restrictedDate));

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
	// $_SESSION['status'] = $status;
    $_SESSION['restrictedDate'] = $restrictedDate;

    $sql="WITH closed_branch_sums AS (
            SELECT
                region_code,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN basic_pay_regular ELSE 0 END) AS sum_basic_pay_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN basic_pay_trainee ELSE 0 END) AS sum_basic_pay_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN allowances ELSE 0 END) AS sum_allowances,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN bm_allowance ELSE 0 END) AS sum_bm_allowance,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN overtime_regular ELSE 0 END) AS sum_overtime_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN overtime_trainee ELSE 0 END) AS sum_overtime_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN cola ELSE 0 END) AS sum_cola,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN excess_pb ELSE 0 END) AS sum_excess_pb,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN other_income ELSE 0 END) AS sum_other_income,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN salary_adjustment ELSE 0 END) AS sum_salary_adjustment,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN graveyard ELSE 0 END) AS sum_graveyard,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN late_regular ELSE 0 END) AS sum_late_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN late_trainee ELSE 0 END) AS sum_late_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN leave_regular ELSE 0 END) AS sum_leave_regular,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN leave_trainee ELSE 0 END) AS sum_leave_trainee,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN all_other_deductions ELSE 0 END) AS sum_all_other_deductions,
                SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total ELSE 0 END) AS sum_total
            FROM " . $database[0] . ".payroll_edi_report

            WHERE payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
            AND description = 'midYearBonus'
            AND NOT payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
            AND NOT description = 'payroll'
            AND ml_matic_status IN ('Pending', 'Inactive')
            GROUP BY region_code
        ),
        active_branch_count AS (
            SELECT
                region_code,
                COUNT(*) AS active_count
            FROM " . $database[0] . ".payroll_edi_report
            
            WHERE payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
            AND description = 'midYearBonus'
            AND NOT payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
            AND NOT description = 'payroll'
            AND ml_matic_status = 'Active'
            GROUP BY region_code
        )

        SELECT 
            epr.payroll_date,
            epr.mainzone,
            epr.zone,
            epr.region,
            epr.ml_matic_region,
            epr.region_code,
            epr.branch_code,
            epr.branch_name,
            
            cbs.sum_basic_pay_regular / NULLIF(abc.active_count, 0) AS basic_pay_regular,
            epr.gl_code_basic_pay_regular,
            cbs.sum_basic_pay_trainee / NULLIF(abc.active_count, 0) AS basic_pay_trainee,
            epr.gl_code_basic_pay_trainee,
            cbs.sum_allowances / NULLIF(abc.active_count, 0) AS allowances,
            epr.gl_code_allowances,
            cbs.sum_bm_allowance / NULLIF(abc.active_count, 0) AS bm_allowance,
            epr.gl_code_bm_allowance,
            cbs.sum_overtime_regular / NULLIF(abc.active_count, 0) AS overtime_regular,
            epr.gl_code_overtime_regular,
            cbs.sum_overtime_trainee / NULLIF(abc.active_count, 0) AS overtime_trainee,
            epr.gl_code_overtime_trainee,
            cbs.sum_cola / NULLIF(abc.active_count, 0) AS cola,
            epr.gl_code_cola,
            cbs.sum_excess_pb / NULLIF(abc.active_count, 0) AS excess_pb,
            epr.gl_code_excess_pb,
            cbs.sum_other_income / NULLIF(abc.active_count, 0) AS other_income,
            epr.gl_code_other_income,
            cbs.sum_salary_adjustment / NULLIF(abc.active_count, 0) AS salary_adjustment,
            epr.gl_code_salary_adjustment,
            cbs.sum_graveyard / NULLIF(abc.active_count, 0) AS graveyard,
            epr.gl_code_graveyard,
            
            cbs.sum_late_regular / NULLIF(abc.active_count, 0) AS late_regular,
            epr.gl_code_late_regular,
            cbs.sum_late_trainee / NULLIF(abc.active_count, 0) AS late_trainee,
            epr.gl_code_late_trainee,
            cbs.sum_leave_regular / NULLIF(abc.active_count, 0) AS leave_regular,
            epr.gl_code_leave_regular,
            cbs.sum_leave_trainee / NULLIF(abc.active_count, 0) AS leave_trainee,
            epr.gl_code_leave_trainee,
            cbs.sum_all_other_deductions / NULLIF(abc.active_count, 0) AS all_other_deductions,
            epr.gl_code_all_other_deductions,
            epr.no_of_branch_employee,
            epr.no_of_employees_allocated,

            cbs.sum_total / NULLIF(abc.active_count, 0) AS total,

            epr.gl_code_total,
            epr.cost_center,
            epr.sheetname
            
        FROM " . $database[0] . ".payroll_edi_report AS epr
        INNER JOIN closed_branch_sums cbs ON cbs.region_code = epr.region_code
        INNER JOIN active_branch_count abc ON abc.region_code = epr.region_code

        WHERE epr.ml_matic_status = 'Active'
        AND epr.payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
        AND epr.description = 'midYearBonus'
        AND NOT epr.payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
        AND NOT epr.description = 'payroll'";

        if($mainzone === 'ALL') {
            $sql .= " AND epr.mainzone IN ('LNCR','VISMIN') ";
            if($zone === 'ALL') {
                $sql .= " AND epr.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN') ";
            }
        } else {
            $sql .= " AND epr.mainzone = '$mainzone'";
            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                $sql .= " AND epr.ml_matic_region = '$zone' AND epr.zone LIKE '%$region%' ";
            }else{
                $sql .= " AND epr.zone = '$zone' AND epr.region_code LIKE '%$region%' AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom') ";
            }
        }

        // if($zone==='VIS'){
        //     $sql .= " AND epr.zone = 'VIS'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $sql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone==='MIN'){
        //     $sql .= " AND epr.zone = 'MIN'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $sql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone==='LZN'){
        //     $sql .= " AND epr.zone = 'LZN'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $sql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone==='NCR'){
        //     $sql .= " AND epr.zone = 'NCR'
        //         AND NOT epr.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
        //     ";
        //     if(!empty($region)){
        //         $sql .= " AND epr.region_code LIKE '%$region%'";
        //     }
        // }elseif($zone ==='VISMIN Showroom'){
        //     $sql .= " AND epr.ml_matic_region = 'VISMIN Showroom'";
        // }elseif($zone ==='LNCR Showroom'){
        //     $sql .= " AND epr.ml_matic_region = 'LNCR Showroom'";
        // }
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

        // Check for query execution errors
        if (!$result) {
            die("Query failed: " . mysqli_error($conn));
        }

         // Check if there are results
        if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            $first_row = mysqli_fetch_assoc($result);

            // $payroll_date = htmlspecialchars($first_row['payroll_date']);
            $payroll_date = htmlspecialchars($payroll_date_format);
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

                if (strpos($row['cost_center'], '0001') === 0 && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
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
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['basic_pay_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['basic_pay_trainee'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['allowances'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['bm_allowance'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['overtime_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['overtime_trainee'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['cola'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['excess_pb'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['other_income'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['salary_adjustment'], 2)) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['graveyard'], 2)) . "</td>";
                // convert to negative if positive value 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular'], 2)) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee'], 2)) . "</td>";

                echo "<td style='background-color: $color; font-weight: $bold'> ".htmlspecialchars(number_format($total, 2))." </td>"; 
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['all_other_deductions'], 2)) . "</td>"; 
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