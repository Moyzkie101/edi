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

function getMissingPayrollBranches($conn, $conn1, $payrollDate, $mainzone , $region ) {
    $sql = "
        SELECT
            bp.mainzone,
            bp.zone,
            bp.region,
            bp.region_code,
            bp.code AS bos_code,
            bp.branch_name
        FROM masterdata.branch_profile bp
        LEFT JOIN edi.payroll p
            ON p.bos_code = CAST(bp.code AS UNSIGNED)
            AND p.payroll_date = ?
            AND p.description = 'payroll'
            AND p.remarks IS NULL
        WHERE bp.code IS NOT NULL
        AND bp.code != ''
        AND bp.code REGEXP '^[0-9]+$'
        AND p.bos_code IS NULL
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

    $stmt = mysqli_prepare($conn1, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $missing = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $missing[] = $row;
    }

    mysqli_stmt_close($stmt);

    usort($missing, function ($a, $b) {
        return [$a['mainzone'], $a['zone'], $a['branch_name']]
            <=> [$b['mainzone'], $b['zone'], $b['branch_name']];
    });

    return $missing;
}

$mainzone = isset($_POST['mainzone']) ? trim($_POST['mainzone']) : '';
$region = isset($_POST['region']) ? trim($_POST['region']) : '';
$restrictedDate = isset($_POST['restricted-date']) ? trim($_POST['restricted-date']) : '';

$missingBranches = [];
$hasSearched = false;

if (isset($_POST['generate'])) {
    $hasSearched = true;
    $missingBranches = getMissingPayrollBranches($conn, $conn1, $restrictedDate, $mainzone, $region);
}

if (isset($_POST['download'])) {
    $missingBranches = getMissingPayrollBranches($conn, $conn1, $restrictedDate, $mainzone, $region);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Missing Payroll');

    $sheet->fromArray(['Zone', 'Mainzone', 'Region', 'Region Code', 'BOS Code', 'Branch Name'], null, 'A1');

    $rowIndex = 2;
    foreach ($missingBranches as $row) {
        $sheet->fromArray([
            $row['zone'],
            $row['mainzone'],
            $row['region'],
            $row['region_code'],
            $row['bos_code'],
            $row['branch_name']
        ], null, 'A' . $rowIndex);
        $rowIndex++;
    }

    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $filename = 'missing_payroll_' . str_replace('-', '', $restrictedDate);
    if ($mainzone !== '') {
        $filename .= '_' . preg_replace('/\s+/', '_', $mainzone);
    }
    if ($region !== '') {
        $filename .= '_' . preg_replace('/\s+/', '_', $region);
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

    <center><h2>Missing Branches <span style="font-size: 22px; color: red;">[by payroll]</span></h2></center>

    <div class="import-file">
        <form id="downloadForm" action="" method="post">
            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateRegionOptions()">
                    <option value="">Select Mainzone</option>
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
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo htmlspecialchars($restrictedDate); ?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl1" <?php echo empty($missingBranches) ? 'style="display:none"' : ''; ?>>
            <form id="exportForm" action="" method="post">
                <input type="hidden" name="mainzone" value="<?php echo htmlspecialchars($mainzone); ?>">
                <input type="hidden" name="region" value="<?php echo htmlspecialchars($region); ?>">
                <input type="hidden" name="restricted-date" value="<?php echo htmlspecialchars($restrictedDate); ?>">
                <input type="submit" class="download-btn" name="download" value="Export to Excel">
            </form>
        </div>
    </div>

    <?php if (!empty($missingBranches)): ?>
            <div style="margin: 20px; font-size: 12px; color: #333;">
                Total Missing Branches: <span style="color: #db120b;"> <?php echo count($missingBranches); ?></span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Mainzone</th>
                            <th>Region</th>
                            <th>Region Code</th>
                            <th>BOS Code</th>
                            <th>Branch Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($missingBranches as $branch): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($branch['zone']); ?></td>
                                <td><?php echo htmlspecialchars($branch['mainzone']); ?></td>
                                <td><?php echo htmlspecialchars($branch['region']); ?></td>
                                <td><?php echo htmlspecialchars($branch['region_code']); ?></td>
                                <td><?php echo htmlspecialchars($branch['bos_code']); ?></td>
                                <td><?php echo htmlspecialchars($branch['branch_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
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

        document.getElementById('mainzone').addEventListener('change', updateRegionOptions);
    </script>

    <script src="<?php echo $relative_path; ?>assets/js/admin/mcash-recon/recon-variance-format/mcash-recon-script.js"></script>
</body>
</html>