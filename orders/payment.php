<?php
/**
 * Payment Processing Page (Dummy Payment Gateway)
 * Displays realistic payment form and processes dummy transactions
 * Saves payment details to database without real transaction
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check if checkout data is in session
if (!isset($_SESSION['checkout_data'])) {
    add_message('Please complete checkout first', 'error');
    redirect('../checkout.php');
}

$checkout_data = $_SESSION['checkout_data'];
$payment_method = $checkout_data['payment_method'];
$total_amount = $checkout_data['total'];
$messages = get_messages();

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $payment_details = [];
    
    // Capture payment details based on method (no hard failures for demo)
    if ($payment_method === 'Credit Card') {
        // Capture card details
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $card_holder = sanitize($_POST['card_holder'] ?? '');
        $expiry_month = intval($_POST['expiry_month'] ?? 0);
        $expiry_year = intval($_POST['expiry_year'] ?? 0);
        $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
        
        // For demo purposes, do not block on validation.
        // Browser "required" attributes handle basic UX validation, and
        // the backend always treats the payment as successful.
        $masked_card = 'XXXX-XXXX-XXXX-' . substr($card_number ?: '0000', -4);
        $payment_details = [
            'card_number' => $masked_card,
            'card_holder' => !empty($card_holder) ? $card_holder : 'Demo User',
            'expiry' => (($expiry_month ?: 1) . '/' . ($expiry_year ?: (date('Y') + 1)))
        ];
        
    } elseif ($payment_method === 'Bank Transfer') {
        // Capture bank details
        $account_holder = sanitize($_POST['account_holder'] ?? '');
        $account_number = preg_replace('/\D/', '', $_POST['account_number'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        
        // No hard validation failures in demo; just mask and store
        $masked_account = 'XXXX-XXXX-' . substr($account_number ?: '0000', -4);
        $payment_details = [
            'account_holder' => !empty($account_holder) ? $account_holder : 'Demo Account',
            'account_number' => $masked_account,
            'bank_name' => !empty($bank_name) ? $bank_name : 'Demo Bank'
        ];
        
    } elseif ($payment_method === 'PayPal' || $payment_method === 'Klarna') {
        // No extra fields to validate for demo PayPal/Klarna flows
        $payment_details = [];
    } elseif ($payment_method === 'COD') {
        // COD doesn't need payment details
        $payment_details = [];
    }
    
    // Always process order for demo (100% success rate configured below)
    if (empty($errors)) {
        // Simulate payment processing with dummy transaction (100% success rate)
        $transaction_id = 'TXN-' . strtoupper(uniqid());
        $payment_status = simulate_payment_processing($payment_method);
        
        if ($payment_status['success']) {
            // Store payment info in session for order creation (for display / debugging only)
            $_SESSION['payment_processed'] = true;
            $_SESSION['payment_details'] = $payment_details;
            $_SESSION['transaction_id'] = $transaction_id;
            $_SESSION['payment_status'] = 'Completed';
            
            // Proceed to process the order immediately
            // Inline cart functions for order processing
            function is_cart_empty() {
                if (!isset($_SESSION['cart'])) {
                    return true;
                }
                return !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0;
            }

            function get_cart_items_inline() {
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

            function calculate_cart_totals_inline($cart_items, $tax_rate = 0.08, $shipping = 50) {
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

                // Get cart data
                $cart_items = get_cart_items_inline();
                $totals = calculate_cart_totals_inline($cart_items, 0.08, 50);

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
                
                // Prepare order insert query (aligned with current DB schema: no payment_status, transaction_id, or payment_details columns)
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
                        $bind_result = $order_stmt->bind_param(
                            "sissssssdsss",
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
                        $bind_result = $order_stmt->bind_param(
                            "sssssssdsss",
                            $order_id,
                            $checkout_data['email'],
                            $checkout_data['phone'],
                            $shipping_address,
                            $checkout_data['city'],
                            $checkout_data['state'],
                            $checkout_data['postal_code'],
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
                    
                    // Redirect to thank you page after successful order creation
                    header('Location: thank_you.php');
                    exit();
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
        } else {
            // Dummy payment failed (should never happen with 100% success rate)
            add_message('Payment processing failed: ' . $payment_status['message'], 'error');
        }
    } else {
        // Show validation errors (currently unused as we do not hard-fail in demo)
        foreach ($errors as $error) {
            add_message($error, 'error');
        }
    }
}

/**
 * Validate credit card using Luhn algorithm
 * (Kept for reference, no longer used for blocking demo payments)
 */
function validate_card_number($card_number) {
    $card_number = preg_replace('/\D/', '', $card_number);
    
    if (strlen($card_number) < 13 || strlen($card_number) > 19) {
        return false;
    }
    
    $sum = 0;
    $digit = 0;
    $add_digit = 0;
    
    for ($i = strlen($card_number) - 1; $i >= 0; $i--) {
        $digit = (int)$card_number[$i];
        
        if ($add_digit % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
        $add_digit++;
    }
    
    return ($sum % 10 === 0);
}

/**
 * Simulate payment processing (Dummy)
 * Always returns success (100% success rate) to avoid confusion
 */
function simulate_payment_processing($payment_method) {
    return [
        'success' => true,
        'message' => 'Payment processed successfully',
        'status'  => 'Completed'
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment - Shopspree</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">
    
    <style>
        .payment-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .card-number-display {
            font-size: 24px;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            margin-bottom: 20px;
            margin-top: 30px;
        }
        
        .card-row {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .card-field {
            flex: 1;
            font-size: 12px;
        }
        
        .card-field label {
            opacity: 0.8;
            display: block;
            font-size: 10px;
            margin-bottom: 3px;
        }
        
        .card-field-value {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .payment-method-section {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .payment-icon {
            font-size: 40px;
            margin-bottom: 20px;
        }
        
        .payment-icon.card {
            color: #667eea;
        }
        
        .payment-icon.bank {
            color: #28a745;
        }
        
        .payment-icon.cod {
            color: #ffc107;
        }
        
        .security-info {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .security-info i {
            color: #2196F3;
            margin-right: 10px;
            font-size: 20px;
        }
        
        .order-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row.total {
            font-weight: bold;
            font-size: 18px;
            border-bottom: 2px solid #667eea;
            padding: 12px 0;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
        }
        
        .payment-processing {
            text-align: center;
            padding: 40px;
            display: none;
        }
        
        .spinner-border-lg {
            width: 3rem;
            height: 3rem;
        }
        
        #paymentForm.hidden {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Topbar Start -->
    <div class="container-fluid px-5 d-none border-bottom d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-4 text-center text-lg-start mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a href="#" class="text-muted me-2"> Help</a><small> / </small>
                    <a href="#" class="text-muted mx-2"> Support</a><small> / </small>
                    <a href="#" class="text-muted ms-2"> Contact</a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-flex align-items-center justify-content-center">
                <small class="text-dark">Call Us:</small>
                <a href="#" class="text-muted">(+012) 1234 567890</a>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted me-2" data-bs-toggle="dropdown"><small>USD</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item"> Euro</a>
                            <a href="#" class="dropdown-item"> Dolar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <a href="../index.php" class="navbar-brand p-0">
                    <h1 class="display-5 text-primary m-0"><i class="fas fa-shopping-bag text-secondary me-2"></i>Electro</h1>
                </a>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-12 col-lg-9 ms-auto">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="../index.php" class="navbar-brand d-block d-lg-none">
                        <h1 class="display-5 text-secondary m-0"><i class="fas fa-shopping-bag text-white me-2"></i>Electro</h1>
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="../index.php" class="nav-item nav-link">Home</a>
                            <a href="../shop.php" class="nav-item nav-link">Shop</a>
                            <a href="../cart.php" class="nav-item nav-link">Cart</a>
                            <a href="../checkout.php" class="nav-item nav-link active">Checkout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Payment</h1>
    </div>
    <!-- Single Page Header End -->

    <!-- Payment Page Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($msg['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <div class="row g-5">
                <!-- Payment Form Column -->
                <div class="col-md-12 col-lg-8">
                    <div class="security-info">
                        <i class="fas fa-lock"></i>
                        <span><strong>Secure Payment:</strong> Your payment information is encrypted and secure. This is a demo payment system.</span>
                    </div>

                    <!-- Payment Processing Overlay -->
                    <div class="payment-processing" id="paymentProcessing">
                        <div class="spinner-border spinner-border-lg text-primary mb-3" role="status">
                            <span class="sr-only">Processing...</span>
                        </div>
                        <h5><strong>Processing Your Payment...</strong></h5>
                        <p class="text-muted mt-2">Please wait, do not refresh this page</p>
                        <div class="mt-4">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>

                    <form method="post" action="payment.php" id="paymentForm" onsubmit="return handlePaymentSubmit(event);">

                        <!-- Credit Card Payment -->
                        <?php if ($payment_method === 'Credit Card'): ?>
                            <h5 class="mb-4">Credit/Debit Card Details</h5>
                            
                            <!-- Card Preview -->
                            <div class="payment-card" id="cardPreview">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div style="font-size: 12px; opacity: 0.8;">Card Number</div>
                                        <div class="card-number-display" id="cardNumberDisplay">•••• •••• •••• ••••</div>
                                    </div>
                                    <div style="font-size: 30px;">💳</div>
                                </div>
                                
                                <div class="card-row">
                                    <div class="card-field">
                                        <label>Cardholder Name</label>
                                        <div class="card-field-value" id="cardholderDisplay">YOUR NAME</div>
                                    </div>
                                    <div class="card-field" style="text-align: right;">
                                        <label>Expires</label>
                                        <div class="card-field-value" id="expiryDisplay">MM/YY</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Input Form -->
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Cardholder Name *</label>
                                    <input type="text" class="form-control" name="card_holder" id="cardHolder" placeholder="John Doe" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Card Number *</label>
                                    <input type="text" class="form-control" name="card_number" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Month *</label>
                                    <select class="form-select" name="expiry_month" id="expiryMonth" required>
                                        <option value="">Select Month</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Year *</label>
                                    <select class="form-select" name="expiry_year" id="expiryYear" required>
                                        <option value="">Select Year</option>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($i = 0; $i < 10; $i++): 
                                            $year = $current_year + $i;
                                        ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">CVV (3-4 digits) *</label>
                                    <input type="text" class="form-control" name="cvv" id="cvv" placeholder="123" maxlength="4" required>
                                </div>
                            </div>

                        <!-- Bank Transfer Payment (legacy/demo) -->
                        <?php elseif ($payment_method === 'Bank Transfer'): ?>
                            <h5 class="mb-4">Bank Transfer Details</h5>
                            
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                Please enter your bank details. The order will be created pending bank transfer confirmation.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Account Holder Name *</label>
                                    <input type="text" class="form-control" name="account_holder" placeholder="John Doe" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Select Bank *</label>
                                    <select class="form-select" name="bank_name" required>
                                        <option value="">Choose a Bank</option>
                                        <option value="State Bank of India">State Bank of India (SBI)</option>
                                        <option value="HDFC Bank">HDFC Bank</option>
                                        <option value="ICICI Bank">ICICI Bank</option>
                                        <option value="Axis Bank">Axis Bank</option>
                                        <option value="Kotak Mahindra Bank">Kotak Mahindra Bank</option>
                                        <option value="Bank of Baroda">Bank of Baroda</option>
                                        <option value="Yes Bank">Yes Bank</option>
                                        <option value="IndusInd Bank">IndusInd Bank</option>
                                        <option value="Other Bank">Other Bank</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Account Number *</label>
                                    <input type="text" class="form-control" name="account_number" placeholder="Enter account number" required>
                                </div>
                                
                                <div class="col-md-12">
                                    <div class="alert alert-warning" role="alert">
                                        <strong>Bank Details Preview:</strong>
                                        <p class="mb-1"><small>Bank: <span id="bankPreview">-</span></small></p>
                                        <p class="mb-1"><small>Account: <span id="accountPreview">-</span></small></p>
                                        <p class="mb-0"><small>Amount to Transfer: <strong>$<?php echo number_format($total_amount, 2); ?></strong></small></p>
                                    </div>
                                </div>
                            </div>

                        <!-- PayPal Payment -->
                        <?php elseif ($payment_method === 'PayPal'): ?>
                            <h5 class="mb-4">Pay with PayPal</h5>
                            
                            <div class="alert alert-info" role="alert">
                                <i class="fab fa-paypal me-2"></i>
                                You will be redirected to PayPal to securely complete your payment. For demo purposes, clicking "Process Payment" will simulate a successful PayPal transaction.
                            </div>
                            <p class="text-muted">On a real store, you would log into your PayPal account and approve the payment before returning here.</p>

                        <!-- Klarna Payment -->
                        <?php elseif ($payment_method === 'Klarna'): ?>
                            <h5 class="mb-4">Pay with Klarna</h5>
                            
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-credit-card me-2"></i>
                                Split your purchase into flexible installments with Klarna. This demo flow simulates a real Klarna authorization.
                            </div>
                            <p class="text-muted">On a real store, you would review your Klarna plan, confirm your details and then authorize the payment.</p>

                        <!-- Cash on Delivery -->
                        <?php else: ?>
                            <h5 class="mb-4">Cash on Delivery</h5>
                            
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                You have selected <strong>Cash on Delivery (COD)</strong>. You can pay when your order is delivered.
                            </div>

                            <div class="card p-4 mb-4">
                                <h6 class="mb-3"><i class="fas fa-shipping-fast me-2"></i>Delivery Details</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Payment due at delivery</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>No advance payment required</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Secure & Risk-free</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Inspect before payment</li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Button -->
                        <div class="mt-5">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-3 text-uppercase w-100" id="paymentBtn">
                                <i class="fas fa-lock me-2"></i>Process Payment - $<?php echo number_format($total_amount, 2); ?>
                            </button>
                        </div>
                    </form>

                    <a href="../checkout.php" class="btn btn-outline-secondary rounded-pill px-4 py-3 text-uppercase w-100 mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                    </a>
                </div>

                <!-- Order Summary Column -->
                <div class="col-md-12 col-lg-4">
                    <div class="order-summary">
                        <h5 class="mb-4"><i class="fas fa-clipboard-list me-2"></i>Order Summary</h5>
                        
                        <div class="summary-row">
                            <span>Payment Method:</span>
                            <strong><?php echo htmlspecialchars($payment_method); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Order Amount:</span>
                            <strong>$<?php echo number_format($total_amount, 2); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Status:</span>
                            <span class="badge bg-warning">Processing</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Total Amount:</span>
                            <span>$<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>Demo Payment Info</h6>
                        <small>
                            <p class="mb-2"><strong>Test Card Numbers (for Credit/Debit Card):</strong></p>
                            <p class="mb-2">✓ 4532015112830366 (Success)</p>
                            <p class="mb-2">✓ 5425233010103442 (Success)</p>
                            <p class="mb-2">✓ 378282246310005 (Success)</p>
                            <p class="mb-2">Use any expiry date in the future and any 3-digit CVV</p>
                            <p class="mb-0 mt-2"><strong>PayPal & Klarna:</strong> These options are fully simulated – no real accounts are needed.</p>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Payment Page End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary btn-lg-square back-to-top"><i class="fa fa-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="../js/main.js"></script>

    <script>
        // Live card preview update
        document.getElementById('cardHolder')?.addEventListener('input', function() {
            document.getElementById('cardholderDisplay').textContent = this.value.toUpperCase() || 'YOUR NAME';
        });

        document.getElementById('cardNumber')?.addEventListener('input', function() {
            let value = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            this.value = formattedValue;
            
            if (value.length === 0) {
                document.getElementById('cardNumberDisplay').textContent = '•••• •••• •••• ••••';
            } else {
                let masked = value.slice(0, -4).replace(/./g, '•') + value.slice(-4);
                masked = masked.match(/.{1,4}/g)?.join(' ') || masked;
                document.getElementById('cardNumberDisplay').textContent = masked;
            }
        });

        document.getElementById('expiryMonth')?.addEventListener('change', updateExpiry);
        document.getElementById('expiryYear')?.addEventListener('change', updateExpiry);

        function updateExpiry() {
            const month = document.getElementById('expiryMonth')?.value || '';
            const year = document.getElementById('expiryYear')?.value || '';
            document.getElementById('expiryDisplay').textContent = month && year ? `${month}/${year.slice(-2)}` : 'MM/YY';
        }

        // Bank transfer preview
        document.querySelector('select[name="bank_name"]')?.addEventListener('change', function() {
            document.getElementById('bankPreview').textContent = this.value || '-';
        });

        document.querySelector('input[name="account_number"]')?.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            let masked = 'XXXX-' + (value.slice(-4) || '••••');
            document.getElementById('accountPreview').textContent = masked;
        });

        // Handle payment form submission
        function handlePaymentSubmit(event) {
            event.preventDefault();
            
            // Hide form and show processing
            document.getElementById('paymentForm').style.display = 'none';
            document.getElementById('paymentProcessing').style.display = 'block';
            
            // Simulate payment processing delay (3 seconds) before submitting
            setTimeout(() => {
                // Now submit the form after showing the processing screen
                document.getElementById('paymentForm').style.display = 'none';
                document.forms[document.forms.length - 1].submit();
            }, 3000);
            
            return false;  // Prevent immediate form submission
        }
            
        // Remove spinner on page load
        window.addEventListener('load', function() {
            document.getElementById('spinner').classList.remove('show');
        });
    </script>
</body>
</html>