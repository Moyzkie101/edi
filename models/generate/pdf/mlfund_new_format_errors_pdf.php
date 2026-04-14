<?php
require '../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request.');
}

$rows = json_decode($_POST['rows'] ?? '[]', true);
$rows = is_array($rows) ? $rows : [];

$payrollDate = $_POST['payroll_date'] ?? '';
$mainzone    = $_POST['mainzone'] ?? '';
$filename    = basename($_POST['filename'] ?? 'mlfund-new-format');

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

$html = '
<style>
    @page { size: A4 portrait; margin: 16px 12px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
    h2 { text-align: center; margin: 0 0 6px; font-size: 11px; }
    .meta { margin-bottom: 8px; line-height: 1.4; font-size: 8px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td {
        border: 1px solid #777;
        padding: 3px 4px;
        vertical-align: top;
        word-wrap: break-word;
    }
    th { background: #f2f2f2; font-size: 8px; }
    .center { text-align: center; }
    .right { text-align: right; }
</style>';

$html .= '<h2>ML FUND NEW FORMAT - ERROR REPORT</h2>';
$html .= '<div class="meta">
    <div><strong>Mainzone:</strong> ' . e($mainzone) . '</div>
    <div><strong>Payroll Date:</strong> ' . e($payrollDate) . '</div>
    <div><strong>Source File:</strong> ' . e($filename) . '</div>
    <div><strong>Total Errors:</strong> ' . count($rows) . '</div>
</div>';

$html .= '<table>
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:14%;">Sheet</th>
            <th style="width:13%;">ID No</th>
            <th style="width:25%;">Name</th>
            <th style="width:18%;">Loan Type</th>
            <th style="width:10%;">Fund</th>
            <th style="width:15%;">Remarks</th>
        </tr>
    </thead>
    <tbody>';

if (empty($rows)) {
    $html .= '<tr><td colspan="7" class="center">No error rows found.</td></tr>';
} else {
    foreach ($rows as $i => $row) {
        $fund = (isset($row['fund']) && $row['fund'] !== '') ? number_format((float)$row['fund'], 2) : '-';

        $html .= '<tr>
            <td class="center">' . ($i + 1) . '</td>
            <td>' . e($row['sheet_name'] ?? '') . '</td>
            <td>' . e($row['idno'] ?? '') . '</td>
            <td>' . e($row['name'] ?? '') . '</td>
            <td>' . e($row['loan_type'] ?? '-') . '</td>
            <td class="right">' . $fund . '</td>
            <td>' . e($row['remarks'] ?? '') . '</td>
        </tr>';
    }
}

$html .= '</tbody></table>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$downloadName = 'mlfund_new_format_error_report_' . date('Ymd_His') . '.pdf';
$dompdf->stream($downloadName, ['Attachment' => 1]);