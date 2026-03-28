<?php
/**
 * Invoice Generation Form
 * Admin interface to create manual invoices for offline purchases
 * ACCESS: Admin only (user_type = 'admin')
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// --- Admin Auth Guard ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    add_message('Access denied. Admin privileges required.', 'error');
    header('Location: ../user/login.php');
    exit;
}
// --- End Auth Guard ---

// Fetch products for selection
$products = [];
try {
    $stmt = $pdo->query("SELECT id, name, price FROM products WHERE quantity > 0 ORDER BY name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error fetching products: ' . $e->getMessage());
}

// Get error message from redirect if any
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice - PrintDepotCo Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .admin-bar {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
        .admin-bar a { color: #3498db; text-decoration: none; }
        .admin-bar a:hover { text-decoration: underline; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .form-wrapper { padding: 40px; }
        .error-message {
            background: #fee; color: #c33;
            padding: 15px; border-radius: 8px;
            margin-bottom: 20px; border-left: 4px solid #c33;
        }
        .form-section { margin-bottom: 40px; }
        .section-title {
            font-size: 18px; font-weight: 600; color: #333;
            margin-bottom: 20px; padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 20px;
        }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: #333; margin-bottom: 8px; font-size: 13px; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px; border: 1px solid #ddd;
            border-radius: 6px; font-size: 14px;
            font-family: inherit; transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none; border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .required { color: #e74c3c; }
        .items-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .items-container { display: flex; flex-direction: column; gap: 15px; }
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 60px;
            gap: 10px; align-items: flex-end;
            background: white; padding: 15px;
            border-radius: 6px; border: 1px solid #e0e0e0;
        }
        .item-row select, .item-row input {
            padding: 10px; border: 1px solid #ddd;
            border-radius: 4px; font-size: 13px;
        }
        .btn {
            padding: 10px 16px; border: none; border-radius: 6px;
            font-weight: 600; cursor: pointer; font-size: 14px;
            transition: all 0.3s; text-decoration: none;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
        .btn-secondary { background: #e0e0e0; color: #333; }
        .btn-secondary:hover { background: #d0d0d0; }
        .btn-danger { background: #e74c3c; color: white; padding: 6px 10px; font-size: 12px; }
        .btn-danger:hover { background: #c0392b; }
        .btn-add-item { background: #27ae60; color: white; align-self: flex-start; }
        .btn-add-item:hover { background: #229954; }
        .summary-section {
            background: #f0f4ff; padding: 20px;
            border-radius: 8px; border: 1px solid #667eea;
        }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 14px; }
        .summary-row.total {
            border-top: 2px solid #667eea; border-bottom: 2px solid #667eea;
            padding: 15px 0; font-size: 18px; font-weight: 700;
            color: #667eea; margin-top: 10px;
        }
        .summary-label { color: #555; }
        .summary-value { font-weight: 600; color: #333; }
        .actions { display: flex; gap: 10px; margin-top: 40px; justify-content: flex-end; }
        .info-box {
            background: #e3f2fd; border-left: 4px solid #2196F3;
            padding: 15px; margin-bottom: 20px;
            border-radius: 4px; font-size: 13px; color: #1565c0;
        }
        @media (max-width: 768px) {
            .form-wrapper { padding: 20px; }
            .item-row { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Admin bar -->
        <div class="admin-bar">
            <span>&#x1F512; Admin Panel &mdash; Logged in as <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Admin'); ?></strong></span>
            <a href="../user/logout.php">Logout</a>
        </div>

        <div class="header">
            <h1>PrintDepotCo &mdash; Generate Invoice</h1>
            <p>Create manual invoice for offline purchases</p>
        </div>

        <div class="form-wrapper">
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="info-box">
                &#x2139; This invoice will be saved to the database and tracked via Order ID. The customer can track their order using the tracking page.
            </div>

            <form method="POST" action="create_invoice.php" id="invoiceForm">

                <!-- Customer Information -->
                <div class="form-section">
                    <div class="section-title">Customer Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name <span class="required">*</span></label>
                            <input type="text" name="customer_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="customer_email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone <span class="required">*</span></label>
                            <input type="tel" name="customer_phone" required>
                        </div>
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="customer_company">
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="form-section">
                    <div class="section-title">Shipping Address</div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Street Address <span class="required">*</span></label>
                            <input type="text" name="shipping_address" required>
                        </div>
                        <div class="form-group">
                            <label>City <span class="required">*</span></label>
                            <input type="text" name="shipping_city" required>
                        </div>
                        <div class="form-group">
                            <label>State <span class="required">*</span></label>
                            <input type="text" name="shipping_state" required>
                        </div>
                        <div class="form-group">
                            <label>Postal Code <span class="required">*</span></label>
                            <input type="text" name="shipping_postal" required>
                        </div>
                        <div class="form-group">
                            <label>Country <span class="required">*</span></label>
                            <input type="text" name="shipping_country" value="India" required>
                        </div>
                    </div>
                </div>

                <!-- Order ID Options -->
                <div class="form-section">
                    <div class="section-title">Order ID</div>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="order_id_mode" id="order_id_auto" value="auto" checked>
                            <label class="form-check-label" for="order_id_auto">Generate automatically</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="order_id_mode" id="order_id_manual" value="manual">
                            <label class="form-check-label" for="order_id_manual">Enter manually</label>
                        </div>
                        <input type="text" name="manual_order_id" id="manual_order_id"
                               class="form-control mt-2" placeholder="e.g., OFFLINE-INV-001"
                               autocomplete="off" disabled>
                        <small class="text-muted">Leave empty to auto-generate. Make sure manual IDs are unique.</small>
                    </div>
                </div>

                <!-- Items Selection -->
                <div class="form-section">
                    <div class="section-title">Products</div>
                    <div class="items-section">
                        <div class="items-container" id="itemsContainer"></div>
                        <button type="button" class="btn btn-add-item" onclick="addItem()">+ Add Item</button>
                    </div>
                </div>

                <!-- Summary -->
                <div class="form-section">
                    <div class="section-title">Order Summary</div>
                    <div class="summary-section">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">&#x20B9;<span id="subtotalValue">0.00</span></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tax (6%):</span>
                            <span class="summary-value">&#x20B9;<span id="taxValue">0.00</span></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Shipping:</span>
                            <div>
                                <input type="number" name="shipping" id="shippingInput" value="0" min="0" step="0.01"
                                       style="width:120px;padding:8px;border:1px solid #ddd;border-radius:4px;"
                                       onchange="updateSummary()">
                            </div>
                        </div>
                        <div class="summary-row total">
                            <span>Total Due:</span>
                            <span>&#x20B9;<span id="totalValue">0.00</span></span>
                        </div>
                    </div>
                    <input type="hidden" name="subtotal" id="subtotalInput">
                    <input type="hidden" name="tax" id="taxInput">
                    <input type="hidden" name="total" id="totalInput">
                    <input type="hidden" name="items_json" id="itemsJson">
                </div>

                <!-- Payment Method -->
                <div class="form-section">
                    <div class="section-title">Payment Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Payment Method <span class="required">*</span></label>
                            <select name="payment_method" id="payment_method" required onchange="toggleCardField()">
                                <option value="">-- Select Payment Method --</option>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Debit Card">Debit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="form-group" id="card_last4_group" style="display:none;">
                            <label>Last 4 Digits (Card)</label>
                            <input type="text" name="card_last4" id="card_last4"
                                   maxlength="4" pattern="[0-9]{4}" class="form-control"
                                   placeholder="e.g., 1234" title="Enter exactly 4 digits">
                            <small class="text-muted">Last 4 digits of the card used</small>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-section">
                    <div class="section-title">Additional Notes</div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Invoice Notes (Optional)</label>
                            <textarea name="invoice_notes" placeholder="Add any special instructions or notes..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="actions">
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                    <button type="submit" class="btn btn-primary" onclick="return validateForm()">Generate &amp; Download Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const products = <?php echo json_encode($products); ?>;
        const items = [];
        let itemCounter = 0;

        function addItem() {
            const itemId = itemCounter++;
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.id = 'item-' + itemId;
            itemRow.innerHTML = `
                <div>
                    <select name="product-${itemId}" class="product-select" onchange="updateItemPrice(${itemId})">
                        <option value="">-- Select Product --</option>
                        ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('')}
                    </select>
                </div>
                <div><input type="number" name="quantity-${itemId}" value="1" min="1" class="quantity-input" onchange="updateSummary()" onkeyup="updateSummary()"></div>
                <div><input type="number" name="price-${itemId}" value="0.00" step="0.01" class="price-input" readonly style="background:#f5f5f5;"></div>
                <div><input type="number" name="subtotal-${itemId}" value="0.00" step="0.01" readonly style="background:#f5f5f5;font-weight:bold;"></div>
                <button type="button" class="btn btn-danger" onclick="removeItem(${itemId})">Remove</button>
            `;
            document.getElementById('itemsContainer').appendChild(itemRow);
            items.push(itemId);
        }

        function removeItem(itemId) {
            document.getElementById('item-' + itemId).remove();
            items.splice(items.indexOf(itemId), 1);
            updateSummary();
        }

        function updateItemPrice(itemId) {
            const select = document.querySelector(`select[name="product-${itemId}"]`);
            const priceInput = document.querySelector(`input[name="price-${itemId}"]`);
            const subtotalInput = document.querySelector(`input[name="subtotal-${itemId}"]`);
            const quantityInput = document.querySelector(`input[name="quantity-${itemId}"]`);
            const selectedOption = select.options[select.selectedIndex];
            const price = parseFloat(selectedOption.dataset.price) || 0;
            const quantity = parseInt(quantityInput.value) || 0;
            priceInput.value = price.toFixed(2);
            subtotalInput.value = (price * quantity).toFixed(2);
            updateSummary();
        }

        function updateSummary() {
            let subtotal = 0;
            const itemsArray = [];
            items.forEach(itemId => {
                const productSelect = document.querySelector(`select[name="product-${itemId}"]`);
                const quantityInput = document.querySelector(`input[name="quantity-${itemId}"]`);
                const subtotalInput = document.querySelector(`input[name="subtotal-${itemId}"]`);
                if (productSelect.value) {
                    const selectedProduct = products.find(p => p.id == productSelect.value);
                    const quantity = parseInt(quantityInput.value) || 0;
                    const price = parseFloat(selectedProduct.price);
                    const itemSubtotal = price * quantity;
                    subtotal += itemSubtotal;
                    subtotalInput.value = itemSubtotal.toFixed(2);
                    itemsArray.push({ id: selectedProduct.id, name: selectedProduct.name, price: price, quantity: quantity });
                }
            });
            const tax = subtotal * 0.06;
            const shipping = parseFloat(document.getElementById('shippingInput').value) || 0;
            const total = subtotal + tax + shipping;
            document.getElementById('subtotalValue').textContent = subtotal.toFixed(2);
            document.getElementById('taxValue').textContent = tax.toFixed(2);
            document.getElementById('totalValue').textContent = total.toFixed(2);
            document.getElementById('subtotalInput').value = subtotal.toFixed(2);
            document.getElementById('taxInput').value = tax.toFixed(2);
            document.getElementById('totalInput').value = total.toFixed(2);
            document.getElementById('itemsJson').value = JSON.stringify(itemsArray);
        }

        function validateForm() {
            const parsedItems = JSON.parse(document.getElementById('itemsJson').value || '[]');
            if (parsedItems.length === 0) {
                alert('Please add at least one item to the invoice.');
                return false;
            }
            return true;
        }

        function toggleCardField() {
            const method = document.getElementById('payment_method').value;
            const cardGroup = document.getElementById('card_last4_group');
            const cardInput = document.getElementById('card_last4');
            if (method === 'Credit Card' || method === 'Debit Card') {
                cardGroup.style.display = 'block';
                cardInput.required = true;
            } else {
                cardGroup.style.display = 'none';
                cardInput.required = false;
                cardInput.value = '';
            }
        }

        const autoRadio = document.getElementById('order_id_auto');
        const manualRadio = document.getElementById('order_id_manual');
        const manualInput = document.getElementById('manual_order_id');
        function toggleOrderIdField() {
            if (manualRadio.checked) {
                manualInput.removeAttribute('disabled');
            } else {
                manualInput.value = '';
                manualInput.setAttribute('disabled', 'disabled');
            }
        }
        autoRadio?.addEventListener('change', toggleOrderIdField);
        manualRadio?.addEventListener('change', toggleOrderIdField);
        toggleOrderIdField();
        addItem();
        toggleCardField();
    </script>
</body>
</html>