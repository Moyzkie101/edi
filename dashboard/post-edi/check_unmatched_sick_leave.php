<?php

    include '../../config/connection.php';
    session_start();

    /* ============================================================
       ROLE / SESSION GUARD (same pattern as the other EDI pages)
       ============================================================ */
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
                case 'CAD':
                    $hasRequiredRole = true;
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

    /**
     * Finds Sick-Leave payroll rows for a given mainzone/date-range that
     * fail the branch_profile join used by both post-edi_sick-leave.php
     * and unpost-edi_sick-leave.php. Rows returned here will NEVER show
     * up in either page's preview, no matter what post_edi says.
     * Supports a single date (start === end) or a range, same as the
     * post/unpost pages.
     */
    function fetchUnmatchedSickLeave($conn, $database, string $mainzone, string $restrictedDate, string $endDate): array
    {
        $singleDate = ($restrictedDate === $endDate);

        $sql = "SELECT
                    p.id, p.bos_code, p.branch_name, p.region, p.region_code, p.zone,
                    p.mainzone, p.payroll_date, p.post_edi, p.description, p.remarks,
                    p.uploaded_by, p.uploaded_date
                FROM " . $database[0] . ".payroll p
                LEFT JOIN " . $database[1] . ".branch_profile bp
                    ON (
                        (p.bos_code IS NOT NULL AND p.bos_code = bp.code AND p.region_code = bp.region_code)
                        OR
                        (p.bos_code IS NULL AND p.region_code = bp.region_code AND p.zone = bp.zone
                         AND TRIM(LOWER(p.branch_name)) = TRIM(LOWER(bp.branch_name))
                         AND bp.ml_matic_status = 'TBO')
                    )
                WHERE bp.code IS NULL
                    AND p.mainzone = ?
                    " . ($singleDate ? "AND p.payroll_date = ?" : "AND p.payroll_date BETWEEN ? AND ?") . "
                    AND p.description = 'Sick-Leave'
                ORDER BY p.payroll_date, p.region, p.branch_name";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['error' => $conn->error, 'rows' => []];
        }

        if ($singleDate) {
            $stmt->bind_param('ss', $mainzone, $restrictedDate);
        } else {
            $stmt->bind_param('sss', $mainzone, $restrictedDate, $endDate);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return ['error' => null, 'rows' => $rows];
    }

    /**
     * Given one unmatched Sick-Leave row, works out WHY it doesn't match
     * branch_profile, so the user knows what to fix instead of just
     * seeing "no match".
     */
    function diagnoseUnmatchedRow($conn, $database, array $row): string
    {
        $bosCode    = $row['bos_code'];
        $regionCode = trim((string) $row['region_code']);
        $zone       = trim((string) $row['zone']);
        $branchName = trim((string) $row['branch_name']);

        if ($bosCode !== null && $bosCode !== '') {
            // Branch code path: does the code exist at all?
            $stmt = $conn->prepare(
                "SELECT region_code, zone, ml_matic_status
                 FROM " . $database[1] . ".branch_profile
                 WHERE code = ?"
            );
            $stmt->bind_param('s', $bosCode);
            $stmt->execute();
            $codeMatches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($codeMatches)) {
                return "Branch code '$bosCode' does not exist in branch_profile at all.";
            }

            $sameRegion = array_filter($codeMatches, fn($m) => (string) $m['region_code'] === $regionCode);
            if (empty($sameRegion)) {
                $foundRegions = implode(', ', array_unique(array_column($codeMatches, 'region_code')));
                return "Branch code '$bosCode' exists but only under region_code(s) [$foundRegions], "
                     . "not the imported region_code '$regionCode'.";
            }

            // Code + region matched — should have joined. Flag as unexpected for investigation.
            return "Branch code '$bosCode' and region_code '$regionCode' both exist in branch_profile "
                 . "but still failed to join — check for whitespace/type mismatches in the code column.";
        }

        // Blank bos_code path: must match by zone + region_code + branch_name AND be 'TBO'.
        $stmt = $conn->prepare(
            "SELECT branch_name, ml_matic_status
             FROM " . $database[1] . ".branch_profile
             WHERE zone = ? AND region_code = ?
                 AND TRIM(LOWER(branch_name)) = TRIM(LOWER(?))"
        );
        $stmt->bind_param('sss', $zone, $regionCode, $branchName);
        $stmt->execute();
        $nameMatches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($nameMatches)) {
            return "Branch code is blank and no branch_profile row matches zone '$zone', "
                 . "region_code '$regionCode', branch name '$branchName'. Check for a typo in the branch name.";
        }

        $statuses = implode(', ', array_unique(array_column($nameMatches, 'ml_matic_status')));
        return "Branch name matched by zone/region but its branch_profile status is '$statuses', not 'TBO'. "
             . "Blank branch-code rows only join when the matching branch is marked TBO.";
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
        .import-file { height: 100px; width: auto; display: flex; justify-content: center; align-items: center; }
        select {
            width: 200px; padding: 10px; font-size: 16px; border: 2px solid #ccc; border-radius: 15px;
            background-color: #f9f9f9; -webkit-appearance: none; -moz-appearance: none; appearance: none; color: #F14A51;
        }
        .custom-select-wrapper { position: relative; display: inline-block; margin-left: 20px; color: #F14A51; }
        input[type="date"] {
            width: 200px; padding: 10px; font-size: 14px; border: 2px solid #ccc; border-radius: 15px;
            background-color: #f9f9f9; margin-right: 20px;
        }
        .generate-btn {
            background-color: #db120b; border: none; color: white; padding: 13px 20px; text-align: center;
            text-decoration: none; display: inline-block; font-size: 16px; border-radius: 20px; margin-left: 30px;
        }
        .table-container {
            top: 35px; position: relative; max-width: 100%; overflow-x: auto; overflow-y: auto;
            max-height: calc(100vh - 200px); margin: 20px; border: 1px solid #ccc;
        }
        table { width: 100%; border-collapse: collapse; border: 1px solid #ccc; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; text-align: center; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #e0e0e0; }
        .status-badge {
            display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;
        }
        .status-pending { background-color: #fff3cd; color: #664d03; }
        .status-posted { background-color: #d4edda; color: #155724; }
        .reason-cell { color: #a10000; }
        .banner-warning {
            margin: 20px; padding: 12px 18px; border-radius: 10px; background-color: #fff3cd;
            border: 1px solid #ffe69c; color: #664d03; font-weight: 500; text-align: center;
        }
        .banner-ok {
            margin: 20px; padding: 12px 18px; border-radius: 10px; background-color: #d4edda;
            border: 1px solid #c3e6cb; color: #155724; font-weight: 500; text-align: center;
        }
    </style>
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>SICK LEAVE CONVERSION <span>[UNMATCHED BRANCH CHECK]</span></h2></center>

    <div class="banner-warning" style="margin-left:auto;margin-right:auto;max-width:900px;">
        This lists imported Sick Leave rows that don't match any <code>branch_profile</code> record —
        these rows will never appear in Post EDI or Unpost EDI, regardless of their current status,
        until the underlying branch/region data is corrected.
    </div>

    <div class="import-file">
        <form id="checkForm" action="" method="post">
            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required>
                    <option value="">Select Mainzone</option>
                    <option value="VISMIN" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
                </select>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Start date </label>
                <input type="date" id="restricted-date" name="restricted-date"
                       value="<?php echo isset($_POST['restricted-date']) ? htmlspecialchars($_POST['restricted-date']) : ''; ?>" required>
            </div>
            <div class="custom-select-wrapper">
                <label for="end-date">End date </label>
                <input type="date" id="end-date" name="end-date"
                       value="<?php echo isset($_POST['end-date']) ? htmlspecialchars($_POST['end-date']) : ''; ?>" required>
            </div>
            <input type="submit" class="generate-btn" name="generate" value="Check">
        </form>
    </div>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['generate'])) {

        $mainzone       = trim($_POST['mainzone'] ?? '');
        $restrictedDate = trim($_POST['restricted-date'] ?? '');
        $endDate        = trim($_POST['end-date'] ?? '');

        if ($mainzone === '' || $restrictedDate === '' || $endDate === '') {
            echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>Please select a Mainzone, Start date, and End date.</div>";
        } else {
            $result = fetchUnmatchedSickLeave($conn, $database, $mainzone, $restrictedDate, $endDate);

            if ($result['error'] !== null) {
                echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>Could not run the check. Please try again or contact support.</div>";
            } elseif (empty($result['rows'])) {
                echo "<div class='banner-ok' style='margin-left:auto;margin-right:auto;max-width:900px;'>No unmatched rows found — every Sick Leave row for this mainzone/date range joins cleanly to branch_profile.</div>";
            } else {
                echo "<div style='margin:10px 20px;color:red;font-weight:bold;'>Total Unmatched Rows: " . count($result['rows']) . "</div>";
                echo "<div class='table-container'>";
                echo "<table>";
                echo "<thead><tr>";
                echo "<th>Status</th><th>Payroll Date</th><th>Bos Code</th><th>Branch Name</th><th>Zone</th><th>Region</th><th>Region Code</th>";
                echo "<th>Description</th><th>Remarks</th><th>Uploaded By</th><th>Uploaded Date</th><th>Why it doesn't match</th>";
                echo "</tr></thead><tbody>";

                foreach ($result['rows'] as $row) {
                    $statusClass = $row['post_edi'] === 'posted' ? 'status-posted' : 'status-pending';
                    $reason = diagnoseUnmatchedRow($conn, $database, $row);

                    echo "<tr>";
                    echo "<td style='text-align:center'><span class='status-badge $statusClass'>" . htmlspecialchars(ucfirst((string) $row['post_edi'])) . "</span></td>";
                    echo "<td>" . htmlspecialchars((string) $row['payroll_date']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) ($row['bos_code'] ?? '(blank)')) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['branch_name']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['zone']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['region']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['region_code']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['description']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) ($row['remarks'] ?? '')) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['uploaded_by']) . "</td>";
                    echo "<td>" . htmlspecialchars((string) $row['uploaded_date']) . "</td>";
                    echo "<td class='reason-cell'>" . htmlspecialchars($reason) . "</td>";
                    echo "</tr>";
                }

                echo "</tbody></table></div>";
                
            }
        }
    }
    ?>

</body>
</html>