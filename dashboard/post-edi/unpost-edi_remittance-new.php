<?php

    include '../../config/connection.php';
    session_start();

    /* ============================================================
       ROLE / SESSION GUARD  (identical pattern to post-edi_remittance-new.php)
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
                    // Only CAD can unpost - same gate as posting.
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

    const REMIT_FORMAT_TYPE = 'NEW';

    /**
     * Builds the shared JOIN + WHERE fragment (as a prepared-statement
     * template) used by both the preview query and the actual unpost.
     *
     * $region === 'ALL' or ''  -> no region restriction (all regions under the zone)
     * otherwise                -> LIKE match on region_code / zone, same as
     *                              the original post-edi_remittance-new.php behaviour.
     *
     * Returns [sql_where_fragment, bind_types, bind_values]
     */
    function buildFilterClause(string $mainzone, string $zone, string $region, string $restrictedDate): array
    {
        $isShowroom = ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom');
        $allRegions = ($region === '' || strtoupper($region) === 'ALL');

        if ($isShowroom) {
            $where = "bp.mainzone = ?
                      AND r.remitance_date = ?
                      AND bp.ml_matic_region = ?
                      AND NOT (bp.code = 18 AND r.zone = 'VIS')
                      AND r.remitance_format_type = ?";
            $types  = 'ssss';
            $values = [$mainzone, $restrictedDate, $zone, REMIT_FORMAT_TYPE];

            if (!$allRegions) {
                $where   .= " AND r.zone LIKE ?";
                $types   .= 's';
                $values[] = '%' . $region . '%';
            }
        } else {
            $where = "bp.mainzone = ?
                      AND r.zone = ?
                      AND r.zone != 'JVIS'
                      AND r.remitance_date = ?
                      AND bp.ml_matic_region != 'LNCR Showroom'
                      AND bp.ml_matic_region != 'VISMIN Showroom'
                      AND r.remitance_format_type = ?";
            $types  = 'ssss';
            $values = [$mainzone, $zone, $restrictedDate, REMIT_FORMAT_TYPE];

            if (!$allRegions) {
                $where   .= " AND bp.region_code LIKE ?";
                $types   .= 's';
                $values[] = '%' . $region . '%';
            }
        }

        return [$where, $types, $values, $isShowroom];
    }

    const JOIN_CLAUSE = "
        FROM %s.remitance r
        INNER JOIN %s.branch_profile bp
            ON (
                (r.bos_code IS NOT NULL AND r.bos_code = bp.code AND r.region_code = bp.region_code)
                OR
                (r.bos_code IS NULL AND r.region_code = bp.region_code AND r.zone = bp.zone
                 AND TRIM(LOWER(r.branch_name)) = TRIM(LOWER(bp.branch_name))
                 AND bp.ml_matic_status = 'TBO')
            )
    ";

    /**
     * Preview: pulls the currently-posted remittance rows matching the filter
     * so the user can confirm before reverting them.
     */
    function fetchPostedPreview($conn, $database, $mainzone, $zone, $region, $restrictedDate)
    {
        [$where, $types, $values] = buildFilterClause($mainzone, $zone, $region, $restrictedDate);
        $join = sprintf(JOIN_CLAUSE, $database[0], $database[1]);

        $sql = "SELECT
                    bp.code, bp.region, r.zone, r.remitance_date,
                    MAX(bp.cost_center)         AS cost_center1,
                    MAX(r.branch_name)           AS branch_name,
                    MAX(r.ee_dr1)                AS ee_dr1,
                    MAX(r.dr1)                   AS dr1,
                    MAX(r.total_ee_er_dr1)       AS total_ee_er_dr1,
                    MAX(r.ee_dr2)                AS ee_dr2,
                    MAX(r.dr2)                   AS dr2,
                    MAX(r.total_ee_er_dr2)       AS total_ee_er_dr2,
                    MAX(r.ee_dr3)                AS ee_dr3,
                    MAX(r.dr3)                   AS dr3,
                    MAX(r.total_ee_er_dr3)       AS total_ee_er_dr3,
                    r.bos_code, r.region
                $join
                WHERE $where
                    AND r.post_edi = 'posted'
                GROUP BY bp.code, bp.region, r.zone, r.remitance_date, r.bos_code, r.region
                ORDER BY bp.region";

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

    /**
     * Performs the actual unpost:
     *   1. Deletes the matching rows from remitance_edi_report
     *   2. Reverts remitance.post_edi back to 'pending' for the same filter
     * Wrapped in a transaction so either both succeed or neither does.
     */
    function unpostData($conn, $database, $mainzone, $zone, $region, $restrictedDate): array
    {
        [$where, $types, $values] = buildFilterClause($mainzone, $zone, $region, $restrictedDate);
        $join = sprintf(JOIN_CLAUSE, $database[0], $database[1]);

        $conn->begin_transaction();

        try {
            // --- 1. Delete the corresponding rows from the EDI report table ---
            // remitance_edi_report doesn't carry bos_code the same way remitance
            // does, so match it via mainzone + zone/region + date + format type,
            // the same grain the post step wrote it at.
            $reportWhere = "mainzone = ? AND remitance_date = ? AND remitance_format_type = ?";
            $reportTypes = 'sss';
            $reportValues = [$mainzone, $restrictedDate, REMIT_FORMAT_TYPE];

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

            $deleteSql = "DELETE FROM " . $database[0] . ".remitance_edi_report WHERE $reportWhere";
            $stmt = $conn->prepare($deleteSql);
            if (!$stmt) {
                throw new RuntimeException('Prepare failed (report delete): ' . $conn->error);
            }
            $stmt->bind_param($reportTypes, ...$reportValues);
            $stmt->execute();
            $deletedReportRows = $stmt->affected_rows;
            $stmt->close();

            // --- 2. Revert post_edi on the source remitance rows ---
            $updateSql = "UPDATE " . $database[0] . ".remitance r
                          INNER JOIN " . $database[1] . ".branch_profile bp
                              ON (
                                  (r.bos_code IS NOT NULL AND r.bos_code = bp.code AND r.region_code = bp.region_code)
                                  OR
                                  (r.bos_code IS NULL AND r.region_code = bp.region_code AND r.zone = bp.zone
                                   AND TRIM(LOWER(r.branch_name)) = TRIM(LOWER(bp.branch_name))
                                   AND bp.ml_matic_status = 'TBO')
                              )
                          SET r.post_edi = 'pending'
                          WHERE $where
                              AND r.post_edi = 'posted'";

            $stmt = $conn->prepare($updateSql);
            if (!$stmt) {
                throw new RuntimeException('Prepare failed (remitance update): ' . $conn->error);
            }
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $updatedRemitanceRows = $stmt->affected_rows;
            $stmt->close();

            $conn->commit();

            return [
                'success' => true,
                'deleted_report_rows' => $deletedReportRows,
                'reverted_remitance_rows' => $updatedRemitanceRows,
            ];
        } catch (Throwable $e) {
            $conn->rollback();
            error_log('Unpost EDI (remittance) failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /* ============================================================
       HANDLE THE CONFIRMED UNPOST  (mirrors ?proceed=true pattern)
       ============================================================ */
    if (isset($_GET['proceed']) && $_GET['proceed'] === 'true') {
        $mainzone       = $_SESSION['unpost_r_mainzone'] ?? '';
        $zone           = $_SESSION['unpost_r_zone'] ?? '';
        $region         = $_SESSION['unpost_r_region'] ?? '';
        $restrictedDate = $_SESSION['unpost_r_restrictedDate'] ?? '';

        if ($mainzone === '' || $zone === '' || $restrictedDate === '') {
            $_SESSION['swal_message'] = [
                'title' => 'Error!',
                'text'  => 'Missing filter selection. Please generate the preview again.',
                'icon'  => 'error',
            ];
        } else {
            $outcome = unpostData($conn, $database, $mainzone, $zone, $region, $restrictedDate);

            if ($outcome['success'] && $outcome['reverted_remitance_rows'] > 0) {
                $_SESSION['swal_message'] = [
                    'title' => 'Success!',
                    'text'  => 'Unposted ' . $outcome['reverted_remitance_rows'] . ' remittance record(s). You can re-import or re-post once corrected.',
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

        header('Location: unpost-edi_remittance-new.php');
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
        td{ text-align: right; }
        .word { text-align: center; font-weight: bold; }
        #user:hover{ background-color: #db120b; color: #fff; padding: 10px; }
        .opt-group { display: flex; background-color: #3262e6; color: white; width: 100%; align-items: center; height: 35px; }
        .import-file { height: 100px; width: auto; display: flex; justify-content: center; align-items: center; }
        select {
            width: 200px; padding: 10px; font-size: 16px; border: 2px solid #ccc; border-radius: 15px;
            background-color: #f9f9f9; -webkit-appearance: none; -moz-appearance: none; appearance: none; color: #F14A51;
        }
        .custom-select-wrapper { position: relative; display: inline-block; margin-left: 20px; color: #F14A51; }
        .custom-arrow {
            position: absolute; top: 50%; right: 10px; width: 0; height: 0; padding: 0; margin-top: -2px;
            border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 5px solid #333; pointer-events: none;
        }
        input[type="date"] {
            width: 200px; padding: 10px; font-size: 14px; border: 2px solid #ccc; border-radius: 15px;
            background-color: #f9f9f9; margin-right: 20px;
        }
        .generate-btn {
            background-color: #db120b; border: none; color: white; padding: 13px 20px; text-align: center;
            text-decoration: none; display: inline-block; font-size: 16px; border-radius: 20px; margin-left: 30px;
        }
        /* Unpost uses the red action color so it's never visually confused with the green "Post" button */
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

    <center><h2>REMITTANCE NEW <span>[UNPOST EDI]</span></h2></center>

    <div class="banner-warning" style="margin-left:auto;margin-right:auto;max-width:900px;">
        Unposting reverts posted remittance records back to <strong>pending</strong> and removes their EDI report
        entries, so you can correct the source Excel import and re-post. This does not delete the original
        remittance rows.
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
                <div class="custom-arrow"></div>
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
                <div class="custom-arrow"></div>
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
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Remittance date </label>
                <input type="date" id="restricted-date" name="restricted-date"
                       value="<?php echo isset($_POST['restricted-date']) ? htmlspecialchars($_POST['restricted-date']) : ''; ?>" required>
            </div>

            <input type="submit" class="generate-btn" name="generate" value="Preview">

        </form>

        <div id="showdl" style="display: none">
            <button class="unpost-btn" onclick="unpostEdi()">Unpost EDI</button>
        </div>
    </div>

    <!-- Reuses the same zone/region dependent-dropdown script as the Post page -->
    <script src="<?php echo $relative_path; ?>assets/js/admin/report-file/script1.js"></script>
</body>
</html>

<script>
    function unpostEdi() {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will revert the selected posted remittance data back to pending and remove it from the EDI report.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#db120b',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, unpost it!',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'unpost-edi_remittance-new.php?proceed=true';
            } else {
                window.location.href = 'unpost-edi_remittance-new.php';
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

    // Stash the filter in session so the confirmed proceed=true step
    // reuses exactly what was previewed - never trust GET params for the
    // destructive step itself.
    $_SESSION['unpost_r_mainzone']       = $mainzone;
    $_SESSION['unpost_r_zone']           = $zone;
    $_SESSION['unpost_r_region']         = $region;
    $_SESSION['unpost_r_restrictedDate'] = $restrictedDate;

    if ($mainzone === '' || $zone === '' || $restrictedDate === '') {
        echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>Please fill in Mainzone, Zone, and Remittance date.</div>";
    } else {
        $preview = fetchPostedPreview($conn, $database, $mainzone, $zone, $region, $restrictedDate);

        if ($preview['error'] !== null) {
            echo "<div class='banner-warning' style='margin-left:auto;margin-right:auto;max-width:900px;'>Could not load preview. Please try again or contact support.</div>";
        } elseif (count($preview['rows']) > 0) {

            $rows = $preview['rows'];
            $totalBranches = count($rows);

            echo "<div style='margin:10px 20px;color:red;font-weight:bold;'>Total Posted Branches Found: $totalBranches</div>";

            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead><tr>";
            echo "<th>BOS Code</th><th>Branch Name</th><th>Region</th><th>Zone</th><th>Cost Center</th>";
            echo "<th>SSS EE</th><th>SSS ER</th><th>SSS Payable</th>";
            echo "<th>PhilHealth EE</th><th>PhilHealth ER</th><th>PhilHealth Payable</th>";
            echo "<th>Pag-IBIG EE</th><th>Pag-IBIG ER</th><th>Pag-IBIG Payable</th>";
            echo "</tr></thead>";
            echo "<tbody>";

            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td class='word'>" . htmlspecialchars((string) $row['bos_code']) . "</td>";
                echo "<td class='word'>" . htmlspecialchars((string) $row['branch_name']) . "</td>";
                echo "<td class='word'>" . htmlspecialchars((string) $row['region']) . "</td>";
                echo "<td class='word'>" . htmlspecialchars((string) $row['zone']) . "</td>";
                echo "<td class='word'>" . htmlspecialchars((string) $row['cost_center1']) . "</td>";
                echo "<td>" . number_format((float) $row['ee_dr1'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['dr1'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['total_ee_er_dr1'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['ee_dr2'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['dr2'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['total_ee_er_dr2'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['ee_dr3'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['dr3'], 2) . "</td>";
                echo "<td>" . number_format((float) $row['total_ee_er_dr3'], 2) . "</td>";
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