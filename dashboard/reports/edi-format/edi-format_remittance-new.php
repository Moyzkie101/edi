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

require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

if (isset($_POST['download'])) {

    $mainzone = $_SESSION['mainzone'] ?? '';
    $zone = $_SESSION['zone'] ?? '';
    $region = $_SESSION['region'] ?? '';
    $status = $_SESSION['status'] ?? '';
    $restrictedDate = $_SESSION['restrictedDate'] ?? '';

    generateDownload($conn, $database, $mainzone, $zone, $region, $status, $restrictedDate);
}
if (isset($_POST['download2'])) {

    $mainzone = $_SESSION['mainzone'] ?? '';
    $zone = $_SESSION['zone'] ?? '';
    $region = $_SESSION['region'] ?? '';
    $status = $_SESSION['status'] ?? '';
    $restrictedDate = $_SESSION['restrictedDate'] ?? '';

    generateDownload2($conn, $database, $mainzone, $zone, $region, $status, $restrictedDate);
}

// Function to generate the download excel file
function generateDownload2($conn, $database, $mainzone, $zone, $region, $status, $restrictedDate){

    $mainzone = $_SESSION['mainzone'] ?? '';
    $zone = $_SESSION['zone'] ?? '';
    $region = $_SESSION['region'] ?? '';
    $status = $_SESSION['status'] ?? '';
    $restrictedDate = $_SESSION['restrictedDate'] ?? '';

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
        $dlsql = "SELECT
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
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

                        MAX(r.branch_name) as branch_name,

                        MAX(r.ee_dr1) as ee_dr1,
                        MAX(r.dr1) as dr1,
						SUM(
							COALESCE(r.ee_dr1, 0)+
							COALESCE(r.dr1, 0)
						) as total_ee_er_dr1,

                        MAX(r.ee_dr2) as ee_dr2,
                        MAX(r.dr2) as dr2,
						SUM(
							COALESCE(r.ee_dr2, 0)+
							COALESCE(r.dr2, 0)
						) as total_ee_er_dr2,

                        MAX(r.ee_dr3) as ee_dr3,
                        MAX(r.dr3) as dr3,
						SUM(
							COALESCE(r.ee_dr3, 0)+
							COALESCE(r.dr3, 0)
						) as total_ee_er_dr3,
                        
                        COUNT(DISTINCT r.branch_code) as branch_count
                    FROM
                        " . $database[0] . ".remitance_edi_report r
                    WHERE
                        r.mainzone = '$mainzone'
                        AND r.remitance_date = '$restrictedDate'
                        AND r.ml_matic_region = '$zone'
                        AND r.zone LIKE '%$region%'
                        AND NOT (r.branch_code = 18 AND r.zone = 'VIS')
                        AND r.remitance_format_type = 'NEW'
                        AND (
                            CASE 
                                WHEN r.ml_matic_status = 'Active' THEN 'Active'
                                WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                            END
                        ) = '".$status."'
                    GROUP BY 
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
                        r.remitance_date
                    ORDER BY 
                        r.region;";
    } else {
        $dlsql = "SELECT
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
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

                        MAX(r.branch_name) as branch_name,

                        MAX(r.ee_dr1) as ee_dr1,
                        MAX(r.dr1) as dr1,
						SUM(
							COALESCE(r.ee_dr1, 0)+
							COALESCE(r.dr1, 0)
						) as total_ee_er_dr1,

                        MAX(r.ee_dr2) as ee_dr2,
                        MAX(r.dr2) as dr2,
						SUM(
							COALESCE(r.ee_dr2, 0)+
							COALESCE(r.dr2, 0)
						) as total_ee_er_dr2,

                        MAX(r.ee_dr3) as ee_dr3,
                        MAX(r.dr3) as dr3,
						SUM(
							COALESCE(r.ee_dr3, 0)+
							COALESCE(r.dr3, 0)
						) as total_ee_er_dr3,

                        COUNT(DISTINCT r.branch_code) as branch_count
                    FROM
                        " . $database[0] . ".remitance_edi_report r
                    WHERE
                        r.mainzone = '$mainzone'
                        AND r.zone = '$zone'
                        AND r.zone != 'JVIS'
                        AND r.region_code LIKE '%$region%'
                        AND r.remitance_date = '$restrictedDate'
                        AND r.ml_matic_region != 'LNCR Showroom'
                        AND r.ml_matic_region != 'VISMIN Showroom'
                        AND r.remitance_format_type = 'NEW'
                        AND (
                            CASE 
                                WHEN r.ml_matic_status = 'Active' THEN 'Active'
                                WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                            END
                        ) = '".$status."'
                    GROUP BY 
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
                        r.remitance_date
                    ORDER BY 
                        r.region;";
    }

    //echo $dlsql;
    $dlresult = mysqli_query($conn, $dlsql);

    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Fetch the first row to get header data
    $first_row = mysqli_fetch_assoc($dlresult);

    $payroll_date = htmlspecialchars($first_row['remitance_date']);

    $ee_gl_code_dr1 = htmlspecialchars($first_row['ee_gl_code_dr1']);
    $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
    $gl_code_total_ee_er_dr1 = htmlspecialchars($first_row['gl_code_total_ee_er_dr1']);

    $ee_gl_code_dr2 = htmlspecialchars($first_row['ee_gl_code_dr2']);
    $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
	$gl_code_total_ee_er_dr2 = htmlspecialchars($first_row['gl_code_total_ee_er_dr2']);

    $ee_gl_code_dr3 = htmlspecialchars($first_row['ee_gl_code_dr3']);
    $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
	$gl_code_total_ee_er_dr3 = htmlspecialchars($first_row['gl_code_total_ee_er_dr3']);

    // First row: DR and Cost Center headers
    $sheet->setCellValue('A1', 'PAYROLL REMITTANCE');
    $sheet->setCellValue('C1', 'DR');
    $sheet->setCellValue('D1', 'DR');
    $sheet->setCellValue('E1', 'CR');
	
    $sheet->setCellValue('F1', 'DR');
    $sheet->setCellValue('G1', 'DR');
    $sheet->setCellValue('H1', 'CR');
	
    $sheet->setCellValue('I1', 'DR');
    $sheet->setCellValue('J1', 'DR');
    $sheet->setCellValue('K1', 'CR');
    $sheet->setCellValue('L1', 'Cost Center');
    $sheet->setCellValue('M1', 'Region');

    // Second row: Column headers
    $sheet->setCellValue('A2', 'BC Code');
    $sheet->setCellValue('B2', 'BC Name');
	
    $sheet->setCellValue('C2', $ee_gl_code_dr1);
    $sheet->setCellValue('D2', $gl_code_dr1);
    $sheet->setCellValue('E2', $gl_code_total_ee_er_dr1);
	
    $sheet->setCellValue('F2', $ee_gl_code_dr2);
    $sheet->setCellValue('G2', $gl_code_dr2);
    $sheet->setCellValue('H2', $gl_code_total_ee_er_dr2);
	
    $sheet->setCellValue('I2', $ee_gl_code_dr3);
    $sheet->setCellValue('J2', $gl_code_dr3);
    $sheet->setCellValue('K2', $gl_code_total_ee_er_dr3);
	
    $sheet->setCellValue('L2', '');
    $sheet->setCellValue('M2', '');

    // Make columns auto-size
    foreach (range('A', 'M') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Reset the result pointer to the beginning and set row index
    mysqli_data_seek($dlresult, 0);
    $rowIndex = 3; // Starting from the 4th row

    while ($row = mysqli_fetch_assoc($dlresult)) {

        $applyStyle = false;

        if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
            $color = '4fc917';
            $bold = true;
            $applyStyle = true;
        } else {
            $bold = false;
        }

        $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
        $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);

        // Use setCellValueExplicit for setting the value and format it as a number
        $sheet->setCellValueExplicit('C' . $rowIndex, $row['ee_dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValueExplicit('E' . $rowIndex, $row['total_ee_er_dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

        $sheet->setCellValueExplicit('F' . $rowIndex, $row['ee_dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('G' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValueExplicit('H' . $rowIndex, $row['total_ee_er_dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

        $sheet->setCellValueExplicit('I' . $rowIndex, $row['ee_dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('J' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValueExplicit('K' . $rowIndex, $row['total_ee_er_dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

        /*$sheet->setCellValueExplicit('I' . $rowIndex, $row['total_ee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('J' . $rowIndex, $row['dr4'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);*/

        $sheet->setCellValue('L' . $rowIndex, $row['cost_center']);
        $sheet->setCellValue('M' . $rowIndex, $row['region']);

        // Apply styles if flag is set
        if ($applyStyle) {
            $sheet->getStyle('A' . $rowIndex . ':M' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($color);
        }

        // Apply bold style regardless of background color
        $sheet->getStyle('A' . $rowIndex . ':M' . $rowIndex)->getFont()->setBold($bold);

        $rowIndex++;
    }

    // Set headers to force download the Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	if($status==='Active'){
		if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . "_RECON.xls";
		}else{
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $zone . "_" . $region . "_" . $restrictedDate . "_RECON.xls";
		}  
	}else{
		if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending)_RECON.xls";
		}else{
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $zone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending)_RECON.xls";
		}
	}		
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Write and save the Excel file
    $writer = IOFactory::createWriter($spreadsheet, 'Xls');
    $writer->save('php://output');
    exit;
    // csv format
    // header('Content-Type: text/csv');
    // $filename = "EDI_Payroll_Report_" . $zone . "_" . $region . "_" . $restrictedDate . ".csv";
    // header('Content-Disposition: attachment; filename="' . $filename . '"');
    // header('Cache-Control: max-age=0');

    // $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Csv');
    // $writer->save('php://output');

}

// Function to generate the download excel file
function generateDownload($conn, $database, $mainzone, $zone, $region, $status, $restrictedDate){

    $mainzone = $_SESSION['mainzone'] ?? '';
    $zone = $_SESSION['zone'] ?? '';
    $region = $_SESSION['region'] ?? '';
    $status = $_SESSION['status'] ?? '';
    $restrictedDate = $_SESSION['restrictedDate'] ?? '';

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
        $dlsql = "SELECT
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
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

                        MAX(r.branch_name) as branch_name,

                        MAX(r.ee_dr1) as ee_dr1,
                        MAX(r.dr1) as dr1,
						SUM(
							COALESCE(r.ee_dr1, 0)+
							COALESCE(r.dr1, 0)
						) as total_ee_er_dr1,

                        MAX(r.ee_dr2) as ee_dr2,
                        MAX(r.dr2) as dr2,
						SUM(
							COALESCE(r.ee_dr2, 0)+
							COALESCE(r.dr2, 0)
						) as total_ee_er_dr2,

                        MAX(r.ee_dr3) as ee_dr3,
                        MAX(r.dr3) as dr3,
						SUM(
							COALESCE(r.ee_dr3, 0)+
							COALESCE(r.dr3, 0)
						) as total_ee_er_dr3,
                        
                        COUNT(DISTINCT r.branch_code) as branch_count
                    FROM
                        " . $database[0] . ".remitance_edi_report r
                    WHERE
                        r.mainzone = '$mainzone'
                        AND r.remitance_date = '$restrictedDate'
                        AND r.ml_matic_region = '$zone'
                        AND r.zone LIKE '%$region%'
                        AND NOT (r.branch_code = 18 AND r.zone = 'VIS')
                        AND r.remitance_format_type = 'NEW'
                        AND (
                            CASE 
                                WHEN r.ml_matic_status = 'Active' THEN 'Active'
                                WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                            END
                        ) = '".$status."'
                    GROUP BY 
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
                        r.remitance_date
                    ORDER BY 
                        r.region;";
    } else {
        $dlsql = "SELECT
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
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

                        MAX(r.branch_name) as branch_name,

                        MAX(r.ee_dr1) as ee_dr1,
                        MAX(r.dr1) as dr1,
						SUM(
							COALESCE(r.ee_dr1, 0)+
							COALESCE(r.dr1, 0)
						) as total_ee_er_dr1,

                        MAX(r.ee_dr2) as ee_dr2,
                        MAX(r.dr2) as dr2,
						SUM(
							COALESCE(r.ee_dr2, 0)+
							COALESCE(r.dr2, 0)
						) as total_ee_er_dr2,

                        MAX(r.ee_dr3) as ee_dr3,
                        MAX(r.dr3) as dr3,
						SUM(
							COALESCE(r.ee_dr3, 0)+
							COALESCE(r.dr3, 0)
						) as total_ee_er_dr3,

                        COUNT(DISTINCT r.branch_code) as branch_count
                    FROM
                        " . $database[0] . ".remitance_edi_report r
                    WHERE
                        r.mainzone = '$mainzone'
                        AND r.zone = '$zone'
                        AND r.zone != 'JVIS'
                        AND r.region_code LIKE '%$region%'
                        AND r.remitance_date = '$restrictedDate'
                        AND r.ml_matic_region != 'LNCR Showroom'
                        AND r.ml_matic_region != 'VISMIN Showroom'
                        AND r.remitance_format_type = 'NEW'
                        AND (
                            CASE 
                                WHEN r.ml_matic_status = 'Active' THEN 'Active'
                                WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                            END
                        ) = '".$status."'
                    GROUP BY 
                        r.branch_code,
                        r.cost_center, 
                        r.region, 
                        r.zone,
                        r.remitance_date
                    ORDER BY 
                        r.region;";
    }

    //echo $dlsql;
    $dlresult = mysqli_query($conn, $dlsql);

    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Fetch the first row to get header data
    $first_row = mysqli_fetch_assoc($dlresult);

    $payroll_date = htmlspecialchars($first_row['remitance_date']);

    $ee_gl_code_dr1 = htmlspecialchars($first_row['ee_gl_code_dr1']);
    $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
    $gl_code_total_ee_er_dr1 = htmlspecialchars($first_row['gl_code_total_ee_er_dr1']);

    $ee_gl_code_dr2 = htmlspecialchars($first_row['ee_gl_code_dr2']);
    $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
	$gl_code_total_ee_er_dr2 = htmlspecialchars($first_row['gl_code_total_ee_er_dr2']);

    $ee_gl_code_dr3 = htmlspecialchars($first_row['ee_gl_code_dr3']);
    $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
	$gl_code_total_ee_er_dr3 = htmlspecialchars($first_row['gl_code_total_ee_er_dr3']);

    // First row: DR and Cost Center headers
    $sheet->setCellValue('A1', 'PAYROLL REMITTANCE');
    $sheet->setCellValue('C1', 'DR');
    $sheet->setCellValue('D1', 'DR');
    $sheet->setCellValue('E1', 'CR');
	
    $sheet->setCellValue('F1', 'DR');
    $sheet->setCellValue('G1', 'DR');
    $sheet->setCellValue('H1', 'CR');
	
    $sheet->setCellValue('I1', 'DR');
    $sheet->setCellValue('J1', 'DR');
    $sheet->setCellValue('K1', 'CR');
    $sheet->setCellValue('L1', 'Cost Center');

    // Second row: Column headers
    $sheet->setCellValue('A2', 'BC Code');
    $sheet->setCellValue('B2', 'BC Name');
	
    $sheet->setCellValue('C2', $ee_gl_code_dr1);
    $sheet->setCellValue('D2', $gl_code_dr1);
    $sheet->setCellValue('E2', $gl_code_total_ee_er_dr1);
	
    $sheet->setCellValue('F2', $ee_gl_code_dr2);
    $sheet->setCellValue('G2', $gl_code_dr2);
    $sheet->setCellValue('H2', $gl_code_total_ee_er_dr2);
	
    $sheet->setCellValue('I2', $ee_gl_code_dr3);
    $sheet->setCellValue('J2', $gl_code_dr3);
    $sheet->setCellValue('K2', $gl_code_total_ee_er_dr3);
	
    $sheet->setCellValue('L2', '');

    // Make columns auto-size
    foreach (range('A', 'M') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Reset the result pointer to the beginning and set row index
    mysqli_data_seek($dlresult, 0);
    $rowIndex = 3; // Starting from the 4th row

    while ($row = mysqli_fetch_assoc($dlresult)) {

        $applyStyle = false;

        if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
            $color = '4fc917';
            $bold = true;
            $applyStyle = true;
        } else {
            $bold = false;
        }

        $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
        $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);

        // Use setCellValueExplicit for setting the value and format it as a number
        $sheet->setCellValueExplicit('C' . $rowIndex, $row['ee_dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValueExplicit('E' . $rowIndex, $row['total_ee_er_dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

        $sheet->setCellValueExplicit('F' . $rowIndex, $row['ee_dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('G' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('G' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValueExplicit('H' . $rowIndex, $row['total_ee_er_dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('H' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

        $sheet->setCellValueExplicit('I' . $rowIndex, $row['ee_dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('J' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValueExplicit('K' . $rowIndex, $row['total_ee_er_dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('K' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);

        /*$sheet->setCellValueExplicit('I' . $rowIndex, $row['total_ee'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
        $sheet->setCellValueExplicit('J' . $rowIndex, $row['dr4'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->getStyle('J' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);*/

        $sheet->setCellValue('L' . $rowIndex, $row['cost_center']);

        // Apply styles if flag is set
        if ($applyStyle) {
            $sheet->getStyle('A' . $rowIndex . ':L' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB($color);
        }

        // Apply bold style regardless of background color
        $sheet->getStyle('A' . $rowIndex . ':L' . $rowIndex)->getFont()->setBold($bold);

        $rowIndex++;
    }

    // Set headers to force download the Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	if($status==='Active'){
		if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
		}else{
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $zone . "_" . $region . "_" . $restrictedDate . ".xls";
		}
	}else{
		if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
		}else{
			$filename = "EDI_Remitance_Report_NEW_FORMAT_" . $zone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
		}
	}
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Write and save the Excel file
    $writer = IOFactory::createWriter($spreadsheet, 'Xls');
    $writer->save('php://output');
    exit;
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
    <link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon" />
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
        input[type="month"] {
            width: 200px;
            padding: 10px;
            font-size: 14px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            margin-right: 20px;
            color: #F14A51;
        }
        .proceed-btn {
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

    <center>
        <h2>REMITTANCE REPORT <span>[EDI - NEW FORMAT]</span></h2>
    </center>

    <div class="import-file">

        <form action="" method="post">

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
                <label for="status">Status</label>
                <select name="status" id="status" autocomplete="off" required>
                    <option value="">Select Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Pending & Inactive</option>?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : ''; ?>" required>
            </div>

            <input type="submit" class="proceed-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl1" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download2" value="Export to Excel for Recon">
            </form>
        </div><div id="showdl" style="display: none;">
            <form action="" method="post">
                <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
            </form>
        </div>

    </div>

    <script src="<?php echo $relative_path; ?>assets/js/admin/remitance-report-edi/edi-format/script1.js"></script>
</body>

</html>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $zone = $_POST['zone'];
    $region = $_POST['region'];
    $status = $_POST['status'];
    $restrictedDate = $_POST['restricted-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
    $_SESSION['status'] = $status;
    $_SESSION['restrictedDate'] = $restrictedDate;

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
        $sql = "SELECT
                r.branch_code,
                r.cost_center, 
                r.region, 
                r.zone,
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

                MAX(r.branch_name) as branch_name,

                MAX(r.ee_dr1) as ee_dr1,
                MAX(r.dr1) as dr1,
				SUM(
					COALESCE(r.ee_dr1, 0)+
					COALESCE(r.dr1, 0)
				) as total_ee_er_dr1,

                MAX(r.ee_dr2) as ee_dr2,
                MAX(r.dr2) as dr2,
				SUM(
					COALESCE(r.ee_dr2, 0)+
					COALESCE(r.dr2, 0)
				) as total_ee_er_dr2,

                MAX(r.ee_dr3) as ee_dr3,
                MAX(r.dr3) as dr3,
				SUM(
					COALESCE(r.ee_dr3, 0)+
					COALESCE(r.dr3, 0)
				) as total_ee_er_dr3,

                COUNT(DISTINCT r.branch_code) as branch_count
            FROM
                " . $database[0] . ".remitance_edi_report r
            WHERE
                r.mainzone = '$mainzone'
                AND r.remitance_date = '$restrictedDate'
                AND r.ml_matic_region = '$zone'
                AND r.zone LIKE '%$region%'
                AND NOT (r.branch_code = 18 AND r.zone = 'VIS')
                AND r.remitance_format_type = 'NEW'
                AND (
                    CASE 
                        WHEN r.ml_matic_status = 'Active' THEN 'Active'
                        WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                    END
                ) = '".$status."'
            GROUP BY 
                r.branch_code,
                r.cost_center, 
                r.region, 
                r.zone,
                r.remitance_date
            ORDER BY 
                r.region;";
    } else {
        $sql = "SELECT
                r.branch_code,
                r.cost_center, 
                r.region, 
                r.zone,
                r.remitance_date,
                r.ml_matic_status,

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

                MAX(r.branch_name) as branch_name,

                MAX(r.ee_dr1) as ee_dr1,
                MAX(r.dr1) as dr1,
				SUM(
					COALESCE(r.ee_dr1, 0)+
					COALESCE(r.dr1, 0)
				) as total_ee_er_dr1,

                MAX(r.ee_dr2) as ee_dr2,
                MAX(r.dr2) as dr2,
				SUM(
					COALESCE(r.ee_dr2, 0)+
					COALESCE(r.dr2, 0)
				) as total_ee_er_dr2,

                MAX(r.ee_dr3) as ee_dr3,
                MAX(r.dr3) as dr3,
				SUM(
					COALESCE(r.ee_dr2, 0)+
					COALESCE(r.dr2, 0)
				) as total_ee_er_dr3,

                COUNT(DISTINCT r.branch_code) as branch_count
            FROM
                " . $database[0] . ".remitance_edi_report r
            WHERE
                r.mainzone = '$mainzone'
                AND r.zone = '$zone'
                AND r.zone != 'JVIS'
                AND r.region_code LIKE '%$region%'
                AND r.remitance_date = '$restrictedDate'
                AND r.ml_matic_region != 'LNCR Showroom'
                AND r.ml_matic_region != 'VISMIN Showroom'
                AND r.remitance_format_type = 'NEW'
                AND (
                    CASE 
                        WHEN r.ml_matic_status = 'Active' THEN 'Active'
                        WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                    END
                ) = '".$status."'
            GROUP BY 
                r.branch_code,
                r.cost_center, 
                r.region, 
                r.zone,
                r.ml_matic_status,
                r.remitance_date
            ORDER BY 
                r.region;";
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

            if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                $color = '#4fc917';
                $bold = 'bold';
            } else {
                $color = 'none';
                $bold = 'normal';
            }

            $totalNumberOfBranches++;

            echo "<tr>";
				echo "<td class='word' style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_code']) . "</td>";
				echo "<td class='word' style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";

				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['ee_dr1'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['dr1'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['total_ee_er_dr1'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'></td>";
				echo "<td style='background-color: $color; font-weight: $bold'></td>";
				echo "<td></td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['ee_dr2'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['dr2'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['total_ee_er_dr2'],2)) . "</td>";
				echo "<td></td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['ee_dr3'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['dr3'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars(number_format($row['total_ee_er_dr3'],2)) . "</td>";
				echo "<td style='background-color: $color; font-weight: $bold'></td>";
				echo "<td style='background-color: $color; font-weight: $bold'></td>";
				echo "<td></td>";
				//echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['cost_center']) . "</td>";
				echo "<td class='word' style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
				echo "<td class='word' style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
            echo "</tr>";
        }


        echo "</tbody>";
        echo "</table>";
        echo "</div>
            
        <script>
            var dlbtn = document.getElementById('showdl');
            dlbtn.style.display = 'block';  
        </script>
		
		<script>
            var dlbtn = document.getElementById('showdl1');
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