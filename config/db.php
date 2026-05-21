<?php
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/config.php';

// ── Session inactivity timeout (30 min) ──────────────────────────────────────
$_session_timeout = 1800;
if (isset($_SESSION['last_activity'])
    && (time() - $_SESSION['last_activity']) > $_session_timeout) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();
unset($_session_timeout);

// ── PDO connection ────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    die('Database connection failed. Please contact support.');
}
