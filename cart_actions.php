<?php
/**
 * Cart Actions Handler
 * Processes cart operations (add, update, remove, clear)
 * Works with session-based cart storage
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

// Initialize session cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get action from request
$action = sanitize($_POST['action'] ?? $_GET['action'] ?? '');

try {
    switch ($action) {
        case 'add':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = max(1, (int)($_POST['quantity'] ?? 1));
            
            if ($product_id <= 0) {
                add_message('Invalid product', 'error');
            } else {
                // Check if product exists and has stock
                $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $product_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            $product = $result->fetch_assoc();
                            if ($product['quantity'] >= $quantity) {
                                // Add or update cart
                                if (isset($_SESSION['cart'][$product_id])) {
                                    $_SESSION['cart'][$product_id] += $quantity;
                                } else {
                                    $_SESSION['cart'][$product_id] = $quantity;
                                }
                                add_message($product['name'] . ' added to cart!', 'success');
                            } else {
                                add_message('Not enough stock available', 'error');
                            }
                        } else {
                            add_message('Product not found', 'error');
                        }
                    }
                    $stmt->close();
                } else {
                    add_message('Database error', 'error');
                }
            }
            break;

        case 'update':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $quantity = max(0, (int)($_POST['qty'] ?? 1));
            
            if ($product_id <= 0) {
                add_message('Invalid product', 'error');
            } else if ($quantity == 0) {
                // Remove item if quantity is 0
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    add_message('Item removed from cart', 'success');
                }
            } else {
                // Check stock
                $stmt = $conn->prepare("SELECT quantity, name FROM products WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $product_id);
                    if ($stmt->execute()) {
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            $product = $result->fetch_assoc();
                            if ($product['quantity'] >= $quantity) {
                                $_SESSION['cart'][$product_id] = $quantity;
                                add_message('Cart updated', 'success');
                            } else {
                                add_message('Not enough stock available. Available: ' . $product['quantity'], 'error');
                            }
                        } else {
                            add_message('Product not found', 'error');
                        }
                    }
                    $stmt->close();
                } else {
                    add_message('Database error', 'error');
                }
            }
            break;

        case 'remove':
            $product_id = (int)($_POST['product_id'] ?? 0);
            
            if ($product_id <= 0) {
                add_message('Invalid product', 'error');
            } else {
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    add_message('Item removed from cart', 'success');
                } else {
                    add_message('Item not in cart', 'error');
                }
            }
            break;

        case 'clear':
            $_SESSION['cart'] = [];
            add_message('Cart cleared', 'success');
            break;

        default:
            add_message('Invalid action', 'error');
    }
} catch (Exception $e) {
    error_log('Cart action error: ' . $e->getMessage());
    add_message('An error occurred: ' . $e->getMessage(), 'error');
}

// Redirect back to referrer or cart
$redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'cart.php';
redirect($redirect);

?>