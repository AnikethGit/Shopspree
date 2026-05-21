<?php
/**
 * Add to Cart (AJAX endpoint)
 * cart/add_to_cart.php
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/cart_handler.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity   = (int)($_POST['quantity']   ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

$result               = add_to_cart($product_id, $quantity);
$result['cart_count'] = get_cart_count();
echo json_encode($result);
