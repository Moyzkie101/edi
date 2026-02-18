<?php
    
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    include '../config/connection.php';
    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>';

    if (isset($_GET['proceed']) && $_GET['proceed'] === 'true') {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        if (checkPostingRecord($conn, $database, $mainzone, $zone, $region, $restrictedDate)) {
            // Set a flag for already posted data
            $_SESSION['swal_message'] = [
                'title' => 'Warning!',
                'text' => 'Data already posted.',
                'icon' => 'warning'
            ];
        } else {
            $insertSuccess = insertData($conn, $database, $mainzone, $zone, $region, $restrictedDate);
    
            if ($insertSuccess) {
                $_SESSION['swal_message'] = [
                    'title' => 'Success!',
                    'text' => 'Data successfully posted.',
                    'icon' => 'success'
                ];
            } else {
                $_SESSION['swal_message'] = [
                    'title' => 'Error!',
                    'text' => 'Failed to post data.',
                    'icon' => 'error'
                ];
            }
        }
    
        // Redirect to prevent form resubmission and ensure clean page reload
        header('Location: remittance_post_old_edi.php');
        exit();
    }

    // Check if there's a SweetAlert message to display
    if (isset($_SESSION['swal_message'])) {
        $swal = $_SESSION['swal_message'];
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: '{$swal['title']}',
                        text: '{$swal['text']}',
                        icon: '{$swal['icon']}',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        // Unset the message after displaying it
        unset($_SESSION['swal_message']);
    }
    
    // Function to check for pending records
    function checkPostingRecord($conn,$database, $mainzone, $zone, $region, $restrictedDate) {
        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $sql = "SELECT post_edi 
                    FROM " . $database[0] . ".remitance r
                    INNER JOIN " . $database[1] . ".branch_profile bp
                    ON 
                        r.bos_code = bp.code AND r.region_code = bp.region_code 
                    WHERE 
                        bp.mainzone = '$mainzone'
                        AND r.remitance_date = '$restrictedDate'
                        AND bp.ml_matic_region = '$zone'
                        AND NOT (bp.code = 18 AND r.zone = 'VIS')  -- to exclude duljo branch
                        AND r.zone like '%$region%'";
        }else{
            $sql = "SELECT post_edi 
                    FROM " . $database[0] . ".remitance r
                    INNER JOIN " . $database[1] . ".branch_profile bp
                    ON 
                        r.bos_code = bp.code AND r.region_code = bp.region_code 
                    WHERE 
                        bp.mainzone = '$mainzone'
                    AND r.zone = '$zone'
                    AND r.zone != 'JVIS' -- to exclude sm seaside showroom
                    AND bp.region_code LIKE '%$region%'
                    AND r.remitance_date = '$restrictedDate'
                    AND bp.ml_matic_region != 'LNCR Showroom'
                    AND bp.ml_matic_region != 'VISMIN Showroom'";
        }
        
        $result = $conn->query($sql);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                if ($row['post_edi'] === 'posted') {
                    return true;
                }
            }
        }
        return false;
    }

    // function to insert data
    function insertData($conn, $database, $mainzone, $zone, $region, $restrictedDate) {
        $errors = [];

        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $fetchQuery = "SELECT
                        bp.code, 
                        bp.region, 
                        r.zone,
                        r.remitance_date,
                        MAX(bp.region_code) as region_code,
                        MAX(bp.ml_matic_region) as ml_matic_region,
                        MAX(bp.ml_matic_status) as ml_matic_status,
                        MAX(bp.kp_code) as kp_code,
                        MAX(bp.cost_center) as cost_center1,
                        MAX(r.gl_code_dr1) as gl_code_dr1,
                        MAX(r.gl_code_dr2) as gl_code_dr2,
                        MAX(r.gl_code_dr3) as gl_code_dr3,
                        MAX(r.gl_code_dr4) as gl_code_dr4,
                        r.bos_code,
                        r.region,
                        MAX(r.branch_name) as branch_name,
                        MAX(r.dr1) as dr1,
                        MAX(r.dr2) as dr2,
                        MAX(r.dr3) as dr3,
                        MAX(r.dr4) as dr4
                    FROM
                        " . $database[0] . ".remitance r
                    INNER JOIN 
                        " . $database[1] . ".branch_profile bp
                    ON 
                        r.bos_code = bp.code AND r.region_code = bp.region_code
                    WHERE
                        bp.mainzone = '$mainzone'
                        AND r.remitance_date = '$restrictedDate'
                        AND bp.ml_matic_region = '$zone'
                        AND NOT (bp.code = 18 AND r.zone = 'VIS')  -- to exclude duljo branch
                        AND r.zone like '%$region%'
                        AND r.post_edi = 'pending'
                    GROUP BY 
                        bp.code,
                        bp.region,
                        r.zone,
                        r.remitance_date,
                        r.bos_code,
                        r.region
                    ORDER BY 
                        bp.region;";
        }else{
            $fetchQuery = "SELECT
                        bp.code, 
                        bp.region, 
                        r.zone,
                        r.remitance_date,
                        MAX(bp.region_code) as region_code,
                        MAX(bp.ml_matic_region) as ml_matic_region,
                        MAX(bp.ml_matic_status) as ml_matic_status,
                        MAX(bp.kp_code) as kp_code,
                        MAX(bp.cost_center) as cost_center1,
                        MAX(r.gl_code_dr1) as gl_code_dr1,
                        MAX(r.gl_code_dr2) as gl_code_dr2,
                        MAX(r.gl_code_dr3) as gl_code_dr3,
                        MAX(r.gl_code_dr4) as gl_code_dr4,
                        r.bos_code,
                        r.region,
                        MAX(r.branch_name) as branch_name,
                        MAX(r.dr1) as dr1,
                        MAX(r.dr2) as dr2,
                        MAX(r.dr3) as dr3,
                        MAX(r.dr4) as dr4
                    FROM
                        " . $database[0] . ".remitance r
                    INNER JOIN 
                        " . $database[1] . ".branch_profile bp
                    ON 
                        r.bos_code = bp.code AND r.region_code = bp.region_code
                    WHERE
                        bp.mainzone = '$mainzone'
                        AND r.zone = '$zone'
                        AND r.zone != 'JVIS' -- to exclude sm seaside showroom
                        AND bp.region_code LIKE '%$region%'
                        AND r.remitance_date = '$restrictedDate'
                        AND bp.ml_matic_region != 'LNCR Showroom'
                        AND bp.ml_matic_region != 'VISMIN Showroom'
                        AND r.post_edi = 'pending'
                    GROUP BY 
                        bp.code,
                        bp.region,
                        r.zone,
                        r.remitance_date,
                        r.bos_code,
                        r.region
                    ORDER BY 
                        bp.region;"; 
        } 
    
        //echo $fetchQuery;
        $result = $conn->query($fetchQuery);

        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {

                $e_remitance_date = $conn->real_escape_string($row['remitance_date']);
                $e_zone = $conn->real_escape_string($row['zone']);
                $e_region = $conn->real_escape_string($row['region']);
                $e_ml_matic_region = $conn->real_escape_string($row['ml_matic_region']);
                $e_region_code = $conn->real_escape_string($row['region_code']);
                $e_kp_code = $conn->real_escape_string($row['kp_code']);
                $e_ml_matic_status = $conn->real_escape_string($row['ml_matic_status']);
                $e_code = $conn->real_escape_string($row['code']);
                $e_branch_name = $conn->real_escape_string($row['branch_name']);
                $e_dr1 = $conn->real_escape_string($row['dr1']);
                $e_gl_code_dr1 = $conn->real_escape_string($row['gl_code_dr1']);
                $e_dr2 = $conn->real_escape_string($row['dr2']);
                $e_gl_code_dr2 = $conn->real_escape_string($row['gl_code_dr2']);
                $e_dr3 = $conn->real_escape_string($row['dr3']);
                $e_gl_code_dr3 = $conn->real_escape_string($row['gl_code_dr3']);
                $e_dr4 = $conn->real_escape_string($row['dr4']);
                $e_gl_code_dr4 = $conn->real_escape_string($row['gl_code_dr4']);
                $e_cost_center1 = $conn->real_escape_string($row['cost_center1']);

                // Set the time zone to Philippines time.
                // date_default_timezone_set('Asia/Manila');

                $posted_date = date('Y-m-d H:i:s');
                $posted_by = $_SESSION['admin_name'];
            
                $insertQuery = "INSERT INTO " . $database[0] . ".remitance_edi_report (remitance_date, mainzone, zone, region, ml_matic_region, region_code, kp_code, 
                                ml_matic_status, branch_code, branch_name, dr1, gl_code_dr1, dr2, gl_code_dr2, dr3, gl_code_dr3, dr4, gl_code_dr4, cost_center, posted_by, posted_date) 
                                VALUES ('" . $e_remitance_date . "', '" . $mainzone . "', '" . $e_zone . "', '" . $e_region . "', '" . $e_ml_matic_region . "', 
                                '" . $e_region_code . "', '" . $e_kp_code . "', '" . $e_ml_matic_status . "', '" . $e_code . "', 
                                '" . $e_branch_name . "', '" . $e_dr1 . "', '" . $e_gl_code_dr1 . "', '" . $e_dr2 . "',
                                '" . $e_gl_code_dr2 . "', '" . $e_dr3 . "', '" . $e_gl_code_dr3 . "', '" . $e_dr4 . "',
                                '" . $e_gl_code_dr4 . "', '" . $e_cost_center1 . "', '" . $posted_by . "', '" . $posted_date . "')";
                
                // Execute insert query and collect status
                if ($conn->query($insertQuery) !== TRUE) {
                    $errors[] = $conn->error;
                }
            }

            // Check if there were any errors
            if (empty($errors)) {

                if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                    $updatePost = "UPDATE " . $database[0] . ".remitance r
                                    INNER JOIN 
                                        " . $database[1] . ".branch_profile bp
                                    ON 
                                        r.bos_code = bp.code AND r.region_code = bp.region_code  
                                    SET post_edi = 'posted'
                                    WHERE 
                                        bp.mainzone = '$mainzone'
                                    AND r.remitance_date = '$restrictedDate'
                                    AND bp.ml_matic_region = '$zone'
                                    AND NOT (bp.code = 18 AND r.zone = 'VIS')  -- to exclude duljo branch
                                    AND r.zone like '%$region%'";
                }else{
                    $updatePost = "UPDATE " . $database[0] . ".remitance r
                                    INNER JOIN 
                                        " . $database[1] . ".branch_profile bp
                                    ON 
                                        r.bos_code = bp.code AND r.region_code = bp.region_code  
                                    SET post_edi = 'posted' 
                                     WHERE
                                        bp.mainzone = '$mainzone'
                                    AND r.zone = '$zone'
                                    AND r.zone != 'JVIS' -- to exclude sm seaside showroom
                                    AND bp.region_code LIKE '%$region%'
                                    AND r.remitance_date = '$restrictedDate'
                                    AND bp.ml_matic_region != 'LNCR Showroom'
                                    AND bp.ml_matic_region != 'VISMIN Showroom'";
                }

                if ($conn->query($updatePost) === TRUE) {
                    return true;  // Success
                } else {
                    $errors[] = $conn->error;
                }

            } else {
                echo "Error inserting records: " . implode(', ', $errors);
            }

        } else {
            return false;  // No records found to insert
            //echo $fetchQuery;
        }

        // If there were any errors, return false
        return empty($errors);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>REMITTANCE OLD <span>[POST EDI]</span></center>

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

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
                <label for="restricted-date">Remittance date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>

        <div id="showdl" style="display: none">
            <button class="post-btn" onclick="postEdi()">Post EDI</button>
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

<script>
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
                window.location.href = 'remittance_post_old_edi.php?proceed=true';
            } else {
                window.location.href = 'remittance_post_old_edi.php';
            }
        });
    }
</script>

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

if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
    $sql = "SELECT
                bp.code,
                bp.cost_center AS cost_center1, 
                bp.region, 
                bp.zone,
                r.remitance_date,
                MAX(r.gl_code_dr1) as gl_code_dr1,
                MAX(r.gl_code_dr2) as gl_code_dr2,
                MAX(r.gl_code_dr3) as gl_code_dr3,
                MAX(r.gl_code_dr4) as gl_code_dr4,
                r.bos_code,
                MAX(r.branch_name) as branch_name,
                r.region,
                MAX(r.dr1) as dr1,
                MAX(r.dr2) as dr2,
                MAX(r.dr3) as dr3,
                MAX(r.dr4) as dr4,
                COUNT(DISTINCT bp.code) as branch_count
            FROM
                " . $database[0] . ".remitance r
            INNER JOIN 
                " . $database[1] . ".branch_profile bp
            ON 
                r.bos_code = bp.code AND r.region_code = bp.region_code
            WHERE
                bp.mainzone = '$mainzone'
                AND r.remitance_date = '$restrictedDate'
                AND bp.ml_matic_region = '$zone'
                AND bp.zone LIKE '%$region%'
                AND NOT (bp.code = 18 AND r.zone = 'VIS')  -- to exclude duljo branch
            GROUP BY 
                bp.code,
                bp.cost_center,
                bp.region,
                bp.zone,
                r.remitance_date,
                r.bos_code,
                r.region
            ORDER BY 
                bp.region;";
}else{
            $sql = "SELECT
                bp.code,
                bp.cost_center AS cost_center1, 
                bp.region, 
                bp.zone,
                r.remitance_date,
                MAX(r.gl_code_dr1) as gl_code_dr1,
                MAX(r.gl_code_dr2) as gl_code_dr2,
                MAX(r.gl_code_dr3) as gl_code_dr3,
                MAX(r.gl_code_dr4) as gl_code_dr4,
                r.bos_code,
                MAX(r.branch_name) as branch_name,
                r.region,
                MAX(r.dr1) as dr1,
                MAX(r.dr2) as dr2,
                MAX(r.dr3) as dr3,
                MAX(r.dr4) as dr4,
                COUNT(DISTINCT bp.code) as branch_count
            FROM
                " . $database[0] . ".remitance r
            INNER JOIN 
                " . $database[1] . ".branch_profile bp
            ON 
                r.bos_code = bp.code AND r.region_code = bp.region_code
            WHERE
                bp.mainzone = '$mainzone'
                AND bp.zone = '$zone'
                AND r.zone != 'JVIS' -- to exclude sm seaside showroom
                AND bp.region_code LIKE '%$region%'
                AND r.remitance_date = '$restrictedDate'
                AND bp.ml_matic_region != 'LNCR Showroom'
                AND bp.ml_matic_region != 'VISMIN Showroom'
            GROUP BY 
                bp.code,
                bp.cost_center,
                bp.region,
                bp.zone,
                r.remitance_date,
                r.bos_code,
                r.region
            ORDER BY 
                bp.region;"; 
}

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

        $payroll_date = htmlspecialchars($first_row['remitance_date']);
        $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
        $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
        $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
        $gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);

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

            if (strpos($row['cost_center1'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                $color = '#4fc917';
                $bold = 'bold';
            } else {
                $color = 'none';
                $bold = 'normal';
            }

            $totalNumberOfBranches++;

            echo "<tr>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['bos_code']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr1']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr2']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr3']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr4']) . "</td>";
            //echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['cost_center']) . "</td>";
            echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center1']) . "</td>";
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