<?php
/**
 * User Logout Handler
 * user/logout.php
 * 
 * Handles user logout and session termination
 */

session_start();

// Destroy session
$_SESSION = [];
session_destroy();

// Clear session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Redirect to homepage
header('Location: ../index.php');
exit;

?>