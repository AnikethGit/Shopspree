<?php
/**
 * User Registration Handler
 * user/register_process.php
 * 
 * Handles user registration with validation and security
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
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    // First name validation
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    } elseif (strlen($first_name) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters';
    }
    
    // Last name validation
    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    } elseif (strlen($last_name) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters';
    }
    
    // Email validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Phone validation (optional but if provided, validate)
    if (!empty($phone)) {
        if (!preg_match('/^[0-9\+\-\(\)\s]{10,}$/', $phone)) {
            $errors['phone'] = 'Invalid phone number format';
        }
    }
    
    // Password validation
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    // Confirm password validation
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        
        // Check if email already exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $stmt->close();
    }
    
    // If still no errors, insert user
    if (empty($errors)) {
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare('INSERT INTO users (first_name, last_name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssssss', $first_name, $last_name, $email, $phone, $hashed_password, $role);
        
        $role = 'customer'; // Default role
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Registration successful! You can now login.';
            
            // Clear form data on success
            $first_name = '';
            $last_name = '';
            $email = '';
            $phone = '';
            $password = '';
            $confirm_password = '';
        } else {
            $response['message'] = 'Registration failed. Please try again.';
            $errors['general'] = $conn->error;
        }
        $stmt->close();
    } else {
        $response['errors'] = $errors;
        $response['message'] = 'Please fix the errors below';
    }
    
    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Close connection
$conn->close();

// If not AJAX, redirect back to form
header('Location: register.php?status=' . ($response['success'] ? 'success' : 'error'));
exit;

?>