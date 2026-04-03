<?php
/**
 * User Registration Form
 * user/register.php
 */

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$status = $_GET['status'] ?? '';
$success_message = '';
if ($status === 'success') {
    $success_message = 'Registration successful! You can now login.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Create Account - PrintDepotCo</title>
    <link rel="icon" type="image/x-icon" href="/img/favicon.png">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Register for PrintDepotCo" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../lib/animate/animate.min.css" rel="stylesheet">
    <link href="../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../css/style.css" rel="stylesheet">

    <style>
        .auth-wrapper {
            min-height: calc(100vh - 300px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            background: #f8f9fa;
        }
        .auth-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.10);
            padding: 48px 42px;
            width: 100%;
            max-width: 540px;
        }
        .auth-card h2 {
            font-family: 'Roboto', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        .auth-card .auth-subtitle {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .auth-card label {
            font-weight: 600;
            font-size: 13px;
            color: #444;
            margin-bottom: 6px;
            display: block;
        }
        .auth-card .form-control {
            border-radius: 8px;
            padding: 11px 15px;
            font-size: 14px;
            border: 1.5px solid #dee2e6;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        .auth-card .form-control:focus {
            border-color: var(--bs-primary, #0d6efd);
            box-shadow: 0 0 0 3px rgba(13,110,253,0.12);
        }
        .auth-card .form-control.is-invalid {
            border-color: #dc3545;
        }
        .auth-card .btn-submit {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 8px;
            letter-spacing: 0.3px;
            margin-top: 8px;
        }
        .auth-card .auth-footer {
            text-align: center;
            margin-top: 22px;
            font-size: 14px;
            color: #6c757d;
        }
        .auth-card .auth-footer a {
            font-weight: 600;
            text-decoration: none;
        }
        .auth-card .auth-footer a:hover { text-decoration: underline; }
        .input-icon-wrap { position: relative; }
        .input-icon-wrap .input-icon {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 15px;
            pointer-events: none;
        }
        .input-icon-wrap .form-control { padding-left: 40px; }
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
        .error-message.show { display: block; }
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0 16px;
            color: #adb5bd;
            font-size: 13px;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9ecef;
        }
        .password-strength {
            margin-top: 6px;
            font-size: 12px;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 4px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
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
    
    <div class="container-fluid px-5 d-none border-bottom d-lg-block">
        <div class="row gx-0 align-items-center">
            <div class="col-lg-4 text-center text-lg-start mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a href="contact.php" class="text-muted ms-2">Contact</a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-flex align-items-center justify-content-center">
                <small class="text-dark">Call Us:</small>
                <a href="#" class="text-muted">(+012) 1234 567890</a>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle text-muted me-2" data-bs-toggle="dropdown"><small>USD</small></a>
                        <div class="dropdown-menu rounded">
                            <a href="#" class="dropdown-item">Euro</a>
                            <a href="#" class="dropdown-item">Dollar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand + Search + Cart row -->
    <div class="container-fluid px-5 py-4 d-none d-lg-block">
        <div class="row gx-0 align-items-center text-center">
            <div class="col-md-4 col-lg-3 text-center text-lg-start">
                <div class="d-inline-flex align-items-center">
                    <a href="../index.php" class="navbar-brand p-0">
                        <img src="/img/printdepotco-icon.png" alt="Printdepotco"
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                </div>
            </div>
            <div class="col-md-4 col-lg-6 text-center">
                <div class="position-relative ps-4">
                    <form method="GET" action="../shop.php" class="d-flex border rounded-pill">
                        <input class="form-control border-0 rounded-pill w-100 py-3" type="text" name="search" placeholder="Search Looking For?">
                        <button type="submit" class="btn btn-primary rounded-pill py-3 px-5" style="border: 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-md-4 col-lg-3 text-center text-lg-end">
                <div class="d-inline-flex align-items-center">
                    <a href="../cart.php" class="text-muted d-flex align-items-center justify-content-center">
                        <span class="rounded-circle btn-md-square border">
                            <i class="fas fa-shopping-cart"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <div class="container-fluid nav-bar p-0">
        <div class="row gx-0 bg-primary px-5 align-items-center">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg navbar-light bg-primary">
                    <a href="../index.php" class="navbar-brand d-block d-lg-none">
                        <img src="/img/printdepotco-icon.png" alt="Printdepotco"
                            style="height: 70px; width: auto; max-width: 100px;">
                    </a>
                    <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars fa-1x text-white"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0">
                            <a href="../index.php" class="nav-item nav-link">Home</a>
                            <a href="../shop.php" class="nav-item nav-link">Shop</a>
                            <a href="../about.html" class="nav-item nav-link">About Us</a>
                            <a href="../orderstrack.php" class="nav-item nav-link">Track My Order</a>
                            <a href="../cart.php" class="nav-item nav-link">Cart</a>
                            <a href="../contact.php" class="nav-item nav-link me-2">Contact</a>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->

    <!-- Register Form -->
    <div class="auth-wrapper">
        <div class="auth-card wow fadeInUp" data-wow-delay="0.1s">
            <h2>Create Your Account</h2>
            <p class="auth-subtitle">Join Print Depot Co and start shopping today</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="register_process.php" novalidate>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label for="first_name">First Name</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                   placeholder="First name" required>
                        </div>
                        <div class="error-message" id="first_name-error"></div>
                    </div>
                    <div class="col-6">
                        <label for="last_name">Last Name</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                   placeholder="Last name" required>
                        </div>
                        <div class="error-message" id="last_name-error"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="you@example.com" required>
                    </div>
                    <div class="error-message" id="email-error"></div>
                </div>

                <div class="mb-3">
                    <label for="phone">Phone Number</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               placeholder="Enter your phone number">
                    </div>
                    <div class="error-message" id="phone-error"></div>
                </div>

                <div class="mb-3">
                    <label for="password">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Minimum 6 characters" required oninput="checkStrength()">
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                    <div class="password-strength text-muted" id="strengthLabel"></div>
                    <div class="error-message" id="password-error"></div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Confirm your password" required>
                    </div>
                    <div class="error-message" id="confirm_password-error"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-submit">
                    <i class="fas fa-user-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="auth-divider">or</div>

            <div class="auth-footer">
                Already have an account? <a href="login.php" class="text-primary">Login here</a>
            </div>
        </div>
    </div>

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Why Choose Us</h5>
                    <p class="mb-4">We provide high-performance printing solutions and expert support to maximize your efficiency and lower your long-term costs.</p>
                    <div class="d-flex align-items-center">
                        <img class="img-fluid flex-shrink-0" src="../img/footer-logo.png" alt="">
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Address</h5>
                    <p><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                    <p><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                    <p><i class="fa fa-envelope me-3"></i>info@printdepotco.com</p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-0" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Quick Links</h5>
                    <a class="btn btn-link" href="../about.html">About Us</a>
                    <a class="btn btn-link" href="../contact.php">Contact Us</a>
                    <a class="btn btn-link" href="../terms.html">Terms &amp; Condition</a>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Newsletter</h5>
                    <p>Sign up for our newsletter</p>
                    <div class="position-relative w-100 mt-3">
                        <input class="form-control border-light w-100 py-2 ps-4 pe-5" type="text"
                            placeholder="Your Email" style="background: rgba(255,255,255,0.87);">
                        <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0">SignUp</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid copyright">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="border-bottom" href="#">Print Depot Co</a>, All Right Reserved.
                </div>
                <!-- <div class="text-center text-md-end">
                    Designed By <a class="border-bottom" href="https://github.com/AnikethGit">aniketh_sahu</a>
                </div> -->
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top">
        <i class="fa fa-arrow-up"></i>
    </a>

    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../lib/wow/wow.min.js"></script>
    <script src="../lib/easing/easing.min.js"></script>
    <script src="../lib/waypoints/waypoints.min.js"></script>
    <script src="../lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="../js/main.js"></script>

    <script>
        // Password strength meter
        function checkStrength() {
            const val = document.getElementById('password').value;
            const fill = document.getElementById('strengthFill');
            const label = document.getElementById('strengthLabel');
            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const levels = [
                { pct: '0%',   color: '#e9ecef', text: '' },
                { pct: '25%',  color: '#dc3545', text: 'Weak' },
                { pct: '50%',  color: '#fd7e14', text: 'Fair' },
                { pct: '75%',  color: '#ffc107', text: 'Good' },
                { pct: '90%',  color: '#20c997', text: 'Strong' },
                { pct: '100%', color: '#198754', text: 'Very Strong' },
            ];
            const lvl = levels[Math.min(strength, 5)];
            fill.style.width = lvl.pct;
            fill.style.background = lvl.color;
            label.textContent = lvl.text;
            label.style.color = lvl.color;
        }

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
            document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));

            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account...';

            fetch('register_process.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'login.php?registered=1';
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorEl = document.getElementById(field + '-error');
                            const inputEl = document.getElementById(field);
                            if (errorEl && inputEl) {
                                errorEl.textContent = data.errors[field];
                                errorEl.classList.add('show');
                                inputEl.classList.add('is-invalid');
                            }
                        });
                    }
                }
            })
            .catch(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Create Account';
            });
        });
    </script>
</body>
</html>