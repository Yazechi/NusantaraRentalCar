<?php
// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_start();

// Error handling
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Site constants
define('SITE_NAME', 'Nusantara Rental Car');
define('SITE_URL', 'http://localhost/NusantaraRentalCar');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/cars/');
define('UPLOAD_URL', SITE_URL . '/uploads/cars/');

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

// Include database connection
require_once BASE_PATH . '/config/database.php';
