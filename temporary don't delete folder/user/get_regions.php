<?php 
include '../config/connection.php';

// Get the selected zone and region (if previously selected)
$zone = $_POST['zone'];
$selected_region = isset($_POST['selected_region']) ? $_POST['selected_region'] : '';

$select_region = "SELECT
                        rm.region_description AS region,
                        rm.region_code AS region_code
                    FROM
                        " . $database[1] . ".main_zone_masterfile AS mzm
                    JOIN
                        " . $database[1] . ".zone_masterfile AS zm
                        ON zm.main_zone_code = mzm.main_zone_code
                    JOIN
                        " . $database[1] . ".region_masterfile AS rm
                        ON rm.zone_code = zm.zone_code
                    WHERE
                        zm.zone_code = '$zone'
                    ORDER BY
                        rm.region_description";
$result = mysqli_query($conn1, $select_region);

$options = '<option value="">Select Region</option>';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if this region was previously selected
        $selected = ($selected_region === $row['region_code']) ? 'selected' : '';
        $options .= '<option value="' . htmlspecialchars($row['region_code']) . '" ' . $selected . '>' . htmlspecialchars($row['region']) . '</option>';
    }
} elseif ($zone === 'VISMIN Showroom') {
    $selected = ($selected_region === 'VIS') ? 'selected' : '';
    $options .= '<option value="VIS" ' . $selected . '>Visayas Jewelry</option>';
    
    $selected = ($selected_region === 'MIN') ? 'selected' : '';
    $options .= '<option value="MIN" ' . $selected . '>Mindanao Jewelry</option>';
} elseif ($zone === 'LNCR Showroom') {
    $selected = ($selected_region === 'LZN') ? 'selected' : '';
    $options .= '<option value="LZN" ' . $selected . '>Luzon Jewelry</option>';
    
    $selected = ($selected_region === 'NCR') ? 'selected' : '';
    $options .= '<option value="NCR" ' . $selected . '>NCR Jewelry</option>';
}

echo $options;
?>