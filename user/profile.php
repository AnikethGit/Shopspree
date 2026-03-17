<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helpers.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../index.php?login=required');
}

$user_id = $_SESSION['user_id'];
$messages = get_messages();

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    redirect('../index.php');
}

// Get user's orders
$orders_stmt = $pdo->prepare(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10"
);
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Electro</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        .profile-sidebar {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
        }
        .profile-sidebar h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .profile-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .profile-nav a {
            padding: 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
            transition: all 0.3s;
        }
        .profile-nav a:hover,
        .profile-nav a.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        .logout-btn {
            background: #e74c3c !important;
            color: white !important;
            border-color: #e74c3c !important;
            margin-top: 20px;
            display: block;
            text-align: center;
        }
        .logout-btn:hover {
            background: #c0392b !important;
            border-color: #c0392b !important;
        }
        .profile-content {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
        }
        .profile-content h2 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-value {
            color: #555;
            margin-top: 8px;
            font-size: 14px;
        }
        .edit-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .edit-btn:hover {
            background: #2980b9;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .orders-table th {
            background: #ecf0f1;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #bdc3c7;
        }
        .orders-table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }
        .orders-table tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        .status-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .view-order-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .view-order-btn:hover {
            background: #2980b9;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
            .orders-table {
                font-size: 12px;
            }
            .orders-table th,
            .orders-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="../index.php">Home</a>
            <a href="../shop.php">Shop</a>
            <a href="../cart.php">Cart</a>
            <a href="profile.php" class="active">Profile</a>
        </nav>
    </header>

    <div class="container">
        <!-- Messages -->
        <?php foreach ($messages as $msg): ?>
            <div class="alert alert-<?php echo $msg['type']; ?>">
                <?php echo htmlspecialchars($msg['text']); ?>
            </div>
        <?php endforeach; ?>

        <div class="profile-container">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <h3>Menu</h3>
                <div class="profile-nav">
                    <a href="profile.php" class="active">My Profile</a>
                    <a href="orders.php">My Orders</a>
                    <a href="settings.php">Settings</a>
                    <a href="edit.php">Edit Profile</a>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Content -->
            <div class="profile-content">
                <h2>My Profile</h2>
                <p style="color: #666; margin: 10px 0;">Welcome back, <strong><?php echo htmlspecialchars($user['name']); ?></strong>!</p>

                <!-- User Information -->
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo format_date($user['created_at']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Status</div>
                        <div class="info-value">
                            <span style="display: inline-block; padding: 5px 10px; background: #d4edda; color: #155724; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                Active
                            </span>
                        </div>
                    </div>
                </div>

                <button class="edit-btn" onclick="location.href='edit.php'">Edit Profile</button>

                <!-- Recent Orders -->
                <h2 style="margin-top: 40px;">Recent Orders</h2>
                
                <?php if (empty($orders)): ?>
                    <div class="empty-state">
                        <p>You haven't placed any orders yet.</p>
                        <a href="../shop.php" style="color: #3498db; text-decoration: none;">Start Shopping &rarr;</a>
                    </div>
                <?php else: ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['order_id']); ?></strong>
                                    </td>
                                    <td><?php echo format_date($order['created_at']); ?></td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $order['order_status'])); ?>">
                                            <?php echo htmlspecialchars($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="view-order-btn">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 Electro. All rights reserved.</p>
    </footer>
</body>
</html>