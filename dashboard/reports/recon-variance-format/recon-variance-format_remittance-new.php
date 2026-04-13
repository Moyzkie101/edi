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

    // ini_set('display_errors',1);
    // error_reporting(E_ALL);
    // mysqli_report(MYSQLI_REPORT_ERROR | E_DEPRECATED | E_STRICT);
    // error_reporting(0);

    require '../../../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Cell\DataType;
    use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;

    if (isset($_POST['download'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
        $status = $_SESSION['status'] ?? '';

    
        generateDownload($conn, $database, $mainzone, $region, $restrictedDate , $status);
        
    }
 
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $region, $restrictedDate, $status) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
        $status = $_SESSION['status'] ?? '';

        $payrollDay = $_SESSION['payroll_day'] ?? '';
        $payrollMonth = $_SESSION['payroll_month'] ?? '';
        $payrollYear = $_SESSION['payroll_year'] ?? '';

        $subtotal_hrmdrfp_ee_shared = $_SESSION['subtotal_hrmdrfp_ee_shared'] ?? 0;
        $subtotal_hrmdrfp_er_shared = $_SESSION['subtotal_hrmdrfp_er_shared'] ?? 0;
        $subtotal_hrmdrfp_total = $_SESSION['subtotal_hrmdrfp_total'] ?? 0;

        $subtotal_hrmdedi_sss_ee_shared = $_SESSION['subtotal_hrmdedi_sss_ee_shared'] ?? 0;
        $subtotal_hrmdedi_sss_er_shared = $_SESSION['subtotal_hrmdedi_sss_er_shared'] ?? 0;

        $subtotal_hrmdedi_philhealth_ee_shared = $_SESSION['subtotal_hrmdedi_philhealth_ee_shared'] ?? 0;
        $subtotal_hrmdedi_philhealth_er_shared = $_SESSION['subtotal_hrmdedi_philhealth_er_shared'] ?? 0;

        $subtotal_hrmdedi_pagibig_ee_shared = $_SESSION['subtotal_hrmdedi_pagibig_ee_shared'] ?? 0;
        $subtotal_hrmdedi_pagibig_er_shared = $_SESSION['subtotal_hrmdedi_pagibig_er_shared'] ?? 0;


        $subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance = $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance'] ?? 0;
        $subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance = $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance'] ?? 0;

        $subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance = $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance'] ?? 0;
        $subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance = $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance'] ?? 0;

        $subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance = $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance'] ?? 0;
        $subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance = $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance'] ?? 0;

        $subtotal_edi_sss_ee_shared_active_branch = $_SESSION['subtotal_edi_sss_ee_shared_active_branch'] ?? 0;
        $subtotal_edi_sss_ee_shared_active_jewelry = $_SESSION['subtotal_edi_sss_ee_shared_active_jewelry'] ?? 0;

        $subtotal_edi_sss_er_shared_active_branch = $_SESSION['subtotal_edi_sss_er_shared_active_branch'] ?? 0;
        $subtotal_edi_sss_er_shared_active_jewelry = $_SESSION['subtotal_edi_sss_er_shared_active_jewelry'] ?? 0;

        $subtotal_edi_sss_ee_shared_closed_branch = $_SESSION['subtotal_edi_sss_ee_shared_closed_branch'] ?? 0;
        $subtotal_edi_sss_ee_shared_closed_jewelry = $_SESSION['subtotal_edi_sss_ee_shared_closed_jewelry'] ?? 0;

        $subtotal_edi_sss_er_shared_closed_branch = $_SESSION['subtotal_edi_sss_er_shared_closed_branch'] ?? 0;
        $subtotal_edi_sss_er_shared_closed_jewelry = $_SESSION['subtotal_edi_sss_er_shared_closed_jewelry'] ?? 0;


        $subtotal_edi_philhealth_ee_shared_active_branch = $_SESSION['subtotal_edi_philhealth_ee_shared_active_branch'] ?? 0;
        $subtotal_edi_philhealth_ee_shared_active_jewelry = $_SESSION['subtotal_edi_philhealth_ee_shared_active_jewelry'] ?? 0;

        $subtotal_edi_philhealth_er_shared_active_branch = $_SESSION['subtotal_edi_philhealth_er_shared_active_branch'] ?? 0;
        $subtotal_edi_philhealth_er_shared_active_jewelry = $_SESSION['subtotal_edi_philhealth_er_shared_active_jewelry'] ?? 0;

        $subtotal_edi_philhealth_ee_shared_closed_branch = $_SESSION['subtotal_edi_philhealth_ee_shared_closed_branch'] ?? 0;
        $subtotal_edi_philhealth_ee_shared_closed_jewelry = $_SESSION['subtotal_edi_philhealth_ee_shared_closed_jewelry'] ?? 0;

        $subtotal_edi_philhealth_er_shared_closed_branch = $_SESSION['subtotal_edi_philhealth_er_shared_closed_branch'] ?? 0;
        $subtotal_edi_philhealth_er_shared_closed_jewelry = $_SESSION['subtotal_edi_philhealth_er_shared_closed_jewelry'] ?? 0;


        $subtotal_edi_pagibig_ee_shared_active_branch = $_SESSION['subtotal_edi_pagibig_ee_shared_active_branch'] ?? 0;
        $subtotal_edi_pagibig_ee_shared_active_jewelry = $_SESSION['subtotal_edi_pagibig_ee_shared_active_jewelry'] ?? 0;

        $subtotal_edi_pagibig_er_shared_active_branch = $_SESSION['subtotal_edi_pagibig_er_shared_active_branch'] ?? 0;
        $subtotal_edi_pagibig_er_shared_active_jewelry = $_SESSION['subtotal_edi_pagibig_er_shared_active_jewelry'] ?? 0;

        $subtotal_edi_pagibig_ee_shared_closed_branch = $_SESSION['subtotal_edi_pagibig_ee_shared_closed_branch'] ?? 0;
        $subtotal_edi_pagibig_ee_shared_closed_jewelry = $_SESSION['subtotal_edi_pagibig_ee_shared_closed_jewelry'] ?? 0;

        $subtotal_edi_pagibig_er_shared_closed_branch = $_SESSION['subtotal_edi_pagibig_er_shared_closed_branch'] ?? 0;
        $subtotal_edi_pagibig_er_shared_closed_jewelry = $_SESSION['subtotal_edi_pagibig_er_shared_closed_jewelry'] ?? 0;


        $subtotal_edi_sss_ee_shared_total = $_SESSION['subtotal_edi_sss_ee_shared_total'] ?? 0;
        $subtotal_edi_sss_er_shared_total = $_SESSION['subtotal_edi_sss_er_shared_total'] ?? 0;

        $subtotal_edi_philhealth_ee_shared_total = $_SESSION['subtotal_edi_philhealth_ee_shared_total'] ?? 0;
        $subtotal_edi_philhealth_er_shared_total = $_SESSION['subtotal_edi_philhealth_er_shared_total'] ?? 0;

        $subtotal_edi_pagibig_ee_shared_total = $_SESSION['subtotal_edi_pagibig_ee_shared_total'] ?? 0;
        $subtotal_edi_pagibig_er_shared_total = $_SESSION['subtotal_edi_pagibig_er_shared_total'] ?? 0;

        $subtotal_hrmdedi_vs_edi_sss_ee_shared_variance = $_SESSION['subtotal_hrmdedi_vs_edi_sss_ee_shared_variance'] ?? 0;
        $subtotal_hrmdedi_vs_edi_sss_er_shared_variance = $_SESSION['subtotal_hrmdedi_vs_edi_sss_er_shared_variance'] ?? 0;

        $subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance = $_SESSION['subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance'] ?? 0;
        $subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance = $_SESSION['subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance'] ?? 0;

        $subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance = $_SESSION['subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance'] ?? 0;
        $subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance = $_SESSION['subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance'] ?? 0;

        //grand total
        $grandtotal_hrmdrfp_ee_shared = $_SESSION['grandtotal_hrmdrfp_ee_shared'] ?? 0;
        $grandtotal_hrmdrfp_er_shared = $_SESSION['grandtotal_hrmdrfp_er_shared'] ?? 0;
        $grandtotal_hrmdrfp_total = $_SESSION['grandtotal_hrmdrfp_total'] ?? 0;

        $grandtotal_hrmdedi_sss_ee_shared = $_SESSION['grandtotal_hrmdedi_sss_ee_shared'] ?? 0;
        $grandtotal_hrmdedi_sss_er_shared = $_SESSION['grandtotal_hrmdedi_sss_er_shared'] ?? 0;

        $grandtotal_hrmdedi_philhealth_ee_shared = $_SESSION['grandtotal_hrmdedi_philhealth_ee_shared'] ?? 0;
        $grandtotal_hrmdedi_philhealth_er_shared = $_SESSION['grandtotal_hrmdedi_philhealth_er_shared'] ?? 0;

        $grandtotal_hrmdedi_pagibig_ee_shared = $_SESSION['grandtotal_hrmdedi_pagibig_ee_shared'] ?? 0;
        $grandtotal_hrmdedi_pagibig_er_shared = $_SESSION['grandtotal_hrmdedi_pagibig_er_shared'] ?? 0;


        $grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance = $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance'] ?? 0;
        $grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance = $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance'] ?? 0;

        $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance = $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance'] ?? 0;
        $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance = $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance'] ?? 0;

        $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance = $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance'] ?? 0;
        $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance = $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance'] ?? 0;

        $grandtotal_edi_sss_ee_shared_active_branch = $_SESSION['grandtotal_edi_sss_ee_shared_active_branch'] ?? 0;
        $grandtotal_edi_sss_ee_shared_active_jewelry = $_SESSION['grandtotal_edi_sss_ee_shared_active_jewelry'] ?? 0;

        $grandtotal_edi_sss_er_shared_active_branch = $_SESSION['grandtotal_edi_sss_er_shared_active_branch'] ?? 0;
        $grandtotal_edi_sss_er_shared_active_jewelry = $_SESSION['grandtotal_edi_sss_er_shared_active_jewelry'] ?? 0;

        $grandtotal_edi_sss_ee_shared_closed_branch = $_SESSION['grandtotal_edi_sss_ee_shared_closed_branch'] ?? 0;
        $grandtotal_edi_sss_ee_shared_closed_jewelry = $_SESSION['grandtotal_edi_sss_ee_shared_closed_jewelry'] ?? 0;

        $grandtotal_edi_sss_er_shared_closed_branch = $_SESSION['grandtotal_edi_sss_er_shared_closed_branch'] ?? 0;
        $grandtotal_edi_sss_er_shared_closed_jewelry = $_SESSION['grandtotal_edi_sss_er_shared_closed_jewelry'] ?? 0;


        $grandtotal_edi_philhealth_ee_shared_active_branch = $_SESSION['grandtotal_edi_philhealth_ee_shared_active_branch'] ?? 0;
        $grandtotal_edi_philhealth_ee_shared_active_jewelry = $_SESSION['grandtotal_edi_philhealth_ee_shared_active_jewelry'] ?? 0;

        $grandtotal_edi_philhealth_er_shared_active_branch = $_SESSION['grandtotal_edi_philhealth_er_shared_active_branch'] ?? 0;
        $grandtotal_edi_philhealth_er_shared_active_jewelry = $_SESSION['grandtotal_edi_philhealth_er_shared_active_jewelry'] ?? 0;

        $grandtotal_edi_philhealth_ee_shared_closed_branch = $_SESSION['grandtotal_edi_philhealth_ee_shared_closed_branch'] ?? 0;
        $grandtotal_edi_philhealth_ee_shared_closed_jewelry = $_SESSION['grandtotal_edi_philhealth_ee_shared_closed_jewelry'] ?? 0;

        $grandtotal_edi_philhealth_er_shared_closed_branch = $_SESSION['grandtotal_edi_philhealth_er_shared_closed_branch'] ?? 0;
        $grandtotal_edi_philhealth_er_shared_closed_jewelry = $_SESSION['grandtotal_edi_philhealth_er_shared_closed_jewelry'] ?? 0;


        $grandtotal_edi_pagibig_ee_shared_active_branch = $_SESSION['grandtotal_edi_pagibig_ee_shared_active_branch'] ?? 0;
        $grandtotal_edi_pagibig_ee_shared_active_jewelry = $_SESSION['grandtotal_edi_pagibig_ee_shared_active_jewelry'] ?? 0;

        $grandtotal_edi_pagibig_er_shared_active_branch = $_SESSION['grandtotal_edi_pagibig_er_shared_active_branch'] ?? 0;
        $grandtotal_edi_pagibig_er_shared_active_jewelry = $_SESSION['grandtotal_edi_pagibig_er_shared_active_jewelry'] ?? 0;

        $grandtotal_edi_pagibig_ee_shared_closed_branch = $_SESSION['grandtotal_edi_pagibig_ee_shared_closed_branch'] ?? 0;
        $grandtotal_edi_pagibig_ee_shared_closed_jewelry = $_SESSION['grandtotal_edi_pagibig_ee_shared_closed_jewelry'] ?? 0;

        $grandtotal_edi_pagibig_er_shared_closed_branch = $_SESSION['grandtotal_edi_pagibig_er_shared_closed_branch'] ?? 0;
        $grandtotal_edi_pagibig_er_shared_closed_jewelry = $_SESSION['grandtotal_edi_pagibig_er_shared_closed_jewelry'] ?? 0;


        $grandtotal_edi_sss_ee_shared_total = $_SESSION['grandtotal_edi_sss_ee_shared_total'] ?? 0;
        $grandtotal_edi_sss_er_shared_total = $_SESSION['grandtotal_edi_sss_er_shared_total'] ?? 0;

        $grandtotal_edi_philhealth_ee_shared_total = $_SESSION['grandtotal_edi_philhealth_ee_shared_total'] ?? 0;
        $grandtotal_edi_philhealth_er_shared_total = $_SESSION['grandtotal_edi_philhealth_er_shared_total'] ?? 0;

        $grandtotal_edi_pagibig_ee_shared_total = $_SESSION['grandtotal_edi_pagibig_ee_shared_total'] ?? 0;
        $grandtotal_edi_pagibig_er_shared_total = $_SESSION['grandtotal_edi_pagibig_er_shared_total'] ?? 0;

        $grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance = $_SESSION['grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance'] ?? 0;
        $grandtotal_hrmdedi_vs_edi_sss_er_shared_variance = $_SESSION['grandtotal_hrmdedi_vs_edi_sss_er_shared_variance'] ?? 0;

        $grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance = $_SESSION['grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance'] ?? 0;
        $grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance = $_SESSION['grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance'] ?? 0;

        $grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance = $_SESSION['grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance'] ?? 0;
        $grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance = $_SESSION['grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance'] ?? 0;


        // Determine payroll day, month and year
        if (empty($payrollDay) || empty($payrollMonth) || empty($payrollYear)) {
            if (!empty($_POST['restricted-date'])) {
                $payrollDay = date('j', strtotime($_POST['restricted-date']));
                $payrollMonth = date('F', strtotime($_POST['restricted-date']));
                $payrollYear = date('Y', strtotime($_POST['restricted-date']));
            }
        }

        $dlsql = "SELECT 
            mzm.main_zone_code,
            rm.region_code, 
            rm.region_description AS region_name, 
            rm.zone_code,
            
            -- RFP Remitance contribution data
            MAX(rc.ee_shared) AS ee_shared,
            MAX(rc.er_shared) AS er_shared,
            MAX(rc.ee_shared + rc.er_shared) AS total_contribution,

            -- HRMD Remitance data table total ee shared & er shared for each region
            SUM(r.ee_dr1) AS sss_ee_shared,
            SUM(r.dr1) AS sss_er_shared,

            SUM(r.ee_dr2) AS philhealth_ee_shared,
            SUM(r.dr2) AS philhealth_er_shared,

            SUM(r.ee_dr3) AS pagibig_ee_shared,
            SUM(r.dr3) AS pagibig_er_shared,
            
            -- RFP Remitance Reports VS HRMD Remitance Reports Variance Calculation
            MAX(COALESCE(rc.ee_shared, 0)) - SUM(COALESCE(r.ee_dr1,0)) AS hr_sss_ee_shared_variance,
            MAX(COALESCE(rc.er_shared, 0)) - SUM(COALESCE(r.dr1,0)) AS hr_sss_er_shared_variance,

            MAX(COALESCE(rc.ee_shared, 0)) - SUM(COALESCE(r.ee_dr2,0)) AS hr_philhealth_ee_shared_variance,
            MAX(COALESCE(rc.er_shared, 0)) - SUM(COALESCE(r.dr2,0)) AS hr_philhealth_er_shared_variance,

            MAX(COALESCE(rc.ee_shared, 0)) - SUM(COALESCE(r.ee_dr3,0)) AS hr_pagibig_ee_shared_variance,
            MAX(COALESCE(rc.er_shared, 0)) - SUM(COALESCE(r.dr3,0)) AS hr_pagibig_er_shared_variance,
            
            -- EDI Remitance Reports data table total ee shared & er shared for each region
            MAX(COALESCE(errab.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errab.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errcb.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_CLOSED_JEWELRY,

            MAX(COALESCE(errcb.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_CLOSED_JEWELRY,


            MAX(COALESCE(errab.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errab.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_BRANCHES,
			MAX(COALESCE(errajew.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errcb.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_JEWELRY,

            MAX(COALESCE(errcb.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_JEWELRY,


            MAX(COALESCE(errab.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errab.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_JEWELRY,
         
            MAX(COALESCE(errcb.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_JEWELRY,

            MAX(COALESCE(errcb.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_JEWELRY,


            -- EDI Remitance Reports total Calculation
            (MAX(COALESCE(errab.sss_ee_shared,0)) + MAX(COALESCE(errajew.sss_ee_shared,0))) - (MAX(COALESCE(errcb.sss_ee_shared,0)) + MAX(COALESCE(errcjew.sss_ee_shared,0))) AS TOTAL_SSS_EE_SHARED_BRANCHES,
            (MAX(COALESCE(errab.sss_er_shared,0)) + MAX(COALESCE(errajew.sss_er_shared,0))) + (MAX(COALESCE(errcb.sss_er_shared,0)) + MAX(COALESCE(errcjew.sss_er_shared,0))) AS TOTAL_SSS_ER_SHARED_BRANCHES,

            (MAX(COALESCE(errab.philhealth_ee_shared,0)) + MAX(COALESCE(errajew.philhealth_ee_shared,0))) + (MAX(COALESCE(errcb.philhealth_ee_shared,0)) + MAX(COALESCE(errcjew.philhealth_ee_shared,0))) AS TOTAL_PHILHEALTH_EE_SHARED_BRANCHES,
            (MAX(COALESCE(errab.philhealth_er_shared,0)) + MAX(COALESCE(errajew.philhealth_er_shared,0))) + (MAX(COALESCE(errcb.philhealth_er_shared,0)) + MAX(COALESCE(errcjew.philhealth_er_shared,0))) AS TOTAL_PHILHEALTH_ER_SHARED_BRANCHES,

            (MAX(COALESCE(errab.pagibig_ee_shared,0)) + MAX(COALESCE(errajew.pagibig_ee_shared,0))) + (MAX(COALESCE(errcb.pagibig_ee_shared,0)) + MAX(COALESCE(errcjew.pagibig_ee_shared,0))) AS TOTAL_PAGIBIG_EE_SHARED_BRANCHES,
            (MAX(COALESCE(errab.pagibig_er_shared,0)) + MAX(COALESCE(errajew.pagibig_er_shared,0))) + (MAX(COALESCE(errcb.pagibig_er_shared,0)) + MAX(COALESCE(errcjew.pagibig_er_shared,0))) AS TOTAL_PAGIBIG_ER_SHARED_BRANCHES,

            -- EDI Remitance Reports VS HRMD Remitance Reports Variance Calculation
            (MAX(COALESCE(errab.sss_ee_shared,0)) + MAX(COALESCE(errajew.sss_ee_shared,0)) + MAX(COALESCE(errcb.sss_ee_shared,0)) + MAX(COALESCE(errcjew.sss_ee_shared,0))) - SUM(COALESCE(r.ee_dr1,0)) AS ERR_SSS_EE_SHARED_BRANCHES_VARIANCE,
            (MAX(COALESCE(errab.sss_er_shared,0)) + MAX(COALESCE(errajew.sss_er_shared,0)) + MAX(COALESCE(errcb.sss_er_shared,0)) + MAX(COALESCE(errcjew.sss_er_shared,0))) - SUM(COALESCE(r.dr1,0)) AS ERR_SSS_ER_SHARED_BRANCHES_VARIANCE,


            (MAX(COALESCE(errab.philhealth_ee_shared,0)) + MAX(COALESCE(errajew.philhealth_ee_shared,0)) + MAX(COALESCE(errcb.philhealth_ee_shared,0)) + MAX(COALESCE(errcjew.philhealth_ee_shared,0))) - SUM(COALESCE(r.ee_dr2,0)) AS ERR_PHILHEALTH_EE_SHARED_BRANCHES_VARIANCE,
            (MAX(COALESCE(errab.philhealth_er_shared,0)) + MAX(COALESCE(errajew.philhealth_er_shared,0)) + MAX(COALESCE(errcb.philhealth_er_shared,0)) + MAX(COALESCE(errcjew.philhealth_er_shared,0))) - SUM(COALESCE(r.dr2,0)) AS ERR_PHILHEALTH_ER_SHARED_BRANCHES_VARIANCE,


            (MAX(COALESCE(errab.pagibig_ee_shared,0)) + MAX(COALESCE(errajew.pagibig_ee_shared,0)) + MAX(COALESCE(errcb.pagibig_ee_shared,0)) + MAX(COALESCE(errcjew.pagibig_ee_shared,0))) - SUM(COALESCE(r.ee_dr3,0)) AS ERR_PAGIBIG_EE_SHARED_BRANCHES_VARIANCE,
            (MAX(COALESCE(errab.pagibig_er_shared,0)) + MAX(COALESCE(errajew.pagibig_er_shared,0)) + MAX(COALESCE(errcb.pagibig_er_shared,0)) + MAX(COALESCE(errcjew.pagibig_er_shared,0))) - SUM(COALESCE(r.dr3,0)) AS ERR_PAGIBIG_ER_SHARED_BRANCHES_VARIANCE,
            
			MAX(rc.remitance_type) AS remitance_type
            
        FROM 
            " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN " . $database[1] . ".region_masterfile AS rm 
            ON ((rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN') OR 
                (rm.zone_code IN ('NCR', 'LZN') AND mzm.main_zone_code = 'LNCR'))
        LEFT JOIN (
			SELECT
				c.region_code,
                SUM(c.ee_shared) AS ee_shared,
                SUM(c.er_shared) AS er_shared,
                SUM(c.total_contribution) AS total_contribution,
                c.remitance_type
			FROM
				" . $database[0] . ".remitance_contribution AS c
			WHERE 
                c.remitance_date = '$restrictedDate'
                AND c.remitance_type='$status'
                
            GROUP BY c.region_code
        ) AS rc 
            ON rm.region_code = rc.region_code
        LEFT JOIN " . $database[0] . ".remitance AS r 
            ON rm.region_code = r.region_code AND r.remitance_date = '$restrictedDate'
        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND err.ml_matic_status = 'Active'
                AND NOT(
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errab
            ON errab.region_code = rm.region_code

        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND err.ml_matic_status = 'Active'
                AND (
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errajew
            ON errajew.region_code = rm.region_code

        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND ml_matic_status IN ('Inactive', 'Pending', 'TBO')
                AND NOT(
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errcb
            ON errcb.region_code = rm.region_code

        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND ml_matic_status IN ('Inactive', 'Pending', 'TBO')
                AND (
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errcjew
            ON errcjew.region_code = rm.region_code
        WHERE mzm.main_zone_code = '$mainzone'";
        
        if (!empty($region)) {
            $dlsql .= " AND rm.region_code = '$region'";
        }
        
        $dlsql .= " GROUP BY mzm.main_zone_code, rm.region_code, rm.region_description, rm.zone_code
                            ORDER BY mzm.main_zone_code, rm.region_description;"; 

        $dlresult = mysqli_query($conn, $dlsql);
                
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
                
        if(mysqli_num_rows($dlresult) > 0) {
                
            // First row
            $sheet->setCellValue('A1', 'RECONCILIATION & VARIANCE REPORT'); 

            // Second row
            $sheet->setCellValue('A2', 'REMITTANCE REPORT'); 

            // Third row
            $sheet->setCellValue('A3', $status.' EE & ER SHARE');

            // Fourth row
            
            // Fifth row
            $sheet->setCellValue('A5', 'Remittance Date: ' . $payrollMonth . ' ' . $payrollYear)->mergeCells('A5:V5')->getStyle('A5:V5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Sixth row
            $sheet->setCellValue('A6', 'Mainzone: ' . $mainzone)->mergeCells('A6:C6')->getStyle('A6:C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            
            $sheet->setCellValue('D6', 'HRMD RFP')->mergeCells('D6:F6')->getStyle('D6:F6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('G6', 'EDI REPORT (ARIEL)')->mergeCells('G6:H6')->getStyle('G6:H6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('I6', 'HRMD VARIANCE')->mergeCells('I6:J6')->getStyle('I6:J6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('K6', 'EDI REPORT (CAD)')->mergeCells('K6:N6')->getStyle('K6:N6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('O6', 'EDI CLOSED BRANCH (CAD)')->mergeCells('O6:R6')->getStyle('O6:R6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('S6', 'EDI REPORT (CAD)')->mergeCells('S6:T6')->getStyle('S6:T6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('U6', 'EDI VARIANCE')->mergeCells('U6:V6')->getStyle('U6:V6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Seventh row
            $sheet->setCellValue('A7', 'REGION CODE')->mergeCells('A7')->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('B7', 'REGION NAME')->mergeCells('B7')->getStyle('B7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('C7', 'ZONE')->mergeCells('C7')->getStyle('C7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('D7', 'EE SHARE')->mergeCells('D7')->getStyle('D7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('E7', 'ER SHARE')->mergeCells('E7')->getStyle('E7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('F7', 'TOTAL')->mergeCells('F7')->getStyle('F7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('G7', 'EE SHARE')->mergeCells('G7')->getStyle('G7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('H7', 'ER SHARE')->mergeCells('H7')->getStyle('H7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('I7', 'EE SHARE')->mergeCells('I7')->getStyle('I7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('J7', 'ER SHARE')->mergeCells('J7')->getStyle('J7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('K7', 'EE SHARE (BRANCH)')->mergeCells('K7')->getStyle('K7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('L7', 'EE SHARE (JEWELRY)')->mergeCells('L7')->getStyle('L7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('M7', 'ER SHARE (BRANCH)')->mergeCells('M7')->getStyle('M7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('N7', 'ER SHARE (JEWELRY)')->mergeCells('N7')->getStyle('N7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('O7', 'EE SHARE (BRANCH)')->mergeCells('O7')->getStyle('O7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('P7', 'EE SHARE (JEWELRY)')->mergeCells('P7')->getStyle('P7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('Q7', 'ER SHARE (BRANCH)')->mergeCells('Q7')->getStyle('Q7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('R7', 'ER SHARE (JEWELRY)')->mergeCells('R7')->getStyle('R7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('S7', 'EE SHARE')->mergeCells('S7')->getStyle('S7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('T7', 'ER SHARE')->mergeCells('T7')->getStyle('T7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->setCellValue('U7', 'EE SHARE')->mergeCells('U7')->getStyle('U7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('V7', 'ER SHARE')->mergeCells('V7')->getStyle('V7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('A1:A3')->getFont()->setBold(true);
            $sheet->getStyle('A5:V7')->getFont()->setBold(true);
            $sheet->getStyle('A10')->getFont()->setBold(true);
            
            // Set the height of row 6
            $sheet->getRowDimension(6)->setRowHeight(64.5);
            $sheet->getRowDimension(7)->setRowHeight(64.5);


            $startRow = 6;
            $endRow = 100; // Adjust based on your data's last row

            foreach (range('D', 'F') as $col) {
                $maxWidth = 13; // Default width for these columns

                for ($row = $startRow; $row <= $endRow; $row++) {
                    $cellAddress = $col . $row;
                    $value = $sheet->getCell($cellAddress)->getFormattedValue();

                    if ($value !== null && $value !== '') {
                        $length = strlen($value);
                        $approxWidth = $length * 1.2;
                        if ($approxWidth > $maxWidth) {
                            $maxWidth = $approxWidth;
                        }
                    }
                }

                // Fallback if column was entirely empty
                if ($maxWidth == 0) {
                    $maxWidth = 10; // or any default width you prefer
                }

                $sheet->getColumnDimension($col)->setWidth($maxWidth);
            }

            foreach (['H','I','K','L', 'M', 'N','O', 'P', 'Q', 'R','U','V'] as $col) {
                $sheet->getColumnDimension($col, '6')->setAutoSize(false)->setWidth(13);
            }   

            // foreach (range('D', 'F') as $col) {
            //     $cellAddress = $col . '5';
            //     $value = $sheet->getCell($cellAddress)->getFormattedValue(); // Use getFormattedValue for visible content
            //     if ($value !== null && $value !== '') {
            //         $length = strlen($value);
            //         $approxWidth = max(13, $length * 1.2); // Adjust multiplier if needed
            //         $sheet->getColumnDimension($col)->setWidth($approxWidth);
            //     } else {
            //         // Optional: fallback width or debug output
            //         $sheet->getColumnDimension($col)->setWidth(10);
            //     }
            // }
            
            $sheet->getColumnDimension('G')->setAutoSize(false)->setWidth(10.00);
            $sheet->getColumnDimension('J')->setAutoSize(false)->setWidth(10.00);
            $sheet->getColumnDimension('S')->setAutoSize(false)->setWidth(10.00);
            $sheet->getColumnDimension('T')->setAutoSize(false)->setWidth(10.00);

            $sheet->getStyle('J6')->getAlignment()->setWrapText(true);
            $sheet->getStyle('G6:T6')->getAlignment()->setWrapText(true);

            $sheet->getStyle('A6')->getAlignment()->setWrapText(true);
            $sheet->getStyle('D6')->getAlignment()->setWrapText(true);
            $sheet->getStyle('K7:R7')->getAlignment()->setWrapText(true);

            $sheet->getColumnDimension('B')->setAutoSize(true);

            

            

            mysqli_data_seek($dlresult, 0);
            $rowIndex = 12; // Starting from the 7th row

            
            if($mainzone === 'VISMIN' && empty($region)) {
                // Seventh row
                $sheet->setCellValue('A8','-');
                $sheet->setCellValue('B8','CEBU MANCOM');
                $sheet->setCellValue('C8', 'VIS');

                // Eighth row
                $sheet->setCellValue('A9','-');
                $sheet->setCellValue('B9','CEBU SUPPORT');
                $sheet->setCellValue('C9', 'VIS');

                // Ninth row
                $sheet->setCellValue('A10','SUB-TOTAL')->mergeCells('A10:C10')->getStyle('A10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            } elseif($mainzone === 'LNCR' && empty($region)){
                // Seventh row
                $sheet->setCellValue('A8','-');
                $sheet->setCellValue('B8','MAKATI MANCOM');
                $sheet->setCellValue('C8', 'LZN');

                // Eighth row
                $sheet->setCellValue('A9','-');
                $sheet->setCellValue('B9','MAKATI SUPPORT');
                $sheet->setCellValue('C9', 'LZN');

                // Ninth row
                $sheet->setCellValue('A10','SUB-TOTAL')->mergeCells('A10:C10')->getStyle('A10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            }
            
            // Tenth row
            $sheet->setCellValue('A11','')->mergeCells('A11:V11');

            // Eleventh row
                
            while ($row = mysqli_fetch_assoc($dlresult)) {
                $sheet->setCellValue('A' . $rowIndex, $row['region_code']);
                $sheet->setCellValue('B' . $rowIndex, $row['region_name']);
                $sheet->setCellValue('C' . $rowIndex, $row['zone_code']);
                
                $sheet->setCellValue('D' . $rowIndex, $row['ee_shared']);
                $sheet->setCellValue('E' . $rowIndex, $row['er_shared']);
                $sheet->setCellValue('F' . $rowIndex, $row['total_contribution']);

                if($status === 'SSS'){
                    $sheet->setCellValue('G' . $rowIndex, $row['sss_ee_shared']);
                    $sheet->setCellValue('H' . $rowIndex, $row['sss_er_shared']);

                    $sheet->setCellValue('I' . $rowIndex, $row['hr_sss_ee_shared_variance']);
                    $sheet->setCellValue('J' . $rowIndex, $row['hr_sss_er_shared_variance']);
                    
                    $sheet->setCellValue('K' . $rowIndex, $row['ERR_TOTAL_SSS_EE_SHARED_ACTIVE_BRANCHES']);
                    $sheet->setCellValue('L' . $rowIndex, $row['ERR_TOTAL_SSS_EE_SHARED_ACTIVE_JEWELRY']);
                    $sheet->setCellValue('M' . $rowIndex, $row['ERR_TOTAL_SSS_ER_SHARED_ACTIVE_BRANCHES']);
                    $sheet->setCellValue('N' . $rowIndex, $row['ERR_TOTAL_SSS_ER_SHARED_ACTIVE_JEWELRY']);
                    
                    $sheet->setCellValue('O' . $rowIndex, $row['ERR_TOTAL_SSS_EE_SHARED_CLOSED_BRANCHES']);
                    $sheet->setCellValue('P' . $rowIndex, $row['ERR_TOTAL_SSS_EE_SHARED_CLOSED_JEWELRY']);
                    $sheet->setCellValue('Q' . $rowIndex, $row['ERR_TOTAL_SSS_ER_SHARED_CLOSED_BRANCHES']);
                    $sheet->setCellValue('R' . $rowIndex, $row['ERR_TOTAL_SSS_ER_SHARED_CLOSED_JEWELRY']);
                    
                    $sheet->setCellValue('S' . $rowIndex, $row['TOTAL_SSS_EE_SHARED_BRANCHES']);
                    $sheet->setCellValue('T' . $rowIndex, $row['TOTAL_SSS_ER_SHARED_BRANCHES']);

                    $sheet->setCellValue('U' . $rowIndex, $row['ERR_SSS_EE_SHARED_BRANCHES_VARIANCE']);
                    $sheet->setCellValue('V' . $rowIndex, $row['ERR_SSS_ER_SHARED_BRANCHES_VARIANCE']);

                }elseif($status === 'PHILHEALTH'){
                    $sheet->setCellValue('G' . $rowIndex, $row['philhealth_ee_shared']);
                    $sheet->setCellValue('H' . $rowIndex, $row['philhealth_er_shared']);

                    $sheet->setCellValue('I' . $rowIndex, $row['hr_philhealth_ee_shared_variance']);
                    $sheet->setCellValue('J' . $rowIndex, $row['hr_philhealth_er_shared_variance']);
                    
                    $sheet->setCellValue('K' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_BRANCHES']);
                    $sheet->setCellValue('L' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_JEWELRY']);
                    $sheet->setCellValue('M' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_BRANCHES']);
                    $sheet->setCellValue('N' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_JEWELRY']);
                    
                    $sheet->setCellValue('O' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_BRANCHES']);
                    $sheet->setCellValue('P' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_JEWELRY']);
                    $sheet->setCellValue('Q' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_BRANCHES']);
                    $sheet->setCellValue('R' . $rowIndex, $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_JEWELRY']);
                    
                    $sheet->setCellValue('S' . $rowIndex, $row['TOTAL_PHILHEALTH_EE_SHARED_BRANCHES']);
                    $sheet->setCellValue('T' . $rowIndex, $row['TOTAL_PHILHEALTH_ER_SHARED_BRANCHES']);

                    $sheet->setCellValue('U' . $rowIndex, $row['ERR_PHILHEALTH_EE_SHARED_BRANCHES_VARIANCE']);
                    $sheet->setCellValue('V' . $rowIndex, $row['ERR_PHILHEALTH_ER_SHARED_BRANCHES_VARIANCE']);

                }elseif($status === 'PAGIBIG'){
                    $sheet->setCellValue('G' . $rowIndex, $row['pagibig_ee_shared']);
                    $sheet->setCellValue('H' . $rowIndex, $row['pagibig_er_shared']);

                    $sheet->setCellValue('I' . $rowIndex, $row['hr_pagibig_ee_shared_variance']);
                    $sheet->setCellValue('J' . $rowIndex, $row['hr_pagibig_er_shared_variance']);
                    
                    $sheet->setCellValue('K' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_BRANCHES']);
                    $sheet->setCellValue('L' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_JEWELRY']);
                    $sheet->setCellValue('M' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_BRANCHES']);
                    $sheet->setCellValue('N' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_JEWELRY']);
                    
                    $sheet->setCellValue('O' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_BRANCHES']);
                    $sheet->setCellValue('P' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_JEWELRY']);
                    $sheet->setCellValue('Q' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_BRANCHES']);
                    $sheet->setCellValue('R' . $rowIndex, $row['ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_JEWELRY']);
                    
                    $sheet->setCellValue('S' . $rowIndex, $row['TOTAL_PAGIBIG_EE_SHARED_BRANCHES']);
                    $sheet->setCellValue('T' . $rowIndex, $row['TOTAL_PAGIBIG_ER_SHARED_BRANCHES']);

                    $sheet->setCellValue('U' . $rowIndex, $row['ERR_PAGIBIG_EE_SHARED_BRANCHES_VARIANCE']);
                    $sheet->setCellValue('V' . $rowIndex, $row['ERR_PAGIBIG_ER_SHARED_BRANCHES_VARIANCE']);

                }
                

                // Format the cell to show 2 decimal places
                $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('T' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('U' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle('V' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');

                $sheet->getStyle('A5:V'.$rowIndex)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Simulate auto-size based on just this row’s cell values
                foreach (range('D', 'V') as $col) {
                    $cellValue = $sheet->getCell($col . $rowIndex)->getValue();
                    $approxWidth = strlen((string) $cellValue) * 1.1; // Adjust multiplier as needed

                    $currentWidth = $sheet->getColumnDimension($col)->getWidth();
                    if ($approxWidth > $currentWidth) {
                        $sheet->getColumnDimension($col)->setWidth($approxWidth);
                    }
                }

                $rowIndex++;

                $region_name = $row['region_name'];
                $_SESSION['region_name'] = $region_name;
            }
            
            $sheet->getStyle('A'. $rowIndex)->getFont()->setBold(true);
            $sheet->getStyle('A'. ($rowIndex+1))->getFont()->setBold(true);

            $sheet->setCellValue('A'. $rowIndex,'SUB-TOTAL')->mergeCells('A'. $rowIndex.':C'.$rowIndex)->getStyle('A'.$rowIndex)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('D' . $rowIndex, $subtotal_hrmdrfp_ee_shared);
            $sheet->setCellValue('E' . $rowIndex, $subtotal_hrmdrfp_er_shared);
            $sheet->setCellValue('F' . $rowIndex, $subtotal_hrmdrfp_total);

            if($status === 'SSS'){
                $sheet->setCellValue('G' . $rowIndex, $subtotal_hrmdedi_sss_ee_shared);
                $sheet->setCellValue('H' . $rowIndex, $subtotal_hrmdedi_sss_er_shared);

                $sheet->setCellValue('I' . $rowIndex, $subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance);
                $sheet->setCellValue('J' . $rowIndex, $subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance);
                
                $sheet->setCellValue('K' . $rowIndex, $subtotal_edi_sss_ee_shared_active_branch);
                $sheet->setCellValue('L' . $rowIndex, $subtotal_edi_sss_ee_shared_active_jewelry);
                $sheet->setCellValue('M' . $rowIndex, $subtotal_edi_sss_er_shared_active_branch);
                $sheet->setCellValue('N' . $rowIndex, $subtotal_edi_sss_er_shared_active_jewelry);

                $sheet->setCellValue('O' . $rowIndex, $subtotal_edi_sss_ee_shared_closed_branch);
                $sheet->setCellValue('P' . $rowIndex, $subtotal_edi_sss_ee_shared_closed_jewelry);
                $sheet->setCellValue('Q' . $rowIndex, $subtotal_edi_sss_er_shared_closed_branch);
                $sheet->setCellValue('R' . $rowIndex, $subtotal_edi_sss_er_shared_closed_jewelry);

                $sheet->setCellValue('S' . $rowIndex, $subtotal_edi_sss_ee_shared_total);
                $sheet->setCellValue('T' . $rowIndex, $subtotal_edi_sss_er_shared_total);

                $sheet->setCellValue('U' . $rowIndex, $subtotal_hrmdedi_vs_edi_sss_ee_shared_variance);
                $sheet->setCellValue('V' . $rowIndex, $subtotal_hrmdedi_vs_edi_sss_er_shared_variance);

            }elseif($status === 'PHILHEALTH'){
                $sheet->setCellValue('G' . $rowIndex, $subtotal_hrmdedi_philhealth_ee_shared);
                $sheet->setCellValue('H' . $rowIndex, $subtotal_hrmdedi_philhealth_er_shared);

                $sheet->setCellValue('I' . $rowIndex, $subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance);
                $sheet->setCellValue('J' . $rowIndex, $subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance);
                
                $sheet->setCellValue('K' . $rowIndex, $subtotal_edi_philhealth_ee_shared_active_branch);
                $sheet->setCellValue('L' . $rowIndex, $subtotal_edi_philhealth_ee_shared_active_jewelry);
                $sheet->setCellValue('M' . $rowIndex, $subtotal_edi_philhealth_er_shared_active_branch);
                $sheet->setCellValue('N' . $rowIndex, $subtotal_edi_philhealth_er_shared_active_jewelry);

                $sheet->setCellValue('O' . $rowIndex, $subtotal_edi_philhealth_ee_shared_closed_branch);
                $sheet->setCellValue('P' . $rowIndex, $subtotal_edi_philhealth_ee_shared_closed_jewelry);
                $sheet->setCellValue('Q' . $rowIndex, $subtotal_edi_philhealth_er_shared_closed_branch);
                $sheet->setCellValue('R' . $rowIndex, $subtotal_edi_philhealth_er_shared_closed_jewelry);

                $sheet->setCellValue('S' . $rowIndex, $subtotal_edi_philhealth_ee_shared_total);
                $sheet->setCellValue('T' . $rowIndex, $subtotal_edi_philhealth_er_shared_total);

                $sheet->setCellValue('U' . $rowIndex, $subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance);
                $sheet->setCellValue('V' . $rowIndex, $subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance);

            }elseif($status === 'PAGIBIG'){
                $sheet->setCellValue('G' . $rowIndex, $subtotal_hrmdedi_pagibig_ee_shared);
                $sheet->setCellValue('H' . $rowIndex, $subtotal_hrmdedi_pagibig_er_shared);

                $sheet->setCellValue('I' . $rowIndex, $subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance);
                $sheet->setCellValue('J' . $rowIndex, $subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance);
                
                $sheet->setCellValue('K' . $rowIndex, $subtotal_edi_pagibig_ee_shared_active_branch);
                $sheet->setCellValue('L' . $rowIndex, $subtotal_edi_pagibig_ee_shared_active_jewelry);
                $sheet->setCellValue('M' . $rowIndex, $subtotal_edi_pagibig_er_shared_active_branch);
                $sheet->setCellValue('N' . $rowIndex, $subtotal_edi_pagibig_er_shared_active_jewelry);

                $sheet->setCellValue('O' . $rowIndex, $subtotal_edi_pagibig_ee_shared_closed_branch);
                $sheet->setCellValue('P' . $rowIndex, $subtotal_edi_pagibig_ee_shared_closed_jewelry);
                $sheet->setCellValue('Q' . $rowIndex, $subtotal_edi_pagibig_er_shared_closed_branch);
                $sheet->setCellValue('R' . $rowIndex, $subtotal_edi_pagibig_er_shared_closed_jewelry);

                $sheet->setCellValue('S' . $rowIndex, $subtotal_edi_pagibig_ee_shared_total);
                $sheet->setCellValue('T' . $rowIndex, $subtotal_edi_pagibig_er_shared_total);

                $sheet->setCellValue('U' . $rowIndex, $subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance);
                $sheet->setCellValue('V' . $rowIndex, $subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance);
            }
            


            $sheet->setCellValue('A'. ($rowIndex+1),'GRAND TOTAL')->mergeCells('A'. ($rowIndex+1).':C'.($rowIndex+1))->getStyle('A'.($rowIndex+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('D' . ($rowIndex+1), $grandtotal_hrmdrfp_ee_shared);
            $sheet->setCellValue('E' . ($rowIndex+1), $grandtotal_hrmdrfp_er_shared);
            $sheet->setCellValue('F' . ($rowIndex+1), $grandtotal_hrmdrfp_total);

            if($status === 'SSS'){
                $sheet->setCellValue('G' . ($rowIndex+1), $grandtotal_hrmdedi_sss_ee_shared);
                $sheet->setCellValue('H' . ($rowIndex+1), $grandtotal_hrmdedi_sss_er_shared);

                $sheet->setCellValue('I' . ($rowIndex+1), $grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance);
                $sheet->setCellValue('J' . ($rowIndex+1), $grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance);

                $sheet->setCellValue('K' . ($rowIndex+1), $grandtotal_edi_sss_ee_shared_active_branch);
                $sheet->setCellValue('L' . ($rowIndex+1), $grandtotal_edi_sss_ee_shared_active_jewelry);
                $sheet->setCellValue('M' . ($rowIndex+1), $grandtotal_edi_sss_er_shared_active_branch);
                $sheet->setCellValue('N' . ($rowIndex+1), $grandtotal_edi_sss_er_shared_active_jewelry);

                $sheet->setCellValue('O' . ($rowIndex+1), $grandtotal_edi_sss_ee_shared_closed_branch);
                $sheet->setCellValue('P' . ($rowIndex+1), $grandtotal_edi_sss_ee_shared_closed_jewelry);
                $sheet->setCellValue('Q' . ($rowIndex+1), $grandtotal_edi_sss_er_shared_closed_branch);
                $sheet->setCellValue('R' . ($rowIndex+1), $grandtotal_edi_sss_er_shared_closed_jewelry);

                $sheet->setCellValue('S' . ($rowIndex+1), $grandtotal_edi_sss_ee_shared_total);
                $sheet->setCellValue('T' . ($rowIndex+1), $grandtotal_edi_sss_er_shared_total);

                $sheet->setCellValue('U' . ($rowIndex+1), $grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance);
                $sheet->setCellValue('V' . ($rowIndex+1), $grandtotal_hrmdedi_vs_edi_sss_er_shared_variance);

            }elseif($status === 'PHILHEALTH'){
                $sheet->setCellValue('G' . ($rowIndex+1), $grandtotal_hrmdedi_philhealth_ee_shared);
                $sheet->setCellValue('H' . ($rowIndex+1), $grandtotal_hrmdedi_philhealth_er_shared);

                $sheet->setCellValue('I' . ($rowIndex+1), $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance);
                $sheet->setCellValue('J' . ($rowIndex+1), $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance);

                $sheet->setCellValue('K' . ($rowIndex+1), $grandtotal_edi_philhealth_ee_shared_active_branch);
                $sheet->setCellValue('L' . ($rowIndex+1), $grandtotal_edi_philhealth_ee_shared_active_jewelry);
                $sheet->setCellValue('M' . ($rowIndex+1), $grandtotal_edi_philhealth_er_shared_active_branch);
                $sheet->setCellValue('N' . ($rowIndex+1), $grandtotal_edi_philhealth_er_shared_active_jewelry);

                $sheet->setCellValue('O' . ($rowIndex+1), $grandtotal_edi_philhealth_ee_shared_closed_branch);
                $sheet->setCellValue('P' . ($rowIndex+1), $grandtotal_edi_philhealth_ee_shared_closed_jewelry);
                $sheet->setCellValue('Q' . ($rowIndex+1), $grandtotal_edi_philhealth_er_shared_closed_branch);
                $sheet->setCellValue('R' . ($rowIndex+1), $grandtotal_edi_philhealth_er_shared_closed_jewelry);

                $sheet->setCellValue('S' . ($rowIndex+1), $grandtotal_edi_philhealth_ee_shared_total);
                $sheet->setCellValue('T' . ($rowIndex+1), $grandtotal_edi_philhealth_er_shared_total);

                $sheet->setCellValue('U' . ($rowIndex+1), $grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance);
                $sheet->setCellValue('V' . ($rowIndex+1), $grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance);

            }elseif($status === 'PAGIBIG'){
                $sheet->setCellValue('G' . ($rowIndex+1), $grandtotal_hrmdedi_pagibig_ee_shared);
                $sheet->setCellValue('H' . ($rowIndex+1), $grandtotal_hrmdedi_pagibig_er_shared);

                $sheet->setCellValue('I' . ($rowIndex+1), $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance);
                $sheet->setCellValue('J' . ($rowIndex+1), $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance);

                $sheet->setCellValue('K' . ($rowIndex+1), $grandtotal_edi_pagibig_ee_shared_active_branch);
                $sheet->setCellValue('L' . ($rowIndex+1), $grandtotal_edi_pagibig_ee_shared_active_jewelry);
                $sheet->setCellValue('M' . ($rowIndex+1), $grandtotal_edi_pagibig_er_shared_active_branch);
                $sheet->setCellValue('N' . ($rowIndex+1), $grandtotal_edi_pagibig_er_shared_active_jewelry);

                $sheet->setCellValue('O' . ($rowIndex+1), $grandtotal_edi_pagibig_ee_shared_closed_branch);
                $sheet->setCellValue('P' . ($rowIndex+1), $grandtotal_edi_pagibig_ee_shared_closed_jewelry);
                $sheet->setCellValue('Q' . ($rowIndex+1), $grandtotal_edi_pagibig_er_shared_closed_branch);
                $sheet->setCellValue('R' . ($rowIndex+1), $grandtotal_edi_pagibig_er_shared_closed_jewelry);

                $sheet->setCellValue('S' . ($rowIndex+1), $grandtotal_edi_pagibig_ee_shared_total);
                $sheet->setCellValue('T' . ($rowIndex+1), $grandtotal_edi_pagibig_er_shared_total);

                $sheet->setCellValue('U' . ($rowIndex+1), $grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance);
                $sheet->setCellValue('V' . ($rowIndex+1), $grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance);

            }
            


            // Format the cell to show 2 decimal places
            $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('L' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('M' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('N' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('O' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('P' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('Q' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('R' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('S' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('T' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('U' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('V' . $rowIndex)->getNumberFormat()->setFormatCode('#,##0.00');


            $sheet->getStyle('D' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('G' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('I' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('L' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('M' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('N' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('O' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('P' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('Q' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('R' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('S' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('T' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('U' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('V' . ($rowIndex+1))->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Set borders for the last two rows
            $sheet->getStyle('A'.$rowIndex.':V'.($rowIndex+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            
            $region_name = $_SESSION['region_name'];
            if(!empty($region)) {
                $filename = "RECON_&_VARIANCE_Remittance_Report_" . $mainzone . "_" . $region_name . "-[".$region."]_(" . $payrollMonth . " " . $payrollYear . ").xls";
            }else{
                $filename = "RECON_&_VARIANCE_Remittance_Report_" . $mainzone . "_(" . $payrollMonth . " " . $payrollYear . ").xls";
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
    </style>
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>Remittance Report <span style="font-size: 22px; color: red;">[RECON & VARIANCE-Format]</span></h2></center>

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
                <label for="status">Status</label>
                <select name="status" id="status" required autocomplete="off">
                    <option value="">Select Status</option>
                    <option value="SSS">SSS</option>
                    <option value="PHILHEALTH">PHILHEALTH</option>
                    <option value="PAGIBIG">PAG-IBIG</option>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>

        <div id="showdl1" style="display: none">
            <form id="exportForm" action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>
    </div>

    <script src="<?php echo $relative_path; ?>assets/js/admin/remitance-recon/recon-variance-format/remitance-recon-script.js"></script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $restrictedDate = $_POST['restricted-date'];
    $status = $_POST['status'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;
    $_SESSION['status'] = $status;

    // Determine payroll day, month and year
    if (!empty($_POST['restricted-date'])) {
        $payrollDay = date('j', strtotime($_POST['restricted-date']));
        $_SESSION['payroll_day'] = $payrollDay;
    }
    if (!empty($_POST['restricted-date'])) {
        $payrollMonth = date('F', strtotime($_POST['restricted-date']));
        $_SESSION['payroll_month'] = $payrollMonth;
    }
    if (!empty($_POST['restricted-date'])) {
        $payrollYear = date('Y', strtotime($_POST['restricted-date']));
        $_SESSION['payroll_year'] = $payrollYear;
    }

    if ($mainzone) {
        $sql = "SELECT 
            mzm.main_zone_code,
            rm.region_code, 
            rm.region_description AS region_name, 
            rm.zone_code,
            
            -- RFP Remitance contribution data
            MAX(rc.ee_shared) AS ee_shared,
            MAX(rc.er_shared) AS er_shared,
            MAX(rc.ee_shared + rc.er_shared) AS total_contribution,

            -- HRMD Remitance data table total ee shared & er shared for each region
            SUM(r.ee_dr1) AS sss_ee_shared,
            SUM(r.dr1) AS sss_er_shared,

            SUM(r.ee_dr2) AS philhealth_ee_shared,
            SUM(r.dr2) AS philhealth_er_shared,

            SUM(r.ee_dr3) AS pagibig_ee_shared,
            SUM(r.dr3) AS pagibig_er_shared,
            
            -- RFP Remitance Reports VS HRMD Remitance Reports Variance Calculation
            MAX(COALESCE(rc.ee_shared, 0)) - SUM(COALESCE(r.ee_dr1,0)) AS hr_sss_ee_shared_variance,
            MAX(COALESCE(rc.er_shared, 0)) - SUM(COALESCE(r.dr1,0)) AS hr_sss_er_shared_variance,

            MAX(COALESCE(rc.ee_shared, 0)) - SUM(COALESCE(r.ee_dr2,0)) AS hr_philhealth_ee_shared_variance,
            MAX(COALESCE(rc.er_shared, 0)) - SUM(COALESCE(r.dr2,0)) AS hr_philhealth_er_shared_variance,

            MAX(COALESCE(rc.ee_shared, 0)) - SUM(COALESCE(r.ee_dr3,0)) AS hr_pagibig_ee_shared_variance,
            MAX(COALESCE(rc.er_shared, 0)) - SUM(COALESCE(r.dr3,0)) AS hr_pagibig_er_shared_variance,
            
            -- EDI Remitance Reports data table total ee shared & er shared for each region
            MAX(COALESCE(errab.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errab.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errcb.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.sss_ee_shared,0)) AS ERR_TOTAL_SSS_EE_SHARED_CLOSED_JEWELRY,

            MAX(COALESCE(errcb.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.sss_er_shared,0)) AS ERR_TOTAL_SSS_ER_SHARED_CLOSED_JEWELRY,


            MAX(COALESCE(errab.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errab.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_BRANCHES,
			MAX(COALESCE(errajew.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errcb.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.philhealth_ee_shared,0)) AS ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_JEWELRY,

            MAX(COALESCE(errcb.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.philhealth_er_shared,0)) AS ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_JEWELRY,


            MAX(COALESCE(errab.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_JEWELRY,

            MAX(COALESCE(errab.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_BRANCHES,
            MAX(COALESCE(errajew.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_JEWELRY,
         
            MAX(COALESCE(errcb.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.pagibig_ee_shared,0)) AS ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_JEWELRY,

            MAX(COALESCE(errcb.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_BRANCHES,
            MAX(COALESCE(errcjew.pagibig_er_shared,0)) AS ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_JEWELRY,


            -- EDI Remitance Reports total Calculation
            (MAX(COALESCE(errab.sss_ee_shared,0)) + MAX(COALESCE(errajew.sss_ee_shared,0))) - (MAX(COALESCE(errcb.sss_ee_shared,0)) + MAX(COALESCE(errcjew.sss_ee_shared,0))) AS TOTAL_SSS_EE_SHARED_BRANCHES,
            (MAX(COALESCE(errab.sss_er_shared,0)) + MAX(COALESCE(errajew.sss_er_shared,0))) + (MAX(COALESCE(errcb.sss_er_shared,0)) + MAX(COALESCE(errcjew.sss_er_shared,0))) AS TOTAL_SSS_ER_SHARED_BRANCHES,

            (MAX(COALESCE(errab.philhealth_ee_shared,0)) + MAX(COALESCE(errajew.philhealth_ee_shared,0))) + (MAX(COALESCE(errcb.philhealth_ee_shared,0)) + MAX(COALESCE(errcjew.philhealth_ee_shared,0))) AS TOTAL_PHILHEALTH_EE_SHARED_BRANCHES,
            (MAX(COALESCE(errab.philhealth_er_shared,0)) + MAX(COALESCE(errajew.philhealth_er_shared,0))) + (MAX(COALESCE(errcb.philhealth_er_shared,0)) + MAX(COALESCE(errcjew.philhealth_er_shared,0))) AS TOTAL_PHILHEALTH_ER_SHARED_BRANCHES,

            (MAX(COALESCE(errab.pagibig_ee_shared,0)) + MAX(COALESCE(errajew.pagibig_ee_shared,0))) + (MAX(COALESCE(errcb.pagibig_ee_shared,0)) + MAX(COALESCE(errcjew.pagibig_ee_shared,0))) AS TOTAL_PAGIBIG_EE_SHARED_BRANCHES,
            (MAX(COALESCE(errab.pagibig_er_shared,0)) + MAX(COALESCE(errajew.pagibig_er_shared,0))) + (MAX(COALESCE(errcb.pagibig_er_shared,0)) + MAX(COALESCE(errcjew.pagibig_er_shared,0))) AS TOTAL_PAGIBIG_ER_SHARED_BRANCHES,

            -- EDI Remitance Reports VS HRMD Remitance Reports Variance Calculation
            (MAX(COALESCE(errab.sss_ee_shared,0)) + MAX(COALESCE(errajew.sss_ee_shared,0)) + MAX(COALESCE(errcb.sss_ee_shared,0)) + MAX(COALESCE(errcjew.sss_ee_shared,0))) - SUM(COALESCE(r.ee_dr1,0)) AS ERR_SSS_EE_SHARED_BRANCHES_VARIANCE,
            (MAX(COALESCE(errab.sss_er_shared,0)) + MAX(COALESCE(errajew.sss_er_shared,0)) + MAX(COALESCE(errcb.sss_er_shared,0)) + MAX(COALESCE(errcjew.sss_er_shared,0))) - SUM(COALESCE(r.dr1,0)) AS ERR_SSS_ER_SHARED_BRANCHES_VARIANCE,


            (MAX(COALESCE(errab.philhealth_ee_shared,0)) + MAX(COALESCE(errajew.philhealth_ee_shared,0)) + MAX(COALESCE(errcb.philhealth_ee_shared,0)) + MAX(COALESCE(errcjew.philhealth_ee_shared,0))) - SUM(COALESCE(r.ee_dr2,0)) AS ERR_PHILHEALTH_EE_SHARED_BRANCHES_VARIANCE,
            (MAX(COALESCE(errab.philhealth_er_shared,0)) + MAX(COALESCE(errajew.philhealth_er_shared,0)) + MAX(COALESCE(errcb.philhealth_er_shared,0)) + MAX(COALESCE(errcjew.philhealth_er_shared,0))) - SUM(COALESCE(r.dr2,0)) AS ERR_PHILHEALTH_ER_SHARED_BRANCHES_VARIANCE,


            (MAX(COALESCE(errab.pagibig_ee_shared,0)) + MAX(COALESCE(errajew.pagibig_ee_shared,0)) + MAX(COALESCE(errcb.pagibig_ee_shared,0)) + MAX(COALESCE(errcjew.pagibig_ee_shared,0))) - SUM(COALESCE(r.ee_dr3,0)) AS ERR_PAGIBIG_EE_SHARED_BRANCHES_VARIANCE,
            (MAX(COALESCE(errab.pagibig_er_shared,0)) + MAX(COALESCE(errajew.pagibig_er_shared,0)) + MAX(COALESCE(errcb.pagibig_er_shared,0)) + MAX(COALESCE(errcjew.pagibig_er_shared,0))) - SUM(COALESCE(r.dr3,0)) AS ERR_PAGIBIG_ER_SHARED_BRANCHES_VARIANCE,
            
			MAX(rc.remitance_type) AS remitance_type
            
        FROM 
            " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN " . $database[1] . ".region_masterfile AS rm 
            ON ((rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN') OR 
                (rm.zone_code IN ('NCR', 'LZN') AND mzm.main_zone_code = 'LNCR'))
        LEFT JOIN (
			SELECT
				c.region_code,
                SUM(c.ee_shared) AS ee_shared,
                SUM(c.er_shared) AS er_shared,
                SUM(c.total_contribution) AS total_contribution,
                c.remitance_type
			FROM
				" . $database[0] . ".remitance_contribution AS c
			WHERE 
                c.remitance_date = '$restrictedDate'
                AND c.remitance_type='$status'
                
            GROUP BY c.region_code
        ) AS rc 
            ON rm.region_code = rc.region_code
        LEFT JOIN " . $database[0] . ".remitance AS r 
            ON rm.region_code = r.region_code AND r.remitance_date = '$restrictedDate'
        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND err.ml_matic_status = 'Active'
                AND NOT(
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errab
            ON errab.region_code = rm.region_code

        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND err.ml_matic_status = 'Active'
                AND (
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errajew
            ON errajew.region_code = rm.region_code

        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND ml_matic_status IN ('Inactive', 'Pending', 'TBO')
                AND NOT(
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errcb
            ON errcb.region_code = rm.region_code

        LEFT JOIN (
            SELECT 
                err.region_code,
                SUM(err.ee_dr1) AS sss_ee_shared,
                SUM(err.dr1) AS sss_er_shared,

                SUM(err.ee_dr2) AS philhealth_ee_shared,
                SUM(err.dr2) AS philhealth_er_shared,

                SUM(err.ee_dr3) AS pagibig_ee_shared,
                SUM(err.dr3) AS pagibig_er_shared

            FROM " . $database[0] . ".remitance_edi_report AS err
            WHERE 
                err.remitance_date = '$restrictedDate' 
                AND ml_matic_status IN ('Inactive', 'Pending', 'TBO')
                AND (
                    (err.zone IN ('VIS','JVIS', 'MIN') AND err.ml_matic_region = 'VISMIN Showroom') OR
                    (err.zone IN ('LZN', 'NCR') AND err.ml_matic_region = 'LNCR Showroom')
                )
            GROUP BY err.region_code
        ) AS errcjew
            ON errcjew.region_code = rm.region_code
        WHERE mzm.main_zone_code = '$mainzone'";
        
        if (!empty($region)) {
            $sql .= " AND rm.region_code = '$region'";
        }
        
        $sql .= " GROUP BY mzm.main_zone_code, rm.region_code, rm.region_description, rm.zone_code
                            ORDER BY mzm.main_zone_code, rm.region_description;";
    }  
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

        // Initialize sub-totals for only Cebu Mancom and Cebu Support


        // Initialize sub-totals for per region
        $subtotal_hrmdrfp_ee_shared = 0;
        $subtotal_hrmdrfp_er_shared = 0;
        $subtotal_hrmdrfp_total = 0;

        
        $subtotal_hrmdedi_sss_ee_shared = 0;
        $subtotal_hrmdedi_sss_er_shared = 0;
        $subtotal_hrmdedi_sss_total = 0;

        $subtotal_hrmdedi_philhealth_ee_shared = 0;
        $subtotal_hrmdedi_philhealth_er_shared = 0;
        $subtotal_hrmdedi_philhealth_total = 0;

        $subtotal_hrmdedi_pagibig_ee_shared = 0;
        $subtotal_hrmdedi_pagibig_er_shared = 0;
        $subtotal_hrmdedi_pagibig_total = 0;

        $subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance = 0;
        $subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance = 0;

        $subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance = 0;
        $subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance = 0;

        $subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance = 0;
        $subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance = 0;


        $subtotal_edi_sss_ee_shared_active_branch = 0;
        $subtotal_edi_sss_ee_shared_active_jewelry = 0;

        $subtotal_edi_sss_er_shared_active_branch = 0;
        $subtotal_edi_sss_er_shared_active_jewelry = 0;

        $subtotal_edi_sss_ee_shared_closed_branch = 0;
        $subtotal_edi_sss_ee_shared_closed_jewelry = 0;

        $subtotal_edi_sss_er_shared_closed_branch = 0;
        $subtotal_edi_sss_er_shared_closed_jewelry = 0;
        
        $subtotal_edi_philhealth_ee_shared_active_branch = 0;
        $subtotal_edi_philhealth_ee_shared_active_jewelry = 0;

        $subtotal_edi_philhealth_er_shared_active_branch = 0;
        $subtotal_edi_philhealth_er_shared_active_jewelry = 0;

        $subtotal_edi_philhealth_ee_shared_closed_branch = 0;
        $subtotal_edi_philhealth_ee_shared_closed_jewelry = 0;

        $subtotal_edi_philhealth_er_shared_closed_branch = 0;
        $subtotal_edi_philhealth_er_shared_closed_jewelry = 0;
        
        $subtotal_edi_pagibig_ee_shared_active_branch = 0;
        $subtotal_edi_pagibig_ee_shared_active_jewelry = 0;

        $subtotal_edi_pagibig_er_shared_active_branch = 0;
        $subtotal_edi_pagibig_er_shared_active_jewelry = 0;

        $subtotal_edi_pagibig_ee_shared_closed_branch = 0;
        $subtotal_edi_pagibig_ee_shared_closed_jewelry = 0;

        $subtotal_edi_pagibig_er_shared_closed_branch = 0;
        $subtotal_edi_pagibig_er_shared_closed_jewelry = 0;


        $subtotal_edi_sss_ee_shared_total = 0;
        $subtotal_edi_sss_er_shared_total = 0;

        $subtotal_edi_philhealth_ee_shared_total = 0;
        $subtotal_edi_philhealth_er_shared_total = 0;

        $subtotal_edi_pagibig_ee_shared_total = 0;
        $subtotal_edi_pagibig_er_shared_total = 0;


        $subtotal_hrmdedi_vs_edi_sss_ee_shared_variance = 0;
        $subtotal_hrmdedi_vs_edi_sss_er_shared_variance = 0;

        $subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance = 0;
        $subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance = 0;

        $subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance = 0;
        $subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance = 0;




        

        // Initialize grand totals
        
        $grandtotal_hrmdrfp_ee_shared = 0;
        $grandtotal_hrmdrfp_er_shared = 0;
        $grandtotal_hrmdrfp_total = 0;

        
        $grandtotal_hrmdedi_sss_ee_shared = 0;
        $grandtotal_hrmdedi_sss_er_shared = 0;
        $grandtotal_hrmdedi_sss_total = 0;

        $grandtotal_hrmdedi_philhealth_ee_shared = 0;
        $grandtotal_hrmdedi_philhealth_er_shared = 0;
        $grandtotal_hrmdedi_philhealth_total = 0;

        $grandtotal_hrmdedi_pagibig_ee_shared = 0;
        $grandtotal_hrmdedi_pagibig_er_shared = 0;
        $grandtotal_hrmdedi_pagibig_total = 0;

        $grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance = 0;
        $grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance = 0;

        $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance = 0;
        $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance = 0;

        $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance = 0;
        $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance = 0;


        $grandtotal_edi_sss_ee_shared_active_branch = 0;
        $grandtotal_edi_sss_ee_shared_active_jewelry = 0;

        $grandtotal_edi_sss_er_shared_active_branch = 0;
        $grandtotal_edi_sss_er_shared_active_jewelry = 0;

        $grandtotal_edi_sss_ee_shared_closed_branch = 0;
        $grandtotal_edi_sss_ee_shared_closed_jewelry = 0;

        $grandtotal_edi_sss_er_shared_closed_branch = 0;
        $grandtotal_edi_sss_er_shared_closed_jewelry = 0;
        
        $grandtotal_edi_philhealth_ee_shared_active_branch = 0;
        $grandtotal_edi_philhealth_ee_shared_active_jewelry = 0;

        $grandtotal_edi_philhealth_er_shared_active_branch = 0;
        $grandtotal_edi_philhealth_er_shared_active_jewelry = 0;

        $grandtotal_edi_philhealth_ee_shared_closed_branch = 0;
        $grandtotal_edi_philhealth_ee_shared_closed_jewelry = 0;

        $grandtotal_edi_philhealth_er_shared_closed_branch = 0;
        $grandtotal_edi_philhealth_er_shared_closed_jewelry = 0;
        
        $grandtotal_edi_pagibig_ee_shared_active_branch = 0;
        $grandtotal_edi_pagibig_ee_shared_active_jewelry = 0;

        $grandtotal_edi_pagibig_er_shared_active_branch = 0;
        $grandtotal_edi_pagibig_er_shared_active_jewelry = 0;

        $grandtotal_edi_pagibig_ee_shared_closed_branch = 0;
        $grandtotal_edi_pagibig_ee_shared_closed_jewelry = 0;

        $grandtotal_edi_pagibig_er_shared_closed_branch = 0;
        $grandtotal_edi_pagibig_er_shared_closed_jewelry = 0;


        $grandtotal_edi_sss_ee_shared_total = 0;
        $grandtotal_edi_sss_er_shared_total = 0;

        $grandtotal_edi_philhealth_ee_shared_total = 0;
        $grandtotal_edi_philhealth_er_shared_total = 0;

        $grandtotal_edi_pagibig_ee_shared_total = 0;
        $grandtotal_edi_pagibig_er_shared_total = 0;


        $grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance = 0;
        $grandtotal_hrmdedi_vs_edi_sss_er_shared_variance = 0;

        $grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance = 0;
        $grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance = 0;

        $grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance = 0;
        $grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance = 0;

        foreach($result as $row){
            // HRMD RFP Remitance contribution data
            $subtotal_hrmdrfp_ee_shared += $row['ee_shared'];
            $subtotal_hrmdrfp_er_shared += $row['er_shared'];
            $subtotal_hrmdrfp_total += $row['ee_shared'] + $row['er_shared'];

            // HRMD EDI Remitance contribution data
            $subtotal_hrmdedi_sss_ee_shared += $row['sss_ee_shared'];
            $subtotal_hrmdedi_sss_er_shared += $row['sss_er_shared'];

            $subtotal_hrmdedi_philhealth_ee_shared += $row['philhealth_ee_shared'];
            $subtotal_hrmdedi_philhealth_er_shared += $row['philhealth_er_shared'];

            $subtotal_hrmdedi_pagibig_ee_shared += $row['pagibig_ee_shared'];
            $subtotal_hrmdedi_pagibig_er_shared += $row['pagibig_er_shared'];

            // Variance Calculation hrmdrfp vs hrmdedi
            $subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance += $row['hr_sss_ee_shared_variance'];
            $subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance += $row['hr_sss_er_shared_variance'];

            $subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance += $row['hr_philhealth_ee_shared_variance'];
            $subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance += $row['hr_philhealth_er_shared_variance'];

            $subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance += $row['hr_pagibig_ee_shared_variance'];
            $subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance += $row['hr_pagibig_er_shared_variance'];

            // EDI Remitance contribution data
            $subtotal_edi_sss_ee_shared_active_branch += $row['ERR_TOTAL_SSS_EE_SHARED_ACTIVE_BRANCHES'];
            $subtotal_edi_sss_ee_shared_active_jewelry += $row['ERR_TOTAL_SSS_EE_SHARED_ACTIVE_JEWELRY'];

            $subtotal_edi_sss_er_shared_active_branch += $row['ERR_TOTAL_SSS_ER_SHARED_ACTIVE_BRANCHES'];
            $subtotal_edi_sss_er_shared_active_jewelry += $row['ERR_TOTAL_SSS_ER_SHARED_ACTIVE_JEWELRY'];

            $subtotal_edi_sss_ee_shared_closed_branch += $row['ERR_TOTAL_SSS_EE_SHARED_CLOSED_BRANCHES'];
            $subtotal_edi_sss_ee_shared_closed_jewelry += $row['ERR_TOTAL_SSS_EE_SHARED_CLOSED_JEWELRY'];

            $subtotal_edi_sss_er_shared_closed_branch += $row['ERR_TOTAL_SSS_ER_SHARED_CLOSED_BRANCHES'];
            $subtotal_edi_sss_er_shared_closed_jewelry += $row['ERR_TOTAL_SSS_ER_SHARED_CLOSED_JEWELRY'];
            
            $subtotal_edi_philhealth_ee_shared_active_branch += $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_BRANCHES'];
            $subtotal_edi_philhealth_ee_shared_active_jewelry += $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_JEWELRY'];

            $subtotal_edi_philhealth_er_shared_active_branch += $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_BRANCHES'];
            $subtotal_edi_philhealth_er_shared_active_jewelry += $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_JEWELRY'];

            $subtotal_edi_philhealth_ee_shared_closed_branch += $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_BRANCHES'];
            $subtotal_edi_philhealth_ee_shared_closed_jewelry += $row['ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_JEWELRY'];

            $subtotal_edi_philhealth_er_shared_closed_branch += $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_BRANCHES'];
            $subtotal_edi_philhealth_er_shared_closed_jewelry += $row['ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_JEWELRY'];
            
            $subtotal_edi_pagibig_ee_shared_active_branch += $row['ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_BRANCHES'];
            $subtotal_edi_pagibig_ee_shared_active_jewelry += $row['ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_JEWELRY'];

            $subtotal_edi_pagibig_er_shared_active_branch += $row['ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_BRANCHES'];
            $subtotal_edi_pagibig_er_shared_active_jewelry += $row['ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_JEWELRY'];

            $subtotal_edi_pagibig_ee_shared_closed_branch += $row['ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_BRANCHES'];
            $subtotal_edi_pagibig_ee_shared_closed_jewelry += $row['ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_JEWELRY'];

            $subtotal_edi_pagibig_er_shared_closed_branch += $row['ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_BRANCHES'];
            $subtotal_edi_pagibig_er_shared_closed_jewelry += $row['ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_JEWELRY'];
            

            // EDI Remitance contribution data total
            $subtotal_edi_sss_ee_shared_total += $row['TOTAL_SSS_EE_SHARED_BRANCHES'];
            $subtotal_edi_sss_er_shared_total += $row['TOTAL_SSS_ER_SHARED_BRANCHES'];

            $subtotal_edi_philhealth_ee_shared_total += $row['TOTAL_PHILHEALTH_EE_SHARED_BRANCHES'];
            $subtotal_edi_philhealth_er_shared_total += $row['TOTAL_PHILHEALTH_ER_SHARED_BRANCHES'];

            $subtotal_edi_pagibig_ee_shared_total += $row['TOTAL_PAGIBIG_EE_SHARED_BRANCHES'];
            $subtotal_edi_pagibig_er_shared_total += $row['TOTAL_PAGIBIG_ER_SHARED_BRANCHES'];

            // Variance Calculation hrmdedi vs edi
            $subtotal_hrmdedi_vs_edi_sss_ee_shared_variance += $row['ERR_SSS_EE_SHARED_BRANCHES_VARIANCE'];
            $subtotal_hrmdedi_vs_edi_sss_er_shared_variance += $row['ERR_SSS_ER_SHARED_BRANCHES_VARIANCE'];

            $subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance += $row['ERR_PHILHEALTH_EE_SHARED_BRANCHES_VARIANCE'];
            $subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance += $row['ERR_PHILHEALTH_ER_SHARED_BRANCHES_VARIANCE'];

            $subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance += $row['ERR_PAGIBIG_EE_SHARED_BRANCHES_VARIANCE'];
            $subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance += $row['ERR_PAGIBIG_ER_SHARED_BRANCHES_VARIANCE'];


            // Grand total calculation
            $grandtotal_hrmdrfp_ee_shared = $subtotal_hrmdrfp_ee_shared;
            $grandtotal_hrmdrfp_er_shared = $subtotal_hrmdrfp_er_shared;
            $grandtotal_hrmdrfp_total = $subtotal_hrmdrfp_total;

            
            $grandtotal_hrmdedi_sss_ee_shared = $subtotal_hrmdedi_sss_ee_shared;
            $grandtotal_hrmdedi_sss_er_shared = $subtotal_hrmdedi_sss_er_shared;

            $grandtotal_hrmdedi_philhealth_ee_shared = $subtotal_hrmdedi_philhealth_ee_shared;
            $grandtotal_hrmdedi_philhealth_er_shared = $subtotal_hrmdedi_philhealth_er_shared;

            $grandtotal_hrmdedi_pagibig_ee_shared = $subtotal_hrmdedi_pagibig_ee_shared;
            $grandtotal_hrmdedi_pagibig_er_shared = $subtotal_hrmdedi_pagibig_er_shared;

            $grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance = $subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance;
            $grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance = $subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance;

            $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance = $subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance;
            $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance = $subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance;

            $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance = $subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance;
            $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance = $subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance;


            $grandtotal_edi_sss_ee_shared_active_branch = $subtotal_edi_sss_ee_shared_active_branch;
            $grandtotal_edi_sss_ee_shared_active_jewelry = $subtotal_edi_sss_ee_shared_active_jewelry;

            $grandtotal_edi_sss_er_shared_active_branch = $subtotal_edi_sss_er_shared_active_branch;
            $grandtotal_edi_sss_er_shared_active_jewelry = $subtotal_edi_sss_er_shared_active_jewelry;

            $grandtotal_edi_sss_ee_shared_closed_branch = $subtotal_edi_sss_ee_shared_closed_branch;
            $grandtotal_edi_sss_ee_shared_closed_jewelry = $subtotal_edi_sss_ee_shared_closed_jewelry;

            $grandtotal_edi_sss_er_shared_closed_branch = $subtotal_edi_sss_er_shared_closed_branch;
            $grandtotal_edi_sss_er_shared_closed_jewelry = $subtotal_edi_sss_er_shared_closed_jewelry;
            
            $grandtotal_edi_philhealth_ee_shared_active_branch = $subtotal_edi_philhealth_ee_shared_active_branch;
            $grandtotal_edi_philhealth_ee_shared_active_jewelry = $subtotal_edi_philhealth_ee_shared_active_jewelry;

            $grandtotal_edi_philhealth_er_shared_active_branch = $subtotal_edi_philhealth_er_shared_active_branch;
            $grandtotal_edi_philhealth_er_shared_active_jewelry = $subtotal_edi_philhealth_er_shared_active_jewelry;

            $grandtotal_edi_philhealth_ee_shared_closed_branch = $subtotal_edi_philhealth_ee_shared_closed_branch;
            $grandtotal_edi_philhealth_ee_shared_closed_jewelry = $subtotal_edi_philhealth_ee_shared_closed_jewelry;

            $grandtotal_edi_philhealth_er_shared_closed_branch = $subtotal_edi_philhealth_er_shared_closed_branch;
            $grandtotal_edi_philhealth_er_shared_closed_jewelry = $subtotal_edi_philhealth_er_shared_closed_jewelry;
            
            $grandtotal_edi_pagibig_ee_shared_active_branch = $subtotal_edi_pagibig_ee_shared_active_branch;
            $grandtotal_edi_pagibig_ee_shared_active_jewelry = $subtotal_edi_pagibig_ee_shared_active_jewelry;

            $grandtotal_edi_pagibig_er_shared_active_branch = $subtotal_edi_pagibig_er_shared_active_branch;
            $grandtotal_edi_pagibig_er_shared_active_jewelry = $subtotal_edi_pagibig_er_shared_active_jewelry;

            $grandtotal_edi_pagibig_ee_shared_closed_branch = $subtotal_edi_pagibig_ee_shared_closed_branch;
            $grandtotal_edi_pagibig_ee_shared_closed_jewelry = $subtotal_edi_pagibig_ee_shared_closed_jewelry;

            $grandtotal_edi_pagibig_er_shared_closed_branch = $subtotal_edi_pagibig_er_shared_closed_branch;
            $grandtotal_edi_pagibig_er_shared_closed_jewelry = $subtotal_edi_pagibig_er_shared_closed_jewelry;


            $grandtotal_edi_sss_ee_shared_total = $subtotal_edi_sss_ee_shared_total;
            $grandtotal_edi_sss_er_shared_total = $subtotal_edi_sss_er_shared_total;

            $grandtotal_edi_philhealth_ee_shared_total = $subtotal_edi_philhealth_ee_shared_total;
            $grandtotal_edi_philhealth_er_shared_total = $subtotal_edi_philhealth_er_shared_total;

            $grandtotal_edi_pagibig_ee_shared_total = $subtotal_edi_pagibig_ee_shared_total;
            $grandtotal_edi_pagibig_er_shared_total = $subtotal_edi_pagibig_er_shared_total;


            $grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance = $subtotal_hrmdedi_vs_edi_sss_ee_shared_variance;
            $grandtotal_hrmdedi_vs_edi_sss_er_shared_variance = $subtotal_hrmdedi_vs_edi_sss_er_shared_variance;

            $grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance = $subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance;
            $grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance = $subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance;

            $grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance = $subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance;
            $grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance = $subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance;
        }

        $_SESSION['subtotal_hrmdrfp_ee_shared'] = $subtotal_hrmdrfp_ee_shared;
        $_SESSION['subtotal_hrmdrfp_er_shared'] = $subtotal_hrmdrfp_er_shared;
        $_SESSION['subtotal_hrmdrfp_total'] = $subtotal_hrmdrfp_total;


        $_SESSION['subtotal_hrmdedi_sss_ee_shared'] = $subtotal_hrmdedi_sss_ee_shared;
        $_SESSION['subtotal_hrmdedi_sss_er_shared'] = $subtotal_hrmdedi_sss_er_shared;

        $_SESSION['subtotal_hrmdedi_philhealth_ee_shared'] = $subtotal_hrmdedi_philhealth_ee_shared;
        $_SESSION['subtotal_hrmdedi_philhealth_er_shared'] = $subtotal_hrmdedi_philhealth_er_shared;

        $_SESSION['subtotal_hrmdedi_pagibig_ee_shared'] = $subtotal_hrmdedi_pagibig_ee_shared;
        $_SESSION['subtotal_hrmdedi_pagibig_er_shared'] = $subtotal_hrmdedi_pagibig_er_shared;


        $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance'] = $subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance;
        $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance'] = $subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance;

        $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance'] = $subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance;
        $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance'] = $subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance;

        $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance'] = $subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance;
        $_SESSION['subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance'] = $subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance;


        $_SESSION['subtotal_edi_sss_ee_shared_active_branch'] = $subtotal_edi_sss_ee_shared_active_branch;
        $_SESSION['subtotal_edi_sss_ee_shared_active_jewelry'] = $subtotal_edi_sss_ee_shared_active_jewelry;

        $_SESSION['subtotal_edi_sss_er_shared_active_branch'] = $subtotal_edi_sss_er_shared_active_branch;
        $_SESSION['subtotal_edi_sss_er_shared_active_jewelry'] = $subtotal_edi_sss_er_shared_active_jewelry;

        $_SESSION['subtotal_edi_sss_ee_shared_closed_branch'] = $subtotal_edi_sss_ee_shared_closed_branch;
        $_SESSION['subtotal_edi_sss_ee_shared_closed_jewelry'] = $subtotal_edi_sss_ee_shared_closed_jewelry;

        $_SESSION['subtotal_edi_sss_er_shared_closed_branch'] = $subtotal_edi_sss_er_shared_closed_branch;
        $_SESSION['subtotal_edi_sss_er_shared_closed_jewelry'] = $subtotal_edi_sss_er_shared_closed_jewelry;


        $_SESSION['subtotal_edi_philhealth_ee_shared_active_branch'] = $subtotal_edi_philhealth_ee_shared_active_branch;
        $_SESSION['subtotal_edi_philhealth_ee_shared_active_jewelry'] = $subtotal_edi_philhealth_ee_shared_active_jewelry;

        $_SESSION['subtotal_edi_philhealth_er_shared_active_branch'] = $subtotal_edi_philhealth_er_shared_active_branch;
        $_SESSION['subtotal_edi_philhealth_er_shared_active_jewelry'] = $subtotal_edi_philhealth_er_shared_active_jewelry;

        $_SESSION['subtotal_edi_philhealth_ee_shared_closed_branch'] = $subtotal_edi_philhealth_ee_shared_closed_branch;
        $_SESSION['subtotal_edi_philhealth_ee_shared_closed_jewelry'] = $subtotal_edi_philhealth_ee_shared_closed_jewelry;

        $_SESSION['subtotal_edi_philhealth_er_shared_closed_branch'] = $subtotal_edi_philhealth_er_shared_closed_branch;
        $_SESSION['subtotal_edi_philhealth_er_shared_closed_jewelry'] = $subtotal_edi_philhealth_er_shared_closed_jewelry;


        $_SESSION['subtotal_edi_pagibig_ee_shared_active_branch'] = $subtotal_edi_pagibig_ee_shared_active_branch;
        $_SESSION['subtotal_edi_pagibig_ee_shared_active_jewelry'] = $subtotal_edi_pagibig_ee_shared_active_jewelry;

        $_SESSION['subtotal_edi_pagibig_er_shared_active_branch'] = $subtotal_edi_pagibig_er_shared_active_branch;
        $_SESSION['subtotal_edi_pagibig_er_shared_active_jewelry'] = $subtotal_edi_pagibig_er_shared_active_jewelry;

        $_SESSION['subtotal_edi_pagibig_ee_shared_closed_branch'] = $subtotal_edi_pagibig_ee_shared_closed_branch;
        $_SESSION['subtotal_edi_pagibig_ee_shared_closed_jewelry'] = $subtotal_edi_pagibig_ee_shared_closed_jewelry;

        $_SESSION['subtotal_edi_pagibig_er_shared_closed_branch'] = $subtotal_edi_pagibig_er_shared_closed_branch;
        $_SESSION['subtotal_edi_pagibig_er_shared_closed_jewelry'] = $subtotal_edi_pagibig_er_shared_closed_jewelry;


        $_SESSION['subtotal_edi_sss_ee_shared_total'] = $subtotal_edi_sss_ee_shared_total;
        $_SESSION['subtotal_edi_sss_er_shared_total'] = $subtotal_edi_sss_er_shared_total;

        $_SESSION['subtotal_edi_philhealth_ee_shared_total'] = $subtotal_edi_philhealth_ee_shared_total;
        $_SESSION['subtotal_edi_philhealth_er_shared_total'] = $subtotal_edi_philhealth_er_shared_total;

        $_SESSION['subtotal_edi_pagibig_ee_shared_total'] = $subtotal_edi_pagibig_ee_shared_total;
        $_SESSION['subtotal_edi_pagibig_er_shared_total'] = $subtotal_edi_pagibig_er_shared_total;


        $_SESSION['subtotal_hrmdedi_vs_edi_sss_ee_shared_variance'] = $subtotal_hrmdedi_vs_edi_sss_ee_shared_variance;
        $_SESSION['subtotal_hrmdedi_vs_edi_sss_er_shared_variance'] = $subtotal_hrmdedi_vs_edi_sss_er_shared_variance;

        $_SESSION['subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance'] = $subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance;
        $_SESSION['subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance'] = $subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance;

        $_SESSION['subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance'] = $subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance;
        $_SESSION['subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance'] = $subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance;


        // grand total calculation
        $_SESSION['grandtotal_hrmdrfp_ee_shared'] = $grandtotal_hrmdrfp_ee_shared;
        $_SESSION['grandtotal_hrmdrfp_er_shared'] = $grandtotal_hrmdrfp_er_shared;
        $_SESSION['grandtotal_hrmdrfp_total'] = $grandtotal_hrmdrfp_total;


        $_SESSION['grandtotal_hrmdedi_sss_ee_shared'] = $grandtotal_hrmdedi_sss_ee_shared;
        $_SESSION['grandtotal_hrmdedi_sss_er_shared'] = $grandtotal_hrmdedi_sss_er_shared;

        $_SESSION['grandtotal_hrmdedi_philhealth_ee_shared'] = $grandtotal_hrmdedi_philhealth_ee_shared;
        $_SESSION['grandtotal_hrmdedi_philhealth_er_shared'] = $grandtotal_hrmdedi_philhealth_er_shared;

        $_SESSION['grandtotal_hrmdedi_pagibig_ee_shared'] = $grandtotal_hrmdedi_pagibig_ee_shared;
        $_SESSION['grandtotal_hrmdedi_pagibig_er_shared'] = $grandtotal_hrmdedi_pagibig_er_shared;


        $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance'] = $grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance;
        $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance'] = $grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance;

        $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance'] = $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance;
        $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance'] = $grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance;

        $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance'] = $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance;
        $_SESSION['grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance'] = $grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance;


        $_SESSION['grandtotal_edi_sss_ee_shared_active_branch'] = $grandtotal_edi_sss_ee_shared_active_branch;
        $_SESSION['grandtotal_edi_sss_ee_shared_active_jewelry'] = $grandtotal_edi_sss_ee_shared_active_jewelry;

        $_SESSION['grandtotal_edi_sss_er_shared_active_branch'] = $grandtotal_edi_sss_er_shared_active_branch;
        $_SESSION['grandtotal_edi_sss_er_shared_active_jewelry'] = $grandtotal_edi_sss_er_shared_active_jewelry;

        $_SESSION['grandtotal_edi_sss_ee_shared_closed_branch'] = $grandtotal_edi_sss_ee_shared_closed_branch;
        $_SESSION['grandtotal_edi_sss_ee_shared_closed_jewelry'] = $grandtotal_edi_sss_ee_shared_closed_jewelry;

        $_SESSION['grandtotal_edi_sss_er_shared_closed_branch'] = $grandtotal_edi_sss_er_shared_closed_branch;
        $_SESSION['grandtotal_edi_sss_er_shared_closed_jewelry'] = $grandtotal_edi_sss_er_shared_closed_jewelry;


        $_SESSION['grandtotal_edi_philhealth_ee_shared_active_branch'] = $grandtotal_edi_philhealth_ee_shared_active_branch;
        $_SESSION['grandtotal_edi_philhealth_ee_shared_active_jewelry'] = $grandtotal_edi_philhealth_ee_shared_active_jewelry;

        $_SESSION['grandtotal_edi_philhealth_er_shared_active_branch'] = $grandtotal_edi_philhealth_er_shared_active_branch;
        $_SESSION['grandtotal_edi_philhealth_er_shared_active_jewelry'] = $grandtotal_edi_philhealth_er_shared_active_jewelry;

        $_SESSION['grandtotal_edi_philhealth_ee_shared_closed_branch'] = $grandtotal_edi_philhealth_ee_shared_closed_branch;
        $_SESSION['grandtotal_edi_philhealth_ee_shared_closed_jewelry'] = $grandtotal_edi_philhealth_ee_shared_closed_jewelry;

        $_SESSION['grandtotal_edi_philhealth_er_shared_closed_branch'] = $grandtotal_edi_philhealth_er_shared_closed_branch;
        $_SESSION['grandtotal_edi_philhealth_er_shared_closed_jewelry'] = $grandtotal_edi_philhealth_er_shared_closed_jewelry;


        $_SESSION['grandtotal_edi_pagibig_ee_shared_active_branch'] = $grandtotal_edi_pagibig_ee_shared_active_branch;
        $_SESSION['grandtotal_edi_pagibig_ee_shared_active_jewelry'] = $grandtotal_edi_pagibig_ee_shared_active_jewelry;

        $_SESSION['grandtotal_edi_pagibig_er_shared_active_branch'] = $grandtotal_edi_pagibig_er_shared_active_branch;
        $_SESSION['grandtotal_edi_pagibig_er_shared_active_jewelry'] = $grandtotal_edi_pagibig_er_shared_active_jewelry;

        $_SESSION['grandtotal_edi_pagibig_ee_shared_closed_branch'] = $grandtotal_edi_pagibig_ee_shared_closed_branch;
        $_SESSION['grandtotal_edi_pagibig_ee_shared_closed_jewelry'] = $grandtotal_edi_pagibig_ee_shared_closed_jewelry;

        $_SESSION['grandtotal_edi_pagibig_er_shared_closed_branch'] = $grandtotal_edi_pagibig_er_shared_closed_branch;
        $_SESSION['grandtotal_edi_pagibig_er_shared_closed_jewelry'] = $grandtotal_edi_pagibig_er_shared_closed_jewelry;


        $_SESSION['grandtotal_edi_sss_ee_shared_total'] = $grandtotal_edi_sss_ee_shared_total;
        $_SESSION['grandtotal_edi_sss_er_shared_total'] = $grandtotal_edi_sss_er_shared_total;

        $_SESSION['grandtotal_edi_philhealth_ee_shared_total'] = $grandtotal_edi_philhealth_ee_shared_total;
        $_SESSION['grandtotal_edi_philhealth_er_shared_total'] = $grandtotal_edi_philhealth_er_shared_total;

        $_SESSION['grandtotal_edi_pagibig_ee_shared_total'] = $grandtotal_edi_pagibig_ee_shared_total;
        $_SESSION['grandtotal_edi_pagibig_er_shared_total'] = $grandtotal_edi_pagibig_er_shared_total;


        $_SESSION['grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance'] = $grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance;
        $_SESSION['grandtotal_hrmdedi_vs_edi_sss_er_shared_variance'] = $grandtotal_hrmdedi_vs_edi_sss_er_shared_variance;

        $_SESSION['grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance'] = $grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance;
        $_SESSION['grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance'] = $grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance;

        $_SESSION['grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance'] = $grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance;
        $_SESSION['grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance'] = $grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance;


        // Check if there are results
        if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
                echo "<table>";
                    echo "<thead>";

                        $first_row = mysqli_fetch_assoc($result);

                        //  first row
                            echo "<tr>";
                                echo "<th colspan='22'> Remittance Date: " . $payrollMonth . " " . $payrollYear . "</th>";
                            echo "</tr>";
                        //  second row
                        echo "<tr>";
                            echo "<th colspan='3'>" . $mainzone . " (".$status.")</th>";
                            echo "<th colspan='3'>HRMD RFP</th>";
                            echo "<th colspan='2'>EDI REPORT (ARIEL)</th>";
                            echo "<th colspan='2'>HRMD VARIANCE</th>";

                            echo "<th colspan='4'>EDI REPORT (CAD)</th>";
                            echo "<th colspan='4'>EDI CLOSED BRANCH (CAD)</th>";
                            echo "<th colspan='2'>TOTAL EDI</th>";
                            echo "<th colspan='2'>EDI VARIANCE</th>";
                        echo "</tr>";
                        // third row
                        echo "<tr>";
                            echo "<th>Region Code</th>";
                            echo "<th>Region Name</th>";
                            echo "<th>Zone</th>";

                            echo "<th>EE SHARE</th>";
                            echo "<th>ER SHARE</th>";
                            echo "<th>Total</th>";

                            echo "<th>EE SHARE</th>";
                            echo "<th>ER SHARE</th>";

                            echo "<th>EE SHARE</th>";
                            echo "<th>ER SHARE</th>";

                            echo "<th>EE SHARE (BRANCH)</th>";
                            echo "<th>EE SHARE (JEWELRY)</th>";
                            echo "<th>ER SHARE (BRANCH)</th>";
                            echo "<th>ER SHARE (JEWELRY)</th>";

                            echo "<th>EE SHARE (BRANCH)</th>";
                            echo "<th>EE SHARE (JEWELRY)</th>";
                            echo "<th>ER SHARE (BRANCH)</th>";
                            echo "<th>ER SHARE (JEWELRY)</th>";

                            echo "<th>EE SHARE</th>";
                            echo "<th>ER SHARE</th>";

                            echo "<th>EE SHARE</th>";
                            echo "<th>ER SHARE</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";

                        $totalNumberOfBranches = 0;
            
                        // Output the data rows
                        mysqli_data_seek($result, 0); // Reset result pointer to the beginning

                        if ($mainzone === 'VISMIN' && empty($region)) {
                            echo "<tr>";
                                echo "<td>-</td>";
                                echo "<td>CEBU MANCOM</td>";
                                echo "<td>VIS</td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td>-</td>";
                                echo "<td>CEBU SUPPORT</td>";
                                echo "<td>VIS</td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                            echo "</tr>";
                        }elseif($mainzone === 'LNCR' && empty($region)){
                            echo "<tr>";
                                echo "<td>-</td>";
                                echo "<td>MAKATI MANCOM</td>";
                                echo "<td>LZN</td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td>-</td>";
                                echo "<td>MAKATI SUPPORT</td>";
                                echo "<td>LZN</td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                            echo "</tr>";
                        }
                        if(empty($region)){
                            echo "<tr>";
                                echo "<td colspan='3'><b>SUB-TOTAL</b></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                                echo "<td></td>";
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td colspan='20'></td>";
                            echo "</tr>";
                        }
                        while ($row = mysqli_fetch_assoc($result)) {

                            $totalNumberOfBranches++;

                            echo "<tr>";
                                echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row['region_code']) . "</td>";
                                echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row['region_name']) . "</td>";
                                echo "<td style='white-space: nowrap;'>" . htmlspecialchars($row['zone_code']) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ee_shared'], 2)) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['er_shared'], 2)) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['total_contribution'], 2)) . "</td>";
                                if ($status == 'SSS') {
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['sss_ee_shared'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['sss_er_shared'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['hr_sss_ee_shared_variance'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['hr_sss_er_shared_variance'], 2)) . "</td>"; 

                                    
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_EE_SHARED_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_EE_SHARED_ACTIVE_JEWELRY'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_ER_SHARED_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_ER_SHARED_ACTIVE_JEWELRY'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_EE_SHARED_CLOSED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_EE_SHARED_CLOSED_JEWELRY'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_ER_SHARED_CLOSED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_SSS_ER_SHARED_CLOSED_JEWELRY'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['TOTAL_SSS_EE_SHARED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['TOTAL_SSS_ER_SHARED_BRANCHES'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_SSS_EE_SHARED_BRANCHES_VARIANCE'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_SSS_ER_SHARED_BRANCHES_VARIANCE'], 2)) . "</td>";

                                }elseif ($status == 'PHILHEALTH') {
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['philhealth_ee_shared'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['philhealth_er_shared'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['hr_philhealth_ee_shared_variance'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['hr_philhealth_er_shared_variance'], 2)) . "</td>"; 

                                    
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_EE_SHARED_ACTIVE_JEWELRY'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_ER_SHARED_ACTIVE_JEWELRY'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_EE_SHARED_CLOSED_JEWELRY'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PHILHEALTH_ER_SHARED_CLOSED_JEWELRY'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['TOTAL_PHILHEALTH_EE_SHARED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['TOTAL_PHILHEALTH_ER_SHARED_BRANCHES'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_PHILHEALTH_EE_SHARED_BRANCHES_VARIANCE'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_PHILHEALTH_ER_SHARED_BRANCHES_VARIANCE'], 2)) . "</td>";
                                }elseif ($status == 'PAGIBIG') {
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['pagibig_ee_shared'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['pagibig_er_shared'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['hr_pagibig_ee_shared_variance'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['hr_pagibig_er_shared_variance'], 2)) . "</td>"; 

                                    
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_EE_SHARED_ACTIVE_JEWELRY'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_ER_SHARED_ACTIVE_JEWELRY'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_EE_SHARED_CLOSED_JEWELRY'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_TOTAL_PAGIBIG_ER_SHARED_CLOSED_JEWELRY'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['TOTAL_PAGIBIG_EE_SHARED_BRANCHES'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['TOTAL_PAGIBIG_ER_SHARED_BRANCHES'], 2)) . "</td>";

                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_PAGIBIG_EE_SHARED_BRANCHES_VARIANCE'], 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($row['ERR_PAGIBIG_ER_SHARED_BRANCHES_VARIANCE'], 2)) . "</td>";
                                }
                                
                            echo "</tr>";
                        }
                            echo "<tr>";
                                echo "<td colspan='3'><b>SUB-TOTAL</b></td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_ee_shared, 2)) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_er_shared, 2)) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_total, 2)) . "</td>";
                                if($status === 'SSS'){
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_sss_ee_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_sss_er_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_ee_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_ee_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_er_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_er_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_ee_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_ee_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_er_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_er_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_ee_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_sss_er_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_vs_edi_sss_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_vs_edi_sss_er_shared_variance, 2)) . "</td>";
                                }elseif($status === 'PHILHEALTH'){
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_philhealth_ee_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_philhealth_er_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_ee_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_ee_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_er_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_er_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_ee_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_ee_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_er_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_er_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_ee_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_philhealth_er_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_vs_edi_philhealth_er_shared_variance, 2)) . "</td>";
                                }elseif($status === 'PAGIBIG'){
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_pagibig_ee_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_pagibig_er_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_ee_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_ee_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_er_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_er_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_ee_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_ee_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_er_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_er_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_ee_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_edi_pagibig_er_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($subtotal_hrmdedi_vs_edi_pagibig_er_shared_variance, 2)) . "</td>";
                                }
                                
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td colspan='3'><b>GRAND TOTAL</b></td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_ee_shared, 2)) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_er_shared, 2)) . "</td>";
                                echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_total, 2)) . "</td>";
                                if($status === 'SSS'){
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_sss_ee_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_sss_er_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_vs_hrmdedi_sss_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_vs_hrmdedi_sss_er_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_ee_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_ee_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_er_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_er_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_ee_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_ee_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_er_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_er_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_ee_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_sss_er_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_vs_edi_sss_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_vs_edi_sss_er_shared_variance, 2)) . "</td>";
                                }elseif($status === 'PHILHEALTH'){
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_philhealth_ee_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_philhealth_er_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_vs_hrmdedi_philhealth_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_vs_hrmdedi_philhealth_er_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_ee_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_ee_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_er_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_er_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_ee_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_ee_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_er_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_er_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_ee_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_philhealth_er_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_vs_edi_philhealth_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_vs_edi_philhealth_er_shared_variance, 2)) . "</td>";
                                }elseif($status === 'PAGIBIG'){
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_pagibig_ee_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_pagibig_er_shared, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_vs_hrmdedi_pagibig_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdrfp_vs_hrmdedi_pagibig_er_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_ee_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_ee_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_er_shared_active_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_er_shared_active_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_ee_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_ee_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_er_shared_closed_branch, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_er_shared_closed_jewelry, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_ee_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_edi_pagibig_er_shared_total, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_vs_edi_pagibig_ee_shared_variance, 2)) . "</td>";
                                    echo "<td style='text-align: right;'>" . htmlspecialchars(number_format($grandtotal_hrmdedi_vs_edi_pagibig_er_shared_variance, 2)) . "</td>";
                                }
                            echo "</tr>";
                    echo "</tbody>";
                echo "</table>";
            echo "</div>";

            echo "<script>
            
            var dlbutton = document.getElementById('showdl');
            dlbutton.style.display = 'block';
            
            </script>";
			
			echo "<script>
            
            var dlbutton = document.getElementById('showdl1');
            dlbutton.style.display = 'block';
            
            </script>";

            echo "<div id='showBranches' style='display: block; position: absolute; top: 190px; color: red; left: 20px;'>";
            echo "Total Number of Regions : $totalNumberOfBranches";
            echo "</div>";
        } else {
            echo "No results found.";
        }

    // Close the connection
    mysqli_close($conn);
}

?>