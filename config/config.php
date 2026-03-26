<?php
/**
 * Global Configuration File
 * config/config.php
 */

// Site Configuration
define('SITE_NAME', 'PrintDepotCo');
define('SITE_URL', 'https://printdepotco.com/');  // Change in production
define('SITE_EMAIL', 'noreply@printdepotco.com');
define('SITE_PHONE', '+1-234-567-8900');

// Currency
define('CURRENCY', 'USD');
define('CURRENCY_SYMBOL', '$');

// Pagination
define('ITEMS_PER_PAGE', 12);

// File Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/img/uploads/');

// Email Configuration (Using PHP mail())
define('MAIL_FROM', 'noreply@printdepotco.com');
define('MAIL_FROM_NAME', 'PrintDepotCo');

// Admin Settings
define('ADMIN_EMAIL', 'admin@printdepotco.com');

// Shipping Cost
define('STANDARD_SHIPPING', 10.00);
define('EXPRESS_SHIPPING', 20.00);
define('FREE_SHIPPING_THRESHOLD', 100.00);

// Tax Rate (6%)
define('TAX_RATE', 0.06);

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to sanitize input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to redirect
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to generate unique order number
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

// Function to format price
function format_price($price) {
    return CURRENCY_SYMBOL . number_format($price, 2, '.', ',');
}

// CSRF Token Generation & Validation
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Function to display error messages
function display_error($message) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

// Function to display success messages
function display_success($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

// Function to get user info by ID
function get_user($user_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get product by ID
function get_product($product_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = TRUE");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get category by ID
function get_category($category_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to get all categories
function get_all_categories($conn) {
    $result = $conn->query("SELECT * FROM categories ORDER BY name");
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to log user activity (optional)
function log_activity($user_id, $action, $conn) {
    // You can extend this to track user actions
    // For now, it's a placeholder
}

?>
