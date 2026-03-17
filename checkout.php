<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/helpers.php';
    
    // Inline cart functions
    function is_cart_empty() {
        if (!isset($_SESSION['cart'])) {
            return true;
        }
        return !is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0;
    }
    
    function get_cart_items() {
        global $conn;
        
        if (!isset($_SESSION['cart'])) {
            return [];
        }
        
        if (!is_array($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
            return [];
        }
        
        $cart_items = [];
        
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity = intval($quantity);
            
            if ($quantity <= 0 || $product_id <= 0) {
                continue;
            }
            
            $query = "SELECT id, name, price FROM products WHERE id = ?";
            
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("i", $product_id);
                
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    
                    if ($result && $result->num_rows > 0) {
                        $product = $result->fetch_assoc();
                        
                        $cart_items[] = [
                            'id' => intval($product['id']),
                            'name' => htmlspecialchars($product['name']),
                            'price' => floatval($product['price']),
                            'quantity' => $quantity
                        ];
                    }
                }
                
                $stmt->close();
            }
        }
        
        return $cart_items;
    }
    
    function calculate_cart_totals($cart_items, $tax_rate = 0.08, $shipping = 50) {
        $subtotal = 0.0;
        
        if (is_array($cart_items)) {
            foreach ($cart_items as $item) {
                $price = isset($item['price']) ? floatval($item['price']) : 0;
                $qty = isset($item['quantity']) ? intval($item['quantity']) : 0;
                $subtotal += ($price * $qty);
            }
        }
        
        $subtotal = round($subtotal, 2);
        $tax = round($subtotal * floatval($tax_rate), 2);
        $total = round($subtotal + $tax + floatval($shipping), 2);
        
        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_rate' => floatval($tax_rate) * 100,
            'shipping' => floatval($shipping),
            'total' => $total,
            'item_count' => count($cart_items)
        ];
    }
    
    // Redirect to cart if empty
    if (is_cart_empty()) {
        header('Location: cart.php');
        exit;
    }
    
    $messages = get_messages();
    $cart_items = get_cart_items();
    $totals = calculate_cart_totals($cart_items, 0.08, 50);
    $cart_total = $totals['total'];
    $user = current_user();
    
} catch (Exception $e) {
    die('ERROR: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout - Shopspree</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Topbar Start -->
    <div class="container-fluid px-5 d-none border-bottom d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-4 text-center text-lg-start mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a href="#" class="text-muted me-2"> Help</a><small> / </small>
                    <a href="#" class="text-muted mx-2"> Support</a><small> / </small>
                    <a href="#" class="text-muted ms-2"> Contact</a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-flex align-items-center justify-content-center">
                <small class="text-dark">Call Us:</small>
                <a href="#" class="text-muted">(+012) 1234 567890</a>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted me-2" data-bs-toggle="dropdown"><small>USD</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item"> Euro</a>
                            <a href="#" class="dropdown-item"> Dolar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <a href="index.php" class="navbar-brand p-0">
                    <h1 class="display-5 text-primary m-0"><i class="fas fa-shopping-bag text-secondary me-2"></i>Electro</h1>
                </a>
            </div>
            <div class="col-md-4 col-lg-6 text-center">
                <div class="position-relative ps-4">
                    <div class="d-flex border rounded-pill">
                        <input class="form-control border-0 rounded-pill w-100 py-3" type="text" placeholder="Search Looking For?">
                        <select class="form-select text-dark border-0 border-start rounded-0 p-3" style="width: 200px;">
                            <option value="All Category">All Category</option>
                        </select>
                        <button type="button" class="btn btn-primary rounded-pill py-3 px-5" style="border: 0;"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 text-center text-lg-end">
                <a href="cart.php" class="text-muted d-flex align-items-center justify-content-center"><span class="rounded-circle btn-md-square border"><i class="fas fa-shopping-cart"></i></span>
                    <span class="text-dark ms-2">$<?php echo number_format($cart_total, 2); ?></span></a>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-12 col-lg-9 ms-auto">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="index.php" class="navbar-brand d-block d-lg-none">
                        <h1 class="display-5 text-secondary m-0"><i class="fas fa-shopping-bag text-white me-2"></i>Electro</h1>
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="index.php" class="nav-item nav-link">Home</a>
                            <a href="shop.php" class="nav-item nav-link">Shop</a>
                            <a href="cart.php" class="nav-item nav-link">Cart</a>
                            <a href="checkout.php" class="nav-item nav-link active">Checkout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Checkout</h1>
    </div>
    <!-- Single Page Header End -->

    <!-- Checkout Page Start -->
    <div class="container-fluid py-5">
        <div class="container py-5">
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($msg['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <div class="row g-5">
                <div class="col-md-12 col-lg-8">
                    <!-- Single form containing ALL checkout fields -->
                    <form method="post" action="orders/create.php">
                        <h5 class="mb-4">Billing Details</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInput" placeholder="Full Name" name="full_name" required>
                                    <label for="floatingInput">Full Name</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="floatingInputEmail" placeholder="name@example.com" name="email" required>
                                    <label for="floatingInputEmail">Email Address</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputAddress" placeholder="1234 Main St" name="address" required>
                                    <label for="floatingInputAddress">Address</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputCity" placeholder="New York" name="city" required>
                                    <label for="floatingInputCity">City</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputState" placeholder="NY" name="state" required>
                                    <label for="floatingInputState">State</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="floatingInputZip" placeholder="10001" name="postal_code" required>
                                    <label for="floatingInputZip">Postal Code</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="floatingInputPhone" placeholder="+1234567890" name="phone" required>
                                    <label for="floatingInputPhone">Phone</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check d-flex bg-light rounded p-3">
                                    <input class="form-check-input bg-primary border-0" type="checkbox" id="Account" name="Account" value="Account">
                                    <label class="form-check-label pt-1 ps-2" for="Account">
                                        Create an account
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a comment here" id="floatingTextarea" name="notes"></textarea>
                                    <label for="floatingTextarea">Order Notes (Optional)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Section Inside Form -->
                        <h5 class="mb-4 mt-5">Payment Method</h5>
                        <div class="row g-4 text-start mb-4">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="COD" value="COD" checked>
                                    <label class="form-check-label pt-1" for="COD">
                                        Cash on Delivery (COD)
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="Card" value="Credit Card">
                                    <label class="form-check-label pt-1" for="Card">
                                        Credit/Debit Card
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input bg-primary border-0" type="radio" name="payment_method" id="Bank" value="Bank Transfer">
                                    <label class="form-check-label pt-1" for="Bank">
                                        Bank Transfer
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Button Inside Form -->
                        <button type="submit" class="btn btn-primary rounded-pill px-4 py-3 text-uppercase w-100" name="placeOrder">Place Order</button>
                    </form>
                </div>

                <!-- Order Summary Sidebar -->
                <div class="col-md-12 col-lg-4">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Products</th>
                                    <th scope="col">Quantity</th>
                                    <th scope="col">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 mt-4"><?php echo htmlspecialchars($item['name']); ?></p>
                                    </th>
                                    <td>
                                        <p class="mb-0 mt-4"><?php echo (int)$item['quantity']; ?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 mt-4">$<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></p>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-4">Subtotal</p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-4">$<?php echo number_format((float)$totals['subtotal'], 2); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-4">Shipping</p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-4">Flat Rate: $<?php echo number_format((float)$totals['shipping'], 2); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-4">Tax</p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-4">$<?php echo number_format((float)$totals['tax'], 2); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <p class="mb-0 py-3"><strong>Total</strong></p>
                                    </th>
                                    <td colspan="2">
                                        <p class="mb-0 py-3"><strong>$<?php echo number_format((float)$totals['total'], 2); ?></strong></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <a href="cart.php" class="btn btn-outline-primary rounded-pill px-4 py-3 text-uppercase w-100 mt-2">Back to Cart</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Checkout Page End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary btn-lg-square back-to-top"><i class="fa fa-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>