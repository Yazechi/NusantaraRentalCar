<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Completely clear the session
logout_user();

// Now set the flash message (session is fresh)
set_flash_message('success', 'You have been logged out.');
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        // Clear chat history from sessionStorage
        sessionStorage.removeItem('chatHistory');
        // Redirect after clearing
        window.location.href = '<?php echo SITE_URL; ?>/login.php';
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>

