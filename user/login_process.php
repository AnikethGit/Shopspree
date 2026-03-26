<?php
/**
 * User Login Handler
 * user/login_process.php
 */

session_start();

require_once '../config/database.php';

$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {

        // Select using actual DB columns: full_name, user_type
        $stmt = $conn->prepare(
            'SELECT id, full_name, email, password, user_type FROM users WHERE email = ? AND is_active = 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // Split full_name into first/last for display in navbar
                $name_parts = explode(' ', $user['full_name'], 2);
                $first_name = $name_parts[0];
                $last_name  = $name_parts[1] ?? '';

                // Set session variables
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['first_name'] = $first_name;   // used in navbar greeting
                $_SESSION['last_name']  = $last_name;
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['user_type']; // map user_type -> role

                $response['success'] = true;
                $response['message'] = 'Login successful!';

            } else {
                $errors['password'] = 'Incorrect password';
            }
        } else {
            $errors['email'] = 'Email not found or account inactive';
        }

        $stmt->close();
    }

    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Login failed. Please check your credentials.';
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

if ($response['success']) {
    header('Location: ../index.php');
} else {
    header('Location: login.php?error=1');
}
exit;
?>