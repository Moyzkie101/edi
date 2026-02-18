<?php
    session_start();

    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
        header('location: ../login.php');
    }

    include '../config/connection.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {

        $mainzone = $_SESSION['mainzone'] ?? '';
        $zone = $_SESSION['zone'] ?? '';
        $region = $_SESSION['region'] ?? '';
        $restrictedDate = $_SESSION['restrictedDate'] ?? '';

        if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {

            $deleteQuery = "DELETE FROM " . $database[0] . ".payroll_edi_report per
                        WHERE
                            per.mainzone = '$mainzone'
                        AND per.payroll_date = '$restrictedDate'
                        AND per.ml_matic_region = '$zone'
                        AND NOT (per.branch_code = 18 AND per.zone = 'VIS')  -- to exclude duljo branch
                        AND per.zone like '%$region%'
                        AND per.description = 'midYearBonus'";

        }else{

            $deleteQuery = "DELETE FROM " . $database[0] . ".payroll_edi_report per
                        WHERE
                            per.mainzone = '$mainzone'
                        AND per.zone = '$zone'
                        AND per.zone != 'JVIS' -- to exclude sm seaside showroom
                        AND per.region_code LIKE '%$region%'
                        AND per.payroll_date = '$restrictedDate'
                        AND per.ml_matic_region != 'LNCR Showroom'
                        AND per.ml_matic_region != 'VISMIN Showroom'
                        AND per.description = 'midYearBonus'"; 

        }

        if($conn->query($deleteQuery)) {

            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {

                $updateQuery = "UPDATE " . $database[0] . ".mid_year_bonus_payroll p
                            INNER JOIN 
                                " . $database[1] . ".branch_profile bp
                            ON 
                                p.bos_code = bp.code AND p.region_code = bp.region_code
                            SET p.post_edi = 'pending'
                            WHERE
                                bp.mainzone = '$mainzone'
                            AND p.payroll_date = '$restrictedDate'
                            AND bp.ml_matic_region = '$zone'
                            AND NOT (p.bos_code = 18 AND p.zone = 'VIS')  -- to exclude duljo branch
                            AND p.zone LIKE '%$region%'";
    
            }else{
    
                $updateQuery = "UPDATE " . $database[0] . ".mid_year_bonus_payroll p
                            INNER JOIN 
                                " . $database[1] . ".branch_profile bp
                            ON 
                                p.bos_code = bp.code AND p.region_code = bp.region_code
                            SET p.post_edi = 'pending'
                            WHERE
                                bp.mainzone = '$mainzone'
                            AND p.zone = '$zone'
                            AND p.zone != 'JVIS' -- to exclude sm seaside showroom
                            AND p.region_code LIKE '%$region%'
                            AND p.payroll_date = '$restrictedDate'
                            AND bp.ml_matic_region != 'LNCR Showroom'
                            AND bp.ml_matic_region != 'VISMIN Showroom'"; 
            }
            if($conn->query($updateQuery)) {
                echo "<script>alert('Deleted Successfully.');</script>";
            }else{
                echo "Failed to update ";
                //echo $updateQuery;
            }   
        }else{
            echo "Failed to delete : ";
        }
    }
 
?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="../assets/picture/MLW Logo.png" type="image/x-icon"/>
    <link rel="stylesheet" href="../assets/css/admin/report-file/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
</head>

<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>Mid Year Bonus</center>

    <div class="import-file">
        
        <form id="downloadForm" action="" method="post">

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
                    <!-- Zones will be populated dynamically by JavaScript -->
                    <?php
                        // If a zone is selected, display it after the page reloads
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
                    <option value="">Select Region</option>
                    <!-- Regions will be populated dynamically by JavaScript -->
                    <?php
                        // If a region is selected, display it after the page reloads
                        if (isset($_POST['region'])) {
                            echo '<option value="' . htmlspecialchars($_POST['region']) . '" selected>' . htmlspecialchars($_POST['region']) . '</option>';
                        }
                    ?>
                </select>
                <div class="custom-arrow"></div>
            </div>
            <div class="custom-select-wrapper">
                <label for="restricted-date">Payroll date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="generate-btn" name="generate" value="Proceed">

        </form>
        <div id="showdl" style="display: none">
            <form action="" method="post">
            <button type="submit" class="generate-btn" name="delete">
                <i style="margin-right: 10px;" class="fa-solid fa-trash"></i>
                Delete
            </button>
            </form>
        </div>
    </div>

    <script>
        //for fetching zone
        function updateZone() {
            var mainzone = document.getElementById("mainzone").value;
            var selectedZone = document.getElementById("zone").value;
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "get_zone.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("zone").innerHTML = xhr.responseText;
                }
            };
            // Pass the current zone as well to preserve the selection
            xhr.send("mainzone=" + mainzone + "&selected_zone=" + selectedZone);
        }

        // Ensure the zones are updated automatically on page load based on the current mainzone
        window.onload = function() {
            var mainzone = document.getElementById("mainzone").value;
            if (mainzone !== "") {
                updateZone(); // Fetch and set the zones automatically if a mainzone is already selected
            }
        };
        
        // Function to fetch regions based on the selected zone
        function updateRegions() {
            var zone = document.getElementById("zone").value;
            var selectedRegion = document.getElementById("region").value; 

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "get_regions.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById("region").innerHTML = xhr.responseText;
                }
            };
            // Pass the current region as well to preserve the selection
            xhr.send("zone=" + zone + "&selected_region=" + selectedRegion);
        }

        // Ensure the regions are updated automatically when a zone is selected or when the page reloads
        document.getElementById("zone").addEventListener('change', updateRegions);

        window.onload = function() {
            var zone = document.getElementById("zone").value;
            if (zone !== "") {
                updateRegions(); // Fetch and set the regions automatically if a zone is already selected
            }
        };
    </script>
    <script>
        // Attach click event listeners to group buttons
        document.querySelectorAll('.group-btn').forEach(button => {
            button.addEventListener('click', () => {
                const group = button.parentElement;

                // Toggle visibility of this group
                group.classList.toggle('show');

                // Close other groups in the dropdown
                document.querySelectorAll('.dropdown-group').forEach(otherGroup => {
                    if (otherGroup !== group) {
                        otherGroup.classList.remove('show');
                    }
                });
            });
        });

        // Close all groups when clicking outside the dropdown
        document.addEventListener('click', event => {
            if (!event.target.closest('.dropdown-content')) {
                document.querySelectorAll('.dropdown-group').forEach(group => {
                    group.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate'])) {

    $mainzone = $_POST['mainzone'];
    $region = $_POST['region'];
    $zone = $_POST['zone'];
    $restrictedDate = $_POST['restricted-date'];

    $_SESSION['mainzone'] = $mainzone;
    $_SESSION['zone'] = $zone;
    $_SESSION['region'] = $region;
    $_SESSION['restrictedDate'] = $restrictedDate;

    if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {
        $sql = "SELECT * FROM
                    " . $database[0] . ".payroll_edi_report per
                WHERE
                    per.mainzone = '$mainzone'
                    AND per.payroll_date = '$restrictedDate'
                    AND per.ml_matic_region = '$zone'
                    AND per.zone like '%$region%'
                    AND NOT (per.branch_code = 18 AND per.zone = 'VIS')  -- to exclude duljo branch
                    AND per.description = 'midYearBonus'
                ORDER BY 
                    per.region;";
    }else{
                $sql = "SELECT * FROM
                    " . $database[0] . ".payroll_edi_report per
                WHERE
                    per.mainzone = '$mainzone'
                    AND per.zone = '$zone'
                    AND per.zone != 'JVIS' -- to exclude sm seaside showroom
                    AND per.region_code LIKE '%$region%'
                    AND per.payroll_date = '$restrictedDate'
                    AND per.ml_matic_region != 'LNCR Showroom'
                    AND per.ml_matic_region != 'VISMIN Showroom'
                    AND per.description = 'midYearBonus'
                ORDER BY 
                    per.region;"; 
    }  
        
        //echo $sql;
        $result = mysqli_query($conn, $sql);

         // Check if there are results
         if (mysqli_num_rows($result) > 0) {

            // Output the table header
            echo "<div class='table-container'>";
            echo "<table>";
            echo "<thead>";

            $first_row = mysqli_fetch_assoc($result);

            $payroll_date = htmlspecialchars($first_row['payroll_date']);
            $gl_code_basic_pay_regular = htmlspecialchars($first_row['gl_code_basic_pay_regular']);
            $gl_code_basic_pay_trainee = htmlspecialchars($first_row['gl_code_basic_pay_trainee']);
            $gl_code_allowances = htmlspecialchars($first_row['gl_code_allowances']);
            $gl_code_bm_allowance = htmlspecialchars($first_row['gl_code_bm_allowance']);
            $gl_code_overtime_regular = htmlspecialchars($first_row['gl_code_overtime_regular']);
            $gl_code_overtime_trainee = htmlspecialchars($first_row['gl_code_overtime_trainee']);
            $gl_code_cola = htmlspecialchars($first_row['gl_code_cola']);
            $gl_code_excess_pb = htmlspecialchars($first_row['gl_code_excess_pb']);
            $gl_code_other_income = htmlspecialchars($first_row['gl_code_other_income']);
            $gl_code_salary_adjustment = htmlspecialchars($first_row['gl_code_salary_adjustment']);
            $gl_code_graveyard = htmlspecialchars($first_row['gl_code_graveyard']);
            $gl_code_late_regular = htmlspecialchars($first_row['gl_code_late_regular']);
            $gl_code_late_trainee = htmlspecialchars($first_row['gl_code_late_trainee']);
            $gl_code_leave_regular = htmlspecialchars($first_row['gl_code_leave_regular']);
            $gl_code_leave_trainee = htmlspecialchars($first_row['gl_code_leave_trainee']);
            $gl_code_all_other_deductions = htmlspecialchars($first_row['gl_code_all_other_deductions']);
            $gl_code_total = htmlspecialchars($first_row['gl_code_total']);


            //  first row
            echo "<tr>";
            echo "<th colspan='2'>Payroll Date - " . $payroll_date . "</th>";
            echo "<th>Basic Pay Regular</th>";
            echo "<th>Basic Pay Trainee</th>";
            echo "<th>Allowances</th>";
            echo "<th>BM Allowance</th>";
            echo "<th>Overtime Regular</th>";
            echo "<th>Overtime Trainee</th>";
            echo "<th>COLA</th>";
            echo "<th>Excess PB</th>";
            echo "<th>Other Income</th>";
            echo "<th>Salary Adjustment</th>";
            echo "<th>Graveyard</th>";
            echo "<th>Late Regular</th>";
            echo "<th>Late Trainee</th>";
            echo "<th>Leave Regular</th>";
            echo "<th>Leave Trainee</th>";
            echo "<th>Total</th>";
            echo "<th>Cost Center</th>";
            echo "<th style='width: 10px;'></th>";
            echo "<th>Region</th>";
            echo "<th>No. of Branch Employee</th>";
            echo "<th>No of Employees Allocated</th>";
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Debit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th>Credit</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            //third row
            echo "<tr>";
            echo "<th style='white-space: nowrap'>BOS Code</th>";
            echo "<th>Branch Name</th>";
            echo "<th>". $gl_code_basic_pay_regular ."</th>";
            echo "<th>". $gl_code_basic_pay_trainee ."</th>";
            echo "<th>". $gl_code_allowances ."</th>";
            echo "<th>". $gl_code_bm_allowance ."</th>";
            echo "<th>". $gl_code_overtime_regular ."</th>";
            echo "<th>". $gl_code_overtime_trainee ."</th>";
            echo "<th>". $gl_code_cola ."</th>";
            echo "<th>". $gl_code_excess_pb ."</th>";
            echo "<th>". $gl_code_other_income ."</th>";
            echo "<th>". $gl_code_salary_adjustment ."</th>";
            echo "<th>". $gl_code_graveyard ."</th>";
            echo "<th>". $gl_code_late_regular ."</th>";
            echo "<th>". $gl_code_late_trainee ."</th>";
            echo "<th>". $gl_code_leave_regular ."</th>";
            echo "<th>". $gl_code_leave_trainee ."</th>";
            echo "<th>". $gl_code_total ."</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalNumberOfBranches = 0;
            $total = 0;
            $totalDebit = 0;
            $totalCredit = 0;

            // Output the data rows
            mysqli_data_seek($result, 0); // Reset result pointer to the beginning
            while ($row = mysqli_fetch_assoc($result)) {

                if (strpos($row['cost_center'], '0001') === 0 && $zone !== 'LNCR Showroom' && $zone !== 'VISMIN Showroom') {
                    $color = '#4fc917';
                    $bold = 'bold';
                } else {
                    $color = 'none';
                    $bold = 'normal';
                }

                $totalNumberOfBranches++;
                $totalDebit = $row['basic_pay_regular'] + $row['basic_pay_trainee'] + $row['allowances'] + $row['bm_allowance'] + $row['overtime_regular'] 
                            + $row['overtime_trainee'] + $row['cola'] + $row['excess_pb'] + $row['other_income'] + $row['salary_adjustment'] + $row['graveyard'];
                $totalCredit = $row['late_regular'] + $row['late_trainee'] + $row['leave_regular'] + $row['leave_trainee'];
                $total = $totalDebit - $totalCredit;

                echo "<tr>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_code']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['basic_pay_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['allowances']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['bm_allowance']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['overtime_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cola']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['excess_pb']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['other_income']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['salary_adjustment']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['graveyard']) . "</td>";
                // convert to negative if positive value 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_regular'] > 0 ? -$row['late_regular'] : $row['late_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['late_trainee'] > 0 ? -$row['late_trainee'] : $row['late_trainee']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_regular'] > 0 ? -$row['leave_regular'] : $row['leave_regular']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['leave_trainee'] > 0 ? -$row['leave_trainee'] : $row['leave_trainee']) . "</td>";

                echo "<td style='background-color: $color; font-weight: $bold'> $total </td>"; 
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: #f2f2f2; font-weight: $bold'></td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_branch_employee']) . "</td>"; 
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['no_of_employees_allocated']) . "</td>"; 
                echo "</tr>";
            }
 
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
            echo "<script>
            
            var dlbutton = document.getElementById('showdl');
            dlbutton.style.display = 'block';
            
            </script>";
            echo "<div id='showBranches' style='display: block; position: absolute; top: 190px; color: red; left: 20px;'>";
            echo "Total Number of Branches : $totalNumberOfBranches";
            echo "</div>";
        } else {
            echo "No results found.";
        }

    // Close the connection
    mysqli_close($conn);
}

?>