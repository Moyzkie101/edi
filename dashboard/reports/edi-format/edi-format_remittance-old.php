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
		$status = $_SESSION['status'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate);
        
    }
	if (isset($_POST['download2'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        generateDownload2($conn, $database, $mainzone, $zone, $region, $restrictedDate);
        
    }

    // Function to generate the download excel file
    function generateDownload2($conn, $database, $mainzone, $zone, $region, $restrictedDate) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
    
        $dlsql = "SELECT
            r.branch_code,
            r.cost_center, 
            r.region, 
            r.zone,
            r.remitance_date,
            r.ml_matic_region,
            MAX(r.gl_code_dr1) as gl_code_dr1,
            MAX(r.gl_code_dr2) as gl_code_dr2,
            MAX(r.gl_code_dr3) as gl_code_dr3,
            MAX(r.gl_code_dr4) as gl_code_dr4,
            MAX(r.branch_name) as branch_name,
            MAX(r.dr1) as dr1,
            MAX(r.dr2) as dr2,
            MAX(r.dr3) as dr3,
            SUM(
                COALESCE(r.dr1, 0) +
                COALESCE(r.dr2, 0) +
                COALESCE(r.dr3, 0)
            ) as dr4,
            COUNT(DISTINCT r.branch_code) as branch_count
        FROM
            " . $database[0] . ".remitance_edi_report r
        WHERE
            r.remitance_date = '$restrictedDate'";
        if ($mainzone === 'ALL'){
            $dlsql .= " AND r.mainzone IN ('LNCR','VISMIN') ";
            if ($zone === 'ALL'){
                $dlsql .= " AND r.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN')";
            }
        }else{
            $dlsql .= " AND r.mainzone = '$mainzone' ";
            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                $dlsql .= " AND r.ml_matic_region = '$zone' AND r.zone LIKE '%$region%' ";
            }else{
                $dlsql .= " AND r.zone = '$zone' AND r.region_code LIKE '%$region%' AND NOT r.ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
            }
        }
        $dlsql .= " AND (
                CASE 
                    WHEN r.ml_matic_status = 'Active' THEN 'Active'
                    WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                END
                ) = '$status'
        GROUP BY 
            r.branch_code,
            r.cost_center, 
            r.region, 
            r.zone,
            r.ml_matic_region,
            r.remitance_date
        ORDER BY 
            r.region;";

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Fetch the first row to get header data
        $first_row = mysqli_fetch_assoc($dlresult);

        $payroll_date = htmlspecialchars($first_row['remitance_date']);
        $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
        $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
        $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
        $gl_code_dr4 = htmlspecialchars(3100001);

        // First row: DR and Cost Center headers
        $sheet->setCellValue('A1', 'PAYROLL REMITTANCE');
        $sheet->setCellValue('C1', 'DR');
        $sheet->setCellValue('D1', 'DR');
        $sheet->setCellValue('E1', 'DR');
        $sheet->setCellValue('F1', 'CR');
        $sheet->setCellValue('G1', 'Cost Center');
        $sheet->setCellValue('H1', 'Region');

        // Second row: Column headers
        $sheet->setCellValue('A2', 'BC Code');
        $sheet->setCellValue('B2', 'BC Name');
        $sheet->setCellValue('C2', $gl_code_dr1);
        $sheet->setCellValue('D2', $gl_code_dr2);
        $sheet->setCellValue('E2', $gl_code_dr3);
        $sheet->setCellValue('F2', $gl_code_dr4);
        $sheet->setCellValue('G2', '');
        $sheet->setCellValue('H2', '');

        // Make columns auto-size
        foreach (range('A', 'H') as $columnID) {
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
			$dr4 = $row['dr1'] + $row['dr2'] + $row['dr3'];
            $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
            $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
            
            // Use setCellValueExplicit for setting the value and format it as a number
            $sheet->setCellValueExplicit('C' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValueExplicit('E' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValueExplicit('F' . $rowIndex, $dr4, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
            
            $sheet->setCellValue('G' . $rowIndex, $row['cost_center']);
            $sheet->setCellValue('H' . $rowIndex, $row['region']);
        
            // Apply styles if flag is set
            if ($applyStyle) {
                $sheet->getStyle('A' . $rowIndex . ':H' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB($color);
            }
        
            // Apply bold style regardless of background color
            $sheet->getStyle('A' . $rowIndex . ':H' . $rowIndex)->getFont()->setBold($bold);
        
            $rowIndex++;
        }

        // Set headers to force download the Excel file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		if($status==='Active'){
			if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
				$filename = "EDI_Remitance_Old-Format_Report_" . $mainzone . "_" . $region . "_" . $restrictedDate . "_RECON.xls";
			}else{
				$filename = "EDI_Remitance_Old-Format_Report_" . $zone . "_" . $region . "_" . $restrictedDate . "_RECON.xls";
			}
		}else{
			if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
				$filename = "EDI_Remitance_Old-Format_Report_" . $mainzone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending)_RECON.xls";
			}else{
				$filename = "EDI_Remitance_Old-Format_Report_" . $zone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending)_RECON.xls";
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
    function generateDownload($conn, $database, $mainzone, $zone, $region, $restrictedDate) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
		$status = $_SESSION['status'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';


        $dlsql = "SELECT
            r.branch_code,
            r.cost_center, 
            r.region, 
            r.zone,
            r.remitance_date,
            r.ml_matic_region,
            MAX(r.gl_code_dr1) as gl_code_dr1,
            MAX(r.gl_code_dr2) as gl_code_dr2,
            MAX(r.gl_code_dr3) as gl_code_dr3,
            MAX(r.gl_code_dr4) as gl_code_dr4,
            MAX(r.branch_name) as branch_name,
            MAX(r.dr1) as dr1,
            MAX(r.dr2) as dr2,
            MAX(r.dr3) as dr3,
            SUM(
                COALESCE(r.dr1, 0) +
                COALESCE(r.dr2, 0) +
                COALESCE(r.dr3, 0)
            ) as dr4,
            COUNT(DISTINCT r.branch_code) as branch_count
        FROM
            " . $database[0] . ".remitance_edi_report r
        WHERE
            r.remitance_date = '$restrictedDate'";
        if ($mainzone === 'ALL'){
            $dlsql .= " AND r.mainzone IN ('LNCR','VISMIN') ";
            if ($zone === 'ALL'){
                $dlsql .= " AND r.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN')";
            }
        }else{
            $dlsql .= " AND r.mainzone = '$mainzone' ";
            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                $dlsql .= " AND r.ml_matic_region = '$zone' AND r.zone LIKE '%$region%' ";
            }else{
                $dlsql .= " AND r.zone = '$zone' AND r.region_code LIKE '%$region%' AND NOT r.ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
            }
        }
        $dlsql .= " AND (
                CASE 
                    WHEN r.ml_matic_status = 'Active' THEN 'Active'
                    WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                END
                ) = '$status'
        GROUP BY 
            r.branch_code,
            r.cost_center, 
            r.region, 
            r.zone,
            r.ml_matic_region,
            r.remitance_date
        ORDER BY 
            r.region;";

        //echo $dlsql;
        $dlresult = mysqli_query($conn, $dlsql);
    
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // --- Begin: Multi-zone Excel and ZIP logic ---
        if($mainzone === 'ALL' || $zone === 'ALL') {
            if(mysqli_num_rows($dlresult) > 0) {
                // Prepare arrays for each group
                $groups = [
                    'EDI_Remitance_Old-Format_Report_LZN_' => [],
                    'EDI_Remitance_Old-Format_Report_NCR_' => [],
                    'EDI_Remitance_Old-Format_Report_VIS_' => [],
                    'EDI_Remitance_Old-Format_Report_MIN_' => [],
                    'EDI_Remitance_Old-Format_Report_NATIONWIDE-SHOWROOM_' => [],
                ];

                // Categorize each row
                while ($row = mysqli_fetch_assoc($dlresult)) {
                    if (($row['zone'] === 'LZN') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Remitance_Old-Format_Report_LZN_'][] = $row;
                    } elseif (($row['zone'] === 'NCR') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Remitance_Old-Format_Report_NCR_'][] = $row;
                    } elseif (($row['zone'] === 'VIS') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Remitance_Old-Format_Report_VIS_'][] = $row;
                    } elseif (($row['zone'] === 'MIN') && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                        $groups['EDI_Remitance_Old-Format_Report_MIN_'][] = $row;
                    } elseif ($row['ml_matic_region'] === 'LNCR Showroom' || $row['ml_matic_region'] === 'VISMIN Showroom') {
                        $groups['EDI_Remitance_Old-Format_Report_NATIONWIDE-SHOWROOM_'][] = $row;
                    }
                }

                // Prepare temp dir for files
                $tmpDir = sys_get_temp_dir() . '/remitance_' . uniqid();
                if (!is_dir($tmpDir)) mkdir($tmpDir);

                $filePaths = [];
                foreach ($groups as $groupName => $rows) {
                    if(empty($rows)) continue; // Skip empty files

                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();

                    // Header rows (adjust as needed)
                    $sheet->setCellValue('A1', 'PAYROLL REMITTANCE');
                    $sheet->setCellValue('C1', 'DR');
                    $sheet->setCellValue('D1', 'DR');
                    $sheet->setCellValue('E1', 'DR');
                    $sheet->setCellValue('F1', 'CR');
                    $sheet->setCellValue('G1', 'Cost Center');

                    $sheet->setCellValue('A2', 'BC Code');
                    $sheet->setCellValue('B2', 'BC Name');
                    $sheet->setCellValue('C2', isset($rows[0]['gl_code_dr1']) ? $rows[0]['gl_code_dr1'] : '');
                    $sheet->setCellValue('D2', isset($rows[0]['gl_code_dr2']) ? $rows[0]['gl_code_dr2'] : '');
                    $sheet->setCellValue('E2', isset($rows[0]['gl_code_dr3']) ? $rows[0]['gl_code_dr3'] : '');
                    $sheet->setCellValue('F2', 3100001);
                    $sheet->setCellValue('G2', '');

                    foreach (range('A', 'G') as $columnID) {
                        $sheet->getColumnDimension($columnID)->setAutoSize(true);
                    }

                    $rowIndex = 3;
                    foreach ($rows as $row) {
                        $applyStyle = false;
                        if (strpos($row['cost_center'], '0001') === 0 && $row['ml_matic_region'] !== 'LNCR Showroom' && $row['ml_matic_region'] !== 'VISMIN Showroom') {
                            $color = '4fc917';
                            $bold = true;
                            $applyStyle = true;
                        } else {
                            $bold = false;
                        }
                        $dr4 = $row['dr1'] + $row['dr2'] + $row['dr3'];
                        $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                        $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
                        $sheet->setCellValueExplicit('C' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('E' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValueExplicit('F' . $rowIndex, $dr4, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                        $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                        $sheet->setCellValue('G' . $rowIndex, $row['cost_center']);

                        if ($applyStyle) {
                            $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB($color);
                        }
                        $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFont()->setBold($bold);

                        $rowIndex++;
                    }

                    // Save file
                    $filename = $groupName . $restrictedDate. '.xls';
                    $filePath = $tmpDir . '/' . $filename;
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
                    $writer->save($filePath);
                    $filePaths[] = $filePath;
                }

                // Create ZIP
                $zipname = "EDI_Remitance_Old-Format_Report_" . $restrictedDate . ".zip";
                $zipPath = $tmpDir . '/' . $zipname;
                $zip = new \ZipArchive();
                $zip->open($zipPath, \ZipArchive::CREATE);
                foreach ($filePaths as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                // Output ZIP
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . $zipname . '"');
                header('Content-Length: ' . filesize($zipPath));
                readfile($zipPath);

                // Cleanup
                foreach ($filePaths as $file) unlink($file);
                unlink($zipPath);
                rmdir($tmpDir);
                exit();
            }
        }else{

            // Fetch the first row to get header data
            $first_row = mysqli_fetch_assoc($dlresult);

            $payroll_date = htmlspecialchars($first_row['remitance_date']);
            $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
            $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
            $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
            $gl_code_dr4 = htmlspecialchars(3100001);

            // First row: DR and Cost Center headers
            $sheet->setCellValue('A1', 'PAYROLL REMITTANCE');
            $sheet->setCellValue('C1', 'DR');
            $sheet->setCellValue('D1', 'DR');
            $sheet->setCellValue('E1', 'DR');
            $sheet->setCellValue('F1', 'CR');
            $sheet->setCellValue('G1', 'Cost Center');

            // Second row: Column headers
            $sheet->setCellValue('A2', 'BC Code');
            $sheet->setCellValue('B2', 'BC Name');
            $sheet->setCellValue('C2', $gl_code_dr1);
            $sheet->setCellValue('D2', $gl_code_dr2);
            $sheet->setCellValue('E2', $gl_code_dr3);
            $sheet->setCellValue('F2', $gl_code_dr4);
            $sheet->setCellValue('G2', '');

            // Make columns auto-size
            foreach (range('A', 'G') as $columnID) {
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
                $dr4 = $row['dr1'] + $row['dr2'] + $row['dr3'];
                $sheet->setCellValue('A' . $rowIndex, $row['branch_code']);
                $sheet->setCellValue('B' . $rowIndex, $row['branch_name']);
                
                // Use setCellValueExplicit for setting the value and format it as a number
                $sheet->setCellValueExplicit('C' . $rowIndex, $row['dr1'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('C' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('D' . $rowIndex, $row['dr2'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('D' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('E' . $rowIndex, $row['dr3'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('E' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValueExplicit('F' . $rowIndex, $dr4, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
                $sheet->getStyle('F' . $rowIndex)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
                
                $sheet->setCellValue('G' . $rowIndex, $row['cost_center']);
            
                // Apply styles if flag is set
                if ($applyStyle) {
                    $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($color);
                }
            
                // Apply bold style regardless of background color
                $sheet->getStyle('A' . $rowIndex . ':G' . $rowIndex)->getFont()->setBold($bold);
            
                $rowIndex++;
            }

            // Set headers to force download the Excel file
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if($status==='Active'){
                if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                    $filename = "EDI_Remitance_Report_OLD_FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . ".xls";
                }else{
                    $filename = "EDI_Remitance_Report_OLD_FORMAT_" . $zone . "_" . $region . "_" . $restrictedDate . ".xls";
                }
            }else{
                if($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom'){
                    $filename = "EDI_Remitance_Report_OLD_FORMAT_" . $mainzone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
                }else{
                    $filename = "EDI_Remitance_Report_OLD_FORMAT_" . $zone . "_" . $region . "_" . $restrictedDate . "(Inactive or Pending).xls";
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

    <center><h2>Remitance Report <span>[EDI-Format OLD]</span></h2></center>

    <div class="import-file">
        
        <form action="" method="post">

        <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <option value="VISMIN" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
                    <option value="ALL" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL') ? 'selected' : ''; ?>>ALL Mainzone</option>
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
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="proceed-btn" name="generate" value="Proceed">
        </form>
		<?php if(isset($_POST['mainzone']) && $_POST['mainzone'] == 'ALL' || isset($_POST['zone']) && $_POST['zone'] == 'ALL') :?>
            <div id="showdl" style="display: none">
                <form id="exportForm" action="" method="post">
                    <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
                </form>
            </div>
        <?php else: ?>
            <div id="showdl1" style="display: none">
                <form id="exportForm" action="" method="post">
                    <input type="submit" class="download-btn" name="download2" value="Export to Excel for Recon">
                </form>
            </div>
            <div id="showdl" style="display: none">
                <form id="exportForm" action="" method="post">
                    <input type="submit" class="download-btn" name="download" value="Export to Excel for MLMatic">
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="<?php echo $relative_path; ?>assets/js/admin/remitance-report-edi/edi-format/script1.js"></script>
</body>
</html>

<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

$mainzone = $_POST['mainzone'];
$zone = $_POST['zone'];
$status = $_POST['status'];
$region = $_POST['region'];
$restrictedDate = $_POST['restricted-date']; 

$_SESSION['mainzone'] = $mainzone;
$_SESSION['zone'] = $zone;
$_SESSION['status'] = $status;
$_SESSION['region'] = $region;
$_SESSION['restrictedDate'] = $restrictedDate; 


$sql = "SELECT
            r.branch_code,
            r.cost_center, 
            r.region, 
            r.zone,
            r.remitance_date,
            r.ml_matic_region,
            MAX(r.gl_code_dr1) as gl_code_dr1,
            MAX(r.gl_code_dr2) as gl_code_dr2,
            MAX(r.gl_code_dr3) as gl_code_dr3,
            MAX(r.gl_code_dr4) as gl_code_dr4,
            MAX(r.branch_name) as branch_name,
            MAX(r.dr1) as dr1,
            MAX(r.dr2) as dr2,
            MAX(r.dr3) as dr3,
            SUM(
                COALESCE(r.dr1, 0) +
                COALESCE(r.dr2, 0) +
                COALESCE(r.dr3, 0)
            ) as dr4,
            COUNT(DISTINCT r.branch_code) as branch_count
        FROM
            " . $database[0] . ".remitance_edi_report r
        WHERE
            r.remitance_date = '$restrictedDate'";
        if ($mainzone === 'ALL'){
            $sql .= " AND r.mainzone IN ('LNCR','VISMIN') ";
            if ($zone === 'ALL'){
                $sql .= " AND r.zone IN ('LZN','NCR', 'VIS', 'JVIS', 'MIN')";
            }
        }else{
            $sql .= " AND r.mainzone = '$mainzone' ";
            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
                $sql .= " AND r.ml_matic_region = '$zone' AND r.zone LIKE '%$region%' ";
            }else{
                $sql .= " AND r.zone = '$zone' AND r.region_code LIKE '%$region%' AND NOT r.ml_matic_region IN ('LNCR Showroom', 'VISMIN Showroom') ";
            }
        }
        $sql .= " AND (
                CASE 
                    WHEN r.ml_matic_status = 'Active' THEN 'Active'
                    WHEN r.ml_matic_status IN ('Pending', 'Inactive') THEN 'Inactive'
                END
                ) = '$status'
        GROUP BY 
            r.branch_code,
            r.cost_center, 
            r.region, 
            r.zone,
            r.ml_matic_region,
            r.remitance_date
        ORDER BY 
            r.region;";

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
        $gl_code_dr4 = 3100001;
        //$gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);

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

            if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                $color = '#4fc917';
                $bold = 'bold';
            } else {
                $color = 'none';
                $bold = 'normal';
            }

            $totalNumberOfBranches++;

            echo "<tr>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_code']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr1'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr2'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr3'],2)) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold; text-align: right'>" . htmlspecialchars(number_format($row['dr4'],2)) . "</td>";
            //echo "<td style='white-space: nowrap'>" . htmlspecialchars($row['cost_center']) . "</td>";
            echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
            echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
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