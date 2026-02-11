<?php
// Admin Export Orders to CSV
$project_root = dirname(__DIR__);

require_once __DIR__ . '/includes/header.php';

// Get filters
$status_filter = trim($_GET['status'] ?? '');
$format = trim($_GET['format'] ?? 'csv'); // csv or pdf

// Build query with prepared statements
$valid_statuses = ['pending', 'approved', 'cancelled', 'completed'];
$has_filter = !empty($status_filter) && in_array($status_filter, $valid_statuses);

// Get orders
if ($has_filter) {
    $orders_query = "
        SELECT 
            o.id, 
            o.status,
            o.order_type,
            o.total_price,
            o.rental_start_date,
            o.rental_end_date,
            o.duration_days,
            o.delivery_option,
            o.created_at,
            u.name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            c.name as car_name,
            cb.name as brand_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN cars c ON o.car_id = c.id
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE o.status = ?
        ORDER BY o.created_at DESC
    ";
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("s", $status_filter);
} else {
    $orders_query = "
        SELECT 
            o.id, 
            o.status,
            o.order_type,
            o.total_price,
            o.rental_start_date,
            o.rental_end_date,
            o.duration_days,
            o.delivery_option,
            o.created_at,
            u.name as user_name,
            u.email as user_email,
            u.phone as user_phone,
            c.name as car_name,
            cb.name as brand_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN cars c ON o.car_id = c.id
        JOIN car_brands cb ON c.brand_id = cb.id
        ORDER BY o.created_at DESC
    ";
    $stmt = $conn->prepare($orders_query);
}

$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Export to CSV
if ($format === 'csv') {
    $filename = 'orders_export_' . date('Y-m-d_His') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Order ID',
        'Customer Name',
        'Customer Email',
        'Customer Phone',
        'Car',
        'Brand',
        'Order Type',
        'Start Date',
        'End Date',
        'Duration (Days)',
        'Delivery Option',
        'Total Price (IDR)',
        'Status',
        'Order Date'
    ]);
    
    // CSV Data
    foreach ($orders as $order) {
        fputcsv($output, [
            '#' . $order['id'],
            $order['user_name'],
            $order['user_email'],
            $order['user_phone'] ?: 'N/A',
            $order['car_name'],
            $order['brand_name'],
            ucfirst($order['order_type']),
            $order['rental_start_date'],
            $order['rental_end_date'],
            $order['duration_days'],
            ucfirst($order['delivery_option']),
            number_format($order['total_price'], 0, ',', '.'),
            ucfirst($order['status']),
            date('Y-m-d H:i', strtotime($order['created_at']))
        ]);
    }
    
    fclose($output);
    exit;
}

// If format is PDF or other, redirect back
set_flash_message('info', 'PDF export coming soon. CSV export is available.');
redirect(SITE_URL . '/admin/orders.php');
