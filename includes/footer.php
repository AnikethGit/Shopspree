    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Why Choose Us</h5>
                    <p class="mb-4">We provide high-performance printing solutions and expert support to maximise your efficiency and lower your long-term costs.</p>
                    <img class="img-fluid flex-shrink-0" src="/img/footer-logo.png" alt="">
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Address</h5>
                    <p><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York, USA</p>
                    <p><i class="fa fa-phone-alt me-3"></i><?php echo htmlspecialchars(defined('SITE_PHONE') ? SITE_PHONE : ''); ?></p>
                    <p><i class="fa fa-envelope me-3"></i><?php echo htmlspecialchars(defined('SITE_EMAIL') ? SITE_EMAIL : ''); ?></p>
                    <div class="d-flex pt-2">
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle me-2" href=""><i class="fab fa-youtube"></i></a>
                        <a class="btn btn-square btn-outline-light rounded-circle rounded-0 me-0" href=""><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Quick Links</h5>
                    <a class="btn btn-link" href="/about.html">About Us</a><br>
                    <a class="btn btn-link" href="/contact.php">Contact Us</a><br>
                    <a class="btn btn-link" href="/terms.html">Terms &amp; Conditions</a>
                </div>
                <div class="col-md-6 col-lg-6 col-xl-3">
                    <h5 class="text-light mb-4">Newsletter</h5>
                    <p>Sign up for our newsletter</p>
                    <div class="position-relative w-100 mt-3">
                        <input class="form-control border-light w-100 py-2 ps-4 pe-5" type="text" placeholder="Your Email" style="background:rgba(255,255,255,0.87);">
                        <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0">SignUp</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid copyright">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="text-center text-md-start mb-3 mb-md-0">
                    &copy; <a class="border-bottom" href="#">Print Depot Co</a>, All Rights Reserved.
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lib/wow/wow.min.js"></script>
    <script src="/lib/easing/easing.min.js"></script>
    <script src="/lib/waypoints/waypoints.min.js"></script>
    <script src="/lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="/js/main.js"></script>
    <?php if (!empty($extra_foot)) echo $extra_foot; ?>
    <script>
        window.addEventListener('load', function() {
            document.getElementById('spinner').classList.remove('show');
        });
        if (typeof WOW !== 'undefined') new WOW().init();
    </script>
</body>
</html>
