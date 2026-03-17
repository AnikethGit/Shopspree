<?php
/**
 * Contact Form Handler
 * Processes contact form submissions and sends emails
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

// Handle both regular form and AJAX requests
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    } else {
        redirect('contact.php');
    }
    exit();
}

try {
    // Validate required fields
    $required_fields = ['name', 'email', 'subject', 'message'];
    $errors = [];
    $data = [];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . ' is required';
        } else {
            $data[$field] = sanitize($_POST[$field]);
        }
    }

    if (!empty($errors)) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'errors' => $errors]);
        } else {
            add_message(implode(', ', $errors), 'error');
            redirect('contact.php');
        }
        exit();
    }

    // Validate email
    if (!is_valid_email($data['email'])) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        } else {
            add_message('Invalid email address', 'error');
            redirect('contact.php');
        }
        exit();
    }

    // Optional phone
    $data['phone'] = sanitize($_POST['phone'] ?? '');

    // Validate message length
    if (strlen($data['message']) < 10) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters long']);
        } else {
            add_message('Message must be at least 10 characters long', 'error');
            redirect('contact.php');
        }
        exit();
    }

    if (strlen($data['message']) > 5000) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Message must not exceed 5000 characters']);
        } else {
            add_message('Message must not exceed 5000 characters', 'error');
            redirect('contact.php');
        }
        exit();
    }

    // Check if contacts table exists and insert if it does
    $table_exists = $conn->query("SHOW TABLES LIKE 'contacts'") !== false && $conn->affected_rows > 0;
    
    if ($table_exists) {
        $stmt = $conn->prepare(
            "INSERT INTO contacts (name, email, phone, subject, message, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        if ($stmt) {
            $stmt->bind_param("sssss", 
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['subject'],
                $data['message']
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    // Send email to admin
    $admin_email = 'admin@electro.com';
    $subject = 'New Contact Form Submission: ' . htmlspecialchars($data['subject']);
    
    $email_body = "New contact form submission from Electro website:\n\n";
    $email_body .= "Name: " . $data['name'] . "\n";
    $email_body .= "Email: " . $data['email'] . "\n";
    if (!empty($data['phone'])) {
        $email_body .= "Phone: " . $data['phone'] . "\n";
    }
    $email_body .= "Subject: " . $data['subject'] . "\n\n";
    $email_body .= "Message:\n" . $data['message'] . "\n\n";
    $email_body .= "---\n";
    $email_body .= "Please reply to: " . $data['email'];
    
    $headers = "From: " . $admin_email . "\r\n";
    $headers .= "Reply-To: " . $data['email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Send email to admin (suppress errors)
    @mail($admin_email, $subject, $email_body, $headers);
    
    // Send confirmation email to user
    $user_subject = 'We received your message - Electro';
    $user_body = "Hi " . $data['name'] . ",\n\n";
    $user_body .= "Thank you for contacting Electro. We have received your message and will get back to you as soon as possible.\n\n";
    $user_body .= "Your message:\n";
    $user_body .= "Subject: " . $data['subject'] . "\n\n";
    $user_body .= $data['message'] . "\n\n";
    $user_body .= "Best regards,\nElectro Team";
    
    $user_headers = "From: " . $admin_email . "\r\n";
    $user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    @mail($data['email'], $user_subject, $user_body, $user_headers);
    
    // Send response
    if ($is_ajax) {
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your message! We will get back to you shortly.'
        ]);
    } else {
        add_message('Thank you for your message! We will get back to you shortly.', 'success');
        redirect('contact.php');
    }
    
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    if ($is_ajax) {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request. Please try again later.'
        ]);
    } else {
        add_message('An error occurred while processing your request. Please try again later.', 'error');
        redirect('contact.php');
    }
}

?>