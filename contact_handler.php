<?php
require_once __DIR__ . '/config/db.php';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $is_ajax ? die(json_encode(['success' => false, 'message' => 'Invalid request method'])) : redirect('contact.php');
}

// CSRF verification
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'Invalid request token. Please refresh the page and try again.']);
        exit;
    }
    add_message('Invalid request. Please refresh and try again.', 'error');
    redirect('contact.php');
}

try {
    $errors = [];
    $data   = [];

    foreach (['name', 'email', 'subject', 'message'] as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . ' is required';
        } else {
            $data[$field] = sanitize($_POST[$field]);
        }
    }

    if (!empty($errors)) {
        if ($is_ajax) { echo json_encode(['success' => false, 'errors' => $errors]); exit; }
        add_message(implode(', ', $errors), 'error');
        redirect('contact.php');
    }

    if (!is_valid_email($data['email'])) {
        if ($is_ajax) { echo json_encode(['success' => false, 'message' => 'Invalid email address']); exit; }
        add_message('Invalid email address', 'error');
        redirect('contact.php');
    }

    $data['phone'] = sanitize($_POST['phone'] ?? '');

    if (strlen($data['message']) < 10) {
        if ($is_ajax) { echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters']); exit; }
        add_message('Message must be at least 10 characters', 'error');
        redirect('contact.php');
    }

    if (strlen($data['message']) > 5000) {
        if ($is_ajax) { echo json_encode(['success' => false, 'message' => 'Message must not exceed 5000 characters']); exit; }
        add_message('Message too long (max 5000 characters)', 'error');
        redirect('contact.php');
    }

    // Save to database
    $pdo->prepare(
        "INSERT INTO contacts (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
    )->execute([$data['name'], $data['email'], $data['phone'], $data['subject'], $data['message']]);

    // Email to admin
    $subject    = 'New Contact Form Submission: ' . $data['subject'];
    $email_body = "New contact form submission from " . SITE_NAME . ":\n\n"
        . "Name: "    . $data['name']    . "\n"
        . "Email: "   . $data['email']   . "\n"
        . (!empty($data['phone']) ? "Phone: " . $data['phone'] . "\n" : '')
        . "Subject: " . $data['subject'] . "\n\n"
        . "Message:\n" . $data['message'] . "\n\n"
        . "---\nReply to: " . $data['email'];

    $headers  = "From: " . MAIL_FROM . "\r\n";
    $headers .= "Reply-To: " . $data['email'] . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail(ADMIN_EMAIL, $subject, $email_body, $headers);

    // Confirmation to user
    $user_subject = 'We received your message — ' . SITE_NAME;
    $user_body    = "Hi " . $data['name'] . ",\n\n"
        . "Thank you for contacting " . SITE_NAME . ". We have received your message and will get back to you shortly.\n\n"
        . "Your message:\nSubject: " . $data['subject'] . "\n\n" . $data['message'] . "\n\n"
        . "Best regards,\n" . SITE_NAME . " Team";
    $user_headers  = "From: " . MAIL_FROM . "\r\n";
    $user_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    @mail($data['email'], $user_subject, $user_body, $user_headers);

    if ($is_ajax) {
        echo json_encode(['success' => true, 'message' => 'Thank you! We will get back to you shortly.']);
        exit;
    }
    add_message('Thank you for your message! We will get back to you shortly.', 'success');
    redirect('contact.php');

} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    if ($is_ajax) {
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
        exit;
    }
    add_message('An error occurred. Please try again later.', 'error');
    redirect('contact.php');
}
