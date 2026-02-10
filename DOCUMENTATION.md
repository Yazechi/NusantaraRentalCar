# Nusantara Rental Car - Project Documentation

## Overview
A car rental website built with native PHP, JavaScript, and Bootstrap CSS using MySQL database.

## Tech Stack
- Backend: PHP (Native)
- Frontend: HTML, CSS (Bootstrap 5), JavaScript
- Database: MySQL
- Icons: Font Awesome

## Coding Standards

### Database Queries - IMPORTANT
Always use **prepared statements** for all database operations. Never concatenate variables directly into SQL queries.

Example of correct usage:
```php
// Correct - Using prepared statement
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

// Also correct - Named parameters with PDO
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
```

Never do this:
```php
// WRONG - SQL Injection vulnerability
$result = $conn->query("SELECT * FROM cars WHERE id = " . $_GET['id']);
```

---

## Security Guidelines

All team members must follow these security practices:

### 1. Password Security
- Use `password_hash()` with PASSWORD_DEFAULT for hashing
- Use `password_verify()` for checking passwords
- Never store plain text passwords

```php
// Hashing password on registration
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Verifying password on login
if (password_verify($input_password, $stored_hash)) {
    // Password correct
}
```

### 2. Session Security
- Regenerate session ID after login to prevent session fixation
- Set secure session configuration in config.php
- Implement session timeout (30 minutes recommended)

```php
// After successful login
session_regenerate_id(true);

// Session configuration (in config.php)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable if using HTTPS
ini_set('session.use_strict_mode', 1);
```

### 3. CSRF Protection
- Generate CSRF token for all forms
- Validate token on form submission

```php
// Generate token (store in session)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In form
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Validate on submission
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Invalid request');
}
```

### 4. Input Validation
- Validate ALL user inputs on server-side
- Use filter_input() and filter_var() functions
- Whitelist allowed values when possible

```php
// Validate email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

// Validate integer
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Sanitize string
$name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
```

### 5. XSS Prevention
- Always escape output with htmlspecialchars()
- Use ENT_QUOTES and UTF-8 encoding

```php
// When displaying user data
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### 6. File Upload Security
- Validate file extension against whitelist (jpg, jpeg, png, webp)
- Check MIME type using finfo
- Limit file size (max 2MB recommended)
- Rename uploaded files with unique names
- Store uploads outside web root or in protected directory

```php
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['image']['tmp_name']);

if (!in_array($mime, $allowed)) {
    die('Invalid file type');
}

// Generate unique filename
$filename = uniqid() . '_' . time() . '.jpg';
```

### 7. Access Control
- Check user authentication on every protected page
- Verify admin role before allowing admin actions
- Never trust client-side validation alone

```php
// At top of protected pages
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// At top of admin pages
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
```

### 8. Error Handling
- Never display detailed errors to users in production
- Log errors to file for debugging
- Show generic error messages to users

```php
// In config.php for production
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

### 9. HTTP Security Headers
Add these headers in includes/header.php or config.php:

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

### 10. Database User Permissions
- Create a dedicated MySQL user for the application
- Grant only necessary permissions (SELECT, INSERT, UPDATE, DELETE)
- Never use root credentials in production

---

## Team Work Division

The project is divided into 4 main sections for team members:

### Member 1: Authentication and User Management
- Files to work on:
  - config/database.php
  - config/config.php
  - includes/auth.php
  - includes/functions.php
  - includes/security.php
  - login.php
  - register.php
  - logout.php
  - profile.php

### Member 2: Public Car Display and Filtering
- Files to work on:
  - includes/header.php
  - includes/footer.php
  - index.php
  - cars.php
  - car-detail.php
  - api/cars.php
  - api/filter.php
  - assets/js/filter.js
  - assets/css/style.css

### Member 3: Order System and AI Chatbox
- Files to work on:
  - order.php
  - my-orders.php
  - api/orders.php
  - api/chat.php
  - assets/js/order.js
  - assets/js/chatbox.js

### Member 4: Admin Panel
- Files to work on:
  - admin/index.php
  - admin/dashboard.php
  - admin/cars.php
  - admin/car-add.php
  - admin/car-edit.php
  - admin/car-delete.php
  - admin/orders.php
  - admin/order-detail.php
  - admin/order-update.php
  - admin/users.php
  - admin/settings.php
  - admin/includes/sidebar.php
  - admin/includes/header.php
  - admin/includes/footer.php
  - assets/css/admin.css
  - assets/js/admin.js

---

## Database Setup

1. Open MySQL client or phpMyAdmin
2. Run the SQL file located at: database/database.sql
3. Default admin credentials:
   - Email: admin@nusantararental.com
   - Password: admin123

---

## Project Structure

```
NusantaraRentalCar/
|-- config/
|   |-- database.php      (Database connection)
|   |-- config.php        (Site configuration)
|
|-- includes/
|   |-- header.php        (Public header)
|   |-- footer.php        (Public footer)
|   |-- functions.php     (Helper functions)
|   |-- auth.php          (Authentication functions)
|   |-- security.php      (Security helper functions)
|
|-- admin/
|   |-- includes/
|   |   |-- header.php    (Admin header)
|   |   |-- footer.php    (Admin footer)
|   |   |-- sidebar.php   (Admin sidebar)
|   |-- index.php         (Admin login redirect)
|   |-- dashboard.php     (Admin dashboard)
|   |-- cars.php          (Manage cars list)
|   |-- car-add.php       (Add new car)
|   |-- car-edit.php      (Edit car)
|   |-- car-delete.php    (Delete car)
|   |-- orders.php        (Manage orders)
|   |-- order-detail.php  (Order details)
|   |-- order-update.php  (Update order status)
|   |-- users.php         (Manage users)
|   |-- settings.php      (Site settings)
|
|-- api/
|   |-- cars.php          (Cars API endpoint)
|   |-- orders.php        (Orders API endpoint)
|   |-- chat.php          (AI Chat API endpoint)
|   |-- filter.php        (Filter API endpoint)
|
|-- assets/
|   |-- css/
|   |   |-- style.css     (Main stylesheet)
|   |   |-- admin.css     (Admin stylesheet)
|   |-- js/
|   |   |-- main.js       (Main JavaScript)
|   |   |-- admin.js      (Admin JavaScript)
|   |   |-- chatbox.js    (AI Chatbox)
|   |   |-- filter.js     (Car filter)
|   |   |-- order.js      (Order handling)
|   |-- images/
|       |-- cars/         (Static car images)
|
|-- uploads/
|   |-- cars/             (Uploaded car images)
|
|-- database/
|   |-- database.sql      (Database schema)
|
|-- index.php             (Homepage)
|-- login.php             (User login)
|-- register.php          (User registration)
|-- logout.php            (Logout handler)
|-- cars.php              (Car listing)
|-- car-detail.php        (Single car details)
|-- order.php             (Place order)
|-- my-orders.php         (User orders)
|-- profile.php           (User profile)
```

---

## How to Run

1. Set up a local server (XAMPP, WAMP, or Laragon)
2. Place project folder in the web server root (htdocs or www)
3. Import database/database.sql to MySQL
4. Update config/database.php with your database credentials
5. Access via http://localhost/NusantaraRentalCar

---

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| api/cars.php | GET | Get all cars or single car |
| api/filter.php | GET | Filter cars by criteria |
| api/orders.php | POST | Create new order |
| api/chat.php | POST | AI chat interaction |

---

## Notes

- All files are created empty and ready for implementation
- Follow the checklist in CHECKLIST.md for progress tracking
- Use Bootstrap 5 CDN for styling
- Use Font Awesome CDN for icons
- ALWAYS use prepared statements for database queries (see Coding Standards section)
