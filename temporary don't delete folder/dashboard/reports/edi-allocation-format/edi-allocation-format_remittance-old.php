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

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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
    
        $dlsql="WITH closed_branch_sums AS (
                    SELECT
                        region_code,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN ee_dr1 ELSE 0 END) AS sum_sss_ee_share,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN dr1 ELSE 0 END) AS sum_sss_er_share,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total_ee_er_dr1 ELSE 0 END) AS sum_sss_total_ee_er_share,

                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN ee_dr2 ELSE 0 END) AS sum_philhealth_ee_share,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN dr2 ELSE 0 END) AS sum_philhealth_er_share,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total_ee_er_dr2 ELSE 0 END) AS sum_philhealth_total_ee_er_share,

                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN ee_dr3 ELSE 0 END) AS sum_pagibig_ee_share,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN dr3 ELSE 0 END) AS sum_pagibig_er_share,
                        SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total_ee_er_dr3 ELSE 0 END) AS sum_pagibig_total_ee_er_share
                        
                    FROM " . $database[0] . ".remitance_edi_report
                    WHERE remitance_date = '$restrictedDate'
                    AND ml_matic_status IN ('Pending', 'Inactive')
                    GROUP BY region_code
                ),

                active_branch_count AS (
                    SELECT
                        region_code,
                        COUNT(*) AS active_count
                    FROM " . $database[0] . ".payroll_edi_report
                    WHERE payroll_date = '$restrictedDate'
                    AND ml_matic_status = 'Active'
                    GROUP BY region_code
                )

                SELECT 
                    err.remitance_date,
                    err.mainzone,
                    err.zone,
                    err.region,
                    err.ml_matic_region,
                    err.region_code,
                    err.branch_code,
                    err.branch_name,
                    
                    cbs.sum_sss_ee_share / NULLIF(abc.active_count, 0) AS ee_dr1,
                    err.ee_gl_code_dr1,
                    cbs.sum_sss_er_share / NULLIF(abc.active_count, 0) AS dr1,
                    err.gl_code_dr1,
                    cbs.sum_sss_total_ee_er_share / NULLIF(abc.active_count, 0) AS total_ee_er_dr1,
                    err.gl_code_total_ee_er_dr1,

                    cbs.sum_philhealth_ee_share / NULLIF(abc.active_count, 0) AS ee_dr2,
                    err.ee_gl_code_dr2,
                    cbs.sum_philhealth_er_share / NULLIF(abc.active_count, 0) AS dr2,
                    err.gl_code_dr2,
                    cbs.sum_philhealth_total_ee_er_share / NULLIF(abc.active_count, 0) AS total_ee_er_dr2,
                    err.gl_code_total_ee_er_dr2,

                    cbs.sum_pagibig_ee_share / NULLIF(abc.active_count, 0) AS ee_dr3,
                    err.ee_gl_code_dr3,
                    cbs.sum_pagibig_er_share / NULLIF(abc.active_count, 0) AS dr3,
                    err.gl_code_dr3,
                    cbs.sum_pagibig_total_ee_er_share / NULLIF(abc.active_count, 0) AS total_ee_er_dr3,
                    err.gl_code_total_ee_er_dr3,
                    err.gl_code_dr4,
                    err.cost_center
                    
                FROM " . $database[0] . ".remitance_edi_report AS err
                INNER JOIN closed_branch_sums cbs ON cbs.region_code = err.region_code
                INNER JOIN active_branch_count abc ON abc.region_code = err.region_code

                WHERE err.ml_matic_status = 'Active'
                    AND err.remitance_date = '$restrictedDate'";

                    if($mainzone === 'ALL') {
                        $dlsql .= " AND err.mainzone IN ('LNCR','VISMIN') ";
                        if($zone === 'ALL') {
                            $dlsql .= " AND err.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN') ";
                        }
                    } else {
                        $dlsql .= " AND err.mainzone = '$mainzone'";
                        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                            $dlsql .= " AND err.ml_matic_region = '$zone' AND err.zone LIKE '%$region%' ";
                        }else{
                            $dlsql .= " AND err.zone = '$zone' AND err.region_code LIKE '%$region%' AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom') ";
                        }
                    }


                // if($zone==='VIS'){
                //     $dlsql .= " AND err.zone = 'VIS'
                //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
                //     ";
                //     if(!empty($region)){
                //         $dlsql .= " AND err.region_code LIKE '%$region%'";
                //     }
                // }elseif($zone==='MIN'){
                //     $dlsql .= " AND err.zone = 'MIN'
                //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
                //     ";
                //     if(!empty($region)){
                //         $dlsql .= " AND err.region_code LIKE '%$region%'";
                //     }
                // }elseif($zone==='LZN'){
                //     $dlsql .= " AND err.zone = 'LZN'
                //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
                //     ";
                //     if(!empty($region)){
                //         $dlsql .= " AND err.region_code LIKE '%$region%'";
                //     }
                // }elseif($zone==='NCR'){
                //     $dlsql .= " AND err.zone = 'NCR'
                //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
                //     ";
                //     if(!empty($region)){
                //         $dlsql .= " AND err.region_code LIKE '%$region%'";
                //     }
                // }elseif($zone ==='VISMIN Showroom'){
                //     $dlsql .= " AND err.ml_matic_region = 'VISMIN Showroom'";
                // }elseif($zone ==='LNCR Showroom'){
                //     $dlsql .= " AND err.ml_matic_region = 'LNCR Showroom'";
                // }

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- Begin: Multi-zone Excel and ZIP logic ---
        if($mainzone === 'ALL' || $zone === 'ALL'){
            // Group data by rules
            $groups = [
                'EDI_Allocation_Remittance_Old-Format_Report_LZN_' => [],
                'EDI_Allocation_Remittance_Old-Format_Report_NCR_' => [],
                'EDI_Allocation_Remittance_Old-Format_Report_VIS_' => [],
                'EDI_Allocation_Remittance_Old-Format_Report_MIN_' => [],
                'EDI_Allocation_Remittance_Old-Format_Report_NATIONWIDE-SHOWROOM_' => [],
            ];

            // Fetch all data and group
            while ($row = mysqli_fetch_assoc($dlresult)) {
                if (($row['zone'] === 'LZN') && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                    $groups['EDI_Allocation_Remittance_Old-Format_Report_LZN_'][] = $row;
                } elseif (($row['zone'] === 'NCR') && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                    $groups['EDI_Allocation_Remittance_Old-Format_Report_NCR_'][] = $row;
                } elseif (($row['zone'] === 'VIS') && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                    $groups['EDI_Allocation_Remittance_Old-Format_Report_VIS_'][] = $row;
                } elseif (($row['zone'] === 'MIN') && !in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                    $groups['EDI_Allocation_Remittance_Old-Format_Report_MIN_'][] = $row;
                } elseif (in_array($row['ml_matic_region'], ['LNCR Showroom', 'VISMIN Showroom'])) {
                    $groups['EDI_Allocation_Remittance_Old-Format_Report_NATIONWIDE-SHOWROOM_'][] = $row;
                }
            }

            $tmpDir = sys_get_temp_dir() . '/edi_excel_' . uniqid();
            mkdir($tmpDir);
            
            // Prepare temp files for ZIP
            $filePaths = [];
            foreach ($groups as $groupName => $rows) {
                if (empty($rows)) continue;

                $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // Header rows
                $sheet->setCellValue('A1', 'PAYROLL REMITTANCE ALLOCATION');
                $sheet->setCellValue('C1', 'DR');
                $sheet->setCellValue('D1', 'DR');
                $sheet->setCellValue('E1', 'DR');
                $sheet->setCellValue('F1', 'CR');
                $sheet->setCellValue('G1', 'Cost Center');
                $sheet->setCellValue('A2', 'BC Code');
                $sheet->setCellValue('B2', 'BC Name');
                $sheet->setCellValue('C2', $rows[0]['gl_code_dr1']);
                $sheet->setCellValue('D2', $rows[0]['gl_code_dr2']);
                $sheet->setCellValue('E2', $rows[0]['gl_code_dr3']);
                $sheet->setCellValue('F2', 3100001);
                $sheet->setCellValue('G2', '');

                foreach (range('A', 'G') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $rowIndex = 3;
                foreach ($rows as $row) {
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
                    $total_amount = $row['dr1'] + $row['dr2'] + $row['dr3'];
                    $sheet->setCellValueExplicit('C' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    $sheet->setCellValueExplicit('E' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    $sheet->setCellValueExplicit('F' . $rowIndex, $total_amount, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    $sheet->setCellValue('G' . $rowIndex, $row['cost_center']);

                    if ($applyStyle) {
                        $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                    }
                    $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFont()->setBold($bold);

                    $rowIndex++;
                }

                // Save to temp file
                $filename = $groupName . $restrictedDate.'.xls';
                $filePath = $tmpDir . '/' . $filename;
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
                $writer->save($filePath);
                $filePaths[] = $filePath;
            }

            // Create ZIP
            $zipPath = $tmpDir . '/EDI_Allocation_Remittance_Old-Format_Report_' . $restrictedDate . '.zip';
            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE);
            foreach ($filePaths as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            // Output ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($zipPath) . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);

            // Cleanup
            foreach ($filePaths as $file) unlink($file);
            unlink($zipPath);
            rmdir($tmpDir);
            exit();
        }else{

            // Fetch the first row to get header data
            $first_row = mysqli_fetch_assoc($dlresult);

            $payroll_date = htmlspecialchars($first_row['remitance_date']);
            $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
            $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
            $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
            $gl_code_dr4 = htmlspecialchars(3100001);
            //$gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);

            // First row: DR and Cost Center headers
            $sheet->setCellValue('A1', 'PAYROLL REMITTANCE ALLOCATION');
            $sheet->setCellValue('C1', 'DR');
            $sheet->setCellValue('D1', 'DR');
            $sheet->setCellValue('E1', 'DR');
            $sheet->setCellValue('F1', 'CR');
            $sheet->setCellValue('G1', 'Cost Center');

            // Second row: Column headers
            $sheet->setCellValue('A2', 'BC Code');
            $sheet->setCellValue('B2', 'BC Name');
            $sheet->setCellValue('C2', $gl_code_dr1);
            $sheet->setCellValue('D2', $gl_code_dr2);
            $sheet->setCellValue('E2', $gl_code_dr3);
            $sheet->setCellValue('F2', $gl_code_dr4);
            $sheet->setCellValue('G2', '');

            // Make columns auto-size
            foreach (range('A', 'G') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Reset the result pointer to the beginning and set row index
            mysqli_data_seek($dlresult, 0);
            $rowIndex = 3; // Starting from the 4th row

            while ($row = mysqli_fetch_assoc($dlresult)) {

                $applyStyle = false; 
            
                if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                    $color = '4fc917';  
                    $bold = true;       
                    $applyStyle = true; 
                } else {
                    $bold = false;      
                }
            
                $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);

                $total_amount = $row['dr1'] + $row['dr2'] + $row['dr3'];
                
                // Use setCellValueExplicit for setting the value and format it as a number
                $sheet->setCellValueExplicit('C' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('E' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('F' . $rowIndex, $total_amount, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValue('G' . $rowIndex, $row['cost_center']);
            
                // Apply styles if flag is set
                if ($applyStyle) {
                    $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($color);
                }
            
                // Apply bold style regardless of background color
                $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFont()->setBold($bold);
            
                $rowIndex++;
            }

            // Set headers to force download the Excel file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

            if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                if(!empty($region)){
                    $filename = "EDI_Allocation_Remittance_Old-Format_Report_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
                }else{
                    $filename = "EDI_Allocation_Remittance_Old-Format_Report_" . $mainzone . "_" . $restrictedDate . ".xls";
                }
            }else{
                if(!empty($region)){
                    $filename = "EDI_Allocation_Remittance_Old-Format_Report_" . $zone .  "_" . $region . "_" . $restrictedDate . ".xls";
                } else {
                    $filename = "EDI_Allocation_Remittance_Old-Format_Report_" . $zone . "_" . $restrictedDate . ".xls";
                }
            }

            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Write and save the Excel file
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
            exit;       
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
    <link rel="icon" href="../../../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../../../assets/css/admin/remitance-report-edi/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <div class="top-content">
        <?php include '../../../templates/sidebar.php' ?>
    </div>

    <center><h2>Remitance Allocation Report <span style="font-size: 22px; color: red;">[EDI-Format OLD]</span></h2></center>

    <div class="import-file">
        
        <form action="" method="post">

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
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="proceed-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>

    </div>

    <script src="../../../assets/js/admin/remitance-report-edi/edi-allocation-format/script1.js"></script>
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
 
include '../../../config/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

$mainzone = $_POST['mainzone'];
$zone = $_POST['zone'];
$region = $_POST['region'];
$restrictedDate = $_POST['restricted-date']; 

$_SESSION['mainzone'] = $mainzone;
$_SESSION['zone'] = $zone;
$_SESSION['region'] = $region;
$_SESSION['restrictedDate'] = $restrictedDate; 

$sql="WITH closed_branch_sums AS (
        SELECT
            region_code,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN ee_dr1 ELSE 0 END) AS sum_sss_ee_share,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN dr1 ELSE 0 END) AS sum_sss_er_share,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total_ee_er_dr1 ELSE 0 END) AS sum_sss_total_ee_er_share,

            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN ee_dr2 ELSE 0 END) AS sum_philhealth_ee_share,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN dr2 ELSE 0 END) AS sum_philhealth_er_share,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total_ee_er_dr2 ELSE 0 END) AS sum_philhealth_total_ee_er_share,

            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN ee_dr3 ELSE 0 END) AS sum_pagibig_ee_share,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN dr3 ELSE 0 END) AS sum_pagibig_er_share,
            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total_ee_er_dr3 ELSE 0 END) AS sum_pagibig_total_ee_er_share
            
        FROM " . $database[0] . ".remitance_edi_report
        WHERE remitance_date = '$restrictedDate'
        AND ml_matic_status IN ('Pending', 'Inactive')
        GROUP BY region_code
    ),

    active_branch_count AS (
        SELECT
            region_code,
            COUNT(*) AS active_count
        FROM " . $database[0] . ".payroll_edi_report
        WHERE payroll_date = '$restrictedDate'
        AND ml_matic_status = 'Active'
        GROUP BY region_code
    )

    SELECT 
        err.remitance_date,
        err.mainzone,
        err.zone,
        err.region,
        err.ml_matic_region,
        err.region_code,
        err.branch_code,
        err.branch_name,
        
        cbs.sum_sss_ee_share / NULLIF(abc.active_count, 0) AS ee_dr1,
        err.ee_gl_code_dr1,
        cbs.sum_sss_er_share / NULLIF(abc.active_count, 0) AS dr1,
        err.gl_code_dr1,
        cbs.sum_sss_total_ee_er_share / NULLIF(abc.active_count, 0) AS total_ee_er_dr1,
        err.gl_code_total_ee_er_dr1,

        cbs.sum_philhealth_ee_share / NULLIF(abc.active_count, 0) AS ee_dr2,
        err.ee_gl_code_dr2,
        cbs.sum_philhealth_er_share / NULLIF(abc.active_count, 0) AS dr2,
        err.gl_code_dr2,
        cbs.sum_philhealth_total_ee_er_share / NULLIF(abc.active_count, 0) AS total_ee_er_dr2,
        err.gl_code_total_ee_er_dr2,

        cbs.sum_pagibig_ee_share / NULLIF(abc.active_count, 0) AS ee_dr3,
        err.ee_gl_code_dr3,
        cbs.sum_pagibig_er_share / NULLIF(abc.active_count, 0) AS dr3,
        err.gl_code_dr3,
        cbs.sum_pagibig_total_ee_er_share / NULLIF(abc.active_count, 0) AS total_ee_er_dr3,
        err.gl_code_total_ee_er_dr3,
        err.gl_code_dr4,
        err.cost_center
        
    FROM " . $database[0] . ".remitance_edi_report AS err
    INNER JOIN closed_branch_sums cbs ON cbs.region_code = err.region_code
    INNER JOIN active_branch_count abc ON abc.region_code = err.region_code

    WHERE err.ml_matic_status = 'Active'
        AND err.remitance_date = '$restrictedDate'";

        if($mainzone === 'ALL') {
            $sql .= " AND err.mainzone IN ('LNCR','VISMIN') ";
            if($zone === 'ALL') {
                $sql .= " AND err.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN') ";
            }
        } else {
            $sql .= " AND err.mainzone = '$mainzone'";
            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                $sql .= " AND err.ml_matic_region = '$zone' AND err.zone LIKE '%$region%' ";
            }else{
                $sql .= " AND err.zone = '$zone' AND err.region_code LIKE '%$region%' AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom') ";
            }
        }

    // if($zone==='VIS'){
    //     $sql .= " AND err.zone = 'VIS'
    //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
    //     ";
    //     if(!empty($region)){
    //         $sql .= " AND err.region_code LIKE '%$region%'";
    //     }
    // }elseif($zone==='MIN'){
    //     $sql .= " AND err.zone = 'MIN'
    //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
    //     ";
    //     if(!empty($region)){
    //         $sql .= " AND err.region_code LIKE '%$region%'";
    //     }
    // }elseif($zone==='LZN'){
    //     $sql .= " AND err.zone = 'LZN'
    //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
    //     ";
    //     if(!empty($region)){
    //         $sql .= " AND err.region_code LIKE '%$region%'";
    //     }
    // }elseif($zone==='NCR'){
    //     $sql .= " AND err.zone = 'NCR'
    //         AND NOT err.ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')
    //     ";
    //     if(!empty($region)){
    //         $sql .= " AND err.region_code LIKE '%$region%'";
    //     }
    // }elseif($zone ==='VISMIN Showroom'){
    //     $sql .= " AND err.ml_matic_region = 'VISMIN Showroom'";
    // }elseif($zone ==='LNCR Showroom'){
    //     $sql .= " AND err.ml_matic_region = 'LNCR Showroom'";
    // }

    //echo $sql;
    $result = mysqli_query($conn, $sql);

     // Check if there are results
    if (mysqli_num_rows($result) > 0) {

        // Output the table header
        echo "<div class='table-container'>";
        echo "<table>";
        echo "<thead>";
        
            
        $first_row = mysqli_fetch_assoc($result);

        $payroll_date = htmlspecialchars($first_row['remitance_date']);
        $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
        $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
        $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
        $gl_code_dr4 = htmlspecialchars(3100001);
		//$gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);

        //  first row
        echo "<tr>";
        echo "<th colspan='2'>Remitance Date : ". $payroll_date ."</th>";
        echo "<th>DR</th>";
        echo "<th>DR</th>";
        echo "<th>DR</th>";
        echo "<th>DR</th>";
        echo "<th>Cost Center</th>";
        echo "<th>Region</th>";
        echo "</tr>";

        // second row
        echo "<tr>";
        echo "<th>BOS Code</th>";
        echo "<th>Branch Name</th>";
        echo "<th>". $gl_code_dr1 ."</th>";
        echo "<th>". $gl_code_dr2 ."</th>";
        echo "<th>". $gl_code_dr3 ."</th>";
        echo "<th>". $gl_code_dr4 ."</th>";
        echo "<th></th>";
        echo "<th></th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $totalNumberOfBranches = 0;

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
            
            $total_amount = $row['dr1'] + $row['dr2'] + $row['dr3'];

            echo "<tr>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_code'],) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name'],) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['dr1'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['dr2'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['dr3'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($total_amount,2)) . "</td>";
            //echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['cost_center']) . "</td>";
            echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
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