<?php

function register_user($name, $email, $password, $phone = null, $address = null) {
    global $conn;

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Email is already registered.'];
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $address);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => 'Registration successful.'];
    }

    $stmt->close();
    return ['success' => false, 'message' => 'Registration failed. Please try again.'];
}

function login_user($email, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    return ['success' => true, 'role' => $user['role']];
}

function logout_user() {
    session_unset();
    session_destroy();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && check_session_timeout();
}

function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Please log in to access this page.');
        redirect(SITE_URL . '/login.php');
    }
}

function require_admin() {
    if (!is_admin()) {
        set_flash_message('danger', 'Access denied.');
        redirect(SITE_URL . '/login.php');
    }
}

function get_logged_in_user() {
    global $conn;
    if (!is_logged_in()) {
        return null;
    }
    $stmt = $conn->prepare("SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function update_user_profile($user_id, $name, $phone, $address) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $phone, $address, $user_id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        $_SESSION['user_name'] = $name;
        return ['success' => true, 'message' => 'Profile updated successfully.'];
    }
    return ['success' => false, 'message' => 'Failed to update profile.'];
}

function change_password($user_id, $current_password, $new_password) {
    global $conn;

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $user_id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
    return ['success' => false, 'message' => 'Failed to change password.'];
}