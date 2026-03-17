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
    public function generateInvoice($data, $filename) {
        // Create HTML content for PDF
        $html = $this->generateHTML($data);
        
        // Use built-in PHP to generate PDF
        // For production, install TCPDF or FPDF library
        // For now, we'll use a simple HTML-to-PDF approach
        
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Use TCPDF if available, otherwise generate downloadable HTML
        if (class_exists('TCPDF')) {
            $this->generateWithTCPDF($html, $filename);
        } else {
            // Fallback: generate HTML that can be printed as PDF
            echo $html;
        }
        
        exit();
    }
    
    /**
     * Generate PDF using TCPDF library
     */
    private function generateWithTCPDF($html, $filename) {
        require_once(__DIR__ . '/tcpdf/tcpdf.php');
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Electro');
        $pdf->SetAuthor('Electro');
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
    private function generateHTML($data) {
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
                <h1>⚡ ELECTRO</h1>
                <p>Online & Offline Sales Platform</p>
                <p>Contact: support@electro.com</p>
                <p>Phone: +1-800-ELECTRO</p>
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
            <p>Thank you for your business! | Electro &copy; " . date('Y') . "</p>
            <p>Order ID: {$order_id} can be used to track this order at electro.com/track</p>
        </div>
    </div>
</body>
</html>";
        
        return $html;
    }
}

?>