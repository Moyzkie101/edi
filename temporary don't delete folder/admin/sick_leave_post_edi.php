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
		$endDate = $_SESSION['endDate'] ?? '';
    
        if (checkPostingRecord($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate)) {
            // Set a flag for already posted data
            $_SESSION['swal_message'] = [
                'title' => 'Warning!',
                'text' => 'Data already posted.',
                'icon' => 'warning'
            ];
        } else {
            $insertSuccess = insertData($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate);
    
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
        header('Location: sick_leave_post_edi.php');
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
    function checkPostingRecord($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate) {
        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $sql = "SELECT post_edi 
                    FROM " . $database[0] . ".sick_leave p
                    INNER JOIN " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code 
                    WHERE 
                        bp.mainzone = '$mainzone'";
                        if($restrictedDate === $endDate){
                            $sql .="AND p.payroll_date = '$restrictedDate'";
                        }else{
                            $sql .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                        }
                        $sql .="AND bp.ml_matic_region = '$zone'
                        AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                        AND p.zone like '%$region%'";
        }else{
            $sql = "SELECT post_edi 
                    FROM " . $database[0] . ".sick_leave p
                    INNER JOIN " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code 
                    WHERE 
                        bp.mainzone = '$mainzone'
                    AND p.zone = '$zone'
                    AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                    AND bp.region_code LIKE '%$region%'";
                    if($restrictedDate === $endDate){
                        $sql .="AND p.payroll_date = '$restrictedDate'";
                    }else{
                        $sql .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                    }
                    $sql .="AND bp.ml_matic_region != 'LNCR Showroom'
                            AND bp.ml_matic_region != 'VISMIN Showroom'";
        }
        echo $sql;
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
    function insertData($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate) {
        $errors = [];

        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $fetchQuery = "SELECT
                        bp.code,
                        p.cost_center, 
                        bp.region, 
                        p.zone,
						MAX(mp.zone) as mlmatic_zone,
                        p.payroll_date,
                        MAX(bp.region_code) as region_code,
                        MAX(bp.ml_matic_region) as ml_matic_region,
                        MAX(bp.ml_matic_status) as ml_matic_status,
                        MAX(bp.kp_code) as kp_code,
                        MAX(p.sheet_name) as sheet_name,
                        MAX(bp.cost_center) as cost_center1,
                        MAX(p.gl_code_basic_pay_regular) as gl_code_basic_pay_regular,
                        MAX(p.gl_code_basic_pay_trainee) as gl_code_basic_pay_trainee,
                        MAX(p.gl_code_allowances) as gl_code_allowances,
                        MAX(p.gl_code_bm_allowance) as gl_code_bm_allowance,
                        MAX(p.gl_code_overtime_regular) as gl_code_overtime_regular,
                        MAX(p.gl_code_overtime_trainee) as gl_code_overtime_trainee,
                        MAX(p.gl_code_cola) as gl_code_cola,
                        MAX(p.gl_code_sick_leave) as gl_code_sick_leave,
                        MAX(p.gl_code_other_income) as gl_code_other_income,
                        MAX(p.gl_code_salary_adjustment) as gl_code_salary_adjustment,
                        MAX(p.gl_code_graveyard) as gl_code_graveyard,
                        MAX(p.gl_code_late_regular) as gl_code_late_regular,
                        MAX(p.gl_code_late_trainee) as gl_code_late_trainee,
                        MAX(p.gl_code_leave_regular) as gl_code_leave_regular,
                        MAX(p.gl_code_leave_trainee) as gl_code_leave_trainee,
                        MAX(p.gl_code_all_other_deductions) as gl_code_all_other_deductions,
                        MAX(p.gl_code_total) as gl_code_total,
                        p.bos_code,
                        MAX(p.branch_name) as branch_name_hr,
						MAX(mp.branch_name) as branch_name_mlmatic,
                        p.region,
                        MAX(p.basic_pay_regular) as basic_pay_regular,
                        MAX(p.basic_pay_trainee) as basic_pay_trainee,
                        MAX(p.allowances) as allowances,
                        MAX(p.bm_allowance) as bm_allowance,
                        MAX(p.overtime_regular) as overtime_regular,
                        MAX(p.overtime_trainee) as overtime_trainee,
                        MAX(p.cola) as cola,
                        MAX(p.sick_leave) as sick_leave,
                        MAX(p.other_income) as other_income,
                        MAX(p.salary_adjustment) as salary_adjustment,
                        MAX(p.graveyard) as graveyard,
                        MAX(p.late_regular) as late_regular,
                        MAX(p.late_trainee) as late_trainee,
                        MAX(p.leave_regular) as leave_regular,
                        MAX(p.leave_trainee) as leave_trainee,
                        MAX(p.all_other_deductions) as all_other_deductions,
                        MAX(p.total) as total,
                        MAX(p.no_of_branch_employee) as no_of_branch_employee,
                        MAX(p.no_of_employees_allocated) as no_of_employees_allocated,
                        COUNT(DISTINCT bp.code) as branch_count
                    FROM
                        " . $database[0] . ".sick_leave p
                    INNER JOIN 
                        " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code
					INNER JOIN
						" . $database[0] . ".mlmatic_profile mp
					ON
						mp.code = p.bos_code AND mp.mlmatic_region = bp.ml_matic_region AND mp.uploaded_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                    WHERE
                        bp.mainzone = '$mainzone'";
                        if($restrictedDate === $endDate){
                            $fetchQuery .="AND p.payroll_date = '$restrictedDate'";
                        }else{
                            $fetchQuery .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                        }
                        $fetchQuery .="AND bp.ml_matic_region = '$zone'
                        AND p.zone like '%$region%'
                        AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                        AND p.post_edi = 'pending'
                    GROUP BY 
                        bp.code,
                        p.cost_center,
                        bp.region,
                        p.zone,
                        p.payroll_date,
                        p.bos_code,
                        p.region
                    ORDER BY 
                        bp.region;";
        }else{
                    $fetchQuery = "SELECT
                        bp.code,
                        p.cost_center, 
                        bp.region, 
                        p.zone,
						MAX(mp.zone) as mlmatic_zone,
                        p.payroll_date,
                        MAX(bp.region_code) as region_code,
                        MAX(bp.ml_matic_region) as ml_matic_region,
                        MAX(bp.ml_matic_status) as ml_matic_status,
                        MAX(bp.kp_code) as kp_code,
                        MAX(p.sheet_name) as sheet_name,
                        MAX(bp.cost_center) as cost_center1,
                        MAX(p.gl_code_basic_pay_regular) as gl_code_basic_pay_regular,
                        MAX(p.gl_code_basic_pay_trainee) as gl_code_basic_pay_trainee,
                        MAX(p.gl_code_allowances) as gl_code_allowances,
                        MAX(p.gl_code_bm_allowance) as gl_code_bm_allowance,
                        MAX(p.gl_code_overtime_regular) as gl_code_overtime_regular,
                        MAX(p.gl_code_overtime_trainee) as gl_code_overtime_trainee,
                        MAX(p.gl_code_cola) as gl_code_cola,
                        MAX(p.gl_code_sick_leave) as gl_code_sick_leave,
                        MAX(p.gl_code_other_income) as gl_code_other_income,
                        MAX(p.gl_code_salary_adjustment) as gl_code_salary_adjustment,
                        MAX(p.gl_code_graveyard) as gl_code_graveyard,
                        MAX(p.gl_code_late_regular) as gl_code_late_regular,
                        MAX(p.gl_code_late_trainee) as gl_code_late_trainee,
                        MAX(p.gl_code_leave_regular) as gl_code_leave_regular,
                        MAX(p.gl_code_leave_trainee) as gl_code_leave_trainee,
                        MAX(p.gl_code_all_other_deductions) as gl_code_all_other_deductions,
                        MAX(p.gl_code_total) as gl_code_total,
                        p.bos_code,
                        MAX(p.branch_name) as branch_name_hr,
						MAX(mp.branch_name) as branch_name_mlmatic,
                        p.region,
                        MAX(p.basic_pay_regular) as basic_pay_regular,
                        MAX(p.basic_pay_trainee) as basic_pay_trainee,
                        MAX(p.allowances) as allowances,
                        MAX(p.bm_allowance) as bm_allowance,
                        MAX(p.overtime_regular) as overtime_regular,
                        MAX(p.overtime_trainee) as overtime_trainee,
                        MAX(p.cola) as cola,
                        MAX(p.sick_leave) as sick_leave,
                        MAX(p.other_income) as other_income,
                        MAX(p.salary_adjustment) as salary_adjustment,
                        MAX(p.graveyard) as graveyard,
                        MAX(p.late_regular) as late_regular,
                        MAX(p.late_trainee) as late_trainee,
                        MAX(p.leave_regular) as leave_regular,
                        MAX(p.leave_trainee) as leave_trainee,
                        MAX(p.all_other_deductions) as all_other_deductions,
                        MAX(p.total) as total,
                        MAX(p.no_of_branch_employee) as no_of_branch_employee,
                        MAX(p.no_of_employees_allocated) as no_of_employees_allocated,
                        COUNT(DISTINCT bp.code) as branch_count
                    FROM
                        " . $database[0] . ".sick_leave p
                    INNER JOIN 
                        " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code
					INNER JOIN
						" . $database[0] . ".mlmatic_profile mp
					ON
						mp.code = p.bos_code AND mp.mlmatic_region = bp.ml_matic_region AND mp.uploaded_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                    WHERE
                        bp.mainzone = '$mainzone'
                        AND p.zone = '$zone'
                        AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                        AND bp.region_code LIKE '%$region%'";
                        if($restrictedDate === $endDate){
                            $fetchQuery .="AND p.payroll_date = '$restrictedDate'";
                        }else{
                            $fetchQuery .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                        }
                        $fetchQuery .=" AND bp.ml_matic_region != 'LNCR Showroom'
                        AND bp.ml_matic_region != 'VISMIN Showroom'
                        AND p.post_edi = 'pending'
                    GROUP BY 
                        bp.code,
                        p.cost_center,
                        bp.region,
                        p.zone,
                        p.payroll_date,
                        p.bos_code,
                        p.region
                    ORDER BY 
                        bp.region;"; 
        } 
    
        //echo $fetchQuery;
        $result = $conn->query($fetchQuery);

        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {

                $e_payroll_date = $conn->real_escape_string($row['payroll_date']);
                $e_zone = $conn->real_escape_string($row['zone']);
				$e_ml_matic_zone = $conn->real_escape_string($row['mlmatic_zone']);
                $e_region = $conn->real_escape_string($row['region']);
                $e_ml_matic_region = $conn->real_escape_string($row['ml_matic_region']);
                $e_region_code = $conn->real_escape_string($row['region_code']);
                $e_kp_code = $conn->real_escape_string($row['kp_code']);
                $e_ml_matic_status = $conn->real_escape_string($row['ml_matic_status']);
                $e_code = $conn->real_escape_string($row['code']);
				$e_branch_name_mlmatic = $conn->real_escape_string($row['branch_name_mlmatic']);
                $e_branch_name_hr = $conn->real_escape_string($row['branch_name_hr']);
                $e_basic_pay_regular = $conn->real_escape_string($row['basic_pay_regular']);
                $e_gl_code_basic_pay_regular = $conn->real_escape_string($row['gl_code_basic_pay_regular']);
                $e_basic_pay_trainee = $conn->real_escape_string($row['basic_pay_trainee']);
                $e_gl_code_basic_pay_trainee = $conn->real_escape_string($row['gl_code_basic_pay_trainee']);
                $e_allowances = $conn->real_escape_string($row['allowances']);
                $e_gl_code_allowances = $conn->real_escape_string($row['gl_code_allowances']);
                $e_bm_allowance = $conn->real_escape_string($row['bm_allowance']);
                $e_gl_code_bm_allowance = $conn->real_escape_string($row['gl_code_bm_allowance']);
                $e_overtime_regular = $conn->real_escape_string($row['overtime_regular']);
                $e_gl_code_overtime_regular = $conn->real_escape_string($row['gl_code_overtime_regular']);
                $e_overtime_trainee = $conn->real_escape_string($row['overtime_trainee']);
                $e_gl_code_overtime_trainee = $conn->real_escape_string($row['gl_code_overtime_trainee']);
                $e_cola = $conn->real_escape_string($row['cola']);
                $e_gl_code_cola = $conn->real_escape_string($row['gl_code_cola']);
                $e_sick_leave = $conn->real_escape_string($row['sick_leave']);
                $e_gl_code_sick_leave = $conn->real_escape_string($row['gl_code_sick_leave']);
                $e_other_income = $conn->real_escape_string($row['other_income']);
                $e_gl_code_other_income = $conn->real_escape_string($row['gl_code_other_income']);
                $e_salary_adjustment = $conn->real_escape_string($row['salary_adjustment']);
                $e_gl_code_salary_adjustment = $conn->real_escape_string($row['gl_code_salary_adjustment']);
                $e_graveyard = $conn->real_escape_string($row['graveyard']);
                $e_gl_code_graveyard = $conn->real_escape_string($row['gl_code_graveyard']);
                $e_late_regular = $conn->real_escape_string($row['late_regular']);
                $e_gl_code_late_regular = $conn->real_escape_string($row['gl_code_late_regular']);
                $e_late_trainee = $conn->real_escape_string($row['late_trainee']);
                $e_gl_code_late_trainee = $conn->real_escape_string($row['gl_code_late_trainee']);
                $e_leave_regular = $conn->real_escape_string($row['leave_regular']);
                $e_gl_code_leave_regular = $conn->real_escape_string($row['gl_code_leave_regular']);
                $e_leave_trainee = $conn->real_escape_string($row['leave_trainee']);
                $e_gl_code_leave_trainee = $conn->real_escape_string($row['gl_code_leave_trainee']);
                $e_all_other_deductions = $conn->real_escape_string($row['all_other_deductions']);
                $e_gl_code_all_other_deductions = $conn->real_escape_string($row['gl_code_all_other_deductions']);
                $e_total = $conn->real_escape_string($row['total']);
                $e_gl_code_total = $conn->real_escape_string($row['gl_code_total']);
                $e_cost_center = $conn->real_escape_string($row['cost_center1']);
                $e_no_of_branch_employee = $conn->real_escape_string($row['no_of_branch_employee']);
                $e_no_of_employees_allocated = $conn->real_escape_string($row['no_of_employees_allocated']);
                $e_sheet_name = $conn->real_escape_string($row['sheet_name']);

                // Set the time zone to Philippines time.
                // date_default_timezone_set('Asia/Manila');

                $posted_date = date('Y-m-d H:i:s');
                $posted_by = $_SESSION['admin_name'];
            
                $insertQuery = "INSERT INTO " . $database[0] . ".sick_leave_edi_report (payroll_date, mainzone, zone, mlmatic_zone, region, ml_matic_region, region_code, kp_code, ml_matic_status, 
                                branch_code, mlmatic_branch_name, branch_name, basic_pay_regular, gl_code_basic_pay_regular, basic_pay_trainee, gl_code_basic_pay_trainee, allowances, 
                                gl_code_allowances, bm_allowance, gl_code_bm_allowance, overtime_regular, gl_code_overtime_regular, overtime_trainee, 
                                gl_code_overtime_trainee, cola, gl_code_cola, sick_leave, gl_code_sick_leave, other_income, gl_code_other_income, salary_adjustment, 
                                gl_code_salary_adjustment, graveyard, gl_code_graveyard, late_regular, gl_code_late_regular, late_trainee, gl_code_late_trainee, 
                                leave_regular, gl_code_leave_regular, leave_trainee, gl_code_leave_trainee, all_other_deductions, gl_code_all_other_deductions, 
                                total, gl_code_total, cost_center, no_of_branch_employee, no_of_employees_allocated, sheetname, posted_by, posted_date, description) 
                                VALUES ('" . $e_payroll_date . "', '" . $mainzone . "', '" . $e_zone . "', '" . $e_ml_matic_zone . "', '" . $e_region . "', '" . $e_ml_matic_region . "', 
                                '" . $e_region_code . "', '" . $e_kp_code . "', '" . $e_ml_matic_status . "', '" . $e_code . "', 
                                '" . $e_branch_name_mlmatic . "', '" . $e_branch_name_hr . "', '" . $e_basic_pay_regular . "', '" . $e_gl_code_basic_pay_regular . "', '" . $e_basic_pay_trainee . "',
                                '" . $e_gl_code_basic_pay_trainee . "', '" . $e_allowances . "', '" . $e_gl_code_allowances . "', '" . $e_bm_allowance . "',
                                '" . $e_gl_code_bm_allowance . "', '" . $e_overtime_regular . "', '" . $e_gl_code_overtime_regular . "', '" . $e_overtime_trainee . "',
                                '" . $e_gl_code_overtime_trainee . "', '" . $e_cola . "', '" . $e_gl_code_cola . "', '" . $e_sick_leave . "', 
                                '" . $e_gl_code_sick_leave . "', '" . $e_other_income . "', '" . $e_gl_code_other_income . "', '" . $e_salary_adjustment . "',
                                '" . $e_gl_code_salary_adjustment . "', '" . $e_graveyard . "', '" . $e_gl_code_graveyard . "', '" . $e_late_regular . "',
                                '" . $e_gl_code_late_regular . "', '" . $e_late_trainee . "', '" . $e_gl_code_late_trainee . "', '" . $e_leave_regular . "',
                                '" . $e_gl_code_leave_regular . "', '" . $e_leave_trainee . "', '" . $e_gl_code_leave_trainee . "', '" . $e_all_other_deductions . "',
                                '" . $e_gl_code_all_other_deductions . "', '" . $e_total . "', '" . $e_gl_code_total . "', '" . $e_cost_center . "',
                                '" . $e_no_of_branch_employee . "', '" . $e_no_of_employees_allocated . "', '" . $e_sheet_name . "', '" . $posted_by . "',
                                '" . $posted_date . "', 'Sick-Leave')";
                
                // Execute insert query and collect status
                if ($conn->query($insertQuery) !== TRUE) {
                    $errors[] = $conn->error;
                }
            }

            // Check if there were any errors
            if (empty($errors)) {

                if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                    $updatePost = "UPDATE " . $database[0] . ".sick_leave p
                                    INNER JOIN 
                                        " . $database[1] . ".branch_profile bp
                                    ON 
                                        p.bos_code = bp.code AND p.region_code = bp.region_code  
                                    SET post_edi = 'posted'
                                    WHERE 
                                        bp.mainzone = '$mainzone'";
                                    if($restrictedDate === $endDate){
                                        $updatePost .="AND p.payroll_date = '$restrictedDate'";
                                    }else{
                                        $updatePost .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                                    }
                                    $updatePost .="AND bp.ml_matic_region = '$zone'
                                    AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                                    AND bp.zone like '%$region%'";
                                    
                }else{
                    $updatePost = "UPDATE " . $database[0] . ".sick_leave p
                                    INNER JOIN 
                                        " . $database[1] . ".branch_profile bp
                                    ON 
                                        p.bos_code = bp.code AND p.region_code = bp.region_code  
                                    SET post_edi = 'posted' 
                                     WHERE
                                        bp.mainzone = '$mainzone'
                                    AND bp.zone = '$zone'
                                    AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                                    AND bp.region_code LIKE '%$region%'";
                                    if($restrictedDate === $endDate){
                                        $updatePost .="AND p.payroll_date = '$restrictedDate'";
                                    }else{
                                        $updatePost .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                                    }
                                    $updatePost .=" AND bp.ml_matic_region != 'LNCR Showroom'
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

    <center><h2>SICK LEAVE CONVERSION <span style="font-size: 22px; color: red;">[POST EDI]</span></center>

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
                <label for="restricted-date">Start date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
			
			<div class="custom-select-wrapper">
                <label for="end-date">End date </label>
                <input type="date" id="end-date" name="end-date" value="<?php echo isset($_POST['end-date']) ? $_POST['end-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>

        <div id="showdl" style="display: none">
            <button class="post-btn" onclick="postEdi()">Post EDI</button>
        </div>
    </div>
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
		function updateZone(callback) {
			var mainzone = document.getElementById("mainzone").value;
			var selectedZone = document.getElementById("zone").value;

			var xhr = new XMLHttpRequest();
			xhr.open("POST", "get_zone.php", true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4 && xhr.status === 200) {
					document.getElementById("zone").innerHTML = xhr.responseText;

					if (typeof callback === "function") {
						callback(); // Call updateRegions after zones are loaded
					}
				}
			};
			xhr.send("mainzone=" + encodeURIComponent(mainzone) + "&selected_zone=" + encodeURIComponent(selectedZone));
		}

		function updateRegions() {
			var zone = document.getElementById("zone").value;
			var selectedRegion = document.getElementById("region").value;

			var xhr = new XMLHttpRequest();
			xhr.open("POST", "get_regions.php", true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			xhr.onreadystatechange = function () {
				if (xhr.readyState === 4 && xhr.status === 200) {
					document.getElementById("region").innerHTML = xhr.responseText;
				}
			};
			xhr.send("zone=" + encodeURIComponent(zone) + "&selected_region=" + encodeURIComponent(selectedRegion));
		}

		// Ensure region updates when zone changes
		document.addEventListener("DOMContentLoaded", function () {
			document.getElementById("zone").addEventListener("change", updateRegions);

			var mainzone = document.getElementById("mainzone").value;
			var zone = document.getElementById("zone").value;

			if (mainzone !== "") {
				updateZone(function () {
					if (document.getElementById("zone").value !== "") {
						updateRegions();
					}
				});
			} else if (zone !== "") {
				updateRegions();
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
                window.location.href = 'sick_leave_post_edi.php?proceed=true';
            } else {
                window.location.href = 'sick_leave_post_edi.php';
            }
        });
    }
</script>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $zone = $_POST['zone'];
    $restrictedDate = $_POST['restricted-date'];
	$endDate = $_POST['end-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;
	$_SESSION['endDate'] = $endDate;

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
        $sql = "SELECT
                    bp.code,
                    p.cost_center, 
                    bp.region, 
                    bp.zone,
                    p.payroll_date,
                    MAX(bp.cost_center) as cost_center1,
                    MAX(p.gl_code_basic_pay_regular) as gl_code_basic_pay_regular,
                    MAX(p.gl_code_basic_pay_trainee) as gl_code_basic_pay_trainee,
                    MAX(p.gl_code_allowances) as gl_code_allowances,
                    MAX(p.gl_code_bm_allowance) as gl_code_bm_allowance,
                    MAX(p.gl_code_overtime_regular) as gl_code_overtime_regular,
                    MAX(p.gl_code_overtime_trainee) as gl_code_overtime_trainee,
                    MAX(p.gl_code_cola) as gl_code_cola,
                    MAX(p.gl_code_sick_leave) as gl_code_sick_leave,
                    MAX(p.gl_code_other_income) as gl_code_other_income,
                    MAX(p.gl_code_salary_adjustment) as gl_code_salary_adjustment,
                    MAX(p.gl_code_graveyard) as gl_code_graveyard,
                    MAX(p.gl_code_late_regular) as gl_code_late_regular,
                    MAX(p.gl_code_late_trainee) as gl_code_late_trainee,
                    MAX(p.gl_code_leave_regular) as gl_code_leave_regular,
                    MAX(p.gl_code_leave_trainee) as gl_code_leave_trainee,
                    MAX(p.gl_code_all_other_deductions) as gl_code_all_other_deductions,
                    MAX(p.gl_code_total) as gl_code_total,
                    p.bos_code,
                    MAX(p.branch_name) as branch_name_hr,
					MAX(mp.branch_name) as branch_name_mlmatic,
                    p.region,
                    MAX(p.basic_pay_regular) as basic_pay_regular,
                    MAX(p.basic_pay_trainee) as basic_pay_trainee,
                    MAX(p.allowances) as allowances,
                    MAX(p.bm_allowance) as bm_allowance,
                    MAX(p.overtime_regular) as overtime_regular,
                    MAX(p.overtime_trainee) as overtime_trainee,
                    MAX(p.cola) as cola,
                    MAX(p.sick_leave) as sick_leave,
                    MAX(p.other_income) as other_income,
                    MAX(p.salary_adjustment) as salary_adjustment,
                    MAX(p.graveyard) as graveyard,
                    MAX(p.late_regular) as late_regular,
                    MAX(p.late_trainee) as late_trainee,
                    MAX(p.leave_regular) as leave_regular,
                    MAX(p.leave_trainee) as leave_trainee,
                    MAX(p.all_other_deductions) as all_other_deductions,
                    MAX(p.total) as total,
                    MAX(p.no_of_branch_employee) as no_of_branch_employee,
                    MAX(p.no_of_employees_allocated) as no_of_employees_allocated,
                    COUNT(DISTINCT bp.code) as branch_count
                FROM
                    " . $database[0] . ".sick_leave p
                INNER JOIN 
                    " . $database[1] . ".branch_profile bp
                ON 
                    p.bos_code = bp.code AND p.region_code = bp.region_code
				INNER JOIN
                    " . $database[0] . ".mlmatic_profile mp
                ON
                    mp.code = p.bos_code AND mp.mlmatic_region = bp.ml_matic_region AND mp.uploaded_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                WHERE
                    bp.mainzone = '$mainzone'";
                    if($restrictedDate === $endDate){
                        $sql .="AND p.payroll_date = '$restrictedDate'";
                    }else{
                        $sql .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                    }
                    $sql .="AND bp.ml_matic_region = '$zone'
                    AND bp.zone like '%$region%'
                    AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                GROUP BY 
                    bp.code,
                    p.cost_center,
                    bp.region,
                    bp.zone,
                    P.payroll_date,
                    P.bos_code,
                    P.region
                ORDER BY 
                    bp.region;";
    }else{
                $sql = "SELECT
                    bp.code,
                    p.cost_center, 
                    bp.region, 
                    bp.zone,
                    p.payroll_date,
                    MAX(bp.cost_center) as cost_center1,
                    MAX(p.gl_code_basic_pay_regular) as gl_code_basic_pay_regular,
                    MAX(p.gl_code_basic_pay_trainee) as gl_code_basic_pay_trainee,
                    MAX(p.gl_code_allowances) as gl_code_allowances,
                    MAX(p.gl_code_bm_allowance) as gl_code_bm_allowance,
                    MAX(p.gl_code_overtime_regular) as gl_code_overtime_regular,
                    MAX(p.gl_code_overtime_trainee) as gl_code_overtime_trainee,
                    MAX(p.gl_code_cola) as gl_code_cola,
                    MAX(p.gl_code_sick_leave) as gl_code_sick_leave,
                    MAX(p.gl_code_other_income) as gl_code_other_income,
                    MAX(p.gl_code_salary_adjustment) as gl_code_salary_adjustment,
                    MAX(p.gl_code_graveyard) as gl_code_graveyard,
                    MAX(p.gl_code_late_regular) as gl_code_late_regular,
                    MAX(p.gl_code_late_trainee) as gl_code_late_trainee,
                    MAX(p.gl_code_leave_regular) as gl_code_leave_regular,
                    MAX(p.gl_code_leave_trainee) as gl_code_leave_trainee,
                    MAX(p.gl_code_all_other_deductions) as gl_code_all_other_deductions,
                    MAX(p.gl_code_total) as gl_code_total,
                    p.bos_code,
                    MAX(p.branch_name) as branch_name_hr,
					MAX(mp.branch_name) as branch_name_mlmatic,
                    p.region,
                    MAX(p.basic_pay_regular) as basic_pay_regular,
                    MAX(p.basic_pay_trainee) as basic_pay_trainee,
                    MAX(p.allowances) as allowances,
                    MAX(p.bm_allowance) as bm_allowance,
                    MAX(p.overtime_regular) as overtime_regular,
                    MAX(p.overtime_trainee) as overtime_trainee,
                    MAX(p.cola) as cola,
                    MAX(p.sick_leave) as sick_leave,
                    MAX(p.other_income) as other_income,
                    MAX(p.salary_adjustment) as salary_adjustment,
                    MAX(p.graveyard) as graveyard,
                    MAX(p.late_regular) as late_regular,
                    MAX(p.late_trainee) as late_trainee,
                    MAX(p.leave_regular) as leave_regular,
                    MAX(p.leave_trainee) as leave_trainee,
                    MAX(p.all_other_deductions) as all_other_deductions,
                    MAX(p.total) as total,
                    MAX(p.no_of_branch_employee) as no_of_branch_employee,
                    MAX(p.no_of_employees_allocated) as no_of_employees_allocated,
                    COUNT(DISTINCT bp.code) as branch_count
                FROM
                    " . $database[0] . ".sick_leave p
                INNER JOIN 
                    " . $database[1] . ".branch_profile bp
                ON 
                    p.bos_code = bp.code AND p.region_code = bp.region_code
				INNER JOIN
                    " . $database[0] . ".mlmatic_profile mp
                ON
                    mp.code = p.bos_code AND mp.mlmatic_region = bp.ml_matic_region AND mp.uploaded_date BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                WHERE
                    bp.mainzone = '$mainzone'
                    AND bp.zone = '$zone'
                    AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                    AND bp.region_code LIKE '%$region%'";
                    if($restrictedDate === $endDate){
                        $sql .="AND p.payroll_date = '$restrictedDate'";
                    }else{
                        $sql .="AND p.payroll_date BETWEEN '$restrictedDate' AND '$endDate'";
                    }
                    $sql .="AND bp.ml_matic_region != 'LNCR Showroom'
                    AND bp.ml_matic_region != 'VISMIN Showroom'
                GROUP BY 
                    bp.code,
                    p.cost_center,
                    bp.region,
                    bp.zone,
                    P.payroll_date,
                    P.bos_code,
                    P.region
                ORDER BY 
                    bp.region;"; 
    }  
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

         // Check if there are results
         if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            $first_row = mysqli_fetch_assoc($result);

            $payroll_date = htmlspecialchars($first_row['payroll_date']);
            $gl_code_basic_pay_regular = htmlspecialchars($first_row['gl_code_basic_pay_regular']);
            $gl_code_basic_pay_trainee = htmlspecialchars($first_row['gl_code_basic_pay_trainee']);
            $gl_code_allowances = htmlspecialchars($first_row['gl_code_allowances']);
            $gl_code_bm_allowance = htmlspecialchars($first_row['gl_code_bm_allowance']);
            $gl_code_overtime_regular = htmlspecialchars($first_row['gl_code_overtime_regular']);
            $gl_code_overtime_trainee = htmlspecialchars($first_row['gl_code_overtime_trainee']);
            $gl_code_cola = htmlspecialchars($first_row['gl_code_cola']);
            $gl_code_sick_leave = htmlspecialchars($first_row['gl_code_sick_leave']);
            $gl_code_other_income = htmlspecialchars($first_row['gl_code_other_income']);
            $gl_code_salary_adjustment = htmlspecialchars($first_row['gl_code_salary_adjustment']);
            $gl_code_graveyard = htmlspecialchars($first_row['gl_code_graveyard']);
            $gl_code_late_regular = htmlspecialchars($first_row['gl_code_late_regular']);
            $gl_code_late_trainee = htmlspecialchars($first_row['gl_code_late_trainee']);
            $gl_code_leave_regular = htmlspecialchars($first_row['gl_code_leave_regular']);
            $gl_code_leave_trainee = htmlspecialchars($first_row['gl_code_leave_trainee']);
            $gl_code_all_other_deductions = htmlspecialchars($first_row['gl_code_all_other_deductions']);
            $gl_code_total = htmlspecialchars($first_row['gl_code_total']);


            //  first row
            echo "<tr>";
            echo "<th colspan='3'>Date Range : " . $payroll_date . " TO ".$endDate. "</th>";
            echo "<th>Basic Pay Regular</th>";
            echo "<th>Basic Pay Trainee</th>";
            echo "<th>Allowances</th>";
            echo "<th>BM Allowance</th>";
            echo "<th>Overtime Regular</th>";
            echo "<th>Overtime Trainee</th>";
            echo "<th>COLA</th>";
            echo "<th>Sick Leave</th>";
            echo "<th>Other Income</th>";
            echo "<th>Salary Adjustment</th>";
            echo "<th>Graveyard</th>";
            echo "<th>Late Regular</th>";
            echo "<th>Late Trainee</th>";
            echo "<th>Leave Regular</th>";
            echo "<th>Leave Trainee</th>";
            echo "<th>Total</th>";
            echo "<th>Cost Center</th>";
            echo "<th style='width: 10px;'></th>";
            echo "<th>Region</th>";
            echo "<th>All Other Deductions</th>";
            echo "<th>No. of Branch Employees</th>";
            echo "<th>No. of Employees Allocated</th>";
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th></th>";
			echo "<th></th>";
            echo "<th></th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            //third row
            echo "<tr>";
            echo "<th style='white-space: nowrap'>BOS Code</th>";
            echo "<th>Branch Name from HRMD DATA</th>";
			echo "<th>Branch Name from MLMATIC DATA</th>";
            echo "<th>". $gl_code_basic_pay_regular ."</th>";
            echo "<th>". $gl_code_basic_pay_trainee ."</th>";
            echo "<th>". $gl_code_allowances ."</th>";
            echo "<th>". $gl_code_bm_allowance ."</th>";
            echo "<th>". $gl_code_overtime_regular ."</th>";
            echo "<th>". $gl_code_overtime_trainee ."</th>";
            echo "<th>". $gl_code_cola ."</th>";
            echo "<th>". $gl_code_sick_leave ."</th>";
            echo "<th>". $gl_code_other_income ."</th>";
            echo "<th>". $gl_code_salary_adjustment ."</th>";
            echo "<th>". $gl_code_graveyard ."</th>";
            echo "<th>". $gl_code_late_regular ."</th>";
            echo "<th>". $gl_code_late_trainee ."</th>";
            echo "<th>". $gl_code_leave_regular ."</th>";
            echo "<th>". $gl_code_leave_trainee ."</th>";
            echo "<th>". $gl_code_total ."</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th>". $gl_code_all_other_deductions ."</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalNumberOfBranches = 0;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;

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
                $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                            + $row['overtime_trainee'] + $row['cola'] + $row['sick_leave'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                $total = $totalDebit - $totalCredit;

                echo "<tr>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['bos_code']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name_hr']) . "</td>";
				echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name_mlmatic']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['allowances']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['bm_allowance']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cola']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['sick_leave']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['other_income']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['salary_adjustment']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['graveyard']) . "</td>";
                // convert to negative if positive value 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']) . "</td>";

                echo "<td style='background-color: $color; font-weight: $bold'> $total </td>"; 
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center1']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['all_other_deductions']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_branch_employee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_employees_allocated']) . "</td>";
                echo "</tr>";
            }
 
            echo "</tbody>";
            echo "</table>";
            echo "</div>";

            echo "<script>
            
            var dlbutton = document.getElementById('showdl');
            dlbutton.style.display = 'block';
            
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