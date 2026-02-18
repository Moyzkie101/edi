<?php
require '../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messages = json_decode($_POST['messages'], true);
    $remitanceDate = $_POST['remitance_date'];
    $filename = $_POST['filename'];

    // Initialize Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $html = '<style>
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid black; padding: 3px; text-align: left; }
                .success { background-color: #d4edda; }
                .error { background-color: #ffffff; }
             </style>';

    $html .= '<h3>Remitance Date: ' . $remitanceDate . '</h2>';
    $html .= '<h3>Filename: ' . $filename . '</h2>';
    $html .= '<table>';
    $html .= '<thead>
				<tr>
					<th rowspan="2">Status</th>
					<th colspan="3"><center>HRMD DATA</center></th>
					<th colspan="2"><center>SYSTEM BRANCH PROFILE</center></th>
				</tr>
				<tr>
					<th>Sheet Name</th>
					<th>Branch Code</th>
					<th>Branch Name</th>
					<th>Region</th>
					<th>Message</th>
				</tr>
            </thead>';
    $html .= '<tbody>';
    foreach ($messages as $msg) {
        if ($msg['type'] === 'error') {
            $class = $msg['type'] === 'success' ? 'success' : 'error';
            $html .= "<tr class='$class'>
                        <td>" . ucfirst($msg['type']) . "</td>
                        <td>{$msg['sheet']}</td>
                        <td>{$msg['D']}</td>
                        <td>{$msg['E']}</td>
                        <td>{$msg['B']}</td>
                        <td>{$msg['message']}</td>
                      </tr>";
        }
    }
    $html .= '</tbody></table>';

    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'landscape');

    // Render the HTML as PDF
    $dompdf->render();

    $dompdf->stream('error_messages_report_[ ' . $filename . ' ].pdf', array("Attachment" => 1));
}
?>