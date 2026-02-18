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
    
        generateDownload($conn, $database, $mainzone, $region, $restrictedDate);
        
    }
 
    // Function to generate the download excel file
    function generateDownload($conn, $database, $mainzone, $region, $restrictedDate) {
        $mainzone = $_SESSION['mainzone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        $payrollDay = $_SESSION['payroll_day'] ?? '';
        $payrollMonth = $_SESSION['payroll_month'] ?? '';
        $payrollYear = $_SESSION['payroll_year'] ?? '';

        $subtotal_mcash_wallet = $_SESSION['subtotal_mcash_wallet'] ?? 0;
        $subtotal_mlkp = $_SESSION['subtotal_mlkp'] ?? 0;
        $subtotal_hrmd_rfp_total = $_SESSION['subtotal_hrmd_rfp_total'] ?? 0;

        $subtotal_gross_income_hrmd_edi_payroll = $_SESSION['subtotal_gross_income_hrmd_edi_payroll'] ?? 0;
        $subtotal_gross_deduction_hrmd_edi_payroll = $_SESSION['subtotal_gross_deduction_hrmd_edi_payroll'] ?? 0;
        $subtotal_hrmd_edi_payroll_net_pay = $_SESSION['subtotal_hrmd_edi_payroll_net_pay'] ?? 0;

        $subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll = $_SESSION['subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll'] ?? 0;

        $subtotal_gross_income_active_branch_cad_edi_report_payroll = $_SESSION['subtotal_gross_income_active_branch_cad_edi_report_payroll'] ?? 0;
        $subtotal_gross_deduction_active_branch_cad_edi_report_payroll = $_SESSION['subtotal_gross_deduction_active_branch_cad_edi_report_payroll'] ?? 0;
        $subtotal_gross_income_active_jewelry_cad_edi_report_payroll = $_SESSION['subtotal_gross_income_active_jewelry_cad_edi_report_payroll'] ?? 0;
        $subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll = $_SESSION['subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll'] ?? 0;

        $subtotal_gross_income_closed_branch_cad_edi_report_payroll = $_SESSION['subtotal_gross_income_closed_branch_cad_edi_report_payroll'] ?? 0;
        $subtotal_gross_deduction_closed_branch_cad_edi_report_payroll = $_SESSION['subtotal_gross_deduction_closed_branch_cad_edi_report_payroll'] ?? 0;
        $subtotal_gross_income_closed_jewelry_cad_edi_report_payroll = $_SESSION['subtotal_gross_income_closed_jewelry_cad_edi_report_payroll'] ?? 0;
        $subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll = $_SESSION['subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll'] ?? 0;

        $subtotal_cad_edi_report_payroll_net_pay = $_SESSION['subtotal_cad_edi_report_payroll_net_pay'] ?? 0;

        $subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll = $_SESSION['subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll'] ?? 0;



        $grandtotal_mcash_wallet = $_SESSION['grandtotal_mcash_wallet'] ?? 0;
        $grandtotal_mlkp = $_SESSION['grandtotal_mlkp'] ?? 0;
        $grandtotal_hrmd_rfp_total = $_SESSION['grandtotal_hrmd_rfp_total'] ?? 0;

        $grandtotal_gross_income_hrmd_edi_payroll = $_SESSION['grandtotal_gross_income_hrmd_edi_payroll'] ?? 0;
        $grandtotal_gross_deduction_hrmd_edi_payroll = $_SESSION['grandtotal_gross_deduction_hrmd_edi_payroll'] ?? 0;
        $grandtotal_hrmd_edi_payroll_net_pay = $_SESSION['grandtotal_hrmd_edi_payroll_net_pay'] ?? 0;

        $grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll = $_SESSION['grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll'] ?? 0;

        $grandtotal_gross_income_active_branch_cad_edi_report_payroll = $_SESSION['grandtotal_gross_income_active_branch_cad_edi_report_payroll'] ?? 0;
        $grandtotal_gross_deduction_active_branch_cad_edi_report_payroll = $_SESSION['grandtotal_gross_deduction_active_branch_cad_edi_report_payroll'] ?? 0;
        $grandtotal_gross_income_active_jewelry_cad_edi_report_payroll = $_SESSION['grandtotal_gross_income_active_jewelry_cad_edi_report_payroll'] ?? 0;
        $grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll = $_SESSION['grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll'] ?? 0;

        $grandtotal_gross_income_closed_branch_cad_edi_report_payroll = $_SESSION['grandtotal_gross_income_closed_branch_cad_edi_report_payroll'] ?? 0;
        $grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll = $_SESSION['grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll'] ?? 0;
        $grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll = $_SESSION['grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll'] ?? 0;
        $grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll = $_SESSION['grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll'] ?? 0;

        $grandtotal_cad_edi_report_payroll_net_pay = $_SESSION['grandtotal_cad_edi_report_payroll_net_pay'] ?? 0;

        $grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll = $_SESSION['grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll'] ?? 0;

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
                    
                    -- Mcash data
                    MAX(mc.mlwallet_amount) AS mlwallet_amount,
                    MAX(mc.mlkp_amount) AS mlkp_amount,
                    MAX(mc.mlwallet_amount + mc.mlkp_amount) AS total_amount,
                    
                    -- Payroll total income for each region
                    SUM(p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) AS P_TOTAL_INCOME,
                    
                    SUM(p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions) AS P_TOTAL_DEDUCTION,
                    
                    SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) - 
                        (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)) AS P_TOTAL_NET_PAY,
                    
                    -- Variance Calculation
                    MAX(COALESCE(mc.mlwallet_amount, 0) + COALESCE(mc.mlkp_amount, 0)) -
                    SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) - 
                        (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)) AS P_VARIANCE,

                    MAX(eprab.total_income) AS EPR_TOTAL_INCOME_ACTIVE_BRANCHES,
                    MAX(eprab.total_deduction) AS EPR_TOTAL_DEDUCTION_ACTIVE_BRANCHES,
                    MAX(eprajew.total_income) AS EPR_TOTAL_INCOME_ACTIVE_JEWELRY,
                    MAX(eprajew.total_deduction) AS EPR_TOTAL_DEDUCTION_ACTIVE_JEWELRY,

                    MAX(eprcb.total_income) AS EPR_TOTAL_INCOME_CLOSED_BRANCH,
                    MAX(eprcb.total_deduction) AS EPR_TOTAL_DEDUCTION_CLOSED_BRANCH,
                    MAX(eprcjew.total_income) AS EPR_TOTAL_INCOME_CLOSED_JEWELRY,
                    MAX(eprcjew.total_deduction) AS EPR_TOTAL_DEDUCTION_CLOSED_JEWELRY,

                    MAX(eprcadnp.total_cad_netpay) AS EPR_TOTAL_CAD_NET_PAY,
                    
                    -- HR EDI NET PAY VS CAD EDI NET PAY Variance Calculation
                    MAX(
                        eprcadnp.total_cad_netpay)
                        -
                        SUM(
                        (p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) - 
                        (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)
                        ) AS EPR_TOTAL_CAD_NET_PAY_VS_P_TOTAL_NET_PAY_VARIANCE

                    
                FROM 
                    " . $database[1] . ".main_zone_masterfile AS mzm
                JOIN " . $database[1] . ".region_masterfile AS rm 
                    ON ((rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN') OR 
                        (rm.zone_code IN ('NCR', 'LZN') AND mzm.main_zone_code = 'LNCR'))
                LEFT JOIN " . $database[0] . ".rfp_payroll AS mc 
                    ON rm.region_code = mc.region_code AND mc.payroll_date = '$restrictedDate'
                LEFT JOIN " . $database[0] . ".payroll AS p 
                    ON rm.region_code = p.region_code AND p.payroll_date = '$restrictedDate'
                    AND p.description = 'payroll'
                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND epr.ml_matic_status = 'Active'
                        AND NOT(
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprab
                    ON eprab.region_code = rm.region_code

                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND epr.ml_matic_status = 'Active'
                        AND (
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprajew
                    ON eprajew.region_code = rm.region_code


                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND ml_matic_status IN ('Inactive', 'Pending')
                        AND NOT(
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprcb
                    ON eprcb.region_code = rm.region_code

                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND ml_matic_status IN ('Inactive', 'Pending')
                        AND (
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprcjew
                    ON eprcjew.region_code = rm.region_code

                LEFT JOIN(
                    SELECT 
                        epr.region_code,
                        SUM(
                            (COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)) 
                            - 
                            (COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        )
                    ) AS total_cad_netpay
                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                    GROUP BY epr.region_code
                    
                        ) AS eprcadnp
                    ON eprcadnp.region_code = rm.region_code
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
            $sheet->setCellValue('A2', 'PAYROLL REPORT'); 

            // Third row

            // Fourth row
            if($payrollDay === '15') {
                $sheet->setCellValue('A4', 'Payroll Date: ' . $payrollMonth . ' 1 - ' . $payrollDay . ', ' . $payrollYear)->mergeCells('A4:T4')->getStyle('A4:T4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            } else {
                $sheet->setCellValue('A4', 'Payroll Date: ' . $payrollMonth . ' 16 - ' . $payrollDay . ', ' . $payrollYear)->mergeCells('A4:T4')->getStyle('A4:T4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            }

            // Fifth row
            $sheet->setCellValue('A5', 'Mainzone: ' . $mainzone)->mergeCells('A5:C5')->getStyle('A5:C5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('D5', 'HRMD RFP')->mergeCells('D5:F5')->getStyle('D5:F5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('G5', 'EDI REPORT (ARIEL)')->mergeCells('G5:I5')->getStyle('G5:I5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('J5', 'HRMD VARIANCE')->mergeCells('J5')->getStyle('J5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('K5', 'EDI REPORT (CAD)')->mergeCells('K5:N5')->getStyle('K5:N5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('O5', 'EDI CLOSED BRANCH (CAD)')->mergeCells('O5:R5')->getStyle('O5:R5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('S5', 'EDI REPORT (CAD)')->mergeCells('S5')->getStyle('S5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('T5', 'EDI VARIANCE')->mergeCells('T5')->getStyle('T5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            // Sixth row
            $sheet->setCellValue('A6', 'REGION CODE')->mergeCells('A6')->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('B6', 'REGION NAME')->mergeCells('B6')->getStyle('B6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('C6', 'ZONE')->mergeCells('C6')->getStyle('C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('D6', 'MCASH WALLET')->mergeCells('D6')->getStyle('D6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('E6', 'ML KP')->mergeCells('E6')->getStyle('E6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('F6', 'TOTAL')->mergeCells('F6')->getStyle('F6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('G6', 'GROSS INCOME')->mergeCells('G6')->getStyle('G6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('H6', 'GROSS DEDUCTION')->mergeCells('H6')->getStyle('H6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('I6', 'NET PAY')->mergeCells('I6')->getStyle('I6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('J6', 'HR RFP VS HR EDI')->mergeCells('J6')->getStyle('J6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('K6', 'GROSS INCOME (BRANCH)')->mergeCells('K6')->getStyle('K6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('L6', 'GROSS DEDUCTION (BRANCH)')->mergeCells('L6')->getStyle('L6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('M6', 'GROSS INCOME (JEWELRY)')->mergeCells('M6')->getStyle('M6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('N6', 'GROSS DEDUCTION (JEWELRY)')->mergeCells('N6')->getStyle('N6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('O6', 'GROSS INCOME (BRANCH)')->mergeCells('O6')->getStyle('O6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('P6', 'GROSS DEDUCTION (BRANCH)')->mergeCells('P6')->getStyle('P6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('Q6', 'GROSS INCOME (JEWELRY)')->mergeCells('Q6')->getStyle('Q6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('R6', 'GROSS DEDUCTION (JEWELRY)')->mergeCells('R6')->getStyle('R6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('S6', 'NET PAY')->mergeCells('S6')->getStyle('S6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->setCellValue('T6', 'HR EDI NET PAY VS CAD EDI NET PAY')->mergeCells('T6')->getStyle('T6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('A1:A2')->getFont()->setBold(true);
            $sheet->getStyle('A4:T6')->getFont()->setBold(true);
            $sheet->getStyle('A9')->getFont()->setBold(true);
            
            // Set the height of row 6
            $sheet->getRowDimension(5)->setRowHeight(64.5);
            $sheet->getRowDimension(6)->setRowHeight(64.5);


            $startRow = 5;
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

            foreach (['O', 'P', 'Q', 'R'] as $col) {
                $sheet->getColumnDimension($col, '5')->setAutoSize(false)->setWidth(13);
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

            $sheet->getStyle('J5')->getAlignment()->setWrapText(true);
            $sheet->getStyle('S5:T5')->getAlignment()->setWrapText(true);

            $sheet->getStyle('A6')->getAlignment()->setWrapText(true);
            $sheet->getStyle('D6')->getAlignment()->setWrapText(true);
            $sheet->getStyle('G6:T6')->getAlignment()->setWrapText(true);

            $sheet->getColumnDimension('B')->setAutoSize(true);

            

            

            mysqli_data_seek($dlresult, 0);
            $rowIndex = 11; // Starting from the 7th row

            
            if($mainzone === 'VISMIN' && empty($region)) {
                // Seventh row
                $sheet->setCellValue('A7','-');
                $sheet->setCellValue('B7','CEBU MANCOM');
                $sheet->setCellValue('C7', 'VIS');

                // Eighth row
                $sheet->setCellValue('A8','-');
                $sheet->setCellValue('B8','CEBU SUPPORT');
                $sheet->setCellValue('C8', 'VIS');

                // Ninth row
                $sheet->setCellValue('A9','SUB-TOTAL')->mergeCells('A9:C9')->getStyle('A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            } elseif($mainzone === 'LNCR' && empty($region)){
                // Seventh row
                $sheet->setCellValue('A7','-');
                $sheet->setCellValue('B7','MAKATI MANCOM');
                $sheet->setCellValue('C7', 'LZN');

                // Eighth row
                $sheet->setCellValue('A8','-');
                $sheet->setCellValue('B8','MAKATI SUPPORT');
                $sheet->setCellValue('C8', 'LZN');

                // Ninth row
                $sheet->setCellValue('A9','SUB-TOTAL')->mergeCells('A9:C9')->getStyle('A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            }
            
            // Tenth row
            $sheet->setCellValue('A10','')->mergeCells('A10:T10');

            // Eleventh row
                
            while ($row = mysqli_fetch_assoc($dlresult)) {
                $sheet->setCellValue('A' . $rowIndex, $row['region_code']);
                $sheet->setCellValue('B' . $rowIndex, $row['region_name']);
                $sheet->setCellValue('C' . $rowIndex, $row['zone_code']);
                
                $sheet->setCellValue('D' . $rowIndex, $row['mlwallet_amount']);
                $sheet->setCellValue('E' . $rowIndex, $row['mlkp_amount']);
                $sheet->setCellValue('F' . $rowIndex, $row['total_amount']);
                
                $sheet->setCellValue('G' . $rowIndex, $row['P_TOTAL_INCOME']);
                $sheet->setCellValue('H' . $rowIndex, $row['P_TOTAL_DEDUCTION']);
                $sheet->setCellValue('I' . $rowIndex, $row['P_TOTAL_NET_PAY']);
                
                $sheet->setCellValue('J' . $rowIndex, $row['P_VARIANCE']);
                
                $sheet->setCellValue('K' . $rowIndex, $row['EPR_TOTAL_INCOME_ACTIVE_BRANCHES']);
                $sheet->setCellValue('L' . $rowIndex, $row['EPR_TOTAL_DEDUCTION_ACTIVE_BRANCHES']);
                $sheet->setCellValue('M' . $rowIndex, $row['EPR_TOTAL_INCOME_ACTIVE_JEWELRY']);
                $sheet->setCellValue('N' . $rowIndex, $row['EPR_TOTAL_DEDUCTION_ACTIVE_JEWELRY']);
                
                $sheet->setCellValue('O' . $rowIndex, $row['EPR_TOTAL_INCOME_CLOSED_BRANCH']);
                $sheet->setCellValue('P' . $rowIndex, $row['EPR_TOTAL_DEDUCTION_CLOSED_BRANCH']);
                $sheet->setCellValue('Q' . $rowIndex, $row['EPR_TOTAL_INCOME_CLOSED_JEWELRY']);
                $sheet->setCellValue('R' . $rowIndex, $row['EPR_TOTAL_DEDUCTION_CLOSED_JEWELRY']);
                
                $sheet->setCellValue('S' . $rowIndex, $row['EPR_TOTAL_CAD_NET_PAY']);
                
                $sheet->setCellValue('T' . $rowIndex, $row['EPR_TOTAL_CAD_NET_PAY_VS_P_TOTAL_NET_PAY_VARIANCE']);

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

                $sheet->getStyle('A4:T'.$rowIndex)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Simulate auto-size based on just this row’s cell values
                foreach (range('D', 'T') as $col) {
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
            $sheet->setCellValue('D' . $rowIndex, $subtotal_mcash_wallet);
            $sheet->setCellValue('E' . $rowIndex, $subtotal_mlkp);
            $sheet->setCellValue('F' . $rowIndex, $subtotal_hrmd_rfp_total);
            
            $sheet->setCellValue('G' . $rowIndex, $subtotal_gross_income_hrmd_edi_payroll);
            $sheet->setCellValue('H' . $rowIndex, $subtotal_gross_deduction_hrmd_edi_payroll);
            $sheet->setCellValue('I' . $rowIndex, $subtotal_hrmd_edi_payroll_net_pay);
            
            $sheet->setCellValue('J' . $rowIndex, $subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll);
            
            $sheet->setCellValue('K' . $rowIndex, $subtotal_gross_income_active_branch_cad_edi_report_payroll);
            $sheet->setCellValue('L' . $rowIndex, $subtotal_gross_deduction_active_branch_cad_edi_report_payroll);
            $sheet->setCellValue('M' . $rowIndex, $subtotal_gross_income_active_jewelry_cad_edi_report_payroll);
            $sheet->setCellValue('N' . $rowIndex, $subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll);
            
            $sheet->setCellValue('O' . $rowIndex, $subtotal_gross_income_closed_branch_cad_edi_report_payroll);
            $sheet->setCellValue('P' . $rowIndex, $subtotal_gross_deduction_closed_branch_cad_edi_report_payroll);
            $sheet->setCellValue('Q' . $rowIndex, $subtotal_gross_income_closed_jewelry_cad_edi_report_payroll);
            $sheet->setCellValue('R' . $rowIndex, $subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll);
            
            $sheet->setCellValue('S' . $rowIndex, $subtotal_cad_edi_report_payroll_net_pay);
            
            $sheet->setCellValue('T' . $rowIndex, $subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll);


            $sheet->setCellValue('A'. ($rowIndex+1),'GRAND TOTAL')->mergeCells('A'. ($rowIndex+1).':C'.($rowIndex+1))->getStyle('A'.($rowIndex+1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('D' . ($rowIndex+1), $grandtotal_mcash_wallet);
            $sheet->setCellValue('E' . ($rowIndex+1), $grandtotal_mlkp);
            $sheet->setCellValue('F' . ($rowIndex+1), $grandtotal_hrmd_rfp_total);
            
            $sheet->setCellValue('G' . ($rowIndex+1), $grandtotal_gross_income_hrmd_edi_payroll);
            $sheet->setCellValue('H' . ($rowIndex+1), $grandtotal_gross_deduction_hrmd_edi_payroll);
            $sheet->setCellValue('I' . ($rowIndex+1), $grandtotal_hrmd_edi_payroll_net_pay);
            
            $sheet->setCellValue('J' . ($rowIndex+1), $grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll);
            
            $sheet->setCellValue('K' . ($rowIndex+1), $grandtotal_gross_income_active_branch_cad_edi_report_payroll);
            $sheet->setCellValue('L' . ($rowIndex+1), $grandtotal_gross_deduction_active_branch_cad_edi_report_payroll);
            $sheet->setCellValue('M' . ($rowIndex+1), $grandtotal_gross_income_active_jewelry_cad_edi_report_payroll);
            $sheet->setCellValue('N' . ($rowIndex+1), $grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll);
            
            $sheet->setCellValue('O' . ($rowIndex+1), $grandtotal_gross_income_closed_branch_cad_edi_report_payroll);
            $sheet->setCellValue('P' . ($rowIndex+1), $grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll);
            $sheet->setCellValue('Q' . ($rowIndex+1), $grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll);
            $sheet->setCellValue('R' . ($rowIndex+1), $grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll);
            
            $sheet->setCellValue('S' . ($rowIndex+1), $grandtotal_cad_edi_report_payroll_net_pay);
            
            $sheet->setCellValue('T' . ($rowIndex+1), $grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll);


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
            
            // Set borders for the last two rows
            $sheet->getStyle('A'.$rowIndex.':T'.($rowIndex+1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                        
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            
            $region_name = $_SESSION['region_name'];
            if(!empty($region)) {
                if($payrollDay === '15') {
                    $filename = "RECON_&_VARIANCE_Payroll_Report_" . $mainzone . "_" . $region_name . "-[".$region."]_(" . $payrollMonth . " 1 - " . $payrollDay . ", " . $payrollYear . ").xls";
                }else{
                    $filename = "RECON_&_VARIANCE_Payroll_Report_" . $mainzone . "_" . $region_name . "-[".$region."]_(" . $payrollMonth . " 16 - " . $payrollDay . ", " . $payrollYear . ").xls";
                }
            }else{
                if($payrollDay === '15') {
                    $filename = "RECON_&_VARIANCE_Payroll_Report_" . $mainzone . "_(" . $payrollMonth . " 1 - " . $payrollDay . ", " . $payrollYear . ").xls";
                }else{
                    $filename = "RECON_&_VARIANCE_Payroll_Report_" . $mainzone . "_(" . $payrollMonth . " 16 - " . $payrollDay . ", " . $payrollYear . ").xls";
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
            color: #F14A51;
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

    <center><h2>Payroll Report <span>[RECON & VARIANCE-Format]</span></h2></center>

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

    <script src="<?php echo $relative_path; ?>assets/js/admin/mcash-recon/recon-variance-format/mcash-recon-script.js"></script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $restrictedDate = $_POST['restricted-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;

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
                    
                    -- Mcash data
                    MAX(mc.mlwallet_amount) AS mlwallet_amount,
                    MAX(mc.mlkp_amount) AS mlkp_amount,
                    MAX(mc.mlwallet_amount + mc.mlkp_amount) AS total_amount,
                    
                    -- Payroll total income for each region
                    SUM(p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) AS P_TOTAL_INCOME,
                    
                    SUM(p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions) AS P_TOTAL_DEDUCTION,
                    
                    SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) - 
                        (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)) AS P_TOTAL_NET_PAY,
                    
                    -- Variance Calculation
                    MAX(COALESCE(mc.mlwallet_amount, 0) + COALESCE(mc.mlkp_amount, 0)) -
                    SUM((p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) - 
                        (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)) AS P_VARIANCE,

                    MAX(eprab.total_income) AS EPR_TOTAL_INCOME_ACTIVE_BRANCHES,
                    MAX(eprab.total_deduction) AS EPR_TOTAL_DEDUCTION_ACTIVE_BRANCHES,
                    MAX(eprajew.total_income) AS EPR_TOTAL_INCOME_ACTIVE_JEWELRY,
                    MAX(eprajew.total_deduction) AS EPR_TOTAL_DEDUCTION_ACTIVE_JEWELRY,

                    MAX(eprcb.total_income) AS EPR_TOTAL_INCOME_CLOSED_BRANCH,
                    MAX(eprcb.total_deduction) AS EPR_TOTAL_DEDUCTION_CLOSED_BRANCH,
                    MAX(eprcjew.total_income) AS EPR_TOTAL_INCOME_CLOSED_JEWELRY,
                    MAX(eprcjew.total_deduction) AS EPR_TOTAL_DEDUCTION_CLOSED_JEWELRY,

                    MAX(eprcadnp.total_cad_netpay) AS EPR_TOTAL_CAD_NET_PAY,
                    
                    -- HR EDI NET PAY VS CAD EDI NET PAY Variance Calculation
                    MAX(
                        eprcadnp.total_cad_netpay)
                        -
                        SUM(
                        (p.basic_pay_regular + p.basic_pay_trainee + p.allowances + p.bm_allowance + 
                        p.overtime_regular + p.overtime_trainee + p.cola + p.excess_pb + 
                        p.other_income + p.salary_adjustment + p.graveyard) - 
                        (p.late_regular + p.late_trainee + p.leave_regular + p.leave_trainee + p.all_other_deductions)
                        ) AS EPR_TOTAL_CAD_NET_PAY_VS_P_TOTAL_NET_PAY_VARIANCE

                    
                FROM 
                    " . $database[1] . ".main_zone_masterfile AS mzm
                JOIN " . $database[1] . ".region_masterfile AS rm 
                    ON ((rm.zone_code IN ('VIS', 'MIN') AND mzm.main_zone_code = 'VISMIN') OR 
                        (rm.zone_code IN ('NCR', 'LZN') AND mzm.main_zone_code = 'LNCR'))
                LEFT JOIN " . $database[0] . ".rfp_payroll AS mc 
                    ON rm.region_code = mc.region_code AND mc.payroll_date = '$restrictedDate'
                LEFT JOIN " . $database[0] . ".payroll AS p 
                    ON rm.region_code = p.region_code AND p.payroll_date = '$restrictedDate'
                    AND p.description = 'payroll'
                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND epr.ml_matic_status = 'Active'
                        AND NOT(
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprab
                    ON eprab.region_code = rm.region_code

                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND epr.ml_matic_status = 'Active'
                        AND (
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprajew
                    ON eprajew.region_code = rm.region_code


                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND ml_matic_status IN ('Inactive', 'Pending')
                        AND NOT(
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprcb
                    ON eprcb.region_code = rm.region_code

                LEFT JOIN (
                    SELECT 
                        epr.region_code,
                        SUM(
                            COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)
                        ) AS total_income,

                        SUM(
                            COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        ) AS total_deduction

                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                        AND ml_matic_status IN ('Inactive', 'Pending')
                        AND (
                            (epr.zone IN ('VIS','JVIS', 'MIN') AND epr.ml_matic_region = 'VISMIN Showroom') OR
                            (epr.zone IN ('LZN', 'NCR') AND epr.ml_matic_region = 'LNCR Showroom')
                        )
                    GROUP BY epr.region_code
                ) AS eprcjew
                    ON eprcjew.region_code = rm.region_code

                LEFT JOIN(
                    SELECT 
                        epr.region_code,
                        SUM(
                            (COALESCE(epr.basic_pay_regular, 0) + 
                            COALESCE(epr.basic_pay_trainee, 0) + 
                            COALESCE(epr.allowances, 0) + 
                            COALESCE(epr.bm_allowance, 0) + 
                            COALESCE(epr.overtime_regular, 0) + 
                            COALESCE(epr.overtime_trainee, 0) + 
                            COALESCE(epr.cola, 0) + 
                            COALESCE(epr.excess_pb, 0) + 
                            COALESCE(epr.other_income, 0) + 
                            COALESCE(epr.salary_adjustment, 0) + 
                            COALESCE(epr.graveyard, 0)) 
                            - 
                            (COALESCE(epr.late_regular, 0) + 
                            COALESCE(epr.late_trainee, 0) + 
                            COALESCE(epr.leave_regular, 0) + 
                            COALESCE(epr.leave_trainee, 0) + 
                            COALESCE(epr.all_other_deductions, 0)
                        )
                    ) AS total_cad_netpay
                    FROM " . $database[0] . ".payroll_edi_report AS epr
                    WHERE 
                        epr.payroll_date = '$restrictedDate' 
                        AND epr.description = 'payroll'
                    GROUP BY epr.region_code
                    
                        ) AS eprcadnp
                    ON eprcadnp.region_code = rm.region_code
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
        $subtotal_mcash_wallet = 0;
        $subtotal_mlkp = 0;
        $subtotal_hrmd_rfp_total = 0;

        $subtotal_gross_income_hrmd_edi_payroll = 0;
        $subtotal_gross_deduction_hrmd_edi_payroll = 0;
        $subtotal_hrmd_edi_payroll_net_pay = 0;

        $subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll = 0;

        $subtotal_gross_income_active_branch_cad_edi_report_payroll = 0;
        $subtotal_gross_deduction_active_branch_cad_edi_report_payroll = 0;
        $subtotal_gross_income_active_jewelry_cad_edi_report_payroll = 0;
        $subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll = 0;

        $subtotal_gross_income_closed_branch_cad_edi_report_payroll = 0;
        $subtotal_gross_deduction_closed_branch_cad_edi_report_payroll = 0;
        $subtotal_gross_income_closed_jewelry_cad_edi_report_payroll = 0;
        $subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll = 0;

        $subtotal_cad_edi_report_payroll_net_pay = 0;

        $subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll = 0;

        // Initialize grand totals
        $grandtotal_mcash_wallet = 0;
        $grandtotal_mlkp = 0;
        $grandtotal_hrmd_rfp_total = 0;

        $grandtotal_gross_income_hrmd_edi_payroll = 0;
        $grandtotal_gross_deduction_hrmd_edi_payroll = 0;
        $grandtotal_hrmd_edi_payroll_net_pay = 0;

        $grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll = 0;

        $grandtotal_gross_income_active_branch_cad_edi_report_payroll = 0;
        $grandtotal_gross_deduction_active_branch_cad_edi_report_payroll = 0;
        $grandtotal_gross_income_active_jewelry_cad_edi_report_payroll = 0;
        $grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll = 0;

        $grandtotal_gross_income_closed_branch_cad_edi_report_payroll = 0;
        $grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll = 0;
        $grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll = 0;
        $grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll = 0;

        $grandtotal_cad_edi_report_payroll_net_pay = 0;

        // $grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll = 0;

        foreach($result as $row){
            $subtotal_mcash_wallet += $row['mlwallet_amount'];
            $subtotal_mlkp += $row['mlkp_amount'];
            $subtotal_hrmd_rfp_total += $row['total_amount'];

            $subtotal_gross_income_hrmd_edi_payroll += $row['P_TOTAL_INCOME'];
            $subtotal_gross_deduction_hrmd_edi_payroll += $row['P_TOTAL_DEDUCTION'];
            $subtotal_hrmd_edi_payroll_net_pay += $row['P_TOTAL_NET_PAY'];

            $subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll += $row['P_VARIANCE'];

            $subtotal_gross_income_active_branch_cad_edi_report_payroll += $row['EPR_TOTAL_INCOME_ACTIVE_BRANCHES'];
            $subtotal_gross_deduction_active_branch_cad_edi_report_payroll += $row['EPR_TOTAL_DEDUCTION_ACTIVE_BRANCHES'];
            $subtotal_gross_income_active_jewelry_cad_edi_report_payroll += $row['EPR_TOTAL_INCOME_ACTIVE_JEWELRY'];
            $subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll += $row['EPR_TOTAL_DEDUCTION_ACTIVE_JEWELRY'];

            $subtotal_gross_income_closed_branch_cad_edi_report_payroll += $row['EPR_TOTAL_INCOME_CLOSED_BRANCH'];
            $subtotal_gross_deduction_closed_branch_cad_edi_report_payroll += $row['EPR_TOTAL_DEDUCTION_CLOSED_BRANCH'];
            $subtotal_gross_income_closed_jewelry_cad_edi_report_payroll += $row['EPR_TOTAL_INCOME_CLOSED_JEWELRY'];
            $subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll += $row['EPR_TOTAL_DEDUCTION_CLOSED_JEWELRY'];

            $subtotal_cad_edi_report_payroll_net_pay += $row['EPR_TOTAL_CAD_NET_PAY'];

            // Variance Calculation
            $subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll += $row['EPR_TOTAL_CAD_NET_PAY'] - $row['P_TOTAL_NET_PAY'];


            $grandtotal_mcash_wallet = $subtotal_mcash_wallet;
            $grandtotal_mlkp = $subtotal_mlkp;
            $grandtotal_hrmd_rfp_total = $subtotal_hrmd_rfp_total;

            $grandtotal_gross_income_hrmd_edi_payroll = $subtotal_gross_income_hrmd_edi_payroll;
            $grandtotal_gross_deduction_hrmd_edi_payroll = $subtotal_gross_deduction_hrmd_edi_payroll;
            $grandtotal_hrmd_edi_payroll_net_pay = $subtotal_hrmd_edi_payroll_net_pay;

            $grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll = $subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll;

            $grandtotal_gross_income_active_branch_cad_edi_report_payroll = $subtotal_gross_income_active_branch_cad_edi_report_payroll;
            $grandtotal_gross_deduction_active_branch_cad_edi_report_payroll = $subtotal_gross_deduction_active_branch_cad_edi_report_payroll;
            $grandtotal_gross_income_active_jewelry_cad_edi_report_payroll = $subtotal_gross_income_active_jewelry_cad_edi_report_payroll;
            $grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll = $subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll;

            $grandtotal_gross_income_closed_branch_cad_edi_report_payroll = $subtotal_gross_income_closed_branch_cad_edi_report_payroll;
            $grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll = $subtotal_gross_deduction_closed_branch_cad_edi_report_payroll;
            $grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll = $subtotal_gross_income_closed_jewelry_cad_edi_report_payroll;
            $grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll = $subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll;

            $grandtotal_cad_edi_report_payroll_net_pay = $subtotal_cad_edi_report_payroll_net_pay;

            $grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll = $subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll;
        }

        $_SESSION['subtotal_mcash_wallet'] = $subtotal_mcash_wallet;
        $_SESSION['subtotal_mlkp'] = $subtotal_mlkp;
        $_SESSION['subtotal_hrmd_rfp_total'] = $subtotal_hrmd_rfp_total;

        $_SESSION['subtotal_gross_income_hrmd_edi_payroll'] = $subtotal_gross_income_hrmd_edi_payroll;
        $_SESSION['subtotal_gross_deduction_hrmd_edi_payroll'] = $subtotal_gross_deduction_hrmd_edi_payroll;
        $_SESSION['subtotal_hrmd_edi_payroll_net_pay'] = $subtotal_hrmd_edi_payroll_net_pay;

        $_SESSION['subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll'] = $subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll;

        $_SESSION['subtotal_gross_income_active_branch_cad_edi_report_payroll'] = $subtotal_gross_income_active_branch_cad_edi_report_payroll;
        $_SESSION['subtotal_gross_deduction_active_branch_cad_edi_report_payroll'] = $subtotal_gross_deduction_active_branch_cad_edi_report_payroll;
        $_SESSION['subtotal_gross_income_active_jewelry_cad_edi_report_payroll'] = $subtotal_gross_income_active_jewelry_cad_edi_report_payroll;
        $_SESSION['subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll'] = $subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll;

        $_SESSION['subtotal_gross_income_closed_branch_cad_edi_report_payroll'] = $subtotal_gross_income_closed_branch_cad_edi_report_payroll;
        $_SESSION['subtotal_gross_deduction_closed_branch_cad_edi_report_payroll'] = $subtotal_gross_deduction_closed_branch_cad_edi_report_payroll;
        $_SESSION['subtotal_gross_income_closed_jewelry_cad_edi_report_payroll'] = $subtotal_gross_income_closed_jewelry_cad_edi_report_payroll;
        $_SESSION['subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll'] = $subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll;

        $_SESSION['subtotal_cad_edi_report_payroll_net_pay'] = $subtotal_cad_edi_report_payroll_net_pay;
        
        $_SESSION['subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll'] = $subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll;


        $_SESSION['grandtotal_mcash_wallet'] = $grandtotal_mcash_wallet;
        $_SESSION['grandtotal_mlkp'] = $grandtotal_mlkp;
        $_SESSION['grandtotal_hrmd_rfp_total'] = $grandtotal_hrmd_rfp_total;

        $_SESSION['grandtotal_gross_income_hrmd_edi_payroll'] = $grandtotal_gross_income_hrmd_edi_payroll;
        $_SESSION['grandtotal_gross_deduction_hrmd_edi_payroll'] = $grandtotal_gross_deduction_hrmd_edi_payroll;
        $_SESSION['grandtotal_hrmd_edi_payroll_net_pay'] = $grandtotal_hrmd_edi_payroll_net_pay;

        $_SESSION['grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll'] = $grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll;

        $_SESSION['grandtotal_gross_income_active_branch_cad_edi_report_payroll'] = $grandtotal_gross_income_active_branch_cad_edi_report_payroll;
        $_SESSION['grandtotal_gross_deduction_active_branch_cad_edi_report_payroll'] = $grandtotal_gross_deduction_active_branch_cad_edi_report_payroll;
        $_SESSION['grandtotal_gross_income_active_jewelry_cad_edi_report_payroll'] = $grandtotal_gross_income_active_jewelry_cad_edi_report_payroll;
        $_SESSION['grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll'] = $grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll;

        $_SESSION['grandtotal_gross_income_closed_branch_cad_edi_report_payroll'] = $grandtotal_gross_income_closed_branch_cad_edi_report_payroll;
        $_SESSION['grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll'] = $grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll;
        $_SESSION['grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll'] = $grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll;
        $_SESSION['grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll'] = $grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll;

        $_SESSION['grandtotal_cad_edi_report_payroll_net_pay'] = $grandtotal_cad_edi_report_payroll_net_pay;
        
        $_SESSION['grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll'] = $grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll;

         // Check if there are results
         if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
                echo "<table>";
                    echo "<thead>";

                        $first_row = mysqli_fetch_assoc($result);

                        //  first row
                        if($payrollDay === '15') {
                            echo "<tr>";
                                echo "<th colspan='20'> Payroll Date: " . $payrollMonth . " 1 - " . $payrollDay . ", " . $payrollYear . "</th>";
                            echo "</tr>";
                        }else{
                            echo "<tr>";
                                echo "<th colspan='20'> Payroll Date: " . $payrollMonth . " 16 - " . $payrollDay . ", " . $payrollYear . "</th>";
                            echo "</tr>";
                        }
                        //  second row
                        echo "<tr>";
                            echo "<th colspan='3'>" . $mainzone . "</th>";
                            echo "<th colspan='3'>HRMD RFP</th>";
                            echo "<th colspan='3'>EDI REPORT (ARIEL)</th>";
                            echo "<th>HRMD VARIANCE</th>";

                            echo "<th colspan='4'>EDI REPORT (CAD)</th>";
                            echo "<th colspan='4'>EDI CLOSED BRANCH (CAD)</th>";
                            echo "<th>EDI REPORT (CAD)</th>";
                            echo "<th>EDI VARIANCE</th>";
                        echo "</tr>";
                        // third row
                        echo "<tr>";
                            echo "<th>Region Code</th>";
                            echo "<th>Region Name</th>";
                            echo "<th>Zone</th>";

                            echo "<th>Mcash Wallet</th>";
                            echo "<th>ML KP</th>";
                            echo "<th>Total</th>";

                            echo "<th>Gross Income</th>";
                            echo "<th>Gross Deduction</th>";
                            echo "<th>NET PAY</th>";
                            echo "<th>HR RFP VS HR EDI</th>";

                            echo "<th>Gross Income (BRANCH)</th>";
                            echo "<th>Gross Deduction (BRANCH)</th>";
                            echo "<th>Gross Income (JEWELRY)</th>";
                            echo "<th>Gross Deduction (JEWELRY)</th>";
                            echo "<th>Gross Income (BRANCH)</th>";
                            echo "<th>Gross Deduction (BRANCH)</th>";
                            echo "<th>Gross Income (JEWELRY)</th>";
                            echo "<th>Gross Deduction (JEWELRY)</th>";
                            echo "<th>NET PAY</th>";
                            echo "<th>HR EDI NET PAY VS CAD EDI NET PAY</th>";
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
                            echo "</tr>";
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
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td colspan='20'></td>";
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
                            echo "</tr>";
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
                                
                                echo "<td>" . htmlspecialchars(number_format($row['mlwallet_amount'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['mlkp_amount'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['total_amount'], 2)) . "</td>";

                                echo "<td>" . htmlspecialchars(number_format($row['P_TOTAL_INCOME'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['P_TOTAL_DEDUCTION'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['P_TOTAL_NET_PAY'], 2)) . "</td>";

                                echo "<td>" . htmlspecialchars(number_format($row['P_VARIANCE'], 2)) . "</td>"; 

                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_INCOME_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_DEDUCTION_ACTIVE_BRANCHES'], 2)) . "</td>"; 
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_INCOME_ACTIVE_JEWELRY'], 2)) . "</td>"; 
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_DEDUCTION_ACTIVE_JEWELRY'], 2)) . "</td>";
                                // convert to negative if positive value 
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_INCOME_CLOSED_BRANCH'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_DEDUCTION_CLOSED_BRANCH'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_INCOME_CLOSED_JEWELRY'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_DEDUCTION_CLOSED_JEWELRY'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_CAD_NET_PAY'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format($row['EPR_TOTAL_CAD_NET_PAY_VS_P_TOTAL_NET_PAY_VARIANCE'], 2)) . "</td>";
                            echo "</tr>";
                        }
                            echo "<tr>";
                                echo "<td colspan='3'><b>SUB-TOTAL</b></td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_mcash_wallet, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_mlkp, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_hrmd_rfp_total, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_income_hrmd_edi_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_deduction_hrmd_edi_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_hrmd_edi_payroll_net_pay, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_income_active_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_deduction_active_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_income_active_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_deduction_active_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_income_closed_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_deduction_closed_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_income_closed_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_cad_edi_report_payroll_net_pay, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($subtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll, 2))."</td>";
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td colspan='3'><b>GRAND TOTAL</b></td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_mcash_wallet, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_mlkp, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_hrmd_rfp_total, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_income_hrmd_edi_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_deduction_hrmd_edi_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_hrmd_edi_payroll_net_pay, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_hrmd_variance_hr_rfp_vs_hr_edi_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_income_active_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_deduction_active_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_income_active_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_deduction_active_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_income_closed_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_deduction_closed_branch_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_income_closed_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_gross_deduction_closed_jewelry_cad_edi_report_payroll, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_cad_edi_report_payroll_net_pay, 2))."</td>";
                                echo "<td>".htmlspecialchars(number_format($grandtotal_edi_variance_hr_edi_payroll_vs_cad_edi_report_payroll, 2))."</td>";
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