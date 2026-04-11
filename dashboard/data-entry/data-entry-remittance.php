<?php
    include '../../config/connection.php';
    session_start();
    // date_default_timezone_set('Asia/Manila');
    // ini_set('display_errors',1);
    // error_reporting(E_ALL);
    // mysqli_report(MYSQLI_REPORT_ERROR | E_DEPRECATED | E_STRICT);
    // error_reporting(0);
    
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
                    // Handle HRMD role - no access to this page
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
                    // Handle HO RFP role - allow access to this page
                    $hasRequiredRole = true;
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

    function isAllowedPayrollDate($value) {
    if (empty($value)) {
        return false;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return false;
    }

    $day = (int) date('j', $ts);
    $lastDay = (int) date('t', $ts);

    return ($day === 15 || $day === $lastDay);
    }

    $mainzone = $_POST['mainzone']?? '';
    $region = $_POST['region'] ?? '';
    $remitanceType = $_POST['remitance_type'] ?? '';
    $date = $_POST['restricted-date']?? '';

    $displayMainzone = htmlspecialchars($mainzone, ENT_QUOTES, 'UTF-8');
    $displayRegion = htmlspecialchars($region, ENT_QUOTES, 'UTF-8');
    $displayRemitanceType = htmlspecialchars($remitanceType, ENT_QUOTES, 'UTF-8');
    $displayDate = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
    
    if (isset($_POST['generate'])) {
        if (!isAllowedPayrollDate($date)) {
            echo "<script>alert('Invalid remittance date. Please select only the 15th or the last day of the month.');</script>";
            $contributionRows = [];
        } else {
            $safeMainzone = mysqli_real_escape_string($conn, $mainzone);
            $safeRegion = mysqli_real_escape_string($conn, $region);
            $safeRemitanceType = mysqli_real_escape_string($conn, $remitanceType);
            $safeDate = mysqli_real_escape_string($conn, $date);

            $isAllMainzone = ($mainzone === 'ALL');
            $isAllRegion = (empty($region) || $region === 'ALL');

            $mainzoneCondition = $isAllMainzone ? '' : " AND mzm.main_zone_code = '" . $safeMainzone . "'";
            $regionCondition = $isAllRegion ? '' : " AND rm.region_code = '" . $safeRegion . "'";
            $remitanceTypeCondition = empty($remitanceType) ? '' : " AND rc.remitance_type = '" . $safeRemitanceType . "'";

            $remittanceQuery = "SELECT
                                    MAX(rc.id) AS id,
                                    mzm.main_zone_code,
                                    rm.region_code,
                                    rm.region_description,
                                    rm.zone_code,
                                    COALESCE(SUM(rc.ee_shared), 0) AS ee_shared,
                                    COALESCE(SUM(rc.er_shared), 0) AS er_shared,
                                    COALESCE(SUM(rc.total_contribution), 0) AS total_contribution
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
                                    " . $database[0] . ".remitance_contribution AS rc
                                    ON rm.region_code = rc.region_code
                                    AND rc.mainzone = mzm.main_zone_code
                                    AND rc.remitance_date = '" . $safeDate . "'
                                    AND rc.remitance_format_type = 'NEW'
                                    AND rc.remitance_type = '" . $safeRemitanceType . "'
                                WHERE 1=1
                                    " . $mainzoneCondition . "
                                    " . $regionCondition . "
                                GROUP BY
                                    mzm.main_zone_code,
                                    rm.region_code,
                                    rm.region_description,
                                    rm.zone_code
                                ORDER BY
                                    mzm.main_zone_code,
                                    rm.region_description";

            $remittanceResult = mysqli_query($conn, $remittanceQuery);

            if (!$remittanceResult) {
                die("Query failed: " . mysqli_error($conn));
            }

            $contributionRows = $remittanceResult->fetch_all(MYSQLI_ASSOC);
        }
    }

    // Check if a payroll date is selected
    if (isset($_POST['restricted-date']) && !empty($_POST['restricted-date'])) {
        // Extract the day from the selected date
        $payrollDay = date('j', strtotime($_POST['restricted-date']));
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
                    if (empty($row['date']) || empty($row['mainzone']) || empty($row['region_code'])) {
                        $errorCount++;
                        continue;
                    }

                    $remittance_date = $row['date'];
                    if (!isAllowedPayrollDate($remittance_date)) {
                        $errorCount++;
                        continue;
                    }

                    $remitance_type = $row['remitance_type'] ?? '';
                    if (empty($remitance_type)) {
                        $errorCount++;
                        continue;
                    }

                    $mainzone_value = $row['mainzone'];
                    $region_code = $row['region_code'];
                    $region_name = $row['region_name'];

                    $ee_shared = floatval($row['ee_shared']);
                    $er_shared = floatval($row['er_shared']);
                    $total_contribution = $ee_shared + $er_shared;

                    if (!empty($row['id']) && $row['id'] !== 'null') {
                        $updateQuery = "UPDATE " . $database[0] . ".remitance_contribution
                            SET ee_shared = ?, er_shared = ?, total_contribution = ?
                            WHERE id = ?
                            AND post_edi = 'pending'
                            AND remitance_date = ?
                            AND mainzone = ?
                            AND region_code = ?
                            AND region_name = ?
                            AND remitance_type = ?
                            AND remitance_format_type = 'NEW'";

                        $stmt = $conn->prepare($updateQuery);
                        $record_id = intval($row['id']);

                        $stmt->bind_param(
                            "dddisssss",
                            $ee_shared,
                            $er_shared,
                            $total_contribution,
                            $record_id,
                            $remittance_date,
                            $mainzone_value,
                            $region_code,
                            $region_name,
                            $remitance_type
                        );
                    } else {
                        $insertQuery = "INSERT INTO " . $database[0] . ".remitance_contribution (
                                            remitance_date,
                                            mainzone,
                                            region_code,
                                            region_name,
                                            ee_shared,
                                            er_shared,
                                            total_contribution,
                                            remitance_type,
                                            remitance_format_type,
                                            uploaded_by,
                                            uploaded_date,
                                            post_edi    
                                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'NEW', ?, ?, 'pending')";

                        $stmt = $conn->prepare($insertQuery);
                        $uploaded_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown User';
                        $uploaded_date = date('Y-m-d H:i:s');

                        $stmt->bind_param(
                            "ssssdddsss",
                            $remittance_date,
                            $mainzone_value,
                            $region_code,
                            $region_name,
                            $ee_shared,
                            $er_shared,
                            $total_contribution,
                            $remitance_type,
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
    <link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script> -->
    
    <style>
        @import url(../../assets/css/shortcodes/Modal/modal.css);

        .print-btn {
            background-color: #d70c0c;
            border: none;
            color: white;
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .print-btn:hover {
            background-color: #423e3d; 
        }
        
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
            /* background-color: #3262e6; */
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
            color: #000000;
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
            font-size: 15px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            margin-right: 20px;
            color: #F14A51;
        }
        .generate-btn {
            background-color: #db120b; 
            border: none;
            color: white;
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .generate-btn:hover{
            background-color:rgb(180, 31, 31);
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
        .post-btn {
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
            font-size: 15px;
            color: #000000;
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
    </style>
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>Remittance NEW <span>[DATA ENTRY]</span></h2></center>

    

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <!-- <option value="ALL" <?php //echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL') ? 'selected' : ''; ?>>ALL MAINZONE</option> -->
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
                <label for="remitance_type">Type</label>
                <select name="remitance_type" id="remitance_type" autocomplete="off" required>
                    <option value="">Select Type</option>
                    <option value="SSS" <?php echo ($remitanceType === 'SSS') ? 'selected' : ''; ?>>SSS</option>
                    <option value="PAGIBIG" <?php echo ($remitanceType === 'PAGIBIG') ? 'selected' : ''; ?>>PAGIBIG</option>
                    <option value="PHILHEALTH" <?php echo ($remitanceType === 'PHILHEALTH') ? 'selected' : ''; ?>>PHILHEALTH</option>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo $displayDate; ?>" required>
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
                    <th colspan="6">(<?php echo $displayMainzone; ?>) - <?php echo $displayRemitanceType; ?></th>
                </tr>
                <tr>
                    <th colspan="3">Remittance Date : <?php echo $displayDate; ?></th>
                    <th rowspan="2">EE SHARE</th>
                    <th rowspan="2">ER SHARE</th>
                    <th rowspan="2">TOTAL</th>
                </tr>
                <tr>
                    <th>REGION CODE</th>
                    <th>REGION NAME</th>
                    <th>ZONE</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    if (isset($_POST['generate'])) {
                        $grand_total_ee_shared = 0;
                        $grand_total_er_shared = 0;
                        $grand_total_contribution = 0;

                        if (!empty($contributionRows)) {
                            $index = 0;
                            foreach ($contributionRows as $contribution) {
                                $rowId = $contribution['id'] ?? 'new_' . $index;

                                echo '<tr class="selectable-row" data-id="' . htmlspecialchars($rowId) . '"
                                    data-region-code="' . htmlspecialchars($contribution['region_code']) . '"
                                    data-region-name="' . htmlspecialchars($contribution['region_description']) . '"
                                    data-zone-code="' . htmlspecialchars($contribution['zone_code']) . '"
                                    ondblclick="displayModal(' . $index . ')">';

                                echo '<td>' . htmlspecialchars($contribution['region_code']) . '</td>';
                                echo '<td>' . htmlspecialchars($contribution['region_description']) . '</td>';
                                echo '<td>' . htmlspecialchars($contribution['zone_code']) . '</td>';
                                echo '<td class="ee-shared" style="text-align: right">' . htmlspecialchars(number_format($contribution['ee_shared'] ?? 0, 2)) . '</td>';
                                echo '<td class="er-shared" style="text-align: right">' . htmlspecialchars(number_format($contribution['er_shared'] ?? 0, 2)) . '</td>';
                                echo '<td class="total-contribution" style="text-align: right">' . htmlspecialchars(number_format($contribution['total_contribution'] ?? 0, 2)) . '</td>';
                                echo '</tr>';

                                $grand_total_ee_shared += $contribution['ee_shared'] ?? 0;
                                $grand_total_er_shared += $contribution['er_shared'] ?? 0;
                                $grand_total_contribution += $contribution['total_contribution'] ?? 0;

                                $index++;
                            }
                        } else {
                            echo '<tr><td colspan="6">No records found for the selected criteria.</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6">Please select Remittance Date to display.</td></tr>';
                    }
                ?>
            </tbody>
            <tfoot id="tableFoot">
                <tr>
                    <th colspan="3">GRAND TOTAL</th>
                    <th id="grand-total-ee-shared"><?php echo isset($grand_total_ee_shared) ? number_format($grand_total_ee_shared, 2) : '0.00'; ?></th>
                    <th id="grand-total-er-shared"><?php echo isset($grand_total_er_shared) ? number_format($grand_total_er_shared, 2) : '0.00'; ?></th>
                    <th id="grand-total-contribution"><?php echo isset($grand_total_contribution) ? number_format($grand_total_contribution, 2) : '0.00'; ?></th>
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
                        <h4 class="update-modal-title">Remittance NEW (Data Entry)</h4>
                        <button type="button" class="update-close" data-dismiss="update-modal">&times</button>
                    </div>
                    <!-- Modal body -->
                    <div class="update-modal-body">
                        <div class="content-wrap">
                            <!-- first content -->
                            <div class="first-content-wrap">
                                <h3>Date : <span name="date"><?php echo $displayDate; ?></span></h3>
                                <h3>Main Zone Code : <span name="mainzone"><?php echo $displayMainzone; ?></span></h3>
                                <h3>Zone Code : <span id="zone_code_update" name="zone_code_update"></span></h3>
                                <h3>Region Code : <span id="region_code_update" name="region_code_update"></span></h3>
                                <h3>Region Name : <span id="region_name_update" name="region_name_update"></span></h3>
                            </div>

                            <input type="hidden" name="date" id="date_input">
                            <input type="hidden" name="mainzone" id="mainzone_input">
                            <input type="hidden" name="region_code_update" id="region_code_hidden">
                            <input type="hidden" name="region_name_update" id="region_name_hidden">
                            <input type="hidden" name="remitance_type" id="remitance_type_input">

                            <!-- second content -->
                            <div class="second-content-wrap text-center fw-normal">
                                <div class="content-item">
                                    <label for="EEShared">EE SHARE</label>
                                    <input class="add_inp" type="text" name="EEShared" id="EEShared" required autocomplete="off" inputmode="decimal">
                                </div>
                                <div class="content-item">
                                    <label for="ERShared">ER SHARE</label>
                                    <input class="add_inp" type="text" name="ERShared" id="ERShared" required autocomplete="off" inputmode="decimal">
                                </div>
                                <div class="content-item">
                                    <label for="TotalContribution">TOTAL</label>
                                    <input class="add_inp" type="text" name="TotalContribution" id="TotalContribution" readonly>
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
    <script src="<?php echo $relative_path; ?>assets/js/admin/mcash-recon/rfp-payroll-data-entry-script.js"></script>
    <script>

        function enforceMoneyInput(selector) {
            document.querySelectorAll(selector).forEach(input => {
                input.addEventListener("input", function () {
                    this.value = this.value
                        .replace(/[^0-9.]/g, '')
                        .replace(/(\..*)\./g, '$1')
                        .replace(/^(\d+)(\.\d{0,2}).*$/, '$1$2');
                });
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            enforceMoneyInput("#EEShared, #ERShared");
        });

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

            const eeInput = document.querySelector("#EEShared");
            const erInput = document.querySelector("#ERShared");

            if (eeInput) {
                eeInput.addEventListener("input", updateComputedTotal);
            }

            if (erInput) {
                erInput.addEventListener("input", updateComputedTotal);
            }
        });

        function clearModalFields() {
            document.querySelector("#EEShared").value = '';
            document.querySelector("#ERShared").value = '';
            document.querySelector("#TotalContribution").value = '';
            document.querySelector("#record_id").value = '';
            document.querySelector("#region_code_update").textContent = '';
            document.querySelector("#region_name_update").textContent = '';
            document.querySelector("#zone_code_update").textContent = '';
            document.querySelector("#save-btn").style.display = "inline-block";
            document.querySelector("#update-btn").style.display = "none";
        }

        function updateComputedTotal() {
            const eeShared = parseFloat(document.querySelector("#EEShared").value) || 0;
            const erShared = parseFloat(document.querySelector("#ERShared").value) || 0;
            document.querySelector("#TotalContribution").value = (eeShared + erShared).toFixed(2);
        }

        function displayModal(rowIndex) {
            const rows = document.querySelectorAll(".selectable-row");
            if (rowIndex < 0 || rowIndex >= rows.length) return;

            const row = rows[rowIndex];
            const rowData = Array.from(row.cells).map(cell => cell.textContent.trim());

            const recordId = row.getAttribute("data-id");
            document.querySelector("#record_id").value = recordId;

            document.querySelector("#region_code_update").textContent = rowData[0] || '';
            document.querySelector("#region_name_update").textContent = rowData[1] || '';
            document.querySelector("#zone_code_update").textContent = rowData[2] || '';
            
            document.querySelector("#date_input").value = document.querySelector("input[name='restricted-date']").value;
            document.querySelector("#mainzone_input").value = document.querySelector("select[name='mainzone']").value;
            document.querySelector("#remitance_type_input").value = document.querySelector("select[name='remitance_type']").value;
            document.querySelector("#region_code_hidden").value = rowData[0] || '';
            document.querySelector("#region_name_hidden").value = rowData[1] || '';

            const eeShared = rowData[3] ? rowData[3].replace(/,/g, '') : '0';
            const erShared = rowData[4] ? rowData[4].replace(/,/g, '') : '0';
            const totalContribution = rowData[5] ? rowData[5].replace(/,/g, '') : '0';

            document.querySelector("#EEShared").value = eeShared;
            document.querySelector("#ERShared").value = erShared;
            document.querySelector("#TotalContribution").value = totalContribution;

            if (recordId && !recordId.startsWith('new_')) {
                document.querySelector("#save-btn").style.display = "none";
                document.querySelector("#update-btn").style.display = "inline-block";
            } else {
                document.querySelector("#save-btn").style.display = "inline-block";
                document.querySelector("#update-btn").style.display = "none";
            }

            document.querySelector("#myModal").classList.add("show");
        }

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
            const eeShared = parseFloat(document.querySelector("#EEShared").value) || 0;
            const erShared = parseFloat(document.querySelector("#ERShared").value) || 0;
            const totalContribution = eeShared + erShared;

            const row = document.querySelector(`tr[data-id="${recordId}"]`);
            if (row) {
                row.querySelector(".ee-shared").textContent = eeShared.toLocaleString('en-US', { minimumFractionDigits: 2 });
                row.querySelector(".er-shared").textContent = erShared.toLocaleString('en-US', { minimumFractionDigits: 2 });
                row.querySelector(".total-contribution").textContent = totalContribution.toLocaleString('en-US', { minimumFractionDigits: 2 });

                tableChanges[recordId] = {
                    id: recordId.startsWith('new_') ? null : recordId,
                    date: document.querySelector("#date_input").value,
                    mainzone: document.querySelector("#mainzone_input").value,
                    region_code: document.querySelector("#region_code_hidden").value,
                    region_name: document.querySelector("#region_name_hidden").value,
                    remitance_type: document.querySelector("#remitance_type_input").value,
                    ee_shared: eeShared,
                    er_shared: erShared
                };

                updateGrandTotals();
            }

            document.querySelector("#myModal").classList.remove("show");
            clearModalFields();
        }

        function updateGrandTotals() {
            let grandTotalEeShared = 0;
            let grandTotalErShared = 0;
            let grandTotalContribution = 0;

            document.querySelectorAll(".selectable-row").forEach(row => {
                grandTotalEeShared += parseFloat(row.querySelector(".ee-shared").textContent.replace(/,/g, '')) || 0;
                grandTotalErShared += parseFloat(row.querySelector(".er-shared").textContent.replace(/,/g, '')) || 0;
                grandTotalContribution += parseFloat(row.querySelector(".total-contribution").textContent.replace(/,/g, '')) || 0;
            });

            document.querySelector("#grand-total-ee-shared").textContent = grandTotalEeShared.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.querySelector("#grand-total-er-shared").textContent = grandTotalErShared.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.querySelector("#grand-total-contribution").textContent = grandTotalContribution.toLocaleString('en-US', { minimumFractionDigits: 2 });
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