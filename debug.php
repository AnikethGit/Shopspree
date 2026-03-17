<?php
// Enable ALL error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debugging Index.php</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test 1: Can we include db.php?
echo "<h2>Test 1: Loading config/db.php</h2>";
try {
    require_once 'config/db.php';
    echo "<p style='color:green'>✓ db.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error loading db.php: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Can we connect to database?
echo "<h2>Test 2: Database Connection</h2>";
if (isset($conn) && $conn->ping()) {
    echo "<p style='color:green'>✓ Database connection working</p>";
} else {
    echo "<p style='color:red'>✗ Database connection failed</p>";
    exit;
}

// Test 3: Can we start session?
echo "<h2>Test 3: Session Management</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p style='color:green'>✓ Session started successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Session error: " . $e->getMessage() . "</p>";
}

// Test 4: Can we query categories?
echo "<h2>Test 4: Categories Query</h2>";
try {
    $stmt = $conn->prepare("SELECT categories.id, categories.name, COUNT(*) as count FROM categories LEFT JOIN products ON categories.id = products.category_id GROUP BY categories.id LIMIT 5");
    if (!$stmt) {
        echo "<p style='color:red'>✗ Prepare failed: " . $conn->error . "</p>";
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        echo "<p style='color:green'>✓ Query executed, found " . $result->num_rows . " categories</p>";
        $stmt->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Query error: " . $e->getMessage() . "</p>";
}

// Test 5: Load the actual index.php
echo "<h2>Test 5: Loading index.php</h2>";
echo "<p>Attempting to include index.php...</p>";
try {
    include 'index.php';
    echo "<p style='color:green'>✓ index.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error in index.php: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

if ($conn) {
    $conn->close();
}
?>
