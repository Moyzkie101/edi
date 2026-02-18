<?php

session_start();

if (!isset($_SESSION['admin_name'])) {
  header('location:../login.php');
}

include '../config/connection.php'; 
include '../fetch/fetch-branch-masterfile-data.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <?php include '../templates/header.php' ?>

</head>

<body>
    <?php include '../templates/main-header.php' ?>
    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>
    <tab-container>
        <tab-content>
            <div class="card">
                <div class="card-header">
                    <div class="card-head row">
                        <div class="card-title col fs-3"><i class="fa-solid fa-code-branch"  style="margin: 0px 10px 0px 10px; font-size:16px; font-weight:700; background-color: #fff; padding: 5px 6px; border-radius:50px; color: #29348e; border:4px solid #ccc;"></i>Region Profile
                    </div>
                    <div class="card-head row col-auto">
                        <select class="filter_status btn btn-outline-dark btn-sm">
                            <option disabled selected value="">Zone Category</option>
                            <option value="LZN">LZN</option>
                            <option value="NCR">NCR</option>
                            <option value="VIS">VIS</option>
                            <option value="MIN">MIN</option>
                            <option value="HO">HO</option>
                            <option value="JEW">JEW</option>
                            
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable" class="ui celled table border table-hover border-danger-subtle text-xxl-center">
                            <thead class="fs-4">
                                <tr>
                                    <th>Region Code</th>
                                    <th>Region Name</th>
                                    <th>Zone Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($regionmaster)): ?>
                                    <?php foreach($regionmaster as $row): ?>
                                    <tr>
                                        <td><?= $row['region_code'] ?></td>

                                        <td><?= $row['region_description'] ?></td>

                                        <td><?= $row['zone_code'] ?></td>
                                    </tr>
                                    <?php endforeach ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3">No Data Available</td>
                                        </tr>
                                <?php endif ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </tab-content>
    </tab-container>
    <?php include '../templates/main-footer.php' ?>
    <?php include '../templates/footer.php' ?>
</body>
</html>
  



