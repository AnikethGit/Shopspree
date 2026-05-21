<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../cart/get_cart.php';

if (!isset($_SESSION['checkout_data'])) {
    add_message('Please complete checkout first', 'error');
    redirect('../checkout.php');
}

$checkout_data  = $_SESSION['checkout_data'];
$payment_method = $checkout_data['payment_method'];
$total_amount   = $checkout_data['total'];
$messages       = get_messages();

function simulate_payment_processing(string $method): array {
    return ['success' => true, 'message' => 'Payment processed successfully', 'status' => 'Completed'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_details = [];

    if ($payment_method === 'Credit Card') {
        $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $card_holder = sanitize($_POST['card_holder'] ?? '');
        $payment_details = [
            'card_number' => 'XXXX-XXXX-XXXX-' . substr($card_number ?: '0000', -4),
            'card_holder' => !empty($card_holder) ? $card_holder : 'Demo User',
            'expiry'      => (intval($_POST['expiry_month'] ?? 1) . '/' . intval($_POST['expiry_year'] ?? (date('Y') + 1))),
        ];
    } elseif ($payment_method === 'Bank Transfer') {
        $account_number = preg_replace('/\D/', '', $_POST['account_number'] ?? '');
        $payment_details = [
            'account_holder' => sanitize($_POST['account_holder'] ?? 'Demo Account'),
            'account_number' => 'XXXX-XXXX-' . substr($account_number ?: '0000', -4),
            'bank_name'      => sanitize($_POST['bank_name'] ?? 'Demo Bank'),
        ];
    }

    $result = simulate_payment_processing($payment_method);

    if ($result['success']) {
        $_SESSION['payment_processed'] = true;
        $_SESSION['payment_details']   = $payment_details;
        $_SESSION['transaction_id']    = 'TXN-' . strtoupper(uniqid());
        $_SESSION['payment_status']    = 'Completed';
        redirect('create.php');
    } else {
        add_message('Payment failed: ' . htmlspecialchars($result['message']), 'error');
    }
}

$page_title   = 'Payment — ' . SITE_NAME;
$active_nav   = 'checkout';
$meta_noindex = true;
$extra_head   = '<style>
    .payment-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .card-number-display { font-size: 24px; letter-spacing: 2px; font-family: "Courier New", monospace; margin-bottom: 20px; margin-top: 30px; }
    .card-row { display: flex; justify-content: space-between; margin-top: 10px; }
    .card-field { flex: 1; font-size: 12px; }
    .card-field label { opacity: 0.8; display: block; font-size: 10px; margin-bottom: 3px; }
    .card-field-value { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
    .payment-method-section { background-color: #f8f9fa; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
    .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
    .security-info { display: flex; align-items: center; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #2196F3; border-radius: 5px; margin-bottom: 20px; }
    .security-info i { color: #2196F3; margin-right: 10px; font-size: 20px; }
    .order-summary { background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
    .summary-row.total { font-weight: bold; font-size: 18px; border-bottom: 2px solid #667eea; padding: 12px 0; }
    .payment-processing { text-align: center; padding: 40px; display: none; }
    .spinner-border-lg { width: 3rem; height: 3rem; }
    #paymentForm.hidden { display: none; }
</style>';

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- Single Page Header -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Payment</h1>
    </div>

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
                <!-- Payment Form -->
                <div class="col-md-12 col-lg-8">
                    <div class="security-info">
                        <i class="fas fa-lock"></i>
                        <span><strong>Secure Payment:</strong> Your payment information is encrypted and secure.</span>
                    </div>

                    <!-- Processing Overlay -->
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

                        <?php if ($payment_method === 'Credit Card'): ?>
                            <h5 class="mb-4">Credit/Debit Card Details</h5>
                            <div class="payment-card" id="cardPreview">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div style="font-size:12px;opacity:0.8;">Card Number</div>
                                        <div class="card-number-display" id="cardNumberDisplay">•••• •••• •••• ••••</div>
                                    </div>
                                    <div style="font-size:30px;">💳</div>
                                </div>
                                <div class="card-row">
                                    <div class="card-field">
                                        <label>Cardholder Name</label>
                                        <div class="card-field-value" id="cardholderDisplay">YOUR NAME</div>
                                    </div>
                                    <div class="card-field" style="text-align:right;">
                                        <label>Expires</label>
                                        <div class="card-field-value" id="expiryDisplay">MM/YY</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Cardholder Name *</label>
                                    <input type="text" class="form-control" name="card_holder" id="cardHolder" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Card Number *</label>
                                    <input type="text" class="form-control" name="card_number" id="cardNumber" placeholder="xxxx-xxxx-xxxx-xxxx" maxlength="19" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Month *</label>
                                    <select class="form-select" name="expiry_month" id="expiryMonth" required>
                                        <option value="">Select Month</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Expiry Year *</label>
                                    <select class="form-select" name="expiry_year" id="expiryYear" required>
                                        <option value="">Select Year</option>
                                        <?php for ($i = 0; $i < 10; $i++): $year = date('Y') + $i; ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">CVV (3-4 digits) *</label>
                                    <input type="text" class="form-control" name="cvv" id="cvv" maxlength="4" required>
                                </div>
                            </div>

                        <?php elseif ($payment_method === 'Bank Transfer'): ?>
                            <h5 class="mb-4">Bank Transfer Details</h5>
                            <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Enter your bank details. The order will be created pending transfer confirmation.</div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Account Holder Name *</label>
                                    <input type="text" class="form-control" name="account_holder" placeholder="John Doe" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Select Bank *</label>
                                    <select class="form-select" name="bank_name" required>
                                        <option value="">Choose a Bank</option>
                                        <option>HDFC Bank</option><option>ICICI Bank</option><option>Axis Bank</option>
                                        <option>Kotak Mahindra Bank</option><option>Yes Bank</option><option>Other Bank</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Account Number *</label>
                                    <input type="text" class="form-control" name="account_number" placeholder="Enter account number" required>
                                </div>
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <strong>Preview:</strong>
                                        <p class="mb-1"><small>Bank: <span id="bankPreview">-</span></small></p>
                                        <p class="mb-0"><small>Amount: <strong><?php echo format_price($total_amount); ?></strong></small></p>
                                    </div>
                                </div>
                            </div>

                        <?php elseif ($payment_method === 'PayPal'): ?>
                            <h5 class="mb-4">Pay with PayPal</h5>
                            <div class="alert alert-info"><i class="fab fa-paypal me-2"></i>You will be redirected to PayPal to securely complete your payment.</div>
                            <p class="text-muted">Log into your PayPal account and approve the payment before returning here.</p>

                        <?php elseif ($payment_method === 'Klarna'): ?>
                            <h5 class="mb-4">Pay with Klarna</h5>
                            <div class="alert alert-info"><i class="fas fa-credit-card me-2"></i>Split your purchase into flexible installments with Klarna.</div>
                            <p class="text-muted">Review your Klarna plan, confirm your details, then authorize the payment.</p>

                        <?php else: ?>
                            <h5 class="mb-4">Cash on Delivery</h5>
                            <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>You selected <strong>Cash on Delivery</strong>. Pay when your order is delivered.</div>
                            <div class="card p-4 mb-4">
                                <h6 class="mb-3"><i class="fas fa-shipping-fast me-2"></i>Delivery Details</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Payment due at delivery</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>No advance payment required</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Secure &amp; Risk-free</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Inspect before payment</li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="mt-5">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 py-3 text-uppercase w-100" id="paymentBtn">
                                <i class="fas fa-lock me-2"></i>Process Payment — <?php echo format_price($total_amount); ?>
                            </button>
                        </div>
                    </form>

                    <a href="../checkout.php" class="btn btn-outline-secondary rounded-pill px-4 py-3 text-uppercase w-100 mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Checkout
                    </a>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="col-md-12 col-lg-4">
                    <div class="order-summary">
                        <h5 class="mb-4"><i class="fas fa-clipboard-list me-2"></i>Order Summary</h5>
                        <div class="summary-row">
                            <span>Payment Method:</span>
                            <strong><?php echo htmlspecialchars($payment_method); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Order Amount:</span>
                            <strong><?php echo format_price($total_amount); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Status:</span>
                            <span class="badge bg-warning">Processing</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount:</span>
                            <span><?php echo format_price($total_amount); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Payment Page End -->

<?php
$extra_foot = <<<'JS'
<script>
document.getElementById('cardHolder')?.addEventListener('input', function() {
    document.getElementById('cardholderDisplay').textContent = this.value.toUpperCase() || 'YOUR NAME';
});
document.getElementById('cardNumber')?.addEventListener('input', function() {
    let v = this.value.replace(/\s+/g,'').replace(/[^0-9]/gi,'');
    this.value = v.match(/.{1,4}/g)?.join(' ') || v;
    document.getElementById('cardNumberDisplay').textContent =
        v.length ? (v.slice(0,-4).replace(/./g,'•') + v.slice(-4)).match(/.{1,4}/g)?.join(' ') : '•••• •••• •••• ••••';
});
document.getElementById('expiryMonth')?.addEventListener('change', updateExpiry);
document.getElementById('expiryYear')?.addEventListener('change', updateExpiry);
function updateExpiry() {
    const m = document.getElementById('expiryMonth')?.value || '';
    const y = document.getElementById('expiryYear')?.value || '';
    document.getElementById('expiryDisplay').textContent = m && y ? `${m}/${y.slice(-2)}` : 'MM/YY';
}
document.querySelector('select[name="bank_name"]')?.addEventListener('change', function() {
    document.getElementById('bankPreview').textContent = this.value || '-';
});
function handlePaymentSubmit(event) {
    event.preventDefault();
    document.getElementById('paymentForm').style.display = 'none';
    document.getElementById('paymentProcessing').style.display = 'block';
    setTimeout(() => { event.target.submit(); }, 2500);
    return false;
}
</script>
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
