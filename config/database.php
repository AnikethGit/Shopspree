<?php
/**
 * Database Configuration File
 * config/database.php
 */

// Database credentials
define('DB_HOST', 'localhost');              // Usually 'localhost' on Hostinger
define('DB_USER', 'u701659873_dist_user');       // Your database username
define('DB_PASS', 'bAAbA@35');       // Your database password
define('DB_NAME', 'u701659873_distributor');  // Your database name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Optional: Set timezone
date_default_timezone_set('UTC');

// Return connection for use in other files
// Usage: include 'config/database.php';
?>