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

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>';

    const DESCRIPTION_SL = 'Sick-Leave';
    const EXCLUDED_DESCRIPTIONS_SL = ['13thMonth', 'midYearBonus', 'payroll'];

    // Builds the WHERE clause for the payroll table, supporting both a
    // single date and a date range, same as post-edi_sick-leave.php.
    function buildFilterClauseSL(string $mainzone, string $zone, string $region, string $restrictedDate, string $endDate): array
    {
        $isShowroom = ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom');
        $allRegions = ($region === '' || strtoupper($region) === 'ALL');
        $singleDate = ($restrictedDate === $endDate);

        if ($isShowroom) {
            $where = "bp.mainzone = ?
                      AND bp.ml_matic_region = ?
                      AND NOT (bp.code = 18 AND p.zone = 'VIS')
                      AND p.description = ?
                      AND p.remarks IS NULL";
            $types  = 'sss';
            $values = [$mainzone, $zone, DESCRIPTION_SL];

            if ($singleDate) {
                $where .= " AND p.payroll_date = ?";
                $types .= 's';
                $values[] = $restrictedDate;
            } else {
                $where .= " AND p.payroll_date BETWEEN ? AND ?";
                $types .= 'ss';
                $values[] = $restrictedDate;
                $values[] = $endDate;
            }

            if (!$allRegions) {
                $where   .= " AND p.zone LIKE ?";
                $types   .= 's';
                $values[] = '%' . $region . '%';
            }
        } else {
            $where = "bp.mainzone = ?
                      AND p.zone = ?
                      AND p.zone != 'JVIS'
                      AND bp.ml_matic_region != 'LNCR Showroom'
                      AND bp.ml_matic_region != 'VISMIN Showroom'
                      AND p.description = ?
                      AND p.remarks IS NULL";
            $types  = 'sss';
            $values = [$mainzone, $zone, DESCRIPTION_SL];

            if ($singleDate) {
                $where .= " AND p.payroll_date = ?";
                $types .= 's';
                $values[] = $restrictedDate;
            } else {
                $where .= " AND p.payroll_date BETWEEN ? AND ?";
                $types .= 'ss';
                $values[] = $restrictedDate;
                $values[] = $endDate;
            }

            if (!$allRegions) {
                $where   .= " AND bp.region_code = ?";
                $types   .= 's';
                $values[] = $region;
            }
        }

        $placeholders = implode(',', array_fill(0, count(EXCLUDED_DESCRIPTIONS_SL), '?'));
        $where .= " AND p.description NOT IN ($placeholders)";
        foreach (EXCLUDED_DESCRIPTIONS_SL as $d) {
            $types   .= 's';
            $values[] = $d;
        }

        return [$where, $types, $values, $isShowroom];
    }

    const JOIN_CLAUSE_SL = "
        FROM %s.payroll p
        INNER JOIN %s.branch_profile bp
            ON (
                (p.bos_code IS NOT NULL AND p.bos_code = bp.code AND p.region_code = bp.region_code)
                OR
                (p.bos_code IS NULL AND p.region_code = bp.region_code AND p.zone = bp.zone
                 AND TRIM(LOWER(p.branch_name)) = TRIM(LOWER(bp.branch_name))
                 AND bp.ml_matic_status = 'TBO')
            )
    ";

    function fetchPostedPreviewSL($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate)
    {
        [$where, $types, $values] = buildFilterClauseSL($mainzone, $zone, $region, $restrictedDate, $endDate);
        $join = sprintf(JOIN_CLAUSE_SL, $database[0], $database[1]);

        $sql = "SELECT
                    bp.code, p.cost_center, bp.region, p.zone, p.payroll_date,
                    MAX(bp.cost_center)              AS cost_center1,
                    MAX(p.branch_name)                AS branch_name,
                    MAX(p.basic_pay_regular)           AS basic_pay_regular,
                    MAX(p.basic_pay_trainee)           AS basic_pay_trainee,
                    MAX(p.allowances)                  AS allowances,
                    MAX(p.all_other_deductions)        AS all_other_deductions,
                    MAX(p.no_of_employees_allocated)   AS no_of_employees_allocated,
                    p.bos_code, p.region
                $join
                WHERE $where
                    AND p.post_edi = 'posted'
                GROUP BY bp.code, p.cost_center, bp.region, p.zone, p.payroll_date, p.bos_code, p.region
                ORDER BY bp.region, p.payroll_date";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['error' => $conn->error, 'rows' => []];
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return ['error' => null, 'rows' => $rows];
    }

    function unpostDataSL($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate): array
    {
        [$where, $types, $values] = buildFilterClauseSL($mainzone, $zone, $region, $restrictedDate, $endDate);

        $conn->begin_transaction();

        try {
            $singleDate = ($restrictedDate === $endDate);

            $reportWhere = "mainzone = ? AND `description` = ?";
            $reportTypes = 'ss';
            $reportValues = [$mainzone, DESCRIPTION_SL];

            if ($singleDate) {
                $reportWhere .= " AND payroll_date = ?";
                $reportTypes .= 's';
                $reportValues[] = $restrictedDate;
            } else {
                $reportWhere .= " AND payroll_date BETWEEN ? AND ?";
                $reportTypes .= 'ss';
                $reportValues[] = $restrictedDate;
                $reportValues[] = $endDate;
            }

            $isShowroom = ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom');
            if ($isShowroom) {
                $reportWhere .= " AND ml_matic_region = ?";
            } else {
                $reportWhere .= " AND `zone` = ?";
            }
            $reportTypes  .= 's';
            $reportValues[] = $zone;

            if (!(strtoupper($region) === 'ALL' || $region === '')) {
                $reportWhere   .= ($isShowroom ? " AND `zone` LIKE ?" : " AND region_code LIKE ?");
                $reportTypes   .= 's';
                $reportValues[] = '%' . $region . '%';
            }

            $deleteSql = "DELETE FROM " . $database[0] . ".payroll_edi_report WHERE $reportWhere";
            $stmt = $conn->prepare($deleteSql);
            if (!$stmt) {
                throw new RuntimeException('Prepare failed (report delete): ' . $conn->error);
            }
            $stmt->bind_param($reportTypes, ...$reportValues);
            $stmt->execute();
            $deletedReportRows = $stmt->affected_rows;
            $stmt->close();

            $updateSql = "UPDATE " . $database[0] . ".payroll p
                          INNER JOIN " . $database[1] . ".branch_profile bp
                              ON (
                                  (p.bos_code IS NOT NULL AND p.bos_code = bp.code AND p.region_code = bp.region_code)
                                  OR
                                  (p.bos_code IS NULL AND p.region_code = bp.region_code AND p.zone = bp.zone
                                   AND TRIM(LOWER(p.branch_name)) = TRIM(LOWER(bp.branch_name))
                                   AND bp.ml_matic_status = 'TBO')
                              )
                          SET p.post_edi = 'pending'
                          WHERE $where
                              AND p.post_edi = 'posted'";

            $stmt = $conn->prepare($updateSql);
            if (!$stmt) {
                throw new RuntimeException('Prepare failed (payroll update): ' . $conn->error);
            }
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $updatedPayrollRows = $stmt->affected_rows;
            $stmt->close();

            $conn->commit();

            return [
                'success' => true,
                'deleted_report_rows' => $deletedReportRows,
                'reverted_payroll_rows' => $updatedPayrollRows,
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('Unpost EDI (sick leave) failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    if (isset($_GET['proceed']) && $_GET['proceed'] === 'true') {
        $mainzone       = $_SESSION['unpost_sl_mainzone'] ?? '';
        $zone           = $_SESSION['unpost_sl_zone'] ?? '';
        $region         = $_SESSION['unpost_sl_region'] ?? '';
        $restrictedDate = $_SESSION['unpost_sl_restrictedDate'] ?? '';
        $endDate        = $_SESSION['unpost_sl_endDate'] ?? '';

        if ($mainzone === '' || $zone === '' || $restrictedDate === '' || $endDate === '') {
            $_SESSION['swal_message'] = [
                'title' => 'Error!',
                'text'  => 'Missing filter selection. Please generate the preview again.',
                'icon'  => 'error',
            ];
        } else {
            $outcome = unpostDataSL($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate);

            if ($outcome['success'] && $outcome['reverted_payroll_rows'] > 0) {
                $_SESSION['swal_message'] = [
                    'title' => 'Success!',
                    'text'  => 'Unposted ' . $outcome['reverted_payroll_rows'] . ' Sick Leave record(s). You can re-import or re-post once corrected.',
                    'icon'  => 'success',
                ];
            } elseif ($outcome['success']) {
                $_SESSION['swal_message'] = [
                    'title' => 'Warning!',
                    'text'  => 'No posted records matched that filter. Nothing was changed.',
                    'icon'  => 'warning',
                ];
            } else {
                $_SESSION['swal_message'] = [
                    'title' => 'Error!',
                    'text'  => 'Failed to unpost data. No changes were made.',
                    'icon'  => 'error',
                ];
            }
        }

        header('Location: unpost-edi_sick-leave.php');
        exit();
    }

    if (isset($_SESSION['swal_message'])) {
        $swal = $_SESSION['swal_message'];
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: " . json_encode($swal['title']) . ",
                        text: " . json_encode($swal['text']) . ",
                        icon: " . json_encode($swal['icon']) . ",
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        unset($_SESSION['swal_message']);
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <style>
        #user:hover{ background-color: #db120b; color: #fff; padding: 10px; }
        .opt-group { display: flex; background-color: #3262e6; color: white; width: 100%; align-items: center; height: 35px; }
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
        .unpost-btn {
            background-color: #db120b; border: none; color: white; padding: 9px 15px; text-align: center;
            text-decoration: none; display: inline-block; font-size: 16px; border-radius: 20px; margin: 5px;
        }
        .table-container {
            top: 35px; position: relative; max-width: 100%; overflow-x: auto; overflow-y: auto;
            max-height: calc(100vh - 200px); margin: 20px; border: 1px solid #ccc;
        }
        table { width: 100%; border-collapse: collapse; border: 1px solid #ccc; font-size: 12px; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #e0e0e0; }
        .banner-warning {
            margin: 20px; padding: 12px 18px; border-radius: 10px; background-color: #fff3cd;
            border: 1px solid #ffe69c; color: #664d03; font-weight: 500; text-align: center;
        }
    </style>
</head>

<body>

    <div class="top-content">
        <?php include $relative_path . 'templates/sidebar.php' ?>
    </div>

    <center><h2>SICK LEAVE CONVERSION <span>[UNPOST EDI]</span></h2></center>

    <div class="banner-warning" style="margin-left:auto;margin-right:auto;max-width:900px;">
        Unposting reverts posted Sick Leave records back to <strong>pending</strong> and removes their EDI
        report entries, so you can correct the source Excel import and re-post. This does not delete the
        original payroll rows.
    </div>

    <div class="import-file">

        <form id="unpostForm" action="" method="post">

            <div class="custom-select-wrapper">
                <label for="mainzone">Mainzone </label>
                <select name="mainzone" id="mainzone" autocomplete="off" required onchange="updateZone()">
                    <option value="">Select Mainzone</option>
                    <option value="VISMIN" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'VISMIN') ? 'selected' : ''; ?>>VISMIN</option>
                    <option value="LNCR" <?php echo (isset($_POST['mainzone']) && $_POST['mainzone'] == 'LNCR') ? 'selected' : ''; ?>>LNCR</option>
                </select>
            </div>
            <div class="custom-select-wrapper">
                <label for="zone">Zone</label>
                <select name="zone" id="zone" autocomplete="off" required onchange="updateRegions()">
                    <option value="">Select Zone</option>
                    <?php
                        if (isset($_POST['zone'])) {
                            echo '<option value="' . htmlspecialchars($_POST['zone']) . '" selected>' . htmlspecialchars($_POST['zone']) . '</option>';
                        }
                    ?>
                </select>
            </div>
            <div class="custom-select-wrapper">
                <label for="region">Region</label>
                <select name="region" id="region" autocomplete="off">
                    <option value="ALL">All Regions</option>
                    <?php
                        if (isset($_POST['region']) && $_POST['region'] !== 'ALL') {
                            echo '<option value="' . htmlspecialchars($_POST['region']) . '" selected>' . htmlspecialchars($_POST['region']) . '</option>';
                        }
                    ?>
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

            <input type="submit" class="generate-btn" name="generate" value="Preview">

        </form>

        <div id="showdl" style="display: none">
            <button class="unpost-btn" onclick="unpostEdi()">Unpost EDI</button>
        </div>
    </div>

    <script>
        function updateZone() {
            var mainzone = document.getElementById("mainzone").value;
            var selectedZone = document.getElementById("zone").value;
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../../fetch/get_zone.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("zone").innerHTML = xhr.responseText;
                }
            };
            xhr.send("mainzone=" + mainzone + "&selected_zone=" + selectedZone);
        }

        function updateRegions() {
            var zone = document.getElementById("zone").value;
            var selectedRegion = document.getElementById("region").value;
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "../../fetch/get_regions.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("region").innerHTML =
                        '<option value="ALL">All Regions</option>' + xhr.responseText;
                }
            };
            xhr.send("zone=" + zone + "&selected_region=" + selectedRegion);
        }

        document.getElementById("zone").addEventListener('change', updateRegions);

        window.onload = function() {
            var mainzone = document.getElementById("mainzone").value;
            if (mainzone !== "") updateZone();
            var zone = document.getElementById("zone").value;
            if (zone !== "") updateRegions();
        };
    </script>
</body>
</html>

<script>
    function unpostEdi() {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will revert the selected posted Sick Leave data back to pending and remove it from the EDI report.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#db120b',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, unpost it!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'unpost-edi_sick-leave.php?proceed=true';
            } else {
                window.location.href = 'unpost-edi_sick-leave.php';
            }
        });
    }
</script>

<?php

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['generate'])) {

    $mainzone       = trim($_POST['mainzone'] ?? '');
    $zone           = trim($_POST['zone'] ?? '');
    $region         = trim($_POST['region'] ?? 'ALL');
    $restrictedDate = trim($_POST['restricted-date'] ?? '');
    $endDate        = trim($_POST['end-date'] ?? '');

    $_SESSION['unpost_sl_mainzone']       = $mainzone;
    $_SESSION['unpost_sl_zone']           = $zone;
    $_SESSION['unpost_sl_region']         = $region;
    $_SESSION['unpost_sl_restrictedDate'] = $restrictedDate;
    $_SESSION['unpost_sl_endDate']        = $endDate;

    if ($mainzone === '' || $zone === '' || $restrictedDate === '' || $endDate === '') {
        echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>Please fill in Mainzone, Zone, Start date, and End date.</div>";
    } else {
        $preview = fetchPostedPreviewSL($conn, $database, $mainzone, $zone, $region, $restrictedDate, $endDate);

        if ($preview['error'] !== null) {
            echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>Could not load preview. Please try again or contact support.</div>";
        } elseif (count($preview['rows']) > 0) {

            $rows = $preview['rows'];
            $totalBranches = count($rows);

            echo "<div style='margin:10px 20px;color:red;font-weight:bold;'>Total Posted Branches Found: $totalBranches</div>";

            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>Payroll Date</th><th>BOS Code</th><th>Branch Name</th><th>Region</th><th>Zone</th><th>Cost Center</th>";
            echo "<th>Basic Pay Reg.</th><th>Basic Pay Trainee</th><th>Allowances</th><th>Total Deductions</th>";
            echo "<th>No. of Employees</th>";
            echo "</tr></thead>";
            echo "<tbody>";

            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars((string) $row['payroll_date']) . "</td>";
                echo "<td>" . htmlspecialchars((string) $row['bos_code']) . "</td>";
                echo "<td>" . htmlspecialchars((string) $row['branch_name']) . "</td>";
                echo "<td>" . htmlspecialchars((string) $row['region']) . "</td>";
                echo "<td>" . htmlspecialchars((string) $row['zone']) . "</td>";
                echo "<td>" . htmlspecialchars((string) $row['cost_center1']) . "</td>";
                echo "<td style='text-align:right'>" . number_format((float) $row['basic_pay_regular'], 2) . "</td>";
                echo "<td style='text-align:right'>" . number_format((float) $row['basic_pay_trainee'], 2) . "</td>";
                echo "<td style='text-align:right'>" . number_format((float) $row['allowances'], 2) . "</td>";
                echo "<td style='text-align:right'>" . number_format((float) $row['all_other_deductions'], 2) . "</td>";
                echo "<td>" . htmlspecialchars((string) $row['no_of_employees_allocated']) . "</td>";
                echo "</tr>";
            }

            echo "</tbody></table></div>";

            echo "<script>
                var dlbutton = document.getElementById('showdl');
                dlbutton.style.display = 'block';
            </script>";

        } else {
            echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>No posted records found for that filter — nothing to unpost.</div>";
        }
    }
}

?>