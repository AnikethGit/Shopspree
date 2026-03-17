# Shopspree Dummy Payment System Documentation

## Overview
The payment system in Shopspree is a **realistic dummy payment gateway** that mimics real payment processing without actual transaction handling. Users experience a complete payment flow that looks and feels like a real payment system, but all transactions are simulated in the background.

## System Architecture

### Flow Diagram
```
Checkout Page
    ↓
User fills billing details & selects payment method
    ↓
"Proceed to Payment" button
    ↓
Payment Processing Page (payment.php)
    ↓
User enters payment details (card, bank, or COD)
    ↓
Form validation & dummy payment processing
    ↓
Transaction ID generated (TXN-xxxxx)
    ↓
Payment status stored in session
    ↓
Order Creation (create.php)
    ↓
Order record created with payment details
    ↓
Order items added, stock updated
    ↓
Receipt email sent
    ↓
Thank You Page
```

## File Structure

```
Shopspree/
├── checkout.php                    # Billing details collection
├── orders/
│   ├── payment.php                 # Payment processing page (NEW)
│   ├── create.php                  # Order creation after payment (UPDATED)
│   ├── thank_you.php               # Order confirmation
│   └── send_receipt.php            # Email receipt
├── config/
│   ├── payment_migration.sql       # Database schema updates (NEW)
│   ├── helpers.php                 # Helper functions
│   └── db.php                      # Database connection
└── css/
    └── style.css                   # Styles
```

## Payment Methods Supported

### 1. Cash on Delivery (COD)
- **Description**: User pays when order is delivered
- **Process**: 
  - No payment validation required
  - Order status: "Pending"
  - Payment status: "Completed" (no actual payment)
- **Database**: No sensitive data stored

### 2. Credit/Debit Card
- **Description**: Simulated credit card payment
- **Validation**:
  - Luhn algorithm for card number validation
  - Expiry date validation
  - CVV validation (3-4 digits)
  - Cardholder name validation
- **Test Card Numbers**:
  - `4532015112830366` → Success
  - `5425233010103442` → Success
  - `378282246310005` → Success
  - Any invalid format → Will fail validation
- **Storage**: Last 4 digits masked as `XXXX-XXXX-XXXX-1234`
- **Validation Rate**: 95% success, 5% failure (simulated)

### 3. Bank Transfer
- **Description**: Direct bank/net banking transfer
- **Validation**:
  - Account holder name (min 3 characters)
  - Bank selection from dropdown
  - Account number (min 8 digits)
- **Banks Supported**:
  - State Bank of India (SBI)
  - HDFC Bank
  - ICICI Bank
  - Axis Bank
  - Kotak Mahindra Bank
  - Bank of Baroda
  - Yes Bank
  - IndusInd Bank
  - Other Bank
- **Storage**: Account number masked as `XXXX-XXXX-1234`
- **Order Status**: "Payment Received" (pending confirmation)

## Database Schema Changes

### Updated `orders` Table
```sql
ALTER TABLE orders ADD COLUMN `payment_status` VARCHAR(50);
ALTER TABLE orders ADD COLUMN `transaction_id` VARCHAR(100) UNIQUE;
ALTER TABLE orders ADD COLUMN `payment_details` LONGTEXT;
```

### New Columns
- **payment_status**: `Pending`, `Completed`, `Failed`, `Refunded`
- **transaction_id**: Unique identifier `TXN-xxxxx` for each transaction
- **payment_details**: JSON object containing masked payment info
  ```json
  {
    "card_number": "XXXX-XXXX-XXXX-1234",
    "card_holder": "John Doe",
    "expiry": "12/25"
  }
  ```

### New Tables (Optional)
- **payment_history**: Audit trail of all payment attempts
- **refunds**: Refund tracking and history

## Installation & Setup

### Step 1: Run Database Migration
```bash
# Option A: Using MySQL client
mysql -u root -p your_database_name < config/payment_migration.sql

# Option B: Using phpMyAdmin
# 1. Open phpMyAdmin
# 2. Go to your database
# 3. Click "Import" tab
# 4. Select config/payment_migration.sql
# 5. Click "Import"
```

### Step 2: Verify Database
```sql
-- Check if columns exist
SHOW COLUMNS FROM orders;

-- You should see:
-- payment_status
-- transaction_id
-- payment_details
```

### Step 3: Test the Payment System
1. Go to your Shopspree site
2. Add items to cart
3. Click "Checkout"
4. Fill billing details
5. Select payment method
6. Click "Proceed to Payment"
7. On payment page:
   - If **COD**: Just proceed
   - If **Card**: Use test card number `4532015112830366`
   - If **Bank**: Enter any valid details
8. Click "Process Payment"
9. Should see thank you page

## Payment Processing Flow

### Frontend Flow
```
1. User on Checkout page
   ↓
2. Fills: Name, Email, Address, Phone, etc.
   ↓
3. Selects Payment Method (radio button)
   ↓
4. Clicks "Proceed to Payment"
   ↓
5. Redirected to payment.php with checkout_data in SESSION
   ↓
6. Payment form shows based on selected method
   ↓
7. User fills payment details
   ↓
8. Form validation on client-side
   ↓
9. Submits form
```

### Backend Flow
```
1. payment.php receives POST request
   ↓
2. Validate payment details (backend)
   ↓
3. If validation fails → Show error messages
   ↓
4. If validation passes → Simulate payment processing
   ↓
5. Generate Transaction ID (TXN-xxxxx)
   ↓
6. Store payment info in SESSION:
   - payment_processed = true
   - payment_details = masked data
   - transaction_id = TXN-xxxxx
   - payment_status = Completed
   ↓
7. Redirect to orders/create.php
   ↓
8. create.php checks SESSION for payment_processed
   ↓
9. If valid, create order record with:
   - Order ID
   - Payment method
   - Transaction ID
   - Masked payment details
   - Payment status: Completed
   ↓
10. Add order items
    ↓
11. Update product stock
    ↓
12. Send receipt email
    ↓
13. Clear SESSION payment data
    ↓
14. Redirect to thank_you.php
```

## Payment Validation Logic

### Credit Card Validation
```php
// Luhn Algorithm (Mod-10 validation)
function validate_card_number($card_number) {
    // Remove non-digit characters
    $card = preg_replace('/\D/', '', $card_number);
    
    // Check length
    if (strlen($card) < 13 || strlen($card) > 19) return false;
    
    // Luhn algorithm
    $sum = 0;
    $add_digit = 0;
    
    for ($i = strlen($card) - 1; $i >= 0; $i--) {
        $digit = (int)$card[$i];
        if ($add_digit % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) $digit -= 9;
        }
        $sum += $digit;
        $add_digit++;
    }
    
    return ($sum % 10 === 0);
}
```

### Expiry Date Validation
```php
// Check if card is expired
$current_month = date('n');
$current_year = date('Y');

if ($expiry_year < $current_year || 
    ($expiry_year === $current_year && $expiry_month < $current_month)) {
    // Card expired
}
```

### CVV Validation
```php
// CVV must be 3-4 digits
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    // Invalid CVV
}
```

## Session Management

### Session Variables Used

**During Checkout**
```php
$_SESSION['cart']           // Shopping cart items
$_SESSION['user_id']        // If user logged in
```

**During Payment**
```php
$_SESSION['checkout_data']  // Billing info from checkout.php
// {
//   'full_name', 'email', 'phone', 'address',
//   'city', 'state', 'postal_code',
//   'payment_method', 'notes'
// }
```

**After Payment Processing**
```php
$_SESSION['payment_processed']  // true if payment succeeded
$_SESSION['payment_details']    // Masked card/bank info
$_SESSION['transaction_id']     // TXN-xxxxx
$_SESSION['payment_status']     // "Completed"
```

**After Order Creation**
```php
$_SESSION['last_order_id']      // Order ID string (ORD-xxxxx)
$_SESSION['last_order_db_id']   // Database ID of order
$_SESSION['receipt_sent']       // Whether email was sent

// Cleared:
unset($_SESSION['cart']);
unset($_SESSION['checkout_data']);
unset($_SESSION['payment_processed']);
unset($_SESSION['payment_details']);
unset($_SESSION['transaction_id']);
```

## Payment Details Storage

### What Gets Stored in Database
```json
{
  "card_number": "XXXX-XXXX-XXXX-1234",
  "card_holder": "John Doe",
  "expiry": "12/2025"
}
```

### What Does NOT Get Stored
- Full card number
- CVV
- Bank account number (full)
- Sensitive credentials

All sensitive data is masked before storage for security!

## Error Handling

### Payment Validation Errors
- Invalid card number → "Invalid card number"
- Expired card → "Card has expired"
- Invalid CVV → "Invalid CVV"
- Invalid cardholder name → "Invalid cardholder name"
- Invalid bank details → "Invalid account holder name/number"

### Order Creation Errors
- Cart empty → "Your cart is empty"
- Product not found → "Product not found"
- Out of stock → "Product [name] is out of stock"
- Payment not processed → "Payment must be processed first"

### All Errors Use Message System
```php
add_message($error_text, 'error');
redirect('checkout.php');
```

## Testing the Payment System

### Test Scenarios

#### Scenario 1: COD Order
1. Add items to cart
2. Go to checkout
3. Fill details
4. Select "Cash on Delivery"
5. Click "Proceed to Payment"
6. Click "Process Payment"
7. Should see thank you page

#### Scenario 2: Successful Card Payment
1. Add items to cart
2. Go to checkout
3. Fill details
4. Select "Credit/Debit Card"
5. Enter card details:
   - Card: `4532015112830366`
   - Holder: Any name
   - Expiry: Any future date
   - CVV: Any 3 digits (e.g., 123)
6. Click "Process Payment"
7. Should see thank you page

#### Scenario 3: Failed Card Payment
1. Follow same as Scenario 2
2. Enter invalid card number (e.g., `1234567890123456`)
3. Should see error: "Invalid card number"

#### Scenario 4: Expired Card
1. Follow Scenario 2
2. Use card with past expiry date
3. Should see error: "Card has expired"

#### Scenario 5: Bank Transfer
1. Add items to cart
2. Go to checkout
3. Fill details
4. Select "Bank Transfer"
5. Fill bank details:
   - Account holder: Any name
   - Bank: Select one
   - Account: 8+ digits
6. Click "Process Payment"
7. Should see thank you page

## Customization

### Change Success Rate
```php
// In orders/payment.php, line ~115
function simulate_payment_processing($payment_method) {
    $random = mt_rand(1, 100);
    
    // Change 95 to any number (0-100) for success percentage
    if ($random <= 95) {  // ← Change this value
        // Success
    }
}
```

### Change Test Card Numbers
```php
// In orders/payment.php, update display in HTML
// Search for: "Test Card Numbers:"
// Change displayed card numbers
```

### Change Banks List
```php
// In orders/payment.php, find <select name="bank_name">
// Add/remove <option> entries
```

### Customize Payment Messages
```php
// In orders/payment.php, function simulate_payment_processing()
$failure_reasons = [
    'Your custom message 1',
    'Your custom message 2',
    // Add more...
];
```

## Security Considerations

### Current Implementation
- ✅ Input validation and sanitization
- ✅ Prepared statements (no SQL injection)
- ✅ Payment details masked before storage
- ✅ Session-based state management
- ✅ HTTPS recommended (setup in production)

### For Production Use
1. **Enable HTTPS**: Force SSL on payment pages
2. **Add CSRF Token**: Implement token validation
3. **Rate Limiting**: Prevent brute force attacks
4. **Logging**: Log all payment attempts
5. **Monitoring**: Alert on suspicious activity
6. **PCI Compliance**: If storing more data, follow PCI-DSS

## Troubleshooting

### Issue: "Payment must be processed first"
**Solution**: Ensure checkout_data is passed correctly to payment.php

### Issue: Payment page shows blank forms
**Solution**: Check if payment_method is being set in POST data

### Issue: Card validation always fails
**Solution**: Verify Luhn algorithm implementation or use test cards provided

### Issue: Database error on order creation
**Solution**: Run payment_migration.sql to add required columns

### Issue: Payment details not saving
**Solution**: Ensure payment_details column exists and is LONGTEXT type

## Future Enhancements

### Possible Improvements
1. **Real Payment Gateway Integration**
   - Stripe API
   - PayPal
   - Razorpay

2. **Advanced Features**
   - Partial refunds
   - Payment retry mechanism
   - Multiple currency support
   - Payment scheduling
   - Subscription billing

3. **Security Enhancements**
   - Two-factor authentication
   - Card tokenization
   - Fraud detection
   - 3D Secure (3DS)

4. **Admin Features**
   - Payment management dashboard
   - Manual payment recording
   - Refund processing
   - Payment reports

## Support & Debugging

### Enable Debug Mode
```php
// Add to top of payment.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

### Check Error Logs
```bash
# View PHP error log
tail -f /path/to/error.log

# Or check browser console (F12 → Console)
```

### Database Verification
```sql
-- Check if payment columns exist
DESCRIBE orders;

-- View payment data for specific order
SELECT order_id, payment_method, payment_status, transaction_id 
FROM orders 
WHERE order_id = 'ORD-xxxxx';

-- Check payment history
SELECT * FROM payment_history ORDER BY created_at DESC LIMIT 10;
```

## Contact & Support
For issues or questions about the payment system, please refer to the main README.md or contact the development team.

---
**Last Updated**: March 2026
**Payment System Version**: 1.0 (Dummy)
