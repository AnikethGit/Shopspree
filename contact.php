<?php
require_once __DIR__ . '/config/db.php';
$messages = get_messages();

$page_title       = 'Contact Us — ' . SITE_NAME;
$active_nav       = 'contact';
$meta_description = 'Get in touch with ' . SITE_NAME . '. We\'re here to help with your printing needs.';
require_once __DIR__ . '/includes/header.php';
?>

    <!-- Page Header -->
    <div class="container-fluid page-header py-5">
        <h1 class="text-center text-white display-6">Contact Us</h1>
        <ol class="breadcrumb justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active text-white">Contact</li>
        </ol>
    </div>

    <!-- Contact -->
    <div class="container-fluid contact py-5">
        <div class="container py-5">
            <div class="p-5 bg-light rounded">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="text-center mx-auto" style="max-width: 900px;">
                            <h4 class="text-primary border-bottom border-primary border-2 d-inline-block pb-2">Get in touch</h4>
                            <p class="mb-5 fs-5 text-dark">We are here for you! How can we help?</p>
                        </div>
                    </div>

                    <?php foreach ($messages as $msg): ?>
                    <div class="col-12">
                        <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($msg['text']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="col-lg-7">
                        <h5 class="text-primary">Let's Connect</h5>
                        <h1 class="display-5 mb-4">Send Your Message</h1>
                        <p class="mb-4">Have a question or feedback? Fill out the form below and we'll get back to you as soon as possible.</p>
                        <form method="post" action="contact_handler.php">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">
                            <div class="row g-4">
                                <div class="col-lg-12 col-xl-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                                        <label for="name">Your Name</label>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
                                        <label for="email">Your Email</label>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone">
                                        <label for="phone">Your Phone</label>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-xl-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                                        <label for="subject">Subject</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" placeholder="Leave a message here" id="message" name="message" style="height: 160px" required></textarea>
                                        <label for="message">Message</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary w-100 py-3" type="submit">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-5">
                        <div class="h-100 rounded">
                            <iframe class="rounded w-100" style="height: 100%; min-height: 400px;"
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387191.33750346623!2d-73.97968099999999!3d40.6974881!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2sbd!4v1694259649153!5m2!1sen!2sbd"
                                loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="row g-4 justify-content-center">
                            <div class="col-md-6 col-xl-3">
                                <div class="rounded p-4">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width:70px;height:70px">
                                        <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                                    </div>
                                    <h4>Address</h4>
                                    <p class="mb-2"><?php echo htmlspecialchars(COMPANY_ADDRESS); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <div class="rounded p-4">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width:70px;height:70px">
                                        <i class="fas fa-envelope fa-2x text-primary"></i>
                                    </div>
                                    <h4>Mail Us</h4>
                                    <p class="mb-2"><a href="mailto:<?php echo htmlspecialchars(SITE_EMAIL); ?>"><?php echo htmlspecialchars(SITE_EMAIL); ?></a></p>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <div class="rounded p-4">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width:70px;height:70px">
                                        <i class="fa fa-phone-alt fa-2x text-primary"></i>
                                    </div>
                                    <h4>Telephone</h4>
                                    <p class="mb-2"><a href="tel:<?php echo htmlspecialchars(SITE_PHONE); ?>"><?php echo htmlspecialchars(SITE_PHONE); ?></a></p>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3">
                                <div class="rounded p-4">
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-4" style="width:70px;height:70px">
                                        <i class="fab fa-firefox-browser fa-2x text-primary"></i>
                                    </div>
                                    <h4>Website</h4>
                                    <p class="mb-2"><a href="<?php echo htmlspecialchars(SITE_URL); ?>" target="_blank"><?php echo htmlspecialchars(parse_url(SITE_URL, PHP_URL_HOST)); ?></a></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>