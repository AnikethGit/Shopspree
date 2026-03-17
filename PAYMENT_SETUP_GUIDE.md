# Shopspree Payment System - Quick Setup Guide

## 🚀 5-Minute Setup

### Step 1: Pull Latest Changes
```bash
git pull origin main
```

### Step 2: Update Your Database

**Option A: Using MySQL Workbench/phpMyAdmin**
1. Open phpMyAdmin in browser
2. Select your Shopspree database
3. Click "Import" tab
4. Browse to `config/payment_migration.sql`
5. Click "Go" or "Import"

**Option B: Using MySQL Command Line**
```bash
mysql -u your_username -p your_database_name < config/payment_migration.sql
```

**Option C: Using SSH/Terminal**
```bash
cd /path/to/shopspree
mysql -u root -p shopspree < config/payment_migration.sql
# Enter your MySQL password when prompted
```

### Step 3: Verify Installation
Run this SQL query to confirm:
```sql
SHOW COLUMNS FROM orders LIKE 'payment%';
```

You should see:
- `payment_status`
- `transaction_id`
- `payment_details`

### Step 4: Test the System

1. **Go to your website**
   ```
   http://localhost/shopspree
   ```

2. **Add items to cart**
   - Browse products and add some to cart

3. **Proceed to checkout**
   - Click cart icon → "Checkout" button

4. **Fill billing details**
   - Name, Email, Address, City, State, Postal Code, Phone

5. **Select payment method**
   - Choose: COD, Credit Card, or Bank Transfer

6. **Click "Proceed to Payment"**
   - Should see payment.php page

7. **Enter payment details**
   - **For COD**: Just click "Process Payment"
   - **For Card**: Use `4532015112830366` (test card)
   - **For Bank**: Enter any valid details

8. **Submit**
   - Should see "Thank You" page

## 📁 Files Changed/Added

### New Files
- ✨ `orders/payment.php` - Payment processing page
- ✨ `config/payment_migration.sql` - Database updates
- 📖 `PAYMENT_SYSTEM_DOCS.md` - Full documentation
- 📖 `PAYMENT_SETUP_GUIDE.md` - This file

### Updated Files
- 🔄 `checkout.php` - Now redirects to payment page
- 🔄 `orders/create.php` - Now handles payment data

## 🧪 Test Card Numbers

**Use these cards to test successful payments:**

| Card Number | Type | Result |
|---|---|---|
| 4532015112830366 | Visa | ✅ Success |
| 5425233010103442 | Mastercard | ✅ Success |
| 378282246310005 | American Express | ✅ Success |

**For all test cards:**
- Expiry: Any future date (e.g., 12/2025)
- CVV: Any 3 digits (e.g., 123)
- Cardholder: Any name (e.g., John Doe)

## 💳 Payment Methods

### 1. Cash on Delivery (COD)
- ✅ No validation needed
- ✅ Fastest to test
- Order Status: "Pending"

### 2. Credit/Debit Card
- 🔐 Full validation (Luhn algorithm)
- 📋 Masked storage (last 4 digits only)
- 95% success rate (simulated)

### 3. Bank Transfer
- 🏦 Bank selection from dropdown
- 📋 Account details masked
- Multiple bank options

## ✅ How to Verify It's Working

### In Browser
1. Complete a payment
2. Check thank you page
3. Should show order details

### In Database
```sql
-- View your order
SELECT * FROM orders ORDER BY created_at DESC LIMIT 1;

-- Check payment info was stored
SELECT order_id, payment_method, payment_status, transaction_id 
FROM orders 
ORDER BY created_at DESC LIMIT 1;
```

### In Emails
- Check email for order receipt
- Should show payment method and details

## 🔧 Common Issues & Fixes

### Issue: "Payment must be processed first"
**Fix**: Database migration not run. Run `payment_migration.sql` again.

### Issue: Card validation always fails
**Fix**: Use one of the test card numbers provided above.

### Issue: Payment page is blank
**Fix**: Clear browser cache (Ctrl+F5 or Cmd+Shift+R)

### Issue: Database error during order creation
**Fix**: 
```bash
# Check if columns exist
mysql -u root -p shopspree
SHOW COLUMNS FROM orders;
# If missing payment columns, run migration again
```

### Issue: Session lost
**Fix**: Ensure `php.ini` has `session.save_path` configured correctly

## 📊 What Gets Saved

### In Orders Table
```json
{
  "order_id": "ORD-5f8d9c2a",
  "payment_method": "Credit Card",
  "payment_status": "Completed",
  "transaction_id": "TXN-5f8d9c2b",
  "payment_details": {
    "card_number": "XXXX-XXXX-XXXX-1234",
    "card_holder": "John Doe",
    "expiry": "12/2025"
  }
}
```

## 🎯 Next Steps

After successful setup:

1. **Test all payment methods** (COD, Card, Bank)
2. **Check database** for stored payment data
3. **Review logs** in browser console (F12)
4. **Customize messages** if needed
5. **Read full docs** in `PAYMENT_SYSTEM_DOCS.md`

## 📞 Troubleshooting

### Enable Debug Mode
Edit `orders/payment.php` (top of file):
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Logs
```bash
# View server error log
tail -f /var/log/apache2/error.log

# Or check PHP error log
grep -i payment /var/log/php/error.log
```

### Test Database Connection
```sql
-- Connect to database
mysql -u root -p shopspree

-- Check orders table
DESCRIBE orders;

-- Check if payment columns exist
SHOW COLUMNS FROM orders WHERE Field LIKE 'payment%';
```

## 📖 Full Documentation
For detailed information, see `PAYMENT_SYSTEM_DOCS.md`

## 🎓 Architecture Overview

```
┌─────────────────┐
│  checkout.php   │ ← User fills billing details
└────────┬────────┘
         │ "Proceed to Payment"
         ↓
┌─────────────────┐
│  payment.php    │ ← Payment form based on method
└────────┬────────┘
         │ Validate & simulate payment
         ↓
┌─────────────────┐
│ orders/create   │ ← Create order with payment data
└────────┬────────┘
         │ Add items, update stock
         ↓
┌─────────────────┐
│  thank_you.php  │ ← Show confirmation
└─────────────────┘
```

## 🚨 Important Notes

⚠️ **This is a DUMMY payment system:**
- No real charges are made
- No actual payment processing
- For demonstration/testing only
- Masked data stored for reference

✅ **Production Considerations:**
- To accept real payments, integrate with Stripe/PayPal
- Never store full card numbers (PCI compliance)
- Always use HTTPS
- Implement proper error logging
- Add rate limiting

## ✨ Features

✅ Realistic payment UI
✅ Card preview animation
✅ Form validation (frontend & backend)
✅ Masked payment storage
✅ Transaction IDs
✅ Error handling
✅ Email receipts
✅ Database integration

## 🎉 You're Ready!

Your Shopspree payment system is now ready. Test it out and enjoy! 🚀

---
**Questions?** Check `PAYMENT_SYSTEM_DOCS.md` for detailed information.
