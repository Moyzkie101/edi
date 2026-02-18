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
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        $dlsql = "SELECT 
                operation_date,
                mainzone,
                `zone`,
                region,
                region_code,
                bos_code,
                branch_name,
                
                SUM(COALESCE(income_tax, 0)) AS income_tax,
                gl_code_income_tax,
                SUM(COALESCE(sss_contribution, 0)) AS sss_contribution,
                gl_code_sss_contribution,
                SUM(COALESCE(sss_loan, 0)) AS sss_loan,
                gl_code_sss_loan,
                SUM(COALESCE(pagibig_contribution, 0)) AS pagibig_contribution,
                gl_code_pagibig_contribution,
                SUM(COALESCE(pagibig_loan, 0)) AS pagibig_loan,
                gl_code_pagibig_loan,
                SUM(COALESCE(philhealth, 0)) AS philhealth,
                gl_code_philhealth,
                SUM(COALESCE(coated, 0)) AS coated,
                gl_code_coated,
                SUM(COALESCE(hmo, 0)) AS hmo,
                gl_code_hmo,
                SUM(COALESCE(canteen, 0)) AS canteen,
                gl_code_canteen,
                SUM(COALESCE(deduction_one, 0)) AS deduction_one,
                gl_code_deduction_one,
                SUM(COALESCE(deduction_two, 0)) AS deduction_two,
                gl_code_deduction_two,
                SUM(COALESCE(ml_fund, 0)) AS ml_fund,
                gl_code_ml_fund,
                SUM(COALESCE(opec, 0)) AS opec,
                gl_code_opec,
                SUM(COALESCE(over_appraisal, 0)) AS over_appraisal,
                gl_code_over_appraisal,
                SUM(COALESCE(vpo_collection, 0)) AS vpo_collection,
                gl_code_vpo_collection,
                SUM(COALESCE(installment_account, 0)) AS installment_account,
                gl_code_installment_account,
                SUM(COALESCE(ticket, 0)) AS ticket,
                gl_code_ticket,
                SUM(COALESCE(mobile_bill, 0)) AS mobile_bill,
                gl_code_mobile_bill,
                SUM(COALESCE(sako, 0)) AS sako,
                gl_code_sako,
                SUM(COALESCE(sako_savings, 0)) AS sako_savings,
                gl_code_sako_savings,
                SUM(
                    COALESCE(income_tax, 0) +
                    COALESCE(sss_contribution, 0) +
                    COALESCE(sss_loan, 0) +
                    COALESCE(pagibig_contribution, 0) +
                    COALESCE(pagibig_loan, 0) +
                    COALESCE(philhealth, 0) +
                    COALESCE(coated, 0) +
                    COALESCE(hmo, 0) +
                    COALESCE(canteen, 0) +
                    COALESCE(deduction_one, 0) +
                    COALESCE(deduction_two, 0) +
                    COALESCE(ml_fund, 0) +
                    COALESCE(opec, 0) +
                    COALESCE(over_appraisal, 0) +
                    COALESCE(vpo_collection, 0) +
                    COALESCE(installment_account, 0) +
                    COALESCE(ticket, 0) +
                    COALESCE(mobile_bill, 0) +
                    COALESCE(sako, 0) +
                    COALESCE(sako_savings, 0)
                ) AS ALL_OTHER_DEDUCTIONS,
                sheet_name,
                uploaded_by,
                uploaded_date,
                post_edi

            FROM " . $database[0] . ".operation_deduction

            WHERE 
                mainzone = '$mainzone'
                AND (`zone` = '$zone' OR `zone` = 'JVIS')
                AND region_code LIKE '%$region%'
                AND operation_date = '$restrictedDate'

            GROUP BY 
                operation_date,
                mainzone,
                `zone`,
                region,
                region_code,
                bos_code,
                branch_name,

                gl_code_income_tax,
                gl_code_sss_contribution,
                gl_code_sss_loan,
                gl_code_pagibig_contribution,
                gl_code_pagibig_loan, 
                gl_code_philhealth,
                gl_code_coated,
                gl_code_hmo,
                gl_code_canteen,
                gl_code_deduction_one,
                gl_code_deduction_two, 
                gl_code_ml_fund,
                gl_code_opec,
                gl_code_over_appraisal,
                gl_code_vpo_collection,
                gl_code_installment_account, 
                gl_code_ticket,
                gl_code_mobile_bill,
                gl_code_sako,
                gl_code_sako_savings,
                sheet_name,
                uploaded_by,
                uploaded_date,
                post_edi;
        "; 

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        if(mysqli_num_rows($dlresult) > 0) {

            $first_row = mysqli_fetch_assoc($dlresult);

            $operation_date = htmlspecialchars($first_row['operation_date']);
            $gl_code_income_tax = htmlspecialchars($first_row['gl_code_income_tax']);
            $gl_code_sss_contribution = htmlspecialchars($first_row['gl_code_sss_contribution']);
            $gl_code_sss_loan = htmlspecialchars($first_row['gl_code_sss_loan']);
            $gl_code_pagibig_contribution = htmlspecialchars($first_row['gl_code_pagibig_contribution']);
            $gl_code_pagibig_loan = htmlspecialchars($first_row['gl_code_pagibig_loan']);
            $gl_code_philhealth = htmlspecialchars($first_row['gl_code_philhealth']);
            $gl_code_coated = htmlspecialchars($first_row['gl_code_coated']);
            $gl_code_hmo = htmlspecialchars($first_row['gl_code_hmo']);
            $gl_code_canteen = htmlspecialchars($first_row['gl_code_canteen']);
            $gl_code_deduction_one = htmlspecialchars($first_row['gl_code_deduction_one']);
            $gl_code_deduction_two = htmlspecialchars($first_row['gl_code_deduction_two']);
            $gl_code_ml_fund = htmlspecialchars($first_row['gl_code_ml_fund']);
            $gl_code_opec = htmlspecialchars($first_row['gl_code_opec']);
            $gl_code_over_appraisal = htmlspecialchars($first_row['gl_code_over_appraisal']);
            $gl_code_vpo_collection = htmlspecialchars($first_row['gl_code_vpo_collection']);
            $gl_code_installment_account = htmlspecialchars($first_row['gl_code_installment_account']);
            $gl_code_ticket = htmlspecialchars($first_row['gl_code_ticket']);
            $gl_code_mobile_bill = htmlspecialchars($first_row['gl_code_mobile_bill']);
            $gl_code_sako = htmlspecialchars($first_row['gl_code_sako']);
            $gl_code_sako_savings = htmlspecialchars($first_row['gl_code_sako_savings']);

            // Reset the result pointer to the beginning
            mysqli_data_seek($dlresult, 0);

            $headerRow1 = [
                '','OD Date - '. $operation_date, 'INCOME TAX', 'SSS CONTRIBUTION', 'SSS LOAN', 'PAGIBIG CONTRIBUTION',
                'PAGIBIG LOAN', 'PHILHEALTH', 'COATED', 'HMO', 'CANTEEN', 'DEDUCTION 1', 'DEDUCTION 2', 'ML FUND', 'OPEC',
                'OVER APPRAISAL', 'VPO COLLECTION', 'INSTALLMENT ACCOUNT', 'TICKET', 'MOBILE BILL', 'SAKO', 'SAKO SAVINGS', 'All Other Deductions', 'Region'
            ];

            $headerRow2 = [
                '', '', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit', 'Credit',
                'Credit', 'Credit', 'Credit', 'Credit', 'Credit'
            ];

            // $headerRow3 = [
            //     'BOS Code', 'Branch Name', $gl_code_income_tax, $gl_code_sss_contribution, $gl_code_sss_loan, $gl_code_pagibig_contribution, $gl_code_pagibig_loan, $gl_code_philhealth,
            //     $gl_code_coated, $gl_code_hmo, $gl_code_canteen, $gl_code_deduction_one, $gl_code_deduction_two, $gl_code_ml_fund, $gl_code_opec,
            //     $gl_code_over_appraisal, $gl_code_vpo_collection, $gl_code_installment_account, $gl_code_ticket, $gl_code_mobile_bill, $gl_code_sako, $gl_code_sako_savings
            // ];
            $headerRow3 = [
                'BOS Code', 'Branch Name', '', '', '', '', '', '',
                '', '', '', '', '', '', '',
                '', '', '', '', '', '', ''
            ];

            $sheet->mergeCells('A1:B2');
            $sheet->setCellValue('A1', 'OD Date - '. $operation_date);

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
                $sheet->setCellValueExplicit('C' . $rowIndex, $row['income_tax'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('D' . $rowIndex, $row['sss_contribution'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('E' . $rowIndex, $row['sss_loan'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('F' . $rowIndex, $row['pagibig_contribution'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('G' . $rowIndex, $row['pagibig_loan'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('H' . $rowIndex, $row['philhealth'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('I' . $rowIndex, $row['coated'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('J' . $rowIndex, $row['hmo'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('K' . $rowIndex, $row['canteen'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('L' . $rowIndex, $row['deduction_one'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('M' . $rowIndex, $row['deduction_two'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('N' . $rowIndex, $row['ml_fund'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('O' . $rowIndex, $row['opec'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('P' . $rowIndex, $row['over_appraisal'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('Q' . $rowIndex, $row['vpo_collection'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('R' . $rowIndex, $row['installment_account'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('S' . $rowIndex, $row['ticket'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('T' . $rowIndex, $row['mobile_bill'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('T' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('U' . $rowIndex, $row['sako'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('U' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('V' . $rowIndex, $row['sako_savings'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('V' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValueExplicit('W' . $rowIndex, $row['ALL_OTHER_DEDUCTIONS'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('W' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                $sheet->setCellValue('X' . $rowIndex, $row['region']);

                $rowIndex++;
            }
    
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $filename = "Operation-Deduction_Report_CAD-FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
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

    <center><h2>Operation Deduction <span>[CAD-Format]</span></h2></center>

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

    <script src="../assets/js/admin/report-file/script1.js"></script>
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
        $restrictedDate = $_POST['restricted-date'];

        $_SESSION['mainzone'] = $mainzone;
        $_SESSION['zone'] = $zone;
        $_SESSION['region'] = $region;
        $_SESSION['restrictedDate'] = $restrictedDate;
        
        $sql = "SELECT 
                operation_date,
                mainzone,
                `zone`,
                region,
                region_code,
                bos_code,
                branch_name,
                
                SUM(COALESCE(income_tax, 0)) AS income_tax,
                gl_code_income_tax,
                SUM(COALESCE(sss_contribution, 0)) AS sss_contribution,
                gl_code_sss_contribution,
                SUM(COALESCE(sss_loan, 0)) AS sss_loan,
                gl_code_sss_loan,
                SUM(COALESCE(pagibig_contribution, 0)) AS pagibig_contribution,
                gl_code_pagibig_contribution,
                SUM(COALESCE(pagibig_loan, 0)) AS pagibig_loan,
                gl_code_pagibig_loan,
                SUM(COALESCE(philhealth, 0)) AS philhealth,
                gl_code_philhealth,
                SUM(COALESCE(coated, 0)) AS coated,
                gl_code_coated,
                SUM(COALESCE(hmo, 0)) AS hmo,
                gl_code_hmo,
                SUM(COALESCE(canteen, 0)) AS canteen,
                gl_code_canteen,
                SUM(COALESCE(deduction_one, 0)) AS deduction_one,
                gl_code_deduction_one,
                SUM(COALESCE(deduction_two, 0)) AS deduction_two,
                gl_code_deduction_two,
                SUM(COALESCE(ml_fund, 0)) AS ml_fund,
                gl_code_ml_fund,
                SUM(COALESCE(opec, 0)) AS opec,
                gl_code_opec,
                SUM(COALESCE(over_appraisal, 0)) AS over_appraisal,
                gl_code_over_appraisal,
                SUM(COALESCE(vpo_collection, 0)) AS vpo_collection,
                gl_code_vpo_collection,
                SUM(COALESCE(installment_account, 0)) AS installment_account,
                gl_code_installment_account,
                SUM(COALESCE(ticket, 0)) AS ticket,
                gl_code_ticket,
                SUM(COALESCE(mobile_bill, 0)) AS mobile_bill,
                gl_code_mobile_bill,
                SUM(COALESCE(sako, 0)) AS sako,
                gl_code_sako,
                SUM(COALESCE(sako_savings, 0)) AS sako_savings,
                gl_code_sako_savings,
                SUM(
                    COALESCE(income_tax, 0) +
                    COALESCE(sss_contribution, 0) +
                    COALESCE(sss_loan, 0) +
                    COALESCE(pagibig_contribution, 0) +
                    COALESCE(pagibig_loan, 0) +
                    COALESCE(philhealth, 0) +
                    COALESCE(coated, 0) +
                    COALESCE(hmo, 0) +
                    COALESCE(canteen, 0) +
                    COALESCE(deduction_one, 0) +
                    COALESCE(deduction_two, 0) +
                    COALESCE(ml_fund, 0) +
                    COALESCE(opec, 0) +
                    COALESCE(over_appraisal, 0) +
                    COALESCE(vpo_collection, 0) +
                    COALESCE(installment_account, 0) +
                    COALESCE(ticket, 0) +
                    COALESCE(mobile_bill, 0) +
                    COALESCE(sako, 0) +
                    COALESCE(sako_savings, 0)
                ) AS ALL_OTHER_DEDUCTIONS,
                sheet_name,
                uploaded_by,
                uploaded_date,
                post_edi

            FROM " . $database[0] . ".operation_deduction

            WHERE 
                mainzone = '$mainzone'
                AND (`zone` = '$zone' OR `zone` = 'JVIS')
                AND region_code LIKE '%$region%'
                AND operation_date = '$restrictedDate'

            GROUP BY 
                operation_date,
                mainzone,
                `zone`,
                region,
                region_code,
                bos_code,
                branch_name,

                gl_code_income_tax,
                gl_code_sss_contribution,
                gl_code_sss_loan,
                gl_code_pagibig_contribution,
                gl_code_pagibig_loan, 
                gl_code_philhealth,
                gl_code_coated,
                gl_code_hmo,
                gl_code_canteen,
                gl_code_deduction_one,
                gl_code_deduction_two, 
                gl_code_ml_fund,
                gl_code_opec,
                gl_code_over_appraisal,
                gl_code_vpo_collection,
                gl_code_installment_account, 
                gl_code_ticket,
                gl_code_mobile_bill,
                gl_code_sako,
                gl_code_sako_savings,
                sheet_name,
                uploaded_by,
                uploaded_date,
                post_edi;
            ";  
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {

            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            $first_row = mysqli_fetch_assoc($result);

            $operation_date = htmlspecialchars($first_row['operation_date']);
            $gl_code_income_tax = htmlspecialchars($first_row['gl_code_income_tax']);
            $gl_code_sss_contribution = htmlspecialchars($first_row['gl_code_sss_contribution']);
            $gl_code_sss_loan = htmlspecialchars($first_row['gl_code_sss_loan']);
            $gl_code_pagibig_contribution = htmlspecialchars($first_row['gl_code_pagibig_contribution']);
            $gl_code_pagibig_loan = htmlspecialchars($first_row['gl_code_pagibig_loan']);
            $gl_code_philhealth = htmlspecialchars($first_row['gl_code_philhealth']);
            $gl_code_coated = htmlspecialchars($first_row['gl_code_coated']);
            $gl_code_hmo = htmlspecialchars($first_row['gl_code_hmo']);
            $gl_code_canteen = htmlspecialchars($first_row['gl_code_canteen']);
            $gl_code_deduction_one = htmlspecialchars($first_row['gl_code_deduction_one']);
            $gl_code_deduction_two = htmlspecialchars($first_row['gl_code_deduction_two']);
            $gl_code_ml_fund = htmlspecialchars($first_row['gl_code_ml_fund']);
            $gl_code_opec = htmlspecialchars($first_row['gl_code_opec']);
            $gl_code_over_appraisal = htmlspecialchars($first_row['gl_code_over_appraisal']);
            $gl_code_vpo_collection = htmlspecialchars($first_row['gl_code_vpo_collection']);
            $gl_code_installment_account = htmlspecialchars($first_row['gl_code_installment_account']);
            $gl_code_ticket = htmlspecialchars($first_row['gl_code_ticket']);
            $gl_code_mobile_bill = htmlspecialchars($first_row['gl_code_mobile_bill']);
            $gl_code_sako = htmlspecialchars($first_row['gl_code_sako']);
            $gl_code_sako_savings = htmlspecialchars($first_row['gl_code_sako_savings']);


            //  first row
            echo "<tr>";
            echo "<th colspan='3'>OD Date - " . $operation_date . "</th>";
            echo "<th>INCOME TAX</th>";
            echo "<th>SSS CONTRIBUTION</th>";
            echo "<th>SSS LOAN</th>";
            echo "<th>PAGIBIG CONTRIBUTION</th>";
            echo "<th>PAGIBIG LOAN</th>";
            echo "<th>PHILHEALTH</th>";
            echo "<th>COATED</th>";
            echo "<th>HMO</th>";
            echo "<th>CANTEEN</th>";
            echo "<th>DEDUCTION 1</th>";
            echo "<th>DEDUCTION 2</th>";
            echo "<th>ML FUND</th>";
            echo "<th>OPEC</th>";
            echo "<th>OVER APPRAISAL</th>";
            echo "<th>VPO COLLECTION</th>";
            echo "<th>INSTALLMENT ACCOUNT</th>";
            echo "<th>TICKET</th>";
            echo "<th>MOBILE BILL</th>";
            echo "<th>SAKO</th>";
            echo "<th>SAKO SAVINGS</th>";
            echo "<th>Total Other Deductions</th>";
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "</tr>";
            //third row
            echo "<tr>";
            echo "<th style='white-space: nowrap'>BOS Code</th>";
            echo "<th>Branch Name</th>";
            echo "<th>Region</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
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
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            // echo "<th>". $gl_code_total ."</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalNumberOfBranches = 0;

            // Output the data rows
            mysqli_data_seek($result, 0); // Reset result pointer to the beginning
            while ($row = mysqli_fetch_assoc($result)) {
                $totalNumberOfBranches++;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['bos_code']) . "</td>";
                echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td>" . htmlspecialchars($row['income_tax']) . "</td>";
                echo "<td>" . htmlspecialchars($row['sss_contribution']) . "</td>";
                echo "<td>" . htmlspecialchars($row['sss_loan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['pagibig_contribution']) . "</td>";
                echo "<td>" . htmlspecialchars($row['pagibig_loan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['philhealth']) . "</td>";
                echo "<td>" . htmlspecialchars($row['coated']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['hmo']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['canteen']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['deduction_one']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['deduction_two']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['ml_fund']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['opec']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['over_appraisal']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['vpo_collection']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['installment_account']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['ticket']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['mobile_bill']) . "</td>"; 
                echo "<td>" . htmlspecialchars($row['sako']) . "</td>"; 
                echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['sako_savings']) . "</td>";
                // echo "<td style='white-space: nowrap'>" . htmlspecialchars(!empty($row['cost_center1']) ? $row['cost_center1'] : $row['cost_center']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ALL_OTHER_DEDUCTIONS']) . "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
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
