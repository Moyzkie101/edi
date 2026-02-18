<?php
include '../../config/connection.php';

session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] === 'user') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required_fields = ['user_type', 'id_number', 'first_name', 'last_name', 'username'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                exit;
            }
        }
        
        // Sanitize inputs
        $id_number = mysqli_real_escape_string($conn, trim($input['id_number']));
        $first_name = mysqli_real_escape_string($conn, trim($input['first_name']));
        $middle_name = mysqli_real_escape_string($conn, trim($input['middle_name'] ?? ''));
        $last_name = mysqli_real_escape_string($conn, trim($input['last_name']));
        $username = mysqli_real_escape_string($conn, trim($input['username']));
        $user_type = mysqli_real_escape_string($conn, trim($input['user_type']));
        $password = md5('Mlinc1234'); // Hash the default password
        $status = 'Active'; // Default status
        $created_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'System';
        $date_created = date('Y-m-d H:i:s');
        
        // Check if ID number already exists
        $check_query = "SELECT id_number FROM " . $database[0] . ".user WHERE id_number = '$id_number'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'ID Number already exists']);
            exit;
        }
        
        // Check if email/username already exists
        $check_email_query = "SELECT email FROM " . $database[0] . ".user WHERE email = '$username'";
        $check_email_result = mysqli_query($conn, $check_email_query);
        
        if (mysqli_num_rows($check_email_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Username/Email already exists']);
            exit;
        }
        
        // Insert new user
        $insert_query = "INSERT INTO " . $database[0] . ".user 
                        (id_number, first_name, middle_name, last_name, email, password, user_type, status, date_created, created_by) 
                        VALUES 
                        ('$id_number', '$first_name', '$middle_name', '$last_name', '$username', '$password', '$user_type', '$status', '$date_created', '$created_by')";
        
        if (mysqli_query($conn, $insert_query)) {
            // Get the newly created user data
            $new_user_query = "SELECT id_number, first_name, middle_name, last_name, email as username, user_type, status, last_online, date_created FROM " . $database[0] . ".user WHERE id_number = '$id_number'";
            $new_user_result = mysqli_query($conn, $new_user_query);
            $new_user_data = mysqli_fetch_assoc($new_user_result);
            
            echo json_encode([
                'success' => true, 
                'message' => 'User created successfully',
                'user_data' => $new_user_data
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

mysqli_close($conn);
?>