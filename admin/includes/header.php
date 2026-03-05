<?php
// Admin header - proteksi akses dan tampilkan topbar
// SEMUA AUTH/LOGIC harus SEBELUM HTML OUTPUT!

// Start output buffering to prevent header errors
ob_start();

// Amankan path relative terhadap root project
$project_root = dirname(dirname(__DIR__));

// Load konfigurasi dan core dari root - SEBELUM OUTPUT HTML
if (!defined('BASE_PATH')) {
    require_once $project_root . '/config/config.php';
    require_once $project_root . '/includes/security.php';
    require_once $project_root . '/includes/auth.php';
    require_once $project_root . '/includes/functions.php';
}
// Always load language support (must be after session_start from config.php)
require_once $project_root . '/includes/language.php';

// Proteksi admin - hanya admin yang bisa akses - JANGAN OUTPUT APAPUN SEBELUM INI!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean(); // Clear buffer before redirect
    set_flash_message('danger', 'Access denied. Admin login required.');
    redirect(SITE_URL . '/admin/index.php');
    exit;
}

// Check session timeout
if (!check_session_timeout()) {
    ob_end_clean(); // Clear buffer before redirect
    set_flash_message('warning', 'Session expired. Please login again.');
    redirect(SITE_URL . '/admin/index.php');
    exit;
}

// Get admin info dari session
$admin_name = $_SESSION['user_name'] ?? 'Admin';
$admin_email = $_SESSION['user_email'] ?? '';
$current_lang = get_current_lang();

// ========================================
// SEMUA LOGIC SELESAI - MULAI OUTPUT HTML
// ========================================
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitize_output($page_title) . ' - ' . SITE_NAME : SITE_NAME; ?> Admin</title>
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
    @font-face {
        font-family: 'ArchicocoRegular';
        src: url('<?php echo SITE_URL; ?>/assets/fonts/ArchicocoRegular.ttf') format('truetype');
        font-weight: bold;
        font-style: normal;
    }
    </style>
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>" rel="stylesheet">
<?php
// Global admin notifications — queries run before HTML so counts are available for the bell badge
$__today = date('Y-m-d');
$__admin_id = (int) $_SESSION['user_id'];

// Get last read timestamp
$__read_q = $conn->query("SELECT last_read_at FROM admin_notification_read WHERE admin_id = $__admin_id");
$__last_read = $__read_q && $__read_q->num_rows > 0 ? $__read_q->fetch_assoc()['last_read_at'] : '1970-01-01 00:00:00';

// Overdue rentals (not dismissible — always shown while overdue)
$__overdue_q = $conn->query("
    SELECT o.id, o.rental_end_date, DATEDIFF('$__today', o.rental_end_date) as days_overdue,
        u.name as user_name, u.phone as user_phone,
        c.name as car_name, cb.name as brand_name, cs.plate_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    WHERE o.rental_end_date < '$__today' AND o.status = 'approved'
    ORDER BY o.rental_end_date ASC
");
$__overdue_list = $__overdue_q ? $__overdue_q->fetch_all(MYSQLI_ASSOC) : [];

// Recent orders (last 10, excluding dismissed)
$__all_orders_q = $conn->query("
    SELECT o.id, o.total_price, o.status, o.created_at,
        u.name as user_name,
        c.name as car_name, cb.name as brand_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    WHERE NOT EXISTS (
        SELECT 1 FROM admin_notification_dismissed d
        WHERE d.admin_id = $__admin_id AND d.notification_key = CONCAT('order_', o.id)
    )
    ORDER BY o.created_at DESC
    LIMIT 10
");
$__all_orders_list = $__all_orders_q ? $__all_orders_q->fetch_all(MYSQLI_ASSOC) : [];

// Recent payments (last 10, excluding dismissed)
$__payments_q = $conn->query("
    SELECT o.id, o.total_price, o.paid_at,
        u.name as user_name,
        c.name as car_name, cb.name as brand_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    WHERE o.payment_status = 'paid'
    AND NOT EXISTS (
        SELECT 1 FROM admin_notification_dismissed d
        WHERE d.admin_id = $__admin_id AND d.notification_key = CONCAT('payment_', o.id)
    )
    ORDER BY o.paid_at DESC
    LIMIT 10
");
$__payments_list = $__payments_q ? $__payments_q->fetch_all(MYSQLI_ASSOC) : [];

// Badge count: only items newer than last_read_at
$__badge_count = count($__overdue_list);
foreach ($__all_orders_list as $__o) {
    if ($__o['created_at'] > $__last_read) $__badge_count++;
}
foreach ($__payments_list as $__p) {
    if ($__p['paid_at'] && $__p['paid_at'] > $__last_read) $__badge_count++;
}
?>
</head>

<body>
    <div class="admin-wrapper">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <img src="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png" alt="MeTrev" style="height:42px;width:42px;object-fit:contain;border-radius:50%;margin-right:8px;">
                    <span class="brand-text"><?php echo SITE_NAME; ?> Admin</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <!-- Notification Bell -->
                        <li class="nav-item me-2">
                            <a class="nav-link position-relative" href="#" id="notifBellToggle" role="button" onclick="toggleNotifPanel(); return false;">
                                <i class="fas fa-bell"></i>
                                <?php if ($__badge_count > 0): ?>
                                <span class="notif-badge" id="notifBadge"><?php echo $__badge_count > 99 ? '99+' : $__badge_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown me-2">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-globe"></i> <?php echo $current_lang === 'id' ? 'ID' : 'EN'; ?>
                            </a>
                            <?php
                            // Build language switch URLs that preserve current query params
                            $current_params_id = $_GET;
                            $current_params_id['lang'] = 'id';
                            $lang_url_id = '?' . http_build_query($current_params_id);
                            $current_params_en = $_GET;
                            $current_params_en['lang'] = 'en';
                            $lang_url_en = '?' . http_build_query($current_params_en);
                            ?>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item <?php echo $current_lang === 'id' ? 'active' : ''; ?>" href="<?php echo sanitize_output($lang_url_id); ?>">🇮🇩 <?php echo __('indonesian'); ?></a></li>
                                <li><a class="dropdown-item <?php echo $current_lang === 'en' ? 'active' : ''; ?>" href="<?php echo sanitize_output($lang_url_en); ?>">🇬🇧 <?php echo __('english'); ?></a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo sanitize_output($admin_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php"><?php echo __('admin_profile_settings'); ?></a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><?php echo __('admin_logout'); ?></a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Notification Panel (toggled by bell icon) -->
        <div class="admin-notif-panel d-none" id="adminNotifPanel">
            <div class="admin-notif-bar">
                <div class="admin-notif-bar-header">
                    <span class="admin-notif-bar-title"><i class="fas fa-bell"></i> <?php echo __('admin_notifications'); ?></span>
                    <button type="button" class="admin-overdue-dismiss" onclick="document.getElementById('adminNotifPanel').classList.add('d-none')" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="admin-notif-sections">

                    <?php if (!empty($__overdue_list)): ?>
                    <div class="admin-notif-section admin-notif-danger">
                        <div class="admin-notif-section-header">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong><?php echo count($__overdue_list); ?> <?php echo __('admin_overdue_notification_title'); ?></strong>
                        </div>
                        <div class="admin-notif-list">
                            <?php foreach ($__overdue_list as $__ov): ?>
                            <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $__ov['id']; ?>" class="admin-notif-item admin-notif-item-link">
                                <div class="admin-notif-item-content">
                                    <span class="admin-notif-car"><?php echo sanitize_output($__ov['brand_name'] . ' ' . $__ov['car_name']); ?></span>
                                    <?php if (!empty($__ov['plate_number'])): ?>
                                    <code class="admin-notif-plate"><?php echo sanitize_output($__ov['plate_number']); ?></code>
                                    <?php endif; ?>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-customer"><?php echo sanitize_output($__ov['user_name']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-days-badge"><?php echo $__ov['days_overdue']; ?> <?php echo __('admin_days_overdue'); ?></span>
                                </div>
                                <span class="admin-notif-order-id">#<?php echo $__ov['id']; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($__all_orders_list)): ?>
                    <div class="admin-notif-section admin-notif-info">
                        <div class="admin-notif-section-header">
                            <i class="fas fa-shopping-cart"></i>
                            <strong><?php echo count($__all_orders_list); ?> <?php echo __('admin_notif_all_orders'); ?></strong>
                            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-notif-viewall"><?php echo __('admin_view_all'); ?></a>
                        </div>
                        <div class="admin-notif-list">
                            <?php foreach ($__all_orders_list as $__no): ?>
                            <div class="admin-notif-item" id="notif-order-<?php echo $__no['id']; ?>">
                                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $__no['id']; ?>" class="admin-notif-item-content admin-notif-item-link">
                                    <span class="admin-notif-car"><?php echo sanitize_output($__no['brand_name'] . ' ' . $__no['car_name']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-customer"><?php echo sanitize_output($__no['user_name']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-amount"><?php echo format_currency($__no['total_price']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <?php echo get_status_badge($__no['status']); ?>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-time"><?php echo date('d M Y', strtotime($__no['created_at'])); ?></span>
                                    <span class="admin-notif-order-id">#<?php echo $__no['id']; ?></span>
                                </a>
                                <button type="button" class="admin-notif-dismiss-btn" onclick="dismissNotif('order_<?php echo $__no['id']; ?>', 'notif-order-<?php echo $__no['id']; ?>'); event.stopPropagation();" title="<?php echo __('admin_notif_dismiss'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($__payments_list)): ?>
                    <div class="admin-notif-section admin-notif-success">
                        <div class="admin-notif-section-header">
                            <i class="fas fa-credit-card"></i>
                            <strong><?php echo count($__payments_list); ?> <?php echo __('admin_notif_all_payments'); ?></strong>
                            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="admin-notif-viewall"><?php echo __('admin_view_all'); ?></a>
                        </div>
                        <div class="admin-notif-list">
                            <?php foreach ($__payments_list as $__pm): ?>
                            <div class="admin-notif-item" id="notif-payment-<?php echo $__pm['id']; ?>">
                                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $__pm['id']; ?>" class="admin-notif-item-content admin-notif-item-link">
                                    <span class="admin-notif-car"><?php echo sanitize_output($__pm['brand_name'] . ' ' . $__pm['car_name']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-customer"><?php echo sanitize_output($__pm['user_name']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-amount admin-notif-paid"><?php echo format_currency($__pm['total_price']); ?></span>
                                    <span class="admin-notif-sep">·</span>
                                    <span class="admin-notif-time"><?php echo date('d M Y, H:i', strtotime($__pm['paid_at'])); ?></span>
                                    <span class="admin-notif-order-id">#<?php echo $__pm['id']; ?></span>
                                </a>
                                <button type="button" class="admin-notif-dismiss-btn" onclick="dismissNotif('payment_<?php echo $__pm['id']; ?>', 'notif-payment-<?php echo $__pm['id']; ?>'); event.stopPropagation();" title="<?php echo __('admin_notif_dismiss'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($__overdue_list) && empty($__all_orders_list) && empty($__payments_list)): ?>
                    <div class="admin-notif-empty">
                        <i class="fas fa-check-circle"></i>
                        <p><?php echo __('admin_notif_empty'); ?></p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="admin-container">