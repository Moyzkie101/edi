<?php
include '../../config/connection.php';
session_start();

if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'user')) {
    header('location: ' . $auth_url . 'logout.php');
    session_destroy();
    exit();
} else {
    if (!isset($_SESSION['user_roles']) || empty($_SESSION['user_roles'])) {
        header('location: ' . $auth_url . 'logout.php');
        session_destroy();
        exit();
    }
    $roles          = array_map('trim', explode(',', $_SESSION['user_roles']));
    $hasRequiredRole = false;
    foreach ($roles as $role) {
        if ($role === 'CAD') { $hasRequiredRole = true; break; }
    }
    if (!$hasRequiredRole) {
        header('location: ' . $auth_url . 'logout.php');
        session_destroy();
        exit();
    }
}

require_once '../../vendor/autoload.php';

// ─────────────────────────────────────────────────────────────────────────────
// Helper functions
// ─────────────────────────────────────────────────────────────────────────────

function mapRegionData($conn1, $database, $regionCode)
{
    $regionCode = trim($regionCode);
    $sql = "SELECT rm.zone_code, rm.region_code, zm.zone_description, rm.region_description
            FROM " . $database[1] . ".zone_masterfile AS zm
            JOIN " . $database[1] . ".region_masterfile AS rm ON rm.zone_code = zm.zone_code";

    if ($regionCode === 'HEADOFFICE1' || $regionCode === 'HEADOFFICE2') {
        $sql .= " WHERE rm.region_code = '" . $conn1->real_escape_string($regionCode) . "'
                  AND rm.region_description IN ('HO VISMIN SUPPORT', 'HO LNCR SUPPORT')";
    } elseif ($regionCode === 'VISMIN-MANCOMM' || $regionCode === 'LNCR-MANCOMM') {
        $sql .= " WHERE zm.zone_code = '" . $conn1->real_escape_string($regionCode) . "'";
    } else {
        $sql .= " WHERE rm.region_code = '" . $conn1->real_escape_string($regionCode) . "'
                  AND rm.region_description NOT IN ('VISMIN-SUPPORT','LNCR-SUPPORT','VISMIN-MANCOMM','LNCR-MANCOMM')
                  ORDER BY rm.region_description ASC";
    }

    $result = $conn1->query($sql);
    $rowDb  = $result ? $result->fetch_assoc() : null;
    if (!$rowDb) return null;

    if ($regionCode === 'HEADOFFICE1') return ['zone' => 'VISMIN-SUPPORT', 'region' => 'HO VISMIN SUPPORT', 'region_code' => 'HEADOFFICE1'];
    if ($regionCode === 'HEADOFFICE2') return ['zone' => 'LNCR-SUPPORT',  'region' => 'HO LNCR SUPPORT',  'region_code' => 'HEADOFFICE2'];
    if ($regionCode === 'VISMIN-MANCOMM' || $regionCode === 'LNCR-MANCOMM') {
        return ['zone' => $rowDb['zone_code'], 'region' => $rowDb['zone_description'], 'region_code' => $rowDb['zone_code']];
    }
    return ['zone' => $rowDb['zone_code'], 'region' => $rowDb['region_description'], 'region_code' => $rowDb['region_code']];
}

function normalize_region_key($s)
{
    return strtoupper(trim(preg_replace('/[^A-Z0-9]/i', '', (string)$s)));
}

$mapVISMIN = [
    'BOHOL' => 'R05',    'BUKIDNON' => 'R30', 'CARGANRT' => 'R12',
    'CARGASUR' => 'R13', 'CDOMISOR' => 'R18', 'CEBCENA'  => 'R01',
    'CEBCENB'  => 'R27', 'COTBTMAG' => 'R17', 'DACODA'   => 'R15',
    'DAVAO'    => 'R14', 'LANAO'    => 'R19', 'LEYTEA'   => 'R06',
    'LEYTEB'   => 'R28', 'MANCOMM'  => 'MANCOMM1', 'NEGOCCA' => 'R08',
    'NEGOCCB'  => 'R29', 'NEGOR'    => 'R04', 'NORTHA'   => 'R02',
    'NORTHB'   => 'R26', 'PALAWAN'  => 'R25', 'PNYCNTRL' => 'R11',
    'PNYNORTH' => 'R10', 'PNYSOUTH' => 'R09', 'SAMAR'    => 'R07',
    'SARGEN'   => 'R16', 'SOCSK'    => 'R24', 'SOUTH'    => 'R03',
    'SUPPORT'  => 'HEADOFFICE1', 'ZAMBAS' => 'R23', 'ZAMSULTA' => 'R31',
    'ZANORTE'  => 'R21', 'ZASURMIS' => 'R20', 'ZMSIBUGY' => 'R22'
];

$mapLNCR = [
    'ALMASOR'  => 'LNCR05', 'BAZAM'    => 'LNCR06', 'BULACAN'  => 'LNCR07',
    'CAMACAT'  => 'LNCR08', 'ILOCABRA' => 'LNCR19', 'LAGUNA'   => 'LNCR09',
    'NCRBTNS'  => 'LNCR01', 'NCRCEN'   => 'LNCR02', 'NCRIZAL'  => 'LNCR04',
    'NCRNRTH'  => 'LNCR03', 'NEL'      => 'LNCR10', 'NOL'      => 'LNCR12',
    'NWL'      => 'LNCR11', 'PMPANGA'  => 'LNCR13', 'QUVISGAO' => 'LNCR14',
    'SEL'      => 'LNCR15', 'SOL'      => 'LNCR17', 'SUPPORTL' => 'HEADOFFICE2',
    'SWL'      => 'LNCR16', 'TARPAN'   => 'LNCR18'
];

function excelRegionToCode($raw, $mainzone)
{
    global $mapVISMIN, $mapLNCR;
    $key = normalize_region_key($raw);
    if ($mainzone === 'VISMIN' && isset($mapVISMIN[$key])) return $mapVISMIN[$key];
    if ($mainzone === 'LNCR'   && isset($mapLNCR[$key]))   return $mapLNCR[$key];
    return null;
}

function mapLoanTypeToColumn($rawLoanType)
{
    $key = strtoupper(trim((string)$rawLoanType));
    $key = preg_replace('/[^A-Z0-9]+/', '_', $key);
    $map = [
        'MLFUND_REGULAR'     => 'mlregular_amount',
        'MLFUND_COMAKERSHIP' => 'mlcomaker_amount',
        'MLFUND_PCL'         => 'mlpcl_amount',
        'MLFUND_JEWELRY'     => 'mljewelry_amount',
        'MLFUND_OPI'         => 'mlopi_amount',
        'MLFUND_EMERGENCY'   => 'mlemergency_amount',
    ];
    return $map[$key] ?? null;
}

/**
 * Compare two float values treating near-zero differences as equal.
 * Returns true if the values are considered different.
 */
function amountDiffers($a, $b)
{
    return abs((float)$a - (float)$b) > 0.001;
}

/**
 * Parse every sheet of the uploaded Excel file.
 * Returns validated + aggregated rows — no DB calls here.
 */
function parseExcelFile($filePath, $mainzone, $payrollDate, $conn1, $database)
{
    $spreadsheet                 = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $dataRows                    = [];
    $directMlfundRows            = [];
    $unknownRegionRows           = [];
    $invalidLoanTypeRows         = [];
    $invalidIdRows               = [];
    $invalidAmountRows           = [];
    $duplicateInFileRows         = [];
    $conflictingRegionRows       = [];
    $seenDuplicateRows           = [];
    $employeeRegionTracker       = [];
    $employeesWithRegionConflict = [];

    foreach ($spreadsheet->getAllSheets() as $worksheet) {
        $sheetName  = $worksheet->getTitle();
        $highestRow = $worksheet->getHighestDataRow();

        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
            $regionCodeInput = trim((string)$worksheet->getCell('A' . $rowIndex)->getValue());
            $idNo            = trim((string)$worksheet->getCell('B' . $rowIndex)->getValue());
            $lastName        = trim((string)$worksheet->getCell('C' . $rowIndex)->getValue());
            $firstName       = trim((string)$worksheet->getCell('D' . $rowIndex)->getValue());
            $loanTypeRaw     = trim((string)$worksheet->getCell('E' . $rowIndex)->getValue());
            $fundRaw         = trim((string)$worksheet->getCell('F' . $rowIndex)->getValue());

            if ($idNo === '' && $regionCodeInput === '' && $lastName === '' &&
                $firstName === '' && $loanTypeRaw === '' && $fundRaw === '') {
                continue;
            }

            // ── Direct MLFUND branch ─────────────────────────────────────────
            if (strtoupper(trim($loanTypeRaw)) === 'MLFUND') {
                if ($idNo === '' || !preg_match('/^\d{8}$/', $idNo))         continue;
                $mappedCode = excelRegionToCode($regionCodeInput, $mainzone);
                if (!$mappedCode)                                             continue;
                $regionData = mapRegionData(
                    $GLOBALS['conn1'], $GLOBALS['database'], $mappedCode
                );
                if (!$regionData)                                             continue;
                if (!is_numeric(str_replace(',', '', $fundRaw)))              continue;

                $directMlfundRows[] = [
                    'idno'               => $idNo,
                    'name'               => trim($lastName . ', ' . $firstName, ', '),
                    'zone'               => $regionData['zone'],
                    'region_code'        => $regionData['region_code'],
                    'region'             => $regionData['region'],
                    'ml_fund_amount'     => (float)str_replace(',', '', $fundRaw),
                    'mlregular_amount'   => 0,
                    'mlcomaker_amount'   => 0,
                    'mlpcl_amount'       => 0,
                    'mljewelry_amount'   => 0,
                    'mlopi_amount'       => 0,
                    'mlemergency_amount' => 0,
                ];
                continue;
            }

            // ── Standard loan-type rows ──────────────────────────────────────
            $fundCleaned = str_replace(',', '', $fundRaw);
            if ($fundRaw === '' || !is_numeric($fundCleaned)) {
                $invalidAmountRows[] = [
                    'sheet_name' => $sheetName,
                    'idno'       => $idNo,
                    'name'       => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type'  => $loanTypeRaw,
                    'fund_raw'   => $fundRaw,
                    'remarks'    => $fundRaw === ''
                                    ? 'missing fund amount'
                                    : 'invalid fund amount: "' . $fundRaw . '"',
                ];
                continue;
            }
            $fund = (float)$fundCleaned;

            if ($idNo === '' || !preg_match('/^\d{8}$/', $idNo)) {
                $invalidIdRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'missing or invalid employee id'];
                continue;
            }
            if ($loanTypeRaw === '') {
                $invalidLoanTypeRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'missing loan type'];
                continue;
            }
            if ($regionCodeInput === '0' || $regionCodeInput === '') {
                $unknownRegionRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'unknown region'];
                continue;
            }

            $mappedCode = excelRegionToCode($regionCodeInput, $mainzone);
            if (!$mappedCode) {
                $unknownRegionRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'unknown region'];
                continue;
            }

            $regionData = mapRegionData(
                $GLOBALS['conn1'], $GLOBALS['database'], $mappedCode
            );
            if (!$regionData) {
                $unknownRegionRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'unknown region'];
                continue;
            }

            // Region conflict check
            $empKey = $idNo . '|' . $payrollDate;
            if (isset($employeeRegionTracker[$empKey])) {
                if ($employeeRegionTracker[$empKey]['region_code'] !== $regionData['region_code']) {
                    $employeesWithRegionConflict[$empKey] = true;
                    $conflictingRegionRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw, 'fund' => $fund,
                        'remarks' => 'same employee has different region in one payroll',
                        'first_region'  => $employeeRegionTracker[$empKey]['region'],
                        'second_region' => $regionData['region']];
                    continue;
                }
            } else {
                $employeeRegionTracker[$empKey] = [
                    'region_code' => $regionData['region_code'],
                    'region'      => $regionData['region'],
                ];
            }
            if (isset($employeesWithRegionConflict[$empKey])) {
                $conflictingRegionRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'same employee has different region in one payroll',
                    'first_region'  => $employeeRegionTracker[$empKey]['region'] ?? '',
                    'second_region' => $regionData['region'] ?? ''];
                continue;
            }

            $loanColumn = mapLoanTypeToColumn($loanTypeRaw);
            if (!$loanColumn) {
                $invalidLoanTypeRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'invalid loan type'];
                continue;
            }

            $dupKey = $idNo . '|' . $loanColumn . '|' . $payrollDate;
            if (isset($seenDuplicateRows[$dupKey])) {
                $duplicateInFileRows[] = ['sheet_name' => $sheetName, 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'loan_type' => $loanTypeRaw, 'fund' => $fund,
                    'remarks' => 'duplicate in file'];
                continue;
            }
            $seenDuplicateRows[$dupKey] = true;

            $key = $idNo . '|' . $regionData['region_code'];
            if (!isset($dataRows[$key])) {
                $dataRows[$key] = [
                    'zone' => $regionData['zone'], 'region_code' => $regionData['region_code'],
                    'region' => $regionData['region'], 'idno' => $idNo,
                    'name' => trim($lastName . ', ' . $firstName, ', '),
                    'mlcomaker_amount' => 0, 'mljewelry_amount'   => 0,
                    'mlopi_amount'     => 0, 'mlemergency_amount' => 0,
                    'mlpcl_amount'     => 0, 'mlregular_amount'   => 0,
                ];
            }
            $dataRows[$key][$loanColumn] += $fund;
        }
    }

    return compact(
        'dataRows', 'directMlfundRows',
        'unknownRegionRows', 'invalidLoanTypeRows',
        'invalidIdRows', 'invalidAmountRows',
        'duplicateInFileRows', 'conflictingRegionRows'
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Result buckets
// ─────────────────────────────────────────────────────────────────────────────
$phase     = '0';  // 0=fresh, 1=phase1 done, 2=phase2 done
$importedRows      = [];  // successfully inserted (green)
$noChangeRows      = [];  // exist in DB, amounts identical (grey)
$changedRows       = [];  // exist in DB, amounts differ (yellow — pending overwrite)
$overwrittenRows   = [];  // actually overwritten in Phase 2 (orange)
$directImported    = [];  // direct MLFUND inserted (green)
$directNoChange    = [];  // direct MLFUND, no change (grey)
$directChanged     = [];  // direct MLFUND, changed (yellow)
$directOverwritten = [];  // direct MLFUND overwritten (orange)
$unknownRegionRows     = [];
$invalidLoanTypeRows   = [];
$invalidIdRows         = [];
$invalidAmountRows     = [];
$duplicateInFileRows   = [];
$conflictingRegionRows = [];
$tempFilePath  = '';
$postedMainzone = '';
$postedDate     = '';
$postedFileName = '';

// ─────────────────────────────────────────────────────────────────────────────
// POST handler
// ─────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $phase          = $_POST['phase']             ?? '1';
    $postedMainzone = trim($_POST['mainzone']      ?? '');
    $postedDate     = trim($_POST['restricted-date'] ?? '');
    $postedFileName = $_POST['original_filename']  ?? '';
    $uploadedBy     = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown user';
    $uploadedDate   = date('Y-m-d H:i:s');
    $postEdi        = 'pending';

    // ── Shared: resolve temp file path ────────────────────────────────────────
    if ($phase === '1') {
        if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('File upload failed. Please try again.');</script>";
            $phase = '0';
        } else {
            $ext = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['xlsx', 'xls'])) {
                echo "<script>alert('Please upload an XLSX or XLS file.');</script>";
                $phase = '0';
            } else {
                $postedFileName = $_FILES['excelFile']['name'];
                $tempFilePath   = sys_get_temp_dir() . '/mlfund_' . session_id() . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['excelFile']['tmp_name'], $tempFilePath);
            }
        }
    } elseif ($phase === '2') {
        $tempFilePath = $_POST['temp_file_path'] ?? '';
        if (!$tempFilePath || !file_exists($tempFilePath)) {
            echo "<script>alert('Session expired or temp file missing. Please re-upload.');</script>";
            $phase = '0';
        }
    }

    if ($phase !== '0') {

        $parsed = parseExcelFile($tempFilePath, $postedMainzone, $postedDate, $conn1, $database);

        $unknownRegionRows     = $parsed['unknownRegionRows'];
        $invalidLoanTypeRows   = $parsed['invalidLoanTypeRows'];
        $invalidIdRows         = $parsed['invalidIdRows'];
        $invalidAmountRows     = $parsed['invalidAmountRows'];
        $duplicateInFileRows   = $parsed['duplicateInFileRows'];
        $conflictingRegionRows = $parsed['conflictingRegionRows'];

        $hasErrors = !empty($unknownRegionRows)   || !empty($invalidLoanTypeRows) ||
                     !empty($invalidIdRows)        || !empty($invalidAmountRows)   ||
                     !empty($duplicateInFileRows)  || !empty($conflictingRegionRows);

        if ($hasErrors) {
            // Errors found — abort everything, keep temp file for error display only
            if ($phase === '1') @unlink($tempFilePath);
            $tempFilePath = '';
            $phase = '1'; // stay on phase 1 view so errors render

        } else {

            $fileExtension = strtolower(pathinfo($postedFileName, PATHINFO_EXTENSION));

            // ── Helper: INSERT one standard row ──────────────────────────────
            $doInsertStandard = function($row, $totalFund)
                use ($conn, $database, $postedDate, $postedMainzone, $uploadedBy, $uploadedDate, $postEdi) {
                $ins = $conn->prepare(
                    "INSERT INTO " . $database[0] . ".mlfund_payroll_new
                     (payroll_date, mainzone, zone, region_code, region,
                      employee_id_no, employee_name,
                      mlcomaker_amount, mljewelry_amount, mlopi_amount,
                      mlpcl_amount, mlemergency_amount, mlregular_amount,
                      ml_fund_amount, uploaded_by, uploaded_date, post_edi)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                    //        s s s s s  s  s  d d d  d  d  d   d  s  s  s  = 7s+7d+3s = 17
                );
                if (!$ins) die('Prepare failed (insert standard): ' . $conn->error);
                $ins->bind_param(
                    'sssssssdddddddsss',  // 7s + 7d + 3s = 17
                    $postedDate,                // s (1)
                    $postedMainzone,            // s (2)
                    $row['zone'],               // s (3)
                    $row['region_code'],        // s (4)
                    $row['region'],             // s (5)
                    $row['idno'],               // s (6)
                    $row['name'],               // s (7)
                    $row['mlcomaker_amount'],   // d (8)
                    $row['mljewelry_amount'],   // d (9)
                    $row['mlopi_amount'],       // d (10)
                    $row['mlpcl_amount'],       // d (11)
                    $row['mlemergency_amount'], // d (12)
                    $row['mlregular_amount'],   // d (13)
                    $totalFund,                 // d (14)
                    $uploadedBy,                // s (15)
                    $uploadedDate,              // s (16)
                    $postEdi                    // s (17)
                );
                $ins->execute();
                if ($ins->errno) die('Execute failed (insert standard): ' . $ins->error);
                $ins->close();
            };

            // ── Helper: UPDATE one standard row ──────────────────────────────
            $doUpdateStandard = function($row, $totalFund)
                use ($conn, $database, $postedDate, $postedMainzone, $uploadedBy, $uploadedDate, $postEdi) {
                $upd = $conn->prepare(
                    "UPDATE " . $database[0] . ".mlfund_payroll_new
                     SET zone               = ?,   -- s (1)
                         region_code        = ?,   -- s (2)
                         region             = ?,   -- s (3)
                         employee_name      = ?,   -- s (4)
                         mlcomaker_amount   = ?,   -- d (5)
                         mljewelry_amount   = ?,   -- d (6)
                         mlopi_amount       = ?,   -- d (7)
                         mlpcl_amount       = ?,   -- d (8)
                         mlemergency_amount = ?,   -- d (9)
                         mlregular_amount   = ?,   -- d (10)
                         ml_fund_amount     = ?,   -- d (11)
                         uploaded_by        = ?,   -- s (12)
                         uploaded_date      = ?,   -- s (13)
                         post_edi           = ?    -- s (14)
                     WHERE payroll_date    = ?    -- s (15)
                       AND employee_id_no  = ?    -- s (16)
                       AND mainzone        = ?"   // s (17)
                );
                if (!$upd) die('Prepare failed (update standard): ' . $conn->error);
                $upd->bind_param(
                    'ssssdddddddssssss',  // 4s + 7d + 6s = 17
                    $row['zone'],               // s (1)
                    $row['region_code'],        // s (2)
                    $row['region'],             // s (3)
                    $row['name'],               // s (4)
                    $row['mlcomaker_amount'],   // d (5)
                    $row['mljewelry_amount'],   // d (6)
                    $row['mlopi_amount'],       // d (7)
                    $row['mlpcl_amount'],       // d (8)
                    $row['mlemergency_amount'], // d (9)
                    $row['mlregular_amount'],   // d (10)
                    $totalFund,                 // d (11)
                    $uploadedBy,                // s (12)
                    $uploadedDate,              // s (13)
                    $postEdi,                   // s (14)
                    $postedDate,                // s (15) WHERE
                    $row['idno'],               // s (16) WHERE
                    $postedMainzone             // s (17) WHERE
                );
                $upd->execute();
                if ($upd->errno) die('Execute failed (update standard): ' . $upd->error);
                $upd->close();
            };

            // ── Process standard rows ─────────────────────────────────────────
            foreach ($parsed['dataRows'] as $row) {
                $totalFund = $row['mlregular_amount'] + $row['mlcomaker_amount']
                    + $row['mlpcl_amount']   + $row['mljewelry_amount']
                    + $row['mlopi_amount']   + $row['mlemergency_amount'];

                // Check if record exists in DB
                $chk = $conn->prepare(
                    "SELECT mlregular_amount, mlcomaker_amount, mlpcl_amount,
                            mljewelry_amount, mlopi_amount, mlemergency_amount, ml_fund_amount
                     FROM " . $database[0] . ".mlfund_payroll_new
                     WHERE payroll_date = ? AND employee_id_no = ? AND mainzone = ?"
                );
                $chk->bind_param('sss', $postedDate, $row['idno'], $postedMainzone);
                $chk->execute();
                $chkResult = $chk->get_result();
                $dbRow     = $chkResult ? $chkResult->fetch_assoc() : null;
                $chk->close();

                $entry = array_merge($row, ['ml_fund_amount' => $totalFund]);

                if (!$dbRow) {
                    // ── Not in DB: insert immediately (both Phase 1 and 2) ───
                    $doInsertStandard($row, $totalFund);
                    $importedRows[] = array_merge($entry, ['remarks' => 'imported']);

                } else {
                    // ── Exists: compare amounts field by field ───────────────
                    $hasChange =
                        amountDiffers($row['mlregular_amount'],   $dbRow['mlregular_amount'])   ||
                        amountDiffers($row['mlcomaker_amount'],   $dbRow['mlcomaker_amount'])   ||
                        amountDiffers($row['mlpcl_amount'],       $dbRow['mlpcl_amount'])       ||
                        amountDiffers($row['mljewelry_amount'],   $dbRow['mljewelry_amount'])   ||
                        amountDiffers($row['mlopi_amount'],       $dbRow['mlopi_amount'])       ||
                        amountDiffers($row['mlemergency_amount'], $dbRow['mlemergency_amount']);

                    // Attach DB values so we can show "old vs new" in the table
                    $entry['db_mlregular_amount']   = (float)$dbRow['mlregular_amount'];
                    $entry['db_mlcomaker_amount']   = (float)$dbRow['mlcomaker_amount'];
                    $entry['db_mlpcl_amount']       = (float)$dbRow['mlpcl_amount'];
                    $entry['db_mljewelry_amount']   = (float)$dbRow['mljewelry_amount'];
                    $entry['db_mlopi_amount']       = (float)$dbRow['mlopi_amount'];
                    $entry['db_mlemergency_amount'] = (float)$dbRow['mlemergency_amount'];
                    $entry['db_ml_fund_amount']     = (float)$dbRow['ml_fund_amount'];

                    if (!$hasChange) {
                        $noChangeRows[] = array_merge($entry, ['remarks' => 'no change']);
                    } else {
                        if ($phase === '2') {
                            // Overwrite confirmed
                            $doUpdateStandard($row, $totalFund);
                            $overwrittenRows[] = array_merge($entry, ['remarks' => 'overwritten']);
                        } else {
                            // Phase 1 — show as changed/pending
                            $changedRows[] = array_merge($entry, ['remarks' => 'data changed']);
                        }
                    }
                }
            }

            // ── Process direct MLFUND rows ────────────────────────────────────
            $insDirectStmt = $conn->prepare(
                "INSERT INTO edi.mlfund_payroll
                 (payroll_date, mainzone, zone, region_code, region,
                  employee_id_no, employee_name, ml_fund_amount,
                  extension_file_type, uploaded_by, uploaded_date, post_edi)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
                // s s s s s  s  s  d  s  s  s  s = 7s+1d+4s = 12
            );
            $updDirectStmt = $conn->prepare(
                "UPDATE edi.mlfund_payroll
                 SET mainzone             = ?,   -- s (1)
                     zone                = ?,   -- s (2)
                     region_code         = ?,   -- s (3)
                     region              = ?,   -- s (4)
                     employee_name       = ?,   -- s (5)
                     ml_fund_amount      = ?,   -- d (6)
                     extension_file_type = ?,   -- s (7)
                     uploaded_by         = ?,   -- s (8)
                     uploaded_date       = ?,   -- s (9)
                     post_edi            = ?    -- s (10)
                 WHERE payroll_date      = ?    -- s (11)
                   AND employee_id_no    = ?"   // s (12)
            );

            foreach ($parsed['directMlfundRows'] as $row) {
                // Check existence + fetch current amount
                $chkD = $conn->prepare(
                    "SELECT ml_fund_amount FROM edi.mlfund_payroll
                     WHERE payroll_date = ? AND employee_id_no = ?"
                );
                $chkD->bind_param('ss', $postedDate, $row['idno']);
                $chkD->execute();
                $chkDResult = $chkD->get_result();
                $dbDRow     = $chkDResult ? $chkDResult->fetch_assoc() : null;
                $chkD->close();

                $entry = array_merge($row);

                if (!$dbDRow) {
                    // INSERT immediately
                    $insDirectStmt->bind_param(
                        'sssssssdssss', // 7s + 1d + 4s = 12
                        $postedDate,            // s (1)
                        $postedMainzone,        // s (2)
                        $row['zone'],           // s (3)
                        $row['region_code'],    // s (4)
                        $row['region'],         // s (5)
                        $row['idno'],           // s (6)
                        $row['name'],           // s (7)
                        $row['ml_fund_amount'], // d (8)
                        $fileExtension,         // s (9)
                        $uploadedBy,            // s (10)
                        $uploadedDate,          // s (11)
                        $postEdi                // s (12)
                    );
                    $insDirectStmt->execute();
                    if ($insDirectStmt->errno) die('Execute failed (direct insert): ' . $insDirectStmt->error);
                    $directImported[] = array_merge($entry, ['remarks' => 'imported (MLFUND direct)']);

                } else {
                    $hasChange = amountDiffers($row['ml_fund_amount'], $dbDRow['ml_fund_amount']);
                    $entry['db_ml_fund_amount'] = (float)$dbDRow['ml_fund_amount'];

                    if (!$hasChange) {
                        $directNoChange[] = array_merge($entry, ['remarks' => 'no change']);
                    } else {
                        if ($phase === '2') {
                            $updDirectStmt->bind_param(
                                'ssssssdsssss', // 6s + 1d + 5s = 12
                                $postedMainzone,        // s (1)
                                $row['zone'],           // s (2)
                                $row['region_code'],    // s (3)
                                $row['region'],         // s (4)
                                $row['name'],           // s (5)
                                $row['ml_fund_amount'], // d (6)
                                $fileExtension,         // s (7)
                                $uploadedBy,            // s (8)
                                $uploadedDate,          // s (9)
                                $postEdi,               // s (10)
                                $postedDate,            // s (11) WHERE
                                $row['idno']            // s (12) WHERE
                            );
                            $updDirectStmt->execute();
                            if ($updDirectStmt->errno) die('Execute failed (direct update): ' . $updDirectStmt->error);
                            $directOverwritten[] = array_merge($entry, ['remarks' => 'overwritten (MLFUND direct)']);
                        } else {
                            $directChanged[] = array_merge($entry, ['remarks' => 'data changed']);
                        }
                    }
                }
            }

            $insDirectStmt->close();
            $updDirectStmt->close();

            // Clean up temp file after Phase 2 commit
            if ($phase === '2') {
                @unlink($tempFilePath);
                $tempFilePath = '';
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// View helpers
// ─────────────────────────────────────────────────────────────────────────────
$errorRowsForPdf = array_values(array_merge(
    $unknownRegionRows, $invalidLoanTypeRows,
    $invalidIdRows, $invalidAmountRows,
    $duplicateInFileRows, $conflictingRegionRows
));

$hasErrors        = !empty($errorRowsForPdf);
$pendingChanged   = array_merge($changedRows, $directChanged);     // yellow rows waiting for overwrite confirm
$hasChanged       = !empty($pendingChanged);
$hasResults       = !empty($importedRows)    || !empty($noChangeRows)    ||
                    !empty($changedRows)      || !empty($overwrittenRows) ||
                    !empty($directImported)   || !empty($directNoChange)  ||
                    !empty($directChanged)    || !empty($directOverwritten)||
                    $hasErrors;

$totalImported    = count($importedRows)  + count($directImported);
$totalOverwritten = count($overwrittenRows) + count($directOverwritten);
$totalChanged     = count($changedRows)   + count($directChanged);
$totalNoChange    = count($noChangeRows)  + count($directNoChange);
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
        .display_data { display:flex; align-items:center; justify-content:center; flex-wrap:wrap; gap:10px; }
        .card         { padding:10px; display:flex; align-items:center; justify-content:center; }
        .form         { display:flex; align-items:center; flex-wrap:wrap; width:100%; padding:10px; gap:10px; }
        .cancel_date label { font-size:14px; margin-right:15px; }
        div .cancel_date   { margin-right:15px; color:#000; }
        .import-file  { display:flex; }
        select, input[type="date"] {
            width:200px; padding:10px; border:2px solid #ccc; border-radius:15px;
            background:#f9f9f9; color:#F14A51;
        }
        .upload-btn {
            background:#d70c0c; color:#fff; padding:5px 10px; font-size:12px; font-weight:700;
            border:1px solid #fff; border-top-right-radius:10px; border-bottom-right-radius:10px;
            width:100px; margin-right:25px; cursor:pointer;
        }
        .choose-file input[type="file"] {
            display:block; padding:5px; border:1px solid #ccc;
            border-top-left-radius:10px; border-bottom-left-radius:10px;
            margin-left:25px; background:#fff; color:#F14A51;
        }

        /* ── Loading overlay ── */
        #loading-overlay {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(255,255,255,0.75); z-index:9999;
            backdrop-filter:blur(2px);
        }
        .loading-spinner {
            position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
            width:52px; height:52px; border-radius:50%;
            border:5px solid #e9ecef; border-top:5px solid #d70c0c;
            animation:spin 0.85s linear infinite;
        }
        @keyframes spin {
            0%   { transform:translate(-50%,-50%) rotate(0deg); }
            100% { transform:translate(-50%,-50%) rotate(360deg); }
        }

        /* ── Results table ── */
        .table-container {
            max-width:100%; overflow:auto;
            max-height:calc(100vh - 220px);
            margin:0 20px 24px;
            border:1px solid #dee2e6;
            border-radius:10px;
            box-shadow:0 2px 8px rgba(0,0,0,.06);
        }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { border:1px solid #dee2e6; padding:7px 8px; text-align:center; white-space:nowrap; }
        th {
            background:#f8f9fa; font-weight:700; font-size:12px;
            letter-spacing:.4px; text-transform:uppercase;
            position:sticky; top:0; z-index:2;
            border-bottom:2px solid #dee2e6;
        }
        tr:nth-child(even) td { background:rgba(0,0,0,.018); }
        tbody tr:hover td { filter:brightness(.96); transition:filter .15s; }

        /* ── Row status colours ── */
        .row-imported    { background:#d1f0da !important; color:#0f5132; }
        .row-changed     { background:#fff8db !important; color:#5c4a00; }
        .row-overwritten { background:#ffe5a0 !important; color:#5a3e00; }
        .row-nochange    { background:#e9ecef !important; color:#495057; }
        .row-error       { background:#fce4e4 !important; color:#7b1d1d; }

        /* ── Changed-cell diff display ── */
        .cell-changed { font-weight:700; }
        .cell-changed .old-val {
            display:block; font-size:10px; font-weight:400;
            text-decoration:line-through; opacity:.55; line-height:1.2;
        }
        .cell-changed .new-val { display:block; font-size:13px; font-weight:700; line-height:1.3; }

        /* ── Action bar (overwrite prompt — sits ABOVE table) ── */
        .action-bar {
            display:flex; align-items:center; justify-content:space-between;
            flex-wrap:wrap; gap:12px;
            margin:0 20px 14px;
            padding:14px 20px;
            background:linear-gradient(135deg,#fffbe6 0%,#fff8d0 100%);
            border:2px solid #e6b800;
            border-radius:12px;
            box-shadow:0 3px 10px rgba(230,184,0,.18);
        }
        .action-bar-left {
            display:flex; align-items:center; gap:12px; flex:1 1 auto;
        }
        .action-bar-left .bar-icon { font-size:26px; color:#c98800; flex-shrink:0; }
        .action-bar-left .bar-text { font-size:13px; color:#4a3800; line-height:1.5; }
        .action-bar-left .bar-text strong { font-size:14px; color:#2d2200; }
        .action-bar-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }

        /* ── Buttons ── */
        .btn-overwrite {
            display:inline-flex; align-items:center; gap:7px;
            background:linear-gradient(135deg,#e53935,#c0392b);
            color:#fff; border:none; border-radius:9px;
            padding:10px 20px; font-size:13px; font-weight:700;
            cursor:pointer; white-space:nowrap;
            box-shadow:0 3px 8px rgba(192,57,43,.35);
            transition:background .2s, transform .1s, box-shadow .2s;
        }
        .btn-overwrite:hover {
            background:linear-gradient(135deg,#c62828,#a93226);
            box-shadow:0 5px 14px rgba(192,57,43,.45);
            transform:translateY(-1px);
        }
        .btn-overwrite:active { transform:translateY(0); }

        .btn-cancel {
            display:inline-flex; align-items:center; gap:7px;
            background:#f8f9fa; color:#495057;
            border:1px solid #ced4da; border-radius:9px;
            padding:10px 18px; font-size:13px; font-weight:600;
            text-decoration:none; cursor:pointer; white-space:nowrap;
            transition:background .2s, border-color .2s;
        }
        .btn-cancel:hover { background:#e9ecef; border-color:#adb5bd; color:#343a40; }

        button.export-btn {
            display:inline-flex; align-items:center; gap:7px;
            border-radius:9px; padding:8px 16px; font-size:13px; font-weight:600;
            background:linear-gradient(135deg,#e53935,#c0392b);
            color:#fff; border:none; cursor:pointer;
            box-shadow:0 2px 6px rgba(192,57,43,.3);
            transition:background .2s, box-shadow .2s;
        }
        button.export-btn:hover {
            background:linear-gradient(135deg,#c62828,#a93226);
            box-shadow:0 4px 10px rgba(192,57,43,.4);
        }

        /* ── Legend strip ── */
        .legend {
            display:flex; flex-wrap:wrap; gap:8px;
            justify-content:center; align-items:center;
            margin:0 20px 12px; padding:10px 16px;
            background:#fff; border:1px solid #dee2e6;
            border-radius:10px;
        }
        .legend-label {
            font-size:11px; font-weight:700; color:#6c757d;
            text-transform:uppercase; letter-spacing:.5px; margin-right:4px;
        }
        .legend-item {
            display:inline-flex; align-items:center; gap:5px;
            padding:4px 11px; border-radius:20px;
            font-size:12px; font-weight:600;
            border:1px solid rgba(0,0,0,.08);
        }
        .legend-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0; }

        /* ── Status banners ── */
        .banner {
            display:flex; align-items:center; gap:12px;
            margin:0 20px 14px; padding:13px 18px;
            border-radius:10px; font-size:13px; font-weight:600;
            border-left:5px solid transparent;
        }
        .banner-success { background:#d1f0da; color:#0f5132; border-left-color:#198754; }
        .banner-error   { background:#fce4e4; color:#7b1d1d; border-left-color:#dc3545; }
        .banner i { font-size:18px; flex-shrink:0; }
        .banner-text { line-height:1.5; }
        .banner-text span { display:block; font-size:12px; font-weight:400; opacity:.8; margin-top:2px; }
    </style>
</head>
<body>
<div class="top-content">
    <?php include $relative_path . 'templates/sidebar.php' ?>
</div>

<center><h2>ML FUND NEW FORMAT <span>[IMPORT]</span></h2></center>

<div id="loading-overlay"><div class="loading-spinner"></div></div>

<!-- ── Upload form ── -->
<div class="card">
    <div class="card-body">
        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="form">
            <input type="hidden" name="phase" value="1">

            <div class="cancel_date">
                <label for="mainzone">Mainzone</label>
                <select name="mainzone" id="mainzone" required>
                    <option value="">Select Mainzone</option>
                    <option value="VISMIN" <?php echo ($postedMainzone === 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR"   <?php echo ($postedMainzone === 'LNCR')   ? 'selected' : ''; ?>>LNCR</option>
                </select>
            </div>

            <div class="cancel_date">
                <label for="restricted-date">Payroll date</label>
                <input type="date" id="restricted-date" name="restricted-date"
                       value="<?php echo htmlspecialchars($postedDate, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="choose-file">
                <div class="import-file">
                    <input type="file" name="excelFile" accept=".xls,.xlsx" class="form-control" required>
                    <input type="submit" class="upload-btn" value="Upload"
                           onclick="document.getElementById('loading-overlay').style.display='block';">
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ── Phase 2 success banner ── -->
<?php if ($phase === '2' && ($totalImported > 0 || $totalOverwritten > 0)): ?>
<div class="banner banner-success">
    <i class="fa-solid fa-circle-check"></i>
    <div class="banner-text">
        Import complete
        <span>
            <?php if ($totalImported > 0)    echo $totalImported    . ' record(s) imported.&nbsp;&nbsp;'; ?>
            <?php if ($totalOverwritten > 0) echo $totalOverwritten . ' record(s) overwritten.&nbsp;&nbsp;'; ?>
            <?php if ($totalNoChange > 0)    echo $totalNoChange    . ' record(s) had no change.'; ?>
        </span>
    </div>
</div>
<?php endif; ?>

<!-- ── Results section ── -->
<?php if ($hasResults): ?>

    <!-- ── Overwrite action bar — ABOVE the table, only when changed rows exist ── -->
    <?php if ($phase === '1' && $hasChanged && !$hasErrors): ?>
    <div class="action-bar">
        <div class="action-bar-left">
            <i class="fa-solid fa-triangle-exclamation bar-icon"></i>
            <div class="bar-text">
                <strong><?php echo $totalChanged; ?> record(s) have changed amounts</strong><br>
                The yellow rows below differ from the current database values.
                New and no-change records have already been saved.
                Click <em>Overwrite</em> to update only the changed records.
            </div>
        </div>
        <div class="action-bar-right">
            <form method="POST" id="overwriteForm">
                <input type="hidden" name="phase"             value="2">
                <input type="hidden" name="mainzone"          value="<?php echo htmlspecialchars($postedMainzone, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="restricted-date"   value="<?php echo htmlspecialchars($postedDate,     ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="temp_file_path"    value="<?php echo htmlspecialchars($tempFilePath,   ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="original_filename" value="<?php echo htmlspecialchars($postedFileName, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn-overwrite"
                        onclick="return confirmOverwrite(<?php echo $totalChanged; ?>)">
                    <i class="fa-solid fa-rotate"></i>
                    Overwrite <?php echo $totalChanged; ?> Changed Record(s)
                </button>
            </form>
            <a href="" class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> Cancel
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Error block notice ── -->
    <?php if ($hasErrors): ?>
    <div class="banner banner-error">
        <i class="fa-solid fa-circle-xmark"></i>
        <div class="banner-text">
            Import blocked — errors detected in the file
            <span>Fix the rows highlighted in red below, then re-upload the file.</span>
        </div>
        <?php if (!empty($errorRowsForPdf)): ?>
        <button type="button" class="export-btn" onclick="exportErrorsToPDF()" style="margin-left:auto;flex-shrink:0;">
            <i class="fa-solid fa-file-pdf"></i> Export Errors to PDF
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Legend strip ── -->
    <div class="legend">
        <span class="legend-label">Legend:</span>
        <?php if ($totalImported > 0): ?>
        <span class="legend-item" style="background:#d1f0da;color:#0f5132;">
            <span class="legend-dot" style="background:#198754;"></span> Imported (<?php echo $totalImported; ?>)
        </span>
        <?php endif; ?>
        <?php if ($hasChanged): ?>
        <span class="legend-item" style="background:#fff8db;color:#5c4a00;">
            <span class="legend-dot" style="background:#e6b800;"></span> Data Changed (<?php echo $totalChanged; ?>)
        </span>
        <?php endif; ?>
        <?php if ($totalOverwritten > 0): ?>
        <span class="legend-item" style="background:#ffe5a0;color:#5a3e00;">
            <span class="legend-dot" style="background:#e6a817;"></span> Overwritten (<?php echo $totalOverwritten; ?>)
        </span>
        <?php endif; ?>
        <?php if ($totalNoChange > 0): ?>
        <span class="legend-item" style="background:#e9ecef;color:#495057;">
            <span class="legend-dot" style="background:#6c757d;"></span> No Change (<?php echo $totalNoChange; ?>)
        </span>
        <?php endif; ?>
        <?php if ($hasErrors): ?>
        <span class="legend-item" style="background:#fce4e4;color:#7b1d1d;">
            <span class="legend-dot" style="background:#dc3545;"></span> Error (<?php echo count($errorRowsForPdf); ?>)
        </span>
        <?php endif; ?>
    </div>

    <!-- ── Results table ── -->
    <div class="table-container">
        <table id="printableTable">
            <thead>
                <tr>
                    <th colspan="12" style="text-align:center;padding:9px 12px;font-size:13px;text-transform:none;letter-spacing:0;font-weight:600;color:#495057;">
                        Payroll Date: <strong><?php echo date('F d, Y', strtotime($postedDate)); ?></strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Mainzone: <strong><?php echo htmlspecialchars($postedMainzone, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <!-- <?php if ($totalOverwritten > 0): ?>
                        &nbsp;&nbsp;|&nbsp;&nbsp;<span style="color:#5a3e00;">⚠ Overwrite applied</span>
                        <?php endif; ?> -->
                    </th>
                </tr>
                <tr>
                    <th>IDNO</th><th>Name</th><th>Region</th>
                    <th>Regular</th><th>Comaker</th><th>PCL</th>
                    <th>Jewelry</th><th>Emergency</th><th>OPI</th>
                    <th>Total Fund</th><th>Remarks</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            /**
             * Render a standard imported/overwritten/no-change/changed row.
             * For changed/overwritten rows, each cell shows "old → new" if different.
             */
            function renderStandardRow($row, $cssClass, $showDiff = false) {
                $amountCols = [
                    'mlregular_amount', 'mlcomaker_amount', 'mlpcl_amount',
                    'mljewelry_amount', 'mlemergency_amount', 'mlopi_amount'
                ];
                echo '<tr class="' . $cssClass . '">';
                echo '<td>' . htmlspecialchars($row['idno']   ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['name']   ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['region'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';

                foreach ($amountCols as $col) {
                    $newVal = (float)($row[$col] ?? 0);
                    $dbCol  = 'db_' . $col;
                    $dbVal  = isset($row[$dbCol]) ? (float)$row[$dbCol] : null;

                    if ($showDiff && $dbVal !== null && abs($newVal - $dbVal) > 0.001) {
                        echo '<td class="cell-changed">'
                            . '<span class="old-val">' . number_format($dbVal, 2) . '</span>'
                            . '<span class="new-val">' . number_format($newVal, 2) . '</span>'
                            . '</td>';
                    } else {
                        echo '<td>' . number_format($newVal, 2) . '</td>';
                    }
                }

                $total   = (float)($row['ml_fund_amount'] ?? 0);
                $dbTotal = isset($row['db_ml_fund_amount']) ? (float)$row['db_ml_fund_amount'] : null;
                if ($showDiff && $dbTotal !== null && abs($total - $dbTotal) > 0.001) {
                    echo '<td class="cell-changed">'
                        . '<span class="old-val">' . number_format($dbTotal, 2) . '</span>'
                        . '<span class="new-val">' . number_format($total, 2) . '</span>'
                        . '</td>';
                } else {
                    echo '<td>' . number_format($total, 2) . '</td>';
                }

                echo '<td>' . htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';

                // Status icon column
                $icons = [
                    'row-imported'    => '<i class="fa-solid fa-circle-check"    style="color:#155724;"></i>',
                    'row-changed'     => '<i class="fa-solid fa-triangle-exclamation" style="color:#664d03;"></i>',
                    'row-overwritten' => '<i class="fa-solid fa-rotate"          style="color:#5a3e00;"></i>',
                    'row-nochange'    => '<i class="fa-solid fa-minus"           style="color:#6c757d;"></i>',
                ];
                echo '<td>' . ($icons[$cssClass] ?? '') . '</td>';
                echo '</tr>';
            }

            function renderErrorRow($row, $extra = '') {
                echo '<tr class="row-error">';
                echo '<td>' . htmlspecialchars($row['idno'] ?? '',       ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['name'] ?? '',       ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['sheet_name'] ?? '', ENT_QUOTES, 'UTF-8')
                    . ($extra ? ' (' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') . ')' : '') . '</td>';
                echo '<td colspan="7">-</td>';
                echo '<td>' . htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td><i class="fa-solid fa-circle-xmark" style="color:#721c24;"></i></td>';
                echo '</tr>';
            }
            ?>

            <?php foreach (array_merge($importedRows,  $directImported)  as $r) renderStandardRow($r, 'row-imported'); ?>
            <?php foreach (array_merge($overwrittenRows,$directOverwritten) as $r) renderStandardRow($r, 'row-overwritten', true); ?>
            <?php foreach (array_merge($changedRows,   $directChanged)   as $r) renderStandardRow($r, 'row-changed',     true); ?>
            <?php foreach (array_merge($noChangeRows,  $directNoChange)  as $r) renderStandardRow($r, 'row-nochange'); ?>

            <?php foreach ($unknownRegionRows     as $r) renderErrorRow($r, 'unknown region'); ?>
            <?php foreach ($invalidLoanTypeRows   as $r) renderErrorRow($r, $r['loan_type'] ?? ''); ?>
            <?php foreach ($invalidIdRows         as $r) renderErrorRow($r); ?>
            <?php foreach ($invalidAmountRows     as $r) renderErrorRow($r, $r['fund_raw']  ?? ''); ?>
            <?php foreach ($duplicateInFileRows   as $r) renderErrorRow($r, $r['loan_type'] ?? ''); ?>
            <?php foreach ($conflictingRegionRows as $r): ?>
            <tr class="row-error">
                <td><?php echo htmlspecialchars($r['idno'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($r['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($r['first_region'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    / <?php echo htmlspecialchars($r['second_region'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td colspan="7">-</td>
                <td><?php echo htmlspecialchars($r['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                <td><i class="fa-solid fa-circle-xmark" style="color:#721c24;"></i></td>
            </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <!-- action bar and error notice are now rendered ABOVE the table -->

<?php endif; ?>

<script>
    function confirmOverwrite(count) {
        document.getElementById('loading-overlay').style.display = 'block';
        var ok = confirm(
            'Overwrite Confirmation\n\n' +
            count + ' record(s) have changed amounts and will be updated in the database.\n\n' +
            'Records with no change will NOT be touched.\n' +
            'This action cannot be undone. Continue?'
        );
        if (!ok) document.getElementById('loading-overlay').style.display = 'none';
        return ok;
    }

    function exportErrorsToPDF() {
        var rows        = <?php echo json_encode($errorRowsForPdf, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        var payrollDate = <?php echo json_encode($postedDate,       JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        var mainzone    = <?php echo json_encode($postedMainzone,   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        var filename    = <?php echo json_encode($postedFileName,   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../models/generate/pdf/mlfund_new_format_errors_pdf.php';

        [['rows', JSON.stringify(rows)], ['payroll_date', payrollDate],
         ['mainzone', mainzone], ['filename', filename]].forEach(function(pair) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = pair[0]; inp.value = pair[1];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
    }
</script>

<script src="<?php echo $relative_path; ?>assets/js/admin/import-file/script1.js"></script>
</body>
</html>