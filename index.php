<?php
// Include database configuration
require_once 'config/db.php';
// Session is already started in db.php, don't start it again!

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Handle Add to Cart BEFORE ANY OUTPUT (but NO redirect)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    
    if ($product_id > 0 && $qty > 0) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = 0;
        }
        $_SESSION['cart'][$product_id] += $qty;
    }
    // Don't redirect - let the page continue loading so cart icon updates
}

// Fetch categories from database and store in array (not result set)
$categories_array = array();
if ($stmt = $conn->prepare("SELECT categories.id, categories.name, COUNT(*) as count FROM categories LEFT JOIN products ON categories.id = products.category_id GROUP BY categories.id LIMIT 5")) {
    if ($stmt->execute()) {
        $categories_result = $stmt->get_result();
        // Store results in array for reuse
        while($row = $categories_result->fetch_assoc()) {
            $categories_array[] = $row;
        }
    } else {
        error_log("Categories query execute error: " . $stmt->error);
    }
    $stmt->close();
} else {
    error_log("Categories query prepare error: " . $conn->error);
}

// Fetch featured products
$featured_result = null;
if ($stmt = $conn->prepare("SELECT * FROM products WHERE featured = 1 LIMIT 4")) {
    if ($stmt->execute()) {
        $featured_result = $stmt->get_result();
    } else {
        error_log("Featured products query execute error: " . $stmt->error);
    }
    $stmt->close();
}

// Fetch all products for main tab
$all_products_result = null;
if ($stmt = $conn->prepare("SELECT * FROM products LIMIT 12")) {
    if ($stmt->execute()) {
        $all_products_result = $stmt->get_result();
    } else {
        error_log("All products query execute error: " . $stmt->error);
    }
    $stmt->close();
}

// Fetch new arrivals - try different possible column names
$new_result = null;

// List of possible column names for "new" products
$new_columns = ['is_new', 'new_product', 'isnew', 'product_new'];
$new_column_found = null;

// Test which column exists
foreach ($new_columns as $col) {
    // Use INFORMATION_SCHEMA to check if column exists
    $check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'products' AND COLUMN_NAME = ?";
    if ($check_stmt = $conn->prepare($check_sql)) {
        $check_stmt->bind_param("s", $col);
        if ($check_stmt->execute()) {
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $new_column_found = $col;
                $check_stmt->close();
                break;
            }
        }
        $check_stmt->close();
    }
}

// If column found, use it; otherwise get recent products
if ($new_column_found) {
    // Build query with the actual column name
    $new_query = "SELECT * FROM products WHERE " . $new_column_found . " = 1 LIMIT 12";
    if ($stmt = $conn->prepare($new_query)) {
        if ($stmt->execute()) {
            $new_result = $stmt->get_result();
        } else {
            error_log("New arrivals query execute error: " . $stmt->error);
        }
        $stmt->close();
    }
} else {
    // Fallback: get recent products by ID
    if ($stmt = $conn->prepare("SELECT * FROM products ORDER BY id DESC LIMIT 12")) {
        if ($stmt->execute()) {
            $new_result = $stmt->get_result();
        } else {
            error_log("New arrivals fallback query execute error: " . $stmt->error);
        }
        $stmt->close();
    }
}

// Fetch top selling products - try different column names
$top_result = null;

// List of possible column names for "top selling" products
$top_columns = ['top_selling', 'bestseller', 'is_bestseller', 'topselling'];
$top_column_found = null;

// Test which column exists
foreach ($top_columns as $col) {
    $check_sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_NAME = 'products' AND COLUMN_NAME = ?";
    if ($check_stmt = $conn->prepare($check_sql)) {
        $check_stmt->bind_param("s", $col);
        if ($check_stmt->execute()) {
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $top_column_found = $col;
                $check_stmt->close();
                break;
            }
        }
        $check_stmt->close();
    }
}

// If column found, use it; otherwise get random products
if ($top_column_found) {
    // Build query with the actual column name
    $top_query = "SELECT * FROM products WHERE " . $top_column_found . " = 1 LIMIT 12";
    if ($stmt = $conn->prepare($top_query)) {
        if ($stmt->execute()) {
            $top_result = $stmt->get_result();
        } else {
            error_log("Top selling query execute error: " . $stmt->error);
        }
        $stmt->close();
    }
} else {
    // Fallback: get random products
    if ($stmt = $conn->prepare("SELECT * FROM products ORDER BY RAND() LIMIT 12")) {
        if ($stmt->execute()) {
            $top_result = $stmt->get_result();
        } else {
            error_log("Top selling fallback query execute error: " . $stmt->error);
        }
        $stmt->close();
    }
}

// Calculate cart total
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
            } else {
                error_log("Cart price query execute error: " . $stmt->error);
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
    <title>PrintDepotCo - Printers & Accessories</title>
    <link rel="icon" type="image/x-icon" href="/img/favicon.png">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">

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
    <div id="spinner"
        class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
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
                    <a href="contact.php" class="text-muted ms-2"> Contact Us</a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-flex align-items-center justify-content-center">
                <small class="text-dark">Contact Us:</small>
                <a href="contact.php" class="text-muted">(+012) 1234 567890</a>
            </div>

            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted me-2" data-bs-toggle="dropdown"><small>
                                USD</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item"> Euro</a>
                            <a href="#" class="dropdown-item"> Dollar</a>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted mx-2" data-bs-toggle="dropdown"><small>
                                English</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item"> English</a>
                            <a href="#" class="dropdown-item"> Turkish</a>
                            <a href="#" class="dropdown-item"> Spanish</a>
                            <a href="#" class="dropdown-item"> Italian</a>
                        </div>
                    </div>
                    <!-- <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted ms-2" data-bs-toggle="dropdown"><small><i
                                    class="fa fa-home me-2"></i> My Dashboard</small></a>
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
    <!-- Topbar End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-lg-3 d-none d-lg-block">
                <nav class="navbar navbar-light position-relative" style="width: 250px;">
                    <button class="navbar-toggler border-0 fs-4 w-100 px-0 text-start" type="button"
                        data-bs-toggle="collapse" data-bs-target="#allCat">
                        <h4 class="m-0"><i class="fa fa-bars me-2"></i>All Categories</h4>
                    </button>
                    <div class="collapse navbar-collapse rounded-bottom" id="allCat">
                        <div class="navbar-nav ms-auto py-0">
                            <ul class="list-unstyled categories-bars">
                                <?php
                                foreach($categories_array as $cat) {
                                    echo "<li>
                                        <div class='categories-bars-item'>
                                            <a href='shop.php?category=" . htmlspecialchars($cat['id']) . "'>" . htmlspecialchars($cat['name']) . "</a>
                                            <span>(" . htmlspecialchars($cat['count']) . ")</span>
                                        </div>
                                    </li>";
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
            <div class="col-12 col-lg-9">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary ">
                    <a href="" class="navbar-brand d-block d-lg-none">
                        <img src="/img/printdepotco-icon.png" alt="Printdepotco" 
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="index.php" class="nav-item nav-link active">Home</a>
                            <a href="shop.php" class="nav-item nav-link">Shop</a>
                            <a href="about.html" class="nav-item nav-link">About Us</a>
                            <a href="orders/track.php" class="nav-item nav-link">Track Orders</a>
                            <!-- <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-0">
                                    <a href="shop.php" class="dropdown-item">All Products</a>
                                    <a href="cart.php" class="dropdown-item">Cart Page</a>
                                    <a href="checkout.php" class="dropdown-item">Checkout</a>
                                </div>
                            </div> -->
                            <a href="cart.php" class="nav-item nav-link">Cart</a>
                            <a href="contact.php" class="nav-item nav-link me-2">Contact</a>
                            <div class="nav-item dropdown d-block d-lg-none mb-3">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">All Category</a>
                                <div class="dropdown-menu m-0">
                                    <ul class="list-unstyled categories-bars">
                                        <?php
                                        foreach($categories_array as $cat) {
                                            echo "<li>
                                                <div class='categories-bars-item'>
                                                    <a href='shop.php?category=" . htmlspecialchars($cat['id']) . "'>" . htmlspecialchars($cat['name']) . "</a>
                                                    <span>(" . htmlspecialchars($cat['count']) . ")</span>
                                                </div>
                                            </li>";
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- <a href="" class="btn btn-secondary rounded-pill py-2 px-4 px-lg-3 mb-3 mb-md-3 mb-lg-0"><i
                                class="fa fa-mobile-alt me-2"></i> +0123 456 7890</a> -->
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Carousel Start -->
    <div class="container-fluid carousel bg-light px-0">
        <div class="row g-0 justify-content-end">
            <div class="col-12 col-lg-7 col-xl-9">
                <div class="header-carousel owl-carousel bg-light py-5">
                    <div class="row g-0 header-carousel-item align-items-center">
                        <div class="col-xl-6 carousel-img wow fadeInLeft" data-wow-delay="0.1s">
                            <img src="img/carousel-1.png" class="img-fluid w-100" alt="Image">
                        </div>
                        <div class="col-xl-6 carousel-content p-4">
                            <h4 class="text-uppercase fw-bold mb-4 wow FadeInRight" data-wow-delay="0.1s"
                                style="letter-spacing: 3px;">Save Up To A $400</h4>
                            <h1 class="display-3 text-capitalize mb-4 wow FadeInRight" data-wow-delay="0.3s">On Selected
                                Laptops &amp; Desktop Or Smartphone</h1>
                            <p class="text-dark wow FadeInRight" data-wow-delay="0.5s">Terms and Condition Apply</p>
                            <a class="btn btn-primary rounded-pill py-3 px-5 wow FadeInRight" data-wow-delay="0.7s"
                                href="shop.php">Shop Now</a>
                        </div>
                    </div>
                    <div class="row g-0 header-carousel-item align-items-center">
                        <div class="col-xl-6 carousel-img wow FadeInLeft" data-wow-delay="0.1s">
                            <img src="img/carousel-2.png" class="img-fluid w-100" alt="Image">
                        </div>
                        <div class="col-xl-6 carousel-content p-4">
                            <h4 class="text-uppercase fw-bold mb-4 wow FadeInRight" data-wow-delay="0.1s"
                                style="letter-spacing: 3px;">Save Up To A $200</h4>
                            <h1 class="display-3 text-capitalize mb-4 wow FadeInRight" data-wow-delay="0.3s">On Selected
                                Laptops &amp; Desktop Or Smartphone</h1>
                            <p class="text-dark wow FadeInRight" data-wow-delay="0.5s">Terms and Condition Apply</p>
                            <a class="btn btn-primary rounded-pill py-3 px-5 wow FadeInRight" data-wow-delay="0.7s"
                                href="shop.php">Shop Now</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5 col-xl-3 wow FadeInRight" data-wow-delay="0.1s">
                <div class="carousel-header-banner h-100">
                    <img src="img/header-img.jpg" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="Image">
                    <div class="carousel-banner-offer">
                        <p class="bg-primary text-white rounded fs-5 py-2 px-4 mb-0 me-3">Save 20%</p>
                        <p class="text-primary fs-5 fw-bold mb-0">Special Offer</p>
                    </div>
                    <div class="carousel-banner">
                        <div class="carousel-banner-content text-center p-4">
                            <a href="#" class="d-block mb-2">SmartPhone</a>
                            <a href="#" class="d-block text-white fs-3">Apple iPad Mini <br> G2356</a>
                            <del class="me-2 text-white fs-5">$1,250.00</del>
                            <span class="text-primary fs-5">$1,050.00</span>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="product_id" value="1">
                            <!-- <button type="submit" class="btn btn-primary rounded-pill py-2 px-4" style="border: none; cursor: pointer;"><i
                                    class="fas fa-shopping-cart me-2"></i> Add To Cart</button> -->
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->

    <!-- Services Start -->
    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-6 col-md-4 col-lg-2 border-start border-end wow FadeInUp" data-wow-delay="0.1s">
                <div class="p-4">
                    <div class="d-inline-flex align-items-center">
                        <i class="fa fa-sync-alt fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Free Return</h6>
                            <p class="mb-0">30 days money back guarantee!</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow FadeInUp" data-wow-delay="0.2s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-telegram-plane fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Free Shipping</h6>
                            <p class="mb-0">Free shipping on orders above $200</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow FadeInUp" data-wow-delay="0.3s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-life-ring fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Support 24/7</h6>
                            <p class="mb-0">We support online 24 hrs a day</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow FadeInUp" data-wow-delay="0.4s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-credit-card fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Receive Gift Card</h6>
                            <p class="mb-0">Receive gift all over order $50</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow FadeInUp" data-wow-delay="0.5s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-lock fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Secure Payment</h6>
                            <p class="mb-0">We Value Your Security</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow FadeInUp" data-wow-delay="0.6s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-blog fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Online Service</h6>
                            <p class="mb-0">Free return products in 30 days</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Services End -->

    <!-- Products Offer Start -->
    <div class="container-fluid bg-light py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-6 wow FadeInLeft" data-wow-delay="0.2s">
                    <a href="shop.php?category=1" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                        <div>
                            <p class="text-muted mb-3">Find The Best Camera for You!</p>
                            <h3 class="text-primary">Smart Camera</h3>
                            <h1 class="display-3 text-secondary mb-0">40% <span
                                    class="text-primary fw-normal">Off</span></h1>
                        </div>
                        <img src="img/product-1.png" class="img-fluid" alt="">
                    </a>
                </div>
                <div class="col-lg-6 wow FadeInRight" data-wow-delay="0.3s">
                    <a href="shop.php?category=2" class="d-flex align-items-center justify-content-between border bg-white rounded p-4">
                        <div>
                            <p class="text-muted mb-3">Find The Best Watches for You!</p>
                            <h3 class="text-primary">Smart Watch</h3>
                            <h1 class="display-3 text-secondary mb-0">20% <span
                                    class="text-primary fw-normal">Off</span></h1>
                        </div>
                        <img src="img/product-2.png" class="img-fluid" alt="">
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Products Offer End -->


    <!-- Our Products Start -->
    <div class="container-fluid product py-5">
        <div class="container py-5">
            <div class="tab-class">
                <div class="row g-4">
                    <div class="col-lg-4 text-start wow FadeInLeft" data-wow-delay="0.1s">
                        <h1>Our Products</h1>
                    </div>
                    <div class="col-lg-8 text-end wow FadeInRight" data-wow-delay="0.1s">
                        <ul class="nav nav-pills d-inline-flex text-center mb-5">
                            <li class="nav-item mb-4">
                                <a class="d-flex mx-2 py-2 bg-light rounded-pill active" data-bs-toggle="pill"
                                    href="#tab-1">
                                    <span class="text-dark" style="width: 130px;">All Products</span>
                                </a>
                            </li>
                            <!-- <li class="nav-item mb-4">
                                <a class="d-flex py-2 mx-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-2">
                                    <span class="text-dark" style="width: 130px;">New Arrivals</span>
                                </a>
                            </li>
                            <li class="nav-item mb-4">
                                <a class="d-flex mx-2 py-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-3">
                                    <span class="text-dark" style="width: 130px;">Featured</span>
                                </a>
                            </li>
                            <li class="nav-item mb-4">
                                <a class="d-flex mx-2 py-2 bg-light rounded-pill" data-bs-toggle="pill" href="#tab-4">
                                    <span class="text-dark" style="width: 130px;">Top Selling</span>
                                </a>
                            </li> -->
                        </ul>
                    </div>
                </div>
                <div class="tab-content">
                    <!-- All Products Tab -->
                    <div id="tab-1" class="tab-pane fade show p-0 active">
                        <div class="row g-4">
                            <?php
                            $delay = 0.1;
                            if ($all_products_result && $all_products_result->num_rows > 0) {
                                while($product = $all_products_result->fetch_assoc()) {
                                    $product_id = htmlspecialchars($product['id'] ?? '');
                                    $product_name = htmlspecialchars($product['name'] ?? '');
                                    $product_price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';
                                    $original_price = isset($product['original_price']) ? number_format($product['original_price'], 2) : $product_price;
                                    $image = htmlspecialchars($product['image_url'] ?? '');
                                    $category = htmlspecialchars($product['category'] ?? '');
                                    $category_id = htmlspecialchars($product['category_id'] ?? '');
                                    $badge = '';
                            ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="product-item rounded wow FadeInUp" data-wow-delay="<?php echo $delay; ?>s">
                                    <div class="product-item-inner border rounded">
                                        <div class="product-item-inner-item">
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" alt="<?php echo $product_name; ?>">
                                            <?php if ($badge): ?>
                                            <div class="product-<?php echo strtolower($badge); ?>"><?php echo $badge; ?></div>
                                            <?php endif; ?>
                                            <div class="product-details">
                                                <a href="single.php?id=<?php echo $product_id; ?>"><i class="fa fa-eye fa-1x"></i></a>
                                            </div>
                                        </div>
                                        <div class="text-center rounded-bottom p-4">
                                            <a href="shop.php?category=<?php echo $category_id; ?>" class="d-block mb-2"><?php echo $category; ?></a>
                                            <a href="single.php?id=<?php echo $product_id; ?>" class="d-block h4"><?php echo $product_name; ?></a>
                                            <del class="me-2 fs-5">$<?php echo $original_price; ?></del>
                                            <span class="text-primary fs-5">$<?php echo $product_price; ?></span>
                                        </div>
                                    </div>
                                    <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4" style="border: none; cursor: pointer;"><i
                                                    class="fas fa-shopping-cart me-2"></i> Add To Cart</button>
                                        </form>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex">
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <!-- <div class="d-flex">
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-3"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-sync-alt"></i></span></a>
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-0"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-heart"></i></span></a>
                                            </div> -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                $delay += 0.2;
                                }
                            } else {
                                echo "<p>No products available.</p>";
                            }
                            ?>
                        </div>
                    </div>

                    <!-- New Arrivals Tab -->
                    <div id="tab-2" class="tab-pane fade show p-0">
                        <div class="row g-4">
                            <?php
                            $delay = 0.1;
                            if ($new_result && $new_result->num_rows > 0) {
                                while($product = $new_result->fetch_assoc()) {
                                    $product_id = htmlspecialchars($product['id'] ?? '');
                                    $product_name = htmlspecialchars($product['name'] ?? '');
                                    $product_price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';
                                    $original_price = isset($product['original_price']) ? number_format($product['original_price'], 2) : $product_price;
                                    $image = htmlspecialchars($product['image'] ?? '');
                                    $category = htmlspecialchars($product['category'] ?? '');
                                    $category_id = htmlspecialchars($product['category_id'] ?? '');
                            ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="product-item rounded wow FadeInUp" data-wow-delay="<?php echo $delay; ?>s">
                                    <div class="product-item-inner border rounded">
                                        <div class="product-item-inner-item">
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" alt="<?php echo $product_name; ?>">
                                            <div class="product-new">New</div>
                                            <div class="product-details">
                                                <a href="single.php?id=<?php echo $product_id; ?>"><i class="fa fa-eye fa-1x"></i></a>
                                            </div>
                                        </div>
                                        <div class="text-center rounded-bottom p-4">
                                            <a href="shop.php?category=<?php echo $category_id; ?>" class="d-block mb-2"><?php echo $category; ?></a>
                                            <a href="single.php?id=<?php echo $product_id; ?>" class="d-block h4"><?php echo $product_name; ?></a>
                                            <del class="me-2 fs-5">$<?php echo $original_price; ?></del>
                                            <span class="text-primary fs-5">$<?php echo $product_price; ?></span>
                                        </div>
                                    </div>
                                    <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <!-- <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4" style="border: none; cursor: pointer;"><i
                                                    class="fas fa-shopping-cart me-2"></i> Add To Cart</button> -->
                                        </form>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex">
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="d-flex">
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-3"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-sync-alt"></i></span></a>
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-0"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-heart"></i></span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                $delay += 0.2;
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Featured Tab -->
                    <div id="tab-3" class="tab-pane fade show p-0">
                        <div class="row g-4">
                            <?php
                            $delay = 0.1;
                            if ($featured_result && $featured_result->num_rows > 0) {
                                while($product = $featured_result->fetch_assoc()) {
                                    $product_id = htmlspecialchars($product['id'] ?? '');
                                    $product_name = htmlspecialchars($product['name'] ?? '');
                                    $product_price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';
                                    $original_price = isset($product['original_price']) ? number_format($product['original_price'], 2) : $product_price;
                                    $image = htmlspecialchars($product['image'] ?? '');
                                    $category = htmlspecialchars($product['category'] ?? '');
                                    $category_id = htmlspecialchars($product['category_id'] ?? '');
                            ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="product-item rounded wow FadeInUp" data-wow-delay="<?php echo $delay; ?>s">
                                    <div class="product-item-inner border rounded">
                                        <div class="product-item-inner-item">
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" alt="<?php echo $product_name; ?>">
                                            <div class="product-details">
                                                <a href="single.php?id=<?php echo $product_id; ?>"><i class="fa fa-eye fa-1x"></i></a>
                                            </div>
                                        </div>
                                        <div class="text-center rounded-bottom p-4">
                                            <a href="shop.php?category=<?php echo $category_id; ?>" class="d-block mb-2"><?php echo $category; ?></a>
                                            <a href="single.php?id=<?php echo $product_id; ?>" class="d-block h4"><?php echo $product_name; ?></a>
                                            <del class="me-2 fs-5">$<?php echo $original_price; ?></del>
                                            <span class="text-primary fs-5">$<?php echo $product_price; ?></span>
                                        </div>
                                    </div>
                                    <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <!-- <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4" style="border: none; cursor: pointer;"><i
                                                    class="fas fa-shopping-cart me-2"></i> Add To Cart</button> -->
                                        </form>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex">
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="d-flex">
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-3"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-sync-alt"></i></span></a>
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-0"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-heart"></i></span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                $delay += 0.2;
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Top Selling Tab -->
                    <div id="tab-4" class="tab-pane fade show p-0">
                        <div class="row g-4">
                            <?php
                            $delay = 0.1;
                            if ($top_result && $top_result->num_rows > 0) {
                                while($product = $top_result->fetch_assoc()) {
                                    $product_id = htmlspecialchars($product['id'] ?? '');
                                    $product_name = htmlspecialchars($product['name'] ?? '');
                                    $product_price = isset($product['price']) ? number_format($product['price'], 2) : '0.00';
                                    $original_price = isset($product['original_price']) ? number_format($product['original_price'], 2) : $product_price;
                                    $image = htmlspecialchars($product['image'] ?? '');
                                    $category = htmlspecialchars($product['category'] ?? '');
                                    $category_id = htmlspecialchars($product['category_id'] ?? '');
                            ?>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="product-item rounded wow FadeInUp" data-wow-delay="<?php echo $delay; ?>s">
                                    <div class="product-item-inner border rounded">
                                        <div class="product-item-inner-item">
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" alt="<?php echo $product_name; ?>">
                                            <div class="product-details">
                                                <a href="single.php?id=<?php echo $product_id; ?>"><i class="fa fa-eye fa-1x"></i></a>
                                            </div>
                                        </div>
                                        <div class="text-center rounded-bottom p-4">
                                            <a href="shop.php?category=<?php echo $category_id; ?>" class="d-block mb-2"><?php echo $category; ?></a>
                                            <a href="single.php?id=<?php echo $product_id; ?>" class="d-block h4"><?php echo $product_name; ?></a>
                                            <del class="me-2 fs-5">$<?php echo $original_price; ?></del>
                                            <span class="text-primary fs-5">$<?php echo $product_price; ?></span>
                                        </div>
                                    </div>
                                    <div class="product-item-add border border-top-0 rounded-bottom text-center p-4 pt-0">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="add_to_cart" value="1">
                                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                            <!-- <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4" style="border: none; cursor: pointer;"><i
                                                    class="fas fa-shopping-cart me-2"></i> Add To Cart</button> -->
                                        </form>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex">
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="d-flex">
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-3"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-sync-alt"></i></span></a>
                                                <a href="#"
                                                    class="text-primary d-flex align-items-center justify-content-center me-0"><span
                                                        class="rounded-circle btn-sm-square border"><i
                                                            class="fas fa-heart"></i></span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                                $delay += 0.2;
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Our Products End -->

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
                    <a class="btn btn-link" href="about.html">About Us</a>
                    <a class="btn btn-link" href="contact.php">Contact Us</a>
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
                <div class="text-center text-md-end">
                    Designed By <a class="border-bottom" href="https://github.com/AnikethGit">aniketh_sahu</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i
            class="fa fa-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>
<?php
if ($conn) {
    $conn->close();
}
?>