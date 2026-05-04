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

    $roles = array_map('trim', explode(',', $_SESSION['user_roles']));
    $hasRequiredRole = false;

    foreach ($roles as $role) {
        if ($role === 'CAD') {
            $hasRequiredRole = true;
            break;
        }
    }

    if (!$hasRequiredRole) {
        header('location: ' . $auth_url . 'logout.php');
        session_destroy();
        exit();
    }
}

require_once '../../vendor/autoload.php';

function mapRegionData($conn1, $database, $regionCode)
{
    $regionCode = trim($regionCode);

    $sql = "SELECT
                rm.zone_code,
                rm.region_code,
                zm.zone_description,
                rm.region_description
            FROM " . $database[1] . ".zone_masterfile AS zm
            JOIN " . $database[1] . ".region_masterfile AS rm
              ON rm.zone_code = zm.zone_code";

    if (
        $regionCode === 'HEADOFFICE1' ||
        $regionCode === 'HEADOFFICE2' ||
        $regionCode === 'VISMIN-MANCOMM' ||
        $regionCode === 'LNCR-MANCOMM'
    ) {
        if ($regionCode === 'HEADOFFICE1' || $regionCode === 'HEADOFFICE2') {
            $sql .= " WHERE rm.region_code = '" . $conn1->real_escape_string($regionCode) . "'
                      AND rm.region_description IN ('HO VISMIN SUPPORT', 'HO LNCR SUPPORT')";
        } else {
            $sql .= " WHERE zm.zone_code = '" . $conn1->real_escape_string($regionCode) . "'";
        }
    } else {
        $sql .= " WHERE rm.region_code = '" . $conn1->real_escape_string($regionCode) . "'
                  AND rm.region_description NOT IN ('VISMIN-SUPPORT', 'LNCR-SUPPORT', 'VISMIN-MANCOMM', 'LNCR-MANCOMM')
                  ORDER BY rm.region_description ASC";
    }

    $result = $conn1->query($sql);
    $rowDb = $result ? $result->fetch_assoc() : null;

    if (!$rowDb) {
        return null;
    }

    if ($regionCode === 'HEADOFFICE1') {
        return [
            'zone' => 'VISMIN-SUPPORT',
            'region' => 'HO VISMIN SUPPORT',
            'region_code' => 'HEADOFFICE1'
        ];
    }

    if ($regionCode === 'HEADOFFICE2') {
        return [
            'zone' => 'LNCR-SUPPORT',
            'region' => 'HO LNCR SUPPORT',
            'region_code' => 'HEADOFFICE2'
        ];
    }

    if ($regionCode === 'VISMIN-MANCOMM' || $regionCode === 'LNCR-MANCOMM') {
        return [
            'zone' => $rowDb['zone_code'],
            'region' => $rowDb['zone_description'],
            'region_code' => $rowDb['zone_code']
        ];
    }

    return [
        'zone' => $rowDb['zone_code'],
        'region' => $rowDb['region_description'],
        'region_code' => $rowDb['region_code']
    ];
}

function normalize_region_key($s)
{
    return strtoupper(trim(preg_replace('/[^A-Z0-9]/i', '', (string)$s)));
}

$mapVISMIN = [
    'BOHOL' => 'R05',
    'BUKIDNON' => 'R30',
    'CARGANRT' => 'R12',
    'CARGASUR' => 'R13',
    'CDOMISOR' => 'R18',
    'CEBCENA' => 'R01',
    'CEBCENB' => 'R27',
    'COTBTMAG' => 'R17',
    'DACODA' => 'R15',
    'DAVAO' => 'R14',
    'LANAO' => 'R19',
    'LEYTEA' => 'R06',
    'LEYTEB' => 'R28',
    'MANCOMM' => 'MANCOMM1',
    'NEGOCCA' => 'R08',
    'NEGOCCB' => 'R29',
    'NEGOR' => 'R04',
    'NORTHA' => 'R02',
    'NORTHB' => 'R26',
    'PALAWAN' => 'R25',
    'PNYCNTRL' => 'R11',
    'PNYNORTH' => 'R10',
    'PNYSOUTH' => 'R09',
    'SAMAR' => 'R07',
    'SARGEN' => 'R16',
    'SOCSK' => 'R24',
    'SOUTH' => 'R03',
    'SUPPORT' => 'HEADOFFICE1',
    'ZAMBAS' => 'R23',
    'ZAMSULTA' => 'R31',
    'ZANORTE' => 'R21',
    'ZASURMIS' => 'R20',
    'ZMSIBUGY' => 'R22'
];

$mapLNCR = [
    'ALMASOR' => 'LNCR05',
    'BAZAM' => 'LNCR06',
    'BULACAN' => 'LNCR07',
    'CAMACAT' => 'LNCR08',
    'ILOCABRA' => 'LNCR19',
    'LAGUNA' => 'LNCR09',
    'NCRBTNS' => 'LNCR01',
    'NCRCEN' => 'LNCR02',
    'NCRIZAL' => 'LNCR04',
    'NCRNRTH' => 'LNCR03',
    'NEL' => 'LNCR10',
    'NOL' => 'LNCR12',
    'NWL' => 'LNCR11',
    'PMPANGA' => 'LNCR13',
    'QUVISGAO' => 'LNCR14',
    'SEL' => 'LNCR15',
    'SOL' => 'LNCR17',
    'SUPPORTL' => 'HEADOFFICE2',
    'SWL' => 'LNCR16',
    'TARPAN' => 'LNCR18'
];

function excelRegionToCode($raw, $mainzone)
{
    global $mapVISMIN, $mapLNCR;
    $key = normalize_region_key($raw);

    if ($mainzone === 'VISMIN' && isset($mapVISMIN[$key])) return $mapVISMIN[$key];
    if ($mainzone === 'LNCR' && isset($mapLNCR[$key])) return $mapLNCR[$key];

    return null;
}

function mapLoanTypeToColumn($rawLoanType)
{
    $key = strtoupper(trim((string)$rawLoanType));
    $key = preg_replace('/[^A-Z0-9]+/', '_', $key);

    $map = [
        'MLFUND_REGULAR' => 'mlregular_amount',
        'MLFUND_COMAKERSHIP' => 'mlcomaker_amount',
        'MLFUND_PCL' => 'mlpcl_amount',
        'MLFUND_JEWELRY' => 'mljewelry_amount',
        'MLFUND_OPI' => 'mlopi_amount',
        'MLFUND_EMERGENCY' => 'mlemergency_amount'
    ];

    return $map[$key] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile'])) {
    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $alreadyExistRows = [];
    $duplicateInFileRows = [];
    $seenDuplicateRows = [];
    $successRows = [];
    $unknownRegionRows = [];
    $invalidLoanTypeRows = [];
    $invalidIdRows = [];
    $dataRows = [];
    $conflictingRegionRows = [];
    $employeeRegionTracker = [];
    $employeesWithRegionConflict = [];

    if ($fileExtension === 'xlsx' || $fileExtension === 'xls') {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);

        foreach ($spreadsheet->getAllSheets() as $worksheet) {
            $sheetName = $worksheet->getTitle();
            $highestRow = $worksheet->getHighestDataRow();

            // New format starts at row 2
            for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
                $idNo = trim((string)$worksheet->getCell('B' . $rowIndex)->getValue());
                $regionCodeInput = trim((string)$worksheet->getCell('A' . $rowIndex)->getValue());
                $lastName = trim((string)$worksheet->getCell('C' . $rowIndex)->getValue());
                $firstName = trim((string)$worksheet->getCell('D' . $rowIndex)->getValue());
                $loanTypeRaw = trim((string)$worksheet->getCell('E' . $rowIndex)->getValue());
                $fundRaw = trim((string)$worksheet->getCell('F' . $rowIndex)->getValue());

                if (
                    $idNo === '' &&
                    $regionCodeInput === '' &&
                    $lastName === '' &&
                    $firstName === '' &&
                    $loanTypeRaw === '' &&
                    $fundRaw === ''
                ) {
                    continue;
                }

                // if (!preg_match('/^\d{8}$/', $idNo)) {
                //     continue;
                // }

                if (!is_numeric(str_replace(',', '', $fundRaw))) {
                    continue;
                }

                $fund = (float)str_replace(',', '', $fundRaw);

                if ($idNo === '' || !preg_match('/^\d{8}$/', $idNo)) {
                    $invalidIdRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'missing or invalid employee id'
                    ];
                    continue;
                }

                if ($loanTypeRaw === '') {
                    $invalidLoanTypeRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'missing loan type'
                    ];
                    continue;
                }

                if ($regionCodeInput === '0' || $regionCodeInput === '') {
                    $unknownRegionRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'unknown region'
                    ];
                    continue;
                }

                $mappedRegionCode = excelRegionToCode($regionCodeInput, $_POST['mainzone'] ?? '');
                if (!$mappedRegionCode) {
                    $unknownRegionRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'unknown region'
                    ];
                    continue;
                }

                $regionData = mapRegionData($conn1, $database, $mappedRegionCode);
                if (!$regionData) {
                    $unknownRegionRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'unknown region'
                    ];
                    continue;
                }

                $employeePayrollKey = $idNo . '|' . ($_POST['restricted-date'] ?? '');

                if (isset($employeeRegionTracker[$employeePayrollKey])) {
                    if ($employeeRegionTracker[$employeePayrollKey]['region_code'] !== $regionData['region_code']) {
                        $employeesWithRegionConflict[$employeePayrollKey] = true;

                        $conflictingRegionRows[] = [
                            'sheet_name' => $sheetName,
                            'idno' => $idNo,
                            'name' => trim($lastName . ', ' . $firstName, ', '),
                            'loan_type' => $loanTypeRaw,
                            'fund' => $fund,
                            'remarks' => 'same employee has different region in one payroll',
                            'first_region' => $employeeRegionTracker[$employeePayrollKey]['region'],
                            'second_region' => $regionData['region']
                        ];

                        continue;
                    }
                } else {
                    $employeeRegionTracker[$employeePayrollKey] = [
                        'region_code' => $regionData['region_code'],
                        'region' => $regionData['region']
                    ];
                }

                if (isset($employeesWithRegionConflict[$employeePayrollKey])) {
                    $conflictingRegionRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'same employee has different region in one payroll',
                        'first_region' => $employeeRegionTracker[$employeePayrollKey]['region'] ?? '',
                        'second_region' => $regionData['region'] ?? ''
                    ];
                    continue;
                }

                $loanColumn = mapLoanTypeToColumn($loanTypeRaw);
                if (!$loanColumn) {
                    $invalidLoanTypeRows[] = [
                        'sheet_name' => $sheetName,
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'loan_type' => $loanTypeRaw,
                        'fund' => $fund,
                        'remarks' => 'invalid loan type'
                    ];
                    continue;
                }

                $dupKey = $idNo . '|' . $loanColumn . '|' . (isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '');
                if (isset($seenDuplicateRows[$dupKey])) {
                $duplicateInFileRows[] = [
                'sheet_name' => $sheetName,
                'idno' => $idNo,
                'name' => trim($lastName . ', ' . $firstName, ', '),
                'loan_type' => $loanTypeRaw,
                'fund' => $fund,
                'remarks' => 'duplicate in file'
                ];
                continue;
                }
                $seenDuplicateRows[$dupKey] = true;

                $key = $idNo . '|' . $regionData['region_code'];

                if (!isset($dataRows[$key])) {
                    $dataRows[$key] = [
                        'zone' => $regionData['zone'],
                        'region_code' => $regionData['region_code'],
                        'region' => $regionData['region'],
                        'idno' => $idNo,
                        'name' => trim($lastName . ', ' . $firstName, ', '),
                        'mlcomaker_amount' => 0,
                        'mljewelry_amount' => 0,
                        'mlopi_amount' => 0,
                        'mlemergency_amount' => 0,
                        'mlpcl_amount' => 0,
                        'mlregular_amount' => 0
                    ];
                }

                $dataRows[$key][$loanColumn] += $fund;
            }
        }

        if (!empty($unknownRegionRows)) {
            echo "<script>alert('Import failed: Unknown/invalid region codes detected. Please fix the file and try again.');</script>";
            $dataRows = [];
        } elseif (!empty($invalidIdRows)) {
            echo "<script>alert('Import failed: Missing or invalid employee IDs detected. Please fix the file and try again.');</script>";
            $dataRows = [];
        } elseif (!empty($invalidLoanTypeRows)) {
            echo "<script>alert('Import failed: Invalid or missing loan type values detected. Please fix the file and try again.');</script>";
            $dataRows = [];
        } elseif (!empty($duplicateInFileRows)) {
            echo "<script>alert('Import failed: Duplicate entries (same ID, loan type, and payroll date) detected in the file. Please fix the file and try again.');</script>";
            $dataRows = [];
        } elseif (!empty($conflictingRegionRows)) {
            echo "<script>alert('Import failed: An employee was found in different regions within one payroll date. Please fix the file and try again.');</script>";
            $dataRows = [];    
        } elseif (empty($dataRows)) {
            echo "<script>alert('No valid payroll data found in Excel file.');</script>";
        }else {
            $uploadedBy = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown user';
            $uploadedDate = date('Y-m-d H:i:s');
            $postEdi = 'pending';

            foreach ($dataRows as $row) {
                $checkStmt = $conn->prepare(
                    "SELECT 1
                     FROM " . $database[0] . ".mlfund_payroll_new
                     WHERE payroll_date = ? AND employee_id_no = ? AND mainzone = ?"
                );
                $checkStmt->bind_param("sss", $_POST['restricted-date'], $row['idno'], $_POST['mainzone']);
                $checkStmt->execute();
                $checkStmt->store_result();

                $totalFund = $row['mlregular_amount']
                    + $row['mlcomaker_amount']
                    + $row['mlpcl_amount']
                    + $row['mljewelry_amount']
                    + $row['mlopi_amount']
                    + $row['mlemergency_amount'];

                if ($checkStmt->num_rows > 0) {
                    $alreadyExistRows[] = [
                        'idno' => $row['idno'],
                        'name' => $row['name'],
                        'region' => $row['region'],
                        'mlregular_amount' => $row['mlregular_amount'],
                        'mlcomaker_amount' => $row['mlcomaker_amount'],
                        'mlpcl_amount' => $row['mlpcl_amount'],
                        'mljewelry_amount' => $row['mljewelry_amount'],
                        'mlopi_amount' => $row['mlopi_amount'],
                        'mlemergency_amount' => $row['mlemergency_amount'],
                        'ml_fund_amount' => $totalFund,
                        'remarks' => 'already exist'
                    ];
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO " . $database[0] . ".mlfund_payroll_new
                        (payroll_date, mainzone, zone, region_code, region, employee_id_no, employee_name,
                         mlcomaker_amount, mljewelry_amount, mlopi_amount, mlpcl_amount, mlemergency_amount, mlregular_amount, ml_fund_amount,
                         uploaded_by, uploaded_date, post_edi)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "sssssssdddddddsss",
                        $_POST['restricted-date'],
                        $_POST['mainzone'],
                        $row['zone'],
                        $row['region_code'],
                        $row['region'],
                        $row['idno'],
                        $row['name'],
                        $row['mlcomaker_amount'],
                        $row['mljewelry_amount'],
                        $row['mlopi_amount'],
                        $row['mlpcl_amount'],
                        $row['mlemergency_amount'],
                        $row['mlregular_amount'],
                        $totalFund,
                        $uploadedBy,
                        $uploadedDate,
                        $postEdi
                    );

                    $stmt->execute();
                    $stmt->close();

                    $successRows[] = [
                        'idno' => $row['idno'],
                        'name' => $row['name'],
                        'region' => $row['region'],
                        'mlregular_amount' => $row['mlregular_amount'],
                        'mlcomaker_amount' => $row['mlcomaker_amount'],
                        'mlpcl_amount' => $row['mlpcl_amount'],
                        'mljewelry_amount' => $row['mljewelry_amount'],
                        'mlopi_amount' => $row['mlopi_amount'],
                        'mlemergency_amount' => $row['mlemergency_amount'],
                        'ml_fund_amount' => $totalFund,
                        'remarks' => 'imported'
                    ];
                }

                $checkStmt->close();
            }

            if (count($successRows) > 0) {
                echo "<script>alert('Payroll data imported successfully! Duplicate entries were skipped.');</script>";
            } else {
                echo "<script>alert('No new payroll data imported. All entries already exist.');</script>";
            }
        }
    } else {
        echo "<script>alert('Please upload an XLSX or XLS file.');</script>";
    }
}

if (!isset($alreadyExistRows)) $alreadyExistRows = [];
if (!isset($successRows)) $successRows = [];
if (!isset($unknownRegionRows)) $unknownRegionRows = [];
if (!isset($invalidLoanTypeRows)) $invalidLoanTypeRows = [];
if (!isset($duplicateInFileRows)) $duplicateInFileRows = [];
if (!isset($invalidIdRows)) $invalidIdRows = [];
if (!isset($conflictingRegionRows)) $conflictingRegionRows = [];

$errorRowsForPdf = array_values(array_merge(
    $unknownRegionRows,
    $invalidLoanTypeRows,
    $invalidIdRows,
    $duplicateInFileRows,
    $conflictingRegionRows
));

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
        .display_data { display:flex; align-items:center; justify-content:center; }
        .card { padding:10px; display:flex; align-items:center; justify-content:center; }
        .form { display:flex; align-items:center; width:100%; height:auto; padding:10px; }
        .cancel_date label { font-size:14px; margin-right:15px; }
        div .cancel_date { margin-right:15px; color:#000; }
        .import-file { display:flex; }
        select, input[type="date"] {
            width:200px; padding:10px; border:2px solid #ccc; border-radius:15px; background:#f9f9f9; color:#F14A51;
        }
        .upload-btn {
            background:#d70c0c; color:#fff; padding:5px 10px; font-size:12px; font-weight:700; border:1px solid #fff;
            border-top-right-radius:10px; border-bottom-right-radius:10px; width:100px; margin-right:25px; cursor:pointer;
        }
        .choose-file input[type="file"] {
            display:block; padding:5px; border:1px solid #ccc; border-top-left-radius:10px; border-bottom-left-radius:10px; margin-left:25px; background:#fff; color:#F14A51;
        }
        #loading-overlay {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:9999;
        }
        .loading-spinner {
            position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:50px; height:50px; border-radius:50%;
            border:5px solid #f3f3f3; border-top:5px solid #3498db; animation:spin 1s linear infinite;
        }
        @keyframes spin { 0% {transform:translate(-50%, -50%) rotate(0deg);} 100% {transform:translate(-50%, -50%) rotate(360deg);} }

        .table-container { top:35px; position:relative; max-width:100%; overflow:auto; max-height:calc(100vh - 200px); margin:20px; border:1px solid #ccc; }
        table { width:100%; border-collapse:collapse; border:1px solid #ccc; font-size:14px; }
        th, td { border:1px solid #ccc; padding:5px; text-align:center; }
        th { background:#f2f2f2; font-weight:bold; }
        tr:nth-child(even) { background:#f9f9f9; }
        .unknown-region { background:#fff3cd !important; color:#856404; }
        .invalid-loan { background:#f8d7da !important; color:#721c24; }
        button.export-btn {border-radius: 12px;  padding: 8px; background: red; color: white;}
    </style>
</head>
<body>
    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>ML FUND NEW FORMAT<span>[IMPORT]</span></h2></center>

    <div id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="form">
                <div class="cancel_date">
                    <label for="mainzone">Mainzone </label>
                    <select name="mainzone" id="mainzone" required>
                        <option value="">Select Mainzone</option>
                        <option value="VISMIN">VISMIN</option>
                        <option value="LNCR">LNCR</option>
                    </select>
                </div>
                <div class="cancel_date">
                    <label for="restricted-date">Payroll date </label>
                    <input type="date" id="restricted-date" name="restricted-date" required>
                </div>
                <div class="choose-file">
                    <div class="import-file">
                        <input type="file" name="excelFile" accept=".xls,.xlsx" class="form-control" required />
                        <input type="submit" class="upload-btn" name="upload" value="Upload">
                    </div>
                </div>
            </form>
            <div class="display_data">
                <div class="showEP" style="display: none">
                    <button type="submit" class="export-btn" onclick="exportToPDF()">Export to PDF</button>
                </div>
                <div class="showEP" style="display: none">
                    <button type="submit" class="print-btn" onclick="printTable()">
                        <i style="margin-right: 7px;" class="fa-solid fa-print"></i> Print
                    </button>
                </div>
                <div class="showEP" style="display: none">
                    <button type="submit" class="print-btn">
                        <i style="margin-right: 7px;" class="fa-solid fa fa-floppy-disk"></i> Save to Database
                    </button>
                </div>
            </div>
        </div>
    </div>



    <script>
        function exportErrorsToPDF() {
            const rows = <?php echo json_encode($errorRowsForPdf, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const payrollDate = <?php echo json_encode($_POST['restricted-date'] ?? ''); ?>;
            const mainzone = <?php echo json_encode($_POST['mainzone'] ?? ''); ?>;
            const filename = <?php echo json_encode($_FILES['excelFile']['name'] ?? 'mlfund-new-format'); ?>;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../models/generate/pdf/mlfund_new_format_errors_pdf.php';

            function addField(name, value) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            }

            addField('rows', JSON.stringify(rows));
            addField('payroll_date', payrollDate);
            addField('mainzone', mainzone);
            addField('filename', filename);

            document.body.appendChild(form);
            form.submit();
        }
    </script>

    <?php if (!empty($alreadyExistRows) || !empty($successRows) || !empty($unknownRegionRows) || !empty($invalidLoanTypeRows) || !empty($duplicateInFileRows) || !empty($invalidIdRows) || !empty($conflictingRegionRows)){ ?>
    <h3 class="display_data">Import Results</h3>
        <?php if (!empty($errorRowsForPdf)) { ?>
        <div class="display_data" style="margin: 10px 20px 0;">
            <button type="button" class="export-btn" onclick="exportErrorsToPDF()">
                Export to PDF
            </button>
        </div>
        <?php } ?>
    <div class="table-container">
        <table id="printableTable">
            <thead>
                <tr>
                    <th>IDNO</th>
                    <th>Name</th>
                    <th>Region</th>
                    <th>Regular</th>
                    <th>Comaker</th>
                    <th>PCL</th>
                    <th>Jewelry</th>
                    <th>Emergency</th>
                    <th>OPI</th>
                    <th>Total Fund</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alreadyExistRows as $row) { ?>
                <tr>
                    <td><?php echo $row['idno']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['region']; ?></td>
                    <td><?php echo number_format((float)$row['mlregular_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlcomaker_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlpcl_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mljewelry_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlemergency_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlopi_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['ml_fund_amount'], 2); ?></td>
                    <td><?php echo $row['remarks']; ?></td>
                </tr>
                <?php } ?>

                <?php foreach ($successRows as $row) { ?>
                <tr>
                    <td><?php echo $row['idno']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['region']; ?></td>
                    <td><?php echo number_format((float)$row['mlregular_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlcomaker_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlpcl_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mljewelry_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlemergency_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['mlopi_amount'], 2); ?></td>
                    <td><?php echo number_format((float)$row['ml_fund_amount'], 2); ?></td>
                    <td><?php echo $row['remarks']; ?></td>
                </tr>
                <?php } ?>

                <?php foreach ($unknownRegionRows as $row) { ?>
                <tr class="unknown-region">
                    <td><?php echo $row['idno']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['sheet_name']; ?> (unknown)</td>
                    <td colspan="7">-</td>
                    <td><?php echo $row['remarks']; ?></td>
                </tr>
                <?php } ?>

                <?php foreach ($invalidLoanTypeRows as $row) { ?>
                <tr class="invalid-loan">
                    <td><?php echo $row['idno']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['sheet_name']; ?> (<?php echo $row['loan_type']; ?>)</td>
                    <td colspan="7">-</td>
                    <td><?php echo $row['remarks']; ?></td>
                </tr>
                <?php } ?>

                <?php foreach ($invalidIdRows as $row) { ?>
                <tr class="invalid-loan"> 
                    <td><?php echo htmlspecialchars($row['idno'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td> 
                    <td><?php echo htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['sheet_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)</td> 
                    <td colspan="7">-</td> <td><?php echo htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td> 
                </tr> 
                <?php } ?>

                <?php foreach ($duplicateInFileRows as $row) { ?> 
                <tr class="invalid-loan"> 
                    <td><?php echo $row['idno']; ?></td> 
                    <td><?php echo $row['name']; ?></td> 
                    <td><?php echo $row['sheet_name']; ?> (<?php echo $row['loan_type']; ?>)</td> 
                    <td colspan="7">-</td> 
                    <td><?php echo $row['remarks']; ?></td> 
                </tr> 
                <?php } ?>

                <?php foreach ($conflictingRegionRows as $row) { ?>
                <tr class="invalid-loan">
                    <td><?php echo htmlspecialchars($row['idno'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php echo htmlspecialchars($row['first_region'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        / 
                        <?php echo htmlspecialchars($row['second_region'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td colspan="7">-</td>
                    <td><?php echo htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } ?>

    
</body>
</html>