<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/product/get_products.php';
require_once __DIR__ . '/cart/cart_handler.php';

$product_id = intval($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: shop.php');
    exit;
}

// Get product details
$stmt = $pdo->prepare(
    "SELECT p.*, c.name as category_name
     FROM products p LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.id = ? AND p.is_active = 1"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: shop.php');
    exit;
}

$messages   = get_messages();
$categories = get_categories();

$page_title       = htmlspecialchars($product['name']) . ' — PrintDepotCo';
$active_nav       = 'shop';
$meta_description = htmlspecialchars(mb_strimwidth(strip_tags($product['description'] ?? $product['name']), 0, 155, '…'));
$show_search      = true;
require_once __DIR__ . '/includes/header.php';
?>

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
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded" loading="lazy" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 400px; object-fit: cover;">
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
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
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

<?php
$extra_foot = <<<'JS'
<script src="/lib/lightbox/js/lightbox.min.js"></script>
<script>
    $('.btn-plus').click(function() {
        var input = $(this).closest('.quantity').find('.qty-input');
        input.val(parseInt(input.val()) + 1);
    });
    $('.btn-minus').click(function() {
        var input = $(this).closest('.quantity').find('.qty-input');
        var v = parseInt(input.val());
        if (v > 1) input.val(v - 1);
    });
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>