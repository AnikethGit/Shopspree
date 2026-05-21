<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Get or create a unique cart token for this visitor.
 * Stored in the session (fast path) and a 30-day cookie (cross-session persistence).
 */
function get_cart_token(): string {
    if (!empty($_SESSION['cart_token']) && preg_match('/^[0-9a-f]{64}$/', $_SESSION['cart_token'])) {
        return $_SESSION['cart_token'];
    }
    if (!empty($_COOKIE['cart_token']) && preg_match('/^[0-9a-f]{64}$/', $_COOKIE['cart_token'])) {
        $_SESSION['cart_token'] = $_COOKIE['cart_token'];
        return $_SESSION['cart_token'];
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['cart_token'] = $token;
    setcookie('cart_token', $token, [
        'expires'  => time() + 30 * 24 * 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $token;
}

function add_to_cart(int $product_id, int $quantity = 1): array {
    global $pdo;

    if ($product_id <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID or quantity'];
    }

    $stmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        return ['success' => false, 'message' => 'Product not found'];
    }

    $token = get_cart_token();

    // How many units are already in the cart?
    $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE cart_token = ? AND product_id = ?");
    $stmt->execute([$token, $product_id]);
    $existing = (int)($stmt->fetchColumn() ?: 0);

    $new_qty = $existing + $quantity;
    if ($new_qty > (int)$product['quantity']) {
        return ['success' => false, 'message' => 'Insufficient stock. Only ' . $product['quantity'] . ' available.'];
    }

    $pdo->prepare(
        "INSERT INTO cart_items (cart_token, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = NOW()"
    )->execute([$token, $product_id, $new_qty]);

    add_message(htmlspecialchars($product['name']) . ' added to cart', 'success');
    return ['success' => true, 'message' => 'Product added to cart'];
}

function update_cart_quantity(int $product_id, int $quantity): bool {
    global $pdo;

    if ($quantity <= 0) {
        return remove_from_cart($product_id);
    }

    $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product || (int)$product['quantity'] < $quantity) {
        return false;
    }

    $token = get_cart_token();
    $pdo->prepare(
        "UPDATE cart_items SET quantity = ?, updated_at = NOW()
         WHERE cart_token = ? AND product_id = ?"
    )->execute([$quantity, $token, $product_id]);

    return true;
}

function remove_from_cart(int $product_id): bool {
    global $pdo;
    $token = get_cart_token();
    $stmt  = $pdo->prepare("DELETE FROM cart_items WHERE cart_token = ? AND product_id = ?");
    $stmt->execute([$token, $product_id]);
    return $stmt->rowCount() > 0;
}

function clear_cart(): bool {
    global $pdo;
    if (empty($_SESSION['cart_token']) && empty($_COOKIE['cart_token'])) {
        return true;
    }
    $token = get_cart_token();
    $pdo->prepare("DELETE FROM cart_items WHERE cart_token = ?")->execute([$token]);
    // Expire session token and cookie so a fresh token is generated next visit
    unset($_SESSION['cart_token']);
    setcookie('cart_token', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return true;
}

function get_cart_count(): int {
    global $pdo;
    if (empty($_SESSION['cart_token']) && empty($_COOKIE['cart_token'])) {
        return 0;
    }
    $token = get_cart_token();
    $stmt  = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_token = ?");
    $stmt->execute([$token]);
    return (int)$stmt->fetchColumn();
}
