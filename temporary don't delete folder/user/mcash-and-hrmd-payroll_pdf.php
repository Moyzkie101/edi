<?php
require '../vendor/autoload.php';
session_start();

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payrollrows = $_SESSION['payrollRows'] ?? []; // Retrieve stored payroll data
    $mainzone = $_SESSION['mainzone'] ?? '';
    $region = $_SESSION['region'] ?? '';
    $date = $_SESSION['restricted-date'] ?? '';
    $payrollDay = $_SESSION['payroll_day'] ?? '';

    $grandTotalMCash = $_SESSION['grandTotalMCash'] ?? 0;
    $grandTotalPayrollIncome = $_SESSION['grandTotalPayrollIncome'] ?? 0;
    $grandTotalPayrollDeductions = $_SESSION['grandTotalPayrollDeductions'] ?? 0;
    $grandTotalPayrollNetPay = $_SESSION['grandTotalPayrollNetPay'] ?? 0;
    $grandTotalVariance = $_SESSION['grandTotalVariance'] ?? 0;

    // Initialize Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    // Define styles
    $html = '
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 12px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .header-zone {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }
        .footer-total {
            font-weight: bold;
            background-color: #ddd;
        }
        .page-break {
            page-break-before: always;
        }
    </style>';

     // Header
     $html .= '<h2 style="text-align:center;">MCash VS HRMD Payroll '.$payrollDay.' Report</h2>';
     $html .= "<div class='header-zone'>(".htmlspecialchars($mainzone).")</div>";
     // Open Table (Header appears only on the first page)
    $html .= '<table>
    <thead>
        <tr>
            <th colspan="3">MCash Data</th>
            <th></th>
            <th colspan="3">HRMD PAYROLL (' . htmlspecialchars($payrollDay) . ') Data</th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>REGION CODE</th>
            <th>REGION NAME</th>
            <th>TOTAL AMOUNT PER REGION</th>
            <th></th>
            <th>TOTAL INCOME</th>
            <th>TOTAL DEDUCTION</th>
            <th>TOTAL NET PAY</th>
            <th></th>
            <th>VARIANCE</th>
        </tr>
    </thead>
    <tbody>';

    // Table Data
    if (!empty($payrollrows)) {
        foreach ($payrollrows as $index => $mcash) {
            $mcashAmount = !empty($mcash['mcash_total_amount']) ? $mcash['mcash_total_amount'] : 0;
            $netPay = !empty($mcash['TOTAL_NET_PAY']) ? $mcash['TOTAL_NET_PAY'] : 0;
            $variance = $mcashAmount - $netPay;

            $html .= "<tr>
                <td>" . htmlspecialchars($mcash['region_code']) . "</td>
                <td>" . htmlspecialchars($mcash['region_name']) . "</td>
                <td align='right'>" . (!empty($mcash['mcash_total_amount']) ? number_format($mcash['mcash_total_amount'], 2) : '-') . "</td>
                <td></td>
                <td align='right'>" . (!empty($mcash['TOTAL_INCOME']) ? number_format($mcash['TOTAL_INCOME'], 2) : '-') . "</td>
                <td align='right'>" . (!empty($mcash['TOTAL_DEDUCTION']) ? number_format($mcash['TOTAL_DEDUCTION'], 2) : '-') . "</td>
                <td align='right'>" . (!empty($mcash['TOTAL_NET_PAY']) ? number_format($mcash['TOTAL_NET_PAY'], 2) : '-') . "</td>
                <td></td>
                <td align='right'>" . number_format($variance, 2) . "</td>
            </tr>";

            // Insert a page break after a certain number of rows (adjust as needed)
            if (($index + 1) % 20 === 0) { // Change 20 to control page breaks
                $html .= '</tbody></table>'; // Close the table
                $html .= '<div class="page-break"></div>'; // Page break
                $html .= '<table><tbody>'; // Reopen table body without headers
            }
        }
    }
    // Close final table body
    $html .= '</tbody>';

    // Footer (Appears at the bottom of the last page)
    $html .= '<tfoot>
        <tr class="footer-total">
            <th colspan="2">GRAND TOTAL</th>
            <th align="right">' . number_format($grandTotalMCash, 2) . '</th>
            <th></th>
            <th align="right">' . number_format($grandTotalPayrollIncome, 2) . '</th>
            <th align="right">' . number_format($grandTotalPayrollDeductions, 2) . '</th>
            <th align="right">' . number_format($grandTotalPayrollNetPay, 2) . '</th>
            <th></th>
            <th align="right">' . number_format($grandTotalVariance, 2) . '</th>
        </tr>
    </tfoot>
    </table>'; // Close final table


    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'portrait'); // Set paper size and orientation

    // Render the HTML as PDF
    $dompdf->render();

    $dompdf->stream('Payroll_Report.pdf', ['Attachment' => 1]); // Forces download
}
?>