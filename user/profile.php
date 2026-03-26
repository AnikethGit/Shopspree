<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../index.php?login=required');
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];
$messages = get_messages();

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('../index.php');
}

// Auto-link any guest orders placed with this email to this user account
$link_stmt = $pdo->prepare(
    "UPDATE orders SET user_id = ? WHERE email = ? AND (user_id IS NULL OR user_id = 0)"
);
$link_stmt->execute([$user_id, $user_email]);

// Get user's orders: by user_id OR by matching email (catches any still-unlinked ones)
$orders_stmt = $pdo->prepare(
    "SELECT * FROM orders WHERE user_id = ? OR email = ? ORDER BY created_at DESC LIMIT 20"
);
$orders_stmt->execute([$user_id, $user_email]);
$orders = $orders_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PrintDepotCo</title>
    <link rel="icon" type="image/x-icon" href="/img/favicon.png">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .profile-wrapper { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .profile-sidebar {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 25px;
            height: fit-content;
        }
        .profile-sidebar .avatar {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: white; margin: 0 auto 15px;
        }
        .profile-sidebar h5 { text-align: center; margin-bottom: 5px; }
        .profile-sidebar p { text-align: center; color: #888; font-size: 13px; margin-bottom: 20px; }
        .profile-nav a {
            display: block; padding: 10px 15px;
            border-radius: 6px; text-decoration: none;
            color: #444; margin-bottom: 5px; transition: all 0.2s;
        }
        .profile-nav a:hover, .profile-nav a.active {
            background: #667eea; color: white;
        }
        .profile-nav a.logout { color: #e74c3c; }
        .profile-nav a.logout:hover { background: #e74c3c; color: white; }
        .profile-content {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 30px;
        }
        .profile-content h4 {
            border-bottom: 2px solid #667eea;
            padding-bottom: 12px; margin-bottom: 20px; color: #333;
        }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
        .info-item { background: #f9f9f9; border-radius: 8px; padding: 15px; }
        .info-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #999; }
        .info-value { font-size: 14px; color: #333; margin-top: 5px; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table th {
            background: #f0f0f0; padding: 12px 15px;
            text-align: left; font-size: 13px; color: #555;
            border-bottom: 2px solid #ddd;
        }
        .orders-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .orders-table tr:hover td { background: #fafafa; }
        .badge-status {
            display: inline-block; padding: 4px 12px;
            border-radius: 20px; font-size: 11px; font-weight: 700;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-processing { background: #cce5ff; color: #004085; }
        .badge-payment\ received { background: #cce5ff; color: #004085; }
        .badge-shipped { background: #d1ecf1; color: #0c5460; }
        .badge-delivered { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 50px 20px; color: #aaa; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; display: block; }
        @media (max-width: 768px) {
            .profile-wrapper { flex-direction: column; }
            .info-grid { grid-template-columns: 1fr; }
            .orders-table { font-size: 12px; }
        }
    </style>
</head>
<body>

    <!-- Topbar Start -->
    <div class="container-fluid px-5 d-none border-bottom d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center" style="height:45px;">
                    <a href="../contact.php" class="text-muted ms-2">Contact Us</a>
                </div>
            </div>
            <div class="col-lg-6 text-end">
                <div class="d-inline-flex align-items-center" style="height:45px;">
                    <span class="text-muted me-3"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <a href="logout.php" class="text-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid px-5 py-3 d-none d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-3">
                <a href="../index.php" class="navbar-brand p-0">
                    <img src="/img/printdepotco-icon.png" alt="PrintDepotCo" style="height:60px; width:auto;">
                </a>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="../index.php" class="navbar-brand d-block d-lg-none">
                        <img src="/img/printdepotco-icon.png" alt="PrintDepotCo" style="height:55px; width:auto;">
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="../index.php" class="nav-item nav-link">Home</a>
                            <a href="../shop.php" class="nav-item nav-link">Shop</a>
                            <a href="../cart.php" class="nav-item nav-link">Cart</a>
                            <a href="profile.php" class="nav-item nav-link active">My Profile</a>
                            <a href="logout.php" class="nav-item nav-link text-warning">Logout</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->

    <div class="container profile-wrapper">
        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show mb-3" role="alert">
                <?php echo htmlspecialchars($msg['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <div class="d-flex gap-4 flex-column flex-lg-row">
            <!-- Sidebar -->
            <div style="width:240px; flex-shrink:0;">
                <div class="profile-sidebar">
                    <div class="avatar"><i class="fas fa-user"></i></div>
                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <nav class="profile-nav">
                        <a href="profile.php" class="active"><i class="fas fa-id-card me-2"></i>My Profile</a>
                        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-grow-1">
                <div class="profile-content mb-4">
                    <h4><i class="fas fa-id-card me-2"></i>Account Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: '—'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Type</div>
                            <div class="info-value">
                                <span style="display:inline-block; padding:4px 12px; background:#d4edda; color:#155724; border-radius:20px; font-size:12px; font-weight:700;">
                                    <?php echo ucfirst(htmlspecialchars($user['user_type'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Account Status</div>
                            <div class="info-value">
                                <span style="display:inline-block; padding:4px 12px; background:#d4edda; color:#155724; border-radius:20px; font-size:12px; font-weight:700;">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Section -->
                <div class="profile-content">
                    <h4><i class="fas fa-box me-2"></i>My Orders</h4>

                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <p>You haven't placed any orders yet.</p>
                            <a href="../shop.php" class="btn btn-primary rounded-pill px-4">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="orders-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <?php
                                            $status = strtolower(str_replace(' ', '_', $order['order_status'] ?? 'pending'));
                                            $badge_class = 'badge-' . str_replace('_', ' ', $status);
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                            <td><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></td>
                                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['payment_method'] ?? '—'); ?></td>
                                            <td>
                                                <span class="badge-status <?php echo htmlspecialchars($badge_class); ?>">
                                                    <?php echo htmlspecialchars($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../orders/track.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill">Track</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <br><br>

    <!-- Footer -->
    <div class="container-fluid bg-dark text-light py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <a class="border-bottom text-light" href="../index.php">Print Depot Co</a>, All Rights Reserved.</p>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>