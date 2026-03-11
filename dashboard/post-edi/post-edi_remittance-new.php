<?php
    
    include '../../config/connection.php';
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
                    // Handle HRMD role - no access to this page
                    break;
                case 'CAD':
                    // Handle CAD role - allow access to this page
                    $hasRequiredRole = true;
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
        header('Location: post-edi_remittance-new.php');
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
    function checkPostingRecord($conn, $database, $mainzone, $zone, $region, $restrictedDate) {
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
                        AND r.zone like '%$region%'
                        AND r.remitance_format_type='NEW'
                        
                ";
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
                    AND bp.ml_matic_region != 'VISMIN Showroom'
                    AND r.remitance_format_type='NEW'
                    
                ";
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

                        MAX(r.ee_gl_code_dr1) as ee_gl_code_dr1,
                        MAX(r.gl_code_dr1) as gl_code_dr1,
                        MAX(r.gl_code_total_ee_er_dr1) as gl_code_total_ee_er_dr1,

                        MAX(r.ee_gl_code_dr2) as ee_gl_code_dr2,
                        MAX(r.gl_code_dr2) as gl_code_dr2,
                        MAX(r.gl_code_total_ee_er_dr2) as gl_code_total_ee_er_dr2,

                        MAX(r.ee_gl_code_dr3) as ee_gl_code_dr3,
                        MAX(r.gl_code_dr3) as gl_code_dr3,
                        MAX(r.gl_code_total_ee_er_dr3) as gl_code_total_ee_er_dr3,
                        
                        MAX(r.gl_code_dr4) as gl_code_dr4,

                        r.bos_code,
                        r.region,
                        MAX(r.branch_name) as branch_name,

                        MAX(r.ee_dr1) as ee_dr1,
                        MAX(r.dr1) as dr1,
                        MAX(r.total_ee_er_dr1) as total_ee_er_dr1,

                        MAX(r.ee_dr2) as ee_dr2,
                        MAX(r.dr2) as dr2,
                        MAX(r.total_ee_er_dr2) as total_ee_er_dr2,

                        MAX(r.ee_dr3) as ee_dr3,
                        MAX(r.dr3) as dr3,
                        MAX(r.total_ee_er_dr3) as total_ee_er_dr3,

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
                        AND r.remitance_format_type='NEW'
                    GROUP BY 
                        bp.code,
                        bp.region,
                        r.zone,
                        r.remitance_date,
                        r.bos_code,
                        r.region
                    ORDER BY 
                        bp.region;
                        
                ";
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

                        MAX(r.ee_gl_code_dr1) as ee_gl_code_dr1,
                        MAX(r.gl_code_dr1) as gl_code_dr1,
                        MAX(r.gl_code_total_ee_er_dr1) as gl_code_total_ee_er_dr1,

                        MAX(r.ee_gl_code_dr2) as ee_gl_code_dr2,
                        MAX(r.gl_code_dr2) as gl_code_dr2,
                        MAX(r.gl_code_total_ee_er_dr2) as gl_code_total_ee_er_dr2,

                        MAX(r.ee_gl_code_dr3) as ee_gl_code_dr3,
                        MAX(r.gl_code_dr3) as gl_code_dr3,
                        MAX(r.gl_code_total_ee_er_dr3) as gl_code_total_ee_er_dr3,
                        
                        MAX(r.gl_code_dr4) as gl_code_dr4,

                        r.bos_code,
                        r.region,
                        MAX(r.branch_name) as branch_name,

                        MAX(r.ee_dr1) as ee_dr1,
                        MAX(r.dr1) as dr1,
                        MAX(r.total_ee_er_dr1) as total_ee_er_dr1,

                        MAX(r.ee_dr2) as ee_dr2,
                        MAX(r.dr2) as dr2,
                        MAX(r.total_ee_er_dr2) as total_ee_er_dr2,

                        MAX(r.ee_dr3) as ee_dr3,
                        MAX(r.dr3) as dr3,
                        MAX(r.total_ee_er_dr3) as total_ee_er_dr3,
                        
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
                        AND r.remitance_format_type='NEW'
                    GROUP BY 
                        bp.code,
                        bp.region,
                        r.zone,
                        r.remitance_date,
                        r.bos_code,
                        r.region
                    ORDER BY 
                        bp.region;
                        
                "; 
        } 
    
        // echo $fetchQuery;
        // echo ("Hello World");
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

            $ee_dr1 = $conn->real_escape_string($row['ee_dr1']);
            $ee_gl_code_dr1 = $conn->real_escape_string($row['ee_gl_code_dr1']);
            $e_dr1 = $conn->real_escape_string($row['dr1']);
            $e_gl_code_dr1 = $conn->real_escape_string($row['gl_code_dr1']);

            $e_total_ee_er_dr1 = $conn->real_escape_string($row['total_ee_er_dr1']);
            $e_gl_code_total_ee_er_dr1 = $conn->real_escape_string($row['gl_code_total_ee_er_dr1']);

            $ee_dr2 = $conn->real_escape_string($row['ee_dr2']);
            $ee_gl_code_dr2 = $conn->real_escape_string($row['ee_gl_code_dr2']);
            $e_dr2 = $conn->real_escape_string($row['dr2']);
            $e_gl_code_dr2 = $conn->real_escape_string($row['gl_code_dr2']);

            $e_total_ee_er_dr2 = $conn->real_escape_string($row['total_ee_er_dr2']);
            $e_gl_code_total_ee_er_dr2 = $conn->real_escape_string($row['gl_code_total_ee_er_dr2']);

            $ee_dr3 = $conn->real_escape_string($row['ee_dr3']);
            $ee_gl_code_dr3 = $conn->real_escape_string($row['ee_gl_code_dr3']);
            $e_dr3 = $conn->real_escape_string($row['dr3']);
            $e_gl_code_dr3 = $conn->real_escape_string($row['gl_code_dr3']);
            
            $e_total_ee_er_dr3 = $conn->real_escape_string($row['total_ee_er_dr3']);
            $e_gl_code_total_ee_er_dr3 = $conn->real_escape_string($row['gl_code_total_ee_er_dr3']);

            $e_cost_center1 = $conn->real_escape_string($row['cost_center1']);

            // Set the time zone to Philippines time.
            // date_default_timezone_set('Asia/Manila');

            $posted_date = date('Y-m-d H:i:s');
            $posted_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown User';
        
            $insertQuery = "INSERT INTO " . $database[0] . ".remitance_edi_report (
                                    remitance_date,
                                    mainzone,
                                    `zone`,
                                    region,
                                    ml_matic_region,
                                    region_code,
                                    kp_code, 
                                    ml_matic_status,
                                    branch_code,
                                    branch_name,

                                    ee_dr1,
                                    ee_gl_code_dr1, 
                                    dr1,
                                    gl_code_dr1,
                                    total_ee_er_dr1,
                                    gl_code_total_ee_er_dr1,

                                    ee_dr2,
                                    ee_gl_code_dr2,
                                    dr2,
                                    gl_code_dr2,
                                    total_ee_er_dr2,
                                    gl_code_total_ee_er_dr2,

                                    ee_dr3,
                                    ee_gl_code_dr3,
                                    dr3,
                                    gl_code_dr3,
                                    total_ee_er_dr3,
                                    gl_code_total_ee_er_dr3,

                                    cost_center,
                                    remitance_format_type,
                                    posted_by, 
                                    posted_date
                                ) 
                            VALUES (
                                    '" . $e_remitance_date . "',
                                    '" . $mainzone . "',
                                    '" . $e_zone . "',
                                    '" . $e_region . "',
                                    '" . $e_ml_matic_region . "', 
                                    '" . $e_region_code . "',
                                    '" . $e_kp_code . "',
                                    '" . $e_ml_matic_status . "',
                                    '" . $e_code . "', 
                                    '" . $e_branch_name . "',

                                    '" . $ee_dr1 . "',
                                    '" . $ee_gl_code_dr1 . "',
                                    '" . $e_dr1 . "',
                                    '" . $e_gl_code_dr1 . "',
                                    '" . $e_total_ee_er_dr1 . "',
                                    '" . $e_gl_code_total_ee_er_dr1 . "',

                                    '" . $ee_dr2 . "',
                                    '" . $ee_gl_code_dr2 . "',
                                    '" . $e_dr2 . "',
                                    '" . $e_gl_code_dr2 . "',
                                    '" . $e_total_ee_er_dr2 . "',
                                    '" . $e_gl_code_total_ee_er_dr2 . "',

                                    '" . $ee_dr3 . "',
                                    '" . $ee_gl_code_dr3 . "',
                                    '" . $e_dr3 . "',
                                    '" . $e_gl_code_dr3 . "',
                                    '" . $e_total_ee_er_dr3 . "',
                                    '" . $e_gl_code_total_ee_er_dr3 . "',
                                    
                                    '" . $e_cost_center1 . "',
                                    'NEW',
                                    '" . $posted_by . "',
                                    '" . $posted_date . "'
                                )

                            ";
            
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
                                    AND r.zone like '%$region%'
                                    AND r.remitance_format_type='NEW'
                                    
                                ";
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
                                    AND bp.ml_matic_region != 'VISMIN Showroom'
                                    AND r.remitance_format_type='NEW'
                                    
                                ";
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
    <link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <style>
        td{
            text-align: right;
        }
        .word {
            text-align: center;
            font-weight: bold;
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
        input[type="month"] {
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
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
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
    </style>
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>REMITTANCE NEW<span>[POST EDI ]</span></center>

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

    <script src="<?php echo $relative_path; ?>assets/js/admin/report-file/script1.js"></script>
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
                window.location.href = 'post-edi_remittance-new.php?proceed=true';
            } else {
                window.location.href = 'post-edi_remittance-new.php';
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
                MAX(r.ee_gl_code_dr1) as ee_gl_code_dr1,
                MAX(r.gl_code_dr1) as gl_code_dr1,
                MAX(r.gl_code_total_ee_er_dr1) as gl_code_total_ee_er_dr1,

                MAX(r.ee_gl_code_dr2) as ee_gl_code_dr2,
                MAX(r.gl_code_dr2) as gl_code_dr2,
                MAX(r.gl_code_total_ee_er_dr2) as gl_code_total_ee_er_dr2,

                MAX(r.ee_gl_code_dr3) as ee_gl_code_dr3,
                MAX(r.gl_code_dr3) as gl_code_dr3,
                MAX(r.gl_code_total_ee_er_dr3) as gl_code_total_ee_er_dr3,
                
                MAX(r.gl_code_dr4) as gl_code_dr4,
                r.bos_code,
                MAX(r.branch_name) as branch_name,
                r.region,
                MAX(r.ee_dr1) as ee_dr1,
                MAX(r.dr1) as dr1,
                MAX(r.total_ee_er_dr1) as total_ee_er_dr1,

                MAX(r.ee_dr2) as ee_dr2,
                MAX(r.dr2) as dr2,
                MAX(r.total_ee_er_dr1) as total_ee_er_dr2,

                MAX(r.ee_dr3) as ee_dr3,
                MAX(r.dr3) as dr3,
                MAX(r.total_ee_er_dr1) as total_ee_er_dr3,
                
                MAX(r.total_ee) as total_ee,
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
                AND r.post_edi = 'pending'
                AND r.remitance_format_type='NEW'
            GROUP BY 
                bp.code,
                bp.cost_center,
                bp.region,
                bp.zone,
                r.remitance_date,
                r.bos_code,
                r.region
            ORDER BY 
                bp.region;
                
        ";
}else{
    $sql = "SELECT
                bp.code,
                bp.cost_center AS cost_center1, 
                bp.region, 
                bp.zone,
                r.remitance_date,
                MAX(r.ee_gl_code_dr1) as ee_gl_code_dr1,
                MAX(r.gl_code_dr1) as gl_code_dr1,
                MAX(r.gl_code_total_ee_er_dr1) as gl_code_total_ee_er_dr1,

                MAX(r.ee_gl_code_dr2) as ee_gl_code_dr2,
                MAX(r.gl_code_dr2) as gl_code_dr2,
                MAX(r.gl_code_total_ee_er_dr2) as gl_code_total_ee_er_dr2,

                MAX(r.ee_gl_code_dr3) as ee_gl_code_dr3,
                MAX(r.gl_code_dr3) as gl_code_dr3,
                MAX(r.gl_code_total_ee_er_dr3) as gl_code_total_ee_er_dr3,
                
                MAX(r.gl_code_dr4) as gl_code_dr4,
                r.bos_code,
                MAX(r.branch_name) as branch_name,
                r.region,
                MAX(r.ee_dr1) as ee_dr1,
                MAX(r.dr1) as dr1,
                MAX(r.total_ee_er_dr1) as total_ee_er_dr1,

                MAX(r.ee_dr2) as ee_dr2,
                MAX(r.dr2) as dr2,
                MAX(r.total_ee_er_dr2) as total_ee_er_dr2,

                MAX(r.ee_dr3) as ee_dr3,
                MAX(r.dr3) as dr3,
                MAX(r.total_ee_er_dr3) as total_ee_er_dr3,
                
                MAX(r.total_ee) as total_ee,
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
                AND r.post_edi = 'pending'
                AND r.remitance_format_type='NEW'
            GROUP BY 
                bp.code,
                bp.cost_center,
                bp.region,
                bp.zone,
                r.remitance_date,
                r.bos_code,
                r.region
            ORDER BY 
                bp.region;
                
        ";
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
            echo "<th rowspan='4'></th>";
            echo "<th style='color: red;' colspan='3'>PHILHEALTH</th>";
            echo "<th rowspan='4'></th>";
            echo "<th style='color: red;' colspan='5'>PAGIBIG</th>";
            echo "<th rowspan='4'></th>";
            echo "<th rowspan='4'>Cost Center</th>";
            echo "<th rowspan='4'>Region</th>";
        echo "</tr>";

        // second row
        echo "<tr>";
            echo "<th>EE PREMIUM</th>";
            echo "<th>ER PREMIUM</th>";
            echo "<th style='color: darkred;'>PAYABLE</th>";
            echo "<th>EE LOAN</th>";
            echo "<th style='color: darkred;'>PAYABLE</th>";
            echo "<th>EE PREMIUM</th>";
            echo "<th>ER PREMIUM</th>";
            echo "<th style='color: darkred;'>PAYABLE</th>";
            echo "<th>EE PREMIUM</th>";
            echo "<th>ER PREMIUM</th>";
            echo "<th style='color: darkred;'>PAYABLE</th>";
            echo "<th>EE LOAN</th>";
            echo "<th style='color: darkred;'>PAYABLE</th>";
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
            echo "<th>". $ee_gl_code_dr2 ."</th>";
            echo "<th>". $gl_code_dr2 ."</th>";
            echo "<th>". $gl_code_ee_er_dr2 ."</th>";
            echo "<th>". $ee_gl_code_dr3 ."</th>";
            echo "<th>". $gl_code_dr3 ."</th>";
            echo "<th>". $gl_code_ee_er_dr3 ."</th>";
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
            echo "<th>DR</th>";
            echo "<th>DR</th>";
            echo "<th>CR</th>";
            echo "<th>DR</th>";
            echo "<th>DR</th>";
            echo "<th>CR</th>";
            echo "<th>DR</th>";
            echo "<th>CR</th>";
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
            echo "<td class='word' style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['bos_code']) . "</td>";
            echo "<td class='word' style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";

            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['ee_dr1'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr1'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['total_ee_er_dr1'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'></td>";
            echo "<td style='background-color: $color; font-weight: $bold'></td>";
            echo "<td></td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['ee_dr2'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr2'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['total_ee_er_dr2'],2)) . "</td>";
            echo "<td></td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['ee_dr3'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr3'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['total_ee_er_dr3'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'></td>";
            echo "<td style='background-color: $color; font-weight: $bold'></td>";
            echo "<td></td>";
            //echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['cost_center']) . "</td>";
            echo "<td class='word' style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center1']) . "</td>";
            echo "<td class='word' style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
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