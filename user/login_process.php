<?php
require_once __DIR__ . '/../config/db.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

// ── Session-based login rate limiting ──────────────────────────────────────
$_SESSION['login_attempts']     = $_SESSION['login_attempts']     ?? 0;
$_SESSION['login_last_attempt'] = $_SESSION['login_last_attempt'] ?? 0;
$max_attempts = 5;
$lockout_secs = 900; // 15 minutes

if ($_SESSION['login_attempts'] >= $max_attempts) {
    $elapsed = time() - $_SESSION['login_last_attempt'];
    if ($elapsed < $lockout_secs) {
        $mins_left = (int)ceil(($lockout_secs - $elapsed) / 60);
        $msg = "Too many failed login attempts. Please try again in {$mins_left} minute(s).";
        $is_ajax_rl = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($is_ajax_rl) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $msg, 'errors' => ['auth' => $msg]]);
            exit;
        }
        add_message($msg, 'error');
        redirect('login.php?error=1');
    }
    $_SESSION['login_attempts'] = 0; // lockout expired — reset
}
// ───────────────────────────────────────────────────────────────────────────

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$errors   = [];

if (empty($email)) {
    $errors['email'] = 'Email is required';
}
if (empty($password)) {
    $errors['password'] = 'Password is required';
}

if (empty($errors)) {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, password, user_type FROM users WHERE email = ? AND is_active = 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $user_id = $user['id'];

        // Prevent session fixation on privilege change
        session_regenerate_id(true);

        // Link any guest orders placed with this email
        $pdo->prepare('UPDATE orders SET user_id = ? WHERE email = ? AND (user_id IS NULL OR user_id = 0)')
            ->execute([$user_id, $email]);

        $name_parts = explode(' ', $user['full_name'], 2);

        $_SESSION['user_id']    = $user_id;
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['first_name'] = $name_parts[0];
        $_SESSION['last_name']  = $name_parts[1] ?? '';
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['user_type'];

        // Reset rate limiting on success
        $_SESSION['login_attempts'] = 0;

        $response['success'] = true;
        $response['message'] = 'Login successful!';
    } else {
        // Track failed attempt
        $_SESSION['login_attempts']++;
        $_SESSION['login_last_attempt'] = time();

        $errors['auth'] = 'Invalid email or password.';
        $response['message'] = 'Invalid email or password.';
    }
}

if (!empty($errors)) {
    $response['errors'] = $errors;
}

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if ($response['success']) {
    redirect('../index.php');
} else {
    redirect('login.php?error=1');
}
