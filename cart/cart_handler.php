<?php
/**
 * Cart Handler
 * Manages shopping cart operations in SESSION
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

/**
 * Add product to cart (SESSION-based)
 * @param int $product_id
 * @param int $quantity
 * @return array Success or error details
 */
function add_to_cart($product_id, $quantity = 1) {
    global $conn;
    
    $product_id = intval($product_id);
    $quantity = intval($quantity);
    
    if ($product_id <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid product ID or quantity'];
    }
    
    // Initialize SESSION cart if not exists
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product exists and has stock
    if ($stmt = $conn->prepare("SELECT id, name, quantity, price FROM products WHERE id = ?")) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                // Check stock
                if (intval($product['quantity']) < $quantity) {
                    $stmt->close();
                    return ['success' => false, 'message' => 'Insufficient stock available. Only ' . $product['quantity'] . ' available.'];
                }
                
                // Add to session cart
                if (!isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] = 0;
                }
                $_SESSION['cart'][$product_id] += $quantity;
                
                $stmt->close();
                add_message($product['name'] . ' added to cart', 'success');
                return ['success' => true, 'message' => 'Product added to cart'];
            } else {
                $stmt->close();
                return ['success' => false, 'message' => 'Product not found'];
            }
        }
        $stmt->close();
    }
    
    return ['success' => false, 'message' => 'Error adding to cart'];
}

/**
 * Update cart item quantity (SESSION-based)
 * @param int $product_id
 * @param int $quantity
 * @return bool
 */
function update_cart_quantity($product_id, $quantity) {
    global $conn;
    
    $product_id = intval($product_id);
    $quantity = intval($quantity);
    
    if ($quantity <= 0) {
        return remove_from_cart($product_id);
    }
    
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || !isset($_SESSION['cart'][$product_id])) {
        return false;
    }
    
    // Check stock before updating
    if ($stmt = $conn->prepare("SELECT quantity FROM products WHERE id = ?")) {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $available = intval($product['quantity']);
                
                if ($available < $quantity) {
                    $stmt->close();
                    return false;
                }
                
                $_SESSION['cart'][$product_id] = $quantity;
                $stmt->close();
                return true;
            }
        }
        $stmt->close();
    }
    
    return false;
}

/**
 * Remove item from cart (SESSION-based)
 * @param int $product_id
 * @return bool
 */
function remove_from_cart($product_id) {
    $product_id = intval($product_id);
    
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            return true;
        }
    }
    
    return false;
}

/**
 * Clear entire cart for user/session
 * @return bool
 */
function clear_cart() {
    $_SESSION['cart'] = [];
    return true;
}

/**
 * Get cart count (total items)
 * @return int
 */
function get_cart_count() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    
    $count = 0;
    foreach ($_SESSION['cart'] as $qty) {
        $count += intval($qty);
    }
    
    return $count;
}

?>
