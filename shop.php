<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/product/get_products.php';
require_once __DIR__ . '/cart/cart_handler.php';

// Get filter parameters
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$page = get_page_number();

// Fetch products
$result = get_products([
    'category' => $category,
    'search' => $search,
    'page' => $page
], 12);

$products = $result['products'];
$categories = get_categories();
$messages = get_messages();

$page_title       = 'Shop — ' . SITE_NAME;
$active_nav       = 'shop';
$meta_description = 'Browse our full range of printing products — ink cartridges, toner, paper, and accessories.';
$show_search      = true;
require_once __DIR__ . '/includes/header.php';
?>

    <!-- Single Page Header start -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6 wow fadeInUp" data-wow-delay="0.1s">Shop Page</h1>
        <ol class="breadcrumb justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active text-white">Shop</li>
        </ol>
    </div>
    <!-- Single Page Header End -->

    <!-- Services Start -->
    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-6 col-md-4 col-lg-2 border-start border-end wow fadeInUp" data-wow-delay="0.1s">
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
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.2s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fab fa-telegram-plane fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Free Shipping</h6>
                            <p class="mb-0">Free shipping on orders above <?php echo format_price(FREE_SHIPPING_THRESHOLD); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.3s">
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
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.4s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-credit-card fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Receive Gift Card</h6>
                            <p class="mb-0">Receive a gift card on orders over <?php echo format_price(GIFT_CARD_THRESHOLD); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.5s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-phone-alt fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Contact Us</h6>
                            <p class="mb-0"><a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL); ?>" class="text-dark"><?php echo htmlspecialchars(SITE_EMAIL); ?></a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2 border-end wow fadeInUp" data-wow-delay="0.6s">
                <div class="p-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-save fa-2x text-primary"></i>
                        <div class="ms-4">
                            <h6 class="text-uppercase mb-2">Secure Payment</h6>
                            <p class="mb-0">100% secure transactions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Services End -->

    <!-- Main Content Start -->
    <div class="container-fluid py-5">
        <div class="container">
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($msg['text']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <div class="row g-4">
                <!-- Sidebar -->
                <div class="col-lg-3 col-xl-2 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="input-group w-100 mx-auto d-flex mb-4">
                        <form method="get" action="shop.php" class="w-100 d-flex">
                            <input type="text" class="form-control p-3" name="search" placeholder="keywords" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                            <span class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </form>
                    </div>
                    <div class="product-categories mb-4">
                        <h4>Products Categories</h4>
                        <ul class="list-unstyled">
                            <li>
                                <div class="categories-item">
                                    <a href="shop.php" class="text-dark"><i class="fas fa-apple-alt text-secondary me-2"></i>All Products</a>
                                </div>
                            </li>
                            <?php foreach($categories as $cat): ?>
                            <li>
                                <div class="categories-item">
                                    <a href="shop.php?category=<?php echo htmlspecialchars($cat['id']); ?>" class="text-dark"><i class="fas fa-apple-alt text-secondary me-2"></i><?php echo htmlspecialchars($cat['name']); ?></a>
                                    <span>(<?php echo htmlspecialchars($cat['count'] ?? 0); ?>)</span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="col-lg-9 col-xl-10 wow fadeInUp" data-wow-delay="0.1s">
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <p class="text-muted">No products found. Try adjusting your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($products as $product): ?>
                            <div class="col-md-6 col-lg-6 col-xl-4">
                                <div class="product-item rounded">
                                    <div class="product-img border rounded position-relative overflow-hidden">
                                        <?php if ($product['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded" loading="lazy" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 250px; object-fit: cover; width: 100%;">
                                        <?php else: ?>
                                            <div style="height: 250px; display: flex; align-items: center; justify-content: center; background: #f0f0f0; color: #999;">No Image</div>
                                        <?php endif; ?>
                                        <div class="product-action">
                                            <a class="btn btn-primary" href="single.php?id=<?php echo $product['id']; ?>"><i class="fa fa-eye"></i></a>
                                        </div>
                                    </div>
                                    <div class="text-center p-4">
                                        <a href="single.php?id=<?php echo $product['id']; ?>" class="d-block mb-2"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></a>
                                        <a href="single.php?id=<?php echo $product['id']; ?>" class="d-block h4"><?php echo htmlspecialchars($product['name']); ?></a>
                                        <p class="text-muted text-truncate mb-3"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?>...</p>
                                        <div class="d-flex justify-content-center mb-3">
                                            <h5 class="fw-bold me-2"><?php echo format_price($product['price']); ?></h5>
                                        </div>
                                    </div>
                                    <div class="text-center p-4 pt-0">
                                        <?php if ($product['quantity'] > 0): ?>
                                            <form method="post" action="cart_actions.php" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-primary border-secondary rounded-pill py-2 px-4 mb-4"><i class="fas fa-shopping-cart me-2"></i> Add To Cart</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-secondary rounded-pill py-2 px-4 mb-4" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex">
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star text-primary"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <div class="d-flex">
                                                <a href="#" class="text-primary d-flex align-items-center justify-content-center me-3"><span class="rounded-circle btn-sm-square border"><i class="fas fa-sync-alt"></i></span></a>
                                                <a href="#" class="text-primary d-flex align-items-center justify-content-center me-0"><span class="rounded-circle btn-sm-square border"><i class="fas fa-heart"></i></span></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($result['pages'] > 1): ?>
                            <div class="row">
                                <div class="col-12 text-center py-5">
                                    <nav>
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($result['current_page'] > 1): ?>
                                                <li class="page-item"><a class="page-link" href="shop.php?page=1<?php echo $category ? '&category=' . $category : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">First</a></li>
                                                <li class="page-item"><a class="page-link" href="shop.php?page=<?php echo $result['current_page'] - 1; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Previous</a></li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                                                <li class="page-item <?php echo $i === $result['current_page'] ? 'active' : ''; ?>"><a class="page-link" href="shop.php?page=<?php echo $i; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a></li>
                                            <?php endfor; ?>

                                            <?php if ($result['current_page'] < $result['pages']): ?>
                                                <li class="page-item"><a class="page-link" href="shop.php?page=<?php echo $result['current_page'] + 1; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Next</a></li>
                                                <li class="page-item"><a class="page-link" href="shop.php?page=<?php echo $result['pages']; ?><?php echo $category ? '&category=' . $category : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">Last</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Main Content End -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>