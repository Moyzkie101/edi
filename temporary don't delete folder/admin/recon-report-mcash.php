<?php
    session_start();

    if (!isset($_SESSION['admin_name'])) {
        header('location: ../login.php');
    }

    require '../vendor/autoload.php';
    include '../config/connection.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    if(isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        $dlsql = "SELECT      
                p.payroll_date,     
                p.mainzone,     
                p.zone,     
                p.region,     
                p.region_code,     
                p.bos_code,     
                p.branch_name,     

                -- Payroll Income     
                SUM(COALESCE(p.basic_pay_regular, 0)) AS basic_pay_regular,     
                p.gl_code_basic_pay_regular,     
                SUM(COALESCE(p.basic_pay_trainee, 0)) AS basic_pay_trainee,     
                p.gl_code_basic_pay_trainee,     
                SUM(COALESCE(p.allowances, 0)) AS allowances,     
                p.gl_code_allowances,     
                SUM(COALESCE(p.bm_allowance, 0)) AS bm_allowance,     
                p.gl_code_bm_allowance,     
                SUM(COALESCE(p.overtime_regular, 0)) AS overtime_regular,     
                p.gl_code_overtime_regular,     
                SUM(COALESCE(p.overtime_trainee, 0)) AS overtime_trainee,     
                p.gl_code_overtime_trainee,     
                SUM(COALESCE(p.cola, 0)) AS cola,     
                p.gl_code_cola,     
                SUM(COALESCE(p.excess_pb, 0)) AS excess_pb,     
                p.gl_code_excess_pb,     
                SUM(COALESCE(p.other_income, 0)) AS other_income,     
                p.gl_code_other_income,     
                SUM(COALESCE(p.salary_adjustment, 0)) AS salary_adjustment,     
                p.gl_code_salary_adjustment,     
                SUM(COALESCE(p.graveyard, 0)) AS graveyard,     
                p.gl_code_graveyard,     

                -- Total Income Calculation     
                SUM(         
                    COALESCE(p.basic_pay_regular, 0) +          
                    COALESCE(p.basic_pay_trainee, 0) +          
                    COALESCE(p.allowances, 0) +          
                    COALESCE(p.bm_allowance, 0) +          
                    COALESCE(p.overtime_regular, 0) +          
                    COALESCE(p.overtime_trainee, 0) +          
                    COALESCE(p.cola, 0) +          
                    COALESCE(p.excess_pb, 0) +          
                    COALESCE(p.other_income, 0) +          
                    COALESCE(p.salary_adjustment, 0) +          
                    COALESCE(p.graveyard, 0)     
                ) AS TOTAL_INCOME,     

                -- Payroll Deductions     
                SUM(COALESCE(p.late_regular, 0)) AS late_regular,     
                p.gl_code_late_regular,     
                SUM(COALESCE(p.late_trainee, 0)) AS late_trainee,     
                p.gl_code_late_trainee,     
                SUM(COALESCE(p.leave_regular, 0)) AS leave_regular,     
                p.gl_code_leave_regular,     
                SUM(COALESCE(p.leave_trainee, 0)) AS leave_trainee,     
                p.gl_code_leave_trainee,
                SUM(
                    COALESCE(o.income_tax, 0) +
                    COALESCE(o.sss_contribution, 0) + 
                    COALESCE(o.sss_loan, 0) +
                    COALESCE(o.pagibig_contribution, 0) +
                    COALESCE(o.pagibig_loan, 0) +
                    COALESCE(o.philhealth, 0) +
                    COALESCE(o.coated, 0) +
                    COALESCE(o.hmo, 0) +
                    COALESCE(o.canteen, 0) +
                    COALESCE(o.deduction_one, 0) +
                    COALESCE(o.deduction_two, 0) +
                    COALESCE(o.ml_fund, 0) +
                    COALESCE(o.opec, 0) +
                    COALESCE(o.over_appraisal, 0) +
                    COALESCE(o.vpo_collection, 0) +
                    COALESCE(o.installment_account, 0) +
                    COALESCE(o.ticket, 0) +
                    COALESCE(o.mobile_bill, 0) +
                    COALESCE(o.sako, 0) +
                    COALESCE(o.sako_savings, 0) 
                ) AS all_other_deductions,
                p.gl_code_all_other_deductions,
                
                -- Total Deductions     
                SUM(         
                    COALESCE(p.late_regular, 0) +         
                    COALESCE(p.late_trainee, 0) +         
                    COALESCE(p.leave_regular, 0) +         
                    COALESCE(p.leave_trainee, 0) +
                    COALESCE(o.income_tax, 0) +
                    COALESCE(o.sss_contribution, 0) + 
                    COALESCE(o.sss_loan, 0) +
                    COALESCE(o.pagibig_contribution, 0) +
                    COALESCE(o.pagibig_loan, 0) +
                    COALESCE(o.philhealth, 0) +
                    COALESCE(o.coated, 0) +
                    COALESCE(o.hmo, 0) +
                    COALESCE(o.canteen, 0) +
                    COALESCE(o.deduction_one, 0) +
                    COALESCE(o.deduction_two, 0) +
                    COALESCE(o.ml_fund, 0) +
                    COALESCE(o.opec, 0) +
                    COALESCE(o.over_appraisal, 0) +
                    COALESCE(o.vpo_collection, 0) +
                    COALESCE(o.installment_account, 0) +
                    COALESCE(o.ticket, 0) +
                    COALESCE(o.mobile_bill, 0) +
                    COALESCE(o.sako, 0) +
                    COALESCE(o.sako_savings, 0)     
                ) AS TOTAL_DEDUCTION,     

                -- Net Pay Calculation     
                SUM(         
                    (             
                        COALESCE(p.basic_pay_regular, 0) +              
                        COALESCE(p.basic_pay_trainee, 0) +              
                        COALESCE(p.allowances, 0) +              
                        COALESCE(p.bm_allowance, 0) +              
                        COALESCE(p.overtime_regular, 0) +              
                        COALESCE(p.overtime_trainee, 0) +              
                        COALESCE(p.cola, 0) +              
                        COALESCE(p.excess_pb, 0) +              
                        COALESCE(p.other_income, 0) +              
                        COALESCE(p.salary_adjustment, 0) +              
                        COALESCE(p.graveyard, 0)         
                    ) -          
                    (             
                        COALESCE(p.late_regular, 0) +         
                        COALESCE(p.late_trainee, 0) +         
                        COALESCE(p.leave_regular, 0) +         
                        COALESCE(p.leave_trainee, 0) +
                        COALESCE(o.income_tax, 0) +
                        COALESCE(o.sss_contribution, 0) + 
                        COALESCE(o.sss_loan, 0) +
                        COALESCE(o.pagibig_contribution, 0) +
                        COALESCE(o.pagibig_loan, 0) +
                        COALESCE(o.philhealth, 0) +
                        COALESCE(o.coated, 0) +
                        COALESCE(o.hmo, 0) +
                        COALESCE(o.canteen, 0) +
                        COALESCE(o.deduction_one, 0) +
                        COALESCE(o.deduction_two, 0) +
                        COALESCE(o.ml_fund, 0) +
                        COALESCE(o.opec, 0) +
                        COALESCE(o.over_appraisal, 0) +
                        COALESCE(o.vpo_collection, 0) +
                        COALESCE(o.installment_account, 0) +
                        COALESCE(o.ticket, 0) +
                        COALESCE(o.mobile_bill, 0) +
                        COALESCE(o.sako, 0) +
                        COALESCE(o.sako_savings, 0)         
                    )     
                ) AS TOTAL_NET_PAY,
                p.gl_code_total,
                MAX(p.cost_center) AS cost_center,
                MAX(p.no_of_branch_employee) AS no_of_branch_employee,
                MAX(p.no_of_employees_allocated) AS no_of_employees_allocated


            FROM " . $database[0] . ".payroll p
            LEFT JOIN " . $database[0] . ".operation_deduction o 
                ON p.payroll_date = o.operation_date 
                AND p.mainzone = o.mainzone 
                AND p.zone = o.zone 
                AND p.region = o.region 
                AND p.region_code = o.region_code 
                AND p.bos_code = o.bos_code 
                AND p.branch_name = o.branch_name 

            WHERE 
                p.mainzone = '$mainzone'
                AND (p.zone = '$zone' OR p.zone = 'JVIS')
                AND p.region_code LIKE '%$region%'
                AND p.payroll_date = '$restrictedDate'

            GROUP BY     
                p.payroll_date,
                p.mainzone,
                p.zone,
                p.region,
                p.region_code,
                p.bos_code,
                p.branch_name,     
                p.gl_code_basic_pay_regular,
                p.gl_code_basic_pay_trainee,
                p.gl_code_allowances,     
                p.gl_code_bm_allowance,
                p.gl_code_overtime_regular,
                p.gl_code_overtime_trainee,     
                p.gl_code_cola,
                p.gl_code_excess_pb,
                p.gl_code_other_income,
                p.gl_code_salary_adjustment,     
                p.gl_code_graveyard,
                p.gl_code_late_regular,
                p.gl_code_late_trainee,
                p.gl_code_leave_regular,     
                p.gl_code_leave_trainee,
                p.gl_code_total,

                o.gl_code_income_tax,
                o.gl_code_sss_contribution,
                o.gl_code_sss_loan,     
                o.gl_code_pagibig_contribution,
                o.gl_code_pagibig_loan,
                o.gl_code_philhealth,
                o.gl_code_coated,     
                o.gl_code_hmo,
                o.gl_code_canteen,
                o.gl_code_deduction_one,
                o.gl_code_deduction_two, 
                p.gl_code_all_other_deductions;
        "; 

        //echo $dlsql;
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
                '','Payroll Date - '. $payroll_date, 'Basic Pay Regular', 'Basic Pay Trainee', 'Allowances', 'BM Allowance',
                'Overtime Regular', 'Overtime Trainee', 'COLA', 'Excess PB', 'Other Income', 'Salary Adjustment', 'Graveyard', 'TOTAL INCOME', 'Late Regular',
                'Late Trainee', 'Leave Regular', 'Leave Trainee', 'All Other Deductions', 'TOTAL DEDUCTION', 'TOTAL NET PAY', 'Cost Center', 'No. of Branch Employees', 'No. of Employees Allocated', 'Region'
            ];

            $headerRow2 = [
                '', '', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit', 'Debit',
                'Credit', 'Credit', 'Credit', 'Credit', 'Credit'
            ];

            $headerRow3 = [
                'BOS Code', 'Branch Name', $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
                $gl_code_cola, $gl_code_excess_pb, $gl_code_other_income, $gl_code_salary_adjustment, $gl_code_graveyard, $gl_code_late_regular, $gl_code_late_trainee,
                $gl_code_leave_regular, $gl_code_leave_trainee, $gl_code_total, '', $gl_code_all_other_deductions, '', ''
            ];

            $sheet->mergeCells('A1:B1');
            $sheet->setCellValue('A1', 'Payroll Date - '. $payroll_date);

            $sheet->fromArray([$headerRow1], null, 'A1');
            $sheet->fromArray([$headerRow2], null, 'A2');
            $sheet->fromArray([$headerRow3], null, 'A3');
    
            foreach(range('A', 'Z') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $sheet->getStyle('A1:Z1')->getFont()->setBold(true);
            $sheet->getStyle('A2:Z2')->getFont()->setBold(true);
            $sheet->getStyle('A3:Z3')->getFont()->setBold(true);
    
            $rowIndex = 4;
    
            while($row = mysqli_fetch_assoc($dlresult)) {

                $sheet->setCellValue('A' . $rowIndex, $row['bos_code']);
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

                $sheet->setCellValueExplicit('N' . $rowIndex, $row['TOTAL_INCOME'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('O' . $rowIndex, $row['late_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('P' . $rowIndex, $row['late_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('Q' . $rowIndex, $row['leave_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('R' . $rowIndex, $row['leave_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('S' . $rowIndex, $row['all_other_deductions'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('T' . $rowIndex, $row['TOTAL_DEDUCTION'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('T' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('U' . $rowIndex, $row['TOTAL_NET_PAY'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('U' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValue('V' . $rowIndex, $row['cost_center']);
                
                $sheet->setCellValue('W' . $rowIndex, $row['no_of_branch_employee']);
                $sheet->setCellValue('X' . $rowIndex, $row['no_of_employees_allocated']);
                $sheet->setCellValue('Y' . $rowIndex, $row['region']);
                $rowIndex++;
            }
    
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $filename = "Payroll_Report_CAD-FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
    
            $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
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

    <center><h2>MCash Report <span>[RECON-Format]</span></h2></center>

    <div class="import-file">
        
        <form action="" method="post">

            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <option value="ALL" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL') ? 'selected' : ''; ?>>ALL REGIONS</option>
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
                            echo '<option value="' . htmlspecialchars($_POST['region']) . '" selected>' . htmlspecialchars($_POST['region']) . '</option>';
                        } else {
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

        <div id="showdl" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>

    </div>

    <script src="../assets/js/admin/mcash-recon/mcash-recon-script.js"></script>
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
        $restrictedDate = $_POST['restricted-date'];

        $_SESSION['mainzone'] = $mainzone;
        $_SESSION['region'] = $region;
        $_SESSION['restrictedDate'] = $restrictedDate;
        
        $sql = "
            ";  
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {

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
            
            echo "<th colspan='3'>Payroll Period : $payroll_date</th>";
            echo "<th rowspan='3'>NO. OF EMPLOYEE (ML WALLET)</th>";
            echo "<th rowspan='3'>ML WALLET</th>";
            echo "<th rowspan='3'>NO. OF EMPLOYEE(ML KP)</th>";
            echo "<th rowspan='3'>ML KP</th>";
            echo "<th rowspan='3'>TOTAL EMPLOYEE</th>";
            echo "<th rowspan='3'>TOTAL AMOUNT PER REGION</th>";
            // echo "<th colspan='3'>Payroll Date - " . $payroll_date . "</th>";
            // echo "<th>Basic Pay Regular</th>";
            // echo "<th>Basic Pay Trainee</th>";
            // echo "<th>Allowances</th>";
            // echo "<th>BM Allowance</th>";
            // echo "<th>Overtime Regular</th>";
            // echo "<th>Overtime Trainee</th>";
            // echo "<th>COLA</th>";
            // echo "<th>Excess PB</th>";
            // echo "<th>Other Income</th>";
            // echo "<th>Salary Adjustment</th>";
            // echo "<th>Graveyard</th>";
            // echo "<th>TOTAL INCOME</th>";
            // echo "<th>Late Regular</th>";
            // echo "<th>Late Trainee</th>";
            // echo "<th>Leave Regular</th>";
            // echo "<th>Leave Trainee</th>";
            // echo "<th>All Other Deductions</th>";
            // echo "<th>TOTAL DEDUCTION</th>";
            // echo "<th>NET PAY</th>";
            // echo "<th>Cost Center</th>";
            // echo "<th>No. of Branch Employees</th>";
            // echo "<th>No. of Employees Allocated</th>";
            
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th colspan='2'> Mainzone - " . $mainzone . "</th>";
            echo "<th rowspan='2'>Region</th>";
            
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Debit</th>";
            // echo "<th>Credit</th>";
            // echo "<th>Credit</th>";
            // echo "<th>Credit</th>";
            // echo "<th>Credit</th>";
            // echo "<th>Credit</th>";
            // echo "<th>Credit</th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            echo "</tr>";
            //third row
            echo "<tr>";
            echo "<th>Region Code</th>";
            echo "<th>Zone Code</th>";
            // echo "<th style='white-space: nowrap'>BOS Code</th>";
            // echo "<th>Branch Name</th>";
            // echo "<th>Region</th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th>". $gl_code_basic_pay_regular ."</th>";
            // echo "<th>". $gl_code_basic_pay_trainee ."</th>";
            // echo "<th>". $gl_code_allowances ."</th>";
            // echo "<th>". $gl_code_bm_allowance ."</th>";
            // echo "<th>". $gl_code_overtime_regular ."</th>";
            // echo "<th>". $gl_code_overtime_trainee ."</th>";
            // echo "<th>". $gl_code_cola ."</th>";
            // echo "<th>". $gl_code_excess_pb ."</th>";
            // echo "<th>". $gl_code_other_income ."</th>";
            // echo "<th>". $gl_code_salary_adjustment ."</th>";
            // echo "<th>". $gl_code_graveyard ."</th>";
            // echo "<th></th>";
            // echo "<th>". $gl_code_late_regular ."</th>";
            // echo "<th>". $gl_code_late_trainee ."</th>";
            // echo "<th>". $gl_code_leave_regular ."</th>";
            // echo "<th>". $gl_code_leave_trainee ."</th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th>". $gl_code_total ."</th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            // echo "<th></th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalNumberOfBranches = 0;

            $totalbasicpayregular = 0;
            $totalbasicpaytrainee = 0;
            $totalallowances = 0;
            $totalbmallowance = 0;
            $totalovertimeregular = 0;
            $totalovertimetrainee = 0;
            $totalcola = 0;
            $totalexcesspb = 0;
            $totalotherincome = 0;
            $totalsalaryadjustment = 0;
            $totalgraveyard = 0;
            $totalincome = 0;

            $totalLateRegular = 0;
            $totalLateTrainee = 0;
            $totalLeaveRegular = 0;
            $totalLeaveTrainee = 0;
            $totalallotherdeduction = 0;
            $totaldeduction = 0;

            $totalnetpay = 0;
            echo "<tr>";
            echo "<td>TEST</td>";
            echo "<td>TEST</td>";
            echo "<td>TEST</td>";
            echo "<td>0.00</td>";
            echo "<td>0.00</td>";
            echo "<td>0.00</td>";
            echo "<td>0.00</td>";
            echo "<td>0.00</td>";
            echo "<td>0.00</td>";
            echo "</tr>";
            // Output the data rows
            mysqli_data_seek($result, 0); // Reset result pointer to the beginning
            while ($row = mysqli_fetch_assoc($result)) {
                $totalNumberOfBranches++;
                // echo "<tr>";
                // echo "<td>" . htmlspecialchars($row['bos_code']) . "</td>";
                // echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['branch_name']) . "</td>";
                // echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row['region']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['basic_pay_regular']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['basic_pay_trainee']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['allowances']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['bm_allowance']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['overtime_regular']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['overtime_trainee']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['cola']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['excess_pb']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['other_income']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['salary_adjustment']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['graveyard']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['TOTAL_INCOME']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['late_regular']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['late_trainee']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['leave_regular']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['leave_trainee']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['all_other_deductions']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['TOTAL_DEDUCTION']) . "</td>"; 
                // echo "<td>" . htmlspecialchars($row['TOTAL_NET_PAY']) . "</td>"; 
                // echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['cost_center']) . "</td>";
                // echo "<td style='white-space: nowrap'>" . htmlspecialchars(!empty($row['cost_center1']) ? $row['cost_center1'] : $row['cost_center']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['no_of_branch_employee']) . "</td>";
                // echo "<td>" . htmlspecialchars($row['no_of_employees_allocated']) . "</td>";
                // echo "</tr>";

                $totalbasicpayregular += floatval($row['basic_pay_regular']);
                $totalbasicpaytrainee += floatval($row['basic_pay_trainee']);
                $totalallowances += floatval($row['allowances']);
                $totalbmallowance += floatval($row['bm_allowance']);
                $totalovertimeregular += floatval($row['overtime_regular']);
                $totalovertimetrainee += floatval($row['overtime_trainee']);
                $totalcola += floatval($row['cola']);
                $totalexcesspb += floatval($row['excess_pb']);
                $totalotherincome += floatval($row['other_income']);
                $totalsalaryadjustment += floatval($row['salary_adjustment']);
                $totalgraveyard += floatval($row['graveyard']);
                $totalincome += floatval($row['TOTAL_INCOME']);

                $totalLateRegular += floatval($row['late_regular']);
                $totalLateTrainee += floatval($row['late_trainee']);
                $totalLeaveRegular += floatval($row['leave_regular']);
                $totalLeaveTrainee += floatval($row['leave_trainee']);
                $totalallotherdeduction += floatval($row['all_other_deductions']);
                $totaldeduction += floatval($row['TOTAL_DEDUCTION']);

                $totalnetpay += floatval($row['TOTAL_NET_PAY']);
            }

            echo "</tbody>";
            echo "<tfoot>";
            echo "<tr>";
            echo "<th colspan='2' style='text-align: right;'>GRAND TOTAL</th>";
            echo "<th>0.00</th>";
            echo "<th>0.00</th>";
            echo "<th>0.00</th>";
            echo "<th>0.00</th>";
            echo "<th>0.00</th>";
            echo "<th>0.00</th>";
            echo "<th>0.00</th>";
            // echo "<th>" . htmlspecialchars($totalbasicpayregular) . "</th>";
            // echo "<th>" . htmlspecialchars($totalbasicpaytrainee) . "</th>";
            // echo "<th>" . htmlspecialchars($totalallowances) . "</th>";
            // echo "<th>" . htmlspecialchars($totalbmallowance) . "</th>";
            // echo "<th>" . htmlspecialchars($totalovertimeregular) . "</th>";
            // echo "<th>" . htmlspecialchars($totalovertimetrainee) . "</th>";
            // echo "<th>" . htmlspecialchars($totalcola) . "</th>";
            // echo "<th>" . htmlspecialchars($totalexcesspb) . "</th>";
            // echo "<th>" . htmlspecialchars($totalotherincome) . "</th>";
            // echo "<th>" . htmlspecialchars($totalsalaryadjustment) . "</th>";
            // echo "<th>" . htmlspecialchars($totalgraveyard) . "</th>";
            // echo "<th>" . htmlspecialchars($totalincome) . "</th>";
            // echo "<th>" . htmlspecialchars($totalLateRegular) . "</th>";
            // echo "<th>" . htmlspecialchars($totalLateTrainee) . "</th>";
            // echo "<th>" . htmlspecialchars($totalLeaveRegular) . "</th>";
            // echo "<th>" . htmlspecialchars($totalLeaveTrainee) . "</th>";
            // echo "<th>" . htmlspecialchars($totalallotherdeduction) . "</th>";
            // echo "<th>" . htmlspecialchars($totaldeduction) . "</th>";
            // echo "<th>" . htmlspecialchars($totalnetpay) . "</th>";
            // echo "<th colspan='3'></th>";
            echo "</tr>";
            echo "</tfoot>";
            echo "</table>";
            echo "</div>
            
            <script>
                var dlbtn = document.getElementById('showdl');
                dlbtn.style.display = 'block';  
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
