<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('warning', 'Please log in to place an order.');
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'Invalid request. Please try again.');
        redirect(SITE_URL . '/cars.php');
    }
    
    $user_id = $_SESSION['user_id'];
    $car_id = filter_var($_POST['car_id'] ?? 0, FILTER_VALIDATE_INT);
    $rental_start_date = $_POST['rental_start_date'] ?? '';
    $duration_days = filter_var($_POST['duration_days'] ?? 0, FILTER_VALIDATE_INT);
    $delivery_option = $_POST['delivery_option'] ?? 'pickup';
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $order_type = $_POST['order_type'] ?? 'website';
    
    // Validate inputs
    $errors = [];
    
    if (!$car_id || $car_id < 1) {
        $errors[] = 'Invalid car selected.';
    }
    
    if (empty($rental_start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $rental_start_date)) {
        $errors[] = 'Please select a valid start date.';
    } elseif (strtotime($rental_start_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Start date cannot be in the past.';
    }
    
    if (!$duration_days || $duration_days < 1 || $duration_days > 30) {
        $errors[] = 'Duration must be between 1 and 30 days.';
    }
    
    if (!in_array($delivery_option, ['pickup', 'delivery'])) {
        $errors[] = 'Invalid delivery option.';
    }
    
    if (!in_array($order_type, ['website', 'whatsapp'])) {
        $errors[] = 'Invalid order type.';
    }
    
    if (!empty($errors)) {
        set_flash_message('danger', implode(' ', $errors));
        redirect(SITE_URL . '/order.php?id=' . $car_id);
    }
    
    // Calculate rental end date
    $rental_end_date = date('Y-m-d', strtotime($rental_start_date . " + $duration_days days"));
    
    // Get car price using prepared statement
    $stmt = $conn->prepare("SELECT price_per_day, is_available FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if (!$car) {
        set_flash_message('danger', 'Car not found.');
        redirect(SITE_URL . '/cars.php');
    }
    
    if (!$car['is_available']) {
        set_flash_message('danger', 'This car is not available for rental.');
        redirect(SITE_URL . '/cars.php');
    }
    
    // Check for double-booking (overlapping dates)
    $stmt = $conn->prepare("SELECT id FROM orders WHERE car_id = ? 
        AND status IN ('pending', 'approved') 
        AND (
            (rental_start_date <= ? AND rental_end_date >= ?) OR
            (rental_start_date <= ? AND rental_end_date >= ?) OR
            (rental_start_date >= ? AND rental_end_date <= ?)
        )");
    $stmt->bind_param("issssss", $car_id, $rental_start_date, $rental_start_date, 
        $rental_end_date, $rental_end_date, $rental_start_date, $rental_end_date);
    $stmt->execute();
    $conflict_result = $stmt->get_result();
    $stmt->close();
    
    if ($conflict_result->num_rows > 0) {
        set_flash_message('danger', 'Sorry, this car is already booked for the selected dates. Please choose different dates.');
        redirect(SITE_URL . '/order.php?id=' . $car_id);
    }
    
    $total_price = $car['price_per_day'] * $duration_days;
    
    // WhatsApp order
    if ($order_type === 'whatsapp') {
        // Get WhatsApp number from settings
        $wa_number = get_site_setting('whatsapp_number') ?? '6281234567890';
        
        // Insert order first
        $stmt = $conn->prepare("INSERT INTO orders (user_id, car_id, order_type, rental_start_date, rental_end_date, duration_days, delivery_option, delivery_address, total_price, status, notes) VALUES (?, ?, 'whatsapp', ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("iissisids", $user_id, $car_id, $rental_start_date, $rental_end_date, $duration_days, $delivery_option, $delivery_address, $total_price, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Send email notifications
        $user = get_logged_in_user();
        if ($user) {
            // Get car details for email
            $car_stmt = $conn->prepare("SELECT c.name, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.id = ?");
            $car_stmt->bind_param("i", $car_id);
            $car_stmt->execute();
            $car_details = $car_stmt->get_result()->fetch_assoc();
            $car_stmt->close();
            $car_full_name = $car_details['brand_name'] . ' ' . $car_details['name'];
            
            // Send confirmation to user
            send_order_confirmation_user($user['email'], $user['name'], $order_id, $car_full_name, $rental_start_date, $rental_end_date, $total_price);
            
            // Send notification to admin
            send_order_notification_admin($order_id, $user['name'], $car_full_name, $total_price);
        }
        
        // Build WhatsApp message
        $message = "Halo Admin, saya ingin menyewa mobil.\n";
        $message .= "Order ID: #$order_id\n";
        $message .= "Tanggal mulai: $rental_start_date\n";
        $message .= "Durasi: $duration_days hari\n";
        $message .= "Total: Rp " . number_format($total_price, 0, ',', '.');
        
        $wa_link = "https://wa.me/$wa_number?text=" . urlencode($message);
        header("Location: $wa_link");
        exit;
    }
    
    // Website order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, car_id, order_type, rental_start_date, rental_end_date, duration_days, delivery_option, delivery_address, total_price, status, notes) VALUES (?, ?, 'website', ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iissisids", $user_id, $car_id, $rental_start_date, $rental_end_date, $duration_days, $delivery_option, $delivery_address, $total_price, $notes);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Send email notifications
        $user = get_logged_in_user();
        if ($user) {
            // Get car details for email
            $car_stmt = $conn->prepare("SELECT c.name, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.id = ?");
            $car_stmt->bind_param("i", $car_id);
            $car_stmt->execute();
            $car_details = $car_stmt->get_result()->fetch_assoc();
            $car_stmt->close();
            $car_full_name = $car_details['brand_name'] . ' ' . $car_details['name'];
            
            // Send confirmation to user
            send_order_confirmation_user($user['email'], $user['name'], $order_id, $car_full_name, $rental_start_date, $rental_end_date, $total_price);
            
            // Send notification to admin
            send_order_notification_admin($order_id, $user['name'], $car_full_name, $total_price);
        }
        
        set_flash_message('success', 'Order placed successfully! We will contact you soon.');
        redirect(SITE_URL . '/my-orders.php');
    } else {
        $stmt->close();
        set_flash_message('danger', 'Failed to place order. Please try again.');
        redirect(SITE_URL . '/order.php?id=' . $car_id);
    }
} else {
    redirect(SITE_URL . '/cars.php');
}