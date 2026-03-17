<?php
/**
 * Order Creation & Processing
 * Processes checkout form, creates order, and clears cart
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/send_receipt.php';

// Inline cart functions
function is_cart_empty() {
    if (!isset($_SESSION['cart'])) {
        return true;
    }
    return !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0;
}

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
        
        $query = "SELECT id, name, price FROM products WHERE id = ?";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $product = $result->fetch_assoc();
                    
                    $cart_items[] = [
                        'id' => intval($product['id']),
                        'product_id' => intval($product['id']),
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkout.php');
}

// Check if cart is empty
if (is_cart_empty()) {
    add_message('Your cart is empty', 'error');
    redirect('cart.php');
}

try {
    // Validate required fields
    $required_fields = ['email', 'full_name', 'phone', 'address', 'city', 'state', 'postal_code', 'payment_method'];
    $errors = [];
    $data = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        } else {
            $data[$field] = sanitize($_POST[$field]);
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            add_message($error, 'error');
        }
        redirect('checkout.php');
    }

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        add_message('Invalid email address', 'error');
        redirect('checkout.php');
    }

    // Get cart data
    $cart_items = get_cart_items();
    $totals = calculate_cart_totals($cart_items, 0.08, 50);

    if (empty($cart_items)) {
        add_message('Your cart is empty or products not found', 'error');
        redirect('cart.php');
    }

    // Validate stock
    foreach ($cart_items as $item) {
        $item_id = intval($item['id']);
        $qty = intval($item['quantity']);
        
        $stock_query = "SELECT quantity FROM products WHERE id = ?";
        if ($stmt = $conn->prepare($stock_query)) {
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                if (intval($product['quantity']) < $qty) {
                    add_message('Product ' . htmlspecialchars($item['name']) . ' is out of stock', 'error');
                    redirect('cart.php');
                }
            } else {
                add_message('Product not found', 'error');
                redirect('cart.php');
            }
            $stmt->close();
        }
    }

    // Create order
    $order_id = 'ORD-' . strtoupper(uniqid());
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    
    $shipping_address = $data['address'] . ', ' . $data['city'] . ', ' . $data['state'];
    $payment_method = $data['payment_method'];
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    $order_status = 'Pending';
    
    // Use conditional query based on whether user is logged in
    if ($user_id !== null) {
        // User logged in - include user_id
        $order_query = "INSERT INTO orders (
                            order_id, 
                            user_id, 
                            email, 
                            phone, 
                            shipping_address, 
                            shipping_city, 
                            shipping_state, 
                            shipping_postal_code, 
                            total_amount, 
                            payment_method, 
                            order_status, 
                            notes, 
                            created_at
                        ) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if ($order_stmt = $conn->prepare($order_query)) {
            // 12 values: order_id(s), user_id(i), email(s), phone(s), 
            // shipping_address(s), city(s), state(s), postal_code(s), 
            // total(d), payment_method(s), status(s), notes(s)
            $bind_result = $order_stmt->bind_param(
                "sissssssdsss",
                $order_id,
                $user_id,
                $data['email'],
                $data['phone'],
                $shipping_address,
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $totals['total'],
                $payment_method,
                $order_status,
                $notes
            );
            
            if (!$bind_result) {
                throw new Exception("bind_param failed (logged-in user): " . $order_stmt->error);
            }
        } else {
            throw new Exception("prepare failed: " . $conn->error);
        }
    } else {
        // Guest checkout - no user_id
        $order_query = "INSERT INTO orders (
                            order_id, 
                            email, 
                            phone, 
                            shipping_address, 
                            shipping_city, 
                            shipping_state, 
                            shipping_postal_code, 
                            total_amount, 
                            payment_method, 
                            order_status, 
                            notes, 
                            created_at
                        ) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if ($order_stmt = $conn->prepare($order_query)) {
            // 11 values: order_id(s), email(s), phone(s), 
            // shipping_address(s), city(s), state(s), postal_code(s), 
            // total(d), payment_method(s), status(s), notes(s)
            $bind_result = $order_stmt->bind_param(
                "sssssssdsss",
                $order_id,
                $data['email'],
                $data['phone'],
                $shipping_address,
                $data['city'],
                $data['state'],
                $data['postal_code'],
                $totals['total'],
                $payment_method,
                $order_status,
                $notes
            );
            
            if (!$bind_result) {
                throw new Exception("bind_param failed (guest): " . $order_stmt->error);
            }
        } else {
            throw new Exception("prepare failed: " . $conn->error);
        }
    }
    
    // Execute the order insert
    if ($order_stmt->execute()) {
        $order_db_id = $conn->insert_id;
        $order_stmt->close();
        
        // Add items to order_items
        $item_query = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) 
                      VALUES (?, ?, ?, ?, ?, ?)";
        
        foreach ($cart_items as $item) {
            $subtotal = floatval($item['price']) * intval($item['quantity']);
            $item_id = intval($item['id']);
            $item_qty = intval($item['quantity']);
            $item_price = floatval($item['price']);
            
            if ($item_stmt = $conn->prepare($item_query)) {
                // Type string: order_id(i), product_id(i), product_name(s), 
                // quantity(i), price(d), subtotal(d)
                $item_bind = $item_stmt->bind_param(
                    "iisidd",
                    $order_db_id,
                    $item_id,
                    $item['name'],
                    $item_qty,
                    $item_price,
                    $subtotal
                );
                
                if (!$item_bind) {
                    error_log("Item bind_param error: " . $item_stmt->error);
                    continue;
                }
                
                if ($item_stmt->execute()) {
                    // Update product stock
                    $stock_update = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
                    if ($stock_stmt = $conn->prepare($stock_update)) {
                        $stock_stmt->bind_param("ii", $item_qty, $item_id);
                        $stock_stmt->execute();
                        $stock_stmt->close();
                    }
                } else {
                    error_log("Error inserting order item: " . $item_stmt->error);
                }
                $item_stmt->close();
            }
        }
        
        // SEND RECEIPT EMAIL
        $email_sent = send_order_receipt(
            $order_id,
            $order_db_id,
            $data['email'],
            $data['phone'],
            $cart_items,
            $totals,
            $payment_method,
            $shipping_address
        );
        
        // Clear cart from SESSION
        $_SESSION['cart'] = [];
        
        // Set session variables for thank you page
        $_SESSION['last_order_id'] = $order_id;
        $_SESSION['last_order_db_id'] = $order_db_id;
        $_SESSION['receipt_sent'] = $email_sent;
        
        add_message('Order placed successfully!', 'success');
        redirect('thank_you.php');
    } else {
        error_log("Order execution failed: " . $order_stmt->error);
        add_message('Error creating order. Please try again.', 'error');
        redirect('checkout.php');
    }

} catch (Exception $e) {
    error_log("Exception in order creation: " . $e->getMessage());
    add_message('Error processing order: ' . htmlspecialchars($e->getMessage()), 'error');
    redirect('checkout.php');
}

?>