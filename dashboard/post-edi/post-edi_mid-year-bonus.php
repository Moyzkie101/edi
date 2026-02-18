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
        header('Location: post-edi_mid-year-bonus.php');
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
                    FROM " . $database[0] . ".payroll p
                    INNER JOIN " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code 
                    WHERE 
                        bp.mainzone = '$mainzone'
                        AND p.payroll_date = '$restrictedDate'
                        AND bp.ml_matic_region = '$zone'
                        AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                        AND p.zone like '%$region%'
                        and p.description = 'midYearBonus'";
        }else{
            $sql = "SELECT post_edi 
                    FROM " . $database[0] . ".payroll p
                    INNER JOIN " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code 
                    WHERE 
                        bp.mainzone = '$mainzone'
                    AND p.zone = '$zone'
                    AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                    AND bp.region_code LIKE '%$region%'
                    AND p.payroll_date = '$restrictedDate'
                    AND bp.ml_matic_region != 'LNCR Showroom'
                    AND bp.ml_matic_region != 'VISMIN Showroom'
                    and p.description = 'midYearBonus'";
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
    function insertData($conn, $database, $mainzone, $zone, $region, $restrictedDate) {
        $errors = [];

        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
            $fetchQuery = "SELECT
                        bp.code,
                        p.cost_center, 
                        bp.region, 
                        p.zone,
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
                        MAX(p.gl_code_excess_pb) as gl_code_excess_pb,
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
                        MAX(p.branch_name) as branch_name,
                        p.region,
                        MAX(p.basic_pay_regular) as basic_pay_regular,
                        MAX(p.basic_pay_trainee) as basic_pay_trainee,
                        MAX(p.allowances) as allowances,
                        MAX(p.bm_allowance) as bm_allowance,
                        MAX(p.overtime_regular) as overtime_regular,
                        MAX(p.overtime_trainee) as overtime_trainee,
                        MAX(p.cola) as cola,
                        MAX(p.excess_pb) as excess_pb,
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
                        " . $database[0] . ".payroll p
                    INNER JOIN 
                        " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code
                    WHERE
                        bp.mainzone = '$mainzone'
                        AND p.payroll_date = '$restrictedDate'
                        AND bp.ml_matic_region = '$zone'
                        AND p.zone like '%$region%'
                        AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                        AND p.post_edi = 'pending'
                        AND p.description = 'midYearBonus'
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
                        MAX(p.gl_code_excess_pb) as gl_code_excess_pb,
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
                        MAX(p.branch_name) as branch_name,
                        p.region,
                        MAX(p.basic_pay_regular) as basic_pay_regular,
                        MAX(p.basic_pay_trainee) as basic_pay_trainee,
                        MAX(p.allowances) as allowances,
                        MAX(p.bm_allowance) as bm_allowance,
                        MAX(p.overtime_regular) as overtime_regular,
                        MAX(p.overtime_trainee) as overtime_trainee,
                        MAX(p.cola) as cola,
                        MAX(p.excess_pb) as excess_pb,
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
                        " . $database[0] . ".payroll p
                    INNER JOIN 
                        " . $database[1] . ".branch_profile bp
                    ON 
                        p.bos_code = bp.code AND p.region_code = bp.region_code
                    WHERE
                        bp.mainzone = '$mainzone'
                        AND p.zone = '$zone'
                        AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                        AND bp.region_code LIKE '%$region%'
                        AND p.payroll_date = '$restrictedDate'
                        AND bp.ml_matic_region != 'LNCR Showroom'
                        AND bp.ml_matic_region != 'VISMIN Showroom'
                        AND p.post_edi = 'pending'
                        AND p.description = 'midYearBonus'
                    GROUP BY 
                        bp.code,
                        p.cost_center,
                        bp.region,
                        p.zone,
                        P.payroll_date,
                        P.bos_code,
                        P.region
                    ORDER BY 
                        bp.region;"; 
        } 
    
        //echo $fetchQuery;
        $result = $conn->query($fetchQuery);

        if ($result->num_rows > 0) {
            
            while ($row = $result->fetch_assoc()) {

                $e_payroll_date = $conn->real_escape_string($row['payroll_date']);
                $e_zone = $conn->real_escape_string($row['zone']);
                $e_region = $conn->real_escape_string($row['region']);
                $e_ml_matic_region = $conn->real_escape_string($row['ml_matic_region']);
                $e_region_code = $conn->real_escape_string($row['region_code']);
                $e_kp_code = $conn->real_escape_string($row['kp_code']);
                $e_ml_matic_status = $conn->real_escape_string($row['ml_matic_status']);
                $e_code = $conn->real_escape_string($row['code']);
                $e_branch_name = $conn->real_escape_string($row['branch_name']);
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
                $e_excess_pb = $conn->real_escape_string($row['excess_pb']);
                $e_gl_code_excess_pb = $conn->real_escape_string($row['gl_code_excess_pb']);
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
                $posted_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown User';
            
                $insertQuery = "INSERT INTO " . $database[0] . ".payroll_edi_report (
                    payroll_date, mainzone, zone, region, ml_matic_region, region_code, kp_code, ml_matic_status, 
                    branch_code, branch_name, basic_pay_regular, gl_code_basic_pay_regular, basic_pay_trainee, gl_code_basic_pay_trainee, allowances, 
                    gl_code_allowances, bm_allowance, gl_code_bm_allowance, overtime_regular, gl_code_overtime_regular, overtime_trainee, 
                    gl_code_overtime_trainee, cola, gl_code_cola, excess_pb, gl_code_excess_pb, other_income, gl_code_other_income, salary_adjustment, 
                    gl_code_salary_adjustment, graveyard, gl_code_graveyard, late_regular, gl_code_late_regular, late_trainee, gl_code_late_trainee, 
                    leave_regular, gl_code_leave_regular, leave_trainee, gl_code_leave_trainee, all_other_deductions, gl_code_all_other_deductions, 
                    total, gl_code_total, cost_center, no_of_branch_employee, no_of_employees_allocated, sheetname, posted_by, posted_date, description
                ) VALUES (
                    '$e_payroll_date', '$mainzone', '$e_zone', '$e_region', '$e_ml_matic_region', 
                    '$e_region_code', '$e_kp_code', '$e_ml_matic_status', '$e_code', 
                    '$e_branch_name', '$e_basic_pay_regular', '$e_gl_code_basic_pay_regular', '$e_basic_pay_trainee',
                    '$e_gl_code_basic_pay_trainee', '$e_allowances', '$e_gl_code_allowances', '$e_bm_allowance',
                    '$e_gl_code_bm_allowance', '$e_overtime_regular', '$e_gl_code_overtime_regular', '$e_overtime_trainee',
                    '$e_gl_code_overtime_trainee', '$e_cola', '$e_gl_code_cola', '$e_excess_pb', 
                    '$e_gl_code_excess_pb', '$e_other_income', '$e_gl_code_other_income', '$e_salary_adjustment',
                    '$e_gl_code_salary_adjustment', '$e_graveyard', '$e_gl_code_graveyard', '$e_late_regular',
                    '$e_gl_code_late_regular', '$e_late_trainee', '$e_gl_code_late_trainee', '$e_leave_regular',
                    '$e_gl_code_leave_regular', '$e_leave_trainee', '$e_gl_code_leave_trainee', '$e_all_other_deductions',
                    '$e_gl_code_all_other_deductions', '$e_total', '$e_gl_code_total', '$e_cost_center',
                    '$e_no_of_branch_employee', '$e_no_of_employees_allocated', '$e_sheet_name', '$posted_by',
                    '$posted_date', 'midYearBonus'
                )";

                // Execute insert query and collect status
                if ($conn->query($insertQuery) !== TRUE) {
                    $errors[] = $conn->error . " | Query: " . $insertQuery;
                }
            }

            // Check if there were any errors
            if (empty($errors)) {

                if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                    $updatePost = "UPDATE " . $database[0] . ".payroll p
                                    INNER JOIN 
                                        " . $database[1] . ".branch_profile bp
                                    ON 
                                        p.bos_code = bp.code AND p.region_code = bp.region_code  
                                    SET post_edi = 'posted'
                                    WHERE 
                                        bp.mainzone = '$mainzone'
                                    AND p.payroll_date = '$restrictedDate'
                                    AND bp.ml_matic_region = '$zone'
                                    AND NOT (bp.code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                                    AND bp.zone like '%$region%'
                                    and p.description = 'midYearBonus'";
                                    
                }else{
                    $updatePost = "UPDATE " . $database[0] . ".payroll p
                                    INNER JOIN 
                                        " . $database[1] . ".branch_profile bp
                                    ON 
                                        p.bos_code = bp.code AND p.region_code = bp.region_code  
                                    SET post_edi = 'posted' 
                                    WHERE
                                        bp.mainzone = '$mainzone'
                                    AND bp.zone = '$zone'
                                    AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                                    AND bp.region_code LIKE '%$region%'
                                    AND p.payroll_date = '$restrictedDate'
                                    AND bp.ml_matic_region != 'LNCR Showroom'
                                    AND bp.ml_matic_region != 'VISMIN Showroom'
                                    and p.description = 'midYearBonus'";
                }

                if ($conn->query($updatePost) === TRUE) {
                    return true;  // Success
                } else {
                    $errors[] = $conn->error . " | Query: " . $updatePost;
                }

            } else {
                echo "Error inserting records: " . implode(', ', $errors);
            }

        } else {
            echo "No records found to insert. Query: " . $fetchQuery;
            return false;
        }

        // If there were any errors, return false
        return empty($errors);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {
        $mainzone = $_POST['mainzone'];
        $region = $_POST['region'];
        $zone = $_POST['zone'];

        $restrictedDate = $_POST['restricted-date'];

        $payroll_date_format = date('F j, Y', strtotime($restrictedDate));

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
                    MAX(p.gl_code_excess_pb) as gl_code_excess_pb,
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
                    MAX(p.branch_name) as branch_name,
                    p.region,
                    MAX(p.basic_pay_regular) as basic_pay_regular,
                    MAX(p.basic_pay_trainee) as basic_pay_trainee,
                    MAX(p.allowances) as allowances,
                    MAX(p.bm_allowance) as bm_allowance,
                    MAX(p.overtime_regular) as overtime_regular,
                    MAX(p.overtime_trainee) as overtime_trainee,
                    MAX(p.cola) as cola,
                    MAX(p.excess_pb) as excess_pb,
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
                    " . $database[0] . ".payroll p
                INNER JOIN 
                    " . $database[1] . ".branch_profile bp
                ON 
                    p.bos_code = bp.code AND p.region_code = bp.region_code
                WHERE
                    bp.mainzone = '$mainzone'
                    AND p.payroll_date = '$restrictedDate'
                    and p.description = 'midYearBonus'";
                    
                    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                        $sql .= " AND bp.ml_matic_region = '$zone'
                                AND bp.zone LIKE '%$region%'
                                AND NOT (bp.code = 18 AND p.zone = 'VIS')";
                    } else {
                        $sql .= " AND bp.zone = '$zone'
                                AND p.zone != 'JVIS'
                                AND bp.region_code LIKE '%$region%'
                                AND NOT bp.ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom')";
                    }
                    
                $sql .= " GROUP BY 
                    bp.code,
                    p.cost_center,
                    bp.region,
                    bp.zone,
                    p.payroll_date,
                    p.bos_code,
                    p.region
                ORDER BY 
                    bp.region";
        
        // Store the SQL query in session
        $_SESSION['sql_query'] = $sql;
        $_SESSION['payroll_date'] = $payroll_date_format;

        // Save to session for GET requests (Post EDI)
        $_SESSION['mainzone'] = $mainzone;
        $_SESSION['region'] = $region;
        $_SESSION['zone'] = $zone;
        $_SESSION['restrictedDate'] = $restrictedDate;

        // Add error checking for the query
        $result = $conn->query($sql);
        if (!$result) {
            echo "Error in SQL query: " . $conn->error;
            echo "<br>Query: " . $sql;
            exit;
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

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

    <center><h2>POST EDI <span>[Mid Year Bonus]</span></center>

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
                <label for="restricted-date">Mid Year date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>

        <div id="showdl" style="display: none">
            <button class="post-btn" onclick="postEdi()">Post EDI</button>
        </div>
    </div>

    <?php
    
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) :
            $sql_query = $_SESSION['sql_query']; // Retrieve the SQL query from session
            $result = $conn->query($sql_query);
            $first_row = mysqli_fetch_assoc($result);
            
            $payroll_date = isset($_SESSION['payroll_date']) ? $_SESSION['payroll_date'] : '';
            $gl_code_basic_pay_regular = htmlspecialchars($first_row['gl_code_basic_pay_regular']);
            $gl_code_basic_pay_trainee = htmlspecialchars($first_row['gl_code_basic_pay_trainee']);
            $gl_code_allowances = htmlspecialchars($first_row['gl_code_allowances']);
            $gl_code_bm_allowance = htmlspecialchars($first_row['gl_code_bm_allowance']);
            $gl_code_overtime_regular = htmlspecialchars($first_row['gl_code_overtime_regular']);
            $gl_code_overtime_trainee = htmlspecialchars($first_row['gl_code_overtime_trainee']);
            $gl_code_cola = htmlspecialchars($first_row['gl_code_cola']);
            $gl_code_excess_pb = htmlspecialchars($first_row['gl_code_excess_pb']);
            $gl_code_other_income = htmlspecialchars($first_row['gl_code_other_income']);
            $gl_code_salary_adjustment = htmlspecialchars($first_row['gl_code_salary_adjustment']);
            $gl_code_graveyard = htmlspecialchars($first_row['gl_code_graveyard']);
            $gl_code_late_regular = htmlspecialchars($first_row['gl_code_late_regular']);
            $gl_code_late_trainee = htmlspecialchars($first_row['gl_code_late_trainee']);
            $gl_code_leave_regular = htmlspecialchars($first_row['gl_code_leave_regular']);
            $gl_code_leave_trainee = htmlspecialchars($first_row['gl_code_leave_trainee']);
            $gl_code_all_other_deductions = htmlspecialchars($first_row['gl_code_all_other_deductions']);
            $gl_code_total = htmlspecialchars($first_row['gl_code_total']);

            $totalNumberOfBranches = 0;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;

    ?>

    <div class="table-container">
        <table>
            <thead>
                <!-- first row -->
                <tr>
                    <th colspan='2'>Mid Year Date - <?php echo $payroll_date; ?></th>
                    <th>Basic Pay Regular</th>
                    <th>Basic Pay Trainee</th>
                    <th>Allowances</th>
                    <th>BM Allowance</th>
                    <th>Overtime Regular</th>
                    <th>Overtime Trainee</th>
                    <th>COLA</th>
                    <th>Excess PB</th>
                    <th>Other Income</th>
                    <th>Salary Adjustment</th>
                    <th>Graveyard</th>
                    <th>Late Regular</th>
                    <th>Late Trainee</th>
                    <th>Leave Regular</th>
                    <th>Leave Trainee</th>
                    <th>Total</th>
                    <th>Cost Center</th>
                    <th style='width: 10px;'></th>
                    <th>Region</th>
                    <th>All Other Deductions</th>
                    <th>No. of Branch Employees</th>
                    <th>No. of Employees Allocated</th>
                </tr>
                <!-- second row -->
                <tr>
                    <th></th>
                    <th></th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Credit</th>
                    <th>Credit</th>
                    <th>Credit</th>
                    <th>Credit</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                <!-- third row -->
                <tr>
                    <th style='white-space: nowrap'>BOS Code</th>
                    <th>Branch Name</th>
                    <th><?php echo "$gl_code_basic_pay_regular";?></th>
                    <th><?php echo "$gl_code_basic_pay_trainee";?></th>
                    <th><?php echo "$gl_code_allowances";?></th>
                    <th><?php echo "$gl_code_bm_allowance";?></th>
                    <th><?php echo "$gl_code_overtime_regular";?></th>
                    <th><?php echo "$gl_code_overtime_trainee";?></th>
                    <th><?php echo "$gl_code_cola";?></th>
                    <th><?php echo "$gl_code_excess_pb";?></th>
                    <th><?php echo "$gl_code_other_income";?></th>
                    <th><?php echo "$gl_code_salary_adjustment";?></th>
                    <th><?php echo "$gl_code_graveyard";?></th>
                    <th><?php echo "$gl_code_late_regular";?></th>
                    <th><?php echo "$gl_code_late_trainee";?></th>
                    <th><?php echo "$gl_code_leave_regular";?></th>
                    <th><?php echo "$gl_code_leave_trainee";?></th>
                    <th><?php echo "$gl_code_total";?></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th><?php echo "$gl_code_all_other_deductions";?></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php

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
                            + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                $total = $totalDebit - $totalCredit;

                echo "<tr> <td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['bos_code']) . "</td> 
                <td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_regular']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_trainee']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['allowances']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['bm_allowance']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_regular']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_trainee']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cola']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['excess_pb']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['other_income']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['salary_adjustment']) . "</td> 
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['graveyard']) . "</td>
                <!--convert to negative if positive value -->
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']) . "</td>
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']) . "</td>
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']) . "</td>
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']) . "</td>

                <td style='background-color: $color; font-weight: $bold'> $total </td> 
                <td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center1']) . "</td>
                <td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>
                <td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['all_other_deductions']) . "</td>
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_branch_employee']) . "</td>
                <td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_employees_allocated']) . "</td>
                </tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
    <script>
        var dlbutton = document.getElementById('showdl');
        dlbutton.style.display = 'block';
    </script>
    <?php endif; ?>
    <script>
        //for fetching zone
        function updateZone() {
            var mainzone = document.getElementById("mainzone").value;
            var selectedZone = document.getElementById("zone").value; // Get the currently selected zone, if any
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../../fetch/get_zone.php", true);
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
            xhr.open("POST", "../../fetch/get_regions.php", true);
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
                    window.location.href = 'post-edi_mid-year-bonus.php?proceed=true';
                } else {
                    window.location.href = 'post-edi_mid-year-bonus.php';
                }
            });
        }
    </script>

</body>
</html>