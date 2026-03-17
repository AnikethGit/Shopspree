<?php
/**
 * User Registration Form
 * user/register.php
 * 
 * Registration page with form
 */

// Check if already logged in
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Ecommerce Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        input.error {
            border-color: #e74c3c;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <form id="registerForm" method="POST" action="register_process.php">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        placeholder="First name" 
                        required
                    >
                    <div class="error-message" id="first_name-error"></div>
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        placeholder="Last name" 
                        required
                    >
                    <div class="error-message" id="last_name-error"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email" 
                    required
                >
                <div class="error-message" id="email-error"></div>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    placeholder="Enter your phone" 
                >
                <div class="error-message" id="phone-error"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Minimum 6 characters" 
                    required
                >
                <div class="error-message" id="password-error"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Confirm your password" 
                    required
                >
                <div class="error-message" id="confirm_password-error"></div>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => {
                el.classList.remove('show');
                el.parentElement.querySelector('input').classList.remove('error');
            });
            
            // Get form data
            const formData = new FormData(this);
            
            // Submit via AJAX
            fetch('register_process.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Registration successful! Redirecting to login...');
                    window.location.href = 'login.php';
                } else {
                    // Show errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorEl = document.getElementById(field + '-error');
                            const inputEl = document.getElementById(field);
                            if (errorEl && inputEl) {
                                errorEl.textContent = data.errors[field];
                                errorEl.classList.add('show');
                                inputEl.classList.add('error');
                            }
                        });
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>