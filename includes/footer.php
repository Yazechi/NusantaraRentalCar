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

    <!-- AI Chat Widget -->
    <div id="chat-widget">
        <button id="chat-toggle" onclick="toggleChat()" title="Chat with AI Assistant">
            <i class="fas fa-comments"></i>
        </button>
        <div id="chat-window">
            <div id="chat-header">
                <span><i class="fas fa-robot"></i> Car Assistant</span>
                <button onclick="toggleChat()" class="chat-close"><i class="fas fa-times"></i></button>
            </div>
            <div id="chat-content">
                <div class="chat-message bot">
                    Hello! I'm your car rental assistant. Ask me about:
                    <ul>
                        <li>Available cars</li>
                        <li>Family/budget/luxury cars</li>
                        <li>Car specifications</li>
                        <li>Prices and brands</li>
                    </ul>
                </div>
            </div>
            <div id="chat-input-area">
                <input type="text" id="chat-input" placeholder="Ask about cars..." onkeypress="if(event.key==='Enter')handleSend()">
                <button onclick="handleSend()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/chatbox.js"></script>
</body>
</html>
