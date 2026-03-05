<?php
// API: Admin notification actions (dismiss item, mark as read)
$project_root = dirname(__DIR__);
require_once $project_root . '/config/config.php';
require_once $project_root . '/includes/security.php';
require_once $project_root . '/includes/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin_id = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'mark_read') {
    // Update the last_read_at timestamp to clear the badge count
    $stmt = $conn->prepare("INSERT INTO admin_notification_read (admin_id, last_read_at) VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_read_at = NOW()");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);

} elseif ($action === 'dismiss') {
    $key = $_POST['key'] ?? '';
    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing notification key']);
        exit;
    }
    $stmt = $conn->prepare("INSERT IGNORE INTO admin_notification_dismissed (admin_id, notification_key) VALUES (?, ?)");
    $stmt->bind_param("is", $admin_id, $key);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
