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
            if ($role === 'ML FUND') {
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

    require '../../../vendor/autoload.php';

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;

    // ─────────────────────────────────────────────────────────────
    // Shared helper: fetch HRMD RFP & EDI totals for a given date
    // ─────────────────────────────────────────────────────────────
    function fetchTotals($conn, $database, $restrictedDate) {
        // Use prepared statements to prevent SQL injection

        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(ml_fund_amount), 0) AS lncr_total
            FROM `" . $database[0] . "`.rfp_mlfund_collection
            WHERE payroll_date = ? AND mainzone = 'LNCR'"
        );
        $stmt->bind_param('s', $restrictedDate);
        $stmt->execute();
        $lncrTotal = $stmt->get_result()->fetch_assoc()['lncr_total'] ?? 0;
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(ml_fund_amount), 0) AS vismin_total
            FROM `" . $database[0] . "`.rfp_mlfund_collection
            WHERE payroll_date = ? AND mainzone = 'VISMIN'"
        );
        $stmt->bind_param('s', $restrictedDate);
        $stmt->execute();
        $visminTotal = $stmt->get_result()->fetch_assoc()['vismin_total'] ?? 0;
        $stmt->close();

        // HRMD RFP total (LNCR + VISMIN combined — keeps your existing logic intact)
        $hrmdRfpTotal = (float)$lncrTotal + (float)$visminTotal;

        $stmt2 = $conn->prepare(
            "SELECT COALESCE(SUM(ml_fund_amount), 0) AS edi_report_total
             FROM (
                 SELECT ml_fund_amount FROM `" . $database[0] . "`.mlfund_payroll       WHERE payroll_date = ?
                 UNION ALL
                 SELECT ml_fund_amount FROM `" . $database[0] . "`.mlfund_payroll_new   WHERE payroll_date = ?
             ) AS combined_mlfund"
        );
        $stmt2->bind_param('ss', $restrictedDate, $restrictedDate);
        $stmt2->execute();
        $ediReportTotal = $stmt2->get_result()->fetch_assoc()['edi_report_total'] ?? 0;
        $stmt2->close();

        return [
            'lncrTotal'      => (float)$lncrTotal,
            'visminTotal'    => (float)$visminTotal,
            'hrmdRfpTotal'   => $hrmdRfpTotal,
            'ediReportTotal' => (float)$ediReportTotal,
            'variance'       => $hrmdRfpTotal - (float)$ediReportTotal,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Excel download
    // ─────────────────────────────────────────────────────────────
    if (isset($_POST['download'])) {
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';
        $payrollDay     = $_SESSION['payroll_day']   ?? '';
        $payrollMonth   = $_SESSION['payroll_month'] ?? '';
        $payrollYear    = $_SESSION['payroll_year']  ?? '';

        if ($restrictedDate) {
            $totals = fetchTotals($conn, $database, $restrictedDate);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'RECONCILIATION & VARIANCE REPORT');
            $sheet->getStyle('A1')->getFont()->setBold(true);

            $sheet->setCellValue('A2', 'ML FUND DEDUCTION REPORT');
            $sheet->getStyle('A2')->getFont()->setBold(true);

            $dateLabel = ($payrollDay === '15')
                ? 'Payroll Date: ' . $payrollMonth . ' 1 - ' . $payrollDay . ', ' . $payrollYear
                : 'Payroll Date: ' . $payrollMonth . ' 16 - ' . $payrollDay . ', ' . $payrollYear;

            $sheet->setCellValue('A4', $dateLabel);
            $sheet->mergeCells('A4:E4');
            $sheet->getStyle('A4:E4')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $blueHeader = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];

            foreach (['A5:C5', 'A6:B6','D5:D6', 'A4:E4', 'C5', 'C6', 'A7', 'B7', 'C7', 'D7', 'E5', 'E6', 'E7'] as $range) {
                $sheet->getStyle($range)->applyFromArray($blueHeader);
            }

            $sheet->setCellValue('A5', 'HRMD RFP');          $sheet->mergeCells('A5:C5');
            $sheet->setCellValue('A6', 'MAINZONE');      $sheet->mergeCells('A6:B6');
            $sheet->setCellValue('D5', 'EDI REPORT (ARIEL)');$sheet->mergeCells('D5:D6');
            $sheet->setCellValue('E5', 'HRMD VARIANCE');
            $sheet->setCellValue('E6', 'HR RFP VS HR EDI');
            $sheet->setCellValue('A7', 'LNCR');
            $sheet->setCellValue('B7', 'VISMIN');
            $sheet->setCellValue('C6', 'Amount Total'); $sheet->mergeCells('C6:C7');
            $sheet->setCellValue('D7', 'Amount Total');
            $sheet->setCellValue('E7', 'Variance');

            $sheet->setCellValue('A8', $totals['lncrTotal']);
            $sheet->setCellValue('B8', $totals['visminTotal']); 
            $sheet->setCellValue('C8', $totals['hrmdRfpTotal']);
            $sheet->setCellValue('D8', $totals['ediReportTotal']);
            $sheet->setCellValue('E8', $totals['variance']);

            $sheet->getStyle('A8:E8')->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A8:E8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A8:E8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
                $sheet->getColumnDimension($col)->setWidth(25);
            }

            $filename = "RECON_&_VARIANCE_MLFund_Report_({$payrollMonth}_{$payrollDay}_{$payrollYear}).xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx')->save('php://output');
            exit();
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Generate / display table
    // ─────────────────────────────────────────────────────────────
    $tableData = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
        $restrictedDate = $_POST['restricted-date'] ?? '';

        if ($restrictedDate) {
            $_SESSION['restrictedDate']  = $restrictedDate;
            $_SESSION['payroll_day']     = date('j', strtotime($restrictedDate));
            $_SESSION['payroll_month']   = date('F', strtotime($restrictedDate));
            $_SESSION['payroll_year']    = date('Y', strtotime($restrictedDate));

            $tableData = fetchTotals($conn, $database, $restrictedDate);
        }

        mysqli_close($conn);
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
        #user:hover {
            background-color: #db120b;
            color: #fff;
            padding: 10px;
        }
        .opt-group {
            display: flex;
            background-color: #f0f0f0;
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
            gap: 10px;
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
            color: #F14A51;
        }
        .generate-btn {
            background-color: #db120b;
            border: none;
            color: white;
            padding: 9px 15px;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
        }
        .download-btn {
            background-color: #28a745;
            border: none;
            color: white;
            padding: 9px 15px;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
        }

        /* Table */
        .table-container {
            width: 100%;
            max-height: 600px;
            overflow-y: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #d5cece;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            color: black;
            text-align: center;
        }
        td.sub-header {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        tr:hover td {
            background-color: #e8e8e8;
        }
    </style>
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php'; ?>
    </div>

    <center><h2>ML Fund Report <span>[RECON & VARIANCE-Format]</span></h2></center>

    <div class="import-file">
        <!-- Generate form -->
        <form method="post" action="">
            <div class="custom-select-wrapper">
                <label for="restricted-date">Date </label>
                <input type="date" id="restricted-date" name="restricted-date"
                       value="<?php echo isset($_POST['restricted-date']) ? htmlspecialchars($_POST['restricted-date']) : ''; ?>"
                       required>
            </div>
            <input type="submit" class="generate-btn" name="generate" value="Proceed">
        </form>

        <?php if ($tableData !== null): ?>
        <!-- Export form — only shown after a successful generate -->
        <form method="post" action="">
            <input type="submit" class="download-btn" name="download" value="Export to Excel">
        </form>
        <?php endif; ?>
    </div>

    <?php if ($tableData !== null):
        $payrollDay   = $_SESSION['payroll_day']   ?? '';
        $payrollMonth = $_SESSION['payroll_month'] ?? '';
        $payrollYear  = $_SESSION['payroll_year']  ?? '';

        $dateLabel = ($payrollDay === '15')
            ? $payrollMonth . ' 1 - ' . $payrollDay . ', ' . $payrollYear
            : $payrollMonth . ' 16 - ' . $payrollDay . ', ' . $payrollYear;

        $fmt = fn($n) => number_format((float)$n, 2);
    ?>
    <!-- Table Display Section -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <td colspan="5" style="text-align:center; font-weight:bold; background:#f2f2f2">
                        Payroll Date: <?php echo htmlspecialchars($dateLabel); ?>
                    </td>
                </tr>
                <tr>
                    <th colspan="3">HRMD RFP</th>
                    <th rowspan="2">EDI REPORT (ARIEL)
                    <th rowspan="2">HRMD VARIANCE<br>HR RFP VS HR EDI
                </tr>
                <tr>
                    <th colspan="2">MAINZONE</th>
                    <th rowspan="2">AMOUNT TOTAL</th>
                </tr>
                <tr>
                    <td class="sub-header">LNCR</td>
                    <td class="sub-header">VISMIN</td>
                    <td class="sub-header">AMOUNT TOTAL</td>
                    <td class="sub-header">VARIANCE</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:right;"><?php echo $fmt($tableData['lncrTotal']); ?></td>
                    <td style="text-align:right;"><?php echo $fmt($tableData['visminTotal']); ?></td>
                    <td style="text-align:right;"><?php echo $fmt($tableData['hrmdRfpTotal']); ?></td>
                    <td style="text-align:right;"><?php echo $fmt($tableData['ediReportTotal']); ?></td>
                    <td style="text-align:right;"><?php echo $fmt($tableData['variance']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</body>
<script src="<?php echo $relative_path; ?>assets/js/admin/mcash-recon/recon-variance-format/mcash-recon-script.js"></script>

</html>