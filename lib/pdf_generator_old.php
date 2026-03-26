<?php
/**
 * PDF Invoice Generator
 * Generates professional invoices in PDF format
 * Uses built-in PHP PDF generation (FPDF-compatible)
 */

class InvoicePDF {
    private $width = 210;  // A4 width in mm
    private $height = 297; // A4 height in mm
    private $margin = 10;
    private $line_height = 7;
    
    /**
     * Generate Invoice PDF
     * @param array $data Invoice data
     * @param string $filename Output filename
     */
    public function generateInvoice(array $data, string $filename)
    {
        // Build HTML content
        $html = $this->generateHTML($data);
    
        // Clean any previous output
        if (ob_get_length()) {
            ob_end_clean();
        }
    
        // PDF headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    
        // Generate real PDF
        $this->generateWithTCPDF($html, $filename);
        exit;
    }
    
    /**
     * Generate PDF using TCPDF library
     */
    private function generateWithTCPDF($html, $filename) {
        require_once(__DIR__ . '/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Shopspree');
        $pdf->SetAuthor('Shopspree');
        $pdf->SetTitle('Invoice');
        $pdf->SetSubject('Invoice');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(10, 10, 10);
        
        // Add page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Write HTML
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output PDF
        $pdf->Output($filename, 'D');
    }
    
    /**
     * Generate HTML invoice content
     */
     private function generateHTML(array $data)
    {
        $order_id        = htmlspecialchars($data['order_id']);
        $order_date      = htmlspecialchars($data['order_date']);
        $customer_name   = htmlspecialchars($data['customer_name']);
        $customer_email  = htmlspecialchars($data['customer_email']);
        $customer_phone  = htmlspecialchars($data['customer_phone']);
        $customer_company = !empty($data['customer_company']) ? htmlspecialchars($data['customer_company']) : '';
        $payment_method  = htmlspecialchars($data['payment_method']);
        $shipping_address = htmlspecialchars($data['shipping_address']);
        $shipping_city    = htmlspecialchars($data['shipping_city']);
        $shipping_state   = htmlspecialchars($data['shipping_state']);
        $shipping_postal  = htmlspecialchars($data['shipping_postal']);
        $shipping_country = htmlspecialchars($data['shipping_country']);
        $notes            = !empty($data['notes']) ? htmlspecialchars($data['notes']) : '';
    
        $subtotal = number_format($data['subtotal'], 2);
        $tax      = number_format($data['tax'], 2);
        $shipping = number_format($data['shipping'], 2);
        $total    = number_format($data['total'], 2);
    
        // Optional fields
        $card_last4       = isset($data['card_last4']) && $data['card_last4'] !== null
                            ? htmlspecialchars($data['card_last4'])
                            : '';
        $tracking_number  = isset($data['tracking_number']) && $data['tracking_number'] !== null
                            ? htmlspecialchars($data['tracking_number'])
                            : '';
    
        // Build items rows
        $items_html = '';
        foreach ($data['items'] as $item) {
            $name      = htmlspecialchars($item['name']);
            $qty       = (int)$item['quantity'];
            $price     = number_format($item['price'], 2);
            $lineTotal = number_format($item['price'] * $item['quantity'], 2);
    
            $items_html .= '
                <tr>
                    <td align="center" style="border:0.2mm solid #000000;">' . $qty . '</td>
                    <td style="border:0.2mm solid #000000;">' . $name . '</td>
                    <td align="right" style="border:0.2mm solid #000000;">' . $price . '</td>
                    <td align="right" style="border:0.2mm solid #000000;">0.00</td>
                    <td align="right" style="border:0.2mm solid #000000;">' . $lineTotal . '</td>
                </tr>';
        }
    
        // Payment display (e.g. "Mastercard ****4190")
        $payment_display = $payment_method;
        if ($card_last4 !== '' && ($payment_method === 'Credit Card' || $payment_method === 'Debit Card')) {
            $payment_display .= ' ****' . $card_last4;
        }
    
        // Start HTML
        $html = '
        <style>
            .small-text { font-size: 8pt; }
            .normal-text { font-size: 9pt; }
            .title-text { font-size: 14pt; font-weight: bold; }
            .label { font-weight: bold; }
            .table-header { background-color: #000000; color: #FFFFFF; font-weight: bold; }
        </style>
    
        <!-- Header: logo/address on left, invoice info on right -->
        <table width="100%" cellpadding="4">
            <tr>
                <td width="55%" class="normal-text">
                    <span class="title-text">SHOPSPREE</span><br/>
                    Electronics & Accessories<br/>
                    Bhubaneswar, Odisha<br/>
                    India<br/>
                    <br/>
                    www.shopspree.com
                </td>
                <td width="45%" align="right" class="normal-text">
                    <span class="label">Invoice #</span> ' . $order_id . '<br/>
                    <span class="label">Order Date:</span> ' . $order_date . '<br/>
                    <span class="label">Contact Us:</span><br/>
                    support@shopspree.com<br/>
                    (+012) 1234 567890
                </td>
            </tr>
        </table>
    
        <br/>
    
        <!-- Bill / Ship / Customer block -->
        <table width="100%" cellpadding="4" class="normal-text">
            <tr>
                <td width="33%" style="border-bottom:0.3mm solid #000000;">
                    <span class="label">BILL TO</span>
                </td>
                <td width="33%" style="border-bottom:0.3mm solid #000000;">
                    <span class="label">SHIP TO</span>
                </td>
                <td width="34%" style="border-bottom:0.3mm solid #000000;">
                    <span class="label">CUSTOMER PHONE</span>
                </td>
            </tr>
            <tr>
                <td valign="top" class="small-text">
                    ' . ($customer_company !== '' ? $customer_company . '<br/>' : '') . '
                    ' . $customer_name . '<br/>
                    ' . $shipping_address . '<br/>
                    ' . $shipping_city . ', ' . $shipping_state . ' ' . $shipping_postal . '<br/>
                    ' . $shipping_country . '<br/>
                    ' . $customer_email . '
                </td>
                <td valign="top" class="small-text">
                    ' . $customer_name . '<br/>
                    ' . $shipping_address . '<br/>
                    ' . $shipping_city . ', ' . $shipping_state . ' ' . $shipping_postal . '<br/>
                    ' . $shipping_country . '<br/>
                    ' . $customer_email . '
                </td>
                <td valign="top" class="small-text">
                    ' . $customer_phone . '<br/><br/>
                    <span class="label">Payment Method:</span><br/>
                    ' . $payment_display . '<br/><br/>';
        
        if ($tracking_number !== '') {
            $html .= '
                    <span class="label">Tracking #:</span><br/>
                    ' . $tracking_number . '<br/>';
        }
    
        $html .= '
                </td>
            </tr>
        </table>
    
        <br/>
    
        <!-- Items table -->
        <table width="100%" cellpadding="4" cellspacing="0" class="normal-text">
            <tr class="table-header">
                <td width="10%" align="center" style="border:0.2mm solid #000000;">QUANTITY</td>
                <td width="50%" style="border:0.2mm solid #000000;">DESCRIPTION</td>
                <td width="13%" align="right" style="border:0.2mm solid #000000;">UNIT PRICE</td>
                <td width="12%" align="right" style="border:0.2mm solid #000000;">EST. TAX</td>
                <td width="15%" align="right" style="border:0.2mm solid #000000;">SUBTOTAL</td>
            </tr>
            ' . $items_html . '
        </table>
    
        <br/>
    
        <!-- Totals table on the right -->
        <table width="40%" align="right" cellpadding="4" cellspacing="0" class="normal-text">
            <tr>
                <td width="60%" align="right" class="label" style="border-top:0.2mm solid #000000; border-left:0.2mm solid #000000;">Subtotal</td>
                <td width="40%" align="right" style="border-top:0.2mm solid #000000; border-right:0.2mm solid #000000;">' . $subtotal . '</td>
            </tr>
            <tr>
                <td align="right" class="label" style="border-left:0.2mm solid #000000;">Sales Tax</td>
                <td align="right" style="border-right:0.2mm solid #000000;">' . $tax . '</td>
            </tr>
            <tr>
                <td align="right" class="label" style="border-left:0.2mm solid #000000;">Shipping &amp; Handling</td>
                <td align="right" style="border-right:0.2mm solid #000000;">' . $shipping . '</td>
            </tr>
            <tr>
                <td align="right" class="label" style="border-top:0.2mm solid #000000; border-left:0.2mm solid #000000;">Total</td>
                <td align="right" style="border-top:0.2mm solid #000000; border-right:0.2mm solid #000000;">' . $total . '</td>
            </tr>
        </table>
        ';
    
        // Optional notes
        if ($notes !== '') {
            $html .= '
            <br/><br/><br/>
            <span class="label normal-text">Notes:</span><br/>
            <span class="small-text">' . $notes . '</span>';
        }
    
        return $html;
}
     
    /* private function generateHTML($data) {
        $order_id = htmlspecialchars($data['order_id']);
        $order_date = htmlspecialchars($data['order_date']);
        $customer_name = htmlspecialchars($data['customer_name']);
        $customer_email = htmlspecialchars($data['customer_email']);
        $customer_phone = htmlspecialchars($data['customer_phone']);
        $customer_company = htmlspecialchars($data['customer_company']);
        $payment_method = htmlspecialchars($data['payment_method']);
        $shipping_address = htmlspecialchars($data['shipping_address']);
        $shipping_city = htmlspecialchars($data['shipping_city']);
        $shipping_state = htmlspecialchars($data['shipping_state']);
        $shipping_postal = htmlspecialchars($data['shipping_postal']);
        $shipping_country = htmlspecialchars($data['shipping_country']);
        $notes = htmlspecialchars($data['notes']);
        
        $subtotal = number_format($data['subtotal'], 2);
        $tax = number_format($data['tax'], 2);
        $shipping = number_format($data['shipping'], 2);
        $total = number_format($data['total'], 2);
        
        // Build items table
        $items_html = '';
        foreach ($data['items'] as $item) {
            $item_subtotal = number_format($item['price'] * $item['quantity'], 2);
            $item_price = number_format($item['price'], 2);
            $items_html .= "<tr>
                <td style='padding: 8px; border-bottom: 1px solid #ddd;'>{$item['name']}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>₹{$item_price}</td>
                <td style='padding: 8px; border-bottom: 1px solid #ddd; text-align: right;'>₹{$item_subtotal}</td>
            </tr>";
        }
        
        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }
        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .invoice-header {
            border-bottom: 3px solid #3498db;
            padding-bottom: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .company-info h1 {
            margin: 0;
            color: #3498db;
            font-size: 28px;
        }
        .company-info p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .invoice-number {
            text-align: right;
        }
        .invoice-number h2 {
            margin: 0;
            color: #333;
            font-size: 16px;
        }
        .invoice-number p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .invoice-content {
            display: flex;
            gap: 40px;
            margin-bottom: 30px;
        }
        .section {
            flex: 1;
        }
        .section h3 {
            background: #ecf0f1;
            padding: 10px;
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
        }
        .section-content {
            padding: 0 10px;
            font-size: 12px;
            line-height: 1.6;
        }
        .section-content p {
            margin: 5px 0;
        }
        .section-content strong {
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background: #3498db;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            font-weight: bold;
        }
        .totals {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .totals-row {
            display: flex;
            justify-content: flex-end;
            padding: 8px 0;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }
        .totals-row .label {
            width: 200px;
            text-align: right;
            padding-right: 20px;
        }
        .totals-row .amount {
            width: 100px;
            text-align: right;
            font-weight: bold;
        }
        .totals-row.total {
            border-bottom: 2px solid #3498db;
            border-top: 2px solid #3498db;
            padding: 12px 0;
            font-size: 14px;
        }
        .totals-row.total .amount {
            color: #27ae60;
            font-size: 16px;
        }
        .notes {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            font-size: 11px;
            line-height: 1.5;
        }
        .footer {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        .payment-notice {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 10px;
            margin-top: 15px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class='invoice-container'>
        <!-- Header -->
        <div class='invoice-header'>
            <div class='company-info'>
                <h1>Shopspree</h1>
                <p>Online & Offline Sales Platform</p>
                <p>Contact: support@Shopspree.com</p>
                <p>Phone: +1-8000-9999-000</p>
            </div>
            <div class='invoice-number'>
                <h2>INVOICE</h2>
                <p><strong>Order ID:</strong> {$order_id}</p>
                <p><strong>Date:</strong> {$order_date}</p>
                <p><strong>Status:</strong> <span style='background: #fff3cd; padding: 2px 6px; border-radius: 3px; color: #856404;'>Offline Sale</span></p>
            </div>
        </div>
        
        <!-- Customer & Shipping Info -->
        <div class='invoice-content'>
            <div class='section'>
                <h3>BILL TO</h3>
                <div class='section-content'>
                    <p><strong>{$customer_name}</strong></p>
                    ";
        
        if (!empty($customer_company)) {
            $html .= "<p>{$customer_company}</p>";
        }
        
        $html .= "<p>Email: {$customer_email}</p>
                    <p>Phone: {$customer_phone}</p>
                </div>
            </div>
            
            <div class='section'>
                <h3>SHIP TO</h3>
                <div class='section-content'>
                    <p><strong>{$customer_name}</strong></p>
                    <p>{$shipping_address}</p>
                    <p>{$shipping_city}, {$shipping_state} {$shipping_postal}</p>
                    <p>{$shipping_country}</p>
                </div>
            </div>
            
            <div class='section'>
                <h3>PAYMENT INFO</h3>
                <div class='section-content'>
                    <p><strong>Method:</strong> {$payment_method}</p>
                    <p><strong>Payment Status:</strong> Pending</p>
                    ";
        
        if ($payment_method === 'Cash') {
            $html .= "<div class='payment-notice'>
                        <strong>Note:</strong> Cash payment expected on delivery or pickup.
                    </div>";
        }
        
        $html .= "</div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style='text-align: center; width: 80px;'>Qty</th>
                    <th style='text-align: right; width: 100px;'>Unit Price</th>
                    <th style='text-align: right; width: 100px;'>Amount</th>
                </tr>
            </thead>
            <tbody>
                {$items_html}
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class='totals'>
            <div class='totals-row'>
                <div class='label'>Subtotal:</div>
                <div class='amount'>₹{$subtotal}</div>
            </div>
            <div class='totals-row'>
                <div class='label'>Tax ({$data['tax_rate']}%):</div>
                <div class='amount'>₹{$tax}</div>
            </div>
            <div class='totals-row'>
                <div class='label'>Shipping:</div>
                <div class='amount'>₹{$shipping}</div>
            </div>
            <div class='totals-row total'>
                <div class='label'>TOTAL DUE:</div>
                <div class='amount'>₹{$total}</div>
            </div>
        </div>
        
        ";
        
        if (!empty($notes)) {
            $html .= "<div class='notes'>
                <strong>Notes:</strong><br>
                {$notes}
            </div>";
        }
        
        $html .= "<div class='footer'>
            <p>This is a computer-generated invoice. No signature required.</p>
            <p>Thank you for your business! | Shopspree &copy; " . date('Y') . "</p>
            <p>Order ID: {$order_id} can be used to track this order at shopspree.com/track</p>
        </div>
    </div>
</body>
</html>";
        
        return $html;
    } */
}

?>