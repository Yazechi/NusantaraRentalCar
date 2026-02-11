<?php
// Admin header - proteksi akses dan tampilkan topbar
// SEMUA AUTH/LOGIC harus SEBELUM HTML OUTPUT!

// Amankan path relative terhadap root project
$project_root = dirname(dirname(__DIR__));

// Load konfigurasi dan core dari root - SEBELUM OUTPUT HTML
if (!defined('BASE_PATH')) {
    require_once $project_root . '/config/config.php';
    require_once $project_root . '/includes/security.php';
    require_once $project_root . '/includes/auth.php';
    require_once $project_root . '/includes/functions.php';
}

// Proteksi admin - hanya admin yang bisa akses - JANGAN OUTPUT APAPUN SEBELUM INI!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    set_flash_message('danger', 'Access denied. Admin login required.');
    redirect(SITE_URL . '/admin/index.php');
    exit;
}

// Check session timeout
if (!check_session_timeout()) {
    set_flash_message('warning', 'Session expired. Please login again.');
    redirect(SITE_URL . '/admin/index.php');
    exit;
}

// Get admin info dari session
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_email = $_SESSION['user_email'] ?? '';

// ========================================
// SEMUA LOGIC SELESAI - MULAI OUTPUT HTML
// ========================================
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitize_output($page_title) . ' - ' . SITE_NAME : SITE_NAME; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
</head>

<body>
    <div class="admin-wrapper">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-car"></i> <?php echo SITE_NAME; ?> Admin
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo sanitize_output($admin_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php">Profile Settings</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="admin-container">