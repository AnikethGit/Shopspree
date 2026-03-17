<?php
/**
 * Send Order Receipt via Email
 * Called after order is successfully created
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

function send_order_receipt($order_id, $order_db_id, $email, $phone, $order_items, $totals, $payment_method, $shipping_address) {
    // Email configuration
    $to = $email;
    $subject = "Order Confirmation - " . $order_id . " - Shopspree";
    
    // Build items list for email
    $items_html = "";
    $items_count = 0;
    
    if (is_array($order_items)) {
        foreach ($order_items as $item) {
            $item_name = htmlspecialchars($item['name'] ?? 'Unknown');
            $item_qty = intval($item['quantity'] ?? 0);
            $item_price = floatval($item['price'] ?? 0);
            $item_subtotal = $item_price * $item_qty;
            
            $items_html .= "<tr style='border-bottom: 1px solid #ddd;'>
                <td style='padding: 10px;'>{$item_name}</td>
                <td style='padding: 10px; text-align: center;'>{$item_qty}</td>
                <td style='padding: 10px; text-align: right;'>\$" . number_format($item_price, 2) . "</td>
                <td style='padding: 10px; text-align: right;'>\$" . number_format($item_subtotal, 2) . "</td>
            </tr>";
            $items_count++;
        }
    }
    
    // Build tracking URL
    $tracking_url = 'https://' . $_SERVER['HTTP_HOST'] . '/ecommerc/orders/track.php?order_id=' . urlencode($order_id);
    
    // Build email HTML
    $html_message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
            .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 20px; }
            .order-info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .order-info p { margin: 5px 0; }
            .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .items-table th { background-color: #e9ecef; padding: 10px; text-align: left; font-weight: bold; }
            .totals { margin-top: 20px; }
            .total-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
            .total-row.final { border-bottom: 2px solid #007bff; font-weight: bold; font-size: 18px; color: #007bff; }
            .tracking-section { background-color: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .tracking-link { display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Order Confirmation</h1>
                <p style='margin: 5px 0;'>Thank you for your purchase!</p>
            </div>
            
            <div class='content'>
                <h2>Order Details</h2>
                <div class='order-info'>
                    <p><strong>Order ID:</strong> {$order_id}</p>
                    <p><strong>Order Date:</strong> " . date('F d, Y') . "</p>
                    <p><strong>Email:</strong> {$email}</p>
                    <p><strong>Phone:</strong> {$phone}</p>
                    <p><strong>Shipping Address:</strong> {$shipping_address}</p>
                </div>
                
                <h3>Items Ordered</h3>
                <table class='items-table'>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style='text-align: center;'>Quantity</th>
                            <th style='text-align: right;'>Price</th>
                            <th style='text-align: right;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$items_html}
                    </tbody>
                </table>
                
                <div class='totals'>
                    <div class='total-row'>
                        <span>Subtotal:</span>
                        <span>\$" . number_format($totals['subtotal'], 2) . "</span>
                    </div>
                    <div class='total-row'>
                        <span>Tax (" . $totals['tax_rate'] . "%):</span>
                        <span>\$" . number_format($totals['tax'], 2) . "</span>
                    </div>
                    <div class='total-row'>
                        <span>Shipping:</span>
                        <span>\$" . number_format($totals['shipping'], 2) . "</span>
                    </div>
                    <div class='total-row final'>
                        <span>Total Amount:</span>
                        <span>\$" . number_format($totals['total'], 2) . "</span>
                    </div>
                </div>
                
                <p><strong>Payment Method:</strong> {$payment_method}</p>
                
                <div class='tracking-section'>
                    <h3 style='margin-top: 0;'>Track Your Order</h3>
                    <p>You can track your order status anytime using the link below:</p>
                    <a href='{$tracking_url}' class='tracking-link'>Track Order</a>
                </div>
                
                <p style='color: #666; margin-top: 20px;'>If you have any questions about your order, please contact us at support@shopspree.com or call us at (012) 1234 567890.</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2026 Shopspree. All rights reserved.</p>
                <p>This is an automated email. Please do not reply to this address.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Set headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@shopspree.com" . "\r\n";
    
    // Send email
    $mail_sent = mail($to, $subject, $html_message, $headers);
    
    if ($mail_sent) {
        error_log("Order receipt sent to {$email} for order {$order_id}");
        return true;
    } else {
        error_log("Failed to send order receipt to {$email} for order {$order_id}");
        return false;
    }
}

?>