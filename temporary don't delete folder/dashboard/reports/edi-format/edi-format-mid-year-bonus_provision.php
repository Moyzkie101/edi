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

    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $status = $_SESSION['status'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        $payroll_date_format = date('F Y', strtotime($restrictedDate));
        $getYearMonth_date_format = date('Y-m', strtotime($restrictedDate));
        $getLastDay_date_format = date('t', strtotime($restrictedDate));

        // Fetch all rows matching the filters (not just for ALL)
        $dlsql = "SELECT 
            p.zone, p.region, p.payroll_date, p.branch_code, p.branch_name, 
            p.excess_pb, p.zone, p.cost_center, p.ml_matic_region,  
            COUNT(DISTINCT p.branch_code) AS branch_count, 
            SUM(p.excess_pb) AS total_excess_pb
        FROM 
            " . $database[0] . ".payroll_edi_report p
        WHERE p.payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
        AND p.description = 'midYearBonus'
        AND NOT p.payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
        AND NOT p.description = 'payroll'";
        if ($mainzone === 'ALL'){
            $dlsql .= " AND p.mainzone IN ('LNCR','VISMIN') ";
            if ($zone === 'ALL'){
                $dlsql .= " AND p.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN')";
            }
        }else{
            $dlsql .= " AND p.mainzone = '$mainzone' ";
            if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                $dlsql .= " AND p.ml_matic_region = '$zone' AND p.zone LIKE '%$region%' ";
            }else{
                $dlsql .= " AND p.zone = '$zone' AND p.region_code LIKE '%$region%' AND NOT ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
            }
        }
        $dlsql .= " AND (
                CASE 
                    WHEN p.ml_matic_status IN ('Active', 'Pending') THEN 'Active'
                    WHEN p.ml_matic_status = 'Inactive' THEN 'Inactive'
                END
            ) = '$status' 
                GROUP BY
                p.zone, p.region, p.payroll_date, p.branch_code, p.region, p.cost_center,
                p.branch_name, p.excess_pb, p.ml_matic_region
            ORDER BY p.branch_name asc";

        $dlresult = mysqli_query($conn, $dlsql);

        if (!$dlresult) {
            die("Query failed: " . mysqli_error($conn));
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- Begin: Multi-zone Excel and ZIP logic ---
        if($mainzone === 'ALL' || $zone === 'ALL') {
            if(mysqli_num_rows($dlresult) > 0) {
                // Prepare arrays for each group
                $groups = [
                    'EDI_Mid_Year_Provision_Report_LZN_' => [],
                    'EDI_Mid_Year_Provision_Report_NCR_' => [],
                    'EDI_Mid_Year_Provision_Report_VIS_' => [],
                    'EDI_Mid_Year_Provision_Report_MIN_' => [],
                    'EDI_Mid_Year_Provision_Report_NATIONWIDE-SHOWROOM_' => [],
                ];

                // Categorize each row
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    if (($row['zone'] === 'LZN') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Mid_Year_Provision_Report_LZN_'][] = $row;
                    } elseif (($row['zone'] === 'NCR') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Mid_Year_Provision_Report_NCR_'][] = $row;
                    } elseif (($row['zone'] === 'VIS') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Mid_Year_Provision_Report_VIS_'][] = $row;
                    } elseif (($row['zone'] === 'MIN') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Mid_Year_Provision_Report_MIN_'][] = $row;
                    } elseif ($row['ml_matic_region'] === 'LNCR Showroom' || $row['ml_matic_region'] === 'VISMIN Showroom') {
                        $groups['EDI_Mid_Year_Provision_Report_NATIONWIDE-SHOWROOM_'][] = $row;
                    }
                }

                // Prepare temp dir for files
                $tmpDir = sys_get_temp_dir() . '/provision_report_temp';
                if (!is_dir($tmpDir)) {
                    mkdir($tmpDir);
                }

                // For each group, create spreadsheet and save
                $filePaths = [];
                foreach ($groups as $groupName => $rows) {
                    if (empty($rows)) continue; // Skip empty groups

                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    // Header rows
                    $headerRow1 = [
                        'Mid-Year Provision for Bonus', '', '', '', '',
                    ];
                    $headerRow2 = [
                        'Code', 'Branch Name', 'mid', '13th', 'total',
                    ];
                    $sheet->fromArray($headerRow1, null, 'A1');
                    $sheet->fromArray($headerRow2, null, 'A2');
                    foreach (range('A', 'E') as $columnID) {
                        $sheet->getColumnDimension($columnID)->setAutoSize(true);
                    }
                    $sheet->getStyle('A1:E1')->getFont()->setBold(true);

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
                        $total = $row['total_excess_pb'];
                        $mid = $total;
                        $thirteenth = 0;
                        $overall = $mid + $thirteenth;

                        $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                        $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
                        $sheet->setCellValueExplicit('C' . $rowIndex, $mid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('D' . $rowIndex, $thirteenth, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('E' . $rowIndex, $overall, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                        if ($applyStyle) {
                            $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB($color);
                        }
                        $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFont()->setBold($bold);

                        $rowIndex++;
                    }

                    // Save file
                    $filename = $groupName . $payroll_date_format. '.xls';
                    $filePath = $tmpDir . '/' . $filename;
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
                    $writer->save($filePath);
                    $filePaths[] = $filePath;
                }

                // ZIP the files with the desired filename
                $zipFilename = 'EDI_Mid_Year_Provision_Report_' . $payroll_date_format . '.zip';
                $zipPath = $tmpDir . '/' . $zipFilename;
                $zip = new ZipArchive();
                $zip->open($zipPath, ZipArchive::CREATE);
                foreach ($filePaths as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                // Output ZIP with correct filename
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);

                // Cleanup
                foreach ($filePaths as $file) unlink($file);
                unlink($zipPath);
                rmdir($tmpDir);
                exit();
            }
        }else{
            if (mysqli_num_rows($dlresult) > 0) {
                $headerRow1 = [
                    'Mid Year Provision for Bonus', '', '', '', '',
                ];
                $headerRow2 = [
                    'Code', 'Branch Name', 'mid', '13th', 'total',
                ];
                
                $sheet->fromArray($headerRow1, null, 'A1');
                $sheet->fromArray($headerRow2, null, 'A2');

                foreach (range('A', 'E') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
        
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        
                $rowIndex = 3;
                $totalcount = 0;
        
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    $applyStyle = false; 
            
                    if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                        $color = '4fc917';  
                        $bold = true;       
                        $applyStyle = true; 
                    } else {
                        $bold = false;      
                    }

                    $total = $row['total_excess_pb'];
                    $mid = $total;
                    $thirteenth = 0;
                    $overall = $mid + $thirteenth;
        
                    $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                    $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);

                    // Use setCellValueExplicit for setting the value and format it as a number
                    $sheet->setCellValueExplicit('C' . $rowIndex, $mid, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('D' . $rowIndex, $thirteenth, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    $sheet->setCellValueExplicit('E' . $rowIndex, $overall, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                    $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                    if ($applyStyle) {
                        $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                    }
                
                    // Apply bold style regardless of background color
                    $sheet->getStyle('A' . $rowIndex . ':E' . $rowIndex)->getFont()->setBold($bold);

                    $rowIndex++;
                    $totalcount++;
                }
        
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                if($status==='Active'){
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        $filename = "EDI_Mid_Year_Provision_Report_" . $mainzone . "_" . $region . "_" . $payroll_date_format . ".xls";
                    }else{
                        $filename = "EDI_Mid_Year_Provision_Report_" . $zone . "_" . $region . "_" . $payroll_date_format . ".xls";
                    }
                    
                }else{
                    if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                        $filename = "EDI_Mid_Year_Provision_Report_" . $mainzone . "_" . $region . "_" . $payroll_date_format . "(Inactive or Pending).xls";
                    }else{
                        $filename = "EDI_Mid_Year_Provision_Report_" . $zone . "_" . $region . "_" . $payroll_date_format . "(Inactive or Pending).xls";
                    }
                }
                
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
        
                $writer = IOFactory::createWriter($spreadsheet, 'Xls');
                $writer->save('php://output');
                exit;
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
    <link rel="stylesheet" href="../../../assets/css/admin/provision-report/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <div class="top-content">
        <?php include '../../../templates/sidebar.php' ?>
    </div>

    <center><h2>Mid Year Bonus Provision Report <span style="font-size: 22px; color: red;">[EDI-Format]</span></h2></center>

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
                <label for="status">Status</label>
                <select name="status" id="status" autocomplete="off" required>
                    <option value="">Select Status</option>
                    <option value="Active">Active & Pending</option>
                    <option value="Inactive">Inactive</option>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Mid Year Date </label>
                <input type="month" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="proceed-btn" name="proceed" value="Proceed">
        </form>

        <div id="showdl" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>

    </div>
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proceed'])) {

    $mainzone = $_POST['mainzone'];
    $zone = $_POST['zone'];
    $region = $_POST['region'];
	$status = $_POST['status'];
	$_SESSION['status'] = $status;
    $restrictedDate = $_POST['restricted-date']; 

    $payroll_date_format = date('F Y', strtotime($restrictedDate));
    $getYearMonth_date_format = date('Y-m', strtotime($restrictedDate));
    $getLastDay_date_format = date('t', strtotime($restrictedDate));

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;


    $sql = "SELECT 
            p.zone, p.region, p.payroll_date, p.branch_code, p.branch_name, 
            p.excess_pb, p.zone, p.cost_center,  
            COUNT(DISTINCT p.branch_code) AS branch_count, 
            SUM(p.excess_pb) AS total_excess_pb
        FROM 
            " . $database[0] . ".payroll_edi_report p
        WHERE p.payroll_date BETWEEN '$getYearMonth_date_format-01' AND '$getYearMonth_date_format-$getLastDay_date_format'
        AND p.description = 'midYearBonus'
        AND NOT p.payroll_date IN ('$getYearMonth_date_format-15','$getYearMonth_date_format-$getLastDay_date_format')
        AND NOT p.description = 'payroll'
        ";
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
                    WHEN p.ml_matic_status IN ('Active', 'Pending') THEN 'Active'
                    WHEN p.ml_matic_status = 'Inactive' THEN 'Inactive'
                END
            ) = '$status' 
                GROUP BY
                p.zone, p.region, p.payroll_date, p.branch_code, p.region, p.cost_center,
                p.branch_name, p.excess_pb
            ORDER BY p.branch_name asc";
        // Get the result
        $result = mysqli_query($conn, $sql);

        if (!$result) {
            die("Query failed: " . mysqli_error($conn));
        }

        echo "<div class='table-wrapper'>"; // Start wrapper div
        
        // Check if there are results
        if (mysqli_num_rows($result) > 0) {
            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            // first row
            echo "<tr>";
            echo "<th colspan='6'>Mid Year Date : ".$payroll_date_format."</th>";
            echo "</tr>";

            // second row
            echo "<tr>";
            echo "<th>BOS CODE</th>";
            echo "<th>Branch Name</th>";
            echo "<th>Mid - Year</th>";
            echo "<th>13th Month</th>";
            echo "<th>TOTAL</th>";
            echo "<th>Region</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalthirteen = 0;
            $totalmid = 0;
            $totalcount = 0;

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

                $total = $row['total_excess_pb'];
                // $mid = $total * 2 / 9;
                $mid = $total;
                $thirteenth = 0;
                $overall = $mid + $thirteenth;

                
                echo "<tr>";
                echo "<td style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_code']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold;'>" . number_format($mid, 2) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold;'>" . number_format($thirteenth, 2) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold;'>" . number_format($overall, 2) . "</td>";
                echo "<td class='right' style='background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['region']) . "</td>";
                echo "</tr>";

                $totalmid += $mid;
                $totalthirteen += $thirteenth;
                $totalcount++;
                
            }

            if (empty($region)) {
                $region = "All Region";
            }

            echo "</tbody>";
            echo "</table>";

            echo "<div class='total-info'>";
            echo "<p><b>Main Zone: </b>$mainzone</p>";
            echo "<p><b>Zone: </b>$zone</p>";
            echo "<p><b>Region: </b>$region</p>";
            echo "<p><b>Mid Year Date: </b>".$payroll_date_format."</p>";
            echo "<p><b>Number of Branches: </b>$totalcount</p>";
            echo "<p><b>Total MID - Year: </b>" . number_format($totalmid, 2) . "</p>";
            echo "<p><b>Total 13TH Month: </b>" . number_format($totalthirteen, 2) . "</p>";
            echo "</div>";

            echo "</div>
            
            <script>
                var dlbtn = document.getElementById('showdl');
                dlbtn.style.display = 'block';  
            </script>";

        } else {
            echo "No results found.";
        }
    }
    echo "</div>"; // End wrapper div

?>