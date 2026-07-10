<?php
    include '../../../config/connection.php';
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
            switch ($role) {
                case 'SYSTEM':
                    break;
                case 'ML WALLET':
                    break;
                case 'HRMD':
                    break;
                case 'CAD':
                    $hasRequiredRole = true;
                    break;
                case 'ML FUND':
                    break;
                case 'KP DOMESTIC':
                    break;
                case 'FINANCE':
                    break;
                case 'HO RFP':
                    break;
                case 'TELECOMS':
                    break;
                default:
                    break;
            }
        }
        
        if (!$hasRequiredRole) {
            header('location: ' . $auth_url . 'logout.php');
            session_destroy();
            exit();
        }
    }

require '../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Dompdf\Dompdf;
use Dompdf\Options;

function getRegionOptions($conn1, $mainzone) {
    $regions = [];
    if ($mainzone !== '') {
        $sql = "SELECT DISTINCT region FROM branch_profile WHERE region IS NOT NULL AND region != '' AND mainzone = ? ORDER BY region ASC";
        $stmt = mysqli_prepare($conn1, $sql);
        mysqli_stmt_bind_param($stmt, "s", $mainzone);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $sql = "SELECT DISTINCT region FROM branch_profile WHERE region IS NOT NULL AND region != '' ORDER BY region ASC";
        $result = mysqli_query($conn1, $sql);
    }

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $regions[] = $row['region'];
        }
    }
    return $regions;
}

if (isset($_POST['action']) && $_POST['action'] === 'get_regions') {
    $mainzone = isset($_POST['mainzone']) ? trim($_POST['mainzone']) : '';
    $regions = getRegionOptions($conn1, $mainzone);
    foreach ($regions as $r) {
        echo '<option value="' . htmlspecialchars($r) . '">' . htmlspecialchars($r) . '</option>';
    }
    exit();
}

function getInactiveBranchesWithPayroll($conn, $conn1, $payrollDate, $mainzone, $region, $statusFilter = 'all') {
    $sql = "
        SELECT
            p.payroll_date,
            p.branch_name AS payroll_branch_name,
            p.bos_code,
            p.region_code AS payroll_region_code,
            bp.branch_name AS branch_profile_name,
            bp.mainzone,
            bp.zone,
            bp.region,
            bp.region_code,
            bp.ml_matic_status
        FROM edi.payroll p
        JOIN masterdata.branch_profile bp
            ON 1 = 1
        WHERE p.payroll_date = ?
          AND p.description = 'payroll'
          AND p.remarks IS NULL
          AND (
                (
                    p.bos_code IS NOT NULL
                    AND p.bos_code != ''
                    AND bp.code IS NOT NULL
                    AND bp.code != ''
                    AND bp.code REGEXP '^[0-9]+$'
                    AND p.bos_code = CAST(bp.code AS UNSIGNED)
                    AND p.region_code = bp.region_code
                )
                OR
                (
                    COALESCE(p.bos_code, '') = ''
                    AND p.region_code = bp.region_code
                    AND REPLACE(REPLACE(REPLACE(TRIM(LOWER(p.branch_name)), '  ', ' '), '  ', ' '), '  ', ' ')
                        = REPLACE(REPLACE(REPLACE(TRIM(LOWER(bp.branch_name)), '  ', ' '), '  ', ' '), '  ', ' ')
                )
          )
    ";

    $types = 's';
    $params = [$payrollDate];

    if ($mainzone !== '') {
        $sql .= " AND bp.mainzone = ?";
        $types .= 's';
        $params[] = $mainzone;
    }

    if ($region !== '') {
        $sql .= " AND bp.region = ?";
        $types .= 's';
        $params[] = $region;
    }

    if ($statusFilter === 'inactive') {
    $sql .= " AND UPPER(TRIM(COALESCE(bp.ml_matic_status, ''))) = 'INACTIVE'";
    } elseif ($statusFilter === 'tbo') {
        $sql .= " AND UPPER(TRIM(COALESCE(bp.ml_matic_status, ''))) = 'TBO'";
    } else {
        $sql .= " AND UPPER(TRIM(COALESCE(bp.ml_matic_status, ''))) IN ('INACTIVE', 'TBO')";
    }

    $stmt = mysqli_prepare($conn1, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $inactiveWithPayroll = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $inactiveWithPayroll[] = $row;
    }

    mysqli_stmt_close($stmt);

    usort($inactiveWithPayroll, function ($a, $b) {
        return [$a['mainzone'], $a['zone'], $a['payroll_branch_name']]
            <=> [$b['mainzone'], $b['zone'], $b['payroll_branch_name']];
    });

    return $inactiveWithPayroll;
}

$mainzone = isset($_POST['mainzone']) ? trim($_POST['mainzone']) : '';
$region = isset($_POST['region']) ? trim($_POST['region']) : '';
$restrictedDate = isset($_POST['restricted-date']) ? trim($_POST['restricted-date']) : '';
$statusFilter = isset($_POST['status']) ? strtolower(trim($_POST['status'])) : 'all';

$inactiveBranches = [];
$hasSearched = false;

if (isset($_POST['generate'])) {
    $hasSearched = true;
    $inactiveBranches = getInactiveBranchesWithPayroll($conn, $conn1, $restrictedDate, $mainzone, $region, $statusFilter);
}

if (isset($_POST['download'])) {
    $inactiveBranches = getInactiveBranchesWithPayroll($conn, $conn1, $restrictedDate, $mainzone, $region, $statusFilter);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Payroll');

    $sheet->setCellValue('A1', 'Payroll Date:');
    $sheet->setCellValue('B1', $restrictedDate);

    $sheet->setCellValue('A2', 'Mainzone:');
    $sheet->setCellValue('B2', $mainzone !== '' ? $mainzone : 'ALL');

    $sheet->setCellValue('A3', 'Region:');
    $sheet->setCellValue('B3', $region !== '' ? $region : 'ALL');

    $sheet->setCellValue('A4', 'Status:');
    $sheet->setCellValue('B4', strtoupper($statusFilter));

    $sheet->getStyle('A1:A4')->getFont()->setBold(true);

    // leave row 5 blank as a spacer, headers start row 6
    $sheet->fromArray(
        ['Payroll Date', 'Branch Name (Payroll)', 'BOS Code', 'Region Code (Payroll)', 'Branch Name (Branch Profile)', 'Mainzone', 'Zone', 'Region', 'Region Code', 'Status'],
        null,
        'A6'
    );

    $rowIndex = 7;
    foreach ($inactiveBranches as $row) {
        $sheet->fromArray([
            $row['payroll_date'],
            $row['payroll_branch_name'],
            $row['bos_code'],
            $row['payroll_region_code'],
            $row['branch_profile_name'],
            $row['mainzone'],
            $row['zone'],
            $row['region'],
            $row['region_code'],
            $row['ml_matic_status']
        ], null, 'A' . $rowIndex);
        $rowIndex++;
    }

    $sheet->getStyle('A6:J6')->getFont()->setBold(true);

    foreach (range('A', 'J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'Branches_with_transactions_' . str_replace('-', '', $restrictedDate);
    if ($mainzone !== '') {
        $filename .= '_' . preg_replace('/\s+/', '_', $mainzone);
    }
    if ($region !== '') {
        $filename .= '_' . preg_replace('/\s+/', '_', $region);
    }
    if ($statusFilter !== 'all') {
        $filename .= '_' . strtoupper($statusFilter);
    }
    $filename .= '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit();
}

$regionOptions = getRegionOptions($conn1, $mainzone);
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
            height: auto;
            width: auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 20px;
            padding: 20px;
        }
        select {
            width: 200px;
            padding: 10px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
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
        .table-container {
            top: 35px;
            position: relative;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
            margin: 20px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
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

    <center><h2>Inactive / TBO Branches <span style="font-size: 22px; color: red;">[with payroll transactions]</span></h2></center>

    <div class="import-file">
        <form id="downloadForm" action="" method="post">
            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" onchange="updateRegionOptions()">
                    <option value="">ALL Mainzone</option>
                    <option value="VISMIN" <?php echo ($mainzone === 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo ($mainzone === 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
                </select>
                <div class="custom-arrow"></div>
            </div>

            <div class="custom-select-wrapper">
                <label for="region">Region</label>
                <select name="region" id="region" autocomplete="off">
                    <option value="">Select Region</option>
                    <?php foreach ($regionOptions as $optionRegion): ?>
                        <option value="<?php echo htmlspecialchars($optionRegion); ?>" <?php echo ($region === $optionRegion) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($optionRegion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="custom-arrow"></div>
            </div>

            <div class="custom-select-wrapper">
                <label for="status">Status</label>
                <select name="status" id="status" autocomplete="off">
                    <option value="all" <?php echo ($statusFilter === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="inactive" <?php echo ($statusFilter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="tbo" <?php echo ($statusFilter === 'tbo') ? 'selected' : ''; ?>>To Be Open</option>
                </select>
                <div class="custom-arrow"></div>
            </div>

            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo htmlspecialchars($restrictedDate); ?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl1" <?php echo empty($inactiveBranches) ? 'style="display:none"' : ''; ?>>
            <form id="exportForm" action="" method="post">
                <input type="hidden" name="mainzone" value="<?php echo htmlspecialchars($mainzone); ?>">
                <input type="hidden" name="region" value="<?php echo htmlspecialchars($region); ?>">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <input type="hidden" name="restricted-date" value="<?php echo htmlspecialchars($restrictedDate); ?>">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>
    </div>

    <?php if (!empty($inactiveBranches)): ?>
            <div style="margin: 20px; font-size: 12px; color: #333;">
                Total Matching Branches: <span style="color: #db120b;"> <?php echo count($inactiveBranches); ?></span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Payroll Date</th>
                            <th>Branch Name (Payroll)</th>
                            <th>BOS Code</th>
                            <th>Region Code (Payroll)</th>
                            <th>Branch Name (Branch Profile)</th>
                            <th>Mainzone</th>
                            <th>Zone</th>
                            <th>Region</th>
                            <th>Region Code</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactiveBranches as $branch): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($branch['payroll_date']); ?></td>
                                <td><?php echo htmlspecialchars($branch['payroll_branch_name']); ?></td>
                                <td><?php echo htmlspecialchars($branch['bos_code']); ?></td>
                                <td><?php echo htmlspecialchars($branch['payroll_region_code']); ?></td>
                                <td><?php echo htmlspecialchars($branch['branch_profile_name']); ?></td>
                                <td><?php echo htmlspecialchars($branch['mainzone']); ?></td>
                                <td><?php echo htmlspecialchars($branch['zone']); ?></td>
                                <td><?php echo htmlspecialchars($branch['region']); ?></td>
                                <td><?php echo htmlspecialchars($branch['region_code']); ?></td>
                                <td><?php echo htmlspecialchars($branch['ml_matic_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        </div>
    <?php elseif ($hasSearched): ?>
        <div style="margin: 20px; font-size: 14px; color: #db120b; text-align: center;">
            No result found in the filters.
        </div>
    <?php endif; ?>

    <script>
        async function updateRegionOptions() {
            const mainzone = document.getElementById('mainzone').value;
            const form = new FormData();
            form.append('action', 'get_regions');
            form.append('mainzone', mainzone);

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: form });
                const newOptions = await response.text();
                
                // Update the region dropdown with new options
                document.getElementById('region').innerHTML = '<option value="">Select Region</option>' + newOptions;
                // Reset the selected region
                document.getElementById('region').value = '';
            } catch (error) {
                console.error('Error fetching regions:', error);
            }
        }

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
    </script>
</body>
</html>