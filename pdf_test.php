<?php
// ── TEMPORARY DIAGNOSTIC — delete this file after fixing ──────────

echo '<pre>';

// 1. PHP info
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n";
echo "Max execution time: " . ini_get('max_execution_time') . "\n";
echo "ini_set works: ";
@ini_set('memory_limit', '256M');
echo (ini_get('memory_limit') === '256M' ? 'YES' : 'NO - blocked by host') . "\n\n";

// 2. Directory checks
$invoiceDir = __DIR__ . '/invoices/';
echo "Invoices dir path: " . $invoiceDir . "\n";
echo "Invoices dir exists: " . (is_dir($invoiceDir) ? 'YES' : 'NO') . "\n";
echo "Invoices dir writable: " . (is_writable($invoiceDir) ? 'YES' : 'NO - fix permissions') . "\n\n";

// 3. Logo checks
// Adjust this path to match your COMPANY_LOGO constant
$logo = __DIR__ . '/img/printdepotco-logo.png';
echo "Logo path: " . $logo . "\n";
echo "Logo exists: " . (file_exists($logo) ? 'YES' : 'NO') . "\n";
if (file_exists($logo)) {
    $size = filesize($logo);
    echo "Logo size: " . $size . " bytes (" . round($size/1024) . " KB)\n";
    echo "Logo size OK: " . ($size < 200000 ? 'YES' : 'NO - too large, resize to under 200KB') . "\n";
    $info = getimagesize($logo);
    echo "Logo dimensions: " . $info[0] . " x " . $info[1] . " px\n";
}
echo "\n";

// 4. TCPDF check
$tcpdfPath = __DIR__ . '/lib/tcpdf/tcpdf.php';
echo "TCPDF path: " . $tcpdfPath . "\n";
echo "TCPDF exists: " . (file_exists($tcpdfPath) ? 'YES' : 'NO') . "\n\n";

// 5. Try creating the invoices directory if missing
if (!is_dir($invoiceDir)) {
    $made = mkdir($invoiceDir, 0755, true);
    echo "Tried to create invoices dir: " . ($made ? 'SUCCESS' : 'FAILED - create it manually') . "\n";
} else {
    // 6. Try writing a test file to it
    $testFile = $invoiceDir . 'write_test.tmp';
    $written  = file_put_contents($testFile, 'test');
    echo "Write test to invoices dir: " . ($written !== false ? 'SUCCESS' : 'FAILED - fix permissions') . "\n";
    if ($written !== false) {
        unlink($testFile);
        echo "Cleanup test file: OK\n";
    }
}

echo "\n";
echo "=== All checks done ===\n";
echo '</pre>';
```

---

### Step 2 — Open it in your browser

Go to:
```
https://yourdomain.com/pdf_test.php