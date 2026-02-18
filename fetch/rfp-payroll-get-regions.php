<?php
include '../config/connection.php';

// Get the selected zone and region (if previously selected)
$mainzone = $_POST['mainzone'];
$selected_region = isset($_POST['selected_region']) ? $_POST['selected_region'] : '';

if($mainzone == 'VISMIN'){
    $select_region = "SELECT
        mzm.main_zone_code,
        rm.region_code, 
        rm.region_description, 
        rm.zone_code
        FROM 
            " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN 
            " . $database[1] . ".region_masterfile AS rm
        ON
            (rm.zone_code IN ('VIS', 'MIN', 'VISMIN-MANCOMM', 'VISMIN-SUPPORT') AND mzm.main_zone_code = '$mainzone')
        ORDER BY 
            mzm.main_zone_code, rm.region_description";
}elseif($mainzone == 'LNCR'){
    $select_region = "SELECT
        mzm.main_zone_code,
        rm.region_code, 
        rm.region_description, 
        rm.zone_code
        FROM 
            " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN 
            " . $database[1] . ".region_masterfile AS rm
        ON 
            (rm.zone_code IN ('LZN', 'NCR', 'LNCR-MANCOMM', 'LNCR-SUPPORT') AND mzm.main_zone_code = '$mainzone')
        ORDER BY 
            mzm.main_zone_code, rm.region_description";
}elseif($mainzone == 'ALL'){
    $select_region = "SELECT
        mzm.main_zone_code,
        rm.region_code, 
        rm.region_description, 
        rm.zone_code
        FROM 
            " . $database[1] . ".main_zone_masterfile AS mzm
        JOIN 
            " . $database[1] . ".region_masterfile AS rm
        ON 
            (rm.zone_code IN ('LZN', 'NCR', 'LNCR-MANCOMM', 'LNCR-SUPPORT') AND mzm.main_zone_code = 'LNCR') OR
            (rm.zone_code IN ('VIS', 'MIN', 'VISMIN-MANCOMM', 'VISMIN-SUPPORT') AND mzm.main_zone_code = 'VISMIN')
        ORDER BY 
            mzm.main_zone_code, rm.region_description";
}else{
    echo '<option value="">Select Region</option>';
    exit;
}

// Use $conn1 for masterdata database queries
$result = mysqli_query($conn1, $select_region);

$options = '<option value="">Select Region</option>';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if this region was previously selected
        $selected = ($selected_region === $row['region_code']) ? 'selected' : '';
        $options .= '<option value="' . htmlspecialchars($row['region_code']) . '" ' . $selected . '>' . htmlspecialchars($row['region_description']) . '</option>';
    }
}

echo $options;
?>