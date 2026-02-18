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

            $deleteQuery = "DELETE FROM " . $database[0] . ".remitance_edi_report rer
                        WHERE
                            rer.mainzone = '$mainzone'
                        AND rer.remitance_date = '$restrictedDate'
                        AND rer.ml_matic_region = '$zone'
                        AND NOT (rer.branch_code = 18 AND rer.zone = 'VIS')  -- to exclude duljo branch
                        AND rer.zone like '%$region%'";

        }else{

            $deleteQuery = "DELETE FROM " . $database[0] . ".remitance_edi_report rer
                        WHERE
                            rer.mainzone = '$mainzone'
                        AND rer.zone = '$zone'
                        AND rer.zone != 'JVIS' -- to exclude sm seaside showroom
                        AND rer.region_code LIKE '%$region%'
                        AND rer.remitance_date = '$restrictedDate'
                        AND rer.ml_matic_region != 'LNCR Showroom'
                        AND rer.ml_matic_region != 'VISMIN Showroom'"; 
        }

        if($conn->query($deleteQuery)) {

            if ($zone === 'LNCR Showroom' || $zone === 'VISMIN Showroom') {

                $updateQuery = "UPDATE " . $database[0] . ".remitance r
                            INNER JOIN 
                                " . $database[1] . ".branch_profile bp
                            ON 
                                r.bos_code = bp.code AND r.region_code = bp.region_code
                            SET r.post_edi = 'pending'
                            WHERE
                                bp.mainzone = '$mainzone'
                            AND r.remitance_date = '$restrictedDate'
                            AND bp.ml_matic_region = '$zone'
                            AND NOT (bp.code = 18 AND r.zone = 'VIS')  -- to exclude duljo branch
                            AND bp.zone LIKE '%$region%'";
    
            }else{
    
                $updateQuery = "UPDATE " . $database[0] . ".remitance r
                            INNER JOIN 
                                " . $database[1] . ".branch_profile bp
                            ON 
                                r.bos_code = bp.code AND r.region_code = bp.region_code
                            SET r.post_edi = 'pending'
                            WHERE
                                bp.mainzone = '$mainzone'
                            AND bp.zone = '$zone'
                            AND r.zone != 'JVIS' -- to exclude sm seaside showroom
                            AND r.region_code LIKE '%$region%'
                            AND r.remitance_date = '$restrictedDate'
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
    <link rel="stylesheet" href="../assets/css/admin/remitance-report-edi/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    <div class="top-content">
        <?php include '../templates/sidebar.php' ?>
    </div>

    <center><h2>Remittance</h2></center>

    <div class="import-file">
        
        <form action="" method="post">

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
                <label for="restricted-date">Remittance date </label>
                <input type="date" id="restricted-date" name="restricted-date" value="<?php echo isset($_POST['restricted-date']) ? $_POST['restricted-date'] : '';?>" required>
            </div>
            
            <input type="submit" class="proceed-btn" name="generate" value="Proceed">
        </form>

        <div id="showdl" style="display: none">
            <form action="" method="post">
            <button type="submit" class="proceed-btn" name="delete">
                <i style="margin-right: 10px;" class="fa-solid fa-trash"></i>
                Delete
            </button>
            </form>
        </div>

    </div>

    <script src="../assets/js/admin/remitance-report-edi/script1.js"></script>
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
                    " . $database[0] . ".remitance_edi_report rer
                WHERE
                    rer.mainzone = '$mainzone'
                    AND rer.remitance_date = '$restrictedDate'
                    AND rer.ml_matic_region = '$zone'
                    AND NOT (rer.branch_code = 18 AND rer.zone = 'VIS')  -- to exclude duljo branch
                    AND rer.zone like '%$region%'
                ORDER BY 
                    rer.region;";
    }else{
                $sql = "SELECT * FROM
                    " . $database[0] . ".remitance_edi_report rer
                WHERE
                    rer.mainzone = '$mainzone'
                    AND rer.zone = '$zone'
                    AND rer.zone != 'JVIS' -- to exclude sm seaside showroom
                    AND rer.region_code LIKE '%$region%' 
                    AND rer.remitance_date = '$restrictedDate'
                    AND rer.ml_matic_region != 'LNCR Showroom'
                    AND rer.ml_matic_region != 'VISMIN Showroom'
                ORDER BY 
                    rer.region;"; 
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

            $remitance_date = htmlspecialchars($first_row['remitance_date']);
            $gl_code_dr1 = htmlspecialchars($first_row['gl_code_dr1']);
            $gl_code_dr2 = htmlspecialchars($first_row['gl_code_dr2']);
            $gl_code_dr3 = htmlspecialchars($first_row['gl_code_dr3']);
            $gl_code_dr4 = htmlspecialchars($first_row['gl_code_dr4']);


            //  first row
            echo "<tr>";
            echo "<th colspan='2'>Remittance Date - " . $remitance_date . "</th>";
            echo "<th>DR</th>";
            echo "<th>DR</th>";
            echo "<th>DR</th>";
            echo "<th>DR</th>";
            echo "<th>Cost Center</th>";
            echo "<th>Region</th>";
            echo "</tr>";
            // second row
            echo "<tr>";
            echo "<th>BOS Code</th>";
            echo "<th>Branch Name</th>";
            echo "<th>$gl_code_dr1</th>";
            echo "<th>$gl_code_dr2</th>";
            echo "<th>$gl_code_dr3</th>";
            echo "<th>$gl_code_dr4</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            $totalNumberOfBranches = 0;

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

                echo "<tr>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold;'>" . htmlspecialchars($row['branch_code']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['branch_name']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr1']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr2']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr3']) . "</td>";
                echo "<td style='background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['dr4']) . "</td>"; 
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['cost_center']) . "</td>";
                echo "<td style='white-space: nowrap; background-color: $color; font-weight: $bold'>" . htmlspecialchars($row['region']) . "</td>";
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