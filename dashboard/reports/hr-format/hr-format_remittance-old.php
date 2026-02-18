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
                    // Handle HRMD role - allow access to this page
                    $hasRequiredRole = true;
                    break;
                case 'CAD':
                    // Handle CAD role - no access to this page
                    break;
                case 'ML FUND':
                    // Handle ML FUND role - no access to this page
                    break;
                case 'KP DOMESTIC':
                    // Handle KP DOMESTIC role - no access to this page
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

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

    if(isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        $dlsql = "SELECT r.*
                FROM
                    " . $database[0] . ".remitance r
                WHERE
                    r.mainzone = '$mainzone'
                AND (r.zone = '$zone' OR r.zone = 'JVIS')
                AND r.region_code LIKE '%$region%'
                AND r.remitance_date = '$restrictedDate'
                ORDER BY 
                    r.region;"; 

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Fetch the first row to get header data
        $first_row = mysqli_fetch_assoc($dlresult);

        $remitance_date = htmlspecialchars($first_row['remitance_date']);
        $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
        $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
        $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
        $gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);

        // First row: Payroll Date
        $sheet->setCellValue('A1', 'Remitance Date: ' . $remitance_date);
        $sheet->mergeCells('A1:H1');

        // Second row: DR and Cost Center headers
        $sheet->setCellValue('C2', 'DR');
        $sheet->setCellValue('D2', 'DR');
        $sheet->setCellValue('E2', 'DR');
        $sheet->setCellValue('F2', 'DR');
        $sheet->setCellValue('G2', 'Cost Center');
        $sheet->mergeCells('A2:B2');

        // Third row: Column headers
        $sheet->setCellValue('A3', 'BOS Code');
        $sheet->setCellValue('B3', 'Branch Name');
        $sheet->setCellValue('C3', $gl_code_dr1);
        $sheet->setCellValue('D3', $gl_code_dr2);
        $sheet->setCellValue('E3', $gl_code_dr3);
        $sheet->setCellValue('F3', $gl_code_dr4);
        $sheet->setCellValue('G3', '');
        $sheet->setCellValue('H2', 'Region');

        // Apply styles to header rows
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A2:H2')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);

        // Make columns auto-size
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Reset the result pointer to the beginning and set row index
        mysqli_data_seek($dlresult, 0);
        $rowIndex = 4; // Starting from the 4th row

        while ($row = mysqli_fetch_assoc($dlresult)) {
            $sheet->setCellValue('A' . $rowIndex, $row['bos_code']);
            $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
            
            // Use setCellValueExplicit for setting the value and format it as a number
            $sheet->setCellValueExplicit('C' . $rowIndex, $row['dr1'], DataType::TYPE_NUMERIC);
            $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr2'], DataType::TYPE_NUMERIC);
            $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValueExplicit('E' . $rowIndex, $row['dr3'], DataType::TYPE_NUMERIC);
            $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValueExplicit('F' . $rowIndex, $row['dr4'], DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValue('G' . $rowIndex, $row['cost_center']);
            $sheet->setCellValue('H' . $rowIndex, $row['region']);

            $rowIndex++;
        }

        // Set headers to force download the Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filename = "Remitance_Report_HRMD-Format_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Write and save the Excel file
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        
?>
    <script>
        window.location.href='hr-format_remittance-old.php';
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
        }
        .generate-btn {
            background-color: #db120b; 
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
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
    </style>
    
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>Remitance Report <span>[HRMD-Format OLD]</span></h2></center>

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

    <script src="<?php echo $relative_path; ?>assets/js/admin/report-remitance-hr/hr-format/script1.js"></script>
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
    
    $sql = "SELECT r.*
            FROM
                " . $database[0] . ".remitance r
            WHERE
                r.mainzone = '$mainzone'
            AND (r.zone = '$zone' OR r.zone = 'JVIS')
            AND r.region_code LIKE '%$region%'
            AND r.remitance_date = '$restrictedDate'
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
            $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
            $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
            $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
            $gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);


            //  first row
            echo "<tr>";
            echo "<th colspan='2'>Remitance Date - " . $remitance_date . "</th>";
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

                $totalNumberOfBranches++;

                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['bos_code']) . "</td>";
                echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dr1']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dr2']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dr3']) . "</td>";
                echo "<td>" . htmlspecialchars($row['dr4']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cost_center']) . "</td>";
                echo "<td>" . htmlspecialchars($row['region']) . "</td>";
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