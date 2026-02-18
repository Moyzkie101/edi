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
        $required_fields = ['original_id_number', 'user_type', 'id_number', 'first_name', 'last_name', 'username'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                exit;
            }
        }
        
        // Sanitize inputs
        $original_id_number = mysqli_real_escape_string($conn, trim($input['original_id_number']));
        $id_number = mysqli_real_escape_string($conn, trim($input['id_number']));
        $first_name = mysqli_real_escape_string($conn, trim($input['first_name']));
        $middle_name = mysqli_real_escape_string($conn, trim($input['middle_name'] ?? ''));
        $last_name = mysqli_real_escape_string($conn, trim($input['last_name']));
        $username = mysqli_real_escape_string($conn, trim($input['username']));
        $user_type = mysqli_real_escape_string($conn, trim($input['user_type']));
        $modified_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'System';
        $date_modified = date('Y-m-d H:i:s');
        
        // Check if ID number already exists (excluding current user)
        if ($id_number !== $original_id_number) {
            $check_query = "SELECT id_number FROM " . $database[0] . ".user WHERE id_number = '$id_number'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                echo json_encode(['success' => false, 'message' => 'ID Number already exists']);
                exit;
            }
        }
        
        // Check if email/username already exists (excluding current user)
        $check_email_query = "SELECT email FROM " . $database[0] . ".user WHERE email = '$username' AND id_number != '$original_id_number'";
        $check_email_result = mysqli_query($conn, $check_email_query);
        
        if (mysqli_num_rows($check_email_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'Username/Email already exists']);
            exit;
        }
        
        // Update user
        $update_query = "UPDATE " . $database[0] . ".user 
                        SET id_number = '$id_number',
                            first_name = '$first_name',
                            middle_name = '$middle_name',
                            last_name = '$last_name',
                            email = '$username',
                            user_type = '$user_type',
                            date_modified = '$date_modified',
                            modified_by = '$modified_by'
                        WHERE id_number = '$original_id_number'";
        
        if (mysqli_query($conn, $update_query)) {
            // Get the updated user data
            $updated_user_query = "SELECT id_number, first_name, middle_name, last_name, email as username, user_type, status, last_online, date_created, date_modified FROM " . $database[0] . ".user WHERE id_number = '$id_number'";
            $updated_user_result = mysqli_query($conn, $updated_user_query);
            $updated_user_data = mysqli_fetch_assoc($updated_user_result);
            
            echo json_encode([
                'success' => true, 
                'message' => 'User updated successfully',
                'user_data' => $updated_user_data
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