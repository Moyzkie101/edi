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
        $required_fields = ['id_number', 'username'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                exit;
            }
        }
        
        // Sanitize inputs
        $id_number = mysqli_real_escape_string($conn, trim($input['id_number']));
        $username = mysqli_real_escape_string($conn, trim($input['username']));
        $new_password = md5('Mlinc1234'); // Hash the default password
        $modified_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'System';
        $date_modified = date('Y-m-d H:i:s');
        
        // Check if user exists
        $check_query = "SELECT id_number, email FROM " . $database[0] . ".user WHERE id_number = '$id_number' AND email = '$username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        // Update user password
        $update_query = "UPDATE " . $database[0] . ".user 
                        SET password = '$new_password',
                            date_modified = '$date_modified',
                            modified_by = '$modified_by'
                        WHERE id_number = '$id_number' AND email = '$username'";
        
        if (mysqli_query($conn, $update_query)) {
            // Check if any rows were affected
            if (mysqli_affected_rows($conn) > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Password has been reset successfully to default: Mlinc1234',
                    'username' => $username
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes were made to the password']);
            }
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