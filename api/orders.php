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
    $rental_occasion = $_POST['rental_occasion'] ?? null;
    if ($rental_occasion && !in_array($rental_occasion, ['business', 'family', 'vacation', 'daily', 'other'])) {
        $rental_occasion = null;
    }
    
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
    $stmt = $conn->prepare("SELECT price_per_day FROM cars WHERE id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $car = $result->fetch_assoc();
    $stmt->close();
    
    if (!$car) {
        set_flash_message('danger', 'Car not found.');
        redirect(SITE_URL . '/cars.php');
    }
    
    // Check available stock for this car (not rented during the requested dates)
    $rental_end_date = date('Y-m-d', strtotime($rental_start_date . " + $duration_days days"));
    
    // Find an available stock unit that doesn't have overlapping bookings
    $stmt = $conn->prepare("SELECT cs.id, cs.plate_number FROM car_stock cs 
        WHERE cs.car_id = ? AND cs.status = 'available'
        AND cs.id NOT IN (
            SELECT o.car_stock_id FROM orders o 
            WHERE o.car_stock_id IS NOT NULL
            AND o.car_id = ? 
            AND o.status IN ('pending', 'approved')
            AND (
                (o.rental_start_date <= ? AND o.rental_end_date >= ?) OR
                (o.rental_start_date <= ? AND o.rental_end_date >= ?) OR
                (o.rental_start_date >= ? AND o.rental_end_date <= ?)
            )
        )
        LIMIT 1");
    $stmt->bind_param("iissssss", $car_id, $car_id, $rental_start_date, $rental_start_date, 
        $rental_end_date, $rental_end_date, $rental_start_date, $rental_end_date);
    $stmt->execute();
    $stock_result = $stmt->get_result();
    $available_unit = $stock_result->fetch_assoc();
    $stmt->close();
    
    if (!$available_unit) {
        set_flash_message('danger', 'Sorry, no units of this car are available for the selected dates. Please choose different dates or another car.');
        redirect(SITE_URL . '/order.php?id=' . $car_id);
    }
    
    $car_stock_id = $available_unit['id'];
    
    $original_price = $car['price_per_day'] * $duration_days;
    
    // Determine best applicable discount (highest percentage wins)
    $discount_type = null;
    $discount_percent = 0;
    
    // 1. Weekend discount (25%) — all rental days must be Sat-Sun
    $all_weekend = true;
    $check_date = strtotime($rental_start_date);
    for ($i = 0; $i < $duration_days; $i++) {
        $dow = date('w', $check_date);
        if ($dow != 0 && $dow != 6) { $all_weekend = false; break; }
        $check_date = strtotime('+1 day', $check_date);
    }
    if ($all_weekend && 25 > $discount_percent) {
        $discount_type = 'weekend'; $discount_percent = 25;
    }
    
    // 2. Long rental discount (20%) — 7+ days
    if ($duration_days >= 7 && 20 > $discount_percent) {
        $discount_type = 'long_rental'; $discount_percent = 20;
    }
    
    // 3. First order discount (15%)
    $stmt_first = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status IN ('approved','completed')");
    $stmt_first->bind_param("i", $user_id);
    $stmt_first->execute();
    $first_order_count = $stmt_first->get_result()->fetch_assoc()['cnt'];
    $stmt_first->close();
    if ($first_order_count == 0 && 15 > $discount_percent) {
        $discount_type = 'first_order'; $discount_percent = 15;
    }
    
    // 4. Family package discount (10%)
    if ($rental_occasion === 'family' && 10 > $discount_percent) {
        $discount_type = 'family'; $discount_percent = 10;
    }
    
    $total_price = $discount_percent > 0
        ? round($original_price * (1 - $discount_percent / 100))
        : $original_price;
    
    // WhatsApp order
    if ($order_type === 'whatsapp') {
        // Get WhatsApp number from settings
        $wa_number = get_site_setting('whatsapp_number') ?? '6281234567890';
        
        // Insert order first
        $stmt = $conn->prepare("INSERT INTO orders (user_id, car_id, car_stock_id, order_type, rental_start_date, rental_end_date, duration_days, delivery_option, delivery_address, total_price, original_price, discount_type, discount_percent, rental_occasion, status, notes) VALUES (?, ?, ?, 'whatsapp', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->bind_param("iiissisiddsiss", $user_id, $car_id, $car_stock_id, $rental_start_date, $rental_end_date, $duration_days, $delivery_option, $delivery_address, $total_price, $original_price, $discount_type, $discount_percent, $rental_occasion, $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Mark stock unit as rented
        $stmt = $conn->prepare("UPDATE car_stock SET status = 'rented' WHERE id = ?");
        $stmt->bind_param("i", $car_stock_id);
        $stmt->execute();
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
        if ($discount_percent > 0) {
            $message .= "Diskon: $discount_percent% ($discount_type)\n";
            $message .= "Harga Asli: Rp " . number_format($original_price, 0, ',', '.') . "\n";
        }
        $message .= "Total: Rp " . number_format($total_price, 0, ',', '.');
        
        $wa_link = "https://wa.me/$wa_number?text=" . urlencode($message);
        header("Location: $wa_link");
        exit;
    }
    
    // Website order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, car_id, car_stock_id, order_type, rental_start_date, rental_end_date, duration_days, delivery_option, delivery_address, total_price, original_price, discount_type, discount_percent, rental_occasion, status, notes) VALUES (?, ?, ?, 'website', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iiissisiddsiss", $user_id, $car_id, $car_stock_id, $rental_start_date, $rental_end_date, $duration_days, $delivery_option, $delivery_address, $total_price, $original_price, $discount_type, $discount_percent, $rental_occasion, $notes);
    
    if ($stmt->execute()) {
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Mark stock unit as rented
        $stmt = $conn->prepare("UPDATE car_stock SET status = 'rented' WHERE id = ?");
        $stmt->bind_param("i", $car_stock_id);
        $stmt->execute();
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
        
        // Redirect to payment page
        set_flash_message('success', 'Order placed successfully! Please proceed with payment.');
        redirect(SITE_URL . '/payment.php?order_id=' . $order_id);
    } else {
        $stmt->close();
        set_flash_message('danger', 'Failed to place order. Please try again.');
        redirect(SITE_URL . '/order.php?id=' . $car_id);
    }
} else {
    redirect(SITE_URL . '/cars.php');
}