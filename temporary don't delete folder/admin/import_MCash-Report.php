<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    include '../config/connection.php';
	require '../vendor/autoload.php';

	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// Function to check distinct region_code
function checkDistinctRegionCode($conn,$database, $regionCode) {
    $query = "SELECT DISTINCT region_code FROM " . $database[1] . ".region_masterfile WHERE region_code = '" . $conn->real_escape_string($regionCode) . "'";
    $result = $conn->query($query);
    return $result->num_rows > 0; // Returns true if a match is found
}

// Handle form submission
if (isset($_POST['upload'])) {
    $mainzone = $_POST['mainzone'];
    $payrollDate = $_POST['restricted-date'];

    if (isset($_FILES['excelFile']['tmp_name'])) {
        $file = $_FILES['excelFile']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; ++$row) {
            // Check for blank rows by verifying key cells are not empty
            if (empty($worksheet->getCell('A' . $row)->getValue()) && empty($worksheet->getCell('B' . $row)->getValue())
                && empty($worksheet->getCell('C' . $row)->getValue())) {
                break;
            }

            $region_code = $conn->real_escape_string(strval($worksheet->getCell('A' . $row)->getValue())); //region_code
            $region_name = $conn->real_escape_string(strval($worksheet->getCell('B' . $row)->getValue())); //region_name

            $no_employee_mlwallet = $conn->real_escape_string(floatval($worksheet->getCell('C' . $row)->getValue())); //no_employee_mlwallet
            $mlwallet_amount = $conn->real_escape_string(floatval($worksheet->getCell('D' . $row)->getValue())); //mlwallet_amount
            $no_employee_mlkp = $conn->real_escape_string(floatval($worksheet->getCell('E' . $row)->getValue())); //no_employee_mlkp
            $mlkp_amount = $conn->real_escape_string(floatval($worksheet->getCell('F' . $row)->getValue())); //mlkp_amount
            // $total_employee = $conn->real_escape_string(floatval($worksheet->getCell('G' . $row)->getValue())); //total_employee
            //$mcash_total_amount = $conn->real_escape_string(floatval($worksheet->getCell('H' . $row)->getValue())); //mcash_total_amount
            $uploaded_by = $conn->real_escape_string($_SESSION['admin_name']);
            date_default_timezone_set('Asia/Manila');
            $uploaded_date = date('Y-m-d H:i:s');

            // Check if region_code exists in " . $database[1] . ".region_masterfile
            if (checkDistinctRegionCode($conn, $database, $region_code)) {
                // Insert data into edi.mcash
                $mcash_total_amount = $mlwallet_amount + $mlkp_amount; // Calculate total amount
                $total_employee = $no_employee_mlwallet + $no_employee_mlkp; // Calculate total amount
                $insertQuery = "
                    INSERT INTO " . $database[0] . ".mcash (
                        mcash_date, mcash_mainzone, region_code, region_name, 
                        no_employee_mlwallet, mlwallet_amount, no_employee_mlkp, mlkp_amount, 
                        total_employee, mcash_total_amount,mcash_type, uploaded_by, uploaded_date
                    ) VALUES (
                        '$payrollDate', '$mainzone', '$region_code', '$region_name', 
                        $no_employee_mlwallet, $mlwallet_amount, $no_employee_mlkp, $mlkp_amount, 
                        $total_employee, $mcash_total_amount, 'Data-Entry', '$uploaded_by', '$uploaded_date'
                    )
                ";
                $conn->query($insertQuery);
            }
        }
        echo "Data uploaded successfully.";
    } else {
        echo "Please upload a valid Excel file.";
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
    <link rel="stylesheet" href="../assets/css/admin/import-file/style1.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printableTable, #printableTable * {
                visibility: visible;
            }
            #printableTable {
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>

</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>
    <center><h2>MCash REPORT <span>[IMPORT]</span></h2></center>
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="form">
                <div class="cancel_date">
                    <label for="mainzone">Mainzone </label>
                    <select name="mainzone" id="mainzone" required>
                        <option value="">Select Mainzone</option>
                        <option value="VISMIN">VISMIN</option>
                        <option value="LNCR">LNCR</option>
                    </select>
                    <!-- <div class="custom-arrow"></div> -->
                </div>
                <div class="cancel_date">
                    <label for="restricted-date">Payroll date </label>
                    <input type="date" id="restricted-date" name="restricted-date" required>
                </div>
                <div class="choose-file">
                    <div class="import-file">
                        <input type="file" name="excelFile" accept=".xls,.xlsx" class="form-control" required />
                        <input type="submit" class="upload-btn" name="upload" value="Upload">
                    </div>
                </div>
            </form>
            <div class="display_data">
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
        </div>
    </div>

    <script src="../assets/js/admin/import-file/script1.js"></script>

</body>

</html>