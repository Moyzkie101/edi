<?php
    include '../config/connection.php';
    session_start();
    // date_default_timezone_set('Asia/Manila');
    // ini_set('display_errors',1);
    // error_reporting(E_ALL);
    // mysqli_report(MYSQLI_REPORT_ERROR | E_DEPRECATED | E_STRICT);
    // error_reporting(0);
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    $mainzone = $_POST['mainzone']?? '';
    $region = $_POST['region'] ?? '';
    $date = $_POST['restricted-date']?? '';
    
    if (isset($_POST['generate'])) {

        if ($mainzone && empty($region)) { 
            // Display all records belonging to choosen mainzone for VISMIN or LNCR
            $payrollquery = "SELECT
                                mc.id,
                                mzm.main_zone_code,
                                rm.region_code, 
                                rm.region_description, 
                                rm.zone_code,
                                MAX(mc.no_employee_mlwallet) AS no_employee_mlwallet, 
                                MAX(mc.mlwallet_amount) AS mlwallet_amount, 
                                MAX(mc.no_employee_mlkp) AS no_employee_mlkp, 
                                MAX(mc.mlkp_amount) AS mlkp_amount,
                                SUM(
                                    mc.no_employee_mlwallet+
                                    mc.no_employee_mlkp
                                ) AS total_employee,
                                SUM(
                                    mc.mlwallet_amount+
                                    mc.mlkp_amount
                                ) AS total_amount_per_region 
                                
                            FROM 
                                " . $database[1] . ".main_zone_masterfile AS mzm
                            JOIN 
                                " . $database[1] . ".region_masterfile AS rm
                                ON (
                                    (rm.zone_code IN ('VIS', 'MIN', 'VISMIN-MANCOMM', 'VISMIN-SUPPORT') AND mzm.main_zone_code = 'VISMIN') 
                                    OR 
                                    (rm.zone_code IN ('NCR', 'LZN', 'LNCR-MANCOMM', 'LNCR-SUPPORT') AND mzm.main_zone_code = 'LNCR')
                                )
                            LEFT JOIN 
                                " . $database[0] . ".rfp_payroll AS mc
                            ON 
                                rm.region_code = mc.region_code  -- Adjust based on the correct relationship
                            AND 
                                mc.payroll_date = '".$date."'  -- Move filtering condition here to keep LEFT JOIN effective
                            WHERE 
                                mzm.main_zone_code = '".$mainzone."'
                            GROUP BY
                                mc.id,
                                mzm.main_zone_code,
                                rm.region_code, 
                                rm.region_description, 
                                rm.zone_code
                            ORDER BY 
                                mzm.main_zone_code,
                                rm.region_description;
                        ";
            
        }elseif($mainzone && !empty($region)){
            // Display all records belonging to choosen mainzone for VISMIN or LNCR, however filter by region
            $payrollquery = "SELECT
                                mc.id,
                                mzm.main_zone_code,
                                rm.region_code, 
                                rm.region_description, 
                                rm.zone_code,
                                MAX(mc.no_employee_mlwallet) AS no_employee_mlwallet, 
                                MAX(mc.mlwallet_amount) AS mlwallet_amount, 
                                MAX(mc.no_employee_mlkp) AS no_employee_mlkp, 
                                MAX(mc.mlkp_amount) AS mlkp_amount ,
                                SUM(
                                    mc.no_employee_mlwallet+
                                    mc.no_employee_mlkp
                                ) AS total_employee,
                                SUM(
                                    mc.mlwallet_amount+
                                    mc.mlkp_amount
                                ) AS total_amount_per_region
                            FROM 
                                " . $database[1] . ".main_zone_masterfile AS mzm
                            JOIN 
                                " . $database[1] . ".region_masterfile AS rm
                                ON (
                                    (rm.zone_code IN ('VIS', 'MIN', 'VISMIN-MANCOMM', 'VISMIN-SUPPORT') AND mzm.main_zone_code = 'VISMIN') 
                                    OR 
                                    (rm.zone_code IN ('NCR', 'LZN', 'LNCR-MANCOMM', 'LNCR-SUPPORT') AND mzm.main_zone_code = 'LNCR')
                                )
                            LEFT JOIN 
                                " . $database[0] . ".rfp_payroll AS mc
                            ON 
                                rm.region_code = mc.region_code  -- Adjust based on the correct relationship
                            AND 
                                mc.payroll_date = '".$date."'  -- Move filtering condition here to keep LEFT JOIN effective
                            WHERE 
                                mzm.main_zone_code = '".$mainzone."'
                                AND
                                    rm.region_code = '".$region."'
                            GROUP BY
                                mc.id,
                                mzm.main_zone_code,
                                rm.region_code, 
                                rm.region_description, 
                                rm.zone_code
                            ORDER BY 
                                mzm.main_zone_code,
                                rm.region_description;

                ";
            
        }else{
            // Display all records belonging to all mainzones
            $payrollquery = "SELECT
                                mc.id,
                                mzm.main_zone_code,
                                rm.region_code, 
                                rm.region_description, 
                                rm.zone_code
                            FROM 
                                " . $database[1] . ".main_zone_masterfile AS mzm
                            JOIN 
                                " . $database[1] . ".region_masterfile AS rm
                            ON 
                                (rm.zone_code IN ('LZN', 'NCR') AND mzm.main_zone_code = 'LNCR') OR
                                (rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN')
                            WHERE
                                rm.region_code = '$region'
                            ORDER BY 
                                mzm.main_zone_code, rm.region_description
                ";
        }

        // Execute and check query
        $payrollresult = mysqli_query($conn, $payrollquery);
        
        if (!$payrollresult) {
            die("Query failed: " . mysqli_error($conn)); // Debugging line
        }

        // Fetch results
        $payrollrows = $payrollresult->fetch_all(MYSQLI_ASSOC);

        // Execute query
        //$mcashresult = mysqli_query($conn, $mcashquery);
        //$payrollresult = mysqli_query($conn, $payrollquery);

        // Fetch results
        //$mcashrows = $mcashresult->fetch_all(MYSQLI_ASSOC);
         //$payrollrows = $payrollresult->fetch_all(MYSQLI_ASSOC);
    }

    // Check if a payroll date is selected
    if (isset($_POST['restricted-date']) && !empty($_POST['restricted-date'])) {
        // Extract the day from the selected date
        $payrollDay = date('j', strtotime($_POST['restricted-date']));

        // // Determine the header text dynamically
        // $payrollHeader = ($payrollDay == 15) ? "EDI PAYROLL 15 Data" : "EDI PAYROLL $payrollDay Data";
    }
    

    if (isset($_POST['submit'])) {
        // Get the JSON data from the hidden input
        $jsonData = $_POST['table_data'] ?? '';
        
        if (!empty($jsonData)) {
            $tableData = json_decode($jsonData, true);
            
            if ($tableData && is_array($tableData)) {
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($tableData as $row) {
                    // Validate required fields
                    if (empty($row['date']) || empty($row['mainzone']) || empty($row['region_code'])) {
                        $errorCount++;
                        continue;
                    }
                    
                    // Calculate totals and store in variables (required for bind_param)
                    $no_employee_mlwallet = intval($row['no_employee_mlwallet']);
                    $mlwallet_amount = floatval($row['mlwallet_amount']);
                    $no_employee_mlkp = intval($row['no_employee_mlkp']);
                    $mlkp_amount = floatval($row['mlkp_amount']);
                    $total_employee = $no_employee_mlwallet + $no_employee_mlkp;
                    $total_amount = $mlwallet_amount + $mlkp_amount;
                    $modified_by = $_SESSION['admin_name'];
                    $modified_date = date('Y-m-d H:i:s');
                    
                    // Sanitize input values
                    $payroll_date = $row['date'];
                    $payroll_mainzone = $row['mainzone'];
                    $region_code = $row['region_code'];
                    $region_name = $row['region_name'];
                    
                    // Insert or update record
                    if (!empty($row['id']) && $row['id'] !== 'null') {
                        // Update existing record
                        $updateQuery = "UPDATE " . $database[0] . ".rfp_payroll
                                        SET no_employee_mlwallet = ?, mlwallet_amount = ?, 
                                            no_employee_mlkp = ?, mlkp_amount = ?, 
                                            total_employee = ?, total_amount = ?,
                                            modified_by = ?, modified_date = ? 
                                        WHERE id = ? and post_edi = 'pending' and payroll_date = ? and mainzone = ? and region_code = ? and region_name = ?";
                        
                        $stmt = $conn->prepare($updateQuery);
                        $record_id = intval($row['id']);
                        $stmt->bind_param("idididssissss", 
                            $no_employee_mlwallet, 
                            $mlwallet_amount, 
                            $no_employee_mlkp, 
                            $mlkp_amount, 
                            $total_employee, 
                            $total_amount, 
                            $modified_by, 
                            $modified_date,
                            $record_id,
                            $payroll_date, 
                            $payroll_mainzone, 
                            $region_code, 
                            $region_name
                        );
                    } else {
                        // Insert new record
                        $insertQuery = "INSERT INTO " . $database[0] . ".rfp_payroll (
                                            payroll_date, mainzone, region_code, region_name,
                                            no_employee_mlwallet, mlwallet_amount, no_employee_mlkp, mlkp_amount,
                                            total_employee, total_amount, payroll_type, uploaded_by, uploaded_date, post_edi
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Data-Entry', ?, ?, 'pending')";
                        
                        $stmt = $conn->prepare($insertQuery);
                        $payroll_date = $row['date'];
                        $mainzone = $row['mainzone'];
                        $region_code = $row['region_code'];
                        $region_name = $row['region_name'];
                        $uploaded_by = $_SESSION['admin_name'];
                        $uploaded_date = date('Y-m-d H:i:s');
                        
                        $stmt->bind_param("ssssidididss", 
                            $payroll_date, 
                            $mainzone, 
                            $region_code, 
                            $region_name,
                            $no_employee_mlwallet, 
                            $mlwallet_amount, 
                            $no_employee_mlkp, 
                            $mlkp_amount,
                            $total_employee, 
                            $total_amount,
                            $uploaded_by,
                            $uploaded_date
                        );
                    }
                    
                    if ($stmt->execute()) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                    $stmt->close();
                }
                
                echo "<script>alert('Batch processing complete! Success: $successCount, Errors: $errorCount');</script>";
            } else {
                echo "<script>alert('Invalid JSON data received.');</script>";
            }
        } else {
            echo "<script>alert('No data to submit.');</script>";
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
    <link rel="stylesheet" href="../assets/css/admin/rfp-payroll-data-entry/rfp-payroll-data-entry-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script> -->
    
</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>RFP Payroll <span>[DATA ENTRY]</span></h2></center>

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

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

        <!-- <div id="showdl" style="display: none">
            <button class="post-btn" onclick="postEdi()">Post EDI</button>
        </div> -->
    </div>
    <div>
        <center>
            <form action="" method="POST" id="batchSubmitForm">
                <input type="hidden" name="table_data" id="table_data_input">
                <input type="submit" class="generate-btn" name="submit" value="Submit All Changes" onclick="return submitAllChanges()">
            </form>
        </center>
    </div>
    <div class='table-container'>
        <table id="dataTable">
            <thead>
                <tr>
                    <th colspan="9">(<?php echo isset($_POST['mainzone']) ? $_POST['mainzone'] : ''; ?>)</th>
                </tr>
                <tr>
                    <th colspan="3">RFP Payroll Date : <?php echo $date;?> </th>
                    <th rowspan="2">NO. OF EMPLOYEE (ML WALLET)</th>
                    <th rowspan="2">ML WALLET AMOUNT</th>
                    <th rowspan="2">NO. OF EMPLOYEE(ML KP)</th>
                    <th rowspan="2">ML KP AMOUNT</th>
                    <th rowspan="2">TOTAL EMPLOYEE</th>
                    <th rowspan="2">TOTAL AMOUNT PER REGION</th>
                </tr>
                <tr>
                    <th>REGION CODE  </th>
                    <th>REGION NAME</th>
                    <th>ZONE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if (isset($_POST['generate'])) {
                        $grand_total_employees_mlwallet = 0;
                        $grand_total_mlwallet_amount = 0;
                        $grand_total_employees_mlkp = 0;
                        $grand_total_mlkp_amount = 0;
                        $grand_total_employees = 0;
                        $grand_total_amount_per_region = 0;

                        if (!empty($payrollrows)) {
                            $index = 0;
                            foreach ($payrollrows as $payroll) {
                                $rowId = $payroll['id'] ?? 'new_' . $index;
                                echo '<tr class="selectable-row" data-id="' . htmlspecialchars($rowId) . '" 
                                    data-region-code="' . htmlspecialchars($payroll['region_code']) . '"
                                    data-region-name="' . htmlspecialchars($payroll['region_description']) . '"
                                    data-zone-code="' . htmlspecialchars($payroll['zone_code']) . '"
                                    ondblclick="displayModal(' . $index . ')" onclick="highlightRow(this)">';
                                echo '<td>' . htmlspecialchars($payroll['region_code']) . '</td>';
                                echo '<td>' . htmlspecialchars($payroll['region_description']) . '</td>';
                                echo '<td align="right">' . htmlspecialchars($payroll['zone_code']) . '</td>';
                                echo '<td class="ml-wallet-emp">' . htmlspecialchars($payroll['no_employee_mlwallet'] ?? 0) . '</td>';
                                echo '<td class="ml-wallet-amount">' . htmlspecialchars(number_format($payroll['mlwallet_amount'] ?? 0, 2)) . '</td>';
                                echo '<td class="ml-kp-emp">' . htmlspecialchars($payroll['no_employee_mlkp'] ?? 0) . '</td>';
                                echo '<td class="ml-kp-amount">' . htmlspecialchars(number_format($payroll['mlkp_amount'] ?? 0, 2)) . '</td>';
                                echo '<td class="total-emp">' . htmlspecialchars($payroll['total_employee'] ?? 0) . '</td>';
                                echo '<td class="total-amount">' . htmlspecialchars(number_format($payroll['total_amount_per_region'] ?? 0, 2)) . '</td>';
                                echo '</tr>';

                                // Accumulate grand totals
                                $grand_total_employees_mlwallet += $payroll['no_employee_mlwallet'] ?? 0;
                                $grand_total_mlwallet_amount += $payroll['mlwallet_amount'] ?? 0;
                                $grand_total_employees_mlkp += $payroll['no_employee_mlkp'] ?? 0;
                                $grand_total_mlkp_amount += $payroll['mlkp_amount'] ?? 0;
                                $grand_total_employees += $payroll['total_employee'] ?? 0;
                                $grand_total_amount_per_region += $payroll['total_amount_per_region'] ?? 0;

                                $index++;
                            }
                        } else {
                            echo '<tr><td colspan="9">No records found for the selected criteria.</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9">Please select Payroll Date to display.</td></tr>';
                    }
                ?>
            </tbody>
            <tfoot id="tableFoot">
                <tr>
                    <th colspan="3">GRAND TOTAL</th>
                    <th id="grand-total-ml-wallet-emp"><?php echo isset($grand_total_employees_mlwallet) ? $grand_total_employees_mlwallet : 0; ?></th>
                    <th id="grand-total-ml-wallet-amount"><?php echo isset($grand_total_mlwallet_amount) ? number_format($grand_total_mlwallet_amount, 2) : '0.00'; ?></th>
                    <th id="grand-total-ml-kp-emp"><?php echo isset($grand_total_employees_mlkp) ? $grand_total_employees_mlkp : 0; ?></th>
                    <th id="grand-total-ml-kp-amount"><?php echo isset($grand_total_mlkp_amount) ? number_format($grand_total_mlkp_amount, 2) : '0.00'; ?></th>
                    <th id="grand-total-emp"><?php echo isset($grand_total_employees) ? $grand_total_employees : 0; ?></th>
                    <th id="grand-total-amount"><?php echo isset($grand_total_amount_per_region) ? number_format($grand_total_amount_per_region, 2) : '0.00'; ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Modal -->
    <form action="" method="POST">
        <div class="update-modal" id="myModal">
            <div class="update-modal-dialog">
                <div class="update-modal-content">
                    <!-- Modal Header -->
                    <div class="update-modal-header">
                        <h4 class="update-modal-title">RFP Payroll (Data Entry)</h4>
                        <button type="button" class="update-close" data-dismiss="update-modal">&times</button>
                    </div>
                    <!-- Modal body -->
                    <div class="update-modal-body">
                        <div class="content-wrap">
                            <!-- first content -->
                            <div class="first-content-wrap">
                                <h3>Date : <span name="date"><?php echo $date;?></span></h3>
                                <h3>Main Zone Code : <span name="mainzone"><?php echo isset($_POST['mainzone']) ? $_POST['mainzone'] : ''; ?></span></h3>
                                <h3>Zone Code : <span id="zone_code_update" name="zone_code_update"></span></h3>
                                <h3>Region Code : <span id="region_code_update" name="region_code_update"></span></h3>
                                <h3>Region Name : <span id="region_name_update" name="region_name_update"></span></h3>
                            </div>

                            <input type="hidden" name="date" id="date_input">
                            <input type="hidden" name="mainzone" id="mainzone_input">
                            <input type="hidden" name="region_code_update" id="region_code_hidden">
                            <input type="hidden" name="region_name_update" id="region_name_hidden">

                            <!-- second content -->
                            <div class="second-content-wrap text-center fw-normal">
                                <div class="content-item">
                                    <label for="EMLWallet">No. of Employee (ML Wallet)</label>
                                    <input class="add_inp" type="text" name="EMLWallet" id="EMLWallet" required autocomplete="off" onkeypress="return (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 46 && event.keyCode == 18 );">
                                </div>
                                <div class="content-item">
                                    <label for="MLWallet">ML WALLET AMOUNT</label>
                                    <input class="add_inp" type="text" name="MLWallet" id="MLWallet" required autocomplete="off" onkeypress="return (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 46 && event.keyCode == 46 );">
                                </div>
                                <div class="content-item">
                                    <label for="EMLKP">No. of Employee (ML KP)</label>
                                    <input class="add_inp" type="text" name="EMLKP" id="EMLKP" required autocomplete="off" onkeypress="return (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 46 && event.keyCode == 18 );">
                                </div>
                                <div class="content-item">
                                    <label for="MLKP">ML KP AMOUNT</label>
                                    <input class="add_inp" type="text" name="MLKP" id="MLKP" required autocomplete="off" onkeypress="return (event.keyCode >= 48 && event.keyCode <= 57) || (event.keyCode == 46 && event.keyCode == 46 );">
                                    
                                    <!-- Hidden input to store the record ID -->
                                    <input type="hidden" name="record_id" id="record_id" readonly>
                                </div>
                            </div>
                            <div class="update-modal-footer text-center">
                                <button type="submit" name="save" id="save-btn" class="generate-btn">Save</button>
                                <button type="submit" name="update" id="update-btn" class="generate-btn" style="display:none;">Update</button>
                                <button type="button" name="close" class="print-btn" data-dismiss="update-modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <script src="../assets/js/admin/mcash-recon/rfp-payroll-data-entry-script.js"></script>
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
        // Store table data changes
        let tableChanges = {};

        document.addEventListener("DOMContentLoaded", function () {
            const closeButtons = document.querySelectorAll(".print-btn, .update-close");
            const modal = document.querySelector("#myModal");
            
            if (modal && closeButtons.length > 0) {
                closeButtons.forEach(button => {
                    button.addEventListener("click", function () {
                        modal.classList.remove("show");
                        clearModalFields();
                    });
                });
            }
        });

        function clearModalFields() {
            document.querySelector("#EMLWallet").value = '';
            document.querySelector("#MLWallet").value = '';
            document.querySelector("#EMLKP").value = '';
            document.querySelector("#MLKP").value = '';
            document.querySelector("#record_id").value = '';
            document.querySelector("#region_code_update").textContent = '';
            document.querySelector("#region_name_update").textContent = '';
            document.querySelector("#zone_code_update").textContent = '';
            document.querySelector("#save-btn").style.display = "inline-block";
            document.querySelector("#update-btn").style.display = "none";
        }

        function displayModal(rowIndex) {
            const rows = document.querySelectorAll(".selectable-row");
            if (rowIndex < 0 || rowIndex >= rows.length) return;

            const row = rows[rowIndex];
            const rowData = Array.from(row.cells).map(cell => cell.textContent.trim());

            const recordId = row.getAttribute("data-id");
            document.querySelector("#record_id").value = recordId;

            // Populate modal labels
            document.querySelector("#region_code_update").textContent = rowData[0] || '';
            document.querySelector("#region_name_update").textContent = rowData[1] || '';
            document.querySelector("#zone_code_update").textContent = rowData[2] || '';

            // Populate hidden form inputs
            document.querySelector("#date_input").value = document.querySelector("input[name='restricted-date']").value;
            document.querySelector("#mainzone_input").value = document.querySelector("select[name='mainzone']").value;
            document.querySelector("#region_code_hidden").value = rowData[0] || '';
            document.querySelector("#region_name_hidden").value = rowData[1] || '';

            // Check if record exists and populate fields
            const hasData = recordId && recordId !== 'null' && rowData[3] && rowData[3] !== '0';
            
            if (hasData) {
                // Parse formatted numbers (remove commas)
                document.querySelector("#EMLWallet").value = rowData[3] || '0';
                document.querySelector("#MLWallet").value = rowData[4] ? rowData[4].replace(/,/g, '') : '0';
                document.querySelector("#EMLKP").value = rowData[5] || '0';
                document.querySelector("#MLKP").value = rowData[6] ? rowData[6].replace(/,/g, '') : '0';
                
                document.querySelector("#save-btn").style.display = "none";
                document.querySelector("#update-btn").style.display = "inline-block";
            } else {
                document.querySelector("#EMLWallet").value = '';
                document.querySelector("#MLWallet").value = '';
                document.querySelector("#EMLKP").value = '';
                document.querySelector("#MLKP").value = '';
                
                document.querySelector("#save-btn").style.display = "inline-block";
                document.querySelector("#update-btn").style.display = "none";
            }

            document.querySelector("#myModal").classList.add("show");
        }

        // Handle save/update in modal
        document.querySelector("#save-btn").addEventListener("click", function(e) {
            e.preventDefault();
            updateTableRow();
        });

        document.querySelector("#update-btn").addEventListener("click", function(e) {
            e.preventDefault();
            updateTableRow();
        });

        function updateTableRow() {
            const recordId = document.querySelector("#record_id").value;
            const emlWallet = parseInt(document.querySelector("#EMLWallet").value) || 0;
            const mlWallet = parseFloat(document.querySelector("#MLWallet").value) || 0;
            const emlKp = parseInt(document.querySelector("#EMLKP").value) || 0;
            const mlKp = parseFloat(document.querySelector("#MLKP").value) || 0;
            
            const totalEmp = emlWallet + emlKp;
            const totalAmount = mlWallet + mlKp;

            // Find the corresponding row
            const row = document.querySelector(`tr[data-id="${recordId}"]`);
            if (row) {
                // Update table cells
                row.querySelector(".ml-wallet-emp").textContent = emlWallet;
                row.querySelector(".ml-wallet-amount").textContent = mlWallet.toLocaleString('en-US', {minimumFractionDigits: 2});
                row.querySelector(".ml-kp-emp").textContent = emlKp;
                row.querySelector(".ml-kp-amount").textContent = mlKp.toLocaleString('en-US', {minimumFractionDigits: 2});
                row.querySelector(".total-emp").textContent = totalEmp;
                row.querySelector(".total-amount").textContent = totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2});

                // Store changes in tableChanges object
                tableChanges[recordId] = {
                    id: recordId.startsWith('new_') ? null : recordId,
                    date: document.querySelector("#date_input").value,
                    mainzone: document.querySelector("#mainzone_input").value,
                    region_code: document.querySelector("#region_code_hidden").value,
                    region_name: document.querySelector("#region_name_hidden").value,
                    no_employee_mlwallet: emlWallet,
                    mlwallet_amount: mlWallet,
                    no_employee_mlkp: emlKp,
                    mlkp_amount: mlKp
                };

                // Update grand totals
                updateGrandTotals();
            }

            // Close modal
            document.querySelector("#myModal").classList.remove("show");
            clearModalFields();
        }

        function updateGrandTotals() {
            let grandTotalMLWalletEmp = 0;
            let grandTotalMLWalletAmount = 0;
            let grandTotalMLKpEmp = 0;
            let grandTotalMLKpAmount = 0;
            let grandTotalEmp = 0;
            let grandTotalAmount = 0;

            document.querySelectorAll(".selectable-row").forEach(row => {
                grandTotalMLWalletEmp += parseInt(row.querySelector(".ml-wallet-emp").textContent) || 0;
                grandTotalMLWalletAmount += parseFloat(row.querySelector(".ml-wallet-amount").textContent.replace(/,/g, '')) || 0;
                grandTotalMLKpEmp += parseInt(row.querySelector(".ml-kp-emp").textContent) || 0;
                grandTotalMLKpAmount += parseFloat(row.querySelector(".ml-kp-amount").textContent.replace(/,/g, '')) || 0;
                grandTotalEmp += parseInt(row.querySelector(".total-emp").textContent) || 0;
                grandTotalAmount += parseFloat(row.querySelector(".total-amount").textContent.replace(/,/g, '')) || 0;
            });

            // Update footer totals
            document.querySelector("#grand-total-ml-wallet-emp").textContent = grandTotalMLWalletEmp;
            document.querySelector("#grand-total-ml-wallet-amount").textContent = grandTotalMLWalletAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.querySelector("#grand-total-ml-kp-emp").textContent = grandTotalMLKpEmp;
            document.querySelector("#grand-total-ml-kp-amount").textContent = grandTotalMLKpAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.querySelector("#grand-total-emp").textContent = grandTotalEmp;
            document.querySelector("#grand-total-amount").textContent = grandTotalAmount.toLocaleString('en-US', {minimumFractionDigits: 2});
        }

        function submitAllChanges() {
            if (Object.keys(tableChanges).length === 0) {
                alert('No changes to submit.');
                return false;
            }

            const tableData = Object.values(tableChanges);
            document.querySelector("#table_data_input").value = JSON.stringify(tableData);
            
            return confirm(`Are you sure you want to submit ${tableData.length} record(s)?`);
        }
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