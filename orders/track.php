<?php
/**
 * Order Tracking Page
 * Allows customers to track their orders using Order ID
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

$order_id = isset($_GET['order_id']) ? sanitize($_GET['order_id']) : '';
$order = null;
$order_items = [];
$error_message = '';

if (!empty($order_id)) {
    // Fetch order
    $query = "SELECT * FROM orders WHERE order_id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $order_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $order = $result->fetch_assoc();
                
                // Fetch order items
                $items_query = "SELECT * FROM order_items WHERE order_id = ?";
                if ($items_stmt = $conn->prepare($items_query)) {
                    $items_stmt->bind_param("i", $order['id']);
                    if ($items_stmt->execute()) {
                        $items_result = $items_stmt->get_result();
                        while ($item = $items_result->fetch_assoc()) {
                            $order_items[] = $item;
                        }
                    }
                    $items_stmt->close();
                }
            } else {
                $error_message = "Order not found. Please check your Order ID.";
            }
        }
        $stmt->close();
    }
}

// Status color mapping - matches Shopspree theme
$status_colors = [
    'Pending' => '#FFC107',
    'Processing' => '#17A2B8',
    'Shipped' => '#0D6EFD',
    'Delivered' => '#28A745',
    'Cancelled' => '#DC3545'
];

$current_status = $order ? $order['order_status'] : '';
$status_color = isset($status_colors[$current_status]) ? $status_colors[$current_status] : '#6C757D';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Track Your Order - Shopspree</title>
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
    <link href="../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f5f5;
        }

        .nav-bar {
            background-color: #0D6EFD;
        }

        .page-header {
            background: linear-gradient(135deg, #0D6EFD 0%, #0056B3 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .tracking-form-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .tracking-form-card h3 {
            color: #0D6EFD;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .form-group input {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #0D6EFD;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
            outline: none;
        }

        .btn-track {
            background: #0D6EFD;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-track:hover {
            background: #0056B3;
            color: white;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-header h2 {
            color: #333;
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }

        .order-header-meta {
            color: #666;
            font-size: 0.95rem;
        }

        .status-badge {
            background: <?php echo $status_color; ?>;
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .timeline {
            margin: 30px 0;
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 19px;
            top: 60px;
            width: 2px;
            height: 40px;
            background-color: #e0e0e0;
        }

        .timeline-marker {
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
            border: 3px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-marker.completed {
            background: #28A745;
        }

        .timeline-marker.current {
            background: #0D6EFD;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.2);
        }

        .timeline-content {
            flex: 1;
            padding-top: 5px;
        }

        .timeline-content h4 {
            color: #333;
            margin: 0 0 5px 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .timeline-content p {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
        }

        .order-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #0D6EFD;
        }

        .detail-label {
            color: #666;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .detail-value {
            color: #333;
            font-size: 1rem;
            font-weight: 600;
        }

        .detail-value.price {
            color: #0D6EFD;
            font-size: 1.2rem;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.95rem;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table td strong {
            color: #333;
        }

        .error-alert {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-alert i {
            font-size: 1.2rem;
        }

        .no-result {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 8px;
        }

        .no-result i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
            display: block;
        }

        .no-result h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        .no-result p {
            color: #666;
            margin-bottom: 0;
        }

        .back-home-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #0D6EFD;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: color 0.3s;
        }

        .back-home-btn:hover {
            color: #0056B3;
            text-decoration: none;
        }

        .section-title {
            color: #333;
            font-weight: 700;
            font-size: 1.3rem;
            margin: 30px 0 20px 0;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 40px 0;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-badge {
                width: 100%;
                text-align: center;
            }

            .timeline-marker {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .items-table {
                font-size: 0.9rem;
            }

            .items-table th,
            .items-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <!-- Navbar & Hero Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-12 col-lg-9 ms-auto">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="../index.php" class="navbar-brand d-block d-lg-none">
                        <h1 class="display-5 text-secondary m-0" style="font-size: 1.5rem;"><i class="fas fa-shopping-bag text-white me-2"></i>Shopspree</h1>
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="../index.php" class="nav-item nav-link">Home</a>
                            <a href="../shop.php" class="nav-item nav-link">Shop</a>
                            <a href="../cart.php" class="nav-item nav-link">Cart</a>
                            <a href="../index.php#contact" class="nav-item nav-link me-2">Contact</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar & Hero End -->

    <!-- Page Header Start -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1><i class="fas fa-box me-3"></i>Track Your Order</h1>
                    <p class="mb-0">Monitor your order status and delivery information</p>
                </div>
                <div class="col-lg-4 text-lg-end d-none d-lg-block">
                    <i class="fas fa-shopping-cart fa-3x" style="opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Main Content Start -->
    <div class="container" style="margin-bottom: 60px;">
        <!-- Track Form Card -->
        <div class="tracking-form-card wow fadeInUp" data-wow-delay="0.1s">
            <h3><i class="fas fa-search me-2"></i>Find Your Order</h3>
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="form-group">
                        <input type="text" name="order_id" class="form-control" placeholder="Enter your Order ID (e.g., ORD-ABC123)" value="<?php echo htmlspecialchars($order_id); ?>" required>
                        <small class="text-muted d-block mt-2">You can find your Order ID in the confirmation email we sent you.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-track w-100" style="padding: 12px 30px; height: 100%; margin-top: 0;">
                        <i class="fas fa-search me-2"></i>Track Order
                    </button>
                </div>
            </form>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
            <div class="error-alert wow fadeInUp" data-wow-delay="0.2s">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error_message); ?></div>
            </div>
        <?php endif; ?>

        <!-- Order Details -->
        <?php if ($order): ?>
            <!-- Order Header -->
            <div class="order-card wow fadeInUp" data-wow-delay="0.2s">
                <div class="order-header">
                    <div>
                        <h2><?php echo htmlspecialchars($order['order_id']); ?></h2>
                        <div class="order-header-meta">Ordered on <?php echo date('F d, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="status-badge"><?php echo htmlspecialchars($order['order_status']); ?></div>
                </div>

                <!-- Timeline -->
                <h4 class="section-title"><i class="fas fa-tasks me-2" style="color: #0D6EFD;"></i>Order Timeline</h4>
                <div class="timeline">
                    <?php
                    $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered'];
                    foreach ($statuses as $index => $status):
                        $is_completed = in_array($current_status, ['Processing', 'Shipped', 'Delivered']) && $index < array_search($current_status, $statuses);
                        $is_current = $current_status === $status;
                        $is_pending = !$is_completed && !$is_current;
                        
                        $marker_class = $is_completed ? 'completed' : ($is_current ? 'current' : '');
                        $status_text = '';
                        
                        if ($is_completed) {
                            $status_text = 'Completed';
                        } elseif ($is_current) {
                            $status_text = 'Current Status';
                        } else {
                            $status_text = 'Upcoming';
                        }
                    ?>
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo $marker_class; ?>">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <h4><?php echo htmlspecialchars($status); ?></h4>
                                <p><?php echo $status_text; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Details Card -->
            <div class="order-card wow fadeInUp" data-wow-delay="0.3s">
                <h4 class="section-title"><i class="fas fa-info-circle me-2" style="color: #0D6EFD;"></i>Order Information</h4>
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
                        <div class="detail-value price">$<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Order Status</div>
                        <div class="detail-value" style="color: <?php echo $status_color; ?>;"><?php echo htmlspecialchars($order['order_status']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <?php if (!empty($order_items)): ?>
                <div class="order-card wow fadeInUp" data-wow-delay="0.4s">
                    <h4 class="section-title"><i class="fas fa-shopping-bag me-2" style="color: #0D6EFD;"></i>Items in Your Order</h4>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                    <td class="text-center"><?php echo intval($item['quantity']); ?></td>
                                    <td class="text-right">$<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-right"><strong>$<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Back to Home -->
            <a href="../index.php" class="back-home-btn wow fadeInUp" data-wow-delay="0.5s">
                <i class="fas fa-arrow-left"></i>Back to Home
            </a>

        <?php else: ?>
            <!-- No Result -->
            <div class="no-result wow fadeInUp" data-wow-delay="0.2s">
                <i class="fas fa-search"></i>
                <h3>No Order Found</h3>
                <p>Enter your Order ID above to track your order status.</p>
            </div>
        <?php endif; ?>
    </div>
    <!-- Main Content End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Why Choose Us</h5>
                    <p class="mb-4">Shopspree offers the best electronics with competitive pricing, fast shipping, and exceptional customer service.</p>
                    <div class="d-flex align-items-center">
                        <h4 class="text-secondary m-0"><i class="fas fa-shopping-bag text-primary me-2"></i>Shopspree</h4>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Quick Links</h5>
                    <a class="btn btn-link" href="../index.php">Home</a>
                    <a class="btn btn-link" href="../shop.php">Shop</a>
                    <a class="btn btn-link" href="../cart.php">Cart</a>
                    <a class="btn btn-link" href="../index.php#contact">Contact</a>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Need Help?</h5>
                    <p><i class="fa fa-phone-alt me-3"></i>(+012) 1234 567890</p>
                    <p><i class="fa fa-envelope me-3"></i>support@shopspree.com</p>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Follow Us</h5>
                    <div class="d-flex pt-2">
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle rounded-0 me-0" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid copyright">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="border-bottom" href="#">Shopspree</a>, All Right Reserved.
                </div>
                <div class="text-center text-md-end">
                    <a class="border-bottom" href="track.php">Order Tracking</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/waypoints/waypoints.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="../js/main.js"></script>
    
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
<?php
if ($conn) {
    $conn->close();
}
?>