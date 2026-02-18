<?php
    session_start();

    if (!isset($_SESSION['admin_name'])) {
        header('location: ../login.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAMPLE</title>
    <!-- <link rel="stylesheet" href="../asset/admin/css/admin-style.css> -->
    <link rel="icon" href="../asset/picture/MLW Logo.png?v=<?php echo time(); ?>" type="image/x-icon"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600&display=swap');

        * {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        outline: none;
        border: none;
        text-decoration: none;
        }
        .top-content {
        color: white;
        display: flex;
        padding: 1px 20px;
        background-color: #db120b;
        justify-content: space-between;
        align-items: center;
        }
        .navLogo {
            width: 50px;
        }
        .nav-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: transparent;
        width: 100%;
        }

        .nav-list li a {
        text-decoration: none;
        color: #fff;
        font-size: 12px;
        font-weight: bold;
        padding: 16px 10px;
        }

        .nav-list li #user {
        text-decoration: none;
        color: #db120b;
        font-size: 12px;
        font-weight: bold;
        padding: 10px 20px 10px 20px;
        }

        .nav-list li a:hover {
        color: #db120b;
        background-color: whitesmoke;
        }

        .nav-list li #user:hover {
        color: #fff;
        }

        .header {
        text-align: center;
        padding: 0px;
        }

        .logout a {
        text-decoration: none;
        background-color: transparent;
        padding: 5px 10px 5px 10px;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        transition: background-color 0.3s ease;
        }


        .logout a:hover {
        text-decoration: none;
        background-color: black;
        padding: 5px 10px 5px 10px;
        color: #db120b;
        transition: background-color 0.3s ease;
        }

        .usernav {
        display: flex;
        align-items: center;
        }

        .btn-nav {
        display: flex;
        }

        .nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
        }

        .nav-list li {
        display: inline-block;
        margin-right: 10px;
        }

        .nav-list li a {
        text-decoration: none;
        color: #fff;
        font-size: 12px;
        }

        .dropdown {
        position: relative;
        display: inline-block;
        }
        .dropdown:hover{
        background-color: whitesmoke;
        color: #db120b;
        }
        .dropdown-btn {
        background-color: transparent;
        color: #fff;
        font-weight: 700;
        border: none;
        font-size: 12px;
        cursor: pointer;
        padding: 15px 10px;
        }
        .dropdown-btn:hover{
        color: #db120b;
        padding: 15px 10px;
        }

        .dropdown-group {
        margin-bottom: 10px;
        }

        .dropdown-content {
        position: absolute;
        display: none;
        flex-direction: column;
        background-color: #f9f9f9;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
        min-width: 230px;
        }

        .group-btn {
        background-color: #3262e6;
        color: white;
        padding: 8px 10px;
        width: 100%;
        border: none;
        text-align: left;
        cursor: pointer;
        font-weight: bold;
        }
        .group-btn:hover {
            background-color: #274bb5;
        }

        .group-content {
        display: none;
        flex-direction: column;
        background-color: #f9f9f9;
        padding: 5px;
        border: 1px solid #ddd;
        }
        .group-content a {
        padding: 5px 10px;
        color: #333;
        text-decoration: none;
        }
        .group-content a:hover {
        background-color: #ddd;
        }
        .dropdown-group.show .group-content {
        display: flex;
        }

        .dropdown-content a {
        display: block;
        padding: 8px 0;
        text-decoration: none;
        color: #333;
        }
        .dropdown:hover .dropdown-content {
        display: block;
        }
        #user:hover{
        background-color: #db120b;
        color: #fff;
        padding: 10px;
        }
        .opt-group {
        display: flex;
        background-color: #3262e6;
        color: white;
        width: 100%;
        align-items: center;
        height: 35px;
        }
        .home-logo {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100vh;
        position: fixed;
        } 
        img {
        display: block;
        margin: auto;
        }
        .container {
        display: flex;
        justify-content: center;
        align-items: center;
        }
    </style >
    
</head>
<body>

    <div class="top-content">
        <div class="usernav">
            <img src="../asset/picture/logo.png" alt="Logo" class="navLogo"> 
        </div>
        <div class="btn-nav">
            <ul class="nav-list">
                <li><a href="admin.php">HOME</a></li>
                <li class="dropdown">
                    <button class="dropdown-btn">Import File</button>
                    <div class="dropdown-content">
                        <a id="user" href="import-file.php">Payroll</a>
                        <a id="user" href="import-remitance-old.php">Remitance OLD</a>
                        <a id="user" href="import-remitance-new.php">Remitance NEW</a>
                        <a id="user" href="import-MidYearBonusPayroll.php">Mid Year Bonus Payroll</a>
                        <a id="user" href="import_thirteenth_month.php">13th Month</a>
                        <a id="user" href="import_MCash-Report.php">MCash Report</a>
                        <a id="user" href="#">Operation Deduction</a>
                    </div>
                </li>
                <li class="dropdown">
                    <button class="dropdown-btn">Recon</button>
                    <div class="dropdown-content">
                    <a id="user" href="../admin/mcash_recon.php">MCash Report Vs Import File</a>
                        <a id="user" href="#">Verify Payroll Per Branch</a>
                    </div>
                </li>
                <li class="dropdown">
                    <button class="dropdown-btn">Master File</button>
                    <div class="dropdown-content">
                        <a id="user" href="branch_master_file.php">Branch Master File</a>
                    </div>
                </li>
                <li class="dropdown">
                    <button class="dropdown-btn">Post EDI</button>
                    <div class="dropdown-content">
                        <a id="user" href="payroll_post_edi.php">Payroll</a>
                        <a id="user" href="remittance_post_old_edi.php">Remmitance OLD</a>
                        <a id="user" href="remittance_post_new_edi.php">Remmitance NEW</a>
                        <a id="user" href="midYearBonus_post_edi.php">Mid Year Bonus Payroll</a>
                    </div>
                </li>
                <li class="dropdown">
                    <button class="dropdown-btn">Report</button>
                    <div class="dropdown-content">
                        <div class="dropdown-group">
                            <button class="group-btn">HR Format</button>
                            <div class="group-content">
                                <a id="user" href="report-file.php">Payroll</a>
                                <a id="user" href="report-remitance-hr-old.php">Remitance OLD</a>
                                <a id="user" href="report-remitance-hr-new.php">Remitance NEW</a>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">EDI Format</button>
                            <div class="group-content">
                                <a id="user" href="report-file-edi.php">Payroll</a>
                                <a id="user" href="provision-report.php">Provision</a>
                                <a id="user" href="remitance-report-edi.php">Remitance OLD</a>
                                <a id="user" href="remitance-report-edi-new.php">Remitance NEW</a>
                                <a id="user" href="mid-year-bonus-edi.php">Mid Year Bonus</a>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">CAD Format</button>
                            <div class="group-content">
                                <a id="user" href="#">Payroll</a>
                                <a id="user" href="#">Provision</a>
                                <a id="user" href="#">Remitance OLD</a>
                                <a id="user" href="#">Remitance NEW</a>
                                <a id="user" href="#">Mid Year Bonus</a>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">Summary & Detailed</button>
                            <div class="group-content">
                                <a id="user" href="payroll-summary-report.php">Payroll</a>
                                <a id="user" href="#">Provision</a>
                                <a id="user" href="#">Remitance</a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="dropdown">
                    <button class="dropdown-btn">MAINTENANCE</button>
                    <div class="dropdown-content">
                        <a id="user" href="userLog.php">USER</a>
                        <a id="user" href="region_profile.php">Region Profile</a>
                        <a id="user" href="region_area_branch.php">Region Area Branch</a>
                        <a id="user" href="payrollLog.php">Payroll</a>
                        <a id="user" href="remitanceLog.php">Remittance</a>
                        <a id="user" href="midYearBonusLog.php">Mid Year Bonus</a>
                    </div>
                </li>
                <li><a href="../logout.php">LOGOUT</a></li>
            </ul>
        </div>
    </div>
    <div class="home-logo">
        <img src="../asset/picture/weblogo.png" alt="MLhuillier" width="850px" height="250px">
    </div>

</body>
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
</html>
