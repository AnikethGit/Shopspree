<?php
/**
 * Invoice Creation & PDF Generation
 * Processes manual invoice form and generates downloadable PDF
 * Creates database entries identical to online orders
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check admin access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('generate_invoice.php?admin_key=admin_access_key');
}

try {
    // Validate required fields
    $required_fields = ['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 
                       'shipping_city', 'shipping_state', 'shipping_postal', 'shipping_country', 
                       'payment_method', 'items_json'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
        }
    }
    
    // Parse form data
    $customer_name = sanitize($_POST['customer_name']);
    $customer_email = sanitize($_POST['customer_email']);
    $customer_phone = sanitize($_POST['customer_phone']);
    $customer_company = sanitize($_POST['customer_company'] ?? '');
    $shipping_address = sanitize($_POST['shipping_address']);
    $shipping_city = sanitize($_POST['shipping_city']);
    $shipping_state = sanitize($_POST['shipping_state']);
    $shipping_postal = sanitize($_POST['shipping_postal']);
    $shipping_country = sanitize($_POST['shipping_country']);
    $payment_method = sanitize($_POST['payment_method']);
    $invoice_notes = sanitize($_POST['invoice_notes'] ?? '');
    
    // Parse items JSON
    $items_json = $_POST['items_json'];
    $items = json_decode($items_json, true);
    
    if (empty($items)) {
        throw new Exception('No items in invoice');
    }
    
    // Validate email
    if (!is_valid_email($customer_email)) {
        throw new Exception('Invalid email address');
    }
    
    // Get totals from form
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $tax = floatval($_POST['tax'] ?? 0);
    $shipping = floatval($_POST['shipping'] ?? 0);
    $total = floatval($_POST['total'] ?? 0);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate unique order ID
    $order_id = generate_order_id();
    
    // Create full shipping address
    $full_address = $shipping_address . ', ' . $shipping_city . ', ' . $shipping_state;
    
    // Insert order
    $stmt = $pdo->prepare(
        "INSERT INTO orders (order_id, email, phone, shipping_address, shipping_city, 
         shipping_state, shipping_postal_code, shipping_country, total_amount, payment_method, 
         order_status, notes, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Offline', ?, NOW())"
    );
    
    $stmt->execute([
        $order_id,
        $customer_email,
        $customer_phone,
        $full_address,
        $shipping_city,
        $shipping_state,
        $shipping_postal,
        $shipping_country,
        $total,
        $payment_method,
        $invoice_notes
    ]);
    
    // Get the created order's database ID
    $order_db_id = $pdo->lastInsertId();
    
    // Insert order items
    $item_stmt = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($items as $item) {
        $item_subtotal = $item['price'] * $item['quantity'];
        $item_stmt->execute([
            $order_db_id,
            $item['id'],
            $item['name'],
            $item['quantity'],
            $item['price'],
            $item_subtotal
        ]);
        
        // Update product stock (optional - for offline sales)
        $stock_stmt = $pdo->prepare(
            "UPDATE products SET quantity = quantity - ? WHERE id = ?"
        );
        $stock_stmt->execute([$item['quantity'], $item['id']]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Generate PDF
    require_once __DIR__ . '/../lib/pdf_generator.php';
    $pdf = new InvoicePDF();
    
    $invoice_data = [
        'order_id' => $order_id,
        'order_date' => date('Y-m-d H:i'),
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'customer_phone' => $customer_phone,
        'customer_company' => $customer_company,
        'shipping_address' => $full_address,
        'shipping_city' => $shipping_city,
        'shipping_state' => $shipping_state,
        'shipping_postal' => $shipping_postal,
        'shipping_country' => $shipping_country,
        'payment_method' => $payment_method,
        'notes' => $invoice_notes,
        'items' => $items,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'tax_rate' => 8,
        'shipping' => $shipping,
        'total' => $total
    ];
    
    // Generate and download PDF
    $filename = 'Invoice_' . $order_id . '_' . date('Ymd') . '.pdf';
    $pdf->generateInvoice($invoice_data, $filename);
    
} catch (Exception $e) {
    // Rollback if error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error
    error_log('Invoice creation error: ' . $e->getMessage());
    
    // Redirect with error
    header('Location: generate_invoice.php?admin_key=admin_access_key&error=' . urlencode($e->getMessage()));
    exit();
}

?>