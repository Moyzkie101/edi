<?php
include '../config/connection.php';

// Get the selected region and previously selected branch
$region = $_POST['region'];
$selected_branch = isset($_POST['selected_branch']) ? $_POST['selected_branch'] : ''; // Corrected variable name
if($region === 'VIS'){
    $select_branch = "SELECT DISTINCT branch_name, code FROM " . $database[1] . ".branch_profile WHERE zone IN ('$region','JVIS') AND ml_matic_region='VISMIN Showroom'";
}elseif($region === 'MIN'){
    $select_branch = "SELECT DISTINCT branch_name, code FROM " . $database[1] . ".branch_profile WHERE zone='$region' AND ml_matic_region='VISMIN Showroom'";
}elseif($region === 'LZN'){
    $select_branch = "SELECT DISTINCT branch_name, code FROM " . $database[1] . ".branch_profile WHERE zone='$region' AND ml_matic_region='LNCR Showroom'";
}elseif($region === 'NCR'){
    $select_branch = "SELECT DISTINCT branch_name, code FROM " . $database[1] . ".branch_profile WHERE zone='$region' AND ml_matic_region='LNCR Showroom'";
}else{
    $select_branch = "SELECT DISTINCT branch_name, code FROM " . $database[1] . ".branch_profile WHERE region_code = '$region' AND NOT ml_matic_region IN ('VISMIN Showroom', 'LNCR Showroom')";
}
$result = mysqli_query($conn1, $select_branch);

$options = '<option value="">Select Branch</option>';

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Check if this branch was previously selected
        $selected = ($selected_branch === $row['code']) ? 'selected' : ''; // Add this line to check for selected branch
        $options .= '<option value="' . htmlspecialchars($row['code']) . '" ' . $selected . '>' . htmlspecialchars($row['branch_name']) . '</option>';
    }
}

echo $options; 
?>