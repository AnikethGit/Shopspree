<?php
/**
 * Process Order
 * checkout/process_order.php
 */

require_once '../config/database.php';
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Validate CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit();
}

// Get form data
$customer_email = sanitize_input($_POST['email'] ?? '');
$customer_phone = sanitize_input($_POST['phone'] ?? '');
$shipping_address = sanitize_input($_POST['address'] ?? '');
$shipping_city = sanitize_input($_POST['city'] ?? '');
$shipping_state = sanitize_input($_POST['state'] ?? '');
$shipping_postal = sanitize_input($_POST['postal_code'] ?? '');
$payment_method = sanitize_input($_POST['payment_method'] ?? 'COD');
$shipping_method = sanitize_input($_POST['shipping_method'] ?? 'standard');

// Validate required fields
if (empty($customer_email) || empty($customer_phone) || empty($shipping_address) || empty($shipping_city)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

if (!is_valid_email($customer_email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Generate order number
    $order_number = generate_order_number();
    $user_id = $_SESSION['user_id'] ?? NULL;

    // Calculate totals
    $subtotal = 0;
    $order_items = [];

    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product = get_product($product_id, $conn);
        
        if (!$product) {
            throw new Exception('Product not found');
        }

        $price = $product['discount_price'] ?? $product['price'];
        $item_subtotal = $price * $quantity;
        $subtotal += $item_subtotal;

        $order_items[] = [
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $item_subtotal
        ];
    }

    // Calculate shipping
    $shipping_cost = 0;
    if ($shipping_method === 'express') {
        $shipping_cost = EXPRESS_SHIPPING;
    } elseif ($subtotal < FREE_SHIPPING_THRESHOLD) {
        $shipping_cost = STANDARD_SHIPPING;
    }

    // Calculate tax
    $tax = $subtotal * TAX_RATE;
    $total = $subtotal + $shipping_cost + $tax;

    // Insert order
    $order_status = ($payment_method === 'COD') ? 'Pending' : 'Pending';
    $payment_status = 'Pending';

    $stmt = $conn->prepare(
        "INSERT INTO orders (
            order_number, user_id, customer_email, customer_phone,
            shipping_address, shipping_city, shipping_state, shipping_postal_code,
            subtotal, shipping_cost, tax, total,
            payment_method, payment_status, order_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "sissssssddddsss",
        $order_number, $user_id, $customer_email, $customer_phone,
        $shipping_address, $shipping_city, $shipping_state, $shipping_postal,
        $subtotal, $shipping_cost, $tax, $total,
        $payment_method, $payment_status, $order_status
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create order');
    }

    $order_id = $conn->insert_id;

    // Insert order items
    $item_stmt = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($order_items as $item) {
        $item_stmt->bind_param(
            "iisdd",
            $order_id,
            $item['product_id'],
            $item['product_name'],
            $item['quantity'],
            $item['price'],
            $item['subtotal']
        );

        if (!$item_stmt->execute()) {
            throw new Exception('Failed to add order items');
        }
    }

    // Update product quantities
    $update_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    
    foreach ($order_items as $item) {
        $update_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
        if (!$update_stmt->execute()) {
            throw new Exception('Failed to update product quantity');
        }
    }

    // Commit transaction
    $conn->commit();

    // Send confirmation email
    send_order_confirmation_email($order_number, $customer_email, $order_items, $total, $conn);

    // Clear cart
    unset($_SESSION['cart']);

    // Return success
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'redirect' => '../checkout/confirmation.php?order_id=' . $order_id
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}

// Function to send order confirmation email
function send_order_confirmation_email($order_number, $email, $items, $total, $conn) {
    $subject = 'Order Confirmation - ' . $order_number . ' | ' . SITE_NAME;
    
    $message = "
    <html>
    <head>
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .email-container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #667eea; color: white; padding: 20px; text-align: center; border-radius: 5px; }
            .order-details { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .item-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .item-table th, .item-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            .total-row { font-weight: bold; font-size: 18px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>Order Confirmation</h1>
                <p>Order #: $order_number</p>
            </div>
            
            <div class='order-details'>
                <p>Thank you for your order! Your order has been received and is being processed.</p>
                
                <h3>Order Items:</h3>
                <table class='item-table'>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>";
    
    foreach ($items as $item) {
        $message .= "
                    <tr>
                        <td>{$item['product_name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>" . format_price($item['price']) . "</td>
                        <td>" . format_price($item['subtotal']) . "</td>
                    </tr>";
    }
    
    $message .= "
                    <tr class='total-row'>
                        <td colspan='3'>Total Amount</td>
                        <td>" . format_price($total) . "</td>
                    </tr>
                </table>
                
                <p>You can track your order using Order ID: <strong>$order_number</strong></p>
                <p>Thank you for shopping with us!</p>
            </div>
            
            <p style='text-align: center; color: #666; font-size: 12px;'>
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . MAIL_FROM . "\r\n";

    // Send email
    mail($email, $subject, $message, $headers);
}
?>
