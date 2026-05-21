<?php
require_once 'config/db.php';
require_once 'cart/cart_handler.php';

// Handle add-to-cart POST without redirect (cart icon updates in place)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $qty        = intval($_POST['qty'] ?? 1);
    if ($product_id > 0 && $qty > 0) {
        add_to_cart($product_id, $qty);
    }
}

// Categories
$stmt = $pdo->query(
    "SELECT c.id, c.name, COUNT(p.id) as count
     FROM categories c LEFT JOIN products p ON c.id = p.category_id
     GROUP BY c.id LIMIT 5"
);
$categories_array = $stmt->fetchAll();

// Featured products
$stmt = $pdo->prepare("SELECT * FROM products WHERE featured = 1 AND is_active = 1 LIMIT 4");
$stmt->execute();
$featured_result = $stmt->fetchAll();

// All products
$stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 LIMIT 12");
$stmt->execute();
$all_products_result = $stmt->fetchAll();

// New arrivals — detect column, fallback to latest by created_at
$new_column_found = null;
foreach (['is_new', 'new_product', 'isnew', 'product_new'] as $col) {
    $chk = $pdo->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = ?"
    );
    $chk->execute([$col]);
    if ($chk->fetchColumn()) { $new_column_found = $col; break; }
}
if ($new_column_found) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE `$new_column_found` = 1 AND is_active = 1 LIMIT 12");
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 12");
}
$stmt->execute();
$new_result = $stmt->fetchAll();

// Top selling — detect column, fallback to random
$top_column_found = null;
foreach (['top_selling', 'bestseller', 'is_bestseller', 'topselling'] as $col) {
    $chk = $pdo->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = ?"
    );
    $chk->execute([$col]);
    if ($chk->fetchColumn()) { $top_column_found = $col; break; }
}
if ($top_column_found) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE `$top_column_found` = 1 AND is_active = 1 LIMIT 12");
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY RAND() LIMIT 12");
}
$stmt->execute();
$top_result = $stmt->fetchAll();

$page_title       = 'PrintDepotCo — Printers & Accessories';
$active_nav       = 'home';
$meta_description = 'Print Depot Co — Premium ink cartridges, toner, paper, and printing accessories. Fast delivery, great prices, expert support.';
$show_search      = true;
require_once __DIR__ . '/includes/header.php';
?>

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
                        </ul>
                    </div>
                </div>
                <div class="tab-content">
                    <!-- All Products Tab -->
                    <div id="tab-1" class="tab-pane fade show p-0 active">
                        <div class="row g-4">
                            <?php
                            $delay = 0.1;
                            if (!empty($all_products_result)) {
                                foreach($all_products_result as $product) {
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
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" loading="lazy" alt="<?php echo $product_name; ?>">
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
                            if (!empty($new_result)) {
                                foreach($new_result as $product) {
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
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" loading="lazy" alt="<?php echo $product_name; ?>">
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
                            if (!empty($featured_result)) {
                                foreach($featured_result as $product) {
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
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" loading="lazy" alt="<?php echo $product_name; ?>">
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
                            if (!empty($top_result)) {
                                foreach($top_result as $product) {
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
                                            <img src="<?php echo $image; ?>" class="img-fluid w-100 rounded-top" loading="lazy" alt="<?php echo $product_name; ?>">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
