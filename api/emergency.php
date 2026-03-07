<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    $location = trim($_POST['location_details'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$order_id || empty($location) || empty($message)) {
        set_flash_message('danger', 'Please provide all required details.');
        redirect(SITE_URL . '/my-orders.php');
    }

    // Verify order belongs to user and is active
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'approved'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        set_flash_message('danger', 'Invalid or inactive order.');
        redirect(SITE_URL . '/my-orders.php');
    }
    $stmt->close();

    // Insert SOS request
    $stmt = $conn->prepare("INSERT INTO emergency_requests (user_id, order_id, location_details, message, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iiss", $user_id, $order_id, $location, $message);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'SOS Request Sent! Our team has been notified and a mechanic will be dispatched to your location.');
    } else {
        set_flash_message('danger', 'Failed to send SOS request. Please call our emergency number directly.');
    }
    $stmt->close();

    redirect(SITE_URL . '/my-orders.php');
} else {
    redirect(SITE_URL . '/my-orders.php');
}
