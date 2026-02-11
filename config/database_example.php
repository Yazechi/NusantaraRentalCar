<?php
// ============================================
// Database Configuration Example
// ============================================
// 
// INSTRUCTIONS:
// 1. Copy this file to config/database.php
// 2. Update the credentials below with your actual database settings
// 3. Never commit database.php to version control
//

// Environment detection
$is_production = ($_SERVER['SERVER_NAME'] ?? 'localhost') !== 'localhost';

// Database credentials
// IMPORTANT: Update these values for your environment
if ($is_production) {
    // Production database credentials
    // Using environment variables is recommended for production
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_user = getenv('DB_USER') ?: 'your_production_user';
    $db_pass = getenv('DB_PASS') ?: 'your_production_password';
    $db_name = getenv('DB_NAME') ?: 'nusantara_rental_car';
} else {
    // Development/Local database credentials
    // Update these for your local setup
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "nusantara_rental_car";
}

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    
    if ($is_production) {
        // Production: Show generic error
        die('Unable to connect to the database. Please contact support.');
    } else {
        // Development: Show detailed error
        die('Connection failed: ' . $conn->connect_error);
    }
}

// Set charset to UTF-8
$conn->set_charset('utf8mb4');
