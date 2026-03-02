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
                    // Handle HRMD role - no access to this page
                    break;
                case 'CAD':
                    // Handle CAD role - no access to this page
                    break;
                case 'ML FUND':
                    // Handle ML FUND role - no access to this page
                    break;
                case 'KP DOMESTIC':
                    // Handle KP DOMESTIC role - allow access to this page
                    $hasRequiredRole = true;
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

    //Fix the all information comes from branch profile dropdown query to get distinct values
    // mainzone
    $dataQuery = "SELECT DISTINCT mainzone FROM masterdata.branch_profile WHERE NOT mainzone IN ('HO') GROUP BY mainzone ORDER BY mainzone";
    $mainzone_result = $conn->query($dataQuery);

    // zone - Modified to be populated via AJAX based on mainzone selection
    $zone_result = null; // Will be populated via AJAX

    // Add AJAX handler for fetching zone data based on mainzone
    if (isset($_POST['action']) && $_POST['action'] === 'get_zones') {
        $mainzone = $_POST['mainzone'];
        
        $zoneQuery = "SELECT 
                        mzm.zone_code
                    FROM masterdata.main_zone_masterfile AS mmzm
                    JOIN masterdata.zone_masterfile AS mzm
                        ON mmzm.main_zone_code = mzm.main_zone_code
                    AND mzm.zone_code NOT IN (
                            'HO',
                            'JEW',
                            'VISMIN-MANCOMM',
                            'LNCR-MANCOMM',
                            'VISMIN-SUPPORT',
                            'LNCR-SUPPORT'
                    )
                    AND mmzm.main_zone_code NOT IN ('JEW', 'HO')";
                    
        if($mainzone !== 'ALL'){
            $zoneQuery .= " WHERE mmzm.main_zone_code = ?";
            $stmt = $conn->prepare($zoneQuery);
            $stmt->bind_param("s", $mainzone);
        } else {
            $stmt = $conn->prepare($zoneQuery);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $options = '';
        while ($row = $result->fetch_assoc()) {
            $options .= '<option value="' . $row['zone_code'] . '">' . $row['zone_code'] . '</option>';
        }
        
        echo $options;
        exit; // Stop execution for AJAX response
    }
    if (isset($_POST['action']) && $_POST['action'] === 'get_regions'){
        $mainzone = $_POST['mainzone'];
        $zone = $_POST['zone'];
        
        $regionQuery = "SELECT 
                        mrm.region_code,
                        mrm.region_description
                    FROM masterdata.main_zone_masterfile AS mmzm
                    JOIN masterdata.zone_masterfile AS mzm
                        ON mmzm.main_zone_code = mzm.main_zone_code
                    AND mzm.zone_code NOT IN (
                            'VISMIN-MANCOMM',
                            'LNCR-MANCOMM',
                            'VISMIN-SUPPORT',
                            'LNCR-SUPPORT'
                        )
                    AND mmzm.main_zone_code NOT IN (
                            'JEW', 'HO'
                        )
                    JOIN masterdata.region_masterfile AS mrm
                        ON mzm.zone_code = mrm.zone_code";
                        
                    if($mainzone !== 'ALL'){ //(LNCR, VISMIN)
                        if($zone !== 'ALL'){ // (LZN,NCR,VIS,MIN,SHOWROOM), (LNCR, VISMIN)
                            if ($zone !== 'Showroom') { // (LZN,NCR,VIS,MIN), (LNCR, VISMIN)
                                $regionQuery .= " WHERE mmzm.main_zone_code = ? AND mzm.zone_code = ?";
                            }
                        }else{
                            $regionQuery .= " WHERE mmzm.main_zone_code = ?";
                        }
                    }else{ // (ALL)
                        if($zone !== 'ALL'){ // (LZN,NCR,VIS,MIN,SHOWROOM), (ALL)
                            if ($zone !== 'Showroom') { // (LZN,NCR,VIS,MIN), (ALL)
                                $regionQuery .= " WHERE mzm.zone_code = ?";
                            }
                        }
                    }
        
        $stmt = $conn->prepare($regionQuery);

        if($mainzone !== 'ALL'){ //(LNCR, VISMIN)
            if($zone !== 'ALL'){ // (LZN,NCR,VIS,MIN,SHOWROOM), (LNCR, VISMIN)
                if ($zone !== 'Showroom') { // (LZN,NCR,VIS,MIN), (LNCR, VISMIN)
                    $stmt->bind_param("ss", $mainzone, $zone);
                }
            }else{
                $stmt->bind_param("s", $mainzone);
            }
        }else{ // (ALL)
            if($zone !== 'ALL'){ // (LZN,NCR,VIS,MIN,SHOWROOM), (ALL)
                if ($zone !== 'Showroom') { // (LZN,NCR,VIS,MIN), (ALL)
                    $stmt->bind_param("s", $zone);
                }
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $options2 = '';
        while ($row = $result->fetch_assoc()) {
            $options2 .= '<option value="' . $row['region_code'] . '">' . $row['region_description'] . '</option>';
        }
        
        echo $options2;
        exit;
    }

    // AJAX handler to fetch branches based on selected filters
    if (isset($_POST['action']) && $_POST['action'] === 'get_branches') {
        $startDate = $_POST['startdate'] ?? '';
        $endDate = $_POST['enddate'] ?? '';
        $mainzone = $_POST['mainzone'] ?? '';
        $zone = $_POST['zone'] ?? '';
        $region = $_POST['region'] ?? '';

        $query = "SELECT DISTINCT branch_name FROM edi.payroll_edi_report";
        $conditions = [];
        $params = [];
        $types = '';

        if (!empty($startDate) && !empty($endDate)) {
            if ($startDate === $endDate) {
                $conditions[] = "payroll_date = ?";
                $types .= 's';
                $params[] = $startDate;
            } else {
                $conditions[] = "payroll_date BETWEEN ? AND ?";
                $types .= 'ss';
                $params[] = $startDate;
                $params[] = $endDate;
            }
        }

        if (!empty($mainzone) && $mainzone !== 'ALL') {
            $conditions[] = "mainzone = ?";
            $types .= 's';
            $params[] = $mainzone;
        }

        if (!empty($zone) && $zone !== 'ALL' && $zone !== 'Showroom') {
            $conditions[] = "zone = ?";
            $types .= 's';
            $params[] = $zone;
        }

        if (!empty($region) && $region !== 'ALL') {
            if($region === 'LZN' || $region === 'NCR'){
                    if ($zone === 'Showroom') {
                        $conditions[] = "zone = ? AND ml_matic_region = 'LNCR $zone'";
                        $types .= 's';
                        $params[] = $region;
                    }else{
                        $conditions[] = "zone = ? AND ml_matic_region = 'LNCR Showroom'";
                        $types .= 's';
                        $params[] = $region;
                    }
            }elseif($region === 'VIS' || $region === 'MIN'){
                    if ($zone === 'Showroom') {
                        $conditions[] = "zone = ? AND ml_matic_region = 'VISMIN $zone'";
                        $types .= 's';
                        $params[] = $region;
                    }else{
                        $conditions[] = "zone = ? AND ml_matic_region = 'VISMIN Showroom'";
                        $types .= 's';
                        $params[] = $region;
                    }
            }else{
                $conditions[] = "region_code = ?";
                $types .= 's';
                $params[] = $region;
            }
        }

        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY branch_name';

        $stmt = $conn->prepare($query);
        if ($stmt) {
            if (!empty($params)) {
                $bind_names[] = $types;
                for ($i = 0; $i < count($params); $i++) {
                    $bind_name = 'bind' . $i;
                    $$bind_name = $params[$i];
                    $bind_names[] = &$$bind_name;
                }
                call_user_func_array(array($stmt, 'bind_param'), $bind_names);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            $options = '';
            while ($r = $res->fetch_assoc()) {
                $options .= '<option value="' . htmlspecialchars($r['branch_name']) . '">' . htmlspecialchars($r['branch_name']) . '</option>';
            }
            echo $options;
            exit;
        } else {
            echo '';
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'get_data_results') {
        $mainzone = $_POST['mainzone'] ?? '';
        $zone = $_POST['zone'] ?? '';
        $region = $_POST['region'] ?? '';
        $branch = $_POST['branch'] ?? '';
        $status = $_POST['status'] ?? '';
        $startDate = $_POST['startdate'] ?? '';
        $endDate = $_POST['enddate'] ?? '';

        $_SESSION['mainzone'] = $mainzone;
        $_SESSION['zone'] = $zone;
        $_SESSION['region'] = $region;
        $_SESSION['branch'] = $branch;
        $_SESSION['status'] = $status;
        $_SESSION['startdate'] = $startDate;
        $_SESSION['enddate'] = $endDate;

        $dataResultSql = "
            SELECT
                per.payroll_date, 
                per.mainzone, 
                per.zone, 
                per.region, 
                per.ml_matic_region, 
                per.region_code, 
                per.kp_code, 
                per.ml_matic_status, 
                per.branch_code, 
                per.branch_name, 

                SUM(per.basic_pay_regular) AS basic_pay_regular, 
                per.gl_code_basic_pay_regular, 

                SUM(per.basic_pay_trainee) AS basic_pay_trainee, 
                per.gl_code_basic_pay_trainee,

                SUM(per.allowances) AS allowances, 
                per.gl_code_allowances, 

                SUM(per.bm_allowance) AS bm_allowance, 
                per.gl_code_bm_allowance, 

                SUM(per.overtime_regular) AS overtime_regular, 
                per.gl_code_overtime_regular, 

                SUM(per.overtime_trainee) AS overtime_trainee, 
                per.gl_code_overtime_trainee, 

                SUM(per.cola) AS cola, 
                per.gl_code_cola, 

                SUM(per.excess_pb) AS excess_pb, 
                per.gl_code_excess_pb, 

                SUM(per.other_income) AS other_income, 
                per.gl_code_other_income, 

                SUM(per.salary_adjustment) AS salary_adjustment, 
                per.gl_code_salary_adjustment, 

                SUM(per.graveyard) AS graveyard, 
                per.gl_code_graveyard, 

                SUM(per.late_regular) AS late_regular, 
                per.gl_code_late_regular, 

                SUM(per.late_trainee) AS late_trainee, 
                per.gl_code_late_trainee, 

                SUM(per.leave_regular) AS leave_regular, 
                per.gl_code_leave_regular, 

                SUM(per.leave_trainee) AS leave_trainee, 
                per.gl_code_leave_trainee, 

                SUM(per.all_other_deductions) AS all_other_deductions, 
                per.gl_code_all_other_deductions, 

                SUM(per.total) AS total, 
                per.gl_code_total, 

                per.cost_center, 
                SUM(per.no_of_branch_employee) AS no_of_branch_employee, 
                SUM(per.no_of_employees_allocated) AS no_of_employees_allocated

            FROM " . $database[0] . ".payroll_edi_report per 
            WHERE ";
            if ($startDate === $endDate) {
                $dataResultSql .= "per.payroll_date = '$startDate' ";
            } else {
                $dataResultSql .= "per.payroll_date BETWEEN '$startDate' AND '$endDate' ";
            }

            if ($mainzone !== 'ALL') {
                $dataResultSql .= "AND per.mainzone = '$mainzone' ";
            }

            if ($zone !== 'ALL') {
                if ($zone !== 'Showroom') {
                    $dataResultSql .= "AND per.zone = '$zone' ";
                }
            }

            if ($region !== 'ALL') {
                if($region === 'LZN' || $region === 'NCR'){
                    if ($zone === 'Showroom') {
                        $dataResultSql .= "AND per.zone = '$zone' AND per.ml_matic_region = 'LNCR $zone' ";
                    } else {
                        $dataResultSql .= "AND per.zone = '$region' AND per.ml_matic_region = 'LNCR Showroom' ";
                    }
                }elseif($region === 'VIS' || $region === 'MIN'){
                    if ($zone === 'Showroom') {
                        $dataResultSql .= "AND per.zone = '$zone' AND per.ml_matic_region = 'VISMIN $zone' ";
                    } else {
                        $dataResultSql .= "AND per.zone = '$region' AND per.ml_matic_region = 'VISMIN Showroom' ";
                    }
                }else{
                    $dataResultSql .= "AND per.region_code = '$region' ";
                }
            }

            if ($branch !== 'ALL' && !empty($branch)) {
                // branch dropdown returns branch name for many cases; detect numeric branch code vs name
                if (ctype_digit($branch)) {
                    $dataResultSql .= " AND per.branch_code = " . intval($branch);
                } else {
                    $escaped_branch = mysqli_real_escape_string($conn, $branch);
                    $dataResultSql .= " AND per.branch_name = '" . $escaped_branch . "'";
                }
            }

            if ($status !== 'ALL') {
                $dataResultSql .= " AND per.ml_matic_status = '$status' ";
            }
                
            $dataResultSql .= " GROUP BY 
                    per.region_code, per.mainzone, per.zone, per.payroll_date,
                    per.region, per.ml_matic_region, per.kp_code, per.ml_matic_status,
                    per.branch_code, per.branch_name,
                    per.gl_code_basic_pay_regular, per.gl_code_basic_pay_trainee,
                    per.gl_code_allowances, per.gl_code_bm_allowance,
                    per.gl_code_overtime_regular, per.gl_code_overtime_trainee,
                    per.gl_code_cola, per.gl_code_excess_pb, per.gl_code_other_income,
                    per.gl_code_salary_adjustment, per.gl_code_graveyard,
                    per.gl_code_late_regular, per.gl_code_late_trainee,
                    per.gl_code_leave_regular, per.gl_code_leave_trainee,
                    per.gl_code_all_other_deductions, per.gl_code_total, per.cost_center
            ";
            // Execute the query and return JSON for AJAX requests
            $result = mysqli_query($conn, $dataResultSql);
            if (!$result) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => mysqli_error($conn),
                    'sql' => $dataResultSql,
                    'rows' => [],
                    'totalBranches' => 0
                ]);
                exit;
            }

            $outputRows = [];
            while ($r = mysqli_fetch_assoc($result)) {
                $outputRows[] = $r;
            }

            // Prefer returning an HTML fragment of <tr> rows so the frontend
            // can inject them directly into the table tbody. JavaScript also
            // accepts JSON, but returning HTML keeps behavior consistent.
            header('Content-Type: text/html; charset=utf-8');

            if (empty($outputRows)) {
                echo '<tr><td colspan="26" style="padding:12px; color:#333;">No records found for the selected filters.</td></tr>';
                exit;
            }

            // Define the same column order used by the frontend renderer
            $columnsOrder = [
                'payroll_date', 'kp_code', 'branch_name', 'ml_matic_status',
                'gl_code_basic_pay_regular','basic_pay_regular',
                'gl_code_basic_pay_trainee','basic_pay_trainee',
                'gl_code_allowances','allowances',
                'gl_code_bm_allowance','bm_allowance',
                'gl_code_overtime_regular','overtime_regular',
                'gl_code_overtime_trainee','overtime_trainee',
                'gl_code_cola','cola',
                'gl_code_excess_pb','excess_pb',
                'gl_code_other_income','other_income',
                'gl_code_salary_adjustment','salary_adjustment',
                'gl_code_graveyard','graveyard',
                'gl_code_late_regular','late_regular',
                'gl_code_late_trainee','late_trainee',
                'gl_code_leave_regular','leave_regular',
                'gl_code_leave_trainee','leave_trainee',
                'gl_code_all_other_deductions','all_other_deductions',
                'gl_code_total','total',
                'cost_center','region','no_of_branch_employee','no_of_employees_allocated'
            ];

            $html = '';
            foreach ($outputRows as $r) {
                $html .= '<tr>';
                foreach ($columnsOrder as $key) {
                    $val = array_key_exists($key, $r) ? $r[$key] : '';
                    // For GL code fields (keys starting with 'gl_code_'), always treat as plain text
                    if (is_string($key) && strpos($key, 'gl_code_') === 0) {
                        $val = htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    } else {
                        // Format numeric-like values to two decimal places for amount columns only
                        if (is_numeric($val)) {
                            $val = number_format((float)$val, 2, '.', ',');
                        } else {
                            $val = htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        }
                    }
                    $html .= '<td>' . $val . '</td>';
                }
                $html .= '</tr>';
            }

            echo $html;
            exit;
    }

    require '../../../vendor/autoload.php';


    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;



    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $branch = $_SESSION['branch'] ?? '';
        $startDate = $_SESSION['startdate'] ?? '';
        $endDate = $_SESSION['enddate'] ?? '';

        generateDownload($conn, $database, $mainzone, $zone, $region, $branch, $startDate, $endDate);

    }


    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $zone, $region, $branch, $startDate, $endDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $branch = $_SESSION['branch'] ?? '';
        $startDate = $_SESSION['startdate'] ?? '';
        $endDate = $_SESSION['enddate'] ?? '';

        $dlsql="WITH summarized AS (
                        SELECT
                            region_code,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN basic_pay_regular ELSE 0 END) AS sum_basic_pay_regular,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN basic_pay_trainee ELSE 0 END) AS sum_basic_pay_trainee,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN allowances ELSE 0 END) AS sum_allowances,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN bm_allowance ELSE 0 END) AS sum_bm_allowance,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN overtime_regular ELSE 0 END) AS sum_overtime_regular,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN overtime_trainee ELSE 0 END) AS sum_overtime_trainee,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN cola ELSE 0 END) AS sum_cola,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN excess_pb ELSE 0 END) AS sum_excess_pb,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN other_income ELSE 0 END) AS sum_other_income,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN salary_adjustment ELSE 0 END) AS sum_salary_adjustment,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN graveyard ELSE 0 END) AS sum_graveyard,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN late_regular ELSE 0 END) AS sum_late_regular,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN late_trainee ELSE 0 END) AS sum_late_trainee,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN leave_regular ELSE 0 END) AS sum_leave_regular,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN leave_trainee ELSE 0 END) AS sum_leave_trainee,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN all_other_deductions ELSE 0 END) AS sum_all_other_deductions,
                            SUM(CASE WHEN ml_matic_status IN ('Pending', 'Inactive') THEN total ELSE 0 END) AS sum_total,
                            COUNT(CASE WHEN ml_matic_status = 'Active' THEN 1 ELSE NULL END) AS active_count
                        FROM " . $database[0] . ".payroll_edi_report";
                        if ($startDate === $endDate) {
                            $dlsql .= " WHERE payroll_date = '$startDate'";
                        } else {
                            $dlsql .= " WHERE payroll_date BETWEEN '$startDate' AND '$endDate'";
                        }
                            $dlsql .= " GROUP BY region_code
                    )

                    SELECT 
                        per.payroll_date, 
                        per.mainzone, 
                        per.zone, 
                        per.region, 
                        per.ml_matic_region, 
                        per.region_code, 
                        per.kp_code, 
                        per.ml_matic_status, 
                        per.branch_code, 
                        per.branch_name, 

                        s.sum_basic_pay_regular / NULLIF(s.active_count, 0) AS basic_pay_regular_avg,
                        SUM(per.basic_pay_regular) AS basic_pay_regular, 
                        per.gl_code_basic_pay_regular, 

                        s.sum_basic_pay_trainee / NULLIF(s.active_count, 0) AS basic_pay_trainee_avg,
                        SUM(per.basic_pay_trainee) AS basic_pay_trainee, 
                        per.gl_code_basic_pay_trainee,

                        s.sum_allowances / NULLIF(s.active_count, 0) AS allowances_avg,
                        SUM(per.allowances) AS allowances, 
                        per.gl_code_allowances, 

                        s.sum_bm_allowance / NULLIF(s.active_count, 0) AS bm_allowance_avg,
                        SUM(per.bm_allowance) AS bm_allowance, 
                        per.gl_code_bm_allowance, 

                        s.sum_overtime_regular / NULLIF(s.active_count, 0) AS overtime_regular_avg,
                        SUM(per.overtime_regular) AS overtime_regular, 
                        per.gl_code_overtime_regular, 

                        s.sum_overtime_trainee / NULLIF(s.active_count, 0) AS overtime_trainee_avg,
                        SUM(per.overtime_trainee) AS overtime_trainee, 
                        per.gl_code_overtime_trainee, 

                        s.sum_cola / NULLIF(s.active_count, 0) AS cola_avg,
                        SUM(per.cola) AS cola, 
                        per.gl_code_cola, 

                        s.sum_excess_pb / NULLIF(s.active_count, 0) AS excess_pb_avg,
                        SUM(per.excess_pb) AS excess_pb, 
                        per.gl_code_excess_pb, 

                        s.sum_other_income / NULLIF(s.active_count, 0) AS other_income_avg,
                        SUM(per.other_income) AS other_income, 
                        per.gl_code_other_income, 

                        s.sum_salary_adjustment / NULLIF(s.active_count, 0) AS salary_adjustment_avg,
                        SUM(per.salary_adjustment) AS salary_adjustment, 
                        per.gl_code_salary_adjustment, 

                        s.sum_graveyard / NULLIF(s.active_count, 0) AS graveyard_avg,
                        SUM(per.graveyard) AS graveyard, 
                        per.gl_code_graveyard, 

                        s.sum_late_regular / NULLIF(s.active_count, 0) AS late_regular_avg,
                        SUM(per.late_regular) AS late_regular, 
                        per.gl_code_late_regular, 

                        s.sum_late_trainee / NULLIF(s.active_count, 0) AS late_trainee_avg,
                        SUM(per.late_trainee) AS late_trainee, 
                        per.gl_code_late_trainee, 

                        s.sum_leave_regular / NULLIF(s.active_count, 0) AS leave_regular_avg,
                        SUM(per.leave_regular) AS leave_regular, 
                        per.gl_code_leave_regular, 

                        s.sum_leave_trainee / NULLIF(s.active_count, 0) AS leave_trainee_avg,
                        SUM(per.leave_trainee) AS leave_trainee, 
                        per.gl_code_leave_trainee, 

                        s.sum_all_other_deductions / NULLIF(s.active_count, 0) AS all_other_deductions_avg,
                        SUM(per.all_other_deductions) AS all_other_deductions, 
                        per.gl_code_all_other_deductions, 

                        s.sum_total / NULLIF(s.active_count, 0) AS total_avg,
                        SUM(per.total) AS total, 
                        per.gl_code_total, 

                        per.cost_center, 
                        SUM(per.no_of_branch_employee) AS no_of_branch_employee, 
                        SUM(per.no_of_employees_allocated) AS no_of_employees_allocated

                    FROM " . $database[0] . ".payroll_edi_report per
                    INNER JOIN summarized s ON s.region_code = per.region_code";

                    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                        $dlsql .= " WHERE per.mainzone = '$mainzone'
                        AND per.ml_matic_region = '$zone'
                        AND per.zone like '%$region%'";
                    }else{
                        $dlsql .= " WHERE per.mainzone = '$mainzone'
                            AND per.zone = '$zone'
                            AND per.region_code LIKE '%$region%'
                            AND per.ml_matic_region != 'LNCR Showroom'
                            AND per.ml_matic_region != 'VISMIN Showroom'";      
                    }

                    if (!empty($branch)) {
                        $dlsql .= " AND per.branch_code = $branch";
                    }
                    if ($startDate === $endDate) {
                        $dlsql .= " AND per.payroll_date = '$startDate'";
                    } else {
                        $dlsql .= " AND per.payroll_date BETWEEN '$startDate' AND '$endDate'";
                    }
                    
                    $dlsql .= " AND NOT per.ml_matic_status IN ('Pending', 'Inactive')
                        GROUP BY 
                            per.region_code, per.mainzone, per.zone, per.payroll_date,
                            per.region, per.ml_matic_region, per.kp_code, per.ml_matic_status,
                            per.branch_code, per.branch_name,
                            per.gl_code_basic_pay_regular, per.gl_code_basic_pay_trainee,
                            per.gl_code_allowances, per.gl_code_bm_allowance,
                            per.gl_code_overtime_regular, per.gl_code_overtime_trainee,
                            per.gl_code_cola, per.gl_code_excess_pb, per.gl_code_other_income,
                            per.gl_code_salary_adjustment, per.gl_code_graveyard,
                            per.gl_code_late_regular, per.gl_code_late_trainee,
                            per.gl_code_leave_regular, per.gl_code_leave_trainee,
                            per.gl_code_all_other_deductions, per.gl_code_total, per.cost_center
                    ";
                    
            $dlresult = mysqli_query($conn, $dlsql);
                    
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
                    
            $first_row = mysqli_fetch_assoc($dlresult);
            $payroll_date = htmlspecialchars($first_row['payroll_date']);
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
                            
            // Reset the result pointer to the beginning
            mysqli_data_seek($dlresult, 0);

            // First row
            if(!empty($branch) && !empty($region)){
                $sheet->setCellValue('A1', 'Payroll Summary - Per Branch & Per Region')->mergeCells('A1:C2')->getStyle('A1:C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }else{
                $sheet->setCellValue('A1', 'Payroll Summary - All Branches & All Regions')->mergeCells('A1:C2')->getStyle('A1:C2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }

            $sheet->setCellValue('D1','Basic Pay')->mergeCells('D1:E1')->getStyle('D1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('F1','Allowances')->mergeCells('F1:F2')->getStyle('F1:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('G1','BM Allowance')->mergeCells('G1:G2')->getStyle('G1:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('H1','Overtime')->mergeCells('H1:I1')->getStyle('H1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('J1','COLA')->mergeCells('J1:J2')->getStyle('J1:J2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('K1','Excess PB')->mergeCells('K1:K2')->getStyle('K1:K2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('L1','Other Income')->mergeCells('L1:L2')->getStyle('L1:L2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('M1','Salary Adjustment')->mergeCells('M1:M2')->getStyle('M1:M2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('N1','Graveyard')->mergeCells('N1:N2')->getStyle('N1:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


            $sheet->setCellValue('O1','Late')->mergeCells('O1:P1')->getStyle('O1:P1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('Q1','Leave')->mergeCells('Q1:R1')->getStyle('Q1:R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('S1','Total')->mergeCells('S1:S2')->getStyle('S1:S2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('T1','Cost Center')->mergeCells('T1:T4')->getStyle('T1:T4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('U1','No. of Branches Employees')->mergeCells('U1:U4')->getStyle('U1:U4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('V1','No. of Employees Allocated')->mergeCells('V1:V4')->getStyle('V1:V4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Second row
            $sheet->setCellValue('D2','Regular')->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('E2','Trainee')->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('H2','Regular')->getStyle('H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('I2','Trainee')->getStyle('I2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('O2','Regular')->getStyle('O2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('P2','Trainee')->getStyle('P2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('Q2','Regular')->getStyle('Q2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('R2','Trainee')->getStyle('R2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Third row
            if($startDate === $endDate){
                $sheet->setCellValue('A3', 'Payroll Date: ' . $payroll_date)->mergeCells('A3:C3')->getStyle('A3:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }else{
                $sheet->setCellValue('A3', 'Payroll Date: ' . $startDate . " to " . $endDate)->mergeCells('A3:C3')->getStyle('A3:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }
            $headerRow3 = [
                    'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                    'credit', 'credit', 'credit', 'credit','credit', 'credit'
                ];
            $sheet->fromArray($headerRow3, NULL, 'D3');

            // Fourth row
            $sheet->setCellValue('A4', 'Date')->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('B4', 'BOS Code')->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('C4', 'Branch Name')->getStyle('C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            
            $headerRow4 = [
                $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
                $gl_code_cola, $gl_code_excess_pb, $gl_code_other_income, $gl_code_salary_adjustment, $gl_code_graveyard, $gl_code_late_regular, $gl_code_late_trainee,
                $gl_code_leave_regular, $gl_code_leave_trainee,$gl_code_total
            ];

            $sheet->fromArray($headerRow4, NULL, 'D4');

            foreach(range('A', 'Z') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $rowIndex = 5;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;

            // Fifth row
            while ($row = mysqli_fetch_assoc($dlresult)) {
                $applyStyle = false; 

                if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                    $color = '4fc917';  
                    $bold = true;       
                    $applyStyle = true; 
                } else {
                    $bold = false;      
                }
                                    
                $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                                + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'] + $row['all_other_deductions'];
                $total = $totalDebit - $totalCredit;
                    
                $sheet->setCellValue('A' . $rowIndex, $row['payroll_date']);
                $sheet->setCellValue('B' . $rowIndex, $row['branch_code']);
                $sheet->setCellValue('C' . $rowIndex, $row['branch_name']);
                    
                // Use setCellValueExplicit for setting the value and format it as a number
                $sheet->setCellValueExplicit('D' . $rowIndex, $row['basic_pay_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('E' . $rowIndex, $row['basic_pay_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('F' . $rowIndex, $row['allowances'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                $sheet->setCellValueExplicit('G' . $rowIndex, $row['bm_allowance'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('H' . $rowIndex, $row['overtime_regular'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('I' . $rowIndex, $row['overtime_trainee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('J' . $rowIndex, $row['cola'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('K' . $rowIndex, $row['excess_pb'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('L' . $rowIndex, $row['other_income'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('M' . $rowIndex, $row['salary_adjustment'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                $sheet->setCellValueExplicit('N' . $rowIndex, $row['graveyard'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                // convert to negative if positive value 
                $sheet->setCellValueExplicit('O' . $rowIndex, ($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                $sheet->setCellValueExplicit('P' . $rowIndex, ($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                $sheet->setCellValueExplicit('Q' . $rowIndex, ($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                $sheet->setCellValueExplicit('R' . $rowIndex, ($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValueExplicit('S' . $rowIndex, $total, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                    
                $sheet->setCellValue('T' . $rowIndex, $row['cost_center']);
                $sheet->setCellValue('U' . $rowIndex, $row['no_of_branch_employee']);
                $sheet->setCellValue('V' . $rowIndex, $row['no_of_employees_allocated']);
                
                // $sheet->setCellValue('U' . $rowIndex, $row['region']);
                        
                if ($applyStyle) {
                    $sheet->getStyle('A' . $rowIndex . ':V' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);
                }
                                
                // Apply bold style regardless of background color
                $sheet->getStyle('A' . $rowIndex . ':V' . $rowIndex)->getFont()->setBold($bold);
                    
                $rowIndex++;
            }
            $_SESSION['rowIndex'] = $rowIndex;
            $rowIndex1 = $_SESSION['rowIndex'];

            if( $first_row['basic_pay_regular_avg'] != 0 ||  $first_row['basic_pay_trainee_avg'] != 0 ||  $first_row['allowances_avg'] != 0 ||  $first_row['bm_allowance_avg'] != 0 ||  $first_row['overtime_regular_avg'] != 0 ||  $first_row['overtime_trainee_avg'] != 0 ||  $first_row['cola_avg'] != 0 ||  $first_row['excess_pb_avg'] != 0 ||  $first_row['other_income_avg'] != 0 ||  $first_row['salary_adjustment_avg'] != 0 ||  $first_row['graveyard_avg'] != 0 ||  $first_row['late_regular_avg'] != 0 ||  $first_row['late_trainee_avg'] != 0 ||  $first_row['leave_regular_avg'] != 0 ||  $first_row['leave_trainee_avg'] != 0 ||  $first_row['all_other_deductions_avg'] != 0 ||  $first_row['total_avg'] != 0) {
                // Seventh row
                if(!empty($branch) && !empty($region)){
                    $sheet->setCellValue('A'.($rowIndex1+1), 'Allocated amount from closed branch')->mergeCells('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getStyle('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }else{
                    $sheet->setCellValue('A'.($rowIndex1+1), 'Allocated amount from closed branch')->mergeCells('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getStyle('A'.($rowIndex1+1).':C'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }

                $sheet->setCellValue('D'.($rowIndex1+1),'Basic Pay')->mergeCells('D'.($rowIndex1+1).':E'.($rowIndex1+1))->getStyle('D'.($rowIndex1+1).':E'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('F'.($rowIndex1+1),'Allowances')->mergeCells('F'.($rowIndex1+1).':F'.($rowIndex1+2))->getStyle('F'.($rowIndex1+1).':F'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('G'.($rowIndex1+1),'BM Allowance')->mergeCells('G'.($rowIndex1+1).':G'.($rowIndex1+2))->getStyle('G'.($rowIndex1+1).':G'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue('H'.($rowIndex1+1),'Overtime')->mergeCells('H'.($rowIndex1+1).':I'.($rowIndex1+1))->getStyle('H'.($rowIndex1+1).':I'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('J'.($rowIndex1+1),'COLA')->mergeCells('J'.($rowIndex1+1).':J'.($rowIndex1+2))->getStyle('J'.($rowIndex1+1).':J'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('K'.($rowIndex1+1),'Excess PB')->mergeCells('K'.($rowIndex1+1).':K'.($rowIndex1+2))->getStyle('K'.($rowIndex1+1).':K'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('L'.($rowIndex1+1),'Other Income')->mergeCells('L'.($rowIndex1+1).':L'.($rowIndex1+2))->getStyle('L'.($rowIndex1+1).':L'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('M'.($rowIndex1+1),'Salary Adjustment')->mergeCells('M'.($rowIndex1+1).':M'.($rowIndex1+2))->getStyle('M'.($rowIndex1+1).':M'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('N'.($rowIndex1+1),'Graveyard')->mergeCells('N'.($rowIndex1+1).':N'.($rowIndex1+2))->getStyle('N'.($rowIndex1+1).':N'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);


                $sheet->setCellValue('O'.($rowIndex1+1),'Late')->mergeCells('O'.($rowIndex1+1).':P'.($rowIndex1+1))->getStyle('O'.($rowIndex1+1).':P'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('Q'.($rowIndex1+1),'Leave')->mergeCells('Q'.($rowIndex1+1).':R'.($rowIndex1+1))->getStyle('Q'.($rowIndex1+1).':R'.($rowIndex1+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('S'.($rowIndex1+1),'Total')->mergeCells('S'.($rowIndex1+1).':S'.($rowIndex1+2))->getStyle('S'.($rowIndex1+1).':S'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->setCellValue('T'.($rowIndex1+1),'Cost Center')->mergeCells('T'.($rowIndex1+1).':T'.($rowIndex1+4))->getStyle('T'.($rowIndex1+1).':T'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('U'.($rowIndex1+1),'No. of Branches Employees')->mergeCells('U'.($rowIndex1+1).':U'.($rowIndex1+4))->getStyle('U'.($rowIndex1+1).':U'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('V'.($rowIndex1+1),'No. of Employees Allocated')->mergeCells('V'.($rowIndex1+1).':V'.($rowIndex1+4))->getStyle('V'.($rowIndex1+1).':V'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                // Eighth row
                $sheet->setCellValue('D'.($rowIndex1+2),'Regular')->getStyle('D'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('E'.($rowIndex1+2),'Trainee')->getStyle('E'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('H'.($rowIndex1+2),'Regular')->getStyle('H'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('I'.($rowIndex1+2),'Trainee')->getStyle('I'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('O'.($rowIndex1+2),'Regular')->getStyle('O'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('P'.($rowIndex1+2),'Trainee')->getStyle('P'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('Q'.($rowIndex1+2),'Regular')->getStyle('Q'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue('R'.($rowIndex1+2),'Trainee')->getStyle('R'.($rowIndex1+2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Nineth row
                if($startDate === $endDate){
                    $sheet->setCellValue('A'.($rowIndex1+3), 'Payroll Date: ' . $payroll_date)->mergeCells('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getStyle('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }else{
                    $sheet->setCellValue('A'.($rowIndex1+3), 'Payroll Date: ' . $startDate . " to " . $endDate)->mergeCells('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getStyle('A'.($rowIndex1+3).':C'.($rowIndex1+3))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                }
                $headerRow3 = [
                        'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit', 'debit',
                        'credit', 'credit', 'credit', 'credit','credit', 'credit'
                    ];
                $sheet->fromArray($headerRow3, NULL, 'D'.($rowIndex1+3));

                // Tenth row
                $sheet->setCellValue('A'.($rowIndex1+4), 'Date')->getStyle('A'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('B'.($rowIndex1+4), 'BOS Code')->getStyle('B'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->setCellValue('C'.($rowIndex1+4), 'Branch Name')->getStyle('C'.($rowIndex1+4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                $headerRow4 = [
                    $gl_code_basic_pay_regular, $gl_code_basic_pay_trainee, $gl_code_allowances, $gl_code_bm_allowance, $gl_code_overtime_regular, $gl_code_overtime_trainee,
                    $gl_code_cola, $gl_code_excess_pb, $gl_code_other_income, $gl_code_salary_adjustment, $gl_code_graveyard, $gl_code_late_regular, $gl_code_late_trainee,
                    $gl_code_leave_regular, $gl_code_leave_trainee,$gl_code_total
                ];

                $sheet->fromArray($headerRow4, NULL, 'D'.($rowIndex1+4));

                foreach(range('A', 'Z') as $columnID1) {
                    $sheet->getColumnDimension($columnID1)->setAutoSize(true);
                }

                $rowIndex2 = $rowIndex1 + 5;
                $total1 = 0;
                $totalDebit1 = 0;
                $totalCredit1 = 0;

                // Eleventh row
                mysqli_data_seek($dlresult, 0);
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    // Only write rows where at least one *_avg column is not zero
                    if (
                        $row['basic_pay_regular_avg'] != 0 ||
                        $row['basic_pay_trainee_avg'] != 0 ||
                        $row['allowances_avg'] != 0 ||
                        $row['bm_allowance_avg'] != 0 ||
                        $row['overtime_regular_avg'] != 0 ||
                        $row['overtime_trainee_avg'] != 0 ||
                        $row['cola_avg'] != 0 ||
                        $row['excess_pb_avg'] != 0 ||
                        $row['other_income_avg'] != 0 ||
                        $row['salary_adjustment_avg'] != 0 ||
                        $row['graveyard_avg'] != 0 ||
                        $row['late_regular_avg'] != 0 ||
                        $row['late_trainee_avg'] != 0 ||
                        $row['leave_regular_avg'] != 0 ||
                        $row['leave_trainee_avg'] != 0 ||
                        $row['all_other_deductions_avg'] != 0 ||
                        $row['total_avg'] != 0
                    ) {
                        $applyStyle = false; 

                        if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                            $color = '4fc917';  
                            $bold = true;       
                            $applyStyle = true; 
                        } else {
                            $bold = false;      
                        }

                        $totalDebit1 = $row['basic_pay_regular_avg'] + $row['basic_pay_trainee_avg'] + $row['allowances_avg'] + $row['bm_allowance_avg'] + $row['overtime_regular_avg'] 
                                        + $row['overtime_trainee_avg'] + $row['cola_avg'] + $row['excess_pb_avg'] + $row['other_income_avg'] + $row['salary_adjustment_avg'] + $row['graveyard_avg'];
                        $totalCredit1 = $row['late_regular_avg'] + $row['late_trainee_avg'] + $row['leave_regular_avg'] + $row['leave_trainee_avg'] + $row['all_other_deductions_avg'];
                        $total1 = $totalDebit1 - $totalCredit1;
                            
                        $sheet->setCellValue('A' . $rowIndex2, $row['payroll_date']);
                        $sheet->setCellValue('B' . $rowIndex2, $row['branch_code']);
                        $sheet->setCellValue('C' . $rowIndex2, $row['branch_name']);
                            
                        // Use setCellValueExplicit for setting the value and format it as a number
                        $sheet->setCellValueExplicit('D' . $rowIndex2, $row['basic_pay_regular_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('D' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

                        $sheet->setCellValueExplicit('E' . $rowIndex2, $row['basic_pay_trainee_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('E' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('F' . $rowIndex2, $row['allowances_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('F' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('G' . $rowIndex2, $row['bm_allowance_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('G' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('H' . $rowIndex2, $row['overtime_regular_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('H' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('I' . $rowIndex2, $row['overtime_trainee_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('I' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('J' . $rowIndex2, $row['cola_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('J' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('K' . $rowIndex2, $row['excess_pb_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('K' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('L' . $rowIndex2, $row['other_income_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('L' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('M' . $rowIndex2, $row['salary_adjustment_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('M' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('N' . $rowIndex2, $row['graveyard_avg'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('N' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        // convert to negative if positive value 
                        $sheet->setCellValueExplicit('O' . $rowIndex2, ($row['late_regular_avg'] > 0 ? -$row['late_regular_avg'] : $row['late_regular_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('O' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('P' . $rowIndex2, ($row['late_trainee_avg'] > 0 ? -$row['late_trainee_avg'] : $row['late_trainee_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('P' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('Q' . $rowIndex2, ($row['leave_regular_avg'] > 0 ? -$row['leave_regular_avg'] : $row['leave_regular_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('Q' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                    
                        $sheet->setCellValueExplicit('R' . $rowIndex2, ($row['leave_trainee_avg'] > 0 ? -$row['leave_trainee_avg'] : $row['leave_trainee_avg']), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('R' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValueExplicit('S' . $rowIndex2, $total1, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('S' . $rowIndex2)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                                                
                        $sheet->setCellValue('T' . $rowIndex2, $row['cost_center']);
                        $sheet->setCellValue('U' . $rowIndex2, $row['no_of_branch_employee']);
                        $sheet->setCellValue('V' . $rowIndex2, $row['no_of_employees_allocated']);

                        if ($applyStyle) {
                            $sheet->getStyle('A' . $rowIndex2 . ':V' . $rowIndex2)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                        }
                                    
                        // Apply bold style regardless of background color
                        $sheet->getStyle('A' . $rowIndex2 . ':V' . $rowIndex2)->getFont()->setBold($bold);
                        
                        $rowIndex2++;
                    }
                }
            }
            

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

			if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                if(!empty($region)){
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $region . "_" . $startDate . ".xls";
                    }else{
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $region . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                }else{
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $startDate . ".xls";
                    }else{
                        $filename = "EDI_Summary_Payroll_Report_" . $mainzone . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                }
                    
            }else{
                if(!empty($region)){
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $zone .  "_" . $region . "_" . $startDate . ".xls";
                    }else {
                        $filename = "EDI_Summary_Payroll_Report_" . $zone .  "_" . $region . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                } else {
                    if($startDate === $endDate){
                        $filename = "EDI_Summary_Payroll_Report_" . $zone . "_" . $startDate . ".xls";
                    }else{
                        $filename = "EDI_Summary_Payroll_Report_" . $zone . "_" . $startDate . "_to_" . $endDate . ".xls";
                    }
                    
                }
            }
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
                        
            $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
            exit();        
            // csv format
            // header('Content-Type: text/csv');
            // $filename = "EDI_Payroll_Report_" . $zone . "_" . $region . "_" . $restrictedDate . ".csv";
            // header('Content-Disposition: attachment; filename="' . $filename . '"');
            // header('Cache-Control: max-age=0');
                    
            // $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Csv');
            // $writer->save('php://output');
        
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <!-- Select2 Bootstrap theme -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-5-theme/1.3.0/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
            width: 160px;
            padding: 10px 36px 10px 10px; /* leave room on the right for the custom arrow */
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            -webkit-appearance: none; /* Remove default arrow in WebKit browsers */
            -moz-appearance: none; /* Remove default arrow in Firefox */
            appearance: none; /* Remove default arrow in most modern browsers */
            color: #F14A51;
            vertical-align: middle;
        }
        .custom-select-wrapper {
            position: relative;
            display: inline-block;
            margin-left: 10px;
            color: #F14A51;
            vertical-align: middle;
        }
        .custom-select-wrapper label {
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
            font-weight: 600;
            color: #F14A51;
        }
        .custom-arrow {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            padding: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #333;
            pointer-events: none;
        }
        input[type="date"] {
            width: 150px;
            padding: 10px 14px;
            font-size: 14px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            margin-right: 20px;
            color: #F14A51;
            vertical-align: middle;
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

        /* Layout: keep the outer import container padded,
           but make its immediate wrapper a horizontal row so
           the main form and the export box align on one line. */
        .import-file {
            padding: 10px 16px;
        }

        /* The direct child wrapper becomes the horizontal flex row */
        .import-file > .custom-select-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* The main form inside that wrapper should also present its
           field-groups inline with wrapping enabled. */
        .import-file > .custom-select-wrapper > form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* Individual field groups keep label above the control */
        .import-file > .custom-select-wrapper .custom-select-wrapper {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin: 0;
        }

        /* Tighter spacing for label and inputs in the import area */
        .import-file .custom-select-wrapper label {
            margin-bottom: 6px;
            font-size: 13px;
        }

        /* Ensure controls don't stretch too wide and align nicely */
        .import-file .custom-select-wrapper select,
        .import-file .custom-select-wrapper input[type="date"],
        .import-file .custom-select-wrapper input[type="submit"] {
            display: inline-block;
            vertical-align: middle;
        }

        /* Keep the Export box inline with the form (it is a sibling inside the row) */
        #showdl {
            display: flex;
            align-items: center;
            margin: 0;
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

    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div>
        <center>
            <h3>Payroll Detailed Report <span>[Payroll]</span></h3>
        </center>
    </div>

    <div class="import-file">
        
        <div class="custom-select-wrapper">
            <form action="get_data_results" method="post">
                <div class="custom-select-wrapper">
                    <label for="restricted-date">FROM </label>
                    <input type="date" id="restricted-date" name="startdate" value="<?php echo isset($_POST['startdate']) ? $_POST['startdate'] : '';?>" required> 
                </div>
                <div class="custom-select-wrapper">
                    <label for="restricted-date1">TO </label>
                    <input type="date" id="restricted-date1" name="enddate" value="<?php echo isset($_POST['enddate']) ? $_POST['enddate'] : '';?>" required>
                </div>
                <div class="custom-select-wrapper">
                    <label for="mainzone">Mainzone </label>
                    <select name="mainzone" id="mainzone" autocomplete="off" required>
                        <option value="">Select Mainzone</option>
                        <option value="ALL">ALL</option>
                        <?php 
                            if ($mainzone_result && mysqli_num_rows($mainzone_result) > 0) {
                                while ($row = mysqli_fetch_assoc($mainzone_result)) {
                                    $mainzone = htmlspecialchars($row['mainzone']);
                                    $selected = (isset($_GET['mainzone']) && $_GET['mainzone'] == $mainzone) ? 'selected' : '';
                                    echo "<option value='$mainzone' $selected>" . ucfirst($mainzone) . "</option>";
                                }
                            }
                        ?>
                    </select>
                </div>
                <div class="custom-select-wrapper">
                    <label for="zone_filter">Zone</label>
                    <select id="zone_filter" name="zone" autocomplete="off" required>
                        <option value="">Select Zone</option>
                        <!-- Zones will be populated dynamically by JavaScript -->
                    </select>
                </div>
                <div class="custom-select-wrapper">
                    <label for="region">Region</label>
                    <select name="region" id="region" autocomplete="off" required>
                        <option value="">Select Region</option>
                        <!-- Regions will be populated dynamically by JavaScript -->
                        <?php
                            // If a region is selected, display it after the page reloads
                            if (isset($_POST['region'])) {
                                echo '<option value="' . htmlspecialchars($_POST['region']) . '" selected>' . htmlspecialchars($_POST['region']) . '</option>';
                            }
                        ?>
                    </select>
                </div>
                <div class="custom-select-wrapper">
                    <label for="branchDropdown">Branch Name</label>
                    <select id="branchDropdown" name="branch" class="select2" data-placeholder="Search Branch Name..." aria-label="Select Branch Name" required>
                        <option value="">Select Branch</option>
                        <option value="ALL">ALL</option>
                    </select>
                </div>
                <div class="custom-select-wrapper">
                    <label for="status">Status</label>
                    <select name="status" id="status" autocomplete="off" required>
                        <option value="">Select Status</option>
                        <option value="ALL">ALL</option>
                        <option value="TBO">To Be Open</option>
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="custom-select-wrapper">
                    <input type="submit" class="generate-btn" name="generate" value="Proceed">
                </div>
            </form>

            <!-- <div id="showdl" style="display: none"> -->
            <div id="showdl" class="custom-select-wrapper" style="display: none">
                <form action="" method="post">
                    <input type="submit" class="download-btn" name="download" value="Export to Excel">
                </form>
            </div>
        </div>
    </div>

    <div id="showBranches" style=" position: absolute; top: 190px; color: red; left: 20px;">
        Total Number of Branches : 0
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th colspan="4">Payroll Summary - All Branches &amp; All Regions</th>
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
                    <th rowspan="3">Cost Center</th>
                    <th rowspan="3" style="width: 10px;"></th>
                    <th rowspan="3">Region</th>
                    <th rowspan="2">All Other Deductions</th>
                    <th rowspan="3">No. of Branch Employees</th>
                    <th rowspan="3">No. of Employees Allocated</th>
                </tr>
                <tr>
                    <th colspan="4">Payroll Date - [start] to [end]</th>
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
                </tr>
                <tr id="gl-code-row">
                    <!-- Data rows will be populated here via JavaScript -->
                    <th style="white-space: nowrap">Date</th>
                    <th style="white-space: nowrap">BOS Code</th>
                    <th>Branch Name</th>
                    <th>Branch Status</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                    <th>GL Code</th>
                </tr>
            </thead>
            <tbody id="payroll-rows">
                <!-- Data rows will be populated here via JavaScript -->
            </tbody>
        </table>
    </div>
    
    <!-- Date Validation -->
    <script>
        // only 15 and last day of the month
        function isLastDayOfMonth(date) {
            const nextDay = new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1);
            return nextDay.getDate() === 1;
        }

        document.getElementById('restricted-date').addEventListener('change', function(event) {
            const input = event.target;
            const date = new Date(input.value);
            const day = date.getDate();

            // Allow only the 15th and the last day of the month
            if (day !== 15 && !isLastDayOfMonth(date)) {
                // Reset the value if it's not a valid day
                input.value = '';
                alert('Please select only the 15th or the last day of the month.');
            }
        });
        document.getElementById('restricted-date1').addEventListener('change', function(event) {
            const input = event.target;
            const date = new Date(input.value);
            const day = date.getDate();

            // Allow only the 15th and the last day of the month
            if (day !== 15 && !isLastDayOfMonth(date)) {
                // Reset the value if it's not a valid day
                input.value = '';
                alert('Please select only the 15th or the last day of the month.');
            }
        });
    </script>

    <!-- Mainzone, Zone, Region Validation -->
    <script>
        $(document).ready(function() {
            // Cached selectors
            const mainzoneSelect = $('#mainzone');
            const zoneSelect = $('#zone_filter');
            const regionSelect = $('#region');
            const branchSelect = $('#branchDropdown');

            // Helper to populate zone select from either JSON (zones array) or HTML string
            function populateZoneOptions(response) {
                let options = '<option value="">Select Zone</option><option value="ALL">ALL</option>';
                if (typeof response === 'object' && response !== null && response.success && Array.isArray(response.zones)) {
                    response.zones.forEach(function(z) {
                        options += `<option value="${z}">${z}</option>`;
                    });
                } else if (typeof response === 'string') {
                    // assume server returned raw <option>... HTML
                    options += response;
                }
                options += '<option value="Showroom">SHOWROOM</option>';
                zoneSelect.html(options);
            }

            // Mainzone change handler (adapted from your sample)
            mainzoneSelect.on('change', function() {
                const selectedValue = $(this).val();

                if (selectedValue !== '') {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: {
                            action: 'get_zones',
                            mainzone: selectedValue
                        },
                        dataType: 'html',
                        success: function(response) {
                            // support both JSON (success/zones) and HTML string (if backend returns options)
                            if (typeof response === 'object') {
                                populateZoneOptions(response);
                            } else {
                                // if JSON parsing failed and backend returned HTML, try as string
                                populateZoneOptions(response);
                            }
                        },
                        error: function() {
                            zoneSelect.html('<option value="">Select Zone</option><option value="ALL">ALL</option><option value="Showroom">SHOWROOM</option>');
                        }
                    });
                } else {
                    zoneSelect.html('<option value="">Select Zone</option>');
                    regionSelect.html('<option value="">Select Region</option>');
                    branchSelect.html('<option value="">Select Branch</option>');
                }
            });

            // Fetch branches when all required filters are selected
            function fetchBranches() {
                const startDate = $('#restricted-date').val();
                const endDate = $('#restricted-date1').val();
                const mainzone = mainzoneSelect.val();
                const zone = zoneSelect.val();
                const region = regionSelect.val();

                // require all filters to be set (user requested behavior)
                if (!startDate || !endDate || !mainzone || !zone || !region) {
                    // clear branches
                    branchSelect.html('<option value="">Select Branch</option><option value="ALL">ALL</option>');
                    return;
                }

                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: {
                        action: 'get_branches',
                        startdate: startDate,
                        enddate: endDate,
                        mainzone: mainzone,
                        zone: zone,
                        region: region
                    },
                    dataType: 'html',
                    success: function(response) {
                        let options = '<option value="">Select Branch</option><option value="ALL">ALL</option>';
                        options += response;
                        branchSelect.html(options).val('').trigger('change');
                    },
                    error: function() {
                        branchSelect.html('<option value="">Select Branch</option><option value="ALL">ALL</option>');
                    }
                });
            }

            // Hook fetchBranches to relevant inputs
            $('#restricted-date, #restricted-date1, #mainzone, #zone_filter, #region').on('change', fetchBranches);

            // Zone change handler (adapted from your sample, areaSelect removed)
            zoneSelect.on('change', function() {
                const selectedZone = $(this).val();
                const mainzoneValue = mainzoneSelect.val();
                const zoneValue = selectedZone;

                // Clear dependent selects first
                regionSelect.html('<option value="">Select Region</option>');
                branchSelect.html('<option value="">Select Branch</option>');

                if (selectedZone !== '') {
                    // When mainzone is not ALL (LNCR, VISMIN)
                    if (mainzoneValue !== 'ALL') {
                        if (selectedZone !== 'ALL') {
                            if (selectedZone !== 'Showroom') {
                                // normal region fetch
                                $.ajax({
                                    type: 'POST',
                                    url: window.location.href,
                                    data: {
                                        action: 'get_regions',
                                        mainzone: mainzoneValue,
                                        zone: selectedZone
                                    },
                                    dataType: 'html',
                                    success: function(response) {
                                        let regionOptions = '<option value="">Select Region</option><option value="ALL">ALL</option>';
                                        regionOptions += response;
                                        regionSelect.html(regionOptions);
                                    },
                                    error: function() {
                                        regionSelect.html('<option value="">Select Region</option><option value="ALL">ALL</option>');
                                    }
                                });
                            } else {
                                // Showroom handling for LNCR/VISMIN when specific showroom selected
                                let regionOptions = '<option value="">Select Region</option><option value="' + mainzoneValue + ' ' + zoneValue + '">ALL</option>';
                                if (mainzoneValue === 'VISMIN') {
                                    regionOptions += '<option value="VIS">VISAYAS SHOWROOM</option>';
                                    regionOptions += '<option value="MIN">MINDANAO SHOWROOM</option>';
                                }
                                if (mainzoneValue === 'LNCR') {
                                    regionOptions += '<option value="LZN">LUZON SHOWROOM</option>';
                                    regionOptions += '<option value="NCR">NCR SHOWROOM</option>';
                                }
                                regionSelect.html(regionOptions);
                            }
                        } else {
                            // selectedZone === 'ALL' for non-ALL mainzone
                            $.ajax({
                                type: 'POST',
                                url: window.location.href,
                                data: {
                                    action: 'get_regions',
                                    mainzone: mainzoneValue,
                                    zone: zoneValue
                                },
                                dataType: 'html',
                                success: function(response) {
                                    let regionOptions = '<option value="">Select Region</option><option value="ALL">ALL</option>';
                                    regionOptions += response;
                                    regionOptions += '<option value="' + mainzoneValue + ' Showroom">' + mainzoneValue + ' SHOWROOM</option>';
                                    regionSelect.html(regionOptions);
                                },
                                error: function() {
                                    regionSelect.html('<option value="">Select Region</option><option value="ALL">ALL</option>');
                                }
                            });
                        }
                    } else {
                        // mainzone is ALL
                        if (selectedZone !== 'ALL') {
                            if (selectedZone !== 'Showroom') {
                                $.ajax({
                                    type: 'POST',
                                    url: window.location.href,
                                    data: {
                                        action: 'get_regions',
                                        mainzone: mainzoneValue,
                                        zone: selectedZone
                                    },
                                    dataType: 'html',
                                    success: function(response) {
                                        let regionOptions = '<option value="">Select Region</option><option value="ALL">ALL</option>';
                                        regionOptions += response;
                                        if (selectedZone === 'LZN') {
                                            regionOptions += '<option value="LZN">LUZON SHOWROOM</option>';
                                        } else if (selectedZone === 'NCR') {
                                            regionOptions += '<option value="NCR">NCR SHOWROOM</option>';
                                        } else if (selectedZone === 'VIS') {
                                            regionOptions += '<option value="VIS">VISAYAS SHOWROOM</option>';
                                        } else if (selectedZone === 'MIN') {
                                            regionOptions += '<option value="MIN">MINDANAO SHOWROOM</option>';
                                        }
                                        regionSelect.html(regionOptions);
                                    },
                                    error: function() {
                                        regionSelect.html('<option value="">Select Region</option><option value="ALL">ALL</option>');
                                    }
                                });
                            } else {
                                // Showroom when mainzone is ALL
                                let regionOptions = '<option value="">Select Region</option><option value="ALL">ALL</option>';
                                regionOptions += '<option value="LZN">LUZON SHOWROOM</option>';
                                regionOptions += '<option value="NCR">NCR SHOWROOM</option>';
                                regionOptions += '<option value="VIS">VISAYAS SHOWROOM</option>';
                                regionOptions += '<option value="MIN">MINDANAO SHOWROOM</option>';
                                regionSelect.html(regionOptions);
                            }
                        } else {
                            // selectedZone === 'ALL' and mainzone === 'ALL'
                            $.ajax({
                                type: 'POST',
                                url: window.location.href,
                                data: {
                                    action: 'get_regions',
                                    mainzone: mainzoneValue,
                                    zone: zoneValue
                                },
                                dataType: 'html',
                                success: function(response) {
                                    let regionOptions = '<option value="">Select Region</option><option value="ALL">ALL</option>';
                                    regionOptions += response;
                                    regionOptions += '<option value="LZN">LUZON SHOWROOM</option>';
                                    regionOptions += '<option value="NCR">NCR SHOWROOM</option>';
                                    regionOptions += '<option value="VIS">VISAYAS SHOWROOM</option>';
                                    regionOptions += '<option value="MIN">MINDANAO SHOWROOM</option>';
                                    regionSelect.html(regionOptions);
                                },
                                error: function() {
                                    regionSelect.html('<option value="">Select Region</option><option value="ALL">ALL</option>');
                                }
                            });
                        }
                    }
                } else {
                    regionSelect.html('<option value="">Select Region</option>');
                    // areaSelect removed per request
                }
            });

            // If a mainzone is already selected on page load, trigger change to populate zones
            if (mainzoneSelect.val()) {
                mainzoneSelect.trigger('change');
            }
        });
    </script>

    <!-- Branch Validation based on get at edi.payroll_edi_report through branch name-->
    <script>
        $('#branchDropdown').select2({
            placeholder: 'Search or select a Branch Name...',
            allowClear: true
        });
    </script>

    <script>
    (function(){
        const loadingOverlay = document.getElementById('loading-overlay');
        const payrollRows = document.getElementById('payroll-rows');
        const showBranches = document.getElementById('showBranches');
        const showdl = document.getElementById('showdl');

        function formatNumber(val){
            if (val === null || val === undefined || val === '') return '';
            const n = parseFloat(val);
            if (isNaN(n)) return val;
            return n.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2});
        }

        function renderRow(item){
            return `
                <tr>
                    <td style="white-space:nowrap">${item.payroll_date ?? ''}</td>
                    <td>${item.branch_code ?? ''}</td>
                    <td>${item.branch_name ?? ''}</td>
                    <td>${item.ml_matic_status ?? item.branch_status ?? ''}</td>
                    <td>${formatNumber(item.basic_pay_regular)}</td>
                    <td>${formatNumber(item.basic_pay_trainee)}</td>
                    <td>${formatNumber(item.allowances)}</td>
                    <td>${formatNumber(item.bm_allowance)}</td>
                    <td>${formatNumber(item.overtime_regular)}</td>
                    <td>${formatNumber(item.overtime_trainee)}</td>
                    <td>${formatNumber(item.cola)}</td>
                    <td>${formatNumber(item.excess_pb)}</td>
                    <td>${formatNumber(item.other_income)}</td>
                    <td>${formatNumber(item.salary_adjustment)}</td>
                    <td>${formatNumber(item.graveyard)}</td>
                    <td>${formatNumber(item.late_regular)}</td>
                    <td>${formatNumber(item.late_trainee)}</td>
                    <td>${formatNumber(item.leave_regular)}</td>
                    <td>${formatNumber(item.leave_trainee)}</td>
                    <td>${formatNumber(item.total)}</td>
                    <td>${item.cost_center ?? ''}</td>
                    <td>${item.region ?? ''}</td>
                    <td></td>
                    <td>${formatNumber(item.all_other_deductions)}</td>
                    <td>${item.no_of_branch_employee ?? ''}</td>
                    <td>${item.no_of_employees_allocated ?? ''}</td>
                </tr>
            `;
        }

        function fetchPayrollReport(params){
            if (!params || !params.startdate || !params.enddate) {
                payrollRows.innerHTML = '<tr><td colspan="26" style="color:gray">Please set both FROM and TO dates and click Proceed.</td></tr>';
                return;
            }

            loadingOverlay.style.display = 'block';
            payrollRows.innerHTML = '';

            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: Object.assign({ action: 'get_data_results' }, params),
                dataType: 'html',
                success: function(responseHtml){
                    // stop loader
                    loadingOverlay.style.display = 'none';

                    // try to parse JSON first; if not JSON, treat response as HTML rows
                    try {
                        var data = (typeof responseHtml === 'string') ? JSON.parse(responseHtml) : responseHtml;
                        if (Array.isArray(data)) {
                            if (data.length === 0) {
                                payrollRows.innerHTML = '<tr><td colspan="26" style="color:gray">No data found.</td></tr>';
                                showdl.style.display = 'none';
                                showBranches.textContent = 'Total Number of Branches : 0';
                                return;
                            }
                            payrollRows.innerHTML = data.map(renderRow).join('');
                            showdl.style.display = 'block';
                            showBranches.textContent = 'Total Number of Branches : ' + data.length;
                            return;
                        }
                    } 
                    catch (e) {
                        // not JSON — fall through to treat responseHtml as HTML
                    }

                    // fallback: server returned raw HTML rows
                    payrollRows.innerHTML = responseHtml && responseHtml.trim() !== '' ? responseHtml : '<tr><td colspan="26" style="color:gray">No data found.</td></tr>';
                    const rowCount = payrollRows.querySelectorAll('tr').length;
                    showdl.style.display = rowCount ? 'block' : 'none';
                    showBranches.textContent = 'Total Number of Branches : ' + (rowCount || 0);
                },
                error: function(){
                    payrollRows.innerHTML = '<tr><td colspan="26" style="color:red">Failed to fetch report. Try again.</td></tr>';
                    showdl.style.display = 'none';
                    showBranches.textContent = 'Total Number of Branches : 0';
                    loadingOverlay.style.display = 'none';
                }
            });
        }

        // Hook the filter form submit to load rows via AJAX instead of full page post
        const filterForm = document.querySelector('form[action="get_data_results"]');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e){
                e.preventDefault();
                const params = {
                    startdate: document.getElementById('restricted-date').value,
                    enddate: document.getElementById('restricted-date1').value,
                    mainzone: document.getElementById('mainzone').value,
                    zone: document.getElementById('zone_filter').value,
                    region: document.getElementById('region').value,
                    branch: document.getElementById('branchDropdown').value,
                    status: document.getElementById('status').value
                };
                fetchPayrollReport(params);
            });
        }
    })();
    </script>
    
</body>

</html>
