<?php
    require '../vendor/autoload.php';
    include '../config/connection.php';
    
    session_start();

    // ini_set('display_errors',1);
    // error_reporting(E_ALL);
    // mysqli_report(MYSQLI_REPORT_ERROR | E_DEPRECATED | E_STRICT);
    // error_reporting(0);
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'admin') {
        header('location: ../login.php');
    }
    
    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $date = $_SESSION['restricted-date'] ?? '';
        $payrollDay = $_SESSION['payroll_day'] ?? '';

        generateDownload($conn, $database, $mainzone, $region, $date, $payrollDay);
    }
    
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $region, $date, $payrollDay) {

        // Initialize grand total variables
        $grand_total_amount = 0;
        $grand_total_income = 0;
        $grand_total_deduction = 0;
        $grand_total_net_pay = 0;
        $grand_total_variance = 0;

        $mainzone = $_SESSION['mainzone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $date = $_SESSION['restricted-date'] ?? '';
        $payrollDay = $_SESSION['payroll_day'] ?? '';

        $payrollquery = "SELECT
                            mzm.main_zone_code,
                            rm.region_code, 
                            rm.region_description AS region_name, 
                            rm.zone_code,
                            
                            -- Mcash data
                            MAX(mc.mlwallet_amount + mc.mlkp_amount) AS mcash_total_amount,
                            
                            -- Payroll data
                            SUM(p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                                p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                                p.other_income + p.salary_adjustment + p.graveyard) AS TOTAL_INCOME,
                            
                            SUM(p.all_other_deductions) AS TOTAL_DEDUCTION,
                            
                            -- Net Pay Calculation
                            SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                                p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                                p.other_income + p.salary_adjustment + p.graveyard) - 
                                (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)) 
                            AS TOTAL_NET_PAY,
                            
                            -- Variance Calculation
                            MAX(mc.mlwallet_amount + mc.mlkp_amount) -
                            SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                                p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                                p.other_income + p.salary_adjustment + p.graveyard) - 
                                (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions))
                            AS VARIANCE
                        FROM " . $database[1] . ".main_zone_masterfile AS mzm
                        JOIN " . $database[1] . ".region_masterfile AS rm ON 
                            ((rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN') OR 
                            (rm.zone_code IN ('NCR', 'LZN') AND mzm.main_zone_code = 'LNCR'))
                        LEFT JOIN " . $database[0] . ".mcash AS mc ON rm.region_code = mc.region_code AND mc.mcash_date = '$date'
                        LEFT JOIN " . $database[0] . ".payroll AS p ON rm.region_code = p.region_code AND p.payroll_date = '$date'
                        WHERE mzm.main_zone_code = '$mainzone'";
        
        if (!empty($region)) {
            $payrollquery .= " AND rm.region_code = '$region'";
        }
        
        $payrollquery .= " GROUP BY mzm.main_zone_code, rm.region_code, rm.region_description, rm.zone_code
                            ORDER BY mzm.main_zone_code, rm.region_description;";

        // Execute query
        $payrollresult = mysqli_query($conn, $payrollquery);

        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        
        if(mysqli_num_rows($payrollresult) > 0) {
            while($first_row = mysqli_fetch_assoc($payrollresult)){
                $grand_total_amount += $first_row["mcash_total_amount"];
                $grand_total_income += $first_row["TOTAL_INCOME"];
                $grand_total_deduction += $first_row["TOTAL_DEDUCTION"];
                $grand_total_net_pay += $first_row["TOTAL_NET_PAY"];
                $grand_total_variance += $first_row["VARIANCE"];
            }
            // Reset the result pointer to the beginning
            mysqli_data_seek($payrollresult, 0);

            // First row header
            $sheet->setCellValue('A1', '('.$mainzone.')')->mergeCells('A1:I1')->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            
            // Second row header
            $sheet->setCellValue('A2', 'MCash Data')->mergeCells('A2:C2')->getStyle('A2:C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            if($mainzone ==='VISMIN') {
                $sheet->setCellValue('D2','')->mergeCells('D2:D34');
            }else{
                $sheet->setCellValue('D2','')->mergeCells('D2:D22');
            }

            $sheet->setCellValue('E2', 'HRMD PAYROLL ('.$payrollDay.') Data')->mergeCells('E2:G2')->getStyle('E2:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            if($mainzone ==='VISMIN') {
                $sheet->setCellValue('H2','')->mergeCells('H2:H34');
            }else{
                $sheet->setCellValue('H2','')->mergeCells('H2:H22');
            }

            $sheet->setCellValue('I2', 'VARIANCE')->mergeCells('I2:I3')->getStyle('I2:I3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        
            // Third row header
            $sheet->setCellValue('A3', 'REGION CODE')->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('B3', 'REGION NAME')->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('C3', 'TOTAL AMOUNT PER REGION')->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            //$sheet->setCellValue('D3','');
            $sheet->setCellValue('E3', 'TOTAL INCOME')->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('F3', 'TOTAL DEDUCTION')->getStyle('F3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('G3', 'TOTAL NET PAY')->getStyle('G3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            //$sheet->setCellValue('H3','');

            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A2:I2')->getFont()->setBold(true);
            $sheet->getStyle('A3:H3')->getFont()->setBold(true);

            if($mainzone ==='VISMIN') {
                $sheet->getStyle('A1:I34')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }else{
                $sheet->getStyle('A1:I22')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }

            foreach (range('A', 'C') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(0.83);
            foreach (range('E', 'G') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }
            $sheet->getColumnDimension('H')->setAutoSize(false)->setWidth(0.83);
            $sheet->getColumnDimension('I')->setAutoSize(true);

            mysqli_data_seek($payrollresult, 0);
            $row = 4; // Start from the fourth row for data

            // Fourth row Body Data
            while ($rowdata = mysqli_fetch_assoc($payrollresult)) {
                $sheet->setCellValue('A' . $row, $rowdata['region_code']);
                $sheet->setCellValue('B' . $row, $rowdata['region_name']);
                $sheet->setCellValue('C' . $row, $rowdata['mcash_total_amount']);
                $sheet->setCellValue('E' . $row, $rowdata['TOTAL_INCOME']);
                $sheet->setCellValue('F' . $row, $rowdata['TOTAL_DEDUCTION']);
                $sheet->setCellValue('G' . $row, $rowdata['TOTAL_NET_PAY']);
                $sheet->setCellValue('I' . $row, $rowdata['VARIANCE']);
                $row++;
            }
            
            // First row footer
            $sheet->setCellValue('A' . $row, 'GRAND TOTAL')->mergeCells('A' . $row . ':B' . $row)->getStyle('A' . $row . ':B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('C' . $row, $grand_total_amount);
            $sheet->setCellValue('E' . $row, $grand_total_income);
            $sheet->setCellValue('F' . $row, $grand_total_deduction);
            $sheet->setCellValue('G' . $row, $grand_total_net_pay);
            $sheet->setCellValue('I' . $row, $grand_total_variance);
            $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
           
            // Set headers to force download the Excel file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if(empty($region)){ 
                $filename = "MCash_VS_HRMD-PAYROLL_(".$payrollDay.")_". $mainzone ."_".$date.".xls";
            } else {
                $filename = "MCash_VS_HRMD-PAYROLL_(".$payrollDay.")_". $mainzone ."_".$region ."_".$date.".xls";
            }
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Write and save the Excel file
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
            exit;
        }

    }

    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

        $mainzone = $_POST['mainzone']?? '';
        $region = $_POST['region'] ?? '';
        $date = $_POST['restricted-date']?? '';

        $_SESSION['mainzone'] = $mainzone;
        $_SESSION['region'] = $region;
        $_SESSION['restricted-date'] = $date;

        if ($mainzone) {
            $payrollquery = "SELECT
                                mzm.main_zone_code,
                                rm.region_code, 
                                rm.region_description AS region_name, 
                                rm.zone_code,
                                
                                -- Mcash data
                                MAX(mc.mlwallet_amount + mc.mlkp_amount) AS mcash_total_amount,
                                
                                -- Payroll data
                                SUM(p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                                    p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                                    p.other_income + p.salary_adjustment + p.graveyard) AS TOTAL_INCOME,
                                
                                SUM(p.all_other_deductions) AS TOTAL_DEDUCTION,
                                
                                -- Net Pay Calculation
                                SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                                    p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                                    p.other_income + p.salary_adjustment + p.graveyard) - 
                                    (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)) 
                                AS TOTAL_NET_PAY,
                                
                                -- Variance Calculation
                                MAX(mc.mlwallet_amount + mc.mlkp_amount) -
                                SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                                    p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                                    p.other_income + p.salary_adjustment + p.graveyard) - 
                                    (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions))
                                AS VARIANCE
                            FROM " . $database[1] . ".main_zone_masterfile AS mzm
                            JOIN " . $database[1] . ".region_masterfile AS rm ON 
                                ((rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN') OR 
                                (rm.zone_code IN ('NCR', 'LZN') AND mzm.main_zone_code = 'LNCR'))
                            LEFT JOIN " . $database[0] . ".mcash AS mc ON rm.region_code = mc.region_code AND mc.mcash_date = '$date'
                            LEFT JOIN " . $database[0] . ".payroll AS p ON rm.region_code = p.region_code AND p.payroll_date = '$date'
                            WHERE mzm.main_zone_code = '$mainzone'";
            
            if (!empty($region)) {
                $payrollquery .= " AND rm.region_code = '$region'";
            }
            
            $payrollquery .= " GROUP BY mzm.main_zone_code, rm.region_code, rm.region_description, rm.zone_code
                                ORDER BY mzm.main_zone_code, rm.region_description;";
        }//elseif($mainzone==='ALL' && !empty($region)){
            // Display all records belonging to choosen mainzone for VISMIN or LNCR, however filter by region
            // $mcashquery = "SELECT * FROM mcash WHERE mcash_date = '$date'";
            // $payrollquery = "SELECT      
            //             p.payroll_date,     
            //             p.mainzone,    
            //             p.region,     
            //             p.region_code,     

            //             -- Total Income Calculation     
            //             SUM(  
            //                 p.basic_pay_regular +          
            //                 p.basic_pay_trainee +          
            //                 p.allowances +          
            //                 p.bm_allowance +          
            //                 p.overtime_regular +          
            //                 p.overtime_trainee +          
            //                 p.cola +          
            //                 p.excess_pb +          
            //                 p.other_income +          
            //                 p.salary_adjustment +          
            //                 p.graveyard
            //             ) AS TOTAL_INCOME,
                        
            //             SUM(p.all_other_deductions) as all_other_deductions,
                        
            //             -- Total Deductions     
            //             SUM(
            //                 p.late_regular +         
            //                 p.late_trainee +         
            //                 p.leave_regular +         
            //                 p.leave_trainee +
            //                 p.all_other_deductions
            //             ) AS TOTAL_DEDUCTION,     

            //             -- Net Pay Calculation     
            //             SUM(         
            //                 (             
            //                     p.basic_pay_regular +              
            //                     p.basic_pay_trainee +              
            //                     p.allowances +              
            //                     p.bm_allowance +              
            //                     p.overtime_regular +              
            //                     p.overtime_trainee +              
            //                     p.cola +              
            //                     p.excess_pb +              
            //                     p.other_income +              
            //                     p.salary_adjustment +              
            //                     p.graveyard         
            //                 ) -          
            //                 (             
            //                     p.late_regular +         
            //                     p.late_trainee +         
            //                     p.leave_regular +         
            //                     p.leave_trainee +
            //                     p.all_other_deductions         
            //                 )     
            //             ) AS TOTAL_NET_PAY

            //         FROM " . $database[0] . ".payroll p

            //         WHERE p.payroll_date = '$date'

            //         GROUP BY     
            //             p.payroll_date,
            //             p.mainzone,
            //             p.region,
            //             p.region_code;
            //     ";  
            // $payrollquery = "SELECT      
            //             p.payroll_date,     
            //             p.mainzone,    
            //             p.region,     
            //             p.region_code,     

            //             -- Total Income Calculation     
            //             SUM(         
            //                 COALESCE(p.basic_pay_regular, 0) +          
            //                 COALESCE(p.basic_pay_trainee, 0) +          
            //                 COALESCE(p.allowances, 0) +          
            //                 COALESCE(p.bm_allowance, 0) +          
            //                 COALESCE(p.overtime_regular, 0) +          
            //                 COALESCE(p.overtime_trainee, 0) +          
            //                 COALESCE(p.cola, 0) +          
            //                 COALESCE(p.excess_pb, 0) +          
            //                 COALESCE(p.other_income, 0) +          
            //                 COALESCE(p.salary_adjustment, 0) +          
            //                 COALESCE(p.graveyard, 0)     
            //             ) AS TOTAL_INCOME,
                        
            //             SUM(
            //                 COALESCE(o.income_tax, 0) +
            //                 COALESCE(o.sss_contribution, 0) + 
            //                 COALESCE(o.sss_loan, 0) +
            //                 COALESCE(o.pagibig_contribution, 0) +
            //                 COALESCE(o.pagibig_loan, 0) +
            //                 COALESCE(o.philhealth, 0) +
            //                 COALESCE(o.coated, 0) +
            //                 COALESCE(o.hmo, 0) +
            //                 COALESCE(o.canteen, 0) +
            //                 COALESCE(o.deduction_one, 0) +
            //                 COALESCE(o.deduction_two, 0) +
            //                 COALESCE(o.ml_fund, 0) +
            //                 COALESCE(o.opec, 0) +
            //                 COALESCE(o.over_appraisal, 0) +
            //                 COALESCE(o.vpo_collection, 0) +
            //                 COALESCE(o.installment_account, 0) +
            //                 COALESCE(o.ticket, 0) +
            //                 COALESCE(o.mobile_bill, 0) +
            //                 COALESCE(o.sako, 0) +
            //                 COALESCE(o.sako_savings, 0) 
            //             ) AS all_other_deductions,
                        
            //             -- Total Deductions     
            //             SUM(         
            //                 COALESCE(p.late_regular, 0) +         
            //                 COALESCE(p.late_trainee, 0) +         
            //                 COALESCE(p.leave_regular, 0) +         
            //                 COALESCE(p.leave_trainee, 0) +
            //                 COALESCE(o.income_tax, 0) +
            //                 COALESCE(o.sss_contribution, 0) + 
            //                 COALESCE(o.sss_loan, 0) +
            //                 COALESCE(o.pagibig_contribution, 0) +
            //                 COALESCE(o.pagibig_loan, 0) +
            //                 COALESCE(o.philhealth, 0) +
            //                 COALESCE(o.coated, 0) +
            //                 COALESCE(o.hmo, 0) +
            //                 COALESCE(o.canteen, 0) +
            //                 COALESCE(o.deduction_one, 0) +
            //                 COALESCE(o.deduction_two, 0) +
            //                 COALESCE(o.ml_fund, 0) +
            //                 COALESCE(o.opec, 0) +
            //                 COALESCE(o.over_appraisal, 0) +
            //                 COALESCE(o.vpo_collection, 0) +
            //                 COALESCE(o.installment_account, 0) +
            //                 COALESCE(o.ticket, 0) +
            //                 COALESCE(o.mobile_bill, 0) +
            //                 COALESCE(o.sako, 0) +
            //                 COALESCE(o.sako_savings, 0)     
            //             ) AS TOTAL_DEDUCTION,     

            //             -- Net Pay Calculation     
            //             SUM(         
            //                 (             
            //                     COALESCE(p.basic_pay_regular, 0) +              
            //                     COALESCE(p.basic_pay_trainee, 0) +              
            //                     COALESCE(p.allowances, 0) +              
            //                     COALESCE(p.bm_allowance, 0) +              
            //                     COALESCE(p.overtime_regular, 0) +              
            //                     COALESCE(p.overtime_trainee, 0) +              
            //                     COALESCE(p.cola, 0) +              
            //                     COALESCE(p.excess_pb, 0) +              
            //                     COALESCE(p.other_income, 0) +              
            //                     COALESCE(p.salary_adjustment, 0) +              
            //                     COALESCE(p.graveyard, 0)         
            //                 ) -          
            //                 (             
            //                     COALESCE(p.late_regular, 0) +         
            //                     COALESCE(p.late_trainee, 0) +         
            //                     COALESCE(p.leave_regular, 0) +         
            //                     COALESCE(p.leave_trainee, 0) +
            //                     COALESCE(o.income_tax, 0) +
            //                     COALESCE(o.sss_contribution, 0) + 
            //                     COALESCE(o.sss_loan, 0) +
            //                     COALESCE(o.pagibig_contribution, 0) +
            //                     COALESCE(o.pagibig_loan, 0) +
            //                     COALESCE(o.philhealth, 0) +
            //                     COALESCE(o.coated, 0) +
            //                     COALESCE(o.hmo, 0) +
            //                     COALESCE(o.canteen, 0) +
            //                     COALESCE(o.deduction_one, 0) +
            //                     COALESCE(o.deduction_two, 0) +
            //                     COALESCE(o.ml_fund, 0) +
            //                     COALESCE(o.opec, 0) +
            //                     COALESCE(o.over_appraisal, 0) +
            //                     COALESCE(o.vpo_collection, 0) +
            //                     COALESCE(o.installment_account, 0) +
            //                     COALESCE(o.ticket, 0) +
            //                     COALESCE(o.mobile_bill, 0) +
            //                     COALESCE(o.sako, 0) +
            //                     COALESCE(o.sako_savings, 0)         
            //                 )     
            //             ) AS TOTAL_NET_PAY

            //         FROM " . $database[0] . ".payroll p
            //         LEFT JOIN " . $database[0] . ".operation_deduction o 
            //             ON p.payroll_date = o.operation_date 
            //             AND p.mainzone = o.mainzone 
            //             AND p.zone = o.zone 
            //             AND p.region = o.region 
            //             AND p.region_code = o.region_code 
            //             AND p.bos_code = o.bos_code 
            //             AND p.branch_name = o.branch_name 

            //         WHERE p.payroll_date = '$date'

            //         GROUP BY     
            //             p.payroll_date,
            //             p.mainzone,
            //             p.region,
            //             p.region_code;
            //     ";  
        //}

        // Execute query
        // $mcashresult = mysqli_query($conn, $mcashquery);
        $payrollresult = mysqli_query($conn, $payrollquery);

        // Fetch results
        // $mcashrows = $mcashresult->fetch_all(MYSQLI_ASSOC);
        $payrollrows = $payrollresult->fetch_all(MYSQLI_ASSOC);
    }

    // Initialize grand totals
    $grandTotalMCash = 0;
    $grandTotalPayrollIncome = 0;
    $grandTotalPayrollDeductions = 0;
    $grandTotalPayrollNetPay = 0;
    $grandTotalVariance = 0;

    // Calculate totals
    if (isset($_POST['generate']) && !empty($payrollrows)) {
        foreach ($payrollrows as $mcash) {
            $grandTotalMCash += $mcash['mcash_total_amount'];
            $grandTotalPayrollIncome += $mcash['TOTAL_INCOME'];
            $grandTotalPayrollDeductions += $mcash['TOTAL_DEDUCTION'];
            $grandTotalPayrollNetPay += $mcash['TOTAL_NET_PAY'];
            $grandTotalVariance += $mcash['VARIANCE'];
        }

        $_SESSION['payrollRows'] = $payrollrows;
        $_SESSION['grandTotalMCash'] = $grandTotalMCash;
        $_SESSION['grandTotalPayrollIncome'] = $grandTotalPayrollIncome;
        $_SESSION['grandTotalPayrollDeductions'] = $grandTotalPayrollDeductions;
        $_SESSION['grandTotalPayrollNetPay'] = $grandTotalPayrollNetPay;
        $_SESSION['grandTotalVariance'] = $grandTotalVariance;

        
    }

    // Determine payroll day
    if (!empty($_POST['restricted-date'])) {
        $payrollDay = date('j', strtotime($_POST['restricted-date']));
        $_SESSION['payroll_day'] = $payrollDay;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../assets/css/admin/mcash-recon/mcash-recon-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>ML Wallet Report <span>[RECON & VARIANCE-Format]</span></h2></center>

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <!-- <option value="ALL" <?php //echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL') ? 'selected' : ''; ?>>ALL REGIONS</option> -->
                    <option value="VISMIN" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="region">Region</label>
                <select name="region" id="region" autocomplete="off">
                    <!-- Regions will be populated dynamically by JavaScript -->
                    <?php
                        if (isset($_POST['region']) && !empty($_POST['region'])) {
                            echo '<option value="' . htmlspecialchars($_POST['region']) ? htmlspecialchars($_POST['region']): '' . '" selected>' . htmlspecialchars($_POST['region']) . '</option>';
                        }else{
                            echo '<option value="">Select Region</option>';
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
    </div>
    <div class="display_data">
        <div class="showEP" style="display: none">
            <form id="exportForm" action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>
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
        <!-- <div id="showdl" style="display: none">
            <button class="post-btn" onclick="postEdi()">Post EDI</button>
        </div> -->
    
    <div class='table-container'>
        <table>
            <thead>
                <tr>
                <th colspan="9">(<?php echo isset($_POST['mainzone']) ? $_POST['mainzone'] : ''; ?>)</th>
                </tr>
                <tr>
                    <th colspan="3">MCash Data</th>
                    <th></th>
                    <th colspan="3">HRMD PAYROLL (<?php echo isset($payrollDay) ? $payrollDay : ''; ?>) Data</th>
                    <th></th>
                    <th></th>
                </tr>
                <tr>
                    <th>REGION CODE  </th>
                    <th>REGION NAME</th>
                    <th>TOTAL AMOUNT PER REGION</th>
                    <th></th>
                    <th>TOTAL INCOME</th>
                    <th>TOTAL DEDUCTION</th>
                    <th>TOTAL NET PAY</th>
                    <th></th>
                    <th>VARIANCE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if (isset($_POST['generate'])) {
                        if (!empty($payrollrows)) {
                            foreach ($payrollrows as $mcash) {
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($mcash['region_code']) . '</td>';
                                echo '<td>' . htmlspecialchars($mcash['region_name']) . '</td>';
                                echo '<td align="right">' . (!empty($mcash['mcash_total_amount']) ? number_format($mcash['mcash_total_amount'], 2) : '-') . '</td>';
                                echo '<td></td>';
                                echo '<td align="right">' . (!empty($mcash['TOTAL_INCOME']) ? number_format($mcash['TOTAL_INCOME'], 2) : '-') . '</td>';
                                echo '<td align="right">' . (!empty($mcash['TOTAL_DEDUCTION']) ? number_format($mcash['TOTAL_DEDUCTION'], 2) : '-') . '</td>';
                                echo '<td align="right">' . (!empty($mcash['TOTAL_NET_PAY']) ? number_format($mcash['TOTAL_NET_PAY'], 2) : '-') . '</td>';
                                echo '<td></td>';
                            
                                // Variance Calculation Based on Conditions
                                $mcashAmount = !empty($mcash['mcash_total_amount']) ? $mcash['mcash_total_amount'] : 0;
                                $netPay = !empty($mcash['TOTAL_NET_PAY']) ? $mcash['TOTAL_NET_PAY'] : 0;
                            
                                if ($mcashAmount || $netPay) { // If either has a value
                                    $variance = $mcashAmount - $netPay;
                                    echo '<td align="right">' . number_format($variance, 2) . '</td>';
                                } else {
                                    echo '<td>-</td>'; // Placeholder if both are empty
                                }
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="9">No records found for the selected criteria.</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9">Please select Payroll Date to display.</td></tr>';
                    }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">GRAND TOTAL</th>
                    <th><?php echo number_format($grandTotalMCash, 2); ?></th>
                    <th></th>
                    <th><?php echo number_format($grandTotalPayrollIncome, 2); ?></th>
                    <th><?php echo number_format($grandTotalPayrollDeductions, 2); ?></th>
                    <th><?php echo number_format($grandTotalPayrollNetPay, 2); ?></th>
                    <th></th>
                    <th><?php echo number_format($grandTotalVariance, 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>


    <script src="../assets/js/admin/mcash-recon/mcash-recon-script.js"></script>
    <?php
        echo '<script>

        function printTable() {
            window.print();
        }

        function exportToPDF() {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "mcash-and-hrmd-payroll_pdf.php";
            document.body.appendChild(form);
            form.submit();
        }   
    </script>';
    ?>
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
    <script>
		function showExportButtons() {
			var elements = document.getElementsByClassName("showEP");
			for (var i = 0; i < elements.length; i++) {
				elements[i].style.display = 'block';
			}
		}

		<?php if (isset($_POST['generate']) && !empty($payrollrows)) : ?>
			showExportButtons();
		<?php endif; ?>
	</script>
</body>
</html>

<!-- <script>
    function postEdi() {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to post this data?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, post it!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, redirect to process
                window.location.href = 'payroll_post_edi.php?proceed=true';
            } else {
                window.location.href = 'payroll_post_edi.php';
            }
        });
    }
</script> -->