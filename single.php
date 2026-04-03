<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/product/get_products.php';
require_once __DIR__ . '/cart/cart_handler.php';

$product_id = intval($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details
if ($stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?")) {
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $product = $result->fetch_assoc();
        } else {
            header('Location: shop.php');
            exit;
        }
    }
    $stmt->close();
}

$messages = get_messages();
$categories = get_categories();
$cart_total = 0;

if (is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $pid = intval($pid);
        $qty = intval($qty);
        
        if ($stmt = $conn->prepare("SELECT price FROM products WHERE id = ?")) {
            $stmt->bind_param("i", $pid);
            if ($stmt->execute()) {
                $price_result = $stmt->get_result();
                if ($price_result && $price_result->num_rows > 0) {
                    $row = $price_result->fetch_assoc();
                    $cart_total += floatval($row['price']) * $qty;
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - PrintDepotCo</title>
    <link rel="icon" type="image/x-icon" href="/img/favicon.png">
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
    <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">

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
                    <a href="contact.php" class="text-muted ms-2"> Contact</a>
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
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted mx-2" data-bs-toggle="dropdown"><small>English</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item"> English</a>
                            <a href="#" class="dropdown-item"> Turkish</a>
                            <a href="#" class="dropdown-item"> Spanol</a>
                            <a href="#" class="dropdown-item"> Italiano</a>
                        </div>
                    </div>
                    <!-- <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted ms-2" data-bs-toggle="dropdown"><small><i class="fa fa-home me-2"></i> My Dashboard</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item"> Login</a>
                            <a href="#" class="dropdown-item"> Wishlist</a>
                            <a href="#" class="dropdown-item"> My Card</a>
                            <a href="#" class="dropdown-item"> Notifications</a>
                            <a href="#" class="dropdown-item"> Account Settings</a>
                            <a href="#" class="dropdown-item"> My Account</a>
                            <a href="#" class="dropdown-item"> Log Out</a>
                        </div>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <div class="d-inline-flex align-items-center">
                    <a href="index.php" class="navbar-brand p-0">
                        <img src="/img/printdepotco-icon.png" alt="Printdepotco" 
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                </div>
            </div>
            <div class="col-md-4 col-lg-6 text-center">
                <div class="position-relative ps-4">
                    <form method="GET" action="shop.php" class="d-flex border rounded-pill">
                        <input class="form-control border-0 rounded-pill w-100 py-3" type="text" name="search" placeholder="Search Looking For?">
                        <button type="submit" class="btn btn-primary rounded-pill py-3 px-5" style="border: 0;"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 text-center text-lg-end">
                <div class="d-inline-flex align-items-center">
                    <a href="cart.php" class="text-muted d-flex align-items-center justify-content-center"><span class="rounded-circle btn-md-square border"><i class="fas fa-shopping-cart"></i></span>
                        <span class="text-dark ms-2">$<?php echo number_format($cart_total, 2); ?></span></a>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="navbar navbar-light position-relative" style="width: 250px;">
                    <button class="navbar-toggler border-0 fs-4 w-100 px-0 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#allCat">
                        <h4 class="m-0"><i class="fa fa-bars me-2"></i>All Categories</h4>
                    </button>
                    <div class="collapse navbar-collapse rounded-bottom" id="allCat">
                        <div class="navbar-nav ms-auto py-0">
                            <ul class="list-unstyled categories-bars">
                                <li><div class='categories-bars-item'><a href='shop.php'>All Products</a></div></li>
                                <?php foreach($categories as $cat): ?>
                                <li><div class='categories-bars-item'><a href='shop.php?category=<?php echo htmlspecialchars($cat['id']); ?>'><?php echo htmlspecialchars($cat['name']); ?></a></div></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
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
                            <a href="cart.php" class="nav-item nav-link">Cart</a>
                            <a href="checkout.php" class="nav-item nav-link">Checkout</a>
                        </div>
                        <a href="#" class="btn btn-secondary rounded-pill py-2 px-4 px-lg-3 mb-3 mb-md-3 mb-lg-0"><i class="fa fa-mobile-alt me-2"></i> +0123 456 7890</a>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Product Details</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </div>
    <!-- Single Page Header End -->

    <!-- Single Products Start -->
    <div class="container-fluid shop py-5">
        <div class="container py-5">
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show mb-4" role="alert">
                    <?php echo htmlspecialchars($msg['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
            
            <div class="row g-4">
                <div class="col-lg-5 col-xl-3 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="product-categories mb-4">
                        <h4>Products Categories</h4>
                        <ul class="list-unstyled">
                            <li><div class="categories-item"><a href="shop.php" class="text-dark"><i class="fas fa-apple-alt text-secondary me-2"></i>All Products</a></div></li>
                            <?php foreach($categories as $cat): ?>
                            <li><div class="categories-item"><a href="shop.php?category=<?php echo htmlspecialchars($cat['id']); ?>" class="text-dark"><i class="fas fa-apple-alt text-secondary me-2"></i><?php echo htmlspecialchars($cat['name']); ?></a><span>(<?php echo htmlspecialchars($cat['count'] ?? 0); ?>)</span></div></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-7 col-xl-9 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="row g-4 single-product">
                        <div class="col-xl-6">
                            <div class="border rounded">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 400px; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 400px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; color: #999; font-size: 18px;">No Image Available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h4>
                            <p class="mb-3">Category: <strong><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></strong></p>
                            <h5 class="fw-bold mb-3"><?php echo format_price($product['price']); ?></h5>
                            <div class="d-flex mb-4">
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star text-secondary"></i>
                                <i class="fa fa-star"></i>
                            </div>
                            <div class="d-flex flex-column mb-3">
                                <small>Product SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></small>
                                <small>Available: <strong class="text-primary"><?php echo intval($product['quantity']); ?> items in stock</strong></small>
                            </div>
                            <p class="mb-4"><?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></p>
                            
                            <?php if ($product['quantity'] > 0): ?>
                                <form method="post" action="cart_actions.php">
                                    <div class="input-group quantity mb-5" style="width: 150px;">
                                        <div class="input-group-btn">
                                            <button class="btn btn-sm btn-minus rounded-circle bg-light border" type="button">
                                                <i class="fa fa-minus"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="text" class="form-control form-control-sm text-center border-0 qty-input" name="quantity" value="1">
                                        <div class="input-group-btn">
                                            <button class="btn btn-sm btn-plus rounded-circle bg-light border" type="button">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary border border-secondary rounded-pill px-4 py-2 mb-4 text-white"><i class="fa fa-shopping-bag me-2"></i> Add to cart</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary rounded-pill px-4 py-2 mb-4" disabled>Out of Stock</button>
                            <?php endif; ?>
                            
                            <div>
                                <h5 class="fw-bold mb-3">Product Details</h5>
                                <p><?php echo htmlspecialchars($product['description'] ?? 'No additional details available'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Single Products End -->

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

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="js/main.js"></script>
    
    <script>
        // Quantity increment/decrement
        $('.btn-plus').click(function() {
            var input = $(this).closest('.quantity').find('.qty-input');
            var currentVal = parseInt(input.val());
            input.val(currentVal + 1);
        });
        
        $('.btn-minus').click(function() {
            var input = $(this).closest('.quantity').find('.qty-input');
            var currentVal = parseInt(input.val());
            if (currentVal > 1) {
                input.val(currentVal - 1);
            }
        });
    </script>
</body>
</html>