<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/cart/get_cart.php';

try {
    if (is_cart_empty()) {
        redirect('cart.php');
    }

    $messages   = get_messages();
    $cart_items = get_cart_items();
    $totals     = calculate_cart_totals($cart_items, TAX_RATE, STANDARD_SHIPPING);
    $cart_total = $totals['total'];
    $user       = current_user();

    // Handle checkout submission: store in session then redirect to payment step
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceedToPayment'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            add_message('Invalid form submission. Please try again.', 'danger');
            redirect('checkout.php');
        }
        $checkout_data = [
            'full_name'      => sanitize($_POST['full_name'] ?? ''),
            'email'          => sanitize($_POST['email'] ?? ''),
            'address'        => sanitize($_POST['address'] ?? ''),
            'city'           => sanitize($_POST['city'] ?? ''),
            'state'          => sanitize($_POST['state'] ?? ''),
            'postal_code'    => sanitize($_POST['postal_code'] ?? ''),
            'phone'          => sanitize($_POST['phone'] ?? ''),
            'notes'          => sanitize($_POST['notes'] ?? ''),
            'payment_method' => $_POST['payment_method'] ?? 'COD',
            'total'          => $totals['total'],
        ];

        $_SESSION['checkout_data'] = $checkout_data;
        redirect('orders/payment.php');
        exit;
    }

} catch (Exception $e) {
    die('ERROR: ' . htmlspecialchars($e->getMessage()));
}

$page_title   = 'Checkout — PrintDepotCo';
$active_nav   = 'checkout';
$meta_noindex = true;
require_once __DIR__ . '/includes/header.php';
?>

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Checkout</h1>
    </div>
    <!-- Single Page Header End -->

    <!-- Checkout Page Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($msg['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <div class="row g-5">
                <div class="col-md-12 col-lg-8">
                    <!-- Checkout Form -->
                    <form method="post" action="checkout.php" id="checkoutForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                        <h5 class="mb-4">Billing Details</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInput" placeholder="Full Name" name="full_name" required>
                                    <label for="floatingInput">Full Name</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="floatingInputEmail" placeholder="name@example.com" name="email" required>
                                    <label for="floatingInputEmail">Email Address</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputAddress" placeholder="1234 Main St" name="address" required>
                                    <label for="floatingInputAddress">Address</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputCity" placeholder="New York" name="city" required>
                                    <label for="floatingInputCity">City</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputState" placeholder="NY" name="state" required>
                                    <label for="floatingInputState">State</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputZip" placeholder="10001" name="postal_code" required>
                                    <label for="floatingInputZip">Postal Code</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="floatingInputPhone" placeholder="+1234567890" name="phone" required>
                                    <label for="floatingInputPhone">Phone</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check d-flex bg-light rounded p-3">
                                    <input class="form-check-input bg-primary border-0" type="checkbox" id="Account" name="Account" value="Account">
                                    <label class="form-check-label pt-1 ps-2" for="Account">
                                        Create an account
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a comment here" id="floatingTextarea" name="notes"></textarea>
                                    <label for="floatingTextarea">Order Notes (Optional)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <h5 class="mb-4 mt-5">Select Payment Method</h5>
                        <div class="row g-4 text-start mb-4">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="COD" value="COD" checked>
                                    <label class="form-check-label pt-1" for="COD">
                                        <strong>Cash on Delivery (COD)</strong>
                                        <p class="text-muted small mb-0">Pay in cash when your order is delivered.</p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="PayPal" value="PayPal">
                                    <label class="form-check-label pt-1" for="PayPal">
                                        <strong>PayPal</strong>
                                        <p class="text-muted small mb-0">Pay securely using your PayPal account.</p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="Card" value="Credit Card">
                                    <label class="form-check-label pt-1" for="Card">
                                        <strong>Credit/Debit Card</strong>
                                        <p class="text-muted small mb-0">Visa, Mastercard, or American Express.</p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="Klarna" value="Klarna">
                                    <label class="form-check-label pt-1" for="Klarna">
                                        <strong>Klarna</strong>
                                        <p class="text-muted small mb-0">Buy now, pay later with Klarna.</p>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Proceed to Payment Button -->
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-3 text-uppercase w-100" name="proceedToPayment">
                            <i class="fas fa-credit-card me-2"></i>Proceed to Payment
                        </button>
                    </form>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="col-md-12 col-lg-4">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Products</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 mt-4"><?php echo htmlspecialchars($item['name']); ?></p>
                                    </th>
                                    <td>
                                        <p class="mb-0 mt-4"><?php echo (int)$item['quantity']; ?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 mt-4">$<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></p>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-4">Subtotal</p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-4">$<?php echo number_format((float)$totals['subtotal'], 2); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-4">Shipping</p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-4">Flat Rate: $<?php echo number_format((float)$totals['shipping'], 2); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-4">Tax</p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-4">$<?php echo number_format((float)$totals['tax'], 2); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-3"><strong>Total</strong></p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-3"><strong>$<?php echo number_format((float)$totals['total'], 2); ?></strong></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="cart.php" class="btn btn-outline-primary rounded-pill px-4 py-3 text-uppercase w-100 mt-2">Back to Cart</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Checkout Page End -->
    
<?php
$extra_foot = <<<'JS'
<script>
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    var errors = [];
    var email = document.querySelector('[name=email]').value.trim();
    var phone = document.querySelector('[name=phone]').value.trim();
    var postal = document.querySelector('[name=postal_code]').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errors.push('Please enter a valid email address.');
    if (phone && !/^[\d\+\-\(\)\s]{7,}$/.test(phone)) errors.push('Please enter a valid phone number.');
    if (postal && !/^[A-Za-z0-9\s\-]{3,10}$/.test(postal)) errors.push('Please enter a valid postal code.');
    if (errors.length) {
        e.preventDefault();
        var div = document.getElementById('checkout-errors');
        if (!div) { div = document.createElement('div'); div.id = 'checkout-errors'; div.className = 'alert alert-danger'; document.getElementById('checkoutForm').prepend(div); }
        div.innerHTML = errors.join('<br>');
        div.scrollIntoView({behavior:'smooth'});
    }
});
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>