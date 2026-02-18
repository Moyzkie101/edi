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
    function checkDistinctRegionCode($conn1, $database, $branch_code, $kp_code, $kp_zone,$rm_code,$am_code,$zone) {
        if($zone === 'HO'||$kp_zone === 'HO'||$rm_code === 1||$am_code === 1){
            $query = "SELECT DISTINCT kpcode, code, zone FROM " . $database[1] . ".mlmatic_profile WHERE kpzone = '".$conn1->real_escape_string($kp_zone)."' AND zone = '".$conn1->real_escape_string($zone)."' AND rm = '".$conn1->real_escape_string($rm_code)."' AND am = '".$conn1->real_escape_string($am_code)."' AND uploaded_date = '2024-12-09'";
            $result = $conn1->query($query);
        }else{
            $query = "SELECT DISTINCT kp_code, code, zone FROM " . $database[1] . ".branch_profile WHERE code = '" . $conn1->real_escape_string($branch_code) . "' AND kp_code = '" . $conn1->real_escape_string($kp_code) . "' AND zone = '".$conn1->real_escape_string($kp_zone)."'";
            $result = $conn1->query($query);
        }
        
        if (!$result) {
            die("SQL Error: " . $conn1->error);
        }
        return $result->num_rows > 0; // Returns true if a match is found
    }

    $unmatchedRecords = [];
    $matchedRecords = [];
    $messages = []; // Define messages array

    if (isset($_POST['upload'])) {
        if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] != UPLOAD_ERR_OK) {
            die("File upload error: " . $_FILES['excelFile']['error']);
        }

        $file = $_FILES['excelFile']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; ++$row) {
            if (empty($worksheet->getCell('A' . $row)->getValue()) && empty($worksheet->getCell('B' . $row)->getValue())
                && empty($worksheet->getCell('C' . $row)->getValue())) {
                break;
            }

            $kp_code = $conn->real_escape_string(strval($worksheet->getCell('A' . $row)->getValue())); 
            $zone = $conn->real_escape_string(strval($worksheet->getCell('B' . $row)->getValue())); 
            $kp_zone = $conn->real_escape_string(strval($worksheet->getCell('C' . $row)->getValue()));
            $region = $conn->real_escape_string(strval($worksheet->getCell('D' . $row)->getValue())); 
            $kp_region = $conn->real_escape_string(strval($worksheet->getCell('E' . $row)->getValue())); 
            $gle_region = $conn->real_escape_string(strval($worksheet->getCell('F' . $row)->getValue())); 
            $area_code = $conn->real_escape_string(strval($worksheet->getCell('G' . $row)->getValue())); 
            $branches = $conn->real_escape_string(strval($worksheet->getCell('H' . $row)->getValue())); 
            $kp_branches = $conn->real_escape_string(strval($worksheet->getCell('I' . $row)->getValue())); 
            $rm_code = $conn->real_escape_string(intval($worksheet->getCell('J' . $row)->getValue())); 
            $am_code = $conn->real_escape_string(intval($worksheet->getCell('K' . $row)->getValue())); 
            $branch_code = $conn->real_escape_string(intval($worksheet->getCell('L' . $row)->getValue())); 
            $branch_status = $conn->real_escape_string(preg_replace('/^\s+|\s+$/u', '', $worksheet->getCell('M' . $row)->getValue()));

            $uploaded_by = $conn->real_escape_string($_SESSION['admin_name']);
            $uploaded_date = date('Y-m-d', strtotime($_POST['restricted-date']));

            if (!$kp_code || !$branch_code ||!$kp_zone){
                continue; // Skip empty rows
            }    

            if (checkDistinctRegionCode($conn1, $database, $branch_code, $kp_code, $kp_zone,$rm_code, $am_code, $zone)) {

                $matchedRecords[] = [
                    'kp_code' => $kp_code,
                    'zone' => $zone,
                    'kp_zone' => $kp_zone,
                    'region' => $region,
                    'kp_region' => $kp_region,
                    'gle_region' => $gle_region,
                    'area_code' => $area_code,
                    'branches' => $branches,
                    'kp_branches' => $kp_branches,
                    'rm_code' => $rm_code,
                    'am_code' => $am_code,
                    'branch_code' => $branch_code,
                    'branch_status' => $branch_status,
                    'uploaded_date' => $uploaded_date,
                    'uploaded_by' => $uploaded_by
                ];

            } else {

                $unmatchedRecords[] = [
                    'kp_code' => $kp_code,
                    'branch_code' => $branch_code,
                    'zone' => $kp_zone,
                    'row' => $row,
                    'message' => 'Branch code and KP code mismatch'
                ];

                $messages[] = [
                    'type' => 'error',
                    'sheet' => 'MLMatic Data',
                    'A' => $branch_code,
                    'B' => $kp_code,
                    'C' => $branches,
                    'V' => $region,
                    'message' => 'Branch code and KP code mismatch'
                ];
            }
        }

        // Store matched records in session for later use
        $_SESSION['un_matched_records'] = $unmatchedRecords;

        // Check if there are unmatched records
        if (!empty($unmatchedRecords)) {
            echo "Unmatched records found. Data upload aborted.";
        } else {
            // Proceed with inserting matched records if no unmatched records exist
            if (!empty($matchedRecords)) {
                foreach ($matchedRecords as $record) {
                    $insertQuery = "
                        INSERT INTO `$database[1]`.mlmatic_profile(
                            kpcode, 
                            zone, 
                            kpzone, 
                            mlmatic_region, 
                            kp_region, 
                            gl_region, 
                            area, 
                            branch_name, 
                            kp_branch_name, 
                            rm, 
                            am, 
                            code, 
                            mlmatic_status, 
                            uploaded_date, 
                            uploaded_by
                        ) VALUES (
                            '".$record['kp_code']."', 
                            '".$record['zone']."', 
                            '".$record['kp_zone']."', 
                            '".$record['region']."', 
                            '".$record['kp_region']."', 
                            '".$record['gle_region']."', 
                            '".$record['area_code']."', 
                            '".$record['branches']."', 
                            '".$record['kp_branches']."', 
                            '".$record['rm_code']."', 
                            '".$record['am_code']."', 
                            '".$record['branch_code']."', 
                            '".$record['branch_status']."', 
                            '".$record['uploaded_date']."', 
                            '".$record['uploaded_by']."'
                        )";

                    if (!$conn1->query($insertQuery)) {
                        die("Insert Error: " . $conn1->error);
                    }
                }

                // Perform the update query after successful insertion
                $updateQuery = "
                    UPDATE `$database[1]`.branch_profile AS bp
                    JOIN `$database[1]`.mlmatic_profile AS mp
                        ON mp.kpcode = bp.kp_code
                        AND mp.code = bp.code
                        AND mp.kpzone = bp.zone
                    JOIN `$database[1]`.region_masterfile AS rm
                        ON rm.region_description = mp.kp_region
                        AND rm.zone_code = mp.kpzone
                        AND rm.zone_code NOT IN ('VISMIN-SUPPORT','LNCR-SUPPORT','VISMIN-MANCOMM','LNCR-MANCOMM', 'HO')
                    JOIN `$database[1]`.zone_masterfile AS zm
                        ON zm.zone_code = mp.kpzone
                        AND zm.zone_code NOT IN ('VISMIN-SUPPORT','LNCR-SUPPORT','VISMIN-MANCOMM','LNCR-MANCOMM', 'HO')
                    LEFT JOIN `$database[1]`.kpx_branch_masterfile AS mkbm
                        ON mkbm.branch_id = bp.branch_id
                    SET
                        bp.alias = CONCAT(mp.zone, mp.code),
                        bp.branch_name = CASE 
                            WHEN bp.branch_id IS NOT NULL AND bp.branch_id != '' THEN mkbm.branch_name
                            ELSE mp.branch_name
                        END,
                        bp.corporate_name = CASE 
                            WHEN bp.branch_id IS NOT NULL AND bp.branch_id != '' THEN mkbm.corporate_name
                        END,
                        bp.region = (
                            SELECT trm.region_description
                            FROM `$database[1]`.region_masterfile AS trm
                            WHERE trm.region_description = mp.kp_region
                            AND trm.zone_code = mp.kpzone
                            LIMIT 1
                        ),
                        bp.gl_region = mp.gl_region,
                        bp.area = mp.area,
                        bp.cost_center = CONCAT(
                            CASE
                                WHEN mp.zone = 'JEW' THEN LPAD('1', 4, '0')
                                ELSE LPAD(mp.rm, 4, '0')
                            END,
                            '-',
                            LPAD(mp.code, 3, '0')
                        ),
                        bp.ml_matic_region = mp.mlmatic_region,
                        bp.ml_matic_status = mp.mlmatic_status,
                        bp.region_code = (
                            SELECT trm.region_code
                            FROM `$database[1]`.region_masterfile AS trm
                            WHERE trm.region_description = mp.kp_region
                            AND trm.zone_code = mp.kpzone
                            LIMIT 1
                        )
                    WHERE mp.uploaded_date = '".$uploaded_date."'
                    AND bp.code = mp.code
                    AND bp.kp_code = mp.kpcode
                    AND bp.zone = mp.kpzone
                    AND NOT (mp.zone = 'HO' OR mp.kpzone = 'HO' OR mp.rm = 1 OR mp.am = 1)
                ";

                if (!$conn->query($updateQuery)) {
                    die("Update Error: " . $conn->error);
                }

                echo "Data uploaded successfully.";
            }
        } 
    }

    // Handle write-db button
    if (isset($_POST['write-db'])) {
        if (isset($_SESSION['un_matched_records']) && !empty($_SESSION['un_matched_records'])) {
            foreach ($_SESSION['un_matched_records'] as $record) {
                $insertBranchQuery = "
                    INSERT INTO " . $database[1] . ".branch_profile (kp_code, zone, code, mainzone) 
                    SELECT 
                        '" . $conn1->real_escape_string($record['kp_code']) . "', 
                        '" . $conn1->real_escape_string($record['zone']) . "', 
                        '" . $conn1->real_escape_string($record['branch_code']) . "',
                        mzm.main_zone_code
                    FROM " . $database[1] . ".zone_masterfile AS mzm 
                    WHERE mzm.zone_code = '" . $conn1->real_escape_string($record['zone']) . "'";

                if (!$conn1->query($insertBranchQuery)) {
                    die("Insert Error: " . $conn1->error);
                }
            }
            
            // Clear session data after successful insert
            unset($_SESSION['un_matched_records']);
            unset($_SESSION['upload_date']);
            
            echo "Data written to branch_profile successfully.";
        } else {
            echo "No unmatched records to write to database.";
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
    <link rel="stylesheet" href="../assets/css/admin/import-file/bp-mp.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>
    <center><h2>Branch Profile from MLMatic <span>[DATA IMPORT]</span></h2></center>
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="form">
                <div class="cancel_date">
                    <label for="restricted-date">Import Date </label>
                    <input type="date" id="restricted-date" name="restricted-date" value="<?php echo date('Y-m-d'); ?>" required readonly>
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
                    <form method="POST" style="display: inline;">
                        <button type="submit" class="export-btn" name="write-db">
                            Write to Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="table-container">
        <?php if (!empty($unmatchedRecords)): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Row</th>
                    <th>KP Code</th>
                    <th>Branch Code</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unmatchedRecords as $record): ?>
                    <tr>
                        <td><?php echo $record['row']; ?></td>
                        <td><?php echo $record['kp_code']; ?></td>
                        <td><?php echo $record['branch_code']; ?></td>
                        <td><?php echo $record['message']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php  
        // After processing, hide loading overlay using JavaScript
        echo '<script>
            document.getElementById("loading-overlay").style.display = "none";

            var elements = document.getElementsByClassName("showEP");

            // Loop through each element and set its display style to "block"
            for (var i = 0; i < elements.length; i++) {
                elements[i].style.display = "block";
            }

            function printTable() {
                window.print();
            }

            function exportToPDF() {
                var form = document.createElement("form");
                form.method = "post";
                form.action = "bp-mp_export_pdf.php";

                var messagesInput = document.createElement("input");
                messagesInput.type = "hidden";
                messagesInput.name = "messages";
                messagesInput.value = JSON.stringify(' . json_encode($messages) . ');

                var payrollDateInput = document.createElement("input");
                payrollDateInput.type = "hidden";
                payrollDateInput.name = "payroll_date";
                payrollDateInput.value = "' . $_POST['restricted-date'] . '";

                var filenameInput = document.createElement("input");
                filenameInput.type = "hidden";
                filenameInput.name = "filename";
                filenameInput.value = "' . $_FILES['excelFile']['name'] . '";

                form.appendChild(messagesInput);
                form.appendChild(payrollDateInput);
                form.appendChild(filenameInput);
                document.body.appendChild(form);
                form.submit();
            }   
        </script>';
    endif;?>
    <script src="../assets/js/admin/import-file/script1.js"></script>

</body>

</html>