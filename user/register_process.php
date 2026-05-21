<?php
require_once __DIR__ . '/../config/db.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

$first_name       = trim($_POST['first_name'] ?? '');
$last_name        = trim($_POST['last_name'] ?? '');
$email            = trim($_POST['email'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$full_name        = trim($first_name . ' ' . $last_name);

$errors = [];

if (empty($first_name) || strlen($first_name) < 2) {
    $errors['first_name'] = 'First name must be at least 2 characters';
}
if (empty($last_name) || strlen($last_name) < 2) {
    $errors['last_name'] = 'Last name must be at least 2 characters';
}
if (empty($email) || !is_valid_email($email)) {
    $errors['email'] = 'Valid email is required';
}
if (!empty($phone) && !preg_match('/^[0-9\+\-\(\)\s]{10,}$/', $phone)) {
    $errors['phone'] = 'Invalid phone number format';
}
if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $errors['password'] = 'Password must contain letters and numbers';
}
if ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match';
}

if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors['email'] = 'Email already registered';
    }
}

if (empty($errors)) {
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password, full_name, phone, user_type) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$email, hash_password($password), $full_name, $phone, 'customer']);
    $new_user_id = (int)$pdo->lastInsertId();

    // Link any guest orders placed with this email
    $pdo->prepare('UPDATE orders SET user_id = ? WHERE email = ? AND (user_id IS NULL OR user_id = 0)')
        ->execute([$new_user_id, $email]);

    session_regenerate_id(true);
    $response['success'] = true;
    $response['message'] = 'Registration successful! You can now log in.';
} else {
    $response['errors']  = $errors;
    $response['message'] = 'Please fix the errors below.';
}

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

header('Location: register.php?status=' . ($response['success'] ? 'success' : 'error'));
exit;
