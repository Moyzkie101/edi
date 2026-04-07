

<nav class="btn-nav" role="navigation" aria-label="Main navigation">
    <ul class="nav-list">
        <li><a href="<?php echo $base_url; ?>home.php">HOME</a></li>
        <?php 
            $currYM = date('Y-m');
            $thisYear = date('Y');
            $nextYear = date('Y', strtotime('+1 year'));
            if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'user')):?>
                
                <!-- IMPORT FILES -->
                <li class="dropdown">
                    <button class="dropdown-btn" type="button" aria-haspopup="true" aria-expanded="false">
                        Import File
                    </button>
                    <div class="dropdown-content" role="menu">
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">Payroll Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-payroll.php" role="menuitem">Payroll</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-mid-year-bonus.php" role="menuitem">Mid Year Bonus</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-13th-month.php" role="menuitem">13th Month</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-sick-leave.php" role="menuitem">Sick Leave</a>
                                <?php else: ?>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Payroll</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Mid Year Bonus</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">13th Month</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Sick Leave</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">Remittance Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-remittance-old.php" role="menuitem">Remitance OLD</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-remittance-new.php" role="menuitem">Remitance NEW</a>
                                <?php else: ?>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Remitance OLD</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Remitance NEW</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">ML Fund Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-mlfund.php" role="menuitem">ML Fund</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>import/import-mlfund-new-format.php" role="menuitem">ML Fund New Format</a>
                                <?php else: ?>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">ML Fund</a>
                                    <a class="user-link" href="#" role="menuitem">ML Fund New Format</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </li>
                    
                <li class="dropdown">
                    <button class="dropdown-btn" type="button" aria-haspopup="true" aria-expanded="false">
                        Data Entry
                    </button>
                    <div class="dropdown-content" role="menu">
                        <?php if (in_array('HO RFP', $roles)): ?>
                            <a class="user-link" href="<?php echo $base_url; ?>data-entry/data-entry_rfp-payroll.php" role="menuitem">RFP Payroll</a>
                        <?php else: ?>
                            <a class="user-link" href="#" role="menuitem" aria-disabled="true">RFP Payroll</a>
                        <?php endif; ?>
                    </div>
                </li>
                
                <li class="dropdown">
                    <button class="dropdown-btn" type="button" aria-haspopup="true" aria-expanded="false">
                        Post EDI
                    </button>
                    <div class="dropdown-content" role="menu">
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">Payroll Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>post-edi/post-edi_payroll.php" role="menuitem">Payroll</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>post-edi/post-edi_mid-year-bonus.php" role="menuitem">Mid Year Bonus</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>post-edi/post-edi_13th-month.php" role="menuitem">13th Month</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>post-edi/post-edi_sick-leave.php" role="menuitem">Sick Leave</a>
                                <?php else: ?>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Payroll</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Mid Year Bonus</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">13th Month</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Sick Leave</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">Remittance Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>post-edi/post-edi_remittance-old.php" role="menuitem">Remmitance OLD</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>post-edi/post-edi_remittance-new.php" role="menuitem">Remmitance NEW</a>
                                <?php else: ?>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Remmitance OLD</a>
                                    <a class="user-link" href="#" role="menuitem" aria-disabled="true">Remmitance NEW</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                    </div>
                </li>

                <li class="dropdown">
                    <button class="dropdown-btn" type="button" aria-haspopup="true" aria-expanded="false">
                        Reports
                    </button>
                    <div class="dropdown-content" role="menu">
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">HR Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('HRMD', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/hr-format/hr-format_payroll.php" role="menuitem">Payroll</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/hr-format/hr-format_13th-month_payroll.php" role="menuitem">13th Month (Payroll Format)</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/hr-format/hr-format_remittance-old.php" role="menuitem">Remitance OLD</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/hr-format/hr-format_remittance-new.php" role="menuitem">Remitance NEW</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/hr-format/hr-format_sick-leave.php" role="menuitem">Sick Leave</a>
                                <?php else: ?>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Payroll</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">13th Month (Payroll Format)</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Remitance OLD</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Remitance NEW</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Sick Leave</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">RECON & VARIANCE Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('CAD', $roles)): ?>
                                    <!-- <a id="user" href="recon-report-mcash.php">MCash Wallet</a> -->
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/recon-variance-format/recon-variance-format_payroll.php" role="menuitem">Payroll</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/recon-variance-format/recon-variance-format_remittance-new.php" role="menuitem">Remitance NEW</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/recon-variance-format/recon-variance-format_ml-wallet.php" role="menuitem">ML Wallet</a>
                                    <!--<a id="user" href="#">Sick Leave</a>-->
                                <?php else: ?>
                                    <!-- <a id="user" href="recon-report-mcash.php">MCash Wallet</a> -->
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Payroll</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Remitance NEW</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">ML Wallet</a>
                                    <!--<a id="user" href="#">Sick Leave</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">EDI Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('KP DOMESTIC', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format_payroll.php" role="menuitem">Payroll</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format_provision.php" role="menuitem">Provision</a>

                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format-mid-year-bonus_payroll.php" role="menuitem">Mid Year Bonus - (Payroll Format)</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format-mid-year-bonus_provision.php" role="menuitem">Mid Year Bonus - (Provision Format)</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format-13th-month_payroll.php" role="menuitem">13th Month - (Payroll Format)</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format-13th-month_provision.php" role="menuitem">13th Month - (Provision Format)</a>
                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>


                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>

                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format_remittance-old.php" role="menuitem">Remitance OLD</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format_remittance-new.php" role="menuitem">Remitance NEW</a>
                                    <!-- <a id="user" href="#">Mid Year Bonus</a> -->
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-format/edi-format_sick-leave.php" role="menuitem">Sick Leave</a>
                                    <!--<a id="user" href="#">GL Code Reports</a>-->
                                <?php else: ?>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Payroll</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Provision</a>

                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Mid Year Bonus - (Payroll Format)</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Mid Year Bonus - (Provision Format)</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">13th Month - (Payroll Format)</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">13th Month - (Provision Format)</a>
                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>


                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>

                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Remitance OLD</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Remitance NEW</a>
                                    <!-- <a id="user" href="#">Mid Year Bonus</a> -->
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Sick Leave</a>
                                    <!--<a id="user" href="#">GL Code Reports</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">EDI Allocation Format</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('KP DOMESTIC', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format_payroll.php" role="menuitem">Payroll</a>

                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format-mid-year-bonus_payroll.php" role="menuitem">Mid Year Bonus - (Payroll Format)</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format-mid-year-bonus_provision.php" role="menuitem">Mid Year Bonus - (Provision Format)</a>

                                    <a class="user-link" href="<?php echo $base_url; ?>reports/edi-allocation-format/edi-allocation-format_remittance-old.php" role="menuitem">Remitance OLD</a>
                                    <!--<a id="user" href="#">Remitance NEW</a>-->
                                <?php else: ?>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Payroll</a>

                                    <?php //if ($currYM >= $thisYear . '-10' && $currYM <= $nextYear . '-02'): ?>
                                        <?php //endif; ?>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Mid Year Bonus - (Payroll Format)</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Mid Year Bonus - (Provision Format)</a>

                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Remitance OLD</a>
                                    <!--<a id="user" href="#">Remitance NEW</a>-->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">Detailed Report</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('KP DOMESTIC', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/summary-detailed-format/summary-detailed-format_payroll.php" role="menuitem">Payroll</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php else: ?>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">Payroll</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="dropdown-group">
                            <button class="group-btn" type="button">Deduction</button>
                            <div class="group-content" role="submenu">
                                <?php if (in_array('ML FUND', $roles)): ?>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/deduction-format/deduction-format_mlfund.php" role="menuitem">ML Fund</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>reports/deduction-format/deduction-format_mlfund-id-no.php" role="menuitem">ML Fund (ID NO)</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php else: ?>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">ML Fund</a>
                                    <a class="user-link" href="#" aria-disabled="true" role="menuitem">ML Fund (ID NO)</a>
                                    <!-- <a id="user" href="#">Provision</a>
                                    <a id="user" href="#">Remitance</a> -->
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </li>
                
                <?php if (in_array('SYSTEM', $roles)): ?>
                    <li class="dropdown">
                        <button class="dropdown-btn" type="button" aria-haspopup="true" aria-expanded="false">
                            MAINTENANCE
                        </button>
                        <div class="dropdown-content" role="menu">
                            <div class="dropdown-group">
                                <button class="group-btn" type="button">Accounts</button>
                                <div class="group-content" role="submenu">
                                    <a class="user-link" href="<?php echo $base_url; ?>maintenance/user-management.php" role="menuitem">USER MANAGEMENT</a>
                                    <a class="user-link" href="<?php echo $base_url; ?>maintenance/user-role.php" role="menuitem">USER ROLE</a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        <li><a href="<?php echo $auth_url; ?>logout.php">LOGOUT</a></li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown elements
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const dropdownBtn = dropdown.querySelector('.dropdown-btn');
        const dropdownContent = dropdown.querySelector('[role="menu"]'); // Main menu content
        const dropdownGroups = dropdown.querySelectorAll('.dropdown-group');
        
        let hoverTimeout;
        
        // ===== MAIN MENU FUNCTIONALITY (HOVER ONLY) - role="menu" =====
        dropdown.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            dropdown.classList.add('active');
            
            // IMPORTANT: Close all submenus when hovering main menu
            dropdownGroups.forEach(group => {
                group.classList.remove('active');
                // Reset aria-expanded for submenus
                const btn = group.querySelector('.group-btn');
                if (btn) {
                    btn.setAttribute('aria-expanded', 'false');
                }
            });
        });
        
        dropdown.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                dropdown.classList.remove('active');
                // Close all group submenus when main dropdown closes
                dropdownGroups.forEach(group => {
                    group.classList.remove('active');
                    // Reset aria-expanded for submenus
                    const btn = group.querySelector('.group-btn');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            }, 200);
        });
        
        // Keep dropdown open when hovering over MAIN dropdown content (role="menu")
        if (dropdownContent) {
            dropdownContent.addEventListener('mouseenter', function() {
                clearTimeout(hoverTimeout);
                dropdown.classList.add('active');
            });
            
            dropdownContent.addEventListener('mouseleave', function() {
                hoverTimeout = setTimeout(() => {
                    dropdown.classList.remove('active');
                    // Close all submenus when leaving main dropdown
                    dropdownGroups.forEach(group => {
                        group.classList.remove('active');
                        // Reset aria-expanded for submenus
                        const btn = group.querySelector('.group-btn');
                        if (btn) {
                            btn.setAttribute('aria-expanded', 'false');
                        }
                    });
                }, 200);
            });
        }
        
        // ===== SUBMENU FUNCTIONALITY (CLICK ONLY) - role="submenu" =====
        dropdownGroups.forEach(group => {
            const groupBtn = group.querySelector('.group-btn');
            const groupContent = group.querySelector('[role="submenu"]'); // Submenu content
            
            if (groupBtn && groupContent) {
                // CLICK EVENT for group buttons (submenu toggle) - ONLY for role="submenu"
                groupBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Close all OTHER group submenus in this dropdown
                    dropdownGroups.forEach(otherGroup => {
                        if (otherGroup !== group) {
                            otherGroup.classList.remove('active');
                            const otherBtn = otherGroup.querySelector('.group-btn');
                            if (otherBtn) {
                                otherBtn.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                    
                    // Toggle current group submenu
                    const isCurrentlyActive = group.classList.contains('active');
                    group.classList.toggle('active');
                    
                    // Update aria-expanded for accessibility
                    groupBtn.setAttribute('aria-expanded', !isCurrentlyActive ? 'true' : 'false');
                    
                    // Keep main dropdown open
                    dropdown.classList.add('active');
                    clearTimeout(hoverTimeout);
                });
                
                // HOVER EVENT for group buttons (keep main menu open, NO submenu interaction)
                groupBtn.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    dropdown.classList.add('active');
                    // Do NOT open/close submenu on hover - only keep main dropdown open
                });
                
                // HOVER EVENT for group content (keep main dropdown open, NO submenu auto-close)
                groupContent.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    dropdown.classList.add('active');
                    // Do NOT affect submenu state on hover
                });
                
                // Initialize aria attributes for submenus
                groupBtn.setAttribute('aria-expanded', 'false');
                groupBtn.setAttribute('aria-haspopup', 'true');
                
                // CLICK EVENTS for submenu links (close everything)
                const subLinks = groupContent.querySelectorAll('.user-link');
                subLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        // Don't prevent default if it's a valid link
                        if (link.getAttribute('href') === '#' || link.getAttribute('aria-disabled') === 'true') {
                            e.preventDefault();
                            return;
                        }
                        
                        // Close all dropdowns when a valid submenu link is clicked
                        dropdowns.forEach(d => {
                            d.classList.remove('active');
                            const groups = d.querySelectorAll('.dropdown-group');
                            groups.forEach(g => {
                                g.classList.remove('active');
                                const btn = g.querySelector('.group-btn');
                                if (btn) {
                                    btn.setAttribute('aria-expanded', 'false');
                                }
                            });
                        });
                    });
                });
            }
            
            // HOVER EVENTS for group containers (keep main dropdown open only)
            group.addEventListener('mouseenter', function() {
                clearTimeout(hoverTimeout);
                dropdown.classList.add('active');
                // Do NOT toggle submenu on hover - only maintain main dropdown
            });
        });
        
        // ===== DIRECT DROPDOWN LINKS (main menu links, not in subgroups) =====
        if (dropdownContent) {
            // Select only direct links in main menu (role="menu"), not in submenus (role="submenu")
            const directLinks = dropdownContent.querySelectorAll('.user-link:not([role="submenu"] .user-link)');
            directLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Don't prevent default if it's a valid link
                    if (link.getAttribute('href') === '#' || link.getAttribute('aria-disabled') === 'true') {
                        e.preventDefault();
                        return;
                    }
                    
                    // Close dropdown when direct main menu link is clicked
                    dropdown.classList.remove('active');
                });
                
                // Keep main dropdown open on hover for direct links
                link.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    dropdown.classList.add('active');
                });
            });
        }
    });
    
    // ===== GLOBAL EVENT HANDLERS =====
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown')) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
                const dropdownGroups = dropdown.querySelectorAll('.dropdown-group');
                dropdownGroups.forEach(group => {
                    group.classList.remove('active');
                    const btn = group.querySelector('.group-btn');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        }
    });
    
    // Handle keyboard navigation (ESC key)
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
                const dropdownGroups = dropdown.querySelectorAll('.dropdown-group');
                dropdownGroups.forEach(group => {
                    group.classList.remove('active');
                    const btn = group.querySelector('.group-btn');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        }
    });
});
</script>