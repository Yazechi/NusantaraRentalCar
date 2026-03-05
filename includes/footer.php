    </main>

    <footer class="site-footer">
        <div class="container">
            <!-- Top row: Brand + Links + Contact -->
            <div class="row g-4 footer-main">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <img src="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png" alt="MeTrev" class="footer-logo">
                        <span class="footer-brand-text brand-text"><?php echo SITE_NAME; ?></span>
                    </div>
                    <p class="footer-desc"><?php echo __('footer_desc'); ?></p>
                    <div class="footer-social">
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="footer-heading"><?php echo __('quick_links'); ?></h6>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/"><?php echo __('nav_home'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/cars.php"><?php echo __('nav_cars'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/guide.php"><?php echo __('nav_guide'); ?></a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-3 col-6">
                    <h6 class="footer-heading"><?php echo __('nav_account'); ?></h6>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/login.php"><?php echo __('nav_login'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/register.php"><?php echo __('nav_register'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/my-orders.php"><?php echo __('nav_orders'); ?></a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h6 class="footer-heading"><?php echo __('contact_us'); ?></h6>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> <?php echo sanitize_output(get_site_setting('site_address') ?? 'Jakarta, Indonesia'); ?></li>
                        <li><i class="fab fa-whatsapp"></i> +<?php echo sanitize_output(get_site_setting('whatsapp_number') ?? '6281234567890'); ?></li>
                    </ul>
                </div>
            </div>

            <!-- Payment Partners -->
            <div class="footer-partners">
                <h6 class="footer-heading text-center mb-3"><?php echo get_current_lang() === 'id' ? 'Metode Pembayaran' : 'Payment Partners'; ?></h6>
                <div class="partner-logos">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/bca.png" alt="BCA" title="BCA Virtual Account">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/Bni.png" alt="BNI" title="BNI Virtual Account">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/bri.png" alt="BRI" title="BRI Virtual Account">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/mandiri.png" alt="Mandiri" title="Mandiri Virtual Account">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/Permata.png" alt="Permata" title="Permata Virtual Account">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/cimb.png" alt="CIMB" title="CIMB Virtual Account">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/Gopay.png" alt="GoPay" title="GoPay">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/shopeepay.png" alt="ShopeePay" title="ShopeePay">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/dana.png" alt="DANA" title="DANA">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/ovo.png" alt="OVO" title="OVO">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/qris.png" alt="QRIS" title="QRIS">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/visa.png" alt="Visa" title="Visa">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/mastercard.png" alt="Mastercard" title="Mastercard">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/jcb.png" alt="JCB" title="JCB">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/indomaret.png" alt="Indomaret" title="Indomaret">
                    <img src="<?php echo SITE_URL; ?>/assets/images/payments/alfamart.png" alt="Alfamart" title="Alfamart">
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="footer-bottom">
                <small>&copy; <?php echo date('Y'); ?> <span class="brand-text"><?php echo SITE_NAME; ?></span>. All rights reserved.</small>
                <small class="footer-powered">Powered by Midtrans</small>
            </div>
        </div>
    </footer>

    <!-- AI Chat Widget -->
    <div id="chat-widget">
        <button id="chat-toggle" onclick="toggleChat()" title="Chat with AI Assistant">
            <i class="fas fa-comments"></i>
        </button>
        <div id="chat-window">
            <div id="chat-header">
                <span><i class="fas fa-robot"></i> <?php echo __('chat_title'); ?></span>
                <button onclick="toggleChat()" class="chat-close"><i class="fas fa-times"></i></button>
            </div>
            <div id="chat-content">
                <div class="chat-message bot">
                    <?php echo __('chat_welcome'); ?>
                    <ul>
                        <li><?php echo __('chat_opt1'); ?></li>
                        <li><?php echo __('chat_opt2'); ?></li>
                        <li><?php echo __('chat_opt3'); ?></li>
                        <li><?php echo __('chat_opt4'); ?></li>
                    </ul>
                </div>
            </div>
            <div id="chat-input-area">
                <input type="text" id="chat-input" placeholder="<?php echo __('chat_placeholder'); ?>" onkeypress="if(event.key==='Enter')handleSend()">
                <button onclick="handleSend()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/chatbox.js"></script>
</body>
</html>
