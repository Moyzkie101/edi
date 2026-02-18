<?php
    include '../../../config/connection.php';
    require '../../../vendor/autoload.php';

    session_start();

    if (!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'user')) {
        header('location: ' . $auth_url . 'logout.php');
        session_destroy();
        exit();
    }else{
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
                    // Handle SYSTEM role - no access to this page
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
                    // Handle ML FUND role - allow access to this page
                    $hasRequiredRole = true;
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
                    // Handle unknown role - no access
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

    // Fetch users from database using MySQLi
    $data_raw = [];
    try {
    $query = "SELECT payroll_date, mainzone, zone, region_code, region, employee_id_no, employee_name, ml_fund_amount FROM " . $database[0] . ".mlfund_payroll ORDER BY payroll_date";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data_raw[] = $row;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E D I</title>
    <link rel="icon" href="<?php echo $relative_path; ?>assets/picture/MLW Logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/admin/default/default.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Add Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .print-btn {
            background-color: #d70c0c;
            border: none;
            color: white;
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .print-btn:hover {
            background-color: #423e3d; 
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

        .import-file {
            /* background-color: #3262e6; */
            height: 100px;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        select {
            width: 200px;
            padding: 10px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 15px;
            background-color: #f9f9f9;
            -webkit-appearance: none; /* Remove default arrow in WebKit browsers */
            -moz-appearance: none; /* Remove default arrow in Firefox */
            appearance: none; /* Remove default arrow in most modern browsers */
            color: #F14A51;
        }
        .custom-select-wrapper {
            position: relative;
            display: inline-block;
            margin-left: 20px;
            color: #000000;
        }
        .custom-arrow {
            position: absolute;
            top: 50%;
            right: 10px;
            width: 0;
            height: 0;
            padding: 0;
            margin-top: -2px;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid #333;
            pointer-events: none;
        }
        .generate-btn {
            background-color: #db120b; 
            border: none;
            color: white;
            padding: 13px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin-left: 30px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .generate-btn:hover{
            background-color:rgb(180, 31, 31);
        }

        .download-btn {
            background-color: #4fc917; 
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
        }
        .post-btn {
            background-color: #4fc917; 
            border: none;
            color: white;
            padding: 9px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            border-radius: 20px;
            margin: 5px;
        }

        .filter-active {
            border-color: #db120b !important;
            box-shadow: 0 0 5px rgba(219, 18, 11, 0.3);
        }
        
        .filter-badge {
            background-color: #db120b;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 5px;
        }

        /* Fix table header z-index to not interfere with sidebar */
        .table thead th {
            z-index: 10 !important;
            position: sticky;
            top: 0;
        }
        
        .table-responsive {
            position: relative;
            z-index: 1;
        }
        
        /* Ensure sidebar has higher z-index */
        .dropdown-content {
            z-index: 9999 !important;
        }
        
        .nav-container {
            z-index: 9998 !important;
        }
        
        .top-content {
            z-index: 9997 !important;
            position: relative;
        }

        /* Custom pagination styles */
        #paginationControls {
            display: flex;
            align-items: center;
        }

        #pageNumbers {
            display: flex;
            align-items: center;
            margin: 0;
        }

        #pageNumbers .page-item {
            list-style: none;
            margin: 0;
        }

        #pageNumbers .page-link {
            display: block;
            padding: 0.375rem 0.75rem;
            margin-left: -1px;
            color: #0d6efd;
            text-decoration: none;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        #pageNumbers .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        #pageNumbers .page-link:hover {
            z-index: 2;
            color: #0a58ca;
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
    </style>
</head>

<body>
    <div class="top-content">
        <?php include '../../../templates/sidebar.php' ?>
    </div>
    <center>
        <h2>ML FUND ID NO. REPORT<span>[DEDUCTION]</span></h2>
    </center>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-18">
                <div class="card">
                    <div class="card-header">
                        <div class="row g-3 align-items-end">
                            <!-- From Date -->
                            <div class="col-md-2">
                                <label for="start_date" class="form-label small text-muted">From Date:</label>
                                <input type="date" 
                                    id="start_date" 
                                    name="start_date" 
                                    class="form-control form-control-sm" 
                                    required 
                                    max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <!-- To Date -->
                            <div class="col-md-2">
                                <label for="end_date" class="form-label small text-muted">To Date:</label>
                                <input type="date" 
                                    id="end_date" 
                                    name="end_date" 
                                    class="form-control form-control-sm" 
                                    required 
                                    max="<?php echo date('Y-m-d'); ?>">
                            </div>
                    
                            <!-- Search Input -->
                            <div class="col-md-4">
                                <label for="search_input" class="form-label small text-muted">Search:</label>
                                <input type="text" 
                                    id="search_input" 
                                    name="search" 
                                    class="form-control form-control-sm" 
                                    placeholder="Search by ID Number and Employee Name">
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-md-2">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" id="clearFilters" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex align-items-center">
                                <label class="h6 text-muted mb-0 me-2">Total Amount:</label>
                                <span class="h5 mb-0 text-success fw-bold d-flex align-items-center">
                                    <span class="h5 mb-0">₱</span>
                                    <span id="total-amount" class="ms-1 mb-0 h5">
                                        <span class="placeholder placeholder-glow col-6"></span>
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
                            <table class="table table-hover table-striped" id="users-table" style="min-width: 1800px;">
                                <thead class="table-light sticky-top">
                                    <tr class="text-center">
                                        <th>Date</th>
                                        <th>Employee ID</th>
                                        <th>Employee Name</th>
                                        <th>Amount</th>
                                        <th>Zone</th>
                                        <th>Region</th>
                                    </tr>
                                </thead>
                                <tbody id="table-body">
                                    <!-- Loading placeholder rows -->
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-start"><span class="placeholder col-10"></span></td>
                                        <td class="text-end"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-start"><span class="placeholder col-9"></span></td>
                                        <td class="text-end"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-4"></span></td>
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                        <td class="text-start"><span class="placeholder col-11"></span></td>
                                        <td class="text-end"><span class="placeholder col-8"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                        <td class="text-start"><span class="placeholder col-10"></span></td>
                                        <td class="text-end"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-start"><span class="placeholder col-9"></span></td>
                                        <td class="text-end"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-start"><span class="placeholder col-10"></span></td>
                                        <td class="text-end"><span class="placeholder col-5"></span></td>
                                        <td class="text-center"><span class="placeholder col-4"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                        <td class="text-start"><span class="placeholder col-11"></span></td>
                                        <td class="text-end"><span class="placeholder col-8"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-start"><span class="placeholder col-9"></span></td>
                                        <td class="text-end"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-start"><span class="placeholder col-10"></span></td>
                                        <td class="text-end"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-4"></span></td>
                                        <td class="text-center"><span class="placeholder col-7"></span></td>
                                    </tr>
                                    <tr class="placeholder-glow loading-row">
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                        <td class="text-center"><span class="placeholder col-5"></span></td>
                                        <td class="text-start"><span class="placeholder col-11"></span></td>
                                        <td class="text-end"><span class="placeholder col-7"></span></td>
                                        <td class="text-center"><span class="placeholder col-6"></span></td>
                                        <td class="text-center"><span class="placeholder col-8"></span></td>
                                    </tr>
                                    
                                    <!-- Actual data rows (hidden initially) -->
                                    <?php if (!empty($data_raw)): ?>
                                        <?php foreach ($data_raw as $index => $raw): ?>
                                            <tr class="data-row" style="display: none;">
                                                <td class="text-center"><?php echo htmlspecialchars( date('F d, Y', strtotime($raw['payroll_date']))); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($raw['employee_id_no'] ?? ''); ?></td>
                                                <td class="text-start"><?php echo htmlspecialchars($raw['employee_name'] ?? ''); ?></td>
                                                <td class="text-end"><?php echo htmlspecialchars(number_format($raw['ml_fund_amount'], 2)); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($raw['zone'] ?? ''); ?></td>
                                                <td class="text-center"><?php echo htmlspecialchars($raw['region'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="data-row" style="display: none;">
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No records found in the database
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination Controls -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="d-flex align-items-center">
                                <label for="recordsPerPage" class="form-label me-2 small text-muted">Show:</label>
                                <select id="recordsPerPage" class="form-select form-select-sm" style="width: auto;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="all">All</option>
                                </select>
                                <span class="ms-2 small text-muted">entries</span>
                            </div>
                            
                            <div class="small text-muted" id="paginationInfo">
                                Showing <span id="startRecord" class="fs-6">1</span> to <span id="endRecord" class="fs-6">10</span> of <span id="totalRecords" class="fs-6">0</span> entries
                            </div>
                            
                            <nav aria-label="Table pagination">
                                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                    <li class="page-item" id="prevPage">
                                        <a class="page-link" href="#" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <!-- Page numbers container -->
                                    <div id="pageNumbers" class="d-flex">
                                        <!-- Page numbers will be inserted here by JavaScript -->
                                    </div>
                                    <li class="page-item" id="nextPage">
                                        <a class="page-link" href="#" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../../assets/js/admin/report-file/deduction-format/script2.js"></script>
</body>

</html>