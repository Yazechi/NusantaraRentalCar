<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $order_id = filter_var($_POST['order_id'], FILTER_VALIDATE_INT);
    $car_id = filter_var($_POST['car_id'], FILTER_VALIDATE_INT);
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT);
    $comment = trim($_POST['comment'] ?? '');

    if (!$order_id || !$car_id || !$rating || empty($comment)) {
        set_flash_message('danger', 'Please provide all required review details.');
        redirect(SITE_URL . '/my-orders.php');
    }

    // Verify order eligibility again
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'completed'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        set_flash_message('danger', 'Order not eligible for review.');
        redirect(SITE_URL . '/my-orders.php');
    }
    $stmt->close();

    // Check if already reviewed
    $stmt = $conn->prepare("SELECT id FROM car_reviews WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        set_flash_message('info', 'Already reviewed.');
        redirect(SITE_URL . '/my-orders.php');
    }
    $stmt->close();

    // Insert review
    $stmt = $conn->prepare("INSERT INTO car_reviews (user_id, car_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $user_id, $car_id, $order_id, $rating, $comment);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Thank you for your feedback! Your review has been submitted.');
    } else {
        set_flash_message('danger', 'Failed to submit review. Please try again.');
    }
    $stmt->close();

    redirect(SITE_URL . '/my-orders.php');
} else {
    redirect(SITE_URL . '/my-orders.php');
}
