<?php
/**
 * Helper Functions
 * Reusable utilities for formatting, validation, and common operations
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format price with currency symbol
 * @param float $price
 * @return string Formatted price (e.g., "$99.99")
 */
function format_price($price) {
    return '$' . number_format($price, 2, '.', ',');
}

/**
 * Sanitize user input
 * @param string $input
 * @return string Clean input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user data
 * @return array|null User data or null if not logged in
 */
function current_user() {
    if (is_logged_in()) {
        return $_SESSION['user'] ?? null;
    }
    return null;
}

/**
 * Get current session ID or generate one
 * @return string Session ID
 */
function get_session_id() {
    if (!isset($_SESSION['session_id'])) {
        $_SESSION['session_id'] = session_id();
    }
    return $_SESSION['session_id'];
}

/**
 * Add flash message to session
 * @param string $message
 * @param string $type (success, error, info, warning)
 */
function add_message($message, $type = 'info') {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = [
        'text' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash messages
 * @return array Messages
 */
function get_messages() {
    $messages = $_SESSION['messages'] ?? [];
    unset($_SESSION['messages']);
    return $messages;
}

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Generate unique order ID
 * @return string Order ID (e.g., ORD-20260106-123456)
 */
function generate_order_id() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Get user identifier (ID if logged in, session ID otherwise)
 * @return string|int Identifier
 */
function get_user_identifier() {
    return is_logged_in() ? $_SESSION['user_id'] : get_session_id();
}

/**
 * Format date for display
 * @param string $date
 * @return string Formatted date
 */
function format_date($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Validate email
 * @param string $email
 * @return bool
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Get page number from query parameter
 * @return int Page number (minimum 1)
 */
function get_page_number() {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    return max(1, $page);
}

/**
 * Build query string from array
 * @param array $params
 * @return string Query string
 */
function build_query($params) {
    return http_build_query(array_filter($params));
}

?>