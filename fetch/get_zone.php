<?php 
include '../config/connection.php'; 

$mainzone = $_POST['mainzone'];
$selected_zone = isset($_POST['selected_zone']) ? $_POST['selected_zone'] : '';

$select_region = "SELECT
    zm.zone_code AS zone
FROM
    " . $database[1] . ".main_zone_masterfile AS mzm
JOIN
    " . $database[1] . ".zone_masterfile AS zm
    ON zm.main_zone_code = mzm.main_zone_code
JOIN
    " . $database[1] . ".region_masterfile AS rm
    ON rm.zone_code = zm.zone_code
WHERE
    mzm.main_zone_code = '$mainzone'
    GROUP BY
    zm.zone_code";
$result = mysqli_query($conn1, $select_region);

$options = '<option value="">Select Zone</option>';

while ($row = mysqli_fetch_assoc($result)) {
    $selected = ($selected_zone === $row['zone']) ? 'selected' : '';
    $options .= '<option value="' . htmlspecialchars($row['zone']) . '" ' . $selected . '>' . htmlspecialchars($row['zone']) . '</option>';
}

// Add Showroom options based on mainzone
if ($mainzone === 'VISMIN') {
    $selected = ($selected_zone === 'VISMIN Showroom') ? 'selected' : '';
    $options .= '<option value="VISMIN Showroom" ' . $selected . '>VISMIN Showroom</option>';
} elseif ($mainzone === 'LNCR') {
    $selected = ($selected_zone === 'LNCR Showroom') ? 'selected' : '';
    $options .= '<option value="LNCR Showroom" ' . $selected . '>LNCR Showroom</option>';
} elseif ($mainzone === 'ALL') {
    $selected = ($selected_zone === 'ALL') ? 'selected' : '';
    $options .= '<option value="ALL" ' . $selected . '>ALL Zone</option>';
}

echo $options;
?>