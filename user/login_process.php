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

        // Select using actual DB columns: full_name, user_type, is_active
        $stmt = $conn->prepare(
            'SELECT id, full_name, email, password, user_type FROM users WHERE email = ? AND is_active = 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $user_id = $user['id'];

                // Link any guest orders placed with this email to this account
                $link_stmt = $conn->prepare(
                    'UPDATE orders SET user_id = ? WHERE email = ? AND (user_id IS NULL OR user_id = 0)'
                );
                $link_stmt->bind_param('is', $user_id, $email);
                $link_stmt->execute();
                $link_stmt->close();

                // Split full_name for display
                $name_parts = explode(' ', $user['full_name'], 2);
                $first_name = $name_parts[0];
                $last_name  = $name_parts[1] ?? '';

                // Set session variables
                $_SESSION['user_id']    = $user_id;
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name']  = $last_name;
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['user_type'];

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