<?php
/**
 * Get Cart Items
 * Retrieves cart items from SESSION and calculates totals
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

/**
 * Get all cart items for current session
 * @return array Cart items with product details
 */
function get_cart_items() {
    global $conn;
    
    if (!isset($_SESSION['cart'])) {
        return [];
    }
    
    if (!is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
        return [];
    }
    
    $cart_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product_id = intval($product_id);
        $quantity = intval($quantity);
        
        if ($quantity <= 0 || $product_id <= 0) {
            continue;
        }
        
        // Get product details from database
        $query = "SELECT id, name, price FROM products WHERE id = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    
                    $cart_items[] = [
                        'id' => intval($product['id']),
                        'name' => htmlspecialchars($product['name']),
                        'price' => floatval($product['price']),
                        'quantity' => $quantity
                    ];
                }
            }
            
            $stmt->close();
        }
    }
    
    return $cart_items;
}

/**
 * Calculate cart totals
 * @param array $cart_items
 * @param float $tax_rate Tax as decimal (e.g., 0.08 for 8%)
 * @param float $shipping Fixed shipping cost
 * @return array Totals: subtotal, tax, shipping, total
 */
function calculate_cart_totals($cart_items, $tax_rate = 0.08, $shipping = 50) {
    $subtotal = 0.0;
    
    if (is_array($cart_items)) {
        foreach ($cart_items as $item) {
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $qty = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $subtotal += ($price * $qty);
        }
    }
    
    $subtotal = round($subtotal, 2);
    $tax = round($subtotal * floatval($tax_rate), 2);
    $total = round($subtotal + $tax + floatval($shipping), 2);
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'tax_rate' => floatval($tax_rate) * 100,
        'shipping' => floatval($shipping),
        'total' => $total,
        'item_count' => count($cart_items)
    ];
}

/**
 * Get cart summary (items + totals)
 * @param float $tax_rate
 * @param float $shipping
 * @return array Complete cart data
 */
function get_cart_summary($tax_rate = 0.08, $shipping = 50) {
    $items = get_cart_items();
    $totals = calculate_cart_totals($items, $tax_rate, $shipping);
    
    return [
        'items' => $items,
        'totals' => $totals,
        'is_empty' => (count($items) === 0)
    ];
}

/**
 * Get cart count (number of items)
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

/**
 * Check if cart is empty
 * @return bool
 */
function is_cart_empty() {
    if (!isset($_SESSION['cart'])) {
        return true;
    }
    return !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0;
}

/**
 * Get cart item by product ID
 * @param int $product_id
 * @return array|null
 */
function get_cart_item_by_product($product_id) {
    global $conn;
    
    $product_id = intval($product_id);
    
    if (!isset($_SESSION['cart'][$product_id])) {
        return null;
    }
    
    $quantity = intval($_SESSION['cart'][$product_id]);
    
    if ($quantity <= 0) {
        return null;
    }
    
    // Get product details
    $query = "SELECT id, name, price FROM products WHERE id = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                return [
                    'id' => intval($product['id']),
                    'name' => htmlspecialchars($product['name']),
                    'price' => floatval($product['price']),
                    'quantity' => $quantity
                ];
            }
        }
        
        $stmt->close();
    }
    
    return null;
}

/**
 * Validate cart items have sufficient stock
 * @param array $cart_items
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_cart_stock($cart_items) {
    global $conn;
    
    $errors = [];
    
    if (!is_array($cart_items)) {
        return ['valid' => true, 'errors' => []];
    }
    
    foreach ($cart_items as $item) {
        $product_id = intval($item['id']);
        $requested_qty = intval($item['quantity']);
        
        $query = "SELECT quantity FROM products WHERE id = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    $available = intval($product['quantity']);
                    
                    if ($available < $requested_qty) {
                        $errors[] = htmlspecialchars($item['name']) . ' - Insufficient stock (Available: ' . $available . ', Requested: ' . $requested_qty . ')';
                    }
                } else {
                    $errors[] = htmlspecialchars($item['name']) . ' - Product not found';
                }
            }
            
            $stmt->close();
        }
    }
    
    return [
        'valid' => (count($errors) === 0),
        'errors' => $errors
    ];
}

?>