<?php

    // Dynamic base path detection
    function getBasePath() {
        // Get the protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        
        // Get the host
        $host = $_SERVER['HTTP_HOST'];
        
        // Get project folder name from PHP_SELF
        $phpSelf = $_SERVER['PHP_SELF'];
        $pathParts = explode('/', trim($phpSelf, '/'));
        $projectFolder = $pathParts[0]; // First directory is the project folder
        
        // Check if we're in a subfolder (like dashboard)
        $subFolder = '';
        if (count($pathParts) > 1 && $pathParts[1] === 'dashboard') {
            $subFolder = 'dashboard/';
        }
        
        // Use DOCUMENT_ROOT for sub folders
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        
        // Get filename from SCRIPT_NAME
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $filename = basename($scriptName);
        
        // Build the base path
        $basePath = str_replace('\\', '/', $documentRoot);
        
        // Normalize the base path
        if ($basePath === '/') {
            $basePath = '';
        }
        
        // Return the complete base URL with subfolder if present
        return $protocol . $host . '/' . $projectFolder . '/' . $subFolder;
    }

    // Function for logout URL (without dashboard subfolder)
    function getAuthPath() {
        // Get the protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        
        // Get the host
        $host = $_SERVER['HTTP_HOST'];
        
        // Get project folder name from PHP_SELF
        $phpSelf = $_SERVER['PHP_SELF'];
        $pathParts = explode('/', trim($phpSelf, '/'));
        $projectFolder = $pathParts[0]; // First directory is the project folder
        
        // Return base URL without any subfolder for authentication
        return $protocol . $host . '/' . $projectFolder . '/';
    }

    // Get dynamic paths
    $base_url = getBasePath();
    $auth_url = getAuthPath();

?>


<div class="btn-nav">
    <ul class="nav-list">
        <li><a href="<?php echo $base_url; ?>home.php">HOME</a></li>
        <?php 
            $currYM = date('Y-m');
            $thisYear = date('Y');
            $nextYear = date('Y', strtotime('+1 year'));
            if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'user')):?>
                <!-- IMPORT FILES -->
                <li class="dropdown">
                    <button class="dropdown-btn">Import File</button>
                    <div class="dropdown-content">
                        <?php if (in_array('CAD', $roles)): ?>
                            <a id="user" href="<?php echo $base_url; ?>import/import-payroll.php">Payroll</a>
                            
                            <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                            <?php //endif; ?>
                            <a id="user" href="<?php echo $base_url; ?>import/import-mid-year-bonus.php">Mid Year Bonus</a>

                            <a id="user" href="<?php echo $base_url; ?>import/import-remittance-old.php">Remitance OLD</a>
                            <a id="user" href="<?php echo $base_url; ?>import/import-remittance-new.php">Remitance NEW</a>
                            <!-- <a id="user" href="import_thirteenth_month.php">13th Month</a> -->
                            <a id="user" href="<?php echo $base_url; ?>import/import-sick-leave.php">Sick Leave</a>
                            <!-- <a id="user" href="import_MCash-Report.php">ML Wallet</a> -->
                            <a id="user" href="<?php echo $base_url; ?>import/import-mlfund.php">ML Fund</a>
                            <!--<a id="user" href="import-operation-deduction.php">Operation Deduction</a>-->
                        <?php else: ?>
                            <a id="user" href="#">Payroll</a>
                            <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                            <?php //endif; ?>
                            <a id="user" href="#">Mid Year Bonus</a>

                            <a id="user" href="#">Remitance OLD</a>
                            <a id="user" href="#">Remitance NEW</a>
                            <!-- <a id="user" href="import_thirteenth_month.php">13th Month</a> -->
                            <a id="user" href="#">Sick Leave</a>
                            <!-- <a id="user" href="import_MCash-Report.php">ML Wallet</a> -->
                            <a id="user" href="#">ML Fund</a>
                            <!--<a id="user" href="import-operation-deduction.php">Operation Deduction</a>-->
                        <?php endif; ?>
                    </div>
                </li>
                    
                <li class="dropdown">
                    <button class="dropdown-btn">Data Entry</button>
                    <div class="dropdown-content">
                        <?php if (in_array('HO RFP', $roles)): ?>
                            <a id="user" href="<?php echo $base_url; ?>data-entry/data-entry_rfp-payroll.php">RFP Payroll</a>
                        <?php else: ?>
                            <a id="user" href="#">RFP Payroll</a>
                        <?php endif; ?>
                    </div>
                </li>
                <li class="dropdown">
                    <button class="dropdown-btn">Post EDI</button>
                    <div class="dropdown-content">
                        <?php if (in_array('CAD', $roles)): ?>
                            <a id="user" href="<?php echo $base_url; ?>post-edi/post-edi_payroll.php">Payroll</a>

                            <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                            <?php //endif; ?>
                            <a id="user" href="<?php echo $base_url; ?>post-edi/post-edi_mid-year-bonus.php">Mid Year Bonus</a>

                            <a id="user" href="<?php echo $base_url; ?>post-edi/post-edi_remittance-old.php">Remmitance OLD</a>
                            <a id="user" href="<?php echo $base_url; ?>post-edi/post-edi_remittance-new.php">Remmitance NEW</a>
                            <a id="user" href="<?php echo $base_url; ?>post-edi/post-edi_sick-leave.php">Sick Leave</a>
                        <?php else: ?>
                            <a id="user" href="<#">Payroll</a>

                            <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                            <?php //endif; ?>
                            <a id="user" href="<#">Mid Year Bonus</a>

                            <a id="user" href="<#">Remmitance OLD</a>
                            <a id="user" href="<#">Remmitance NEW</a>
                            <a id="user" href="<#">Sick Leave</a>
                        <?php endif; ?>
                    </div>
                </li>

                <li class="dropdown">
                    <button class="dropdown-btn">Reports</button>
                    <div class="dropdown-content">
                        <div class="dropdown-group">
                            <button class="group-btn">HR Format</button>
                            <div class="group-content">
                                <?php if (in_array('HRMD', $roles)): ?>
                                    <a id="user" href="<?php echo $base_url; ?>reports/hr-format/hr-format_payroll.php">Payroll</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/hr-format/hr-format_remittance-old.php">Remitance OLD</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/hr-format/hr-format_remittance-new.php">Remitance NEW</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/hr-format/hr-format_sick-leave.php">Sick Leave</a>
                                    <!--<a id="user" href="#">GL Code Reports</a>-->
                                <?php else: ?>
                                    <a id="user" href="#">Payroll</a>
                                    <a id="user" href="#">Remitance OLD</a>
                                    <a id="user" href="#">Remitance NEW</a>
                                    <a id="user" href="#">Sick Leave</a>
                                    <!--<a id="user" href="#">GL Code Reports</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">RECON & VARIANCE Format</button>
                            <div class="group-content">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <!-- <a id="user" href="recon-report-mcash.php">MCash Wallet</a> -->
                                    <a id="user" href="<?php echo $base_url; ?>reports/recon-variance-format/recon-variance-format_payroll.php">Payroll</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/recon-variance-format/recon-variance-format_remittance-new.php">Remitance NEW</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/recon-variance-format/recon-variance-format_ml-wallet.php">ML Wallet</a>
                                    <!--<a id="user" href="#">Sick Leave</a>-->
                                <?php else: ?>
                                    <!-- <a id="user" href="recon-report-mcash.php">MCash Wallet</a> -->
                                    <a id="user" href="#">Payroll</a>
                                    <a id="user" href="#">Remitance NEW</a>
                                    <a id="user" href="#">ML Wallet</a>
                                    <!--<a id="user" href="#">Sick Leave</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">EDI Format</button>
                            <div class="group-content">
                                <?php if (in_array('KP DOMESTIC', $roles)): ?>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format_payroll.php">Payroll</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format_provision.php">Provision</a>

                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format-mid-year-bonus_payroll.php">Mid Year Bonus - (Payroll Format)</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format-mid-year-bonus_provision.php">Mid Year Bonus - (Provision Format)</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format-13th-month_provision.php">13th Month - (Provision Format)</a>
                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>


                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>

                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format_remittance-old.php">Remitance OLD</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format_remittance-new.php">Remitance NEW</a>
                                    <!-- <a id="user" href="#">Mid Year Bonus</a> -->
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-format/edi-format_sick-leave.php">Sick Leave</a>
                                    <!--<a id="user" href="#">GL Code Reports</a>-->
                                <?php else: ?>
                                    <a id="user" href="#">Payroll</a>
                                    <a id="user" href="#">Provision</a>

                                    <a id="user" href="#">Mid Year Bonus - (Payroll Format)</a>
                                    <a id="user" href="#">Mid Year Bonus - (Provision Format)</a>
                                    <a id="user" href="#">13th Month - (Provision Format)</a>
                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>


                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>

                                    <a id="user" href="#">Remitance OLD</a>
                                    <a id="user" href="#">Remitance NEW</a>
                                    <!-- <a id="user" href="#">Mid Year Bonus</a> -->
                                    <a id="user" href="#">Sick Leave</a>
                                    <!--<a id="user" href="#">GL Code Reports</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">EDI Allocation Format</button>
                            <div class="group-content">
                                <?php if (in_array('KP DOMESTIC', $roles)): ?>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format_payroll.php">Payroll</a>

                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format-mid-year-bonus_payroll.php">Mid Year Bonus - (Payroll Format)</a>
                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format-mid-year-bonus_provision.php">Mid Year Bonus - (Provision Format)</a>

                                    <a id="user" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format_remittance-old.php">Remitance OLD</a>
                                    <!--<a id="user" href="#">Remitance NEW</a>-->
                                <?php else: ?>
                                    <a id="user" href="#">Payroll</a>

                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>
                                    <a id="user" href="#">Mid Year Bonus - (Payroll Format)</a>
                                    <a id="user" href="#">Mid Year Bonus - (Provision Format)</a>

                                    <a id="user" href="#">Remitance OLD</a>
                                    <!--<a id="user" href="#">Remitance NEW</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">Summary & Detailed</button>
                            <div class="group-content">
                                <?php if (in_array('KP DOMESTIC', $roles)): ?>
                                    <a id="user" href="<?php echo $base_url; ?>reports/summary-detailed-format/summary-detailed-format_payroll.php">Payroll</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php else: ?>
                                    <a id="user" href="#">Payroll</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn">Deduction</button>
                            <div class="group-content">
                                <?php if (in_array('ML FUND', $roles)): ?>
                                    <a id="user" href="<?php echo $base_url; ?>reports/deduction-format/deduction-format_mlfund.php">ML Fund</a>
                                    <a id="user" href="#">ML Fund (ID NO)</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php else: ?>
                                    <a id="user" href="#">ML Fund</a>
                                    <a id="user" href="#">ML Fund (ID NO)</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </li>
                <?php if (in_array('SYSTEM', $roles)): ?>
                    <li class="dropdown">
                        <button class="dropdown-btn">MAINTENANCE</button>
                        <div class="dropdown-content">
                            <div class="dropdown-group">
                                <button class="group-btn">Accounts</button>
                                <div class="group-content">
                                    <a id="user" href="<?php echo $base_url; ?>maintenance/user-management.php">USER MANAGEMENT</a>
                                    <a id="user" href="<?php echo $base_url; ?>maintenance/user-role.php">USER ROLE</a>
                                </div>
                            </div>
                            <!-- <div class="dropdown-group">
                                <button class="group-btn">Data Import</button>
                                <div class="group-content">
                                    <a id="user" href="data-import_bp-mlmatic.php">Branch Profile from MLMatic</a>
                                    <a id="user" href="data_import_kpx-tg.php">KPX Branch Profile from TG</a>
                                </div>
                            </div> -->
                            <!--<div class="dropdown-group">
                                <button class="group-btn">Data Update</button>
                                <div class="group-content">
                                    <a id="user" href="#">Branch Profile Masterfile</a>
                                </div>
                            </div>-->
                            <!-- <div class="dropdown-group">
                                <button class="group-btn">Data Removal</button>
                                <div class="group-content">
                                    <a id="user" href="payrollLog.php">Payroll</a>
                                    <a id="user" href="remitanceLog.php">Remittance</a>
                                    <a id="user" href="midYearBonusLog.php">Mid Year Bonus</a>
                                </div>
                            </div> -->
                            <!--<div class="dropdown-group">
                                <button class="group-btn">Setup</button>
                                <div class="group-content">
                                    <a id="user" href="gl-codes.php">GL Codes</a>
                                </div>
                            </div>-->
                        </div>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <li><a href="<?php echo $auth_url; ?>logout.php">LOGOUT</a></li>
    </ul>
</div>