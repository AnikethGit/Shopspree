<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check if there's a recent order
if (empty($_SESSION['last_order_id'])) {
    redirect('index.php');
}

$order_id = $_SESSION['last_order_id'];
$order_db_id = $_SESSION['last_order_db_id'] ?? null;

// Fetch order details
$order = null;
if (!is_null($order_db_id)) {
    $query = "SELECT * FROM orders WHERE id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $order_db_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

if (!$order) {
    // Try to find by order_id
    $query = "SELECT * FROM orders WHERE order_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $order = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        redirect('../index.php');
    }
}

if (!$order) {
    redirect('../index.php');
}

// Fetch order items
$items = [];
$items_query = "SELECT * FROM order_items WHERE order_id = ?";
if ($stmt = $conn->prepare($items_query)) {
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && $row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
}

// Clear session data
unset($_SESSION['last_order_id']);
unset($_SESSION['last_order_db_id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Electro</title>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .thank-you-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
        }
        .success-badge {
            width: 80px;
            height: 80px;
            background: #27ae60;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
        }
        .thank-you-container h1 {
            color: #27ae60;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .thank-you-container p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .order-info {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        .order-info-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .order-info-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .order-info-value {
            color: #555;
        }
        .order-items {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        .order-items h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .item-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
            align-items: center;
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-row.header {
            font-weight: bold;
            background: #e8e8e8;
            padding: 10px;
            border-radius: 4px;
        }
        .order-total {
            background: #e8f8f5;
            border: 1px solid #27ae60;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-size: 18px;
        }
        .order-total strong {
            color: #27ae60;
            font-size: 24px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .note {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="thank-you-container">
            <!-- Success Message -->
            <div class="success-badge">✓</div>
            <h1>Thank You for Your Order!</h1>
            <p>Your order has been successfully placed and is being processed.</p>

            <!-- Order Information -->
            <div class="order-info">
                <div class="order-info-row">
                    <div>
                        <div class="order-info-label">Order ID</div>
                        <div class="order-info-value" style="font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($order['order_id']); ?></div>
                    </div>
                    <div>
                        <div class="order-info-label">Order Date</div>
                        <div class="order-info-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                </div>
                <div class="order-info-row">
                    <div>
                        <div class="order-info-label">Order Status</div>
                        <div class="order-info-value">
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $order['order_status'])); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="order-info-label">Payment Method</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                    </div>
                </div>
                <div class="order-info-row">
                    <div>
                        <div class="order-info-label">Email</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div>
                        <div class="order-info-label">Phone</div>
                        <div class="order-info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                    </div>
                </div>
                <div class="order-info-row">
                    <div style="grid-column: 1 / -1;">
                        <div class="order-info-label">Shipping Address</div>
                        <div class="order-info-value">
                            <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                            <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                            <?php echo htmlspecialchars($order['shipping_state']); ?> 
                            <?php echo htmlspecialchars($order['shipping_postal_code']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                <h3>Order Items (<?php echo count($items); ?> items)</h3>
                <div class="item-row header">
                    <div>Product</div>
                    <div style="text-align: center;">Qty</div>
                    <div style="text-align: center;">Price</div>
                    <div style="text-align: right;">Subtotal</div>
                </div>
                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div style="text-align: center;"><?php echo (int)$item['quantity']; ?></div>
                        <div style="text-align: center;">$<?php echo number_format((float)$item['price'], 2); ?></div>
                        <div style="text-align: right;">$<?php echo number_format((float)$item['subtotal'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Total -->
            <div class="order-total">
                Total Amount: <strong>$<?php echo number_format((float)$order['total_amount'], 2); ?></strong>
            </div>

            <!-- Note -->
            <div class="note">
                <strong>📧 Confirmation Email:</strong> A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['email']); ?></strong>. Please check your inbox and spam folder.
            </div>

            <?php if ($order['payment_method'] === 'COD'): ?>
                <div class="note">
                    <strong>💳 Payment Notice:</strong> As you selected Cash on Delivery, you will pay when your order arrives. Our team will contact you shortly with delivery details.
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="../shop.php" class="btn btn-primary">Continue Shopping</a>
                <a href="../index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>