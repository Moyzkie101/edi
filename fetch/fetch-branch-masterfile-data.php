<?php
include '../config/connection.php';

$queryBm ="SELECT * FROM " . $database[1] . ".region_masterfile";
$resultBm = $conn1->query($queryBm);

if($resultBm->num_rows> 0){
$optionsBm= mysqli_fetch_all($resultBm, MYSQLI_ASSOC);
}
$queryAm ="SELECT * FROM " . $database[1] . ".area_masterfile";
$resultAm = $conn1->query($queryAm);
if($resultAm->num_rows> 0){
$optionsAm= mysqli_fetch_all($resultAm, MYSQLI_ASSOC);
}

$queryMainZone = "SELECT DISTINCT main_zone_code FROM " . $database[1] . ".main_zone_masterfile";
$resultMainZone = $conn1->query($queryMainZone);
if ($resultMainZone->num_rows > 0) {
    $optionsMainZone = mysqli_fetch_all($resultMainZone, MYSQLI_ASSOC);
}

$queryZone = "SELECT DISTINCT zone_code FROM " . $database[1] . ".zone_masterfile";
$resultZone = $conn1->query($queryZone);
if ($resultZone->num_rows > 0) {
    $optionsZone = mysqli_fetch_all($resultZone, MYSQLI_ASSOC);
}

$queryStatus ="SELECT DISTINCT status FROM branch_masterfile";
$resultStatus = $conn1->query($queryStatus);
if($resultStatus->num_rows> 0){
$optionsStatus= mysqli_fetch_all($resultStatus, MYSQLI_ASSOC);
}
$queryCorporate_name ="SELECT DISTINCT corporate_name FROM branch_masterfile";
$resultCorporate_name = $conn1->query($queryCorporate_name);
if($resultCorporate_name->num_rows> 0){
$optionsCorporate_name= mysqli_fetch_all($resultCorporate_name, MYSQLI_ASSOC);
}

$queryAm_base ="SELECT DISTINCT branch_code FROM branch_masterfile";
$resultAm_base = $conn1->query($queryAm_base);
if($resultAm_base->num_rows> 0){
$optionsAm_base = mysqli_fetch_all($resultAm_base, MYSQLI_ASSOC);
}
$queryRm_base ="SELECT DISTINCT branch_code FROM branch_masterfile";
$resultRm_base = $conn1->query($queryRm_base);
if($resultRm_base->num_rows> 0){
$optionsRm_base = mysqli_fetch_all($resultRm_base, MYSQLI_ASSOC);
}
$queryRegion_name ="SELECT DISTINCT region_name FROM branch_masterfile";
$resultRegion_name = $conn1->query($queryRegion_name);
if($resultRegion_name->num_rows> 0){
$optionsRegion_name= mysqli_fetch_all($resultRegion_name, MYSQLI_ASSOC);
}
$queryRegion_code ="SELECT DISTINCT region_code FROM branch_masterfile";
$resultRegion_code = $conn1->query($queryRegion_code);
if($resultRegion_code->num_rows> 0){
$optionsRegion_code= mysqli_fetch_all($resultRegion_code, MYSQLI_ASSOC);
}
$queryArea_code ="SELECT DISTINCT area_code FROM branch_masterfile";
$resultArea_code = $conn1->query($queryArea_code);
if($resultArea_code->num_rows> 0){
$optionsArea_code= mysqli_fetch_all($resultArea_code, MYSQLI_ASSOC);
}
$queryArea_name ="SELECT DISTINCT area_name FROM branch_masterfile";
$resultArea_name = $conn1->query($queryArea_name);
if($resultArea_name->num_rows> 0){
$optionsArea_name= mysqli_fetch_all($resultArea_name, MYSQLI_ASSOC);
}
$queryBranch_name ="SELECT DISTINCT branch_name FROM branch_masterfile";
$resultBranch_name = $conn1->query($queryBranch_name);
if($resultBranch_name->num_rows> 0){
$optionsBranch_name= mysqli_fetch_all($resultBranch_name, MYSQLI_ASSOC);
}
$queryKp_code ="SELECT DISTINCT kp_code FROM branch_masterfile";
$resultKp_code = $conn1->query($queryKp_code);
if($resultKp_code->num_rows> 0){
$optionsKp_code= mysqli_fetch_all($resultKp_code, MYSQLI_ASSOC);
}
$queryKp_zone ="SELECT DISTINCT kp_zone FROM branch_masterfile";
$resultKp_zone = $conn1->query($queryKp_zone);
if($resultKp_zone->num_rows> 0){
$optionsKp_zone= mysqli_fetch_all($resultKp_zone, MYSQLI_ASSOC);
}
$queryKp_region ="SELECT DISTINCT kp_region FROM branch_masterfile";
$resultKp_region = $conn1->query($queryKp_region);
if($resultKp_region->num_rows> 0){
$optionsKp_region= mysqli_fetch_all($resultKp_region, MYSQLI_ASSOC);
}
$queryKp_branch ="SELECT DISTINCT kp_branch FROM branch_masterfile";
$resultKp_branch = $conn1->query($queryKp_branch);
if($resultKp_branch->num_rows> 0){
$optionsKp_branch= mysqli_fetch_all($resultKp_branch, MYSQLI_ASSOC);
}

if (isset($_GET['id'])) {
    $query = "SELECT region_code, region_description, zone_code FROM " . $database[1] . ".region_masterfile WHERE zone_code= '".$_GET['id']."'";
}else{
    $query = "SELECT * FROM " . $database[1] . ".region_masterfile";
}

$result = $conn1->query($query);

$regionmaster = array();
	while($row = $result->fetch_assoc()){
		$regionmaster[] = $row; 
	}