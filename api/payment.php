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

if (!$order_id) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Verify order belongs to user
$stmt = $conn->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email 
        FROM orders o JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Get Midtrans server key from settings
$server_key = get_site_setting('midtrans_server_key') ?? '';

if (empty($server_key)) {
    // No Midtrans key configured - client will use simulator
    echo json_encode(['token' => null, 'message' => 'Midtrans not configured, using simulator']);
    exit;
}

// Build Midtrans Snap API request
$midtrans_url = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

$transaction_data = [
    'transaction_details' => [
        'order_id' => 'ORDER-' . $order_id . '-' . time(),
        'gross_amount' => (int)$order['total_price']
    ],
    'customer_details' => [
        'first_name' => $order['user_name'],
        'email' => $order['user_email']
    ],
    'item_details' => [
        [
            'id' => 'CAR-' . $order['car_id'],
            'price' => (int)$order['total_price'],
            'quantity' => 1,
            'name' => 'Car Rental Order #' . $order_id
        ]
    ],
    'enabled_payments' => [
        'credit_card', 'gopay', 'shopeepay', 'qris',
        'bank_transfer', 'bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va',
        'echannel', 'cstore', 'akulaku', 'kredivo'
    ]
];

$ch = curl_init($midtrans_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . base64_encode($server_key . ':')
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 201 || $http_code === 200) {
    $result = json_decode($response, true);
    
    // Store the token in the order
    $stmt = $conn->prepare("UPDATE orders SET payment_token = ? WHERE id = ?");
    $token = $result['token'] ?? '';
    $stmt->bind_param("si", $token, $order_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['token' => $result['token'] ?? null, 'redirect_url' => $result['redirect_url'] ?? null]);
} else {
    error_log("Midtrans API error: HTTP $http_code - $response");
    echo json_encode(['token' => null, 'error' => 'Payment gateway error']);
}
