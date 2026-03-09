<?php
// Admin Header - Handles auth, session, language, and global notifications
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

// Get last read timestamp for this admin
$__last_read = null;
$__lr_q = $conn->prepare("SELECT last_read_at FROM admin_notification_read WHERE admin_id = ?");
$__lr_q->bind_param("i", $__admin_id);
$__lr_q->execute();
$__lr_res = $__lr_q->get_result();
if ($__lr_res && $__lr_row = $__lr_res->fetch_assoc()) {
    $__last_read = $__lr_row['last_read_at'];
}
$__lr_q->close();

// Get dismissed notification keys
$__dismissed = [];
$__dm_q = $conn->prepare("SELECT notification_key FROM admin_notification_dismissed WHERE admin_id = ?");
$__dm_q->bind_param("i", $__admin_id);
$__dm_q->execute();
$__dm_res = $__dm_q->get_result();
if ($__dm_res) {
    while ($__dm_row = $__dm_res->fetch_assoc()) {
        $__dismissed[] = $__dm_row['notification_key'];
    }
}
$__dm_q->close();

// Helper to check if item is dismissed
if (!function_exists('is_notif_dismissed')) {
    function is_notif_dismissed($key, $dismissed_array) {
        return in_array($key, $dismissed_array);
    }
}

/**
 * Helper to fetch notifications and update badge
 */
function fetch_notifications($query, $prefix, $dismissed, $last_read, &$badge_count, $time_col = 'created_at') {
    global $conn;
    $result = $conn->query($query);
    $items = [];
    if ($result) {
        $all = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($all as $item) {
            $key = $prefix . "-" . $item['id'];
            if (!in_array($key, $dismissed)) {
                $items[] = $item;
                if (!$last_read || $item[$time_col] > $last_read) {
                    $badge_count++;
                }
            }
        }
    }
    return $items;
}

// 1. Pending SOS Requests
$__sos_items = fetch_notifications(
    "SELECT er.*, u.name as user_name, c.name as car_name, cs.plate_number 
     FROM emergency_requests er 
     JOIN users u ON er.user_id = u.id 
     JOIN car_stock cs ON er.car_stock_id = cs.id 
     JOIN cars c ON cs.car_id = c.id
     WHERE er.status = 'pending' ORDER BY er.created_at DESC LIMIT 10",
    "notif-sos", $__dismissed, $__last_read, $__badge_count
);

// 2. Unread Feedback
$__fb_items = fetch_notifications(
    "SELECT * FROM admin_feedback WHERE is_read = 0 ORDER BY created_at DESC LIMIT 10",
    "notif-fb", $__dismissed, $__last_read, $__badge_count
);

// 3. New Reviews (last 7 days)
$__review_items = fetch_notifications(
    "SELECT cr.*, u.name as user_name, c.name as car_name 
     FROM car_reviews cr 
     JOIN users u ON cr.user_id = u.id 
     JOIN cars c ON cr.car_id = c.id
     WHERE cr.created_at >= NOW() - INTERVAL 7 DAY ORDER BY cr.created_at DESC LIMIT 10",
    "notif-review", $__dismissed, $__last_read, $__badge_count
);

// 4. Overdue Rentals
$__overdue_items = fetch_notifications(
    "SELECT o.*, u.name as user_name, c.name as car_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     JOIN cars c ON o.car_id = c.id 
     WHERE o.rental_end_date < '$__today' AND o.status = 'approved' ORDER BY o.rental_end_date ASC LIMIT 10",
    "notif-overdue", $__dismissed, $__last_read, $__badge_count, 'updated_at'
);

// 5. Pending Orders
$__pending_orders_items = fetch_notifications(
    "SELECT o.*, u.name as user_name, c.name as car_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     JOIN cars c ON o.car_id = c.id 
     WHERE o.status = 'pending' ORDER BY o.created_at DESC LIMIT 10",
    "notif-orders", $__dismissed, $__last_read, $__badge_count
);

// 6. Recent Paid Payments (last 3 days)
$__paid_items = fetch_notifications(
    "SELECT o.*, u.name as user_name, c.name as car_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.id 
     JOIN cars c ON o.car_id = c.id 
     WHERE o.payment_status = 'paid' AND o.paid_at >= NOW() - INTERVAL 3 DAY ORDER BY o.paid_at DESC LIMIT 10",
    "notif-paid", $__dismissed, $__last_read, $__badge_count, 'paid_at'
);

$__support_visible = !empty($__sos_items) || !empty($__fb_items) || !empty($__review_items);
$__ops_visible = !empty($__overdue_items) || !empty($__pending_orders_items) || !empty($__paid_items);

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
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>" rel="stylesheet">
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
        .border-bottom-light { border-bottom: 1px solid rgba(0,0,0,0.05); }
        .x-small { font-size: 0.75rem; }
        .animate-pulse { animation: pulse-opacity 1.5s infinite; }
        @keyframes pulse-opacity {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
    <script>
        // Apply theme early to prevent flash
        var savedTheme = localStorage.getItem('adminTheme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>
    <div class="admin-wrapper">
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top custom-admin-navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <img src="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png" alt="MeTrev" style="height:40px; border-radius:50%; margin-right:10px;">
                    <span class="brand-text"><?php echo SITE_NAME; ?> Admin</span>
                </a>
                
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item me-3">
                            <button class="btn btn-link nav-link px-2" id="themeToggleBtn" title="Toggle Theme" style="font-size: 1.1rem; color: var(--navbar-text);">
                                <i class="fas fa-moon"></i>
                            </button>
                        </li>
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
                                <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?php echo !empty($__sos_items) ? 'notif-badge-pulse' : ''; ?>">
                                    <?php echo $__badge_count; ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 340px; max-height: 420px; overflow-y: auto;">
                                <li class="dropdown-header border-bottom py-2">Notifications</li>

                                <?php if ($__support_visible): ?>
                                <li class="dropdown-header small text-uppercase fw-bold text-secondary px-3 pt-3 pb-1"><i class="fas fa-headset me-1"></i> Support & Interaction</li>
                                <?php endif; ?>
                                
                                <?php foreach($__sos_items as $item): ?>
                                <li class="notif-item" id="notif-sos-<?php echo $item['id']; ?>"><a class="dropdown-item py-2 d-flex align-items-start border-bottom-light position-relative" href="<?php echo SITE_URL; ?>/admin/emergencies.php">
                                    <div class="bg-danger bg-opacity-10 p-2 rounded me-3"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                                    <div class="flex-grow-1 pe-3">
                                        <div class="small fw-bold text-danger"><i class="fas fa-xs fa-circle me-1 animate-pulse"></i> SOS: BUTUH BANTUAN</div>
                                        <div class="small fw-bold text-dark"><?php echo sanitize_output($item['car_name']); ?></div>
                                        <div class="x-small text-muted"><?php echo sanitize_output($item['plate_number']); ?> • <?php echo sanitize_output($item['user_name']); ?></div>
                                        <div class="x-small text-danger fw-bold mt-1"><i class="far fa-clock me-1"></i><?php echo time_elapsed_string($item['created_at']); ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 position-absolute top-0 end-0 mt-2 me-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-sos-<?php echo $item['id']; ?>');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endforeach; ?>

                                <?php foreach($__fb_items as $item): ?>
                                <li class="notif-item" id="notif-fb-<?php echo $item['id']; ?>"><a class="dropdown-item py-2 d-flex align-items-start border-bottom-light position-relative" href="<?php echo SITE_URL; ?>/admin/feedback.php">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded me-3"><i class="fas fa-comment-dots text-primary"></i></div>
                                    <div class="flex-grow-1 pe-3">
                                        <div class="small fw-bold text-primary">New Feedback</div>
                                        <div class="small text-truncate" style="max-width: 220px;"><?php echo sanitize_output($item['subject']); ?></div>
                                        <div class="x-small text-muted"><?php echo sanitize_output($item['name']); ?> • <?php echo time_elapsed_string($item['created_at']); ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 position-absolute top-0 end-0 mt-2 me-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-fb-<?php echo $item['id']; ?>');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endforeach; ?>

                                <?php foreach($__review_items as $item): ?>
                                <li class="notif-item" id="notif-review-<?php echo $item['id']; ?>"><a class="dropdown-item py-2 d-flex align-items-start border-bottom-light position-relative" href="<?php echo SITE_URL; ?>/admin/reviews.php">
                                    <div class="bg-warning bg-opacity-10 p-2 rounded me-3"><i class="fas fa-star text-warning"></i></div>
                                    <div class="flex-grow-1 pe-3">
                                        <div class="small fw-bold text-warning">New <?php echo $item['rating']; ?>-Star Review</div>
                                        <div class="small text-truncate" style="max-width: 220px;"><?php echo sanitize_output($item['car_name']); ?></div>
                                        <div class="x-small text-muted"><?php echo sanitize_output($item['user_name']); ?> • <?php echo time_elapsed_string($item['created_at']); ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 position-absolute top-0 end-0 mt-2 me-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-review-<?php echo $item['id']; ?>');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endforeach; ?>

                                <?php if ($__ops_visible): ?>
                                <li class="dropdown-header small text-uppercase fw-bold text-secondary px-3 pt-3 pb-1 border-top-light mt-1"><i class="fas fa-car me-1"></i> Operations</li>
                                <?php endif; ?>

                                <?php foreach($__overdue_items as $item): ?>
                                <li class="notif-item" id="notif-overdue-<?php echo $item['id']; ?>"><a class="dropdown-item py-2 d-flex align-items-start border-bottom-light position-relative" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                    <div class="bg-warning bg-opacity-10 p-2 rounded me-3"><i class="fas fa-exclamation-triangle text-warning"></i></div>
                                    <div class="flex-grow-1 pe-3">
                                        <div class="small fw-bold text-warning">Overdue: <?php echo sanitize_output($item['car_name']); ?></div>
                                        <div class="small">Due: <?php echo format_date($item['rental_end_date']); ?></div>
                                        <div class="x-small text-muted">Customer: <?php echo sanitize_output($item['user_name']); ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 position-absolute top-0 end-0 mt-2 me-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-overdue-<?php echo $item['id']; ?>');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endforeach; ?>

                                <?php foreach($__pending_orders_items as $item): ?>
                                <li class="notif-item" id="notif-orders-<?php echo $item['id']; ?>"><a class="dropdown-item py-2 d-flex align-items-start border-bottom-light position-relative" href="<?php echo SITE_URL; ?>/admin/orders.php?status=pending">
                                    <div class="bg-info bg-opacity-10 p-2 rounded me-3"><i class="fas fa-shopping-cart text-info"></i></div>
                                    <div class="flex-grow-1 pe-3">
                                        <div class="small fw-bold text-info">New Order #<?php echo $item['id']; ?></div>
                                        <div class="small text-truncate" style="max-width: 220px;"><?php echo sanitize_output($item['car_name']); ?> • <?php echo format_currency($item['total_price']); ?></div>
                                        <div class="x-small text-muted"><?php echo sanitize_output($item['user_name']); ?> • <?php echo time_elapsed_string($item['created_at']); ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 position-absolute top-0 end-0 mt-2 me-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-orders-<?php echo $item['id']; ?>');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endforeach; ?>

                                <?php foreach($__paid_items as $item): ?>
                                <li class="notif-item" id="notif-paid-<?php echo $item['id']; ?>"><a class="dropdown-item py-2 d-flex align-items-start border-bottom-light position-relative" href="<?php echo SITE_URL; ?>/admin/orders.php?payment_status=paid">
                                    <div class="bg-success bg-opacity-10 p-2 rounded me-3"><i class="fas fa-money-bill-wave text-success"></i></div>
                                    <div class="flex-grow-1 pe-3">
                                        <div class="small fw-bold text-success">Payment Received</div>
                                        <div class="small text-truncate" style="max-width: 220px;">Order #<?php echo $item['id']; ?> • <?php echo format_currency($item['total_price']); ?></div>
                                        <div class="x-small text-muted"><?php echo sanitize_output($item['user_name']); ?> • <?php echo time_elapsed_string($item['paid_at']); ?></div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 position-absolute top-0 end-0 mt-2 me-2 notif-dismiss" onclick="event.preventDefault();event.stopPropagation();dismissNotifItem('notif-paid-<?php echo $item['id']; ?>');" title="Dismiss"><i class="fas fa-times"></i></button>
                                </a></li>
                                <?php endforeach; ?>

                                <?php if (!$__support_visible && !$__ops_visible): ?>
                                <li class="text-center py-4 text-muted small" id="notif-empty"><i class="fas fa-check-circle d-block mb-2 fa-2x opacity-20"></i>No new notifications</li>
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
