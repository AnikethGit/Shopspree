# Shopspree Payment System - Customization Guide

## Overview
This guide shows how to customize the payment system to match your specific business needs.

## 📌 Customization Options

## 1. Change Success/Failure Rate

**File**: `orders/payment.php` (around line 115)

**Current**: 95% success rate

```php
// BEFORE
function simulate_payment_processing($payment_method) {
    $random = mt_rand(1, 100);
    
    if ($random <= 95) {  // 95% success
        return ['success' => true, ...];
    }
}

// AFTER - Change 95 to your desired percentage
// 50 = 50% success rate
// 99 = 99% success rate
if ($random <= 75) {  // 75% success
    return ['success' => true, ...];
}
```

## 2. Customize Payment Methods

### Add/Remove Payment Methods

**File**: `checkout.php` (line ~290)

```php
<!-- REMOVE this section to disable COD -->
<div class="col-12">
    <div class="form-check">
        <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="COD" value="COD" checked>
        <label class="form-check-label pt-1" for="COD">
            <strong>Cash on Delivery (COD)</strong>
        </label>
    </div>
</div>

<!-- ADD this to add a new payment method -->
<div class="col-12">
    <div class="form-check">
        <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="Wallet" value="Digital Wallet">
        <label class="form-check-label pt-1" for="Wallet">
            <strong>Digital Wallet</strong>
        </label>
    </div>
</div>
```

### Add New Payment Method Logic

**File**: `orders/payment.php` (around line 60)

```php
// ADD this block after Bank Transfer validation
elseif ($payment_method === 'Digital Wallet') {
    // Validate wallet details
    $wallet_id = sanitize($_POST['wallet_id'] ?? '');
    $wallet_provider = sanitize($_POST['wallet_provider'] ?? '');
    
    if (empty($wallet_id) || strlen($wallet_id) < 5) {
        $errors[] = 'Invalid wallet ID';
    }
    
    if (empty($wallet_provider)) {
        $errors[] = 'Please select a wallet provider';
    }
    
    if (empty($errors)) {
        $payment_details = [
            'wallet_id' => 'XXXX-' . substr($wallet_id, -4),
            'wallet_provider' => $wallet_provider
        ];
    }
}
```

## 3. Customize Banks List

**File**: `orders/payment.php` (around line 340)

```php
<!-- CURRENT -->
<select class="form-select" name="bank_name" required>
    <option value="">Choose a Bank</option>
    <option value="State Bank of India">State Bank of India (SBI)</option>
    <option value="HDFC Bank">HDFC Bank</option>
    <!-- ... more banks ... -->
</select>

<!-- CUSTOMIZED - Add your banks -->
<select class="form-select" name="bank_name" required>
    <option value="">Choose a Bank</option>
    <option value="My Bank 1">My Bank 1</option>
    <option value="My Bank 2">My Bank 2</option>
    <option value="My Bank 3">My Bank 3</option>
</select>
```

## 4. Customize Failure Reasons

**File**: `orders/payment.php` (around line 130)

```php
// CURRENT
$failure_reasons = [
    'Insufficient funds',
    'Card declined',
    'Invalid security code',
    'Transaction timeout'
];

// CUSTOMIZE
$failure_reasons = [
    'Your custom reason 1',
    'Your custom reason 2',
    'Your custom reason 3'
];
```

## 5. Change Form Fields

### Add Required Fields

**File**: `checkout.php` (around line 150)

```php
<!-- ADD new field -->
<div class="col-md-12">
    <div class="form-floating">
        <input type="text" class="form-control" id="company" placeholder="Company Name" name="company" required>
        <label for="company">Company Name</label>
    </div>
</div>
```

### Update Backend Validation

**File**: `orders/payment.php` (around line 20)

```php
// ADD to required fields array
$required_fields = [
    'email', 'full_name', 'phone', 'address',
    'city', 'state', 'postal_code',
    'payment_method',
    'company'  // NEW FIELD
];

// ADD to validation
if (empty($_POST['company'])) {
    $errors[] = 'Company name is required';
} else {
    $checkout_data['company'] = sanitize($_POST['company']);
}
```

## 6. Customize Error Messages

### Change Validation Messages

**File**: `orders/payment.php`

```php
// Line ~95 - Change card validation messages
if (!validate_card_number($card_number)) {
    $errors[] = 'Please enter a valid card number';  // Customize this
}

// Line ~103 - Change expiry message
if ($expiry_year < $current_year || ...) {
    $errors[] = 'Your card has expired or is invalid';  // Customize this
}

// Line ~108 - Change CVV message
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    $errors[] = 'CVV must be 3 or 4 digits';  // Customize this
}
```

## 7. Customize Email Receipts

**File**: `orders/send_receipt.php`

```php
// Find the email body and customize
$email_body = "
    <h2>Thank you for your order!</h2>
    <p>Order ID: {order_id}</p>
    <!-- Customize the entire email template -->
";
```

## 8. Change Test Card Numbers

**File**: `orders/payment.php` (around line 520)

```php
<!-- CURRENT INFO BOX -->
<small>
    <p class="mb-2"><strong>Test Card Numbers:</strong></p>
    <p class="mb-2">✓ 4532015112830366 (Success)</p>
    <p class="mb-2">✓ 5425233010103442 (Success)</p>
    <p class="mb-2">✓ 378282246310005 (Success)</p>
</small>

<!-- CUSTOMIZE -->
<small>
    <p class="mb-2"><strong>Test Card Numbers:</strong></p>
    <p class="mb-2">✓ Your Card 1</p>
    <p class="mb-2">✓ Your Card 2</p>
</small>
```

## 9. Modify Transaction ID Format

**File**: `orders/payment.php` (around line 55)

```php
// CURRENT
$transaction_id = 'TXN-' . strtoupper(uniqid());

// CUSTOMIZE - Add timestamp
$transaction_id = 'TXN-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -8));

// OR - Add custom prefix
$transaction_id = 'PAY-' . strtoupper(uniqid());

// OR - Add random number
$transaction_id = 'TXN-' . mt_rand(100000, 999999);
```

## 10. Customize Order Statuses

**File**: `orders/create.php` (around line 75)

```php
// CURRENT
$order_status = ($payment_method === 'COD') ? 'Pending' : 'Payment Received';

// CUSTOMIZE
$order_status = ($payment_method === 'COD') ? 'Awaiting Payment' : 'Confirmed';

// OR more detailed
if ($payment_method === 'COD') {
    $order_status = 'Pending Delivery';
} elseif ($payment_method === 'Bank Transfer') {
    $order_status = 'Awaiting Confirmation';
} else {
    $order_status = 'Processing';
}
```

## 11. Change Payment Form Styling

**File**: `orders/payment.php` (in `<style>` tag around line 80)

```php
/* CUSTOMIZE card colors */
.payment-card {
    /* CHANGE from purple to your brand color */
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* TO your colors */
    background: linear-gradient(135deg, #FF6B6B 0%, #EE5A6F 100%);
}

/* CUSTOMIZE button colors */
.btn-primary {
    background-color: #667eea;  /* Change this */
    border-color: #667eea;      /* Change this */
}
```

## 12. Add Custom Validation

**File**: `orders/payment.php` (add new validation functions)

```php
// ADD custom validation function
function validate_phone($phone) {
    // Phone must be 10 digits
    $phone = preg_replace('/\D/', '', $phone);
    return strlen($phone) === 10;
}

// USE in payment form validation
if (!validate_phone($_POST['phone'] ?? '')) {
    $errors[] = 'Phone must be 10 digits';
}
```

## 13. Customize Payment Summary

**File**: `orders/payment.php` (around line 450)

```php
<!-- CURRENT -->
<div class="summary-row">
    <span>Order Amount:</span>
    <strong>$<?php echo number_format($total_amount, 2); ?></strong>
</div>

<!-- ADD more details -->
<div class="summary-row">
    <span>Subtotal:</span>
    <strong>$<?php echo number_format($total_amount * 0.9, 2); ?></strong>
</div>
<div class="summary-row">
    <span>Tax (10%):</span>
    <strong>$<?php echo number_format($total_amount * 0.1, 2); ?></strong>
</div>
```

## 14. Add Custom Business Logic

### Example: Apply Coupon Discount

**File**: `orders/payment.php` (around line 50)

```php
// ADD coupon validation
if (!empty($_POST['coupon_code'])) {
    $coupon = validate_coupon($_POST['coupon_code']);
    if ($coupon) {
        $total_amount = $total_amount - ($total_amount * $coupon['discount'] / 100);
        $_SESSION['coupon_applied'] = $coupon['code'];
    } else {
        $errors[] = 'Invalid coupon code';
    }
}
```

### Add Loyalty Points

**File**: `orders/create.php` (around line 110)

```php
// AFTER order is created, add loyalty points
if (!is_null($user_id)) {
    $points = floor($totals['total'] / 10);  // 1 point per $10
    $update_points = "UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?";
    if ($stmt = $conn->prepare($update_points)) {
        $stmt->bind_param("ii", $points, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
```

## 15. Add Security Features

### Add Rate Limiting

**File**: `orders/payment.php` (at top of file after requires)

```php
// Check if user is trying too many payments
$ip = $_SERVER['REMOTE_ADDR'];
$attempts_key = 'payment_attempts_' . $ip;

if (!isset($_SESSION[$attempts_key])) {
    $_SESSION[$attempts_key] = 0;
}

$_SESSION[$attempts_key]++;

if ($_SESSION[$attempts_key] > 5) {
    add_message('Too many payment attempts. Please try again later.', 'error');
    redirect('../checkout.php');
}
```

### Add CSRF Token

**File**: `checkout.php` (in form)

```php
<!-- ADD CSRF token to form -->
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
```

**File**: `orders/payment.php` (validation)

```php
// Validate CSRF token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid request');
}
```

## Quick Customization Checklist

- [ ] Change success rate percentage
- [ ] Customize payment methods list
- [ ] Update banks list
- [ ] Change failure reasons
- [ ] Customize error messages
- [ ] Update form styling/colors
- [ ] Modify email template
- [ ] Add business logic
- [ ] Add security features
- [ ] Test all changes

## Common Customizations

### For India-based Business
```php
// Change banks to Indian banks
<option value="State Bank of India">SBI</option>
<option value="HDFC Bank">HDFC</option>
<option value="ICICI Bank">ICICI</option>

// Change currency display
₹<?php echo number_format($total_amount, 2); ?>

// Add GST instead of tax
$gst_rate = 0.18;  // 18% GST
$gst = round($subtotal * $gst_rate, 2);
```

### For US-based Business
```php
// Keep existing card options
// Add payment methods like Venmo, Apple Pay

// Keep USD currency
$<?php echo number_format($total_amount, 2); ?>

// State selector for US states
```

### For B2B Transactions
```php
// Add company name field
// Add tax ID field
// Add PO number field
// Change invoice terms
```

## Testing After Customization

1. **Test all payment methods**
   - Try each one end-to-end
   - Check database for data storage

2. **Test validation**
   - Try invalid inputs
   - Verify error messages

3. **Test edge cases**
   - Very large amounts
   - Special characters
   - Empty fields

4. **Test database**
   - Check order creation
   - Verify payment details stored
   - Check order items

5. **Test email**
   - Check email receipt
   - Verify all details included

## Need Help?

See `PAYMENT_SYSTEM_DOCS.md` for detailed information.

---
**Version**: 1.0
**Last Updated**: March 2026
