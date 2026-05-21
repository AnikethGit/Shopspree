<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/cart/cart_handler.php';

$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

// CSRF check for all POST-based cart mutations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token($_POST['csrf_token'] ?? '')) {
    add_message('Invalid request token. Please try again.', 'error');
    $redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'cart.php';
    redirect($redirect);
}

try {
    switch ($action) {
        case 'add':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity   = max(1, intval($_POST['quantity'] ?? 1));
            if ($product_id <= 0) {
                add_message('Invalid product', 'error');
            } else {
                $result = add_to_cart($product_id, $quantity);
                if (!$result['success']) add_message($result['message'], 'error');
            }
            break;

        case 'update':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity   = max(0, intval($_POST['qty'] ?? 1));
            if ($product_id <= 0) {
                add_message('Invalid product', 'error');
            } elseif ($quantity === 0) {
                remove_from_cart($product_id);
                add_message('Item removed from cart', 'success');
            } else {
                if (update_cart_quantity($product_id, $quantity)) {
                    add_message('Cart updated', 'success');
                } else {
                    add_message('Not enough stock available', 'error');
                }
            }
            break;

        case 'remove':
            $product_id = intval($_POST['product_id'] ?? 0);
            if ($product_id <= 0) {
                add_message('Invalid product', 'error');
            } elseif (remove_from_cart($product_id)) {
                add_message('Item removed from cart', 'success');
            } else {
                add_message('Item not in cart', 'error');
            }
            break;

        case 'clear':
            clear_cart();
            add_message('Cart cleared', 'success');
            break;

        default:
            add_message('Invalid action', 'error');
    }
} catch (Exception $e) {
    error_log('Cart action error: ' . $e->getMessage());
    add_message('An error occurred. Please try again.', 'error');
}

$redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'cart.php';
redirect($redirect);
