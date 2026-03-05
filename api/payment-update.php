<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = filter_var($data['order_id'] ?? 0, FILTER_VALIDATE_INT);
$payment_status = $data['payment_status'] ?? '';
$payment_method = $data['payment_method'] ?? 'midtrans';
$payment_id = $data['payment_id'] ?? '';

if (!$order_id || !in_array($payment_status, ['paid', 'pending', 'failed'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Verify order belongs to user
$stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Update payment information
if ($payment_status === 'paid') {
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', payment_method = ?, payment_id = ?, paid_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $payment_method, $payment_id, $order_id);
} else {
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, payment_method = ?, payment_id = ? WHERE id = ?");
    $stmt->bind_param("sssi", $payment_status, $payment_method, $payment_id, $order_id);
}

if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true]);
} else {
    $stmt->close();
    echo json_encode(['error' => 'Failed to update payment']);
}
