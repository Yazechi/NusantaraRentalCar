    </main>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5><i class="fas fa-car"></i> <?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">Your trusted car rental service in Indonesia. Quality vehicles at affordable prices.</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/cars.php" class="text-muted text-decoration-none">Cars</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/login.php" class="text-muted text-decoration-none">Login</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/register.php" class="text-muted text-decoration-none">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact Us</h5>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?php echo sanitize_output(get_site_setting('site_address') ?? 'Jakarta, Indonesia'); ?><br>
                        <i class="fab fa-whatsapp"></i> +<?php echo sanitize_output(get_site_setting('whatsapp_number') ?? '6281234567890'); ?>
                    </p>
                </div>
            </div>
            <hr class="border-secondary">
            <div class="text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
