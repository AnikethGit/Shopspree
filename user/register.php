<?php
require_once __DIR__ . '/../config/db.php';

if (is_logged_in()) {
    redirect('../index.php');
}

$status          = $_GET['status'] ?? '';
$success_message = ($status === 'success') ? 'Registration successful! You can now login.' : '';

$page_title   = 'Create Account — ' . SITE_NAME;
$active_nav   = '';
$meta_noindex = true;
$extra_head   = '<style>
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
    .auth-card h2 { font-family: "Roboto", sans-serif; font-size: 26px; font-weight: 700; color: #1a1a2e; margin-bottom: 6px; }
    .auth-card .auth-subtitle { color: #6c757d; font-size: 14px; margin-bottom: 28px; }
    .auth-card label { font-weight: 600; font-size: 13px; color: #444; margin-bottom: 6px; display: block; }
    .auth-card .form-control { border-radius: 8px; padding: 11px 15px; font-size: 14px; border: 1.5px solid #dee2e6; transition: border-color 0.25s, box-shadow 0.25s; }
    .auth-card .form-control:focus { border-color: var(--bs-primary, #0d6efd); box-shadow: 0 0 0 3px rgba(13,110,253,0.12); }
    .auth-card .form-control.is-invalid { border-color: #dc3545; }
    .auth-card .btn-submit { width: 100%; padding: 12px; font-size: 15px; font-weight: 700; border-radius: 8px; letter-spacing: 0.3px; margin-top: 8px; }
    .auth-card .auth-footer { text-align: center; margin-top: 22px; font-size: 14px; color: #6c757d; }
    .auth-card .auth-footer a { font-weight: 600; text-decoration: none; }
    .auth-card .auth-footer a:hover { text-decoration: underline; }
    .input-icon-wrap { position: relative; }
    .input-icon-wrap .input-icon { position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: #adb5bd; font-size: 15px; pointer-events: none; }
    .input-icon-wrap .form-control { padding-left: 40px; }
    .error-message { color: #dc3545; font-size: 12px; margin-top: 4px; display: none; }
    .error-message.show { display: block; }
    .auth-divider { display: flex; align-items: center; gap: 12px; margin: 20px 0 16px; color: #adb5bd; font-size: 13px; }
    .auth-divider::before, .auth-divider::after { content: ""; flex: 1; height: 1px; background: #e9ecef; }
    .password-strength { margin-top: 6px; font-size: 12px; }
    .strength-bar { height: 4px; border-radius: 2px; background: #e9ecef; margin-top: 4px; overflow: hidden; }
    .strength-fill { height: 100%; width: 0; border-radius: 2px; transition: width 0.3s, background 0.3s; }
</style>';

require_once __DIR__ . '/../includes/header.php';
?>

    <!-- Register Form -->
    <div class="auth-wrapper">
        <div class="auth-card wow fadeInUp" data-wow-delay="0.1s">
            <h2>Create Your Account</h2>
            <p class="auth-subtitle">Join <?php echo htmlspecialchars(SITE_NAME); ?> and start shopping today</p>

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
                    <label for="phone">Phone Number <span class="text-muted fw-normal">(optional)</span></label>
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
                               placeholder="Minimum 8 characters" required oninput="checkStrength()">
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

<?php
$extra_foot = <<<'JS'
<script>
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
    .then(r => r.json())
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
JS;
require_once __DIR__ . '/../includes/footer.php';
?>
