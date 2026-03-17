<?php
/**
 * User Login Handler
 * user/login_process.php
 * 
 * Handles user login with session management
 */

session_start();

require_once '../config/database.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If validation passes, check credentials
    if (empty($errors)) {
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare('SELECT id, first_name, last_name, email, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                
                // Password is correct, set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $response['success'] = true;
                $response['message'] = 'Login successful!';
                
            } else {
                $errors['password'] = 'Incorrect password';
            }
        } else {
            $errors['email'] = 'Email not found';
        }
        
        $stmt->close();
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Login failed. Please check your credentials.';
    }
    
    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

$conn->close();

// If not AJAX, redirect
if ($response['success']) {
    header('Location: ../index.php');
} else {
    header('Location: login.php?error=1');
}
exit;

?>