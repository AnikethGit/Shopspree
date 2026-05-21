<?php
require_once __DIR__ . '/../config/db.php';

$order_id      = sanitize($_GET['order_id'] ?? '');
$email_lookup  = strtolower(trim($_GET['email'] ?? ''));
$order         = null;
$order_items   = [];
$error_message = '';

if (!empty($order_id) && !empty($email_lookup)) {
    // Require BOTH order ID and email — prevents order enumeration
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND LOWER(email) = ?");
    $stmt->execute([$order_id, $email_lookup]);
    $order = $stmt->fetch();

    if ($order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order_items = $stmt->fetchAll();
    } else {
        $error_message = 'Order not found. Please verify your Order ID and the email address used at checkout.';
    }
} elseif (!empty($order_id) || !empty($email_lookup)) {
    $error_message = 'Please enter both your Order ID and the email address used when placing the order.';
}

$status_colors = [
    'Pending'    => '#FFC107',
    'Processing' => '#17A2B8',
    'Shipped'    => '#0D6EFD',
    'Delivered'  => '#28A745',
    'Cancelled'  => '#DC3545',
];

$current_status = $order ? $order['order_status'] : '';
$status_color   = $status_colors[$current_status] ?? '#6C757D';
$page_title   = 'Track Your Order — PrintDepotCo';
$active_nav   = 'track';
$meta_noindex = true;
$show_search  = true;
$extra_head   = '<style>
        .nav-bar { background-color: #0D6EFD; }
        .page-header { background: linear-gradient(135deg, #ffffff 0%, #c9a961 100%); color: grey; padding: 60px 0; margin-bottom: 40px; }
        .page-header h1 { font-weight: 700; font-size: 2.5rem; margin-bottom: 10px; }
        .tracking-form-card, .order-card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); padding: 30px; margin-bottom: 30px; }
        .tracking-form-card h3 { color: #0D6EFD; font-weight: 700; margin-bottom: 20px; }
        .btn-track { background: #0D6EFD; color: white; border: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color .3s; }
        .btn-track:hover { background: #0056B3; color: white; }
        .order-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 2px solid #e0e0e0; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .status-badge { background: ' . $status_color . '; color: white; padding: 10px 20px; border-radius: 20px; font-weight: 600; }
        .timeline-item { display: flex; margin-bottom: 30px; position: relative; }
        .timeline-item:not(:last-child)::before { content: \'\'; position: absolute; left: 19px; top: 60px; width: 2px; height: 40px; background: #e0e0e0; }
        .timeline-marker { width: 40px; height: 40px; background: #e0e0e0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin-right: 20px; flex-shrink: 0; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .timeline-marker.completed { background: #28A745; }
        .timeline-marker.current { background: #0D6EFD; box-shadow: 0 0 0 4px rgba(13,110,253,.2); }
        .detail-item { background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #0D6EFD; }
        .detail-label { color: #666; font-weight: 600; font-size: .85rem; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
        .detail-value { color: #333; font-size: 1rem; font-weight: 600; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .items-table th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #e0e0e0; }
        .items-table td { padding: 15px; border-bottom: 1px solid #e0e0e0; color: #666; }
        .order-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
        .section-title { color: #333; font-weight: 700; font-size: 1.3rem; margin: 30px 0 20px; }
        .error-alert { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px 20px; border-radius: 6px; margin-bottom: 20px; }
        .no-result { text-align: center; padding: 60px 20px; background: white; border-radius: 8px; }
    </style>';
require_once __DIR__ . '/../includes/header.php';
?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1><i class="fas fa-box me-3"></i>Track Your Order</h1>
                    <p class="mb-0">Enter your Order ID to check delivery status</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="margin-bottom:60px;">
        <!-- Track Form -->
        <div class="tracking-form-card wow fadeInUp" data-wow-delay="0.1s">
            <h3><i class="fas fa-search me-2"></i>Find Your Order</h3>
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="order_id" class="form-control" placeholder="Order ID (e.g., ORD-XXXXXXXX)"
                        value="<?php echo htmlspecialchars($order_id); ?>" required>
                    <small class="text-muted d-block mt-2">Your Order ID is in your confirmation email.</small>
                </div>
                <div class="col-md-5">
                    <input type="email" name="email" class="form-control" placeholder="Email address used at checkout"
                        value="<?php echo htmlspecialchars($email_lookup); ?>" required>
                    <small class="text-muted d-block mt-2">Must match the email used when ordering.</small>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-track w-100" style="height:58px;">
                        <i class="fas fa-search me-2"></i>Track
                    </button>
                </div>
            </form>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-alert wow fadeInUp" data-wow-delay="0.2s">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>
            <!-- Order header -->
            <div class="order-card wow fadeInUp" data-wow-delay="0.2s">
                <div class="order-header">
                    <div>
                        <h2><?php echo htmlspecialchars($order['order_id']); ?></h2>
                        <div class="text-muted">Ordered on <?php echo date('F d, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="status-badge"><?php echo htmlspecialchars($order['order_status']); ?></div>
                </div>

                <!-- Timeline -->
                <h4 class="section-title"><i class="fas fa-tasks me-2" style="color:#0D6EFD;"></i>Order Timeline</h4>
                <div class="timeline">
                    <?php
                    $statuses      = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                    $current_index = array_search($current_status, $statuses);
                    foreach ($statuses as $index => $status):
                        $is_completed  = $current_index !== false && $index < $current_index;
                        $is_current    = $current_status === $status;
                        $marker_class  = $is_completed ? 'completed' : ($is_current ? 'current' : '');
                        $timestamp     = null;
                        if ($status === 'Pending' && !empty($order['created_at'])) {
                            $timestamp = $order['created_at'];
                        } elseif ($current_index !== false && $index <= $current_index && !empty($order['updated_at'])) {
                            $timestamp = $order['updated_at'];
                        }
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-marker <?php echo $marker_class; ?>">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="timeline-content" style="padding-top:5px;">
                            <h4 style="margin:0 0 5px;font-weight:600;"><?php echo htmlspecialchars($status); ?></h4>
                            <p style="color:#666;margin:0;">
                                <?php echo $is_completed ? 'Completed' : ($is_current ? 'Current Status' : 'Upcoming'); ?>
                            </p>
                            <?php if ($timestamp): ?>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php
                                        $dt = new DateTime($timestamp, new DateTimeZone('UTC'));
                                        echo $dt->format('d M Y, h:i A') . ' UTC';
                                    ?>
                                </small>
                            <?php elseif (!$is_completed && !$is_current): ?>
                                <small class="text-muted d-block mt-1"><i class="fas fa-hourglass-half me-1"></i>Awaiting</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order info -->
            <div class="order-card wow fadeInUp" data-wow-delay="0.3s">
                <h4 class="section-title"><i class="fas fa-info-circle me-2" style="color:#0D6EFD;"></i>Order Information</h4>
                <div class="order-details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Shipping Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Payment Method</div>
                        <div class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Order Total</div>
                        <div class="detail-value" style="color:#0D6EFD;font-size:1.2rem;">
                            $<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Order Status</div>
                        <div class="detail-value" style="color:<?php echo $status_color; ?>;">
                            <?php echo htmlspecialchars($order['order_status']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <?php if (!empty($order_items)): ?>
            <div class="order-card wow fadeInUp" data-wow-delay="0.4s">
                <h4 class="section-title"><i class="fas fa-shopping-bag me-2" style="color:#0D6EFD;"></i>Items in Your Order</h4>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                            <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                            <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-end"><strong>$<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <a href="../index.php" class="text-primary fw-bold d-inline-flex align-items-center gap-2 wow fadeInUp" data-wow-delay="0.5s">
                <i class="fas fa-arrow-left"></i>Back to Home
            </a>

        <?php else: ?>
            <div class="no-result wow fadeInUp" data-wow-delay="0.2s">
                <i class="fas fa-search fa-4x text-secondary mb-4 d-block"></i>
                <h3>No Order Found</h3>
                <p class="text-muted">Enter your Order ID above to track your order.</p>
            </div>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
