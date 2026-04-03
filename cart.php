<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Only load absolutely necessary files
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/helpers.php';
    
    // Inline cart functions to avoid dependencies
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
    
    function calculate_cart_totals($cart_items, $tax_rate = 0.06, $shipping = 50) {
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
    
    // Initialize SESSION cart if not exists
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Handle cart updates from forms
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['action']) && isset($_POST['product_id'])) {
            $action = htmlspecialchars($_POST['action']);
            $product_id = intval($_POST['product_id']);
            $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
            
            if ($action == 'update') {
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$product_id]);
                } else {
                    $_SESSION['cart'][$product_id] = $qty;
                }
            } elseif ($action == 'remove') {
                unset($_SESSION['cart'][$product_id]);
            }
            
            header('Location: cart.php');
            exit;
        }
    }
    
    $messages = get_messages();
    $cart_items = get_cart_items();
    $totals = calculate_cart_totals($cart_items);
    $cart_total = $totals['total'];
    
} catch (Exception $e) {
    die('ERROR: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shopping Cart - PrintDepotCo</title>
    <link rel="icon" type="image/x-icon" href="/img/favicon.png">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
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
    <link href="../css/bootstrap.min.css" rel="stylesheet">
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
                    <a href="contact.php" class="text-muted ms-2">Contact</a>
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
                            <a href="#" class="dropdown-item">Euro</a>
                            <a href="#" class="dropdown-item">Dollar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <div class="d-inline-flex align-items-center">
                    <a href="" class="navbar-brand p-0">
                        <img src="/img/printdepotco-icon.png" alt="Printdepotco" 
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                </div>
            </div>
            <div class="col-md-4 col-lg-6 text-center">
                <div class="position-relative ps-4">
                    <form method="GET" action="shop.php" class="d-flex border rounded-pill">
                        <input class="form-control border-0 rounded-pill w-100 py-3" type="text" name="search" placeholder="Search Looking For?" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn btn-primary rounded-pill py-3 px-5" style="border: 0;"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 text-center text-lg-end">
                <div class="d-inline-flex align-items-center">
                    <a href="cart.php" class="text-muted d-flex align-items-center justify-content-center"><span
                        class="rounded-circle btn-md-square border"><i class="fas fa-shopping-cart"></i></span></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="navbar navbar-light position-relative" style="width: 250px;">
                </nav>
            </div>
            <div class="col-12 col-lg-9">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary ">
                    <a href="index.php" class="navbar-brand d-block d-lg-none">
                        <img src="/img/printdepotco-icon.png" alt="Printdepotco" 
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="index.php" class="nav-item nav-link">Home</a>
                            <a href="shop.php" class="nav-item nav-link">Shop</a>
                            <a href="about.html" class="nav-item nav-link">About Us</a>
                            <a href="orders/track.php" class="nav-item nav-link">Track My Order</a>
                            <a href="#" class="nav-item nav-link active">Cart</a>
                            <a href="contact.php" class="nav-item nav-link">Contact</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Cart Page Start -->
    <div class="container my-5">
        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($msg['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <?php if (count($cart_items) === 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart" style="font-size: 64px; color: #ccc; margin: 20px 0;"></i>
                <p style="font-size: 20px; color: #666;">Your cart is empty</p>
                <a href="shop.php" class="btn btn-primary btn-lg mt-3">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="table-responsive mb-4">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                    <div class="input-group" style="width: 120px;">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit" name="qty" value="<?php echo max(1, (int)$item['quantity'] - 1); ?>">−</button>
                                        <input type="text" class="form-control form-control-sm text-center" value="<?php echo (int)$item['quantity']; ?>" readonly>
                                        <button class="btn btn-sm btn-outline-secondary" type="submit" name="qty" value="<?php echo (int)$item['quantity'] + 1; ?>">+</button>
                                    </div>
                                </form>
                            </td>
                            <td><strong>$<?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></strong></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['id']; ?>">
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Remove this item from cart?');">Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format((float)$totals['subtotal'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax (<?php echo number_format((float)$totals['tax_rate'], 1); ?>%):</span>
                                <span>$<?php echo number_format((float)$totals['tax'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping:</span>
                                <span>$<?php echo number_format((float)$totals['shipping'], 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <h6 class="mb-0">Total:</h6>
                                <h6 class="mb-0">$<?php echo number_format((float)$totals['total'], 2); ?></h6>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100">Proceed to Checkout</a>
                            <a href="shop.php" class="btn btn-outline-secondary w-100 mt-2">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <!-- Cart Page End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3 wow FadeInUp" data-wow-delay="0.1s">
                    <h5 class="text-light mb-4">Why Choose Us</h5>
                    <p class="mb-4">We provide high-performance printing solutions and expert support to maximize your efficiency and lower your long-term costs.</p>
                    <div class="d-flex align-items-center">
                        <img class="img-fluid flex-shrink-0" src="img/footer-logo.png" alt="">
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow FadeInUp" data-wow-delay="0.3s">
                    <h5 class="text-light mb-4">Address</h5>
                    <p><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                    <p><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                    <p><i class="fa fa-envelope me-3"></i>info@printdepotco.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i
                                class="fab fa-twitter"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i
                                class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i
                                class="fab fa-youtube"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle rounded-0 me-0" href=""><i
                                class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow FadeInUp" data-wow-delay="0.5s">
                    <h5 class="text-light mb-4">Quick Links</h5>
                    <a class="btn btn-link" href="about.html">About Us</a><br>
                    <a class="btn btn-link" href="contact.php">Contact Us</a><br>
                    <a class="btn btn-link" href="terms.html">Terms &amp; Condition</a>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3 wow FadeInUp" data-wow-delay="0.7s">
                    <h5 class="text-light mb-4">Newsletter</h5>
                    <p>Sign up for our newsletter</p>
                    <div class="position-relative w-100 mt-3">
                        <input class="form-control border-light w-100 py-2 ps-4 pe-5" type="text"
                            placeholder="Your Email" style="background: rgba(255, 255, 255, 0.87);">
                        <button type="button"
                            class="btn btn-primary py-2 position-absolute top-0 end-0">SignUp</button>
                    </div> 
                </div>
            </div>
        </div>
        <div class="container-fluid copyright">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="border-bottom" href="#">Print Depot Co</a>, All Right Reserved.
                </div>
                <!-- <div class="text-center text-md-end">
                    Designed By <a class="border-bottom" href="https://github.com/AnikethGit">aniketh_sahu</a>
                </div> -->
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Hide spinner when page loads
        window.addEventListener('load', function() {
            document.getElementById('spinner').classList.remove('show');
        });

        // Scroll animations
        new WOW().init();
    </script>
</body>
</html>