<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// We only fetch history for the exact current session. 
// If the user logs out, session_destroy() runs and they get a new session ID, resetting the chat.
$session_id = session_id();

if (!$session_id) {
    echo json_encode(['status' => 'success', 'history' => []]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT message, response, created_at FROM chat_history WHERE session_id = ? ORDER BY id ASC");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'history' => $history]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch history']);
}