<?php
/**
 * User Registration Handler
 * user/register_process.php
 */

session_start();

require_once '../config/database.php';

$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name      = trim($_POST['first_name'] ?? '');
    $last_name       = trim($_POST['last_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Combine into full_name to match DB column
    $full_name = trim($first_name . ' ' . $last_name);

    $errors = [];

    if (empty($first_name) || strlen($first_name) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters';
    }
    if (empty($last_name) || strlen($last_name) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required';
    }
    if (!empty($phone) && !preg_match('/^[0-9\+\-\(\)\s]{10,}$/', $phone)) {
        $errors['phone'] = 'Invalid phone number format';
    }
    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors['email'] = 'Email already registered';
        }
        $stmt->close();
    }

    // Insert user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'customer'; // maps to DB column user_type

        $stmt = $conn->prepare(
            'INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $email, $hashed_password, $full_name, $phone, $user_type);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Registration successful! You can now login.';
        } else {
            $response['message'] = 'Registration failed. Please try again.';
            error_log('Register error: ' . $conn->error);
        }
        $stmt->close();
    } else {
        $response['errors'] = $errors;
        $response['message'] = 'Please fix the errors below';
    }

    // AJAX response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

$conn->close();

header('Location: register.php?status=' . ($response['success'] ? 'success' : 'error'));
exit;
?>