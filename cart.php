<?php
try {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/cart/get_cart.php';

    // Handle cart updates from forms
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['product_id'])) {
        // CSRF check
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            header('Location: cart.php');
            exit;
        }

        $action     = sanitize($_POST['action']);
        $product_id = intval($_POST['product_id']);
        $qty        = intval($_POST['qty'] ?? 1);

        if ($action === 'update') {
            update_cart_quantity($product_id, $qty);
        } elseif ($action === 'remove') {
            remove_from_cart($product_id);
        }

        header('Location: cart.php');
        exit;
    }

    $messages   = get_messages();
    $cart_items = get_cart_items();
    $totals     = calculate_cart_totals($cart_items, TAX_RATE, STANDARD_SHIPPING);
    $cart_total = $totals['total'];
    
} catch (Exception $e) {
    die('ERROR: ' . htmlspecialchars($e->getMessage()));
}

$page_title   = 'Shopping Cart — ' . SITE_NAME;
$active_nav   = 'cart';
$meta_noindex = true;
$show_search  = true;
require_once __DIR__ . '/includes/header.php';
?>

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
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
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
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>