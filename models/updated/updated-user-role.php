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
        // Get POST input (since we're sending form data, not JSON)
        $input = $_POST;
        
        // Validate required fields
        $required_fields = ['id_number', 'username', 'roles'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
                exit;
            }
        }
        
        // Sanitize inputs
        $id_number = mysqli_real_escape_string($conn, trim($input['id_number']));
        $username = mysqli_real_escape_string($conn, trim($input['username']));
        $roles = mysqli_real_escape_string($conn, trim($input['roles']));
        $modified_by = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'System';
        $date_modified = date('Y-m-d H:i:s');
        
        // Validate that the user exists with both id_number and email (username)
        $check_user_query = "SELECT id_number, email FROM " . $database[0] . ".user WHERE id_number = '$id_number' AND email = '$username'";
        $check_user_result = mysqli_query($conn, $check_user_query);
        
        if (mysqli_num_rows($check_user_result) === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found with the provided ID Number and Username']);
            exit;
        }
        
        // Update user roles
        $update_query = "UPDATE " . $database[0] . ".user 
                        SET roles = '$roles',
                            date_modified = '$date_modified',
                            modified_by = '$modified_by'
                        WHERE id_number = '$id_number' AND email = '$username'";
        
        if (mysqli_query($conn, $update_query)) {
            // Check if any rows were affected
            $affected_rows = mysqli_affected_rows($conn);
            
            if ($affected_rows > 0) {
                // Get the updated user data
                $updated_user_query = "SELECT id_number, first_name, middle_name, last_name, email as username, user_type, status, roles, date_modified, modified_by FROM " . $database[0] . ".user WHERE id_number = '$id_number' AND email = '$username'";
                $updated_user_result = mysqli_query($conn, $updated_user_query);
                $updated_user_data = mysqli_fetch_assoc($updated_user_result);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'User roles updated successfully',
                    'user_data' => $updated_user_data,
                    'updated_roles' => $roles,
                    'affected_rows' => $affected_rows
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes were made to the user roles']);
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