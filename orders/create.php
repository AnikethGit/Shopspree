<?php
/**
 * Order Creation & Processing (After Payment)
 * Processes payment data, creates order, and clears cart
 * This is called AFTER successful payment processing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/send_receipt.php';

// Check if payment has been processed
if (!isset($_SESSION['payment_processed']) || $_SESSION['payment_processed'] !== true) {
    add_message('Payment must be processed first', 'error');
    redirect('../checkout.php');
}

// Retrieve checkout data from session
if (!isset($_SESSION['checkout_data'])) {
    add_message('Checkout data not found', 'error');
    redirect('../checkout.php');
}

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

function calculate_cart_totals($cart_items, $tax_rate = 0.06, $shipping = 50) {
    $subtotal = 0.0;
    
    if (is_array($cart_items)) {
        foreach ($cart_items as $item) {
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $qty = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $subtotal += ($price * $qty);
        }
    }
    
    $subtotal = round($subtotal, 2);

    // Apply shipping only for orders under $200
    $effective_shipping = ($subtotal > 0 && $subtotal < 200) ? floatval($shipping) : 0.0;

    $tax = round($subtotal * floatval($tax_rate), 2);
    $total = round($subtotal + $tax + $effective_shipping, 2);
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'tax_rate' => floatval($tax_rate) * 100,
        'shipping' => $effective_shipping,
        'total' => $total,
        'item_count' => count($cart_items)
    ];
}

try {
    // Check if cart is empty
    if (is_cart_empty()) {
        add_message('Your cart is empty', 'error');
        redirect('../cart.php');
    }

    // Get checkout and payment data
    $checkout_data = $_SESSION['checkout_data'];
    $payment_details = $_SESSION['payment_details'] ?? [];
    $transaction_id = $_SESSION['transaction_id'] ?? '';
    $payment_method = $checkout_data['payment_method'];
    
    // Validate required checkout fields
    $required_fields = ['email', 'full_name', 'phone', 'address', 'city', 'state', 'postal_code'];
    foreach ($required_fields as $field) {
        if (empty($checkout_data[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    // Get cart data
    $cart_items = get_cart_items();
    $totals = calculate_cart_totals($cart_items, 0.06, 50);

    if (empty($cart_items)) {
        add_message('Your cart is empty or products not found', 'error');
        redirect('../cart.php');
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
                    throw new Exception('Product ' . htmlspecialchars($item['name']) . ' is out of stock');
                }
            } else {
                throw new Exception('Product not found');
            }
            $stmt->close();
        }
    }

    // Create order
    $order_id = 'ORD-' . strtoupper(uniqid());
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    
    $shipping_address = $checkout_data['address'] . ', ' . $checkout_data['city'] . ', ' . $checkout_data['state'];
    $notes = isset($checkout_data['notes']) ? sanitize($checkout_data['notes']) : '';
    
    // Determine order status based on payment method
    $order_status = ($payment_method === 'COD') ? 'Pending' : 'Payment Received';
    
    // Prepare order insert query
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
                            payment_status,
                            transaction_id,
                            payment_details,
                            order_status, 
                            notes, 
                            created_at
                        ) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if ($order_stmt = $conn->prepare($order_query)) {
            $payment_details_json = json_encode($payment_details);
            $payment_status = 'Completed';
            
            $bind_result = $order_stmt->bind_param(
                "sissssssdsssss",
                $order_id,
                $user_id,
                $checkout_data['email'],
                $checkout_data['phone'],
                $shipping_address,
                $checkout_data['city'],
                $checkout_data['state'],
                $checkout_data['postal_code'],
                $totals['total'],
                $payment_method,
                $payment_status,
                $transaction_id,
                $payment_details_json,
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
                            payment_status,
                            transaction_id,
                            payment_details,
                            order_status, 
                            notes, 
                            created_at
                        ) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if ($order_stmt = $conn->prepare($order_query)) {
            $payment_details_json = json_encode($payment_details);
            $payment_status = 'Completed';
            
            $bind_result = $order_stmt->bind_param(
                "sssssssdssss",
                $order_id,
                $checkout_data['email'],
                $checkout_data['phone'],
                $shipping_address,
                $checkout_data['city'],
                $checkout_data['state'],
                $checkout_data['postal_code'],
                $totals['total'],
                $payment_method,
                $payment_status,
                $transaction_id,
                $payment_details_json,
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
            $checkout_data['email'],
            $checkout_data['phone'],
            $cart_items,
            $totals,
            $payment_method,
            $shipping_address,
            $payment_details
        );
        
        // Clear session data
        $_SESSION['cart'] = [];
        unset($_SESSION['checkout_data']);
        unset($_SESSION['payment_processed']);
        unset($_SESSION['payment_details']);
        unset($_SESSION['transaction_id']);
        unset($_SESSION['payment_status']);
        
        // Set session variables for thank you page
        $_SESSION['last_order_id'] = $order_id;
        $_SESSION['last_order_db_id'] = $order_db_id;
        $_SESSION['receipt_sent'] = $email_sent;
        
        add_message('Order placed successfully!', 'success');
        redirect('thank_you.php');
    } else {
        error_log("Order execution failed: " . $order_stmt->error);
        throw new Exception('Error creating order. Please try again.');
    }

} catch (Exception $e) {
    error_log("Exception in order creation: " . $e->getMessage());
    add_message('Error processing order: ' . htmlspecialchars($e->getMessage()), 'error');
    
    // Clear payment session on error
    unset($_SESSION['payment_processed']);
    unset($_SESSION['payment_details']);
    unset($_SESSION['transaction_id']);
    
    redirect('../checkout.php');
}

?>