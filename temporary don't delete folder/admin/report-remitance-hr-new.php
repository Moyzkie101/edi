<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    include '../config/connection.php';
    require '../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;

    if(isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
	
		//AND (r.zone = '$zone' OR r.zone = 'JVIS')
        $dlsql = "SELECT r.*
                FROM
                    " . $database[0] . ".remitance r
                WHERE
                    r.mainzone = '$mainzone'
				AND (
                    CASE 
                        WHEN r.zone = 'VIS' OR r.zone = 'JVIS' THEN 'VIS'
						WHEN r.zone = 'MIN' THEN 'MIN'
						WHEN r.zone = 'LZN' THEN 'LZN'
						WHEN r.zone = 'NCR' THEN 'NCR'
                    END
                ) = '$zone'
                AND r.region_code LIKE '%$region%'
                AND r.remitance_date = '$restrictedDate'
                AND r.remitance_format_type = 'NEW'
                ORDER BY 
                    r.region;"; 

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Fetch the first row to get header data
        $first_row = mysqli_fetch_assoc($dlresult);

        $remitance_date = htmlspecialchars($first_row['remitance_date']);

        $ee_gl_code_dr1 = htmlspecialchars($first_row['ee_gl_code_dr1']);
        $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
        $gl_code_total_ee_er_dr1 = htmlspecialchars($first_row['gl_code_total_ee_er_dr1']);

        $ee_gl_code_dr2 = htmlspecialchars($first_row['ee_gl_code_dr2']);
        $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
        $gl_code_total_ee_er_dr2 = htmlspecialchars($first_row['gl_code_total_ee_er_dr2']);

        $ee_gl_code_dr3 = htmlspecialchars($first_row['ee_gl_code_dr3']);
        $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
        $gl_code_total_ee_er_dr3 = htmlspecialchars($first_row['gl_code_total_ee_er_dr3']);
        
        $gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);

        // First row: Remitance Date
        $sheet->setCellValue('A1', 'Remitance Date: ' . $remitance_date)->mergeCells('A1:B2')->getStyle('A1:B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('C1','SSS')->mergeCells('C1:G1')->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('H1','PHILHEALTH')->mergeCells('H1:J1')->getStyle('H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('K1','PAGIBIG')->mergeCells('K1:O1')->getStyle('K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        // $sheet->setCellValue('P1','TOTAL EE SHARE')->mergeCells('P1:P4')->getStyle('P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        // $sheet->setCellValue('Q1','TOTAL ER SHARE')->mergeCells('Q1:Q4')->getStyle('Q1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        // $sheet->setCellValue('R1','REGION')->mergeCells('R1:R4')->getStyle('R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('P1','REGION')->mergeCells('P1:P4')->getStyle('P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Second row: DR and Cost Center headers
        $sheet->setCellValue('C2', 'EE PREMIUM')->mergeCells('C2')->getStyle('C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('D2', 'ER PREMIUM')->mergeCells('D2')->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('E2', 'PAYABLE')->mergeCells('E2')->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('F2', 'EE LOAN')->mergeCells('F2')->getStyle('F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('G2', 'PAYABLE')->mergeCells('G2')->getStyle('G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('H2', 'EE PREMIUM')->mergeCells('H2')->getStyle('H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('I2', 'ER PREMIUM')->mergeCells('I2')->getStyle('I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('J2', 'PAYABLE')->mergeCells('J2')->getStyle('J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('K2', 'EE PREMIUM')->mergeCells('K2')->getStyle('K2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('L2', 'ER PREMIUM')->mergeCells('L2')->getStyle('L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('M2', 'PAYABLE')->mergeCells('M2')->getStyle('M2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('N2', 'EE LOAN')->mergeCells('N2')->getStyle('N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('O2', 'PAYABLE')->mergeCells('O2')->getStyle('O2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Third row: Column headers
        $sheet->setCellValue('A3', 'BOS Code')->mergeCells('A3:A4')->getStyle('A3:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('B3', 'Branch Name')->mergeCells('B3:B4')->getStyle('B3:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('C3', $ee_gl_code_dr1)->mergeCells('C3')->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('D3', $gl_code_dr1)->mergeCells('D3')->getStyle('D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('E3', $gl_code_total_ee_er_dr1)->mergeCells('E3')->getStyle('E3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('F3', '');
        $sheet->setCellValue('G3', '');

        $sheet->setCellValue('H3', $ee_gl_code_dr2)->mergeCells('H3')->getStyle('H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('I3', $gl_code_dr2)->mergeCells('I3')->getStyle('I3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('J3', $gl_code_total_ee_er_dr2)->mergeCells('J3')->getStyle('J3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('K3', $ee_gl_code_dr3)->mergeCells('K3')->getStyle('K3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('L3', $gl_code_dr3)->mergeCells('L3')->getStyle('L3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('M3', $gl_code_total_ee_er_dr3)->mergeCells('M3')->getStyle('M3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('N3', '');
        $sheet->setCellValue('O3', '');

        // Fourth row: Column headers

        $sheet->setCellValue('C4', 'DR')->mergeCells('C4')->getStyle('C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('D4', 'DR')->mergeCells('D4')->getStyle('D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('E4', 'CR')->mergeCells('E4')->getStyle('E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('F4', 'DR')->mergeCells('F4')->getStyle('F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('G4', 'CR')->mergeCells('G4')->getStyle('G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('H4', 'DR')->mergeCells('H4')->getStyle('H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('I4', 'DR')->mergeCells('I4')->getStyle('I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('J4', 'CR')->mergeCells('J4')->getStyle('J4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->setCellValue('K4', 'DR')->mergeCells('K4')->getStyle('K4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('L4', 'DR')->mergeCells('L4')->getStyle('L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('M4', 'CR')->mergeCells('M4')->getStyle('M4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('N4', 'DR')->mergeCells('N4')->getStyle('N4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->setCellValue('O4', 'CR')->mergeCells('O4')->getStyle('O4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Apply styles to header rows
        // $sheet->getStyle('A1:R1')->getFont()->setBold(true);
        // $sheet->getStyle('A2:R2')->getFont()->setBold(true);
        // $sheet->getStyle('A3:R3')->getFont()->setBold(true);
        // $sheet->getStyle('A4:R4')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A2:P2')->getFont()->setBold(true);
        $sheet->getStyle('A3:P3')->getFont()->setBold(true);
        $sheet->getStyle('A4:P4')->getFont()->setBold(true);

        // Set borders for header rows
        // $sheet->getStyle('A1:R4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A1:P4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Make columns auto-size
        // foreach (range('A', 'R') as $columnID) {
        //     $sheet->getColumnDimension($columnID)->setAutoSize(true);
        // }
        foreach (range('A', 'P') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Reset the result pointer to the beginning and set row index
        mysqli_data_seek($dlresult, 0);
        $rowIndex = 5; // Starting from the 5th row

        // while ($row = mysqli_fetch_assoc($dlresult)) {
        //     $sheet->setCellValue('A' . $rowIndex, $row['bos_code'])->mergeCells('A'. $rowIndex)->getStyle('A'. $rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        //     $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);

        //     $columns = ['C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
    
        //     foreach ($columns as $col) {
        //         $cell = $col . $rowIndex;
        //         $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        //     }
            
        //     // Use setCellValueExplicit for setting the value and format it as a number
        //     $sheet->setCellValueExplicit('C' . $rowIndex, $row['ee_dr1'], DataType::TYPE_NUMERIC)->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr1'], DataType::TYPE_NUMERIC)->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('E' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('F' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('G' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
        //     $sheet->setCellValueExplicit('H' . $rowIndex, $row['ee_dr2'], DataType::TYPE_NUMERIC)->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('I' . $rowIndex, $row['dr2'], DataType::TYPE_NUMERIC)->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('J' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
        //     $sheet->setCellValueExplicit('K' . $rowIndex, $row['ee_dr3'], DataType::TYPE_NUMERIC)->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('L' . $rowIndex, $row['dr3'], DataType::TYPE_NUMERIC)->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('M' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('N' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        //     $sheet->setCellValueExplicit('O' . $rowIndex, 0, DataType::TYPE_NUMERIC)->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
        
        //     // Set borders for each row of data
        //     $sheet->getStyle('A' . $rowIndex . ':O' . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        //     $rowIndex++;
        // }

        while ($row = mysqli_fetch_assoc($dlresult)) {
            // Set branch details
            $sheet->setCellValue('A' . $rowIndex, $row['bos_code'])
                ->mergeCells('A' . $rowIndex)
                ->getStyle('A' . $rowIndex)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
            
            // Define columns and values
            $data = [
                'C' => $row['ee_dr1'], 'D' => $row['dr1'], 'E' => $total_sss=$row['ee_dr1']+$row['dr1'], 'F' => 0, 'G' => 0,
                'H' => $row['ee_dr2'], 'I' => $row['dr2'], 'J' => $totalphilhealt=$row['ee_dr2']+$row['dr2'],
                'K' => $row['ee_dr3'], 'L' => $row['dr3'], 'M' => $total_pagibig=$row['ee_dr3']+$row['dr3'], 'N' => 0, 'O' => 0,
                //'P' => $row['total_ee'], 'Q' => $row['dr4']
            ];
            
            foreach ($data as $col => $value) {
                $cell = $col . $rowIndex;
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_NUMERIC)
                    ->getStyle($cell)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                // Align entire columns C5 to O5 to the right
                //$sheet->getStyle('C5:Q5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('C5:O5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            }
            // $sheet->setCellValue('R' . $rowIndex, $row['region'])
            //     ->mergeCells('R' . $rowIndex)
            //     ->getStyle('R' . $rowIndex)
            //     ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            //     ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('P' . $rowIndex, $row['region'])
                ->mergeCells('P' . $rowIndex)
                ->getStyle('P' . $rowIndex)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            // Set borders for the row
            // $sheet->getStyle("A{$rowIndex}:R{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$rowIndex}:P{$rowIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            $rowIndex++;
        }

        


        // Set headers to force download the Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $filename = "Remitance_Report_HRMD-Format_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
        } else {
            $filename = "Remitance_Report_HRMD-Format_" . $zone . "_" . $region . "_" . $restrictedDate . ".xls";
        }
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Write and save the Excel file
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        
?>
    <script>
        window.location.href='report-remitance-hr-new.php';
    </script>
<?php
        exit;

    }

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../assets/css/admin/report-remitance-hr/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        td{
            text-align: right;
        }
        .word {
            text-align: center;
            font-weight: bold;
        }
    </style>
    
</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>Remitance Report <span style="font-size: 22px; color: red;">[HRMD-Format NEW]</span></h2></center>

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

    <script src="../assets/js/admin/report-remitance-hr/script1.js"></script>
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
    
	//AND (r.zone = '$zone' OR r.zone = 'JVIS')
    $sql = "SELECT r.*
            FROM
                " . $database[0] . ".remitance r
            WHERE
                r.mainzone = '$mainzone'
            AND (
                    CASE 
                        WHEN r.zone = 'VIS' OR r.zone = 'JVIS' THEN 'VIS'
                        WHEN r.zone = 'MIN' THEN 'MIN'
						WHEN r.zone = 'LZN' THEN 'LZN'
						WHEN r.zone = 'NCR' THEN 'NCR'
                    END
                ) = '$zone'
            AND r.region_code LIKE '%$region%'
            AND r.remitance_date = '$restrictedDate'
            AND r.remitance_format_type = 'NEW'
            ORDER BY 
                r.region;"; 
    
    // Get the result
    //echo $sql;
    $result = mysqli_query($conn, $sql);

         // Check if there are results
         if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            $first_row = mysqli_fetch_assoc($result);

            $remitance_date = htmlspecialchars($first_row['remitance_date']);

            $ee_gl_code_dr1 = htmlspecialchars($first_row['ee_gl_code_dr1']);
            $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
            $gl_code_ee_er_dr1 = htmlspecialchars($first_row['gl_code_total_ee_er_dr1']);

            $ee_gl_code_dr2 = htmlspecialchars($first_row['ee_gl_code_dr2']);
            $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
            $gl_code_ee_er_dr2 = htmlspecialchars($first_row['gl_code_total_ee_er_dr2']);

            $ee_gl_code_dr3 = htmlspecialchars($first_row['ee_gl_code_dr3']);
            $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
            $gl_code_ee_er_dr3 = htmlspecialchars($first_row['gl_code_total_ee_er_dr3']);


            $gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);


            //  first row
            echo "<tr>";
                echo "<th colspan='2' rowspan='2'>Remitance Date: <u>". $remitance_date ."</u></th>";
                echo "<th style='color: red;' colspan='5'>SSS</th>";
                echo "<th></th>";
                echo "<th style='color: red;' colspan='3'>PHILHEALTH</th>";
                echo "<th></th>";
                echo "<th style='color: red;' colspan='5'>PAGIBIG</th>";
                echo "<th></th>";
                // echo "<th rowspan='4'>TOTAL EE SHARE</th>";
                // echo "<th rowspan='4'>TOTAL ER SHARE</th>";
                echo "<th rowspan='4'>REGION</th>";
                echo "<th rowspan='4'>ZONE CODE</th>";
            echo "</tr>";
            // second row
            echo "<tr>";
                echo "<th>EE PREMIUM</th>";
                echo "<th>ER PREMIUM</th>";
                echo "<th style='color: darkred;'>PAYABLE</th>";
                echo "<th>EE LOAN</th>";
                echo "<th style='color: darkred;'>PAYABLE</th>";
                echo "<th></th>";
                echo "<th>EE PREMIUM</th>";
                echo "<th>ER PREMIUM</th>";
                echo "<th style='color: darkred;'>PAYABLE</th>";
                echo "<th></th>";
                echo "<th>EE PREMIUM</th>";
                echo "<th>ER PREMIUM</th>";
                echo "<th style='color: darkred;'>PAYABLE</th>";
                echo "<th>EE LOAN</th>";
                echo "<th style='color: darkred;'>PAYABLE</th>";
                echo "<th></th>";
                // echo "<th>". $gl_code_dr2 ."</th>";
                // echo "<th>". $gl_code_dr4 ."</th>";
            echo "</tr>";
                // third row
            echo "<tr>";
                echo "<th rowspan='2'>BOS Code</th>";
                echo "<th rowspan='2'>Branch Name</th>";
                echo "<th>". $ee_gl_code_dr1 ."</th>";
                echo "<th>". $gl_code_dr1 ."</th>";
                echo "<th>". $gl_code_ee_er_dr1 ."</th>";
                echo "<th></th>";
                echo "<th></th>";
                echo "<th></th>";
                echo "<th>". $ee_gl_code_dr2 ."</th>";
                echo "<th>". $gl_code_dr2 ."</th>";
                echo "<th>". $gl_code_ee_er_dr2 ."</th>";
                echo "<th></th>";
                echo "<th>". $ee_gl_code_dr3 ."</th>";
                echo "<th>". $gl_code_dr3 ."</th>";
                echo "<th>". $gl_code_ee_er_dr3 ."</th>";
                echo "<th></th>";
                echo "<th></th>";
                echo "<th></th>";
            echo "</tr>";
            // fourth row
            echo "<tr>";
                echo "<th>DR</th>";
                echo "<th>DR</th>";
                echo "<th>CR</th>";
                echo "<th>DR</th>";
                echo "<th>CR</th>";
                echo "<th></th>";
                echo "<th>DR</th>";
                echo "<th>DR</th>";
                echo "<th>CR</th>";
                echo "<th></th>";
                echo "<th>DR</th>";
                echo "<th>DR</th>";
                echo "<th>CR</th>";
                echo "<th>DR</th>";
                echo "<th>CR</th>";
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
                    echo "<td class='word'>" . htmlspecialchars($row['bos_code']) . "</td>";
                    echo "<td class='word'>" . htmlspecialchars($row['branch_name']) . "</td>";
                    
                    echo "<td>" . htmlspecialchars(number_format($row['ee_dr1'],2)) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['dr1'],2)) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['total_ee_er_dr1'],2)) . "</td>";
                    echo "<td></td>";
                    echo "<td></td>";
                    echo "<td></td>";
                    echo "<td>" . htmlspecialchars(number_format($row['ee_dr2'],2)) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['dr2'],2)) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['total_ee_er_dr2'],2)) . "</td>";
                    echo "<td></td>";
                    echo "<td>" . htmlspecialchars(number_format($row['ee_dr3'],2)) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['dr3'],2)) . "</td>";
                    echo "<td>" . htmlspecialchars(number_format($row['total_ee_er_dr3'],2)) . "</td>";
                    echo "<td></td>";
                    echo "<td></td>";
                    echo "<td></td>";
                    // echo "<td>" . htmlspecialchars(number_format($row['total_ee'],2)) . "</td>";
                    // echo "<td>" . htmlspecialchars(number_format($row['dr4'],2)) . "</td>";
                    echo "<td class='word'>" . htmlspecialchars($row['region']) . "</td>";
                    echo "<td class='word'>" . htmlspecialchars($row['zone']) . "</td>";
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