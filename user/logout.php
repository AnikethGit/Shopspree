<?php
/**
 * User Logout Handler
 * user/logout.php
 * 
 * Handles user logout and session termination
 */

session_start();

// Destroy session
session_destroy();

// Clear cookies if any
setcookie('PHPSESSID', '', time() - 3600, '/');

// Redirect to login page
header('Location: login.php?logout=1');
exit;

?>