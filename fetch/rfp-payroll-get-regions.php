<?php
include '../config/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<option value="">Select Region</option>';
    exit;
}

$mainzone = isset($_POST['mainzone']) ? trim($_POST['mainzone']) : '';
$selected_region = isset($_POST['selected_region']) ? trim($_POST['selected_region']) : '';

$allowedMainzones = ['VISMIN', 'LNCR', 'ALL'];
if (!in_array($mainzone, $allowedMainzones, true)) {
    echo '<option value="">Select Region</option>';
    exit;
}

if ($mainzone === 'VISMIN') {
    $select_region = "SELECT
            mzm.main_zone_code,
            rm.region_code,
            rm.region_description,
            rm.zone_code
        FROM " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN " . $database[1] . ".region_masterfile AS rm
            ON (rm.zone_code IN ('VIS', 'MIN', 'VISMIN-MANCOMM', 'VISMIN-SUPPORT') AND mzm.main_zone_code = ?)
        ORDER BY mzm.main_zone_code, rm.region_description";
} elseif ($mainzone === 'LNCR') {
    $select_region = "SELECT
            mzm.main_zone_code,
            rm.region_code,
            rm.region_description,
            rm.zone_code
        FROM " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN " . $database[1] . ".region_masterfile AS rm
            ON (rm.zone_code IN ('LZN', 'NCR', 'LNCR-MANCOMM', 'LNCR-SUPPORT') AND mzm.main_zone_code = ?)
        ORDER BY mzm.main_zone_code, rm.region_description";
} else {
    $select_region = "SELECT
            mzm.main_zone_code,
            rm.region_code,
            rm.region_description,
            rm.zone_code
        FROM " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN " . $database[1] . ".region_masterfile AS rm
            ON (
                (rm.zone_code IN ('LZN', 'NCR', 'LNCR-MANCOMM', 'LNCR-SUPPORT') AND mzm.main_zone_code = 'LNCR')
                OR
                (rm.zone_code IN ('VIS', 'MIN', 'VISMIN-MANCOMM', 'VISMIN-SUPPORT') AND mzm.main_zone_code = 'VISMIN')
            )
        ORDER BY mzm.main_zone_code, rm.region_description";
}

$options = '<option value="">Select Region</option>';

if ($mainzone === 'ALL') {
    $result = mysqli_query($conn1, $select_region);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $selected = ($selected_region === $row['region_code']) ? 'selected' : '';
            $options .= '<option value="' . htmlspecialchars($row['region_code'], ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($row['region_description'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
    }
} else {
    $stmt = $conn1->prepare($select_region);
    if ($stmt) {
        $stmt->bind_param('s', $mainzone);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = mysqli_fetch_assoc($result)) {
                $selected = ($selected_region === $row['region_code']) ? 'selected' : '';
                $options .= '<option value="' . htmlspecialchars($row['region_code'], ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($row['region_description'], ENT_QUOTES, 'UTF-8') . '</option>';
            }
        }
        $stmt->close();
    }
}

echo $options;
?>