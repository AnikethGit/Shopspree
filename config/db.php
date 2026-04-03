<?php
/**
 * Database Connection Configuration
 * Uses MySQLi for secure database operations
 */

// ============================================
// HOSTINGER PRODUCTION DATABASE CREDENTIALS
// ============================================
// Update these with your actual Hostinger credentials
// Find them in your cPanel > Databases > MySQL Databases

$servername = "localhost"; // Usually 'localhost' on Hostinger
$username = ""; // Your database user (from cPanel)
$password = ""; // Your database password (from cPanel)
$dbname = ""; // Your database name (from cPanel)

// ============================================
// Session Start (guarded to prevent conflicts)
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// MySQLi Connection Setup
// ============================================
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error but don't show to user
        error_log("Database Connection Error: " . $conn->connect_error);
        die("Database connection failed. Please contact support.");
    }
    
    // Set character encoding to UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database Exception: " . $e->getMessage());
    die("Database error occurred. Please try again later.");
}

// ============================================
// PDO Connection Setup (For other files that use PDO)
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . $servername . ";dbname=" . $dbname . ";charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
    die("Database connection failed. Please contact support.");
}
?>
