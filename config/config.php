<?php
// ── Site constants ─────────────────────────────────────────────────────────
define('SITE_NAME',              'PrintDepotCo');
define('SITE_URL',               rtrim(getenv('SITE_URL') ?: 'https://printdepotco.com/', '/') . '/');
define('SITE_EMAIL',             'noreply@printdepotco.com');
define('SITE_PHONE',             '+1-234-567-8900');
define('ADMIN_EMAIL',            getenv('ADMIN_EMAIL') ?: 'admin@printdepotco.com');

define('CURRENCY',               'USD');
define('CURRENCY_SYMBOL',        '$');

define('ITEMS_PER_PAGE',         12);

define('MAX_FILE_SIZE',          5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES',    ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_DIR',             $_SERVER['DOCUMENT_ROOT'] . '/img/uploads/');

define('MAIL_FROM',              'noreply@printdepotco.com');
define('MAIL_FROM_NAME',         'PrintDepotCo');

define('STANDARD_SHIPPING',      10.00);
define('EXPRESS_SHIPPING',       20.00);
define('FREE_SHIPPING_THRESHOLD', 100.00);
define('TAX_RATE',               0.06);

// ── Error reporting ─────────────────────────────────────────────────────────
if (getenv('APP_ENV') === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
}

// ── Session ─────────────────────────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Input / output helpers ───────────────────────────────────────────────────
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Alias kept for any existing callers
function sanitize_input($data) {
    return sanitize($data);
}

function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function format_price($price) {
    return CURRENCY_SYMBOL . number_format((float)$price, 2, '.', ',');
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// ── Flash messages ───────────────────────────────────────────────────────────
function add_message($message, $type = 'info') {
    if (!isset($_SESSION['messages'])) {
        $_SESSION['messages'] = [];
    }
    $_SESSION['messages'][] = ['text' => $message, 'type' => $type];
}

function get_messages() {
    $messages = $_SESSION['messages'] ?? [];
    unset($_SESSION['messages']);
    return $messages;
}

function display_error($message) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
        . htmlspecialchars($message)
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

function display_success($message) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
        . htmlspecialchars($message)
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// ── Auth ─────────────────────────────────────────────────────────────────────
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function current_user() {
    return is_logged_in() ? ($_SESSION['user'] ?? null) : null;
}

function get_user_identifier() {
    return is_logged_in() ? $_SESSION['user_id'] : session_id();
}

// ── Password ─────────────────────────────────────────────────────────────────
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Order helpers ────────────────────────────────────────────────────────────
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

// Alias kept for existing callers
function generate_order_id() {
    return generate_order_number();
}

// ── Pagination ───────────────────────────────────────────────────────────────
function get_page_number() {
    return max(1, (int)($_GET['page'] ?? 1));
}

function build_query($params) {
    return http_build_query(array_filter($params));
}

// ── Database query helpers (use global $pdo) ─────────────────────────────────
function get_user($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

function get_product($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    return $stmt->fetch() ?: null;
}

function get_category($category_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetch() ?: null;
}

function get_all_categories() {
    global $pdo;
    return $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC")
               ->fetchAll();
}
