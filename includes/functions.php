<?php

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function display_flash_message() {
    $flash = get_flash_message();
    if ($flash) {
        $type = sanitize_output($flash['type']);
        $message = sanitize_output($flash['message']);
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

function get_site_setting($key) {
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['setting_value'] : null;
}

function format_currency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function get_car_brands() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM car_brands ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

function check_session_timeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function get_status_badge($status) {
    $badges = [
        'pending' => 'warning',
        'approved' => 'success',
        'cancelled' => 'danger',
        'completed' => 'info'
    ];
    $class = $badges[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . ucfirst(sanitize_output($status)) . '</span>';
}