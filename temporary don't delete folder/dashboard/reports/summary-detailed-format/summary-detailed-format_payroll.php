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


    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;


    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $branch = $_SESSION['branch'] ?? '';
        $startDate = $_SESSION['startdate'] ?? '';
        $endDate = $_SESSION['enddate'] ?? '';

        generateDownload($conn, $database, $mainzone, $zone, $region, $branch, $startDate, $endDate);

    }


    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $zone, $region, $branch, $startDate, $endDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $branch = $_SESSION['branch'] ?? '';
        $startDate = $_SESSION['startdate'] ?? '';
        $endDate = $_SESSION['enddate'] ?? '';

        $dlsql="WITH summarized AS (
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
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total ELSE 0 END) AS sum_total,
                            COUNT(CASE WHEN ml_matic_status = 'Active' THEN 1 ELSE NULL END) AS active_count
                        FROM " . $database[0] . ".payroll_edi_report";
                        if ($startDate === $endDate) {
                            $dlsql .= " WHERE payroll_date = '$startDate'";
                        } else {
                            $dlsql .= " WHERE payroll_date BETWEEN '$startDate' AND '$endDate'";
                        }
                            $dlsql .= " GROUP BY region_code
                    )

                    SELECT 
                        per.payroll_date, 
                        per.mainzone, 
                        per.zone, 
                        per.region, 
                        per.ml_matic_region, 
                        per.region_code, 
                        per.kp_code, 
                        per.ml_matic_status, 
                        per.branch_code, 
                        per.branch_name, 

                        s.sum_basic_pay_regular / NULLIF(s.active_count, 0) AS basic_pay_regular_avg,
                        SUM(per.basic_pay_regular) AS basic_pay_regular, 
                        per.gl_code_basic_pay_regular, 

                        s.sum_basic_pay_trainee / NULLIF(s.active_count, 0) AS basic_pay_trainee_avg,
                        SUM(per.basic_pay_trainee) AS basic_pay_trainee, 
                        per.gl_code_basic_pay_trainee,

                        s.sum_allowances / NULLIF(s.active_count, 0) AS allowances_avg,
                        SUM(per.allowances) AS allowances, 
                        per.gl_code_allowances, 

                        s.sum_bm_allowance / NULLIF(s.active_count, 0) AS bm_allowance_avg,
                        SUM(per.bm_allowance) AS bm_allowance, 
                        per.gl_code_bm_allowance, 

                        s.sum_overtime_regular / NULLIF(s.active_count, 0) AS overtime_regular_avg,
                        SUM(per.overtime_regular) AS overtime_regular, 
                        per.gl_code_overtime_regular, 

                        s.sum_overtime_trainee / NULLIF(s.active_count, 0) AS overtime_trainee_avg,
                        SUM(per.overtime_trainee) AS overtime_trainee, 
                        per.gl_code_overtime_trainee, 

                        s.sum_cola / NULLIF(s.active_count, 0) AS cola_avg,
                        SUM(per.cola) AS cola, 
                        per.gl_code_cola, 

                        s.sum_excess_pb / NULLIF(s.active_count, 0) AS excess_pb_avg,
                        SUM(per.excess_pb) AS excess_pb, 
                        per.gl_code_excess_pb, 

                        s.sum_other_income / NULLIF(s.active_count, 0) AS other_income_avg,
                        SUM(per.other_income) AS other_income, 
                        per.gl_code_other_income, 

                        s.sum_salary_adjustment / NULLIF(s.active_count, 0) AS salary_adjustment_avg,
                        SUM(per.salary_adjustment) AS salary_adjustment, 
                        per.gl_code_salary_adjustment, 

                        s.sum_graveyard / NULLIF(s.active_count, 0) AS graveyard_avg,
                        SUM(per.graveyard) AS graveyard, 
                        per.gl_code_graveyard, 

                        s.sum_late_regular / NULLIF(s.active_count, 0) AS late_regular_avg,
                        SUM(per.late_regular) AS late_regular, 
                        per.gl_code_late_regular, 

                        s.sum_late_trainee / NULLIF(s.active_count, 0) AS late_trainee_avg,
                        SUM(per.late_trainee) AS late_trainee, 
                        per.gl_code_late_trainee, 

                        s.sum_leave_regular / NULLIF(s.active_count, 0) AS leave_regular_avg,
                        SUM(per.leave_regular) AS leave_regular, 
                        per.gl_code_leave_regular, 

                        s.sum_leave_trainee / NULLIF(s.active_count, 0) AS leave_trainee_avg,
                        SUM(per.leave_trainee) AS leave_trainee, 
                        per.gl_code_leave_trainee, 

                        s.sum_all_other_deductions / NULLIF(s.active_count, 0) AS all_other_deductions_avg,
                        SUM(per.all_other_deductions) AS all_other_deductions, 
                        per.gl_code_all_other_deductions, 

                        s.sum_total / NULLIF(s.active_count, 0) AS total_avg,
                        SUM(per.total) AS total, 
                        per.gl_code_total, 

                        per.cost_center, 
                        SUM(per.no_of_branch_employee) AS no_of_branch_employee, 
                        SUM(per.no_of_employees_allocated) AS no_of_employees_allocated

                    FROM " . $database[0] . ".payroll_edi_report per
                    INNER JOIN summarized s ON s.region_code = per.region_code";

                    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                        $dlsql .= " WHERE per.mainzone = '$mainzone'
                        AND per.ml_matic_region = '$zone'
                        AND per.zone like '%$region%'";
                    }else{
                        $dlsql .= " WHERE per.mainzone = '$mainzone'
                            AND per.zone = '$zone'
                            AND per.region_code LIKE '%$region%'
                            AND per.ml_matic_region != 'LNCR Showroom'
                            AND per.ml_matic_region != 'VISMIN Showroom'";      
                    }

                    if (!empty($branch)) {
                        $dlsql .= " AND per.branch_code = $branch";
                    }
                    if ($startDate === $endDate) {
                        $dlsql .= " AND per.payroll_date = '$startDate'";
                    } else {
                        $dlsql .= " AND per.payroll_date BETWEEN '$startDate' AND '$endDate'";
                    }
                    
                    $dlsql .= " AND NOT per.ml_matic_status IN ('Pending', 'Inactive')
                        GROUP BY 
                            per.region_code, per.mainzone, per.zone, per.payroll_date,
                            per.region, per.ml_matic_region, per.kp_code, per.ml_matic_status,
                            per.branch_code, per.branch_name,
                            per.gl_code_basic_pay_regular, per.gl_code_basic_pay_trainee,
                            per.gl_code_allowances, per.gl_code_bm_allowance,
                            per.gl_code_overtime_regular, per.gl_code_overtime_trainee,
                            per.gl_code_cola, per.gl_code_excess_pb, per.gl_code_other_income,
                            per.gl_code_salary_adjustment, per.gl_code_graveyard,
                            per.gl_code_late_regular, per.gl_code_late_trainee,
                            per.gl_code_leave_regular, per.gl_code_leave_trainee,
                            per.gl_code_all_other_deductions, per.gl_code_total, per.cost_center
                    ";
                    
            $dlresult = mysqli_query($conn, $dlsql);
                    
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
                    
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

            // First row
            if(!empty($branch) && !empty($region)){
                $sheet->setCellValue('A1', 'Payroll Summary - Per Branch & Per Region')->mergeCells('A1:C2')->getStyle('A1:C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }else{
                $sheet->setCellValue('A1', 'Payroll Summary - All Branches & All Regions')->mergeCells('A1:C2')->getStyle('A1:C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }

            $sheet->setCellValue('D1','Basic Pay')->mergeCells('D1:E1')->getStyle('D1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('F1','Allowances')->mergeCells('F1:F2')->getStyle('F1:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('G1','BM Allowance')->mergeCells('G1:G2')->getStyle('G1:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('H1','Overtime')->mergeCells('H1:I1')->getStyle('H1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('J1','COLA')->mergeCells('J1:J2')->getStyle('J1:J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('K1','Excess PB')->mergeCells('K1:K2')->getStyle('K1:K2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('L1','Other Income')->mergeCells('L1:L2')->getStyle('L1:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('M1','Salary Adjustment')->mergeCells('M1:M2')->getStyle('M1:M2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('N1','Graveyard')->mergeCells('N1:N2')->getStyle('N1:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet->setCellValue('O1','Late')->mergeCells('O1:P1')->getStyle('O1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('Q1','Leave')->mergeCells('Q1:R1')->getStyle('Q1:R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('S1','Total')->mergeCells('S1:S2')->getStyle('S1:S2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('T1','Cost Center')->mergeCells('T1:T4')->getStyle('T1:T4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('U1','No. of Branches Employees')->mergeCells('U1:U4')->getStyle('U1:U4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('V1','No. of Employees Allocated')->mergeCells('V1:V4')->getStyle('V1:V4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Second row
            $sheet->setCellValue('D2','Regular')->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('E2','Trainee')->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('H2','Regular')->getStyle('H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('I2','Trainee')->getStyle('I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O2','Regular')->getStyle('O2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('P2','Trainee')->getStyle('P2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('Q2','Regular')->getStyle('Q2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('R2','Trainee')->getStyle('R2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Third row
            if($startDate === $endDate){
                $sheet->setCellValue('A3', 'Payroll Date: ' . $payroll_date)->mergeCells('A3:C3')->getStyle('A3:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }else{
                $sheet->setCellValue('A3', 'Payroll Date: ' . $startDate . " to " . $endDate)->mergeCells('A3:C3')->getStyle('A3:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }
            $headerRow3 = [
                    'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                    'credit', 'credit', 'credit', 'credit','credit', 'credit'
                ];
            $sheet->fromArray($headerRow3, NULL, 'D3');

            // Fourth row
            $sheet->setCellValue('A4', 'Date')->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('B4', 'BOS Code')->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('C4', 'Branch Name')->getStyle('C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            
            $headerRow4 = [
                $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
                $gl_code_cola, $gl_code_excess_pb, $gl_code_other_income, $gl_code_salary_adjustment, $gl_code_graveyard, $gl_code_late_regular, $gl_code_late_trainee,
                $gl_code_leave_regular, $gl_code_leave_trainee,$gl_code_total
            ];

            $sheet->fromArray($headerRow4, NULL, 'D4');

            foreach(range('A', 'Z') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $rowIndex = 5;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;

            // Fifth row
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
                $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'] + $row['all_other_deductions'];
                $total = $totalDebit - $totalCredit;
                    
                $sheet->setCellValue('A' . $rowIndex, $row['payroll_date']);
                $sheet->setCellValue('B' . $rowIndex, $row['branch_code']);
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
                                    
                $sheet->setCellValueExplicit('H' . $rowIndex, $row['overtime_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
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
                $sheet->setCellValue('U' . $rowIndex, $row['no_of_branch_employee']);
                $sheet->setCellValue('V' . $rowIndex, $row['no_of_employees_allocated']);
                
                // $sheet->setCellValue('U' . $rowIndex, $row['region']);
                        
                if ($applyStyle) {
                    $sheet->getStyle('A' . $rowIndex . ':V' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);
                }
                                
                // Apply bold style regardless of background color
                $sheet->getStyle('A' . $rowIndex . ':V' . $rowIndex)->getFont()->setBold($bold);
                    
                $rowIndex++;
            }
            $_SESSION['rowIndex'] = $rowIndex;
            $rowIndex1 = $_SESSION['rowIndex'];

            if( $first_row['basic_pay_regular_avg'] != 0 ||  $first_row['basic_pay_trainee_avg'] != 0 ||  $first_row['allowances_avg'] != 0 ||  $first_row['bm_allowance_avg'] != 0 ||  $first_row['overtime_regular_avg'] != 0 ||  $first_row['overtime_trainee_avg'] != 0 ||  $first_row['cola_avg'] != 0 ||  $first_row['excess_pb_avg'] != 0 ||  $first_row['other_income_avg'] != 0 ||  $first_row['salary_adjustment_avg'] != 0 ||  $first_row['graveyard_avg'] != 0 ||  $first_row['late_regular_avg'] != 0 ||  $first_row['late_trainee_avg'] != 0 ||  $first_row['leave_regular_avg'] != 0 ||  $first_row['leave_trainee_avg'] != 0 ||  $first_row['all_other_deductions_avg'] != 0 ||  $first_row['total_avg'] != 0) {
                // Seventh row
                if(!empty($branch) && !empty($region)){
                    $sheet->setCellValue('A'.($rowIndex1+1), 'Allocated amount from closed branch')->mergeCells('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getStyle('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }else{
                    $sheet->setCellValue('A'.($rowIndex1+1), 'Allocated amount from closed branch')->mergeCells('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getStyle('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }

                $sheet->setCellValue('D'.($rowIndex1+1),'Basic Pay')->mergeCells('D'.($rowIndex1+1).':E'.($rowIndex1+1))->getStyle('D'.($rowIndex1+1).':E'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('F'.($rowIndex1+1),'Allowances')->mergeCells('F'.($rowIndex1+1).':F'.($rowIndex1+2))->getStyle('F'.($rowIndex1+1).':F'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('G'.($rowIndex1+1),'BM Allowance')->mergeCells('G'.($rowIndex1+1).':G'.($rowIndex1+2))->getStyle('G'.($rowIndex1+1).':G'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue('H'.($rowIndex1+1),'Overtime')->mergeCells('H'.($rowIndex1+1).':I'.($rowIndex1+1))->getStyle('H'.($rowIndex1+1).':I'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('J'.($rowIndex1+1),'COLA')->mergeCells('J'.($rowIndex1+1).':J'.($rowIndex1+2))->getStyle('J'.($rowIndex1+1).':J'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('K'.($rowIndex1+1),'Excess PB')->mergeCells('K'.($rowIndex1+1).':K'.($rowIndex1+2))->getStyle('K'.($rowIndex1+1).':K'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('L'.($rowIndex1+1),'Other Income')->mergeCells('L'.($rowIndex1+1).':L'.($rowIndex1+2))->getStyle('L'.($rowIndex1+1).':L'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('M'.($rowIndex1+1),'Salary Adjustment')->mergeCells('M'.($rowIndex1+1).':M'.($rowIndex1+2))->getStyle('M'.($rowIndex1+1).':M'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('N'.($rowIndex1+1),'Graveyard')->mergeCells('N'.($rowIndex1+1).':N'.($rowIndex1+2))->getStyle('N'.($rowIndex1+1).':N'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet->setCellValue('O'.($rowIndex1+1),'Late')->mergeCells('O'.($rowIndex1+1).':P'.($rowIndex1+1))->getStyle('O'.($rowIndex1+1).':P'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('Q'.($rowIndex1+1),'Leave')->mergeCells('Q'.($rowIndex1+1).':R'.($rowIndex1+1))->getStyle('Q'.($rowIndex1+1).':R'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('S'.($rowIndex1+1),'Total')->mergeCells('S'.($rowIndex1+1).':S'.($rowIndex1+2))->getStyle('S'.($rowIndex1+1).':S'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue('T'.($rowIndex1+1),'Cost Center')->mergeCells('T'.($rowIndex1+1).':T'.($rowIndex1+4))->getStyle('T'.($rowIndex1+1).':T'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('U'.($rowIndex1+1),'No. of Branches Employees')->mergeCells('U'.($rowIndex1+1).':U'.($rowIndex1+4))->getStyle('U'.($rowIndex1+1).':U'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('V'.($rowIndex1+1),'No. of Employees Allocated')->mergeCells('V'.($rowIndex1+1).':V'.($rowIndex1+4))->getStyle('V'.($rowIndex1+1).':V'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Eighth row
                $sheet->setCellValue('D'.($rowIndex1+2),'Regular')->getStyle('D'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('E'.($rowIndex1+2),'Trainee')->getStyle('E'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('H'.($rowIndex1+2),'Regular')->getStyle('H'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('I'.($rowIndex1+2),'Trainee')->getStyle('I'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('O'.($rowIndex1+2),'Regular')->getStyle('O'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('P'.($rowIndex1+2),'Trainee')->getStyle('P'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('Q'.($rowIndex1+2),'Regular')->getStyle('Q'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('R'.($rowIndex1+2),'Trainee')->getStyle('R'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Nineth row
                if($startDate === $endDate){
                    $sheet->setCellValue('A'.($rowIndex1+3), 'Payroll Date: ' . $payroll_date)->mergeCells('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getStyle('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }else{
                    $sheet->setCellValue('A'.($rowIndex1+3), 'Payroll Date: ' . $startDate . " to " . $endDate)->mergeCells('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getStyle('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }
                $headerRow3 = [
                        'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                        'credit', 'credit', 'credit', 'credit','credit', 'credit'
                    ];
                $sheet->fromArray($headerRow3, NULL, 'D'.($rowIndex1+3));

                // Tenth row
                $sheet->setCellValue('A'.($rowIndex1+4), 'Date')->getStyle('A'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('B'.($rowIndex1+4), 'BOS Code')->getStyle('B'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('C'.($rowIndex1+4), 'Branch Name')->getStyle('C'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $headerRow4 = [
                    $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
                    $gl_code_cola, $gl_code_excess_pb, $gl_code_other_income, $gl_code_salary_adjustment, $gl_code_graveyard, $gl_code_late_regular, $gl_code_late_trainee,
                    $gl_code_leave_regular, $gl_code_leave_trainee,$gl_code_total
                ];

                $sheet->fromArray($headerRow4, NULL, 'D'.($rowIndex1+4));

                foreach(range('A', 'Z') as $columnID1) {
                    $sheet->getColumnDimension($columnID1)->setAutoSize(true);
                }

                $rowIndex2 = $rowIndex1 + 5;
                $total1 = 0;
                $totalDebit1 = 0;
                $totalCredit1 = 0;

                // Eleventh row
                mysqli_data_seek($dlresult, 0);
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    // Only write rows where at least one *_avg column is not zero
                    if (
                        $row['basic_pay_regular_avg'] != 0 ||
                        $row['basic_pay_trainee_avg'] != 0 ||
                        $row['allowances_avg'] != 0 ||
                        $row['bm_allowance_avg'] != 0 ||
                        $row['overtime_regular_avg'] != 0 ||
                        $row['overtime_trainee_avg'] != 0 ||
                        $row['cola_avg'] != 0 ||
                        $row['excess_pb_avg'] != 0 ||
                        $row['other_income_avg'] != 0 ||
                        $row['salary_adjustment_avg'] != 0 ||
                        $row['graveyard_avg'] != 0 ||
                        $row['late_regular_avg'] != 0 ||
                        $row['late_trainee_avg'] != 0 ||
                        $row['leave_regular_avg'] != 0 ||
                        $row['leave_trainee_avg'] != 0 ||
                        $row['all_other_deductions_avg'] != 0 ||
                        $row['total_avg'] != 0
                    ) {
                        $applyStyle = false; 

                        if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                            $color = '4fc917';  
                            $bold = true;       
                            $applyStyle = true; 
                        } else {
                            $bold = false;      
                        }

                        $totalDebit1 = $row['basic_pay_regular_avg'] + $row['basic_pay_trainee_avg'] + $row['allowances_avg'] + $row['bm_allowance_avg'] + $row['overtime_regular_avg'] 
                                        + $row['overtime_trainee_avg'] + $row['cola_avg'] + $row['excess_pb_avg'] + $row['other_income_avg'] + $row['salary_adjustment_avg'] + $row['graveyard_avg'];
                        $totalCredit1 = $row['late_regular_avg'] + $row['late_trainee_avg'] + $row['leave_regular_avg'] + $row['leave_trainee_avg'] + $row['all_other_deductions_avg'];
                        $total1 = $totalDebit1 - $totalCredit1;
                            
                        $sheet->setCellValue('A' . $rowIndex2, $row['payroll_date']);
                        $sheet->setCellValue('B' . $rowIndex2, $row['branch_code']);
                        $sheet->setCellValue('C' . $rowIndex2, $row['branch_name']);
                            
                        // Use setCellValueExplicit for setting the value and format it as a number
                        $sheet->setCellValueExplicit('D' . $rowIndex2, $row['basic_pay_regular_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('D' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                        $sheet->setCellValueExplicit('E' . $rowIndex2, $row['basic_pay_trainee_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('E' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('F' . $rowIndex2, $row['allowances_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('F' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('G' . $rowIndex2, $row['bm_allowance_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('G' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('H' . $rowIndex2, $row['overtime_regular_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('H' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('I' . $rowIndex2, $row['overtime_trainee_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('I' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('J' . $rowIndex2, $row['cola_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('J' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('K' . $rowIndex2, $row['excess_pb_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('K' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('L' . $rowIndex2, $row['other_income_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('L' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('M' . $rowIndex2, $row['salary_adjustment_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('M' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('N' . $rowIndex2, $row['graveyard_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('N' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        // convert to negative if positive value 
                        $sheet->setCellValueExplicit('O' . $rowIndex2, ($row['late_regular_avg'] > 0 ? -$row['late_regular_avg'] : $row['late_regular_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('O' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('P' . $rowIndex2, ($row['late_trainee_avg'] > 0 ? -$row['late_trainee_avg'] : $row['late_trainee_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('P' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('Q' . $rowIndex2, ($row['leave_regular_avg'] > 0 ? -$row['leave_regular_avg'] : $row['leave_regular_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('Q' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('R' . $rowIndex2, ($row['leave_trainee_avg'] > 0 ? -$row['leave_trainee_avg'] : $row['leave_trainee_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('R' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('S' . $rowIndex2, $total1, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('S' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValue('T' . $rowIndex2, $row['cost_center']);
                        $sheet->setCellValue('U' . $rowIndex2, $row['no_of_branch_employee']);
                        $sheet->setCellValue('V' . $rowIndex2, $row['no_of_employees_allocated']);

                        if ($applyStyle) {
                            $sheet->getStyle('A' . $rowIndex2 . ':V' . $rowIndex2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                        }
                                    
                        // Apply bold style regardless of background color
                        $sheet->getStyle('A' . $rowIndex2 . ':V' . $rowIndex2)->getFont()->setBold($bold);
                        
                        $rowIndex2++;
                    }
                }
            }
            

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

			if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                if(!empty($region)){
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $region . "_" . $startDate . ".xls";
                    }else{
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $region . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                }else{
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $startDate . ".xls";
                    }else{
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                }
                    
            }else{
                if(!empty($region)){
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $zone .  "_" . $region . "_" . $startDate . ".xls";
                    }else {
                        $filename = "EDI_Summary_Payroll_Report_" . $zone .  "_" . $region . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                } else {
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $zone . "_" . $startDate . ".xls";
                    }else{
                        $filename = "EDI_Summary_Payroll_Report_" . $zone . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                    
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

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="../../../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../../../assets/css/admin/payroll-summary-report/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>

<body>

    <div class="top-content">
        <?php include '../../../templates/sidebar.php' ?>
    </div>

    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div>
        <center>
            <h3>Payroll Summary & Detailed Report</h3>
        </center>
    </div>

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
                <select name="region" id="region" autocomplete="off" onchange="updateBranches()">
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
                <label for="branch">Branch Name</label>
                <select name="branch" id="branch" autocomplete="off">
                    <option value="">Select Branch</option>
                    <!-- Branches will be populated dynamically by JavaScript -->
                    <?php
                        // If a branch is selected, display it after the page reloads
                        if (isset($_POST['branch'])) {
                            echo '<option value="' . htmlspecialchars($_POST['branch']) . '" selected>' . htmlspecialchars($_POST['branch']) . '</option>';
                        }
                    ?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">FROM </label>
                <input type="date" id="restricted-date" name="startdate" value="<?php echo isset($_POST['startdate']) ? $_POST['startdate'] : '';?>" required> 
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date1">TO </label>
                <input type="date" id="restricted-date1" name="enddate" value="<?php echo isset($_POST['enddate']) ? $_POST['enddate'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl" style="display: none">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>
    </div>

    <script src="../../../assets/js/admin/payroll-summary-report/summary-detailed-format/script1.js"></script>
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
        $zone = $_POST['zone'];
        $region = $_POST['region'];
        $branch = $_POST['branch'];
        $startDate = $_POST['startdate'];
        $endDate = $_POST['enddate'];

        $_SESSION['mainzone'] = $mainzone;
        $_SESSION['zone'] = $zone;
        $_SESSION['region'] = $region;
        $_SESSION['branch'] = $branch;
        $_SESSION['startdate'] = $startDate; 
        $_SESSION['enddate'] = $endDate;

        if ($startDate > $endDate) {
            // Swap dates
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }
                    
            $sql = "WITH summarized AS (
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
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total ELSE 0 END) AS sum_total,
                            COUNT(CASE WHEN ml_matic_status = 'Active' THEN 1 ELSE NULL END) AS active_count
                        FROM " . $database[0] . ".payroll_edi_report";
                        if ($startDate === $endDate) {
                            $sql .= " WHERE payroll_date = '$startDate'";
                        } else {
                            $sql .= " WHERE payroll_date BETWEEN '$startDate' AND '$endDate'";
                        }
                            $sql .= " GROUP BY region_code
                    )

                    SELECT 
                        per.payroll_date, 
                        per.mainzone, 
                        per.zone, 
                        per.region, 
                        per.ml_matic_region, 
                        per.region_code, 
                        per.kp_code, 
                        per.ml_matic_status, 
                        per.branch_code, 
                        per.branch_name, 

                        s.sum_basic_pay_regular / NULLIF(s.active_count, 0) AS basic_pay_regular_avg,
                        SUM(per.basic_pay_regular) AS basic_pay_regular, 
                        per.gl_code_basic_pay_regular, 

                        s.sum_basic_pay_trainee / NULLIF(s.active_count, 0) AS basic_pay_trainee_avg,
                        SUM(per.basic_pay_trainee) AS basic_pay_trainee, 
                        per.gl_code_basic_pay_trainee,

                        s.sum_allowances / NULLIF(s.active_count, 0) AS allowances_avg,
                        SUM(per.allowances) AS allowances, 
                        per.gl_code_allowances, 

                        s.sum_bm_allowance / NULLIF(s.active_count, 0) AS bm_allowance_avg,
                        SUM(per.bm_allowance) AS bm_allowance, 
                        per.gl_code_bm_allowance, 

                        s.sum_overtime_regular / NULLIF(s.active_count, 0) AS overtime_regular_avg,
                        SUM(per.overtime_regular) AS overtime_regular, 
                        per.gl_code_overtime_regular, 

                        s.sum_overtime_trainee / NULLIF(s.active_count, 0) AS overtime_trainee_avg,
                        SUM(per.overtime_trainee) AS overtime_trainee, 
                        per.gl_code_overtime_trainee, 

                        s.sum_cola / NULLIF(s.active_count, 0) AS cola_avg,
                        SUM(per.cola) AS cola, 
                        per.gl_code_cola, 

                        s.sum_excess_pb / NULLIF(s.active_count, 0) AS excess_pb_avg,
                        SUM(per.excess_pb) AS excess_pb, 
                        per.gl_code_excess_pb, 

                        s.sum_other_income / NULLIF(s.active_count, 0) AS other_income_avg,
                        SUM(per.other_income) AS other_income, 
                        per.gl_code_other_income, 

                        s.sum_salary_adjustment / NULLIF(s.active_count, 0) AS salary_adjustment_avg,
                        SUM(per.salary_adjustment) AS salary_adjustment, 
                        per.gl_code_salary_adjustment, 

                        s.sum_graveyard / NULLIF(s.active_count, 0) AS graveyard_avg,
                        SUM(per.graveyard) AS graveyard, 
                        per.gl_code_graveyard, 

                        s.sum_late_regular / NULLIF(s.active_count, 0) AS late_regular_avg,
                        SUM(per.late_regular) AS late_regular, 
                        per.gl_code_late_regular, 

                        s.sum_late_trainee / NULLIF(s.active_count, 0) AS late_trainee_avg,
                        SUM(per.late_trainee) AS late_trainee, 
                        per.gl_code_late_trainee, 

                        s.sum_leave_regular / NULLIF(s.active_count, 0) AS leave_regular_avg,
                        SUM(per.leave_regular) AS leave_regular, 
                        per.gl_code_leave_regular, 

                        s.sum_leave_trainee / NULLIF(s.active_count, 0) AS leave_trainee_avg,
                        SUM(per.leave_trainee) AS leave_trainee, 
                        per.gl_code_leave_trainee, 

                        s.sum_all_other_deductions / NULLIF(s.active_count, 0) AS all_other_deductions_avg,
                        SUM(per.all_other_deductions) AS all_other_deductions, 
                        per.gl_code_all_other_deductions, 

                        s.sum_total / NULLIF(s.active_count, 0) AS total_avg,
                        SUM(per.total) AS total, 
                        per.gl_code_total, 

                        per.cost_center, 
                        SUM(per.no_of_branch_employee) AS no_of_branch_employee, 
                        SUM(per.no_of_employees_allocated) AS no_of_employees_allocated

                    FROM " . $database[0] . ".payroll_edi_report per
                    INNER JOIN summarized s ON s.region_code = per.region_code";
                    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                        $sql .= " WHERE per.mainzone = '$mainzone'
                            AND per.ml_matic_region LIKE '%$zone%'
                            AND per.zone LIKE '%$region%'";
                    } else {
                        $sql .= " WHERE per.mainzone = '$mainzone'
                            AND per.zone = '$zone'
                            AND per.region_code LIKE '%$region%'
                            AND per.ml_matic_region != 'LNCR Showroom'
                            AND per.ml_matic_region != 'VISMIN Showroom'";
                            
                    }

                    if (!empty($branch)) {
                        $sql .= " AND per.branch_code = $branch";
                    }
                    if ($startDate === $endDate) {
                        $sql .= " AND per.payroll_date = '$startDate'";
                    } else {
                        $sql .= " AND per.payroll_date BETWEEN '$startDate' AND '$endDate'";
                    }

                    $sql .= " AND NOT per.ml_matic_status IN ('Pending', 'Inactive')
                        GROUP BY 
                            per.region_code, per.mainzone, per.zone, per.payroll_date,
                            per.region, per.ml_matic_region, per.kp_code, per.ml_matic_status,
                            per.branch_code, per.branch_name,
                            per.gl_code_basic_pay_regular, per.gl_code_basic_pay_trainee,
                            per.gl_code_allowances, per.gl_code_bm_allowance,
                            per.gl_code_overtime_regular, per.gl_code_overtime_trainee,
                            per.gl_code_cola, per.gl_code_excess_pb, per.gl_code_other_income,
                            per.gl_code_salary_adjustment, per.gl_code_graveyard,
                            per.gl_code_late_regular, per.gl_code_late_trainee,
                            per.gl_code_leave_regular, per.gl_code_leave_trainee,
                            per.gl_code_all_other_deductions, per.gl_code_total, per.cost_center
                    ";

        

        //echo $sql;
        $result = mysqli_query($conn, $sql);

         // Check if there are results
         if (mysqli_num_rows($result) > 0) {

            $totalNumberOfBranches = 0;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;

            echo "<div id='showBranches' style='display: block; position: absolute; top: 190px; color: red; left: 20px;'>";
            echo "Total Number of Branches : " . ($_SESSION['totalNumberOfBranches'] ?? 0);
            echo "</div>";
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
            echo "<th colspan='3'>Payroll Summary ";
            if(!empty($branch) && !empty($region)){
                echo " - Per Branch & Per Region";

            }else{
                echo " - All Branches & All Regions";
            }
            "</th>";
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
            if ($startDate === $endDate) {
                echo "<th colspan='3'>Payroll Date - " . $payroll_date . "</th>";
            }else{
                echo "<th colspan='3'>Payroll Date - " . $startDate . " to " . $endDate . "</th>";
            }
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
            echo "<th style='white-space: nowrap'>Date</th>";
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
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['payroll_date']) . "</td>";
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
            $_SESSION['totalNumberOfBranches'] = $totalNumberOfBranches;
            

            echo "<script>
            
            var dlbutton = document.getElementById('showdl');
            dlbutton.style.display = 'block';
            
            </script>";
			
			echo "<script>
            
            var dlbutton = document.getElementById('showdl1');
            dlbutton.style.display = 'block';
            
            </script>";

            

            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            // Collect allocated rows first
            $allocatedRows = [];
            mysqli_data_seek($result, 0); // Reset pointer
            while ($row = mysqli_fetch_assoc($result)) {
                // Check if any *_avg column is non-zero
                if (
                    $row['basic_pay_regular_avg'] != 0 ||
                    $row['basic_pay_trainee_avg'] != 0 ||
                    $row['allowances_avg'] != 0 ||
                    $row['bm_allowance_avg'] != 0 ||
                    $row['overtime_regular_avg'] != 0 ||
                    $row['overtime_trainee_avg'] != 0 ||
                    $row['cola_avg'] != 0 ||
                    $row['excess_pb_avg'] != 0 ||
                    $row['other_income_avg'] != 0 ||
                    $row['salary_adjustment_avg'] != 0 ||
                    $row['graveyard_avg'] != 0 ||
                    $row['late_regular_avg'] != 0 ||
                    $row['late_trainee_avg'] != 0 ||
                    $row['leave_regular_avg'] != 0 ||
                    $row['leave_trainee_avg'] != 0 ||
                    $row['all_other_deductions_avg'] != 0 ||
                    $row['total_avg'] != 0
                ) {
                    $allocatedRows[] = $row;
                }
            }

            // Only display the allocated table if there is at least one non-zero row
            if (count($allocatedRows) > 0) {
                echo "<div class='table-container'>";
                echo "<table>";
                echo "<thead>";
                //  first row
                echo "<tr>";
                echo "<th colspan='3'>Allocated amount from closed branch</th>";
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
                if ($startDate === $endDate) {
                    echo "<th colspan='3'>Payroll Date - " . $payroll_date . "</th>";
                } else {
                    echo "<th colspan='3'>Payroll Date - " . $startDate . " to " . $endDate . "</th>";
                }
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
                echo "<th style='white-space: nowrap'>Date</th>";
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

                foreach ($allocatedRows as $row) {
                    if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                        $color = '#4fc917';
                        $bold = 'bold';
                    } else {
                        $color = 'none';
                        $bold = 'normal';
                    }

                    $totalDebit = $row['basic_pay_regular_avg'] + $row['basic_pay_trainee_avg'] + $row['allowances_avg'] + $row['bm_allowance_avg'] + $row['overtime_regular_avg'] 
                                + $row['overtime_trainee_avg'] + $row['cola_avg'] + $row['excess_pb_avg'] + $row['other_income_avg'] + $row['salary_adjustment_avg'] + $row['graveyard_avg'];
                    $totalCredit = $row['late_regular_avg'] + $row['late_trainee_avg'] + $row['leave_regular_avg'] + $row['leave_trainee_avg'];
                    $total = $totalDebit - $totalCredit;

                    echo "<tr>";
                    echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['payroll_date']) . "</td>";
                    echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_code']) . "</td>";
                    echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['basic_pay_regular_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['basic_pay_trainee_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['allowances_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['bm_allowance_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['overtime_regular_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['overtime_trainee_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['cola_avg'], 2)) . "</td>"; 
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['excess_pb_avg'], 2)) . "</td>"; 
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['other_income_avg'], 2)) . "</td>"; 
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['salary_adjustment_avg'], 2)) . "</td>"; 
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['graveyard_avg'], 2)) . "</td>";
                    // convert to negative if positive value 
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['late_regular_avg'] > 0 ? -$row['late_regular_avg'] : $row['late_regular_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['late_trainee_avg'] > 0 ? -$row['late_trainee_avg'] : $row['late_trainee_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['leave_regular_avg'] > 0 ? -$row['leave_regular_avg'] : $row['leave_regular_avg'], 2)) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['leave_trainee_avg'] > 0 ? -$row['leave_trainee_avg'] : $row['leave_trainee_avg'], 2)) . "</td>";

                    echo "<td style='background-color: $color; font-weight: $bold'> ".htmlspecialchars(number_format($total, 2))." </td>"; 
                    echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
                    echo "<td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>";
                    echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['all_other_deductions_avg'], 2)) . "</td>"; 
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_branch_employee']) . "</td>";
                    echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_employees_allocated']) . "</td>";
                    echo "</tr>";
                    
                }
                echo "</tbody>";
                echo "</table>";
                echo "</div>";
            
        }

        // Close the connection
        mysqli_close($conn);
    }
}

?>