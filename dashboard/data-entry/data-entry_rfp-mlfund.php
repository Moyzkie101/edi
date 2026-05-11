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

    $record = null;

    if (isset($_POST['generate'])) {
        if (!isAllowedPayrollDate($date)) {
            echo "<script>alert('Invalid payroll date. Please select only the 15th or the last day of the month.');</script>";
        } else {
            $stmt = $conn->prepare("SELECT id, payroll_date, ml_fund_amount FROM edi.rfp_mlfund_collection WHERE payroll_date = ? LIMIT 1");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            $stmt->close();
        }
    }

    if (isset($_POST['submit'])) {
        $tableData = json_decode($_POST['table_data'], true);
        $payroll_date = $_POST['payroll_date'];
        $user = $_SESSION['username'] ?? 'system';

        $day = date('d', strtotime($payroll_date));
        $lastDay = date('t', strtotime($payroll_date));

        if (!in_array($day, [15, $lastDay])) {
            echo "<script>alert('Invalid payroll date');</script>";
        } elseif (!empty($tableData)) {
            $success = 0;
            $failed = 0;

            foreach ($tableData as $row) {
                $ml_amount = (float) $row['ml_fund_amount'];

                $stmt = $conn->prepare("SELECT id FROM edi.rfp_mlfund_collection WHERE payroll_date = ? LIMIT 1");
                $stmt->bind_param("s", $payroll_date);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($existing = $result->fetch_assoc()) {
                    $update = $conn->prepare("UPDATE edi.rfp_mlfund_collection SET ml_fund_amount = ?, modified_by = ?, modified_date = NOW(), post_edi = 'pending' WHERE id = ?");
                    $update->bind_param("dsi", $ml_amount, $user, $existing['id']);
                    if ($update->execute()) $success++; else $failed++;
                    $update->close();
                } else {
                    $insert = $conn->prepare("INSERT INTO edi.rfp_mlfund_collection (payroll_date, ml_fund_amount, payroll_type, uploaded_by, uploaded_date, post_edi) VALUES (?, ?, 'Data-Entry', ?, NOW(), 'pending')");
                    $insert->bind_param("sds", $payroll_date, $ml_amount, $user);
                    if ($insert->execute()) $success++; else $failed++;
                    $insert->close();
                }
                $stmt->close();
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
            font-size: 15px;
            color: #000000;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #e0e0e0; cursor: pointer; }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 { margin-top: 0; color: #333; }

        .modal-content label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
            color: #555;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .modal-buttons {
            margin-top: 20px;
            text-align: right;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 20px;
            font-size: 15px;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-save {
            background-color: #4fc917;
            color: white;
        }
        .btn-save:hover { background-color: #3da012; }

        .btn-close {
            background-color: #d70c0c;
            color: white;
        }
        .btn-close:hover { background-color: #a00a0a; }
    </style>
</head>

<body>
    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>RFP ML Fund Collection <span>[DATA ENTRY]</span></h2></center>

    <div class="import-file">
        <form id="generateForm" action="" method="post">
            <label for="payroll_date">Payroll date</label>
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

    <div class='table-container'>
        <table id="dataTable">
            <thead>
                <tr>
                    <th>RFP Payroll Date</th>
                    <th>ML Fund Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($_POST['generate']) && isAllowedPayrollDate($date)): ?>
                    <tr class="selectable-row"
                        data-ml-amount="<?php echo $record ? htmlspecialchars($record['ml_fund_amount']) : '0'; ?>"
                        ondblclick="openEditModal(this)">
                        <td><?php echo $displayDate; ?></td>
                        <td style="text-align: right"><?php echo $record ? number_format($record['ml_fund_amount'], 2) : '0.00'; ?></td>
                    </tr>
                <?php elseif (isset($_POST['generate'])): ?>
                    <tr><td colspan="2">Invalid payroll date.</td></tr>
                <?php else: ?>
                    <tr><td colspan="2">Please select Payroll Date to display.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th id="total-amount" style="text-align: right">
                        <?php
                            if (isset($record)) {
                                echo number_format($record['ml_fund_amount'], 2);
                            } else {
                                echo '0.00';
                            }
                        ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-content">
            <h3>Edit ML Fund Amount</h3>
            <label>Payroll Date</label>
            <input type="text" id="modal_payroll_date" readonly>
            <label>ML Fund Amount</label>
            <input type="number" id="ml_fund_amount" step="0.01" min="0">
            <div class="modal-buttons">
                <button class="btn-close" onclick="closeModal()">Close</button>
                <button class="btn-save" onclick="saveModalChanges()">Save</button>
            </div>
        </div>
    </div>

    <script>
        // Store table data changes keyed by payroll_date
        let tableChanges = {};

        function openEditModal(row) {
            const payrollDateInput = document.getElementById("payroll_date").value;

            if (!payrollDateInput) {
                alert("Please select payroll date first.");
                return;
            }

            const mlAmount = parseFloat(row.dataset.mlAmount || 0);

            document.getElementById("modal_payroll_date").value = payrollDateInput;
            document.getElementById("ml_fund_amount").value = mlAmount;

            document.getElementById("editModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("editModal").style.display = "none";
        }

        function saveModalChanges() {
            const payrollDate = document.getElementById("modal_payroll_date").value;
            const mlAmount = parseFloat(document.getElementById("ml_fund_amount").value);

            if (!payrollDate) {
                alert("Payroll date is required.");
                return;
            }

            if (isNaN(mlAmount)) {
                alert("Invalid ML Fund amount.");
                return;
            }

            tableChanges = {};
            tableChanges[payrollDate] = {
                payroll_date: payrollDate,
                ml_fund_amount: mlAmount,
                payroll_type: "regular"
            };

            // Update the table row visually
            const row = document.querySelector(".selectable-row");
            if (row) {
                row.dataset.mlAmount = mlAmount;
                const cells = row.querySelectorAll("td");
                if (cells.length >= 2) {
                    cells[1].textContent = mlAmount.toLocaleString('en-US', { minimumFractionDigits: 2 });
                }
            }

            // Update footer total
            const totalCell = document.getElementById("total-amount");
            if (totalCell) {
                totalCell.textContent = mlAmount.toLocaleString('en-US', { minimumFractionDigits: 2 });
            }

            document.getElementById("editModal").style.display = "none";
        }

        document.getElementById("mainForm").addEventListener("submit", function (e) {
            if (Object.keys(tableChanges).length === 0) {
                alert("No changes detected.");
                e.preventDefault();
                return;
            }

            document.getElementById("table_data").value = JSON.stringify(
                Object.values(tableChanges)
            );

            if (!confirm("Are you sure you want to submit?")) {
                e.preventDefault();
            }
        });

        // Close modal when clicking outside
        window.addEventListener("click", function (e) {
            const modal = document.getElementById("editModal");
            if (e.target === modal) {
                modal.style.display = "none";
            }
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