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
    
    // Validate payment method
    if ($payment_method === 'Credit Card') {
        // Validate card details
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $card_holder = sanitize($_POST['card_holder'] ?? '');
        $expiry_month = intval($_POST['expiry_month'] ?? 0);
        $expiry_year = intval($_POST['expiry_year'] ?? 0);
        $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
        
        // Validate card number (Luhn algorithm for basic validation)
        if (!validate_card_number($card_number)) {
            $errors[] = 'Invalid card number';
        }
        
        // Validate card holder name
        if (empty($card_holder) || strlen($card_holder) < 3) {
            $errors[] = 'Invalid cardholder name';
        }
        
        // Validate expiry date
        $current_month = date('n');
        $current_year = date('Y');
        if ($expiry_year < $current_year || ($expiry_year === $current_year && $expiry_month < $current_month)) {
            $errors[] = 'Card has expired';
        }
        
        // Validate CVV
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'Invalid CVV';
        }
        
        if (empty($errors)) {
            // Dummy payment success - mask card number for storage
            $masked_card = 'XXXX-XXXX-XXXX-' . substr($card_number, -4);
            $payment_details = [
                'card_number' => $masked_card,
                'card_holder' => $card_holder,
                'expiry' => $expiry_month . '/' . $expiry_year
            ];
        }
        
    } elseif ($payment_method === 'Bank Transfer') {
        // Validate bank details
        $account_holder = sanitize($_POST['account_holder'] ?? '');
        $account_number = preg_replace('/\D/', '', $_POST['account_number'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        
        if (empty($account_holder) || strlen($account_holder) < 3) {
            $errors[] = 'Invalid account holder name';
        }
        
        if (empty($account_number) || strlen($account_number) < 8) {
            $errors[] = 'Invalid account number';
        }
        
        if (empty($bank_name)) {
            $errors[] = 'Please select a bank';
        }
        
        if (empty($errors)) {
            // Dummy payment success - mask account number for storage
            $masked_account = 'XXXX-XXXX-' . substr($account_number, -4);
            $payment_details = [
                'account_holder' => $account_holder,
                'account_number' => $masked_account,
                'bank_name' => $bank_name
            ];
        }
        
    } elseif ($payment_method === 'COD') {
        // COD doesn't need payment details
        $payment_details = [];
    }
    
    // If validation passed, process order
    if (empty($errors)) {
        // Simulate payment processing with dummy transaction
        $transaction_id = 'TXN-' . strtoupper(uniqid());
        $payment_status = simulate_payment_processing($payment_method);
        
        if ($payment_status['success']) {
            // Store payment info in session for order creation
            $_SESSION['payment_processed'] = true;
            $_SESSION['payment_details'] = $payment_details;
            $_SESSION['transaction_id'] = $transaction_id;
            $_SESSION['payment_status'] = 'Completed';
            
            // Redirect to create order
            redirect('create.php');
        } else {
            // Dummy payment failed
            add_message('Payment processing failed: ' . $payment_status['message'], 'error');
        }
    } else {
        // Show validation errors
        foreach ($errors as $error) {
            add_message($error, 'error');
        }
    }
}

/**
 * Validate credit card using Luhn algorithm
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
 * Returns success/failure randomly for realistic experience
 */
function simulate_payment_processing($payment_method) {
    // Simulate 95% success rate for demonstration
    $random = mt_rand(1, 100);
    
    if ($random <= 95) {
        return [
            'success' => true,
            'message' => 'Payment processed successfully',
            'status' => 'Completed'
        ];
    } else {
        // Simulate occasional failures
        $failure_reasons = [
            'Insufficient funds',
            'Card declined',
            'Invalid security code',
            'Transaction timeout'
        ];
        
        return [
            'success' => false,
            'message' => $failure_reasons[array_rand($failure_reasons)],
            'status' => 'Failed'
        ];
    }
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
        }
        
        .spinner-border-lg {
            width: 3rem;
            height: 3rem;
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

                    <form method="post" action="payment.php" id="paymentForm" onsubmit="handlePaymentSubmit(event)">
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

                        <!-- Bank Transfer Payment -->
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

                        <!-- Loading Indicator -->
                        <div class="payment-processing" id="paymentProcessing" style="display: none;">
                            <div class="spinner-border spinner-border-lg text-primary mb-3" role="status">
                                <span class="sr-only">Processing...</span>
                            </div>
                            <p><strong>Processing your payment...</strong></p>
                            <p class="text-muted small">Please wait, do not refresh this page</p>
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
                            <p class="mb-2"><strong>Test Card Numbers:</strong></p>
                            <p class="mb-2">✓ 4532015112830366 (Success)</p>
                            <p class="mb-2">✓ 5425233010103442 (Success)</p>
                            <p class="mb-2">✓ 378282246310005 (Success)</p>
                            <p class="mb-0">Use any expiry date in the future and any 3-digit CVV</p>
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
            
            // Show processing spinner
            document.getElementById('paymentForm').style.display = 'none';
            document.getElementById('paymentProcessing').style.display = 'block';
            
            // Simulate payment processing (2-3 seconds)
            setTimeout(() => {
                document.getElementById('paymentForm').submit();
            }, 2500);
        }

        // Remove spinner on page load
        window.addEventListener('load', function() {
            document.getElementById('spinner').classList.remove('show');
        });
    </script>
</body>
</html>
