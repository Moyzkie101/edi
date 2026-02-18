<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    include '../config/connection.php';
	require '../vendor/autoload.php';

	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

    // Function to check distinct branch_id
    function checkHadBranchID($conn1, $database,$branch_id) {
        $branchID = false; // Initialize the variable
        $sql = " SELECT count(*) as count FROM `$database[1]`.kpx_branch_masterfile WHERE branch_id=?";
        $stmt = $conn1->prepare($sql);
        $stmt->bind_param("i", $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row && $row['count'] > 0) {
                $branchID = true;
            }
        }
        $stmt->close();
        return $branchID; // Return the actual value instead of negating it
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

        //Labels
        $cellD_label = $worksheet->getCell('D1')->getValue();

        for ($row = 2; $row <= $highestRow; ++$row) {
            if (empty($worksheet->getCell('A' . $row)->getValue()) && empty($worksheet->getCell('B' . $row)->getValue())
                && empty($worksheet->getCell('C' . $row)->getValue())) {
                break;
            }

            $branch_id = $conn1->real_escape_string(intval($worksheet->getCell('A' . $row)->getValue()));
            $branch_name = $conn1->real_escape_string(strval($worksheet->getCell('B' . $row)->getValue()));

            // Handle empty cells for complete_address
            $complete_address_cell = $worksheet->getCell('C' . $row)->getValue();
            $complete_address = empty($complete_address_cell) ? null : $conn1->real_escape_string(strval($complete_address_cell));

            if ($cellD_label === 'STREET/BARANGAY ADDRESS') {
                // Handle empty cells for street_brgy_address
                $street_brgy_cell = $worksheet->getCell('D' . $row)->getValue();
                $street_brgy_address = empty($street_brgy_cell) ? null : $conn1->real_escape_string(strval($street_brgy_cell));

                // Handle empty cell for city
                $city_cell = $worksheet->getCell('E' . $row)->getValue();
                $city = empty($city_cell) ? null : $conn1->real_escape_string(strval($city_cell));

                // Handle empty cell for province
                $province_cell = $worksheet->getCell('F' . $row)->getValue();
                $province = empty($province_cell) ? null : $conn1->real_escape_string(strval($province_cell));

                $region = $conn1->real_escape_string(strval($worksheet->getCell('G' . $row)->getValue()));

                // Handle empty cell for post_code
                $post_code_cell = $worksheet->getCell('H' . $row)->getValue();
                $post_code = empty($post_code_cell) ? null : $conn1->real_escape_string(intval($post_code_cell));

                // Handle empty cells for operating_hours
                $operating_hours_cell = $worksheet->getCell('I' . $row)->getValue();
                $operating_hours = empty($operating_hours_cell) ? null : $conn1->real_escape_string(strval($operating_hours_cell));

            }else{
                // Handle empty cell for city
                $city_cell = $worksheet->getCell('D' . $row)->getValue();
                $city = empty($city_cell) ? null : $conn1->real_escape_string(strval($city_cell));

                // Handle empty cell for province
                $province_cell = $worksheet->getCell('E' . $row)->getValue();
                $province = empty($province_cell) ? null : $conn1->real_escape_string(strval($province_cell));
                $region = $conn1->real_escape_string(strval($worksheet->getCell('F' . $row)->getValue()));

                // Handle empty cell for post_code
                $post_code_cell = $worksheet->getCell('G' . $row)->getValue();
                $post_code = empty($post_code_cell) ? null : $conn1->real_escape_string(intval($post_code_cell));

                // Handle empty cells for operating_hours
                $operating_hours_cell = $worksheet->getCell('H' . $row)->getValue();
                $operating_hours = empty($operating_hours_cell) ? null : $conn1->real_escape_string(strval($operating_hours_cell));

                // Handle empty cells for corporate_name
                $corporate_name_cell = $worksheet->getCell('I' . $row)->getValue();
                $corporate_name = empty($corporate_name_cell) ? null : $conn1->real_escape_string(strval($corporate_name_cell));
            }

            $regioncodequery = "SELECT region_code, zone_code FROM `$database[1]`.region_masterfile WHERE (region_desc_kpx = ? or gl_region = ?)";
            $stmt = $conn1->prepare($regioncodequery);

            // Check if prepare was successful
            if ($stmt === false) {
                die("Prepare failed: " . $conn1->error);
            }

            $stmt->bind_param("ss", $region, $region);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $regionRow = $result->fetch_assoc();
                if ($regionRow && isset($regionRow['region_code'])) {
                    $region_code = $conn1->real_escape_string(strval($regionRow['region_code']));
                    $zone_code = $conn1->real_escape_string(strval($regionRow['zone_code']));
                }else {
                    $region_code = null;
                    $zone_code = null;
                }
            }
            $stmt->close();

            $branchcodequery = "SELECT code, ml_matic_region FROM `$database[1]`.branch_profile WHERE  branch_id = ? AND region_code = ? AND zone = ?";
            $stmt = $conn1->prepare($branchcodequery);

            // Check if prepare was successful
            if ($stmt === false) {
                die("Prepare failed: " . $conn1->error);
            }

            $stmt->bind_param("iss", $branch_id, $region_code, $zone_code);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $branchRow = $result->fetch_assoc();
                if ($branchRow && isset($branchRow['code'])) {
                    $branch_code = $conn1->real_escape_string(strval($branchRow['code']));
                    $ml_matic_region = $conn1->real_escape_string(strval($branchRow['ml_matic_region']));
                }else {
                    $branch_code = null;
                    $ml_matic_region = null;
                }
            }
            $stmt->close();

            $uploaded_date = date('Y-m-d', strtotime($_POST['restricted-date']));
            $uploaded_by = $conn->real_escape_string($_SESSION['admin_name']);

            if($cellD_label === 'STREET/BARANGAY ADDRESS'){
                if (checkHadBranchID($conn1, $database,$branch_id)) {
    
                    $matchedRecords[] = [
                        'branch_id' => $branch_id,
                        'branch_name' => $branch_name,
                        'complete_address' => $complete_address,
                        'street_brgy_address' => $street_brgy_address,
                        'city' => $city,
                        'province' => $province,
                        'region' => $region,
                        'post_code' => $post_code,
                        'operating_hours' => $operating_hours,
                        'branch_code' => $branch_code,
                        'region_code' => $region_code,
                        'zone_code' => $zone_code,
                        'ml_matic_region' => $ml_matic_region
                    ];
    
                } else {
    
                    $unmatchedRecords[] = [
                        'row' => $row,
                        'branch_id' => $branch_id,
                        'branch_name' => $branch_name,
                        'region' => $region,
                        'uploaded_date' => $uploaded_date,
                        'uploaded_by' => $uploaded_by,
                        'message' => 'not found in kpx branch masterfile database'
                    ];
    
                    $messages[] = [
                        'type' => 'error',
                        'sheet' => 'KPX FROM TG Data',
                        'A' => $branch_id,
                        'B' => $branch_name,
                        'C' => $region,
                        'message' => 'not found in kpx branch masterfile database'
                    ];
                }
            }else{
                if (checkHadBranchID($conn1, $database,$branch_id)) {
    
                    $matchedRecords[] = [
                        'branch_id' => $branch_id,
                        'branch_name' => $branch_name,
                        'complete_address' => $complete_address,
                        'city' => $city,
                        'province' => $province,
                        'region' => $region,
                        'post_code' => $post_code,
                        'operating_hours' => $operating_hours,
                        'corporate_name' => $corporate_name,
                        'branch_code' => $branch_code,
                        'region_code' => $region_code,
                        'zone_code' => $zone_code,
                        'ml_matic_region' => $ml_matic_region
                    ];
    
                } else {
    
                    $unmatchedRecords[] = [
                        'row' => $row,
                        'branch_id' => $branch_id,
                        'branch_name' => $branch_name,
                        'region' => $region,
                        'uploaded_date' => $uploaded_date,
                        'uploaded_by' => $uploaded_by,
                        'message' => 'not found in kpx branch masterfile database'
                    ];
    
                    $messages[] = [
                        'type' => 'error',
                        'sheet' => 'KPX FROM TG Data',
                        'A' => $branch_id,
                        'B' => $branch_name,
                        'C' => $region,
                        'message' => 'not found in kpx branch masterfile database'
                    ];
                }
            }
        }

        // Store matched records in session for later use
        $_SESSION['un_matched_records'] = $unmatchedRecords;

        // Check if there are unmatched records
        if (!empty($unmatchedRecords)) {
            echo "Unmatched records found. Data upload aborted.";
        } else {
            // Proceed with inserting matched records if no unmatched records exist
            if($cellD_label === 'STREET/BARANGAY ADDRESS'){
                if (!empty($matchedRecords)) {
                    // Prepare insert (upsert) into masterdata.kpx_branch_profile first
                    $insertQuery = "INSERT INTO masterdata.kpx_branch_profile
                        (branch_id, branch_name, complete_address, street_or_brgy_address, city, province, region, post_code, operating_hours, corporate_name, uploaded_date, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            complete_address = VALUES(complete_address),
                            street_or_brgy_address = VALUES(street_or_brgy_address),
                            city = VALUES(city),
                            province = VALUES(province),
                            post_code = VALUES(post_code),
                            operating_hours = VALUES(operating_hours),
                            region = VALUES(region),
                            branch_name = VALUES(branch_name),
                            uploaded_date = VALUES(uploaded_date),
                            uploaded_by = VALUES(uploaded_by)";

                    $insStmt = $conn1->prepare($insertQuery);
                    if ($insStmt === false) {
                        die("Prepare failed (insert): " . $conn1->error);
                    }

                    foreach ($matchedRecords as $record) {
                        // Extract into variables (bind_param requires variables, not expressions)
                        $branch_id_v = $record['branch_id'];
                        $branch_name_v = $record['branch_name'];
                        $complete_address_v = $record['complete_address'];
                        $street_brgy_v = isset($record['street_brgy_address']) ? $record['street_brgy_address'] : null;
                        $city_v = $record['city'];
                        $province_v = $record['province'];
                        $region_v = $record['region'];
                        $post_code_v = $record['post_code'];
                        $operating_hours_v = $record['operating_hours'];
                        $corporate_name_v = null; // for this branch layout corporate_name is null
                        $uploaded_date_v = $uploaded_date;
                        $uploaded_by_v = $uploaded_by;
 
                        $insStmt->bind_param("ssssssssssss",
                            $branch_id_v,
                            $branch_name_v,
                            $complete_address_v,
                            $street_brgy_v,
                            $city_v,
                            $province_v,
                            $region_v,
                            $post_code_v,
                            $operating_hours_v,
                            $corporate_name_v,
                            $uploaded_date_v,
                            $uploaded_by_v
                        );
 
                        if (!$insStmt->execute()) {
                            die("Insert Error: " . $insStmt->error);
                        }
                    }
                    $insStmt->close();
 
                    // Now run the existing update for kpx_branch_masterfile
                    foreach ($matchedRecords as $record) {
                        $updatequery = "UPDATE " . $database[1] . ".kpx_branch_masterfile
                         SET 
                            branch_name = ?,
                             complete_address = ?,
                             street_or_brgy_address = ?,
                             city = ?,
                             province = ?,
                             region = ?,
                             post_code = ?,
                             operating_hours = ?,
                             mbp_code = ?,
                             mrm_region_code = ?,
                             mrm_zone_code = ?,
                             mbp_mlmatic_region = ?
                         WHERE 
                             branch_id = ?";

                         $stmt = $conn1->prepare($updatequery);
                         if ($stmt === false) {
                             die("Prepare failed: " . $conn1->error);
                         }

                        // Extract into variables (bind_param requires variables)
                        $u_branch_name = $record['branch_name'];
                        $u_complete_address = $record['complete_address'];
                        $u_street_brgy = isset($record['street_brgy_address']) ? $record['street_brgy_address'] : null;
                        $u_city = $record['city'];
                        $u_province = $record['province'];
                        $u_region = $record['region'];
                        $u_post_code = $record['post_code'];
                        $u_operating_hours = $record['operating_hours'];
                        $u_mbp_code = $record['branch_code'];
                        $u_mrm_region_code = $record['region_code'];
                        $u_mrm_zone_code = $record['zone_code'];
                        $u_mbp_mlmatic_region = $record['ml_matic_region'];
                        $u_branch_id = $record['branch_id'];

                        // 13 placeholders -> 13 type chars
                        $stmt->bind_param("sssssssssssss",
                            $u_branch_name,
                            $u_complete_address,
                            $u_street_brgy,
                            $u_city,
                            $u_province,
                            $u_region,
                            $u_post_code,
                            $u_operating_hours,
                            $u_mbp_code,
                            $u_mrm_region_code,
                            $u_mrm_zone_code,
                            $u_mbp_mlmatic_region,
                            $u_branch_id
                        );

                         if (!$stmt->execute()) {
                             die("Update Error: " . $stmt->error);
                         }
                         $stmt->close();
                     }
                     echo "Data inserted/updated successfully.";
                 }
             }else{
                 if (!empty($matchedRecords)) {
                     // Prepare insert (upsert) into masterdata.kpx_branch_profile first
                     $insertQuery = "INSERT INTO masterdata.kpx_branch_profile
                         (branch_id, branch_name, complete_address, street_or_brgy_address, city, province, region, post_code, operating_hours, corporate_name, uploaded_date, uploaded_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                             complete_address = VALUES(complete_address),
                             city = VALUES(city),
                             province = VALUES(province),
                             post_code = VALUES(post_code),
                             operating_hours = VALUES(operating_hours),
                             corporate_name = VALUES(corporate_name),
                             region = VALUES(region),
                             branch_name = VALUES(branch_name),
                             uploaded_date = VALUES(uploaded_date),
                             uploaded_by = VALUES(uploaded_by)";

                     $insStmt = $conn1->prepare($insertQuery);
                     if ($insStmt === false) {
                         die("Prepare failed (insert): " . $conn1->error);
                     }
 
                     foreach ($matchedRecords as $record) {
                        // Extract into variables for bind_param
                        $branch_id_v = $record['branch_id'];
                        $branch_name_v = $record['branch_name'];
                        $complete_address_v = $record['complete_address'];
                        $street_brgy_v = null; // this layout doesn't have street_brgy
                        $city_v = $record['city'];
                        $province_v = $record['province'];
                        $region_v = $record['region'];
                        $post_code_v = $record['post_code'];
                        $operating_hours_v = $record['operating_hours'];
                        $corporate_name_v = isset($record['corporate_name']) ? $record['corporate_name'] : null;
                        $uploaded_date_v = $uploaded_date;
                        $uploaded_by_v = $uploaded_by;
 
                        $insStmt->bind_param("ssssssssssss",
                            $branch_id_v,
                            $branch_name_v,
                            $complete_address_v,
                            $street_brgy_v,
                            $city_v,
                            $province_v,
                            $region_v,
                            $post_code_v,
                            $operating_hours_v,
                            $corporate_name_v,
                            $uploaded_date_v,
                            $uploaded_by_v
                        );
 
                         if (!$insStmt->execute()) {
                             die("Insert Error: " . $insStmt->error);
                         }
                     }
                     $insStmt->close();
 
                     // Now run the existing update for kpx_branch_masterfile
                     foreach ($matchedRecords as $record) {
                         $updatequery = "UPDATE " . $database[1] . ".kpx_branch_masterfile
                         SET 
                            branch_name = ?,
                             complete_address = ?,
                             city = ?,
                             province = ?,
                             region = ?,
                             post_code = ?,
                             operating_hours = ?,
                             corporate_name = ?,
                             mbp_code = ?,
                             mrm_region_code = ?,
                             mrm_zone_code = ?,
                             mbp_mlmatic_region = ?
                         WHERE 
                             branch_id = ?";

                         $stmt = $conn1->prepare($updatequery);
                         if ($stmt === false) {
                             die("Prepare failed: " . $conn1->error);
                         }

                        // Extract into variables for bind_param
                        $u_branch_name = $record['branch_name'];
                        $u_complete_address = $record['complete_address'];
                        $u_city = $record['city'];
                        $u_province = $record['province'];
                        $u_region = $record['region'];
                        $u_post_code = $record['post_code'];
                        $u_operating_hours = $record['operating_hours'];
                        $u_corporate_name = isset($record['corporate_name']) ? $record['corporate_name'] : null;
                        $u_mbp_code = $record['branch_code'];
                        $u_mrm_region_code = $record['region_code'];
                        $u_mrm_zone_code = $record['zone_code'];
                        $u_mbp_mlmatic_region = $record['ml_matic_region'];
                        $u_branch_id = $record['branch_id'];

                        // 13 placeholders -> 13 type chars
                        $stmt->bind_param("sssssssssssss",
                            $u_branch_name,
                            $u_complete_address,
                            $u_city,
                            $u_province,
                            $u_region,
                            $u_post_code,
                            $u_operating_hours,
                            $u_corporate_name,
                            $u_mbp_code,
                            $u_mrm_region_code,
                            $u_mrm_zone_code,
                            $u_mbp_mlmatic_region,
                            $u_branch_id
                        );

                         if (!$stmt->execute()) {
                             die("Update Error: " . $stmt->error);
                         }
                         $stmt->close();
                     }
                     echo "Data inserted/updated successfully.";
                 }
             }
         } 
     }

    // Handle write-db button
    if (isset($_POST['write-db'])) {
        if (isset($_SESSION['un_matched_records']) && !empty($_SESSION['un_matched_records'])) {
            foreach ($_SESSION['un_matched_records'] as $record) {
                $insertBranchQuery = "INSERT INTO " . $database[1] . ".kpx_branch_masterfile (branch_id, uploaded_date, uploaded_by) VALUES (?, ?, ?)";
                
                $stmt = $conn1->prepare($insertBranchQuery);
                if ($stmt === false) {
                    die("Prepare failed: " . $conn1->error);
                }
                
                $stmt->bind_param("sss", 
                    $record['branch_id'],
                    $record['uploaded_date'],
                    $record['uploaded_by']
                );
                
                if (!$stmt->execute()) {
                    die("Insert Error: " . $stmt->error);
                }
                $stmt->close();
            }
            
            // Clear session data after successful insert
            unset($_SESSION['un_matched_records']);
            
            echo "Data written to kpx_branch_masterfile successfully.";
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
    <center><h2>KPX Branch Profile from TG <span>[DATA IMPORT]</span></h2></center>
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
                    <th>Branch ID</th>
                    <th>Branch Name</th>
                    <th>Region</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unmatchedRecords as $record): ?>
                    <tr>
                        <td><?php echo $record['row']; ?></td>
                        <td><?php echo $record['branch_id']; ?></td>
                        <td><?php echo $record['branch_name']; ?></td>
                        <td><?php echo $record['region']; ?></td>
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