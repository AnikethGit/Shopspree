<?php
// Auto-load cart handler if get_cart_count not already defined
if (!function_exists('get_cart_count')) {
    $__cart_handler = __DIR__ . '/../cart/cart_handler.php';
    if (file_exists($__cart_handler)) {
        require_once $__cart_handler;
    }
    unset($__cart_handler);
}

$_hdr_cart  = function_exists('get_cart_count') ? (int)get_cart_count() : 0;
$_hdr_auth  = !empty($_SESSION['user_id']);
$_hdr_fname = $_hdr_auth
    ? htmlspecialchars($_SESSION['first_name'] ?? explode(' ', $_SESSION['full_name'] ?? 'Account')[0])
    : null;
$_a = $active_nav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($page_title ?? 'PrintDepotCo'); ?></title>
    <?php if (!empty($meta_noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <?php if (!empty($meta_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
    <link rel="icon" type="image/x-icon" href="/img/favicon.png">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="/lib/animate/animate.min.css" rel="stylesheet">
    <link href="/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="/css/style.css" rel="stylesheet">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
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
                    <a href="/contact.php" class="text-muted ms-2">Contact Us</a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-flex align-items-center justify-content-center">
                <small class="text-dark">Call Us:</small>
                <a href="#" class="text-muted">&nbsp;<?php echo htmlspecialchars(defined('SITE_PHONE') ? SITE_PHONE : '(+012) 1234 567890'); ?></a>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <?php if ($_hdr_auth): ?>
                        <span class="text-muted me-2">Hi, <?php echo $_hdr_fname; ?></span>
                        <a href="/user/profile.php" class="text-muted me-2">My Profile</a>
                        <small class="text-muted">/</small>
                        <a href="/user/logout.php" class="text-muted ms-2">Logout</a>
                    <?php else: ?>
                        <a href="/user/login.php" class="text-muted me-2">Login</a>
                        <small class="text-muted">/</small>
                        <a href="/user/register.php" class="text-muted ms-2">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Logo Row Start -->
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <div class="d-inline-flex align-items-center">
                    <a href="/index.php" class="navbar-brand p-0">
                        <img src="/img/printdepotco-icon.png" alt="PrintDepotCo"
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                </div>
            </div>
            <?php if (!empty($show_search)): ?>
            <div class="col-md-4 col-lg-6 text-center">
                <div class="position-relative ps-4">
                    <form method="GET" action="/shop.php" class="d-flex border rounded-pill">
                        <input class="form-control border-0 rounded-pill w-100 py-3" type="text" name="search" placeholder="Search Looking For?">
                        <button type="submit" class="btn btn-primary rounded-pill py-3 px-5" style="border: 0;"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="col-md-4 col-lg-6"></div>
            <?php endif; ?>
            <div class="col-md-4 col-lg-3 text-center text-lg-end">
                <div class="d-inline-flex align-items-center">
                    <a href="/cart.php" class="text-muted d-flex align-items-center justify-content-center">
                        <span class="rounded-circle btn-md-square border"><i class="fas fa-shopping-cart"></i></span>
                        <?php if ($_hdr_cart > 0): ?>
                            <span class="badge bg-primary rounded-pill ms-1" style="font-size:10px;"><?php echo $_hdr_cart; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Logo Row End -->

    <!-- Navbar Start -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="/index.php" class="navbar-brand d-block d-lg-none">
                        <img src="/img/printdepotco-icon.png" alt="PrintDepotCo"
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="/index.php" class="nav-item nav-link<?php echo $_a === 'home'     ? ' active' : ''; ?>">Home</a>
                            <a href="/shop.php"  class="nav-item nav-link<?php echo $_a === 'shop'     ? ' active' : ''; ?>">Shop</a>
                            <a href="/about.html" class="nav-item nav-link<?php echo $_a === 'about'   ? ' active' : ''; ?>">About Us</a>
                            <a href="/orders/track.php" class="nav-item nav-link<?php echo $_a === 'track' ? ' active' : ''; ?>">Track My Order</a>
                            <a href="/cart.php"  class="nav-item nav-link<?php echo $_a === 'cart'     ? ' active' : ''; ?>">
                                Cart<?php if ($_hdr_cart > 0): ?> <span class="badge bg-light text-primary rounded-pill ms-1" style="font-size:10px;"><?php echo $_hdr_cart; ?></span><?php endif; ?>
                            </a>
                            <a href="/contact.php" class="nav-item nav-link<?php echo $_a === 'contact' ? ' active' : ''; ?>">Contact</a>
                            <?php if ($_hdr_auth): ?>
                                <div class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle<?php echo $_a === 'profile' ? ' active' : ''; ?>" data-bs-toggle="dropdown">
                                        <i class="fas fa-user me-1"></i>Hi, <?php echo $_hdr_fname; ?>
                                    </a>
                                    <div class="dropdown-menu m-0">
                                        <a href="/user/profile.php" class="dropdown-item">My Profile</a>
                                        <a href="/user/logout.php" class="dropdown-item text-danger">Logout</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <a href="/user/login.php" class="nav-item nav-link">Login</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->
