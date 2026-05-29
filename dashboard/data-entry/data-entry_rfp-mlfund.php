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
            if ($role === 'HO RFP') {
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

    function isAllowedPayrollDate($value) {
        if (empty($value)) return false;
        $ts = strtotime($value);
        if ($ts === false) return false;
        $day = (int) date('j', $ts);
        $lastDay = (int) date('t', $ts);
        return $day === 15 || $day === $lastDay;
    }

    $date = $_POST['payroll_date'] ?? '';
    $displayDate = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');

    // Fixed mainzones — always show both
    $mainzones = ['LNCR', 'VISMIN'];
    $records = [];

    if (isset($_POST['generate'])) {
        if (!isAllowedPayrollDate($date)) {
            echo "<script>alert('Invalid payroll date. Please select only the 15th or the last day of the month.');</script>";
        } else {
            // Fetch existing records for both mainzones in one query
            $stmt = $conn->prepare(
                "SELECT id, mainzone, ml_fund_amount 
                 FROM edi.rfp_mlfund_collection 
                 WHERE payroll_date = ? AND mainzone IN ('LNCR', 'VISMIN')"
            );
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $records[$row['mainzone']] = $row;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['submit'])) {
        $tableData = json_decode($_POST['table_data'], true);
        $payroll_date = $_POST['payroll_date'];
        $currentUserFullName = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Unknown User';

        if (!isAllowedPayrollDate($payroll_date)) {
            echo "<script>alert('Invalid payroll date.');</script>";
        } elseif (!empty($tableData)) {
            $success = 0;
            $failed  = 0;

            foreach ($tableData as $row) {
                $mainzone  = $row['mainzone'];
                $ml_amount = (float) $row['ml_fund_amount'];

                // Check existing record for this payroll_date + mainzone
                $stmt = $conn->prepare(
                    "SELECT id FROM edi.rfp_mlfund_collection 
                     WHERE payroll_date = ? AND mainzone = ? LIMIT 1"
                );
                $stmt->bind_param("ss", $payroll_date, $mainzone);
                $stmt->execute();
                $result = $stmt->get_result();
                $existing = $result->fetch_assoc();
                $stmt->close();

                if ($existing) {
                    $upd = $conn->prepare(
                        "UPDATE edi.rfp_mlfund_collection 
                         SET ml_fund_amount = ?, modified_by = ?, modified_date = NOW(), post_edi = 'pending'
                         WHERE id = ?"
                    );
                    $upd->bind_param("dsi", $ml_amount, $currentUserFullName, $existing['id']);
                    if ($upd->execute()) $success++; else $failed++;
                    $upd->close();
                } else {
                    $ins = $conn->prepare(
                        "INSERT INTO edi.rfp_mlfund_collection 
                         (payroll_date, mainzone, ml_fund_amount, payroll_type, uploaded_by, uploaded_date, post_edi)
                         VALUES (?, ?, ?, 'Data-Entry', ?, NOW(), 'pending')"
                    );
                    $ins->bind_param("ssds", $payroll_date, $mainzone, $ml_amount, $currentUserFullName);
                    if ($ins->execute()) $success++; else $failed++;
                    $ins->close();
                }
            }

            echo "<script>alert('Success: $success | Failed: $failed'); window.location.href = 'data-entry_rfp-mlfund.php';</script>";
        } else {
            echo "<script>alert('No data to submit.');</script>";
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
        @import url(../../assets/css/shortcodes/Modal/modal.css);

        .print-btn {
            background-color: #d70c0c;
            border: none;
            color: white;
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .print-btn:hover { background-color: #423e3d; }

        .import-file {
            height: 100px;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        input[type="date"] {
            width: 200px;
            padding: 10px;
            font-size: 15px;
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
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .generate-btn:hover { background-color: rgb(180, 31, 31); }

        .table-container {
            top: 35px;
            position: relative;
            max-width: 600px;
            overflow-x: auto;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
            margin: 20px auto;
            border: 1px solid #ccc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
            font-size: 15px;
            color: #000000;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            text-align: center;
        }

        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        tr.selectable-row:hover {
            background-color: #fde8e8;
            cursor: pointer;
        }

        /* Row dirty indicator */
        tr.selectable-row.row-changed td {
            background-color: #fff8e1;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            animation: fadeInOverlay 0.2s ease-out forwards;
        }

        .modal-content {
            background-color: #fff;
            margin: 12% auto;
            padding: 28px 30px;
            border-radius: 12px;
            width: 380px;
            max-width: 92%;
            box-shadow: 0 6px 28px rgba(0,0,0,0.25);
            border: 1px solid var(--border-color);
            transform: scale(0.95) translateY(-10px);
            opacity: 0;
            animation: scaleInCard 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes fadeInOverlay {
            from { background-color: rgba(15, 23, 42, 0); backdrop-filter: blur(0px); }
            to { background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); }
        }

        @keyframes scaleInCard {
            from {
                transform: scale(0.95) translateY(-10px);
                opacity: 0;
            }
            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-content h3 {
            margin-top: 0;
            color: #222;
            font-size: 17px;
            border-bottom: 2px solid #f2f2f2;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .modal-info {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 14px;
            color: #555;
        }

        .modal-info span {
            font-weight: bold;
            color: #db120b;
        }

        .modal-content label {
            display: block;
            margin: 12px 0 5px;
            font-weight: 600;
            font-size: 13px;
            color: #444;
        }
        .modal-content input[type="text"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            font-family: var(--font-main);
            background-color: #ffffff;
            
            /* Stronger, highly visible border context */
            border: 2px solid #cbd5e1; 
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            
            /* Smooth transition when the user clicks/taps into the field */
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        /* Clear micro-interaction states when active */
        .modal-content input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(219, 18, 11, 0.15);
        }

        /* Subtle accent focus states for hover as well */
        .modal-content input[type="text"]:hover {
            border-color: #94a3b8;
        }
        .modal-content input[type="text"]:focus:hover {
            border-color: var(--primary);
        }

        .modal-content input[type="number"] {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .modal-content input[type="number"]:focus {
            outline: none;
            border-color: #db120b;
        }

        .modal-buttons {
            margin-top: 22px;
            text-align: right;
        }

        .modal-buttons button {
            padding: 10px 22px;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 10px;
            transition: background-color 0.2s;
        }

        .btn-save { background-color: #4fc917; color: white; }
        .btn-save:hover { background-color: #3da012; }

        .btn-close { background-color: #d70c0c; color: white; }
        .btn-close:hover { background-color: #a00a0a; }

        .hint-text {
            font-size: 12px;
            color: #aaa;
            margin-top: 4px;
        }

        tfoot th {
            background-color: #f2f2f2;
        }

        td.amount-cell {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .dblclick-hint {
            font-size: 12px;
            color: #999;
            text-align: center;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>RFP ML Fund Collection <span>[DATA ENTRY]</span></h2></center>

    <div class="import-file">
        <form id="generateForm" action="" method="post">
            <label for="payroll_date">Payroll Date</label>
            <input type="date" id="payroll_date" name="payroll_date" value="<?php echo $displayDate; ?>" required>
            <input type="submit" class="generate-btn" name="generate" value="Proceed">
        </form>
    </div>

    <div>
        <center>
            <form action="" method="POST" id="mainForm">
                <input type="hidden" name="payroll_date" id="hidden_payroll_date" value="<?php echo $displayDate; ?>">
                <input type="hidden" name="table_data" id="table_data">
                <input type="submit" class="generate-btn" name="submit" value="Submit All Changes">
            </form>
        </center>
    </div>

    <div class="table-container">
        <table id="dataTable">
            <thead>
                <tr>
                    <th colspan="2">Payroll Date: <?php echo $displayDate ?: '—'; ?></th>
                </tr>
                <tr>
                    <th>Mainzone</th>
                    <th>ML Fund Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($_POST['generate']) && isAllowedPayrollDate($date)): ?>
                    <?php
                        $grand_total = 0;
                        foreach ($mainzones as $mz):
                            $rec = $records[$mz] ?? null;
                            $amount = $rec ? (float)$rec['ml_fund_amount'] : 0.00;
                            $rowId  = $rec ? $rec['id'] : 'new_' . $mz;
                            $grand_total += $amount;
                    ?>
                    <tr class="selectable-row"
                        data-id="<?php echo htmlspecialchars($rowId); ?>"
                        data-mainzone="<?php echo htmlspecialchars($mz); ?>"
                        data-ml-amount="<?php echo $amount; ?>"
                        ondblclick="openEditModal(this)">
                        <td><?php echo htmlspecialchars($mz); ?></td>
                        <td class="amount-cell"><?php echo number_format($amount, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php elseif (isset($_POST['generate'])): ?>
                    <tr><td colspan="2">Invalid payroll date.</td></tr>
                <?php else: ?>
                    <tr><td colspan="2">Please select a Payroll Date to display.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="amount-cell" id="grand-total">
                        <?php echo isset($grand_total) ? number_format($grand_total, 2) : '0.00'; ?>
                    </th>
                </tr>
            </tfoot>
        </table>
        <?php if (isset($_POST['generate']) && isAllowedPayrollDate($date)): ?>
            <p class="dblclick-hint"><i class="fa fa-info-circle"></i> Double-click a row to edit the amount.</p>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <h3><i class="fa fa-edit" style="color: var(--primary);"></i> Edit Data Metric</h3>
            <div class="modal-info">
                <div>Payroll Reference: <span id="modal_payroll_date_display"></span></div>
                <div>Target Mainzone: <span id="modal_mainzone_display"></span></div>
            </div>
            <div style="margin-bottom: 12px;">
                <label for="ml_fund_amount" style="font-size: 13px; font-weight:600; margin-bottom:6px; display:block;">ML Fund Amount</label>
                <input type="text" id="ml_fund_amount" inputmode="decimal" autocomplete="off" placeholder="0.00">
                <p class="hint-text">Enter 0 to clear amount. Accepts numeric entries containing up to 2 decimal points max precision.</p>
            </div>
            <div class="modal-buttons">
                <button class="btn btn-close" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveModalChanges()">Apply Changes</button>
            </div>
        </div>
    </div>


    <script>
        let tableChanges = {};
        let activeRow    = null;

        // Matches enforceMoneyInput from data-entry-remittance
        // Strips all non-numeric chars except one dot, limits to 2 decimal places
        function enforceMoneyInput(selector) {
            document.querySelectorAll(selector).forEach(function (input) {
                input.addEventListener("input", function () {
                    this.value = this.value
                        .replace(/[^0-9.]/g, '')
                        .replace(/(\..*)\./g, '$1')
                        .replace(/^(\d+)(\.\d{0,2}).*$/, '$1$2');
                });
            });
        }

        document.addEventListener("DOMContentLoaded", function () {
            enforceMoneyInput("#ml_fund_amount");
        });

        function openEditModal(row) {
            const payrollDate = document.getElementById("payroll_date").value;
            if (!payrollDate) {
                alert("Please select a payroll date first.");
                return;
            }

            activeRow = row;

            const mainzone = row.dataset.mainzone;
            const mlAmount = parseFloat(row.dataset.mlAmount || 0);

            document.getElementById("modal_payroll_date_display").textContent = payrollDate;
            document.getElementById("modal_mainzone_display").textContent     = mainzone;
            document.getElementById("ml_fund_amount").value                   = mlAmount.toFixed(2);

            document.getElementById("editModal").style.display = "block";
            // Auto-focus the amount input
            setTimeout(() => document.getElementById("ml_fund_amount").focus(), 100);
        }

        function closeModal() {
            document.getElementById("editModal").style.display = "none";
            activeRow = null;
        }

        function saveModalChanges() {
            const rawValue = document.getElementById("ml_fund_amount").value.trim();
            const mlAmount = parseFloat(rawValue);

            if (rawValue === '' || isNaN(mlAmount) || mlAmount < 0) {
                alert("Please enter a valid amount (0 or greater).");
                return;
            }

            const payrollDate = document.getElementById("payroll_date").value;
            const mainzone    = activeRow.dataset.mainzone;
            const rowId       = activeRow.dataset.id;

            // Update the in-memory change tracker
            tableChanges[mainzone] = {
                id:             rowId.startsWith('new_') ? null : rowId,
                mainzone:       mainzone,
                ml_fund_amount: mlAmount
            };

            // Update row display
            activeRow.dataset.mlAmount = mlAmount;
            const amountCell = activeRow.querySelector(".amount-cell");
            amountCell.textContent = mlAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Mark row as changed (yellow tint)
            activeRow.classList.add("row-changed");

            // Recompute grand total from all rows
            updateGrandTotal();

            closeModal();
        }

        function updateGrandTotal() {
            let total = 0;
            document.querySelectorAll(".selectable-row").forEach(function (row) {
                total += parseFloat(row.dataset.mlAmount || 0);
            });
            document.getElementById("grand-total").textContent =
                total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Submit handler
        document.getElementById("mainForm").addEventListener("submit", function (e) {
            if (Object.keys(tableChanges).length === 0) {
                alert("No changes detected. Please edit a row before submitting.");
                e.preventDefault();
                return;
            }

            document.getElementById("table_data").value = JSON.stringify(Object.values(tableChanges));

            if (!confirm("Are you sure you want to submit " + Object.keys(tableChanges).length + " record(s)?")) {
                e.preventDefault();
            }
        });

        // Close modal on outside click
        window.addEventListener("click", function (e) {
            if (e.target === document.getElementById("editModal")) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closeModal();
        });
    </script>

    <?php if (isset($_GET['succ']) || isset($_GET['fail'])): ?>
        <script>
            (function(){
                var succ = <?php echo isset($_GET['succ']) ? (int)$_GET['succ'] : 0; ?>;
                var fail = <?php echo isset($_GET['fail']) ? (int)$_GET['fail'] : 0; ?>;
                alert('Success: ' + succ + ' | Failed: ' + fail);
            })();
        </script>
    <?php endif; ?>
</body>
</html>