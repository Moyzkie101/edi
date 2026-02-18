<?php

session_start();

include '../../config/connection.php';
//$conn = mysqli_connect($host, $username, $password, $database); 

// if (!$conn) {
//     die("Connection failed: " . mysqli_connect_error());
// }

echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../../assets/login/js/jquery-3.7.1.js"></script>';

    if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'user')) {
        header('location: ' . $auth_url . 'logout.php');
        session_destroy();
        exit();
    } else {
        // Check if user_roles session exists and user has HRMD role
        if (!isset($_SESSION['user_roles']) || empty($_SESSION['user_roles'])) {
            header('location: ' . $auth_url . 'logout.php');
            session_destroy();
            exit();
        }
        
        $roles = array_map('trim', explode(',', $_SESSION['user_roles'])); // Convert roles into an array and trim whitespace
        $hasRequiredRole = false;
        
        foreach($roles as $role) {
            switch($role) {
                case 'SYSTEM':
                    // Handle SYSTEM role - allow access to this page
                    $hasRequiredRole = true;
                    break;
                case 'ML WALLET':
                    // Handle ML WALLET role - no access to this page
                    break;
                case 'HRMD':
                    // Handle HRMD role - no access to this page
                    break;
                case 'CAD':
                    // Handle CAD role - no access to this page
                    break;
                case 'ML FUND':
                    // Handle ML FUND role - no access to this page
                    break;
                case 'KP DOMESTIC':
                    // Handle KP DOMESTIC role - no access to this page
                    break;
                case 'FINANCE':
                    // Handle FINANCE role - no access to this page
                    break;
                case 'HO RFP':
                    // Handle HO RFP role - no access to this page
                    break;
                case 'TELECOMS':
                    // Handle TELECOMS role - no access to this page
                    break;
                default:
                    // Handle unknown role - no access to this page
                    break;
            }
        }
        
        // If user doesn't have required role, redirect to logout
        if (!$hasRequiredRole) {
            header('location: ' . $auth_url . 'logout.php');
            session_destroy();
            exit();
        }
    }

$user_status_options = 'SELECT status FROM ' . $database[0] . '.user WHERE status IS NOT NULL group by status';
$status_result = mysqli_query($conn, $user_status_options);

$user_type_options = 'SELECT user_type FROM ' . $database[0] . '.user WHERE user_type IS NOT NULL AND NOT user_type = "system" group by user_type';
$user_type_result = mysqli_query($conn, $user_type_options);

// Fetch users from database using MySQLi
$users = [];
try {
    $query = "SELECT id_number, first_name, middle_name, last_name, email as username, user_type, status, roles, date_modified, modified_by FROM " . $database[0] . ".user  where status = 'Active' ORDER BY date_created DESC";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        mysqli_free_result($result);
    } else {
        error_log("Database query error: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <!-- custom CSS file link  -->
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../../assets/picture/MLW Logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Include SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Additional styling for better UX */
        .dropdown-item:hover {
            background-color: #dc3545;
            color: white;
        }

        .dropdown-item.active {
            background-color: #dc3545;
            color: white;
        }

        #searchInput:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        #table-info {
            margin-bottom: 10px;
            font-style: italic;
        }

        .no-results {
            background-color: #f8f9fa;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        .text-warning {
            color: #ffc107 !important;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            font-size: 0.75em;
        }

        .table tbody tr.selected {
            background-color: #f8d7da !important;
        }

        .table tbody tr.selected:hover {
            background-color: #f5c6cb !important;
        }

        .table-success {
            background-color: #d4edda !important;
            transition: background-color 0.3s ease;
        }
        .btn-text {
            font-size: 0.875rem; /* This matches Bootstrap's btn-sm font size */
            color: white;
        }
    </style>
</head>

<body>
    <div class="top-content">
        <?php include '../../templates/sidebar.php' ?>
    </div>
    <center><h1>User Role</h1></center>
    <div class="container-fluid">
        <!-- Your content goes here -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="input-group" style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="input-group-append" style="display: flex; align-items: center; gap: 10px;">
                                <form action="" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search by any field..." style="width: 250px;">
                                    <div class="dropdown">
                                        <button class="btn btn-danger dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span id="userTypeText" class="btn-text">User Type</span>
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                            <a class="dropdown-item" href="#" data-value="">All</a>
                                            <?php 
                                                if ($user_type_result && mysqli_num_rows($user_type_result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($user_type_result)) {
                                                        $user_type = htmlspecialchars($row['user_type']);
                                                        $selected = (isset($_GET['user_type']) && $_GET['user_type'] == $user_type) ? 'user' : '';
                                                        echo "<a class='dropdown-item $selected' href='#' data-value='$user_type'>" . ucfirst($user_type) . "</a>";
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    <button type="button" id="clearFilters" class="btn btn-secondary">Clear</button>
                                </form>
                            </div>
                            <div class="input-group-append" style="display: flex; align-items: center; gap: 5px;">
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#viewUserModal"><i class="fa fa-eye"></i> View</button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#editUserModal"><i class="fa fa-edit"></i> Edit</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover" id="users-table">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>ID Number</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Date Modified</th>
                                    <th>User Type</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $index => $user): ?>
                                        <tr data-user-id="<?php echo htmlspecialchars($user['id_number'] ?? ''); ?>" 
                                            data-user-data='<?php echo json_encode($user); ?>' 
                                            style="cursor: pointer;">
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($user['id_number'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></td>
                                            <td>
                                                <?php 
                                                    if (!empty($user['date_modified'])) {
                                                        echo date('F d, Y H:i A', strtotime($user['date_modified']));
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge text-<?php echo ($user['user_type'] ?? '') === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($user['user_type'] ?? '')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                    if (!empty($user['roles'])) {
                                                        echo htmlspecialchars($user['roles'] ?? '');
                                                    }else { 
                                                        echo '<span class="text-danger btn-text">No Role Assign</span>'; 
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fa fa-users fa-2x mb-2"></i><br>
                                            No users found in the database
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="viewUserModalLabel">
                        <i class="fa fa-eye me-2"></i>View User Role
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- User Type -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fa fa-user-tag me-1"></i>User Type
                            </label>
                            <div class="form-control-plaintext border rounded p-2 bg-light" id="viewUserType">
                                -
                            </div>
                        </div>
                        <!-- ID Number -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fa fa-id-card me-1"></i>ID Number
                            </label>
                            <div class="form-control-plaintext border rounded p-2 bg-light" id="viewIdNumber">
                                -
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Username -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fa fa-user me-1"></i>Username
                            </label>
                            <div class="form-control-plaintext border rounded p-2 bg-light" id="viewUsername">
                                -
                            </div>
                        </div>
                        <!-- Full Name -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fa fa-user me-1"></i>Full Name
                            </label>
                            <div class="form-control-plaintext border rounded p-2 bg-light" id="viewFullName">
                                -
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Role -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">
                                <i class="fa fa-user-shield me-1"></i>Role
                            </label>
                            <div class="form-control-plaintext border rounded p-2 bg-light" id="viewRole">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleSystem" disabled>
                                            <label class="form-check-label" for="viewRoleSystem">
                                                SYSTEM
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleMLWallet" disabled>
                                            <label class="form-check-label" for="viewRoleMLWallet">
                                                ML WALLET
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleCAD" disabled>
                                            <label class="form-check-label" for="viewRoleCAD">
                                                CAD
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleMLFund" disabled>
                                            <label class="form-check-label" for="viewRoleMLFund">
                                                ML FUND
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleKPDomestic" disabled>
                                            <label class="form-check-label" for="viewRoleKPDomestic">
                                                KP DOMESTIC
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleFinance" disabled>
                                            <label class="form-check-label" for="viewRoleFinance">
                                                FINANCE
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleHRMD" disabled>
                                            <label class="form-check-label" for="viewRoleHRMD">
                                                HRMD
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleHORFP" disabled>
                                            <label class="form-check-label" for="viewRoleHORFP">
                                                HO RFP
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="viewRoleTelecoms" disabled>
                                            <label class="form-check-label" for="viewRoleTelecoms">
                                                TELECOMS
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div id="noRolesMessage" class="text-danger mt-2" style="display: none;">
                                    <center>
                                        No Role Assign
                                    </center>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="fa fa-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <div class="row">
                            <!-- User Type Dropdown -->
                            <div class="col-md-6 mb-3">
                                <label for="editUserType" class="form-label">
                                    <i class="fa fa-user-tag me-1"></i>User Type
                                </label>
                                <div class="form-control-plaintext border rounded p-2 bg-light" id="editUserType" name="user_type">
                                    -
                                </div>
                            </div>
                            <!-- ID Number -->
                            <div class="col-md-6 mb-3">
                                <label for="editIdNumber" class="form-label">
                                    <i class="fa fa-id-card me-1"></i>ID Number
                                </label>
                                <div class="form-control-plaintext border rounded p-2 bg-light" id="editIdNumber" name="id_number">
                                    -
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Username -->
                            <div class="col-md-6 mb-3">
                                <label for="editUsername" class="form-label">
                                    <i class="fa fa-user me-1"></i>Username
                                </label>
                                <div class="form-control-plaintext border rounded p-2 bg-light" id="editUsername" name="username">
                                    -
                                </div>
                            </div>
                            <!-- Full Name Display -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fa fa-user me-1"></i>Full Name
                                </label>
                                <div class="form-control-plaintext border rounded p-2 bg-light" id="editFullNameDisplay">
                                    -
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Role -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">
                                    <i class="fa fa-user-shield me-1"></i>Role <span class="text-danger btn-text">*</span>
                                </label>
                                <div class="border rounded p-3 bg-light">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleSystem" name="roles[]" value="SYSTEM">
                                                <label class="form-check-label" for="editRoleSystem">
                                                    SYSTEM
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleMLWallet" name="roles[]" value="ML WALLET">
                                                <label class="form-check-label" for="editRoleMLWallet">
                                                    ML WALLET
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleCAD" name="roles[]" value="CAD">
                                                <label class="form-check-label" for="editRoleCAD">
                                                    CAD
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleMLFund" name="roles[]" value="ML FUND">
                                                <label class="form-check-label" for="editRoleMLFund">
                                                    ML FUND
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleKPDomestic" name="roles[]" value="KP DOMESTIC">
                                                <label class="form-check-label" for="editRoleKPDomestic">
                                                    KP DOMESTIC
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleFinance" name="roles[]" value="FINANCE">
                                                <label class="form-check-label" for="editRoleFinance">
                                                    FINANCE
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleHRMD" name="roles[]" value="HRMD">
                                                <label class="form-check-label" for="editRoleHRMD">
                                                    HRMD
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleHORFP" name="roles[]" value="HO RFP">
                                                <label class="form-check-label" for="editRoleHORFP">
                                                    HO RFP
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="editRoleTelecoms" name="roles[]" value="TELECOMS">
                                                <label class="form-check-label" for="editRoleTelecoms">
                                                    TELECOMS
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fa fa-info-circle me-1"></i>
                                            Select one or more roles for this user.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fa fa-info-circle me-1"></i>
                                    Fields marked with <span class="text-danger btn-text">*</span> are required.
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="updateUserBtn">
                        <i class="fa fa-save me-1"></i>Update User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let currentUserTypeFilter = '';
            
            // Initially disable buttons on page load
            $('#viewUserModal').prev().prop('disabled', true);
            $('#editUserModal').prev().prop('disabled', true);
            $('button[data-bs-target="#viewUserModal"]').prop('disabled', true);
            $('button[data-bs-target="#editUserModal"]').prop('disabled', true);
            
            // Search functionality
            $('#searchInput').on('keyup', function() {
                filterTable();
            });
            
            // User type filter functionality
            $('.dropdown-item').on('click', function(e) {
                e.preventDefault();
                const selectedValue = $(this).data('value');
                const selectedText = $(this).text();
                
                // Update dropdown button text
                $('#userTypeText').text(selectedText);
                
                // Update current filter
                currentUserTypeFilter = selectedValue;
                
                // Update active state
                $('.dropdown-item').removeClass('active');
                $(this).addClass('active');
                
                // Filter table
                filterTable();
            });
            
            // Clear filters functionality
            $('#clearFilters').on('click', function() {
                $('#searchInput').val('');
                currentUserTypeFilter = '';
                $('#userTypeText').text('User Type');
                $('.dropdown-item').removeClass('active');
                $('.dropdown-item[data-value=""]').addClass('active');
                
                // Clear row selection
                $('#users-table tbody tr').removeClass('selected');
                
                // Disable buttons
                disableButtons();
                
                filterTable();
            });
            
            // Function to enable buttons
            function enableButtons() {
                $('button[data-bs-target="#viewUserModal"]').prop('disabled', false);
                $('button[data-bs-target="#editUserModal"]').prop('disabled', false);
            }
            
            // Function to disable buttons
            function disableButtons() {
                $('button[data-bs-target="#viewUserModal"]').prop('disabled', true);
                $('button[data-bs-target="#editUserModal"]').prop('disabled', true);
            }
            
            // Main filter function
            function filterTable() {
                const searchTerm = $('#searchInput').val().toLowerCase();
                const table = $('#users-table tbody');
                const rows = table.find('tr');
                let visibleCount = 0;
                
                rows.each(function() {
                    const row = $(this);
                    const userData = row.data('user-data');
                    let showRow = true;
                    
                    // Skip if this is the "no users found" row
                    if (row.find('td').length === 1 && row.find('td').attr('colspan')) {
                        return;
                    }
                    
                    // Search filter
                    if (searchTerm) {
                        const searchableText = [
                            userData?.id_number || '',
                            userData?.username || '',
                            userData?.first_name || '',
                            userData?.middle_name || '',
                            userData?.last_name || '',
                            userData?.user_type || '',
                            userData?.roles || '',
                            userData?.status || '',
                            // Combine full name
                            `${userData?.first_name || ''} ${userData?.middle_name || ''} ${userData?.last_name || ''}`.trim(),
                            // Date modified (formatted)
                            row.find('td:eq(4)').text()
                        ].join(' ').toLowerCase();
                        
                        if (!searchableText.includes(searchTerm)) {
                            showRow = false;
                        }
                    }
                    
                    // User type filter
                    if (currentUserTypeFilter && userData?.user_type !== currentUserTypeFilter) {
                        showRow = false;
                    }
                    
                    // Show/hide row
                    if (showRow) {
                        row.show();
                        visibleCount++;
                    } else {
                        row.hide();
                        // If the row being hidden is selected, clear selection and disable buttons
                        if (row.hasClass('selected')) {
                            row.removeClass('selected');
                            disableButtons();
                        }
                    }
                });
                
                // Update table info and handle no results
                updateTableInfo(visibleCount);
            }
            
            // Update table information
            function updateTableInfo(visibleCount) {
                const table = $('#users-table');
                const tbody = table.find('tbody');
                
                // Remove existing info and no-results rows
                $('#table-info').remove();
                $('.no-results-row').remove();
                
                if (visibleCount === 0) {
                    // Show no results message
                    const noResultsRow = `
                        <tr class="no-results-row no-results">
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fa fa-search fa-2x mb-2"></i><br>
                                No users found matching your search criteria
                            </td>
                        </tr>
                    `;
                    tbody.append(noResultsRow);
                    
                    // Disable buttons when no results
                    disableButtons();
                }
            }
            
            // Row selection functionality
            $('#users-table tbody').on('click', 'tr', function() {
                // Skip if this is the no-results row
                if ($(this).hasClass('no-results-row')) {
                    return;
                }
                
                // Remove previous selection
                $('#users-table tbody tr').removeClass('selected');
                
                // Add selection to clicked row
                $(this).addClass('selected');
                
                // Enable buttons when row is selected
                enableButtons();
                
                // Store selected user data globally
                window.selectedUserData = $(this).data('user-data');
            });
            
            // SEPARATE FUNCTION FOR VIEW MODAL - Triggered when View button is clicked
            $('button[data-bs-target="#viewUserModal"]').on('click', function() {
                if (window.selectedUserData) {
                    populateViewModal(window.selectedUserData);
                }
            });
            
            // SEPARATE FUNCTION FOR EDIT MODAL - Triggered when Edit button is clicked
            $('button[data-bs-target="#editUserModal"]').on('click', function() {
                if (window.selectedUserData) {
                    populateEditModal(window.selectedUserData);
                }
            });
            
            // Function to populate VIEW modal with user data
            function populateViewModal(userData) {
                // Display user type with badge
                $('#viewUserType').html(userData.user_type || '-');
                
                // Display other user information
                $('#viewIdNumber').text(userData.id_number || '-');
                $('#viewUsername').text(userData.username || '-');
                
                // Display full name
                const fullName = [userData.first_name, userData.middle_name, userData.last_name]
                    .filter(name => name && name.trim() !== '')
                    .join(' ');
                $('#viewFullName').text(fullName || '-');
                
                // Handle roles checkboxes for view modal
                populateViewRoleCheckboxes(userData.roles);
            }
            
            // Function to populate EDIT modal with user data
            function populateEditModal(userData) {
                $('#editIdNumber').text(userData.id_number || '');
                $('#editUsername').text(userData.username || '');
                $('#editUserType').text(userData.user_type || '');
                
                // Display full name in edit modal (read-only)
                const fullName = [userData.first_name, userData.middle_name, userData.last_name]
                    .filter(name => name && name.trim() !== '')
                    .join(' ');
                $('#editFullNameDisplay').text(fullName || '-');
                
                // Populate edit modal role checkboxes
                populateEditRoleCheckboxes(userData.roles);
            }
            
            // Function to populate VIEW role checkboxes (separate from edit)
            function populateViewRoleCheckboxes(roles) {
                // Clear all VIEW checkboxes first
                $('#viewRoleSystem, #viewRoleMLWallet, #viewRoleMLFund, #viewRoleKPDomestic, #viewRoleHRMD, #viewRoleHORFP, #viewRoleCAD, #viewRoleFinance, #viewRoleTelecoms').prop('checked', false);
                
                if (roles && roles.trim() !== '') {
                    // Hide no roles message
                    $('#noRolesMessage').hide();
                    
                    // Split roles by comma and trim whitespace
                    const userRoles = roles.split(',').map(role => role.trim().toUpperCase());
                    
                    // Check corresponding VIEW checkboxes
                    userRoles.forEach(role => {
                        switch(role) {
                            case 'SYSTEM':
                                $('#viewRoleSystem').prop('checked', true);
                                break;
                            case 'ML WALLET':
                                $('#viewRoleMLWallet').prop('checked', true);
                                break;
                            case 'ML FUND':
                                $('#viewRoleMLFund').prop('checked', true);
                                break;
                            case 'KP DOMESTIC':
                                $('#viewRoleKPDomestic').prop('checked', true);
                                break;
                            case 'HRMD':
                                $('#viewRoleHRMD').prop('checked', true);
                                break;
                            case 'HO RFP':
                                $('#viewRoleHORFP').prop('checked', true);
                                break;
                            case 'CAD':
                                $('#viewRoleCAD').prop('checked', true);
                                break;
                            case 'FINANCE':
                                $('#viewRoleFinance').prop('checked', true);
                                break;
                            case 'TELECOMS':
                                $('#viewRoleTelecoms').prop('checked', true);
                                break;
                        }
                    });
                } else {
                    // Show no roles message if no roles assigned
                    $('#noRolesMessage').show();
                }
            }
            
            // Function to populate EDIT role checkboxes (separate from view)
            function populateEditRoleCheckboxes(roles) {
                // Clear all EDIT checkboxes first
                $('#editRoleSystem, #editRoleMLWallet, #editRoleMLFund, #editRoleKPDomestic, #editRoleHRMD, #editRoleHORFP, #editRoleCAD, #editRoleFinance, #editRoleTelecoms').prop('checked', false);
                
                if (roles && roles.trim() !== '') {
                    // Split roles by comma and trim whitespace
                    const userRoles = roles.split(',').map(role => role.trim().toUpperCase());
                    
                    // Check corresponding EDIT checkboxes
                    userRoles.forEach(role => {
                        switch(role) {
                            case 'SYSTEM':
                                $('#editRoleSystem').prop('checked', true);
                                break;
                            case 'ML WALLET':
                                $('#editRoleMLWallet').prop('checked', true);
                                break;
                            case 'ML FUND':
                                $('#editRoleMLFund').prop('checked', true);
                                break;
                            case 'KP DOMESTIC':
                                $('#editRoleKPDomestic').prop('checked', true);
                                break;
                            case 'HRMD':
                                $('#editRoleHRMD').prop('checked', true);
                                break;
                            case 'HO RFP':
                                $('#editRoleHORFP').prop('checked', true);
                                break;
                            case 'CAD':
                                $('#editRoleCAD').prop('checked', true);
                                break;
                            case 'FINANCE':
                                $('#editRoleFinance').prop('checked', true);
                                break;
                            case 'TELECOMS':
                                $('#editRoleTelecoms').prop('checked', true);
                                break;
                        }
                    });
                }
            }
            
            // Update full name display when name fields change in edit modal
            $('#editFirstName, #editMiddleName, #editLastName').on('input', function() {
                updateEditFullNameDisplay();
            });

            // Handle update user button click
            $('#updateUserBtn').on('click', function() {
                // Get selected roles
                const selectedRoles = [];
                $('input[name="roles[]"]:checked').each(function() {
                    selectedRoles.push($(this).val());
                });
                
                // Validate that at least one role is selected
                if (selectedRoles.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Role Selected',
                        text: 'Please select at least one role for the user.',
                        confirmButtonColor: '#dc3545',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: false,
                        allowOutsideClick: false
                    });
                    return;
                }
                
                // Get form data - use .text() instead of .val() for div elements
                const formData = {
                    id_number: $('#editIdNumber').text(),
                    username: $('#editUsername').text(),
                    user_type: $('#editUserType').text(),
                    roles: selectedRoles.join(',')
                };
                
                // Validate required fields - check for '-' which indicates empty
                if (!formData.id_number || formData.id_number === '-' || 
                    !formData.username || formData.username === '-' || 
                    !formData.user_type || formData.user_type === '-') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Required Fields',
                        text: 'Please select a valid user first.',
                        confirmButtonColor: '#dc3545',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: false,
                        allowOutsideClick: false
                    });
                    return;
                }
                
                console.log('Form data to submit:', formData);
                
                // Show confirmation dialog before updating
                Swal.fire({
                    title: 'Confirm Update',
                    text: `Are you sure you want to update roles for ${formData.username}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Update',
                    cancelButtonText: 'Cancel',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Disable update button to prevent double submission
                        $('#updateUserBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Updating...');
                        
                        // Send AJAX request to update user roles
                        $.ajax({
                            url: '../../models/updated/updated-user-role.php',
                            method: 'POST',
                            data: formData,
                            dataType: 'json',
                            success: function(response) {
                                // Re-enable button
                                $('#updateUserBtn').prop('disabled', false).html('<i class="fa fa-save me-1"></i>Update User');
                                
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'User Updated Successfully',
                                        text: response.message,
                                        confirmButtonColor: '#dc3545',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: false,
                                        allowOutsideClick: false
                                    }).then(() => {
                                        // Close modal
                                        $('#editUserModal').modal('hide');
                                        
                                        // Update the table row with new roles data immediately
                                        updateTableRowRoles(formData.id_number, formData.username, formData.roles, response.user_data);
                                        
                                        // Clear selection and disable buttons
                                        $('#users-table tbody tr').removeClass('selected');
                                        disableButtons();
                                        
                                        // Show success notification (optional)
                                        showNotification('Table updated successfully!', 'success');
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Update Failed',
                                        text: response.message || 'An error occurred while updating the user.',
                                        confirmButtonColor: '#dc3545',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: false,
                                        allowOutsideClick: false
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                // Re-enable button
                                $('#updateUserBtn').prop('disabled', false).html('<i class="fa fa-save me-1"></i>Update User');
                                
                                console.error('AJAX Error:', xhr.responseText);
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Update Failed',
                                    text: 'An error occurred while updating the user: ' + error,
                                    confirmButtonColor: '#dc3545',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: false,
                                    allowOutsideClick: false
                                });
                            }
                        });
                    }
                });
            });
            // Function to update table row with new roles (enhanced to update immediately)
            function updateTableRowRoles(idNumber, username, newRoles, updatedUserData) {
                $('#users-table tbody tr').each(function() {
                    const userData = $(this).data('user-data');
                    if (userData && userData.id_number === idNumber && userData.username === username) {
                        // Update the roles column in the table (7th column, index 6)
                        const rolesCell = $(this).find('td:eq(6)');
                        if (newRoles && newRoles.trim() !== '') {
                            rolesCell.html(newRoles);
                        } else {
                            rolesCell.html('<span class="text-danger">No Role Assign</span>');
                        }
                        
                        // Update the date modified column (5th column, index 4)
                        if (updatedUserData && updatedUserData.date_modified) {
                            const dateModifiedCell = $(this).find('td:eq(4)');
                            const formattedDate = new Date(updatedUserData.date_modified).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            });
                            dateModifiedCell.text(formattedDate);
                        }
                        
                        // Update the stored user data
                        userData.roles = newRoles;
                        if (updatedUserData) {
                            userData.date_modified = updatedUserData.date_modified;
                            userData.modified_by = updatedUserData.modified_by;
                        }
                        $(this).data('user-data', userData);
                        
                        // Update global selected user data if this is the selected row
                        if ($(this).hasClass('selected')) {
                            window.selectedUserData = userData;
                        }
                        
                        // Add a brief highlight effect to show the row was updated
                        $(this).addClass('table-success');
                        setTimeout(() => {
                            $(this).removeClass('table-success');
                        }, 2000);
                        
                        return false; // Break out of each loop once found
                    }
                });
            }
        });
        
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