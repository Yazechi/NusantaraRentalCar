<?php
// ============================================
// Site Configuration
// ============================================

// Start output buffering to prevent header errors
ob_start();

// Environment detection
$is_production = ($_SERVER['SERVER_NAME'] ?? 'localhost') !== 'localhost';

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// HTTPS enforcement for production
if ($is_production) {
    ini_set('session.cookie_secure', 1); // Require HTTPS for cookies
    
    // Redirect HTTP to HTTPS
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }
}

session_start();

// Error handling - Different for production and development
if ($is_production) {
    // Production: Hide errors, log to file
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
} else {
    // Development: Show all errors
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
}

// Site constants
define('SITE_NAME', 'Nusantara Rental Car');
define('SITE_URL', $is_production ? 'https://yourdomain.com' : 'http://localhost/NusantaraRentalCar');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/cars/');
define('UPLOAD_URL', SITE_URL . '/uploads/cars/');
define('IS_PRODUCTION', $is_production);

// Email configuration (for notifications)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: 'your-email@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'your-app-password');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@nusantararental.com');
define('SMTP_FROM_NAME', 'Nusantara Rental Car');

// File upload limits
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($is_production) {
    // Additional security headers for production
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self';");
}

// Create logs directory if it doesn't exist
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0755, true);
}

// Include database connection
require_once BASE_PATH . '/config/database.php';
