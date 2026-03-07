<?php
// Admin Header - JANGAN OUTPUT APAPUN SEBELUM HTML TAG
// File ini menangani auth, session, language, dan global notifications

if (!session_id()) session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/language.php';

// Proteksi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (ob_get_length()) ob_end_clean();
    set_flash_message('danger', 'Access denied. Admin login required.');
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

// Check session timeout
if (!check_session_timeout()) {
    if (ob_get_length()) ob_end_clean();
    set_flash_message('warning', 'Session expired. Please login again.');
    header('Location: ' . SITE_URL . '/admin/index.php');
    exit;
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$current_lang = get_current_lang();

// --- Global Notification Logic ---
$__today = date('Y-m-d');
$__badge_count = 0;
$__admin_id = (int)$_SESSION['user_id'];

// Get last read timestamp and dismissed keys for this admin
$__last_read = null;
$__lr_q = $conn->prepare("SELECT last_read_at FROM admin_notification_read WHERE admin_id = ?");
$__lr_q->bind_param("i", $__admin_id);
$__lr_q->execute();
$__lr_row = $__lr_q->get_result()->fetch_assoc();
if ($__lr_row) $__last_read = $__lr_row['last_read_at'];
$__lr_q->close();

$__dismissed = [];
$__dm_q = $conn->prepare("SELECT notification_key FROM admin_notification_dismissed WHERE admin_id = ?");
$__dm_q->bind_param("i", $__admin_id);
$__dm_q->execute();
$__dm_res = $__dm_q->get_result();
while ($__dm_row = $__dm_res->fetch_assoc()) $__dismissed[] = $__dm_row['notification_key'];
$__dm_q->close();

// 1. Pending SOS Requests (High Priority)
$__sos_q = $conn->query("SELECT COUNT(*) as cnt FROM emergency_requests WHERE status = 'pending'");
$__sos_count = $__sos_q ? $__sos_q->fetch_assoc()['cnt'] : 0;
$__sos_show = !in_array('notif-sos', $__dismissed);
// Badge: only count SOS created after last read
$__sos_badge = 0;
if ($__sos_count > 0 && $__last_read) {
    $__sq = $conn->prepare("SELECT COUNT(*) as cnt FROM emergency_requests WHERE status = 'pending' AND created_at > ?");
    $__sq->bind_param("s", $__last_read);
    $__sq->execute();
    $__sos_badge = $__sq->get_result()->fetch_assoc()['cnt'];
    $__sq->close();
} elseif ($__sos_count > 0) {
    $__sos_badge = $__sos_count;
}
$__badge_count += $__sos_badge;

// 2. Unread Feedback
$__fb_q = $conn->query("SELECT COUNT(*) as cnt FROM admin_feedback WHERE is_read = 0");
$__fb_count = $__fb_q ? $__fb_q->fetch_assoc()['cnt'] : 0;
$__fb_show = !in_array('notif-fb', $__dismissed);
$__fb_badge = 0;
if ($__fb_count > 0 && $__last_read) {
    $__fq = $conn->prepare("SELECT COUNT(*) as cnt FROM admin_feedback WHERE is_read = 0 AND created_at > ?");
    $__fq->bind_param("s", $__last_read);
    $__fq->execute();
    $__fb_badge = $__fq->get_result()->fetch_assoc()['cnt'];
    $__fq->close();
} elseif ($__fb_count > 0) {
    $__fb_badge = $__fb_count;
}
$__badge_count += $__fb_badge;

// 2b. New Reviews (last 24 hours)
$__review_q = $conn->query("SELECT COUNT(*) as cnt FROM car_reviews WHERE created_at >= NOW() - INTERVAL 24 HOUR");
$__review_count = $__review_q ? $__review_q->fetch_assoc()['cnt'] : 0;
$__review_show = !in_array('notif-review', $__dismissed);
$__review_badge = 0;
if ($__review_count > 0 && $__last_read) {
    $__rq = $conn->prepare("SELECT COUNT(*) as cnt FROM car_reviews WHERE created_at >= NOW() - INTERVAL 24 HOUR AND created_at > ?");
    $__rq->bind_param("s", $__last_read);
    $__rq->execute();
    $__review_badge = $__rq->get_result()->fetch_assoc()['cnt'];
    $__rq->close();
} elseif ($__review_count > 0) {
    $__review_badge = $__review_count;
}
$__badge_count += $__review_badge;

// 3. Overdue Rentals
$__overdue_q = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE rental_end_date < '$__today' AND status = 'approved'");
$__overdue_count = $__overdue_q ? $__overdue_q->fetch_assoc()['cnt'] : 0;
$__overdue_show = !in_array('notif-overdue', $__dismissed);
$__overdue_badge = 0;
if ($__overdue_count > 0 && $__last_read) {
    $__oq = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE rental_end_date < ? AND status = 'approved' AND updated_at > ?");
    $__oq->bind_param("ss", $__today, $__last_read);
    $__oq->execute();
    $__overdue_badge = $__oq->get_result()->fetch_assoc()['cnt'];
    $__oq->close();
} elseif ($__overdue_count > 0) {
    $__overdue_badge = $__overdue_count;
}
$__badge_count += $__overdue_badge;

// 4. Pending Orders
$__pending_orders_q = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending'");
$__pending_orders_count = $__pending_orders_q ? $__pending_orders_q->fetch_assoc()['cnt'] : 0;
$__orders_show = !in_array('notif-orders', $__dismissed);
$__orders_badge = 0;
if ($__pending_orders_count > 0 && $__last_read) {
    $__pq = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE status = 'pending' AND created_at > ?");
    $__pq->bind_param("s", $__last_read);
    $__pq->execute();
    $__orders_badge = $__pq->get_result()->fetch_assoc()['cnt'];
    $__pq->close();
} elseif ($__pending_orders_count > 0) {
    $__orders_badge = $__pending_orders_count;
}
$__badge_count += $__orders_badge;

?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitize_output($page_title) . ' - ' . SITE_NAME : SITE_NAME; ?> Admin</title>
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png">
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time() . rand(1, 1000); ?>" rel="stylesheet">
    <style>
        .notif-badge-pulse {
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .notif-dismiss { opacity: 0.4; transition: opacity 0.2s; }
        .notif-dismiss:hover { opacity: 1; }
        .notif-item { transition: opacity 0.3s, max-height 0.3s; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top custom-admin-navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <img src="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png" alt="MeTrev" style="height:40px; border-radius:50%; margin-right:10px;">
                    <span><?php echo SITE_NAME; ?> Admin</span>
                </a>
                
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <!-- Language Switcher -->
                        <li class="nav-item dropdown me-3">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-globe"></i> <?php echo strtoupper($current_lang); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item <?php echo $current_lang === 'id' ? 'active' : ''; ?>" href="?lang=id">🇮🇩 <?php echo __('indonesian'); ?></a></li>
                                <li><a class="dropdown-item <?php echo $current_lang === 'en' ? 'active' : ''; ?>" href="?lang=en">🇬🇧 <?php echo __('english'); ?></a></li>
                            </ul>
                        </li>

                        <!-- Notification Dropdown -->
                        <li class="nav-item dropdown me-3">
                            <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" id="notifBellToggle" onclick="clearNotifBadge()">
                                <i class="fas fa-bell fa-lg"></i>
                                <?php if ($__badge_count > 0): ?>
                                <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?php echo $__sos_count > 0 ? 'notif-badge-pulse' : ''; ?>">
                                    <?php echo $__badge_count; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 340px; max-height: 420px; overflow-y: auto;">
                                <li class="dropdown-header border-bottom">Notifications</li>

                                <?php $__support_visible = ($__sos_count > 0 && $__sos_show) || ($__fb_count > 0 && $__fb_show) || ($__review_count > 0 && $__review_show); ?>
                                <?php if ($__support_visible): ?>
                                <li class="dropdown-header small text-uppercase fw-bold text-secondary px-3 pt-2 pb-1"><i class="fas fa-headset me-1"></i> Support & Interaction</li>
                                <?php endif; ?>
                                <?php if ($__sos_count > 0 && $__sos_show): ?>
                                <li class="notif-item" id="notif-sos"><a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo SITE_URL; ?>/admin/emergencies.php">
                                    <span class="flex-grow-1"><i class="fas fa-ambulance text-danger me-2"></i> <strong><?php echo $__sos_count; ?></strong> Pending SOS</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-sos');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endif; ?>
                                <?php if ($__fb_count > 0 && $__fb_show): ?>
                                <li class="notif-item" id="notif-fb"><a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo SITE_URL; ?>/admin/feedback.php">
                                    <span class="flex-grow-1"><i class="fas fa-comment-dots text-primary me-2"></i> <strong><?php echo $__fb_count; ?></strong> New Feedbacks</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-fb');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endif; ?>
                                <?php if ($__review_count > 0 && $__review_show): ?>
                                <li class="notif-item" id="notif-review"><a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo SITE_URL; ?>/admin/reviews.php">
                                    <span class="flex-grow-1"><i class="fas fa-star text-warning me-2"></i> <strong><?php echo $__review_count; ?></strong> New Reviews</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-review');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endif; ?>

                                <?php $__ops_visible = ($__overdue_count > 0 && $__overdue_show) || ($__pending_orders_count > 0 && $__orders_show); ?>
                                <?php if ($__ops_visible): ?>
                                <li class="dropdown-header small text-uppercase fw-bold text-secondary px-3 pt-2 pb-1"><i class="fas fa-car me-1"></i> Operations</li>
                                <?php endif; ?>
                                <?php if ($__overdue_count > 0 && $__overdue_show): ?>
                                <li class="notif-item" id="notif-overdue"><a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                    <span class="flex-grow-1"><i class="fas fa-exclamation-triangle text-warning me-2"></i> <strong><?php echo $__overdue_count; ?></strong> Overdue Returns</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-overdue');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endif; ?>
                                <?php if ($__pending_orders_count > 0 && $__orders_show): ?>
                                <li class="notif-item" id="notif-orders"><a class="dropdown-item py-2 d-flex align-items-center" href="<?php echo SITE_URL; ?>/admin/orders.php?status=pending">
                                    <span class="flex-grow-1"><i class="fas fa-shopping-cart text-info me-2"></i> <strong><?php echo $__pending_orders_count; ?></strong> New Orders</span>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-orders');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endif; ?>

                                <?php if (!$__support_visible && !$__ops_visible): ?>
                                <li class="text-center py-3 text-muted small" id="notif-empty">No new notifications</li>
                                <?php endif; ?>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo sanitize_output($admin_name); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="admin-container">
