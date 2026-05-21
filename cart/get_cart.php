<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/cart_handler.php';

function get_cart_items(): array {
    global $pdo;
    if (empty($_SESSION['cart_token']) && empty($_COOKIE['cart_token'])) {
        return [];
    }
    $token = get_cart_token();
    $stmt  = $pdo->prepare(
        "SELECT ci.product_id AS id, p.name, p.price, ci.quantity
         FROM cart_items ci
         JOIN products p ON p.id = ci.product_id AND p.is_active = 1
         WHERE ci.cart_token = ?
         ORDER BY ci.created_at ASC"
    );
    $stmt->execute([$token]);
    return $stmt->fetchAll();
}

function calculate_cart_totals(array $cart_items, float $tax_rate = 0.06, float $shipping = 0): array {
    $subtotal = 0.0;
    foreach ($cart_items as $item) {
        $subtotal += (float)$item['price'] * (int)$item['quantity'];
    }
    $subtotal = round($subtotal, 2);

    // Free shipping over threshold
    if ($subtotal >= FREE_SHIPPING_THRESHOLD && $shipping == STANDARD_SHIPPING) {
        $shipping = 0.0;
    }

    $tax   = round($subtotal * $tax_rate, 2);
    $total = round($subtotal + $tax + $shipping, 2);

    return [
        'subtotal'   => $subtotal,
        'tax'        => $tax,
        'tax_rate'   => $tax_rate * 100,
        'shipping'   => $shipping,
        'total'      => $total,
        'item_count' => count($cart_items),
    ];
}

function get_cart_summary(float $tax_rate = 0.06, float $shipping = 0): array {
    $items  = get_cart_items();
    $totals = calculate_cart_totals($items, $tax_rate, $shipping);
    return ['items' => $items, 'totals' => $totals, 'is_empty' => empty($items)];
}

function is_cart_empty(): bool {
    return get_cart_count() === 0;
}

function validate_cart_stock(array $cart_items): array {
    global $pdo;
    $errors = [];
    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([(int)$item['id']]);
        $product = $stmt->fetch();
        if (!$product) {
            $errors[] = htmlspecialchars($item['name']) . ' — Product not found';
        } elseif ((int)$product['quantity'] < (int)$item['quantity']) {
            $errors[] = htmlspecialchars($item['name'])
                . ' — Only ' . $product['quantity'] . ' in stock (requested ' . $item['quantity'] . ')';
        }
    }
    return ['valid' => empty($errors), 'errors' => $errors];
}
