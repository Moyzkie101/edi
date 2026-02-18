<?php

session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
    header('location: ../login.php');
}

include '../config/connection.php'; 
include '../fetch/fetch-branch-masterfile-data.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <?php include '../templates/header.php' ?>
    <link rel="stylesheet" href="../assets/css/admin/branch-master-file/branch-master-file.css?v=<?php echo time(); ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fomantic-ui/2.9.2/semantic.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.semanticui.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    </head>

<body>
    <?php include '../templates/main-header.php' ?>
    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <tab-container data-tab-number="2">
        <tab-content>
            <form action="" method="POST" class="update_form">
                <div class="update-container">
                    <div class="branch_header">
                        <h3><i class="fa-solid fa-code-branch"  style="margin: 0px 10px 0px 10px; font-size:16px; font-weight:700; background-color: #fff; padding: 5px 6px; border-radius:50px; color: #29348e; border:4px solid #ccc;"></i>Branch Master File</h3>
                    </div>
                    <div class="update-select-wrap">
                        <div class="update-select-content">
                            <div class="branch-select-update">
                                <input type="text" id="branch" name="branch" list="branches_options"  autocomplete="off">
                                <datalist id="branches_options">
                                    <?php
                                    foreach ($optionsBranch_name as $branches) {
                                        echo '<option value="' . $branches['branch_name'] . '">';
                                    }
                                    ?>
                                </datalist>
                            </div>
                            <div class="button-select">
                                <button type="submit" name="proceedButton" id="proceedBtn" class="proceedBtn">Proceed <i class="fa-solid fa-circle-arrow-right" style="margin-left:8px; font-size:18px;"></i></button> 
                            </div>
                        </div>
                    </div>
                </div>
            </form>
                    <div class="note" id="note">Note : Double click on a specific row to view more details</div>
                    <?php   

                        if (isset($_POST['proceedButton']) && !empty($_POST['branch'])) {

                    ?>
                <div class="table-transactions-update">
                    <table class="update-table-result table table-hover text-center">
                        <thead class="table-danger">
                            <tr>
                                <th>Branch Code</th>
                                <th>Branch ID</th>
                                <th>Branch Name</th>
                                <th>Main Zone</th>
                                <th style="display:none;">Zone Code</th>
                                <th>Region Name</th>
                                <th>Region Code</th>
                                <th>Area Name</th>
                                <th>Area Code</th>
                                <th>AM Base</th>
                                <th>RM Base</th>
                                <th style="display:none;">KP Code</th>
                                <th style="display:none;">KP Zone</th>
                                <th style="display:none;">KP Region</th>
                                <th style="display:none;">KP Branch</th>
                                <th style="display:none;">Created By</th>
                                <th style="display:none;">System Date</th>
                                <th style="display:none;">Modified By</th>
                                <th style="display:none;">Modified Date</th>
                                <th>Status</th>
                                <th>Corporate Name</th>
                                <th style="display:none;">Globe Internet Accnumber</th>
                                <th style="display:none;">Globe Internet Accnumber2</th>
                                <th style="display:none;">Globe Internet Accnumber3</th>
                                <th style="display:none;">Globe Internet Accnumber4</th>
                                <th style="display:none;">Globe Internet Accnumber5</th>
                                <th style="display:none;">Globe Mobile Accnumber</th>
                                <th style="display:none;">Globe Mobile Accnumber2</th>
                                <th style="display:none;">Globe Mobile Accnumber3</th>
                                <th style="display:none;">Globe Mobile Accnumber4</th>
                                <th style="display:none;">Globe Mobile Accnumber5</th>
                                <th style="display:none;">Smart Accnumber</th>
                                <th style="display:none;">Smart Accnumber2</th>
                                <th style="display:none;">Smart Accnumber3</th>
                                <th style="display:none;">Smart Accnumber4</th>
                                <th style="display:none;">Smart Accnumber5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                include '../config/connection.php'; 

                                $selectedBranchValue = $_POST['branch']; // Assuming the name attribute of the select tag is "branch"
                                // Use the $selectedValue to fetch the transactions from the branch_masterfile table
                                $query = "SELECT * FROM branch_masterfile WHERE branch_name = '$selectedBranchValue' OR branch_name LIKE '%$selectedBranchValue%' ";
                                
                                $result = mysqli_query($conn1, $query);
                                $rowIndex = 0;
                                // Display the transactions
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr class='selectable-row' ondblclick='displayModal($rowIndex)' onclick='highlightRow(this)'>";
                                    echo "<td>" . $row['branch_code'] . "</td>";
                                    echo "<td>" . $row['branch_id'] . "</td>";
                                    echo "<td>" . $row['branch_name'] . "</td>";
                                    echo "<td>" . $row['main_zone'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['zone_code'] . "</td>";
                                    echo "<td>" . $row['region_name'] . "</td>";
                                    echo "<td>" . $row['region_code'] . "</td>";
                                    echo "<td>" . $row['area_name'] . "</td>";
                                    echo "<td>" . $row['area_code'] . "</td>";
                                    echo "<td>" . $row['am_base'] . "</td>";
                                    echo "<td>" . $row['rm_base'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['kp_code'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['kp_zone'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['kp_region'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['kp_branch'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['created_by'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['system_date'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['modified_by'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['modified_date'] . "</td>";
                                    echo "<td style='text-align:center; width: 150px; font-weight:700; padding-left:10px; width:fit-content; color:" . ($row['status'] === 'Active' ? 'green' : 'red') . ";'><i style='font-size:10px; margin-right: 10px;' class='fa-solid fa-circle fa-fade'></i>" . $row['status'] . "</td>";
                                    // echo "<td>" . $row['status'] . "</td>";
                                    echo "<td>" . $row['corporate_name'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['globe_accnumber'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['globe_accnumber2'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['globe_accnumber3'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['globe_accnumber4'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['globe_accnumber5'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['gmobile_accnumber'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['gmobile_accnumber2'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['gmobile_accnumber3'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['gmobile_accnumber4'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['gmobile_accnumber5'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['smart_accnumber'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['smart_accnumber2'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['smart_accnumber3'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['smart_accnumber4'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['smart_accnumber5'] . "</td>";
                                    echo "<td style='display:none;'>" . $row['id'] . "</td>";
                                    echo "</tr>";
                                    $rowIndex++;
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
        </tab-content>
    </tab-container>

<!-- Modal -->
<form action="" method="POST">
    <div class="update-modal" id="myModal">
        <div class="update-modal-dialog">
            <div class="update-modal-content">
                <!-- Modal Header -->
                <div class="update-modal-header">
                    <h4 class="update-modal-title">Branch Details</h4>
                    <button type="button" class="update-close" data-dismiss="update-modal">&times</button>
                </div>
                <!-- Modal body -->
                <div class="update-modal-body">
                    <div class="content-wrap">
                      <!-- first content -->
                      <div class="first-content-wrap">
                            
                            <h3>Branch ID : <span id="branch_id_update" class=""></span></h3>
                            <h3>Branch Code : <span id="branch_code_update"></span> </h3>
                            <h3>Branch Name : <span id="branch_name_update"></span></h3>
                            
                      </div>
                      <!-- second content -->
                      <div class="second-content-wrap text-center fw-normal">
                            <div class="content-item">
                                <h3>Main Zone Code</h3>
                                <p id="main_zone_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Zone Code</h3>
                                <p id="zone_code_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Region Name</h3>
                                <p id="region_name_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Region Code</h3>
                                <p id="region_code_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Rm Base</h3>
                                <p id="rm_base_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Area Code</h3>
                                <p id="area_code_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Area Name</h3>
                                <p id="area_name_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Am Base</h3>
                                <p id="am_base_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Status</h3>
                                <p id="status_update"></p>
                            </div>
                            <div class="content-item">
                                <h3>Corporate Name</h3>
                                <p id="corporate_name_update"></p>
                            </div>
                        </div>
                        <!-- third content -->
                        <div class="third-content-wrap">
                          <div class="content-item">
                              <h3>Globe Internet Account Number 1</h3>
                              <p id="globe_accnumber_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Internet Account Number 2</h3>
                              <p id="globe_accnumber2_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Internet Account Number 3</h3>
                              <p id="globe_accnumber3_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Internet Account Number 4</h3>
                              <p id="globe_accnumber4_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Internet Account Number 5</h3>
                              <p id="globe_accnumber5_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Mobile Account Number 1</h3>
                              <p id="gmobile_accnumber_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Mobile Account Number 2</h3>
                              <p id="gmobile_accnumber2_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Mobile Account Number 3</h3>
                              <p id="gmobile_accnumber3_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Mobile Account Number 4</h3>
                              <p id="gmobile_accnumber4_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Globe Mobile Account Number 5</h3>
                              <p id="gmobile_accnumber5_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Smart Account Number 1</h3>
                              <p id="smart_accnumber_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Smart Account Number 2</h3>
                              <p id="smart_accnumber2_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Smart Account Number 3</h3>
                              <p id="smart_accnumber3_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Smart Account Number 4</h3>
                              <p id="smart_accnumber4_update"></p>
                          </div>
                          <div class="content-item">
                              <h3>Smart Account Number 5</h3>
                              <p id="smart_accnumber5_update"></p>
                          </div>
                          <input type="text" id="selected_id" name="selected_id" value="" style="display:none;">
                        </div>
                        <!-- end of third content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
    <?php include '../templates/main-footer.php' ?>
    <?php include '../templates/footer.php' ?>
</body>
</html>
  