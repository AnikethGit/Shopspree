<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/send_receipt.php';
require_once __DIR__ . '/../cart/get_cart.php';

// Must arrive via payment flow
if (!isset($_SESSION['payment_processed']) || $_SESSION['payment_processed'] !== true) {
    add_message('Payment must be processed first', 'error');
    redirect('../checkout.php');
}

if (!isset($_SESSION['checkout_data'])) {
    add_message('Checkout data not found', 'error');
    redirect('../checkout.php');
}

try {
    if (is_cart_empty()) {
        add_message('Your cart is empty', 'error');
        redirect('../cart.php');
    }

    $checkout_data   = $_SESSION['checkout_data'];
    $payment_details = $_SESSION['payment_details'] ?? [];
    $transaction_id  = $_SESSION['transaction_id'] ?? '';
    $payment_method  = $checkout_data['payment_method'];

    foreach (['email', 'full_name', 'phone', 'address', 'city', 'state', 'postal_code'] as $field) {
        if (empty($checkout_data[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }

    $cart_items   = get_cart_items();
    $stock_check  = validate_cart_stock($cart_items);
    if (!$stock_check['valid']) {
        throw new Exception(implode('; ', $stock_check['errors']));
    }

    if (empty($cart_items)) {
        add_message('Cart is empty or products not found', 'error');
        redirect('../cart.php');
    }

    // Calculate totals
    $totals            = calculate_cart_totals($cart_items, TAX_RATE, STANDARD_SHIPPING);
    $subtotal          = $totals['subtotal'];
    $effective_shipping = $totals['shipping'];
    $tax               = $totals['tax'];
    $total             = $totals['total'];

    $order_id        = generate_order_number();
    $user_id         = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $shipping_address = sanitize($checkout_data['address']) . ', '
                      . sanitize($checkout_data['city'])    . ', '
                      . sanitize($checkout_data['state']);
    $notes           = sanitize($checkout_data['notes'] ?? '');
    $order_status    = ($payment_method === 'COD') ? 'Pending' : 'Payment Received';
    $payment_status  = 'Completed';
    $payment_json    = json_encode($payment_details);

    $stmt = $pdo->prepare(
        "INSERT INTO orders
            (order_id, user_id, email, phone,
             shipping_address, shipping_city, shipping_state, shipping_postal_code,
             total_amount, payment_method, payment_status, transaction_id,
             payment_details, order_status, notes, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $order_id,
        $user_id,
        sanitize($checkout_data['email']),
        sanitize($checkout_data['phone']),
        $shipping_address,
        sanitize($checkout_data['city']),
        sanitize($checkout_data['state']),
        sanitize($checkout_data['postal_code']),
        $total,
        $payment_method,
        $payment_status,
        $transaction_id,
        $payment_json,
        $order_status,
        $notes,
    ]);
    $order_db_id = (int)$pdo->lastInsertId();

    // Insert order items and decrement stock
    $item_stmt  = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stock_stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");

    foreach ($cart_items as $item) {
        $item_subtotal = $item['price'] * $item['quantity'];
        $item_stmt->execute([$order_db_id, $item['id'], $item['name'], $item['quantity'], $item['price'], $item_subtotal]);
        $stock_stmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
    }

    // Send receipt
    $email_sent = send_order_receipt(
        $order_id, $order_db_id,
        $checkout_data['email'], $checkout_data['phone'],
        $cart_items,
        ['subtotal' => $subtotal, 'tax' => $tax, 'tax_rate' => TAX_RATE * 100, 'shipping' => $effective_shipping, 'total' => $total],
        $payment_method,
        $shipping_address,
        $payment_details
    );

    // Clear cart and session data
    clear_cart();
    unset($_SESSION['checkout_data'], $_SESSION['payment_processed'],
          $_SESSION['payment_details'], $_SESSION['transaction_id'], $_SESSION['payment_status']);

    $_SESSION['last_order_id']    = $order_id;
    $_SESSION['last_order_db_id'] = $order_db_id;
    $_SESSION['receipt_sent']     = $email_sent;

    add_message('Order placed successfully!', 'success');
    redirect('thank_you.php');

} catch (Exception $e) {
    error_log('Order creation error: ' . $e->getMessage());
    add_message('Error processing order: ' . htmlspecialchars($e->getMessage()), 'error');
    unset($_SESSION['payment_processed'], $_SESSION['payment_details'],
          $_SESSION['transaction_id']);
    redirect('../checkout.php');
}
