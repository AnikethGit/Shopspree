<?php
/**
 * PDF Invoice Generator — Printdepotco
 *
 * Generates a two-page PDF matching the refined invoice HTML template:
 *   Page 1 — Invoice   (no QR codes)
 *   Page 2 — Packing Slip  (QR codes: tracking_number + order_id)
 *
 * Requires TCPDF. Place the library at the path below or adjust:
 *   __DIR__ . '/tcpdf/tcpdf.php'
 *
 * Called from generate_pdf_job.php (background) via:
 *   $pdf = new InvoicePDF();
 *   $pdf->generateAndSave($invoice_data, $filename);
 *
 * Expected $data keys (supplied by create_invoice.php $invoice_data):
 *   order_id            string   — e.g. "090925T3T"
 *   order_date          string   — any strtotime-parseable date
 *   customer_name       string
 *   customer_email      string
 *   customer_phone      string
 *   customer_company    string   (optional)
 *   shipping_address    string
 *   shipping_city       string
 *   shipping_state      string
 *   shipping_postal     string
 *   shipping_country    string
 *   payment_method      string   — e.g. "Credit Card"
 *   card_last4          string   (optional) — 4 digits
 *   tracking_number     string   (optional)
 *   notes               string   (optional)
 *   items               array    — [ ['name'=>…,'price'=>…,'quantity'=>…], … ]
 *   subtotal            float
 *   tax                 float
 *   tax_rate            float    — percentage, e.g. 6.0
 *   shipping            float
 *   total               float
 */

class InvoicePDF
{
    // ── Brand constants ─────────────────────────────────────────────────
    const COMPANY_NAME    = ' ';
    const COMPANY_TAGLINE = 'Electronics & Accessories';
    const COMPANY_ADDRESS = 'United States of America';
    const COMPANY_EMAIL   = 'support@printdepotco.com';
    const COMPANY_PHONE   = '(+012) 1234 567890';
    const COMPANY_WEB     = 'www.printdepotco.com';
    const COMPANY_LOGO    = __DIR__ . '/../img/printdepotco-logo.png';

    // ── Design tokens (RGB arrays) ──────────────────────────────────────
    const C_INK    = [24,  24,  27];
    const C_MID    = [82,  82,  91];
    const C_MUTED  = [161, 161, 170];
    const C_BORDER = [228, 228, 231];
    const C_ALT    = [250, 250, 249];
    const C_WHITE  = [255, 255, 255];

    // ── Layout constants (mm, A4) ───────────────────────────────────────
    const MARGIN = 12.0;
    const PW     = 210.0;
    const PH     = 297.0;
    const CW     = 186.0;

    // ── Items table column widths (must sum to CW = 186) ───────────────
    const ITEM_COLS  = [16, 83, 28, 28, 31];
    const ITEM_HDRS  = ['QTY', 'DESCRIPTION', 'UNIT PRICE', 'EST. TAX', 'SUBTOTAL'];
    const ITEM_ALIGN = ['C',   'L',           'R',          'R',        'R'];

    // ────────────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ────────────────────────────────────────────────────────────────────

    public function generateInvoice(array $data, string $filename): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $this->buildPDF($data, $filename);
        exit;
    }

    // ────────────────────────────────────────────────────────────────────
    //  CORE PDF BUILDER  (streams directly to browser)
    // ────────────────────────────────────────────────────────────────────

    private function buildPDF(array $data, string $filename): void
    {
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', '120');

        require_once(__DIR__ . '/tcpdf/tcpdf.php');

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('');
        $pdf->SetAuthor('');
        $pdf->SetTitle('');
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $pdf->SetAutoPageBreak(false, 0);

        // Page 1 — Invoice
        $pdf->AddPage();
        $this->renderInvoicePage($pdf, $data);

        // Page 2 — Packing Slip
        $pdf->AddPage();
        $this->renderPackingSlipPage($pdf, $data);

        // Stream directly to browser — no disk write
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        $pdf->Output($filename, 'D');
    }

    // ────────────────────────────────────────────────────────────────────
    //  PAGE 1 — INVOICE
    // ────────────────────────────────────────────────────────────────────

    private function renderInvoicePage(TCPDF $pdf, array $data): void
    {
        $order_id         = $this->t($data['order_id']);
        $order_date       = $this->formatDate($data['order_date']);
        $customer_name    = $this->t($data['customer_name']);
        $customer_email   = $this->t($data['customer_email']);
        $customer_phone   = $this->t($data['customer_phone']);
        $customer_company = !empty($data['customer_company']) ? $this->t($data['customer_company']) : '';
        $payment_method   = $this->t($data['payment_method']);
        $ship_addr        = $this->t($data['shipping_address']);
        $ship_city        = $this->t($data['shipping_city']);
        $ship_state       = $this->t($data['shipping_state']);
        $ship_postal      = $this->t($data['shipping_postal']);
        $ship_country     = $this->t($data['shipping_country']);
        $tax_rate         = isset($data['tax_rate']) ? (float)$data['tax_rate'] : 6.0;
        $card_last4       = !empty($data['card_last4'])      ? $this->t($data['card_last4'])      : '';
        $tracking         = !empty($data['tracking_number']) ? $this->t($data['tracking_number']) : '';
        $notes            = !empty($data['notes'])           ? $this->t($data['notes'])           : '';

        $subtotal_fmt = '$' . $this->fmt($data['subtotal']);
        $tax_fmt      = '$' . $this->fmt($data['tax']);
        $shipping_fmt = '$' . $this->fmt($data['shipping']);
        $total_fmt    = '$' . $this->fmt($data['total']);

        $payment_display = $payment_method;
        if ($card_last4 !== '' && in_array($data['payment_method'], ['Credit Card', 'Debit Card'])) {
            $payment_display .= ' ****' . $card_last4;
        }

        $bill_name = $customer_company ?: $customer_name;
        $bill_sub  = $customer_company ? $customer_name : '';

        $L  = self::MARGIN;
        $CW = self::CW;

        // ── 1. HEADER ────────────────────────────────────────────────────
        $this->renderInvoiceHeader($pdf, $L, $CW, $order_id, $order_date);
        $pdf->Ln(2);

        // ── 2. PARTIES header bar ────────────────────────────────────────
        $col = $CW / 3;
        $this->fill($pdf, self::C_INK);
        $this->text($pdf, self::C_WHITE);
        $pdf->SetFont('helvetica', 'B', 7);
        $y = $pdf->GetY();

        foreach (['BILL TO', 'SHIP TO', 'CUSTOMER PHONE'] as $i => $lbl) {
            $pdf->SetXY($L + $i * $col, $y);
            $pdf->Cell($col, 6, $lbl, 0, 0, 'L', true);
            if ($i < 2) {
                $this->vline($pdf, $L + ($i + 1) * $col, $y, 6, [80, 80, 83]);
            }
        }
        $pdf->Ln();

        // ── 3. PARTIES content (dynamic height) ──────────────────────────
        $pdf->SetFont('helvetica', '', 7.5);
        $addrText  = $ship_addr . "\n" . $ship_city . ', ' . $ship_state . ' ' . $ship_postal . "\n" . $ship_country . "\n" . $customer_email;
        $addrLines = $pdf->getNumLines($addrText, $col - 4);
        $col3Lines = 3 + ($tracking !== '' ? 2 : 0);
        $cellH     = max(28.0, ($addrLines * 4.0) + 10.0, ($col3Lines * 6.0) + 6.0);
        $rowY      = $pdf->GetY();

        // Column 1: Bill To
        $this->fill($pdf, self::C_WHITE);
        $pdf->Rect($L, $rowY, $col, $cellH, 'F');
        $this->vline($pdf, $L + $col, $rowY, $cellH, self::C_BORDER);

        $this->text($pdf, self::C_INK);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetXY($L + 1.5, $rowY + 2);
        $pdf->Cell($col - 3, 5, $bill_name, 0, 2, 'L');

        if ($bill_sub) {
            $pdf->SetFont('helvetica', '', 7.5);
            $this->text($pdf, self::C_MID);
            $pdf->SetX($L + 1.5);
            $pdf->Cell($col - 3, 4, $bill_sub, 0, 2, 'L');
        }

        $pdf->SetFont('helvetica', '', 7.5);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L + 1.5);
        $pdf->MultiCell($col - 3, 4, $addrText, 0, 'L', false, 2);

        // Column 2: Ship To
        $this->fill($pdf, self::C_WHITE);
        $pdf->Rect($L + $col, $rowY, $col, $cellH, 'F');
        $this->vline($pdf, $L + 2 * $col, $rowY, $cellH, self::C_BORDER);

        $this->text($pdf, self::C_INK);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetXY($L + $col + 1.5, $rowY + 2);
        $pdf->Cell($col - 3, 5, $customer_name, 0, 2, 'L');

        $pdf->SetFont('helvetica', '', 7.5);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L + $col + 1.5);
        $pdf->MultiCell($col - 3, 4, $addrText, 0, 'L', false, 2);

        // Column 3: Phone / Payment / Tracking
        $this->fill($pdf, self::C_WHITE);
        $pdf->Rect($L + 2 * $col, $rowY, $col, $cellH, 'F');

        $cx3 = $L + 2 * $col + 1.5;

        $this->text($pdf, self::C_INK);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetXY($cx3, $rowY + 2);
        $pdf->Cell($col - 3, 5, $customer_phone, 0, 2, 'L');

        $pdf->SetFont('helvetica', 'B', 6.5);
        $this->text($pdf, self::C_MUTED);
        $pdf->SetX($cx3);
        $pdf->Cell($col - 3, 3.5, 'PAYMENT METHOD', 0, 2, 'L');

        $pdf->SetFont('helvetica', 'B', 7.5);
        $this->text($pdf, self::C_INK);
        $pdf->SetX($cx3);
        $pdf->Cell($col - 3, 5, $payment_display, 0, 2, 'L');

        if ($tracking !== '') {
            $pdf->SetFont('helvetica', 'B', 6.5);
            $this->text($pdf, self::C_MUTED);
            $pdf->SetX($cx3);
            $pdf->Cell($col - 3, 3.5, 'TRACKING #', 0, 2, 'L');

            $pdf->SetFont('helvetica', 'B', 7.5);
            $this->text($pdf, self::C_INK);
            $pdf->SetX($cx3);
            $pdf->Cell($col - 3, 5, $tracking, 0, 2, 'L');
        }

        // Bottom border under party cells
        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $bottomY = $rowY + $cellH;
        $pdf->Line($L, $bottomY, $L + $CW, $bottomY);
        $pdf->SetY($bottomY);
        $pdf->Ln(1);

        // ── 4. ITEMS TABLE header bar ─────────────────────────────────────
        $colW   = self::ITEM_COLS;
        $hdrs   = self::ITEM_HDRS;
        $aligns = self::ITEM_ALIGN;

        $this->fill($pdf, self::C_INK);
        $this->text($pdf, self::C_WHITE);
        $pdf->SetFont('helvetica', 'B', 7);
        $y = $pdf->GetY();
        $x = $L;

        foreach ($hdrs as $i => $lbl) {
            $pdf->SetXY($x, $y);
            $pdf->Cell($colW[$i], 6, $lbl, 0, 0, $aligns[$i], true);
            if ($i < count($hdrs) - 1) {
                $this->vline($pdf, $x + $colW[$i], $y, 6, [80, 80, 83]);
            }
            $x += $colW[$i];
        }
        $pdf->Ln();

        // ── 5. ITEMS ROWS (manual page-break guard) ───────────────────────
        $pageBottom = self::PH - self::MARGIN;
        $rowNum     = 0;

        foreach ($data['items'] as $item) {
            $name     = $this->t($item['name']);
            $qty      = (int)$item['quantity'];
            $price    = (float)$item['price'];
            $line_sub = $price * $qty;
            $line_tax = $line_sub * $tax_rate / 100;
            $rowH     = $this->itemRowHeight($pdf, $name, (float)$colW[1] - 2);
            $bg       = ($rowNum % 2 === 1) ? self::C_ALT : self::C_WHITE;

            // Page overflow check
            if ($pdf->GetY() + $rowH > $pageBottom - 30) {
                $pdf->AddPage();
                $pdf->SetXY($L, self::MARGIN);
                $this->fill($pdf, self::C_INK);
                $this->text($pdf, self::C_WHITE);
                $pdf->SetFont('helvetica', 'B', 7);
                $y = $pdf->GetY();
                $x = $L;
                foreach ($hdrs as $i => $lbl) {
                    $pdf->SetXY($x, $y);
                    $pdf->Cell($colW[$i], 6, $lbl, 0, 0, $aligns[$i], true);
                    if ($i < count($hdrs) - 1) {
                        $this->vline($pdf, $x + $colW[$i], $y, 6, [80, 80, 83]);
                    }
                    $x += $colW[$i];
                }
                $pdf->Ln();
            }

            $y = $pdf->GetY();

            $this->fill($pdf, $bg);
            $pdf->Rect($L, $y, $CW, $rowH, 'F');

            $pdf->SetDrawColor(...self::C_BORDER);
            $pdf->SetLineWidth(0.25);
            $pdf->Line($L, $y + $rowH, $L + $CW, $y + $rowH);

            $xd = $L;
            foreach (array_slice($colW, 0, -1) as $cw) {
                $xd += $cw;
                $pdf->Line($xd, $y, $xd, $y + $rowH);
            }

            $pad = 2.0;

            $this->text($pdf, self::C_INK);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($L, $y + $pad);
            $pdf->Cell($colW[0], $rowH - 2 * $pad, (string)$qty, 0, 0, 'C');

            $this->text($pdf, self::C_MID);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetXY($L + $colW[0] + 1, $y + $pad);
            $pdf->MultiCell($colW[1] - 2, 4.2, $name, 0, 'L', false, 0);

            $this->text($pdf, self::C_MID);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetXY($L + $colW[0] + $colW[1], $y + $pad);
            $pdf->Cell($colW[2] - 1, $rowH - 2 * $pad, '$' . $this->fmt($price), 0, 0, 'R');

            $pdf->SetXY($L + $colW[0] + $colW[1] + $colW[2], $y + $pad);
            $pdf->Cell($colW[3] - 1, $rowH - 2 * $pad, '$' . $this->fmt($line_tax), 0, 0, 'R');

            $this->text($pdf, self::C_INK);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($L + $colW[0] + $colW[1] + $colW[2] + $colW[3], $y + $pad);
            $pdf->Cell($colW[4] - 1, $rowH - 2 * $pad, '$' . $this->fmt($line_sub), 0, 0, 'R');

            $pdf->SetY($y + $rowH);
            $rowNum++;
        }

        // ── 6. TOTALS block (right-aligned) ──────────────────────────────
        $totalsW = 78.0;
        $labelW  = 48.0;
        $valueW  = $totalsW - $labelW;
        $tx      = $L + $CW - $totalsW;
        $totalsH = 7.5;

        $totalRows = [
            ['SUBTOTAL',                                         $subtotal_fmt, false],
            ['SALES TAX ' . number_format($tax_rate, 1) . '%', $tax_fmt,      false],
            ['SHIPPING & HANDLING',                              $shipping_fmt, false],
            ['TOTAL',                                            $total_fmt,    true ],
        ];

        $totalsStartY = $pdf->GetY();

        foreach ($totalRows as [$lbl, $val, $isDark]) {
            $y = $pdf->GetY();

            if ($isDark) {
                $this->fill($pdf, self::C_INK);
                $pdf->Rect($tx, $y, $totalsW, $totalsH + 1, 'F');
                $this->text($pdf, [255, 255, 255]);
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetXY($tx, $y + 1);
                $pdf->Cell($labelW, $totalsH, $lbl, 0, 0, 'R');
                $pdf->SetDrawColor(80, 80, 83);
                $pdf->SetLineWidth(0.25);
                $pdf->Line($tx + $labelW, $y, $tx + $labelW, $y + $totalsH + 1);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetXY($tx + $labelW, $y + 1);
                $pdf->Cell($valueW, $totalsH, $val, 0, 2, 'R');
            } else {
                $this->fill($pdf, self::C_WHITE);
                $pdf->Rect($tx, $y, $totalsW, $totalsH, 'F');
                $pdf->SetDrawColor(...self::C_BORDER);
                $pdf->SetLineWidth(0.25);
                $pdf->Line($tx, $y + $totalsH, $tx + $totalsW, $y + $totalsH);
                $pdf->Line($tx + $labelW, $y, $tx + $labelW, $y + $totalsH);
                $this->text($pdf, self::C_MID);
                $pdf->SetFont('helvetica', 'B', 7.5);
                $pdf->SetXY($tx, $y + 1.5);
                $pdf->Cell($labelW, $totalsH - 3, $lbl, 0, 0, 'R');
                $this->text($pdf, self::C_INK);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetXY($tx + $labelW, $y + 1.5);
                $pdf->Cell($valueW, $totalsH - 3, $val, 0, 2, 'R');
            }
        }

        // Continuous left + right borders for light rows
        $lightRowsH = 3 * $totalsH;
        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($tx, $totalsStartY, $tx, $totalsStartY + $lightRowsH);
        $pdf->Line($tx + $totalsW, $totalsStartY, $tx + $totalsW, $totalsStartY + $lightRowsH);

        // ── 7. NOTES ─────────────────────────────────────────────────────
        if ($notes !== '') {
            $pdf->Ln(6);
            $ny = $pdf->GetY();

            $pdf->SetDrawColor(...self::C_BORDER);
            $pdf->SetLineWidth(0.25);
            $pdf->SetFillColor(...self::C_ALT);
            $pdf->Rect($L, $ny, $CW, 5, 'F');
            $pdf->Line($L, $ny, $L + $CW, $ny);

            $this->text($pdf, self::C_MID);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($L + 2, $ny + 1);
            $pdf->Cell($CW - 4, 4, 'NOTES', 0, 2, 'L');

            $pdf->SetFont('helvetica', '', 7.5);
            $this->text($pdf, self::C_MID);
            $pdf->SetXY($L + 2, $ny + 5);
            $pdf->MultiCell($CW - 4, 4.5, $notes, 0, 'L', false, 2);

            $notesH = $pdf->GetY() - $ny + 2;
            $pdf->SetDrawColor(...self::C_BORDER);
            $pdf->Rect($L, $ny, $CW, $notesH, 'D');
        }

        // ── 8. INVOICE PAGE FOOTER ────────────────────────────────────────
        $fy = self::PH - self::MARGIN - 7;
        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($L, $fy, $L + $CW, $fy);

        $this->text($pdf, self::C_MID);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($L, $fy + 1);
        $pdf->Cell($CW / 2, 5, self::COMPANY_ADDRESS, 0, 0, 'L');

        $this->text($pdf, self::C_MUTED);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell($CW / 2, 5,
            self::COMPANY_EMAIL . '  ·  ' . self::COMPANY_PHONE . '  ·  ' . self::COMPANY_WEB,
            0, 1, 'R');
    }

    // ────────────────────────────────────────────────────────────────────
    //  PAGE 2 — PACKING SLIP
    // ────────────────────────────────────────────────────────────────────

    private function renderPackingSlipPage(TCPDF $pdf, array $data): void
    {
        $order_id     = $this->t($data['order_id']);
        $order_date   = $this->formatDate($data['order_date']);
        $cust_name    = $this->t($data['customer_name']);
        $cust_company = !empty($data['customer_company']) ? $this->t($data['customer_company']) : '';
        $ship_addr    = $this->t($data['shipping_address']);
        $ship_city    = $this->t($data['shipping_city']);
        $ship_state   = $this->t($data['shipping_state']);
        $ship_postal  = $this->t($data['shipping_postal']);
        $ship_country = $this->t($data['shipping_country']);

        $hasTracking = !empty($data['tracking_number']);
        $tracking    = $hasTracking ? $this->t($data['tracking_number']) : $order_id;
        $trackingLbl = $hasTracking ? 'TRACKING NUMBER' : 'ORDER NUMBER';
        $ship_name   = $cust_company ?: $cust_name;

        $L  = self::MARGIN;
        $CW = self::CW;

        // ── 1. TITLE BAR ─────────────────────────────────────────────────
        $this->fill($pdf, self::C_INK);
        $this->text($pdf, self::C_WHITE);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetXY($L, $pdf->GetY());
        $pdf->Cell($CW, 7, 'PACKING SLIP', 0, 1, 'C', true);
        $pdf->Ln(3);

        // ── 2. TOP BLOCK (logo + company | invoice meta) ──────────────────
        $y = $pdf->GetY();

        $pdf->Image(self::COMPANY_LOGO, $L, $y - 3, 30, 0, '', '', 'T', false, 300);

        $pdf->SetFont('helvetica', 'B', 9);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($L + 28, $y);
        $pdf->Cell(68, 5, self::COMPANY_NAME, 0, 2, 'L');
        $pdf->SetFont('helvetica', '', 7.5);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L + 28);
        $pdf->Cell(68, 4, self::COMPANY_ADDRESS, 0, 2, 'L');
        $pdf->SetX($L + 28);
        $pdf->Cell(68, 4, self::COMPANY_WEB, 0, 2, 'L');

        $pdf->SetFont('helvetica', 'B', 12);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($L + 90, $y);
        $pdf->Cell($CW - 90, 6, 'Invoice #' . $order_id, 0, 2, 'R');
        $pdf->SetFont('helvetica', '', 7.5);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L + 90);
        $pdf->Cell($CW - 90, 4.5, 'Order Date:  ' . $order_date, 0, 2, 'R');
        if ($hasTracking) {
            $pdf->SetX($L + 90);
            $pdf->Cell($CW - 90, 4.5, 'Tracking #:  ' . $tracking, 0, 2, 'R');
        }

        $divY = max($y + 16, $pdf->GetY() + 2);
        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($L, $divY, $L + $CW, $divY);
        $pdf->SetY($divY + 3);

        // ── 3. SHIP TO ────────────────────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 6.5);
        $this->text($pdf, self::C_MUTED);
        $pdf->SetX($L);
        $pdf->Cell($CW / 2, 4, 'SHIP TO', 0, 2, 'L');

        $pdf->SetFont('helvetica', 'B', 8.5);
        $this->text($pdf, self::C_INK);
        $pdf->SetX($L);
        $pdf->Cell($CW / 2, 5, $ship_name, 0, 2, 'L');

        $pdf->SetFont('helvetica', '', 7.5);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L);
        $pdf->MultiCell(
            $CW / 2, 4.2,
            $ship_addr . "\n" . $ship_city . ', ' . $ship_state . ' ' . $ship_postal . "\n" . $ship_country,
            0, 'L', false, 2
        );

        $afterShip = $pdf->GetY() + 3;
        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($L, $afterShip, $L + $CW, $afterShip);
        $pdf->SetY($afterShip + 1);

        // ── 4. PACKING ITEMS TABLE ────────────────────────────────────────
        $descW = $CW - 28;
        $qtyW  = 28.0;

        $this->fill($pdf, self::C_INK);
        $this->text($pdf, self::C_WHITE);
        $pdf->SetFont('helvetica', 'B', 7);
        $y = $pdf->GetY();
        $pdf->SetXY($L, $y);
        $pdf->Cell($descW, 6, 'DESCRIPTION', 0, 0, 'L', true);
        $this->vline($pdf, $L + $descW, $y, 6, [80, 80, 83]);
        $pdf->SetXY($L + $descW, $y);
        $pdf->Cell($qtyW, 6, 'Q-TY', 0, 1, 'R', true);

        $pageBottom = self::PH - self::MARGIN;
        $rowNum     = 0;

        foreach ($data['items'] as $item) {
            $name = $this->t($item['name']);
            $qty  = (int)$item['quantity'];
            $bg   = ($rowNum % 2 === 1) ? self::C_ALT : self::C_WHITE;
            $rowH = $this->itemRowHeight($pdf, $name, $descW - 2);

            if ($pdf->GetY() + $rowH > $pageBottom - 60) {
                $pdf->AddPage();
                $pdf->SetXY($L, self::MARGIN);
                $this->fill($pdf, self::C_INK);
                $this->text($pdf, self::C_WHITE);
                $pdf->SetFont('helvetica', 'B', 7);
                $y = $pdf->GetY();
                $pdf->SetXY($L, $y);
                $pdf->Cell($descW, 6, 'DESCRIPTION', 0, 0, 'L', true);
                $this->vline($pdf, $L + $descW, $y, 6, [80, 80, 83]);
                $pdf->SetXY($L + $descW, $y);
                $pdf->Cell($qtyW, 6, 'Q-TY', 0, 1, 'R', true);
            }

            $y = $pdf->GetY();

            $this->fill($pdf, $bg);
            $pdf->Rect($L, $y, $CW, $rowH, 'F');

            $pdf->SetDrawColor(...self::C_BORDER);
            $pdf->SetLineWidth(0.25);
            $pdf->Line($L, $y + $rowH, $L + $CW, $y + $rowH);
            $pdf->Line($L + $descW, $y, $L + $descW, $y + $rowH);

            $this->text($pdf, self::C_MID);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetXY($L + 1.5, $y + 2);
            $pdf->MultiCell($descW - 3, 4.2, $name, 0, 'L', false, 0);

            $this->text($pdf, self::C_INK);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($L + $descW, $y + 2);
            $pdf->Cell($qtyW - 1, $rowH - 4, (string)$qty, 0, 2, 'R');

            $pdf->SetY($y + $rowH);
            $rowNum++;
        }

        // ── 5. QR CODE SECTION ───────────────────────────────────────────
        $pdf->Ln(4);
        $qrY = $pdf->GetY();

        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($L, $qrY, $L + $CW, $qrY);

        $qrAreaH = 46.0;
        $qrSize  = 28.0;
        $cellW   = $CW / 2;

        $this->fill($pdf, self::C_ALT);
        $pdf->Rect($L, $qrY, $CW, $qrAreaH, 'F');

        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->Line($L + $cellW, $qrY, $L + $cellW, $qrY + $qrAreaH);

        $qrStyle = [
            'border'  => false,
            'padding' => 1,
            'fgcolor' => self::C_INK,
            'bgcolor' => false,
        ];

        // Left QR — Tracking / Order Number
        $lx = $L;
        $pdf->SetFont('helvetica', 'B', 6.5);
        $this->text($pdf, self::C_MUTED);
        $pdf->SetXY($lx, $qrY + 3);
        $pdf->Cell($cellW, 4, $trackingLbl, 0, 0, 'C');

        $pdf->write2DBarcode(
            $tracking, 'QRCODE,M',
            $lx + ($cellW - $qrSize) / 2,
            $qrY + 8, $qrSize, $qrSize, $qrStyle
        );

        $pdf->SetFont('courier', 'B', 7.5);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($lx, $qrY + 9 + $qrSize);
        $pdf->Cell($cellW, 4, $tracking, 0, 0, 'C');

        // Right QR — Track Your Order (order_id)
        $rx = $L + $cellW;
        $pdf->SetFont('helvetica', 'B', 6.5);
        $this->text($pdf, self::C_MUTED);
        $pdf->SetXY($rx, $qrY + 3);
        $pdf->Cell($cellW, 4, 'TRACK YOUR ORDER', 0, 0, 'C');

        $pdf->write2DBarcode(
            $order_id, 'QRCODE,M',
            $rx + ($cellW - $qrSize) / 2,
            $qrY + 8, $qrSize, $qrSize, $qrStyle
        );

        $pdf->SetFont('courier', 'B', 7.5);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($rx, $qrY + 9 + $qrSize);
        $pdf->Cell($cellW, 4, '#' . $order_id, 0, 0, 'C');

        // ── 6. DARK FOOTER BAR ────────────────────────────────────────────
        $footerY = $qrY + $qrAreaH;
        $this->fill($pdf, self::C_INK);
        $pdf->Rect($L, $footerY, $CW, 8, 'F');

        $this->text($pdf, self::C_WHITE);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($L + 1.5, $footerY + 1.5);
        $pdf->Cell($CW / 2, 5, self::COMPANY_ADDRESS, 0, 0, 'L');

        $this->text($pdf, [120, 120, 128]);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($L + $CW / 2, $footerY + 1.5);
        $pdf->Cell($CW / 2 - 1.5, 5,
            self::COMPANY_EMAIL . '  ·  ' . self::COMPANY_PHONE,
            0, 0, 'R');
    }

    // ────────────────────────────────────────────────────────────────────
    //  INVOICE HEADER  (logo · company · invoice meta)
    // ────────────────────────────────────────────────────────────────────

    private function renderInvoiceHeader(TCPDF $pdf, float $L, float $CW,
                                         string $order_id, string $order_date): void
    {
        $y = $pdf->GetY();

        $pdf->Image(self::COMPANY_LOGO, $L, $y - 6, 34, 0, '', '', 'T', false, 300);

        // Company name / tagline below logo
        $pdf->SetFont('helvetica', 'B', 7.5);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($L, $y + 17.5);
        $pdf->Cell(26, 3.5, self::COMPANY_NAME, 0, 2, 'L');
        $pdf->SetFont('helvetica', '', 6.5);
        $this->text($pdf, self::C_MUTED);
        $pdf->SetX($L);
        $pdf->Cell(26, 3, self::COMPANY_TAGLINE, 0, 2, 'L');

        // Company address (centre column)
        $pdf->SetFont('helvetica', 'B', 9);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($L + 30, $y);
        $pdf->Cell(70, 5.5, self::COMPANY_NAME, 0, 2, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L + 30);
        $pdf->MultiCell(70, 4.5, self::COMPANY_ADDRESS . "\n" . self::COMPANY_WEB, 0, 'L', false, 2);

        // Invoice meta (right column)
        $pdf->SetFont('helvetica', 'B', 14);
        $this->text($pdf, self::C_INK);
        $pdf->SetXY($L + 104, $y);
        $pdf->Cell($CW - 104, 7, 'Invoice #' . $order_id, 0, 2, 'R');

        $pdf->SetFont('helvetica', '', 8);
        $this->text($pdf, self::C_MID);
        $pdf->SetX($L + 104);
        $pdf->Cell($CW - 104, 5, 'Order Date:  ' . $order_date, 0, 2, 'R');

        $pdf->Ln(2);
        $pdf->SetX($L + 104);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $this->text($pdf, self::C_INK);
        $pdf->Cell($CW - 104, 4.5, 'Contact Us:', 0, 2, 'R');

        $pdf->SetX($L + 104);
        $pdf->SetFont('helvetica', '', 8);
        $this->text($pdf, self::C_MID);
        $pdf->Cell($CW - 104, 4.5, self::COMPANY_EMAIL, 0, 2, 'R');

        $pdf->SetX($L + 104);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $this->text($pdf, self::C_INK);
        $pdf->Cell($CW - 104, 4.5, 'Contact Us:', 0, 2, 'R');

        $pdf->SetX($L + 104);
        $pdf->SetFont('helvetica', '', 8);
        $this->text($pdf, self::C_MID);
        $pdf->Cell($CW - 104, 4.5, self::COMPANY_PHONE, 0, 2, 'R');

        // Horizontal rule under header
        $divY = max($pdf->GetY(), $y + 26) + 2;
        $pdf->SetDrawColor(...self::C_BORDER);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($L, $divY, $L + $CW, $divY);
        $pdf->SetY($divY + 2);
    }

    // ────────────────────────────────────────────────────────────────────
    //  UTILITY HELPERS
    // ────────────────────────────────────────────────────────────────────

    private function t(string $s): string
    {
        return trim(html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function itemRowHeight(TCPDF $pdf, string $text, float $cellWidth): float
    {
        $pdf->SetFont('helvetica', '', 8);
        $lines = $pdf->getNumLines($text, $cellWidth);
        return max(10.0, ($lines * 4.2) + 4.0);
    }

    private function vline(TCPDF $pdf, float $x, float $y, float $h, array $rgb): void
    {
        $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->SetLineWidth(0.25);
        $pdf->Line($x, $y, $x, $y + $h);
    }

    private function fill(TCPDF $pdf, array $rgb): void
    {
        $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    }

    private function text(TCPDF $pdf, array $rgb): void
    {
        $pdf->SetTextColor($rgb[0], $rgb[1], $rgb[2]);
    }

    private function fmt(float $n): string
    {
        return number_format($n, 2);
    }

    private function formatDate(string $date): string
    {
        $ts = strtotime($date);
        return ($ts !== false) ? date('F d, Y', $ts) : $date;
    }
}
