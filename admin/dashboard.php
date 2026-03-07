<?php
// Admin Dashboard - Analytics & Statistics
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_dashboard');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Get statistics
$stats = [];
$today = date('Y-m-d');

// Total car models
$result = $conn->query("SELECT COUNT(*) as count FROM cars");
$stats['total_cars'] = $result->fetch_assoc()['count'];

// Total stock units
$result = $conn->query("SELECT COUNT(*) as count FROM car_stock");
$stats['total_stock'] = $result->fetch_assoc()['count'];

// Available stock units
$result = $conn->query("SELECT COUNT(*) as count FROM car_stock WHERE status = 'available'");
$stats['available_stock'] = $result->fetch_assoc()['count'];

// Rented stock units
$result = $conn->query("SELECT COUNT(*) as count FROM car_stock WHERE status = 'rented'");
$stats['rented_stock'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Approved (active) orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'approved'");
$stats['approved_orders'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Pending SOS
$result = $conn->query("SELECT COUNT(*) as count FROM emergency_requests WHERE status = 'pending'");
$stats['pending_sos'] = $result->fetch_assoc()['count'];

// Unread Feedback
$result = $conn->query("SELECT COUNT(*) as count FROM admin_feedback WHERE is_read = 0");
$stats['unread_feedback'] = $result->fetch_assoc()['count'];

// --- Today's Analytics ---

// Rentals starting today
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE rental_start_date = ? AND status IN ('pending', 'approved')");
$stmt->bind_param("s", $today);
$stmt->execute();
$stats['rentals_today'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Returns today (rental end date = today)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE rental_end_date = ? AND status = 'approved'");
$stmt->bind_param("s", $today);
$stmt->execute();
$stats['returns_today'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Overdue returns (rental ended before today but still approved)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE rental_end_date < ? AND status = 'approved'");
$stmt->bind_param("s", $today);
$stmt->execute();
$stats['overdue_returns'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Payments today
$stmt = $conn->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_price), 0) as total FROM orders WHERE DATE(paid_at) = ? AND payment_status = 'paid'");
$stmt->bind_param("s", $today);
$stmt->execute();
$payment_today = $stmt->get_result()->fetch_assoc();
$stats['payments_today_count'] = $payment_today['count'];
$stats['payments_today_total'] = $payment_today['total'];
$stmt->close();

// Orders today
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$stats['orders_today'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Total revenue (all paid orders)
$result = $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE payment_status = 'paid'");
$stats['total_revenue'] = $result->fetch_assoc()['total'];

// Revenue this month
$month_start = date('Y-m-01');
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE payment_status = 'paid' AND DATE(paid_at) >= ?");
$stmt->bind_param("s", $month_start);
$stmt->execute();
$stats['revenue_this_month'] = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Revenue last 7 days for chart
$revenue_7days = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM orders WHERE payment_status = 'paid' AND DATE(paid_at) = ?");
    $stmt->bind_param("s", $day);
    $stmt->execute();
    $revenue_7days[] = [
        'date' => date('M d', strtotime($day)),
        'total' => (float)$stmt->get_result()->fetch_assoc()['total']
    ];
    $stmt->close();
}

// Orders by status for chart
$order_statuses = [];
$result = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $order_statuses[$row['status']] = $row['count'];
}

// Top rented cars
$top_cars = $conn->query("SELECT c.name, cb.name as brand_name, COUNT(o.id) as rental_count, SUM(o.total_price) as total_revenue
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    JOIN car_brands cb ON c.brand_id = cb.id
    WHERE o.status IN ('approved', 'completed') 
    GROUP BY o.car_id ORDER BY rental_count DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recent_orders = $conn->query("
    SELECT o.id, o.rental_start_date, o.rental_end_date, o.status, o.payment_status, o.total_price,
        u.name as user_name, c.name as car_name, cs.plate_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    ORDER BY o.created_at DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Cars due for return today
$due_returns = $conn->query("
    SELECT o.id, o.rental_end_date, u.name as user_name, u.phone as user_phone,
        c.name as car_name, cb.name as brand_name, cs.plate_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    WHERE o.rental_end_date <= '$today' AND o.status = 'approved'
    ORDER BY o.rental_end_date ASC
")->fetch_all(MYSQLI_ASSOC);

// Detail: Rentals starting today
$stmt = $conn->prepare("
    SELECT o.id, o.rental_start_date, o.rental_end_date, o.total_price, o.status,
        u.name as user_name, u.phone as user_phone,
        c.name as car_name, cb.name as brand_name, cs.plate_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    WHERE o.rental_start_date = ? AND o.status IN ('pending', 'approved')
    ORDER BY o.created_at DESC
");
$stmt->bind_param("s", $today);
$stmt->execute();
$rentals_today_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Detail: All currently rented cars (active rentals with status approved)
$active_rentals_list = $conn->query("
    SELECT o.id, o.rental_start_date, o.rental_end_date, o.total_price,
        u.name as user_name, u.phone as user_phone,
        c.name as car_name, cb.name as brand_name, cs.plate_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    WHERE o.status = 'approved'
    ORDER BY o.rental_end_date ASC
")->fetch_all(MYSQLI_ASSOC);

// Detail: All returned/completed cars
$returned_cars_list = $conn->query("
    SELECT o.id, o.rental_start_date, o.rental_end_date, o.total_price,
        u.name as user_name, c.name as car_name, cb.name as brand_name, cs.plate_number
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    WHERE o.status = 'completed'
    ORDER BY o.updated_at DESC
    LIMIT 50
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-content">
    <!-- Dashboard Header -->
    <div class="dash-header">
        <div class="dash-header-left">
            <h1><?php echo __('admin_dashboard'); ?></h1>
            <p><?php echo __('admin_welcome_back'); ?>, <strong><?php echo sanitize_output($_SESSION['user_name']); ?></strong> · <?php echo date('l, d M Y'); ?></p>
        </div>
        <div class="dash-header-actions">
            <a href="<?php echo SITE_URL; ?>/admin/car-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i><?php echo __('admin_add_new_car'); ?></a>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php?status=pending" class="btn btn-outline-warning btn-sm"><i class="fas fa-clock me-1"></i><?php echo __('admin_pending_orders'); ?> (<?php echo $stats['pending_orders']; ?>)</a>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <!-- Alert Banner -->
    <?php if ($stats['overdue_returns'] > 0 || $stats['pending_sos'] > 0): ?>
    <div class="dash-alerts mb-3">
        <?php if ($stats['pending_sos'] > 0): ?>
        <div class="dash-alert dash-alert-danger shadow-sm mb-2">
            <i class="fas fa-ambulance animate-pulse"></i>
            <span><strong><?php echo $stats['pending_sos']; ?> EMERGENCY SOS REQUESTS PENDING!</strong> — <a href="emergencies.php" class="text-white text-decoration-underline">Take action immediately</a></span>
        </div>
        <?php endif; ?>
        
        <?php if ($stats['overdue_returns'] > 0): ?>
        <div class="dash-alert dash-alert-warning shadow-sm">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong><?php echo $stats['overdue_returns']; ?> <?php echo __('admin_overdue_returns'); ?></strong> — <?php echo __('admin_overdue_followup'); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Revenue Banner -->
    <div class="dash-revenue-banner">
        <div class="dash-revenue-main">
            <span class="dash-revenue-label"><?php echo __('admin_revenue_today'); ?></span>
            <span class="dash-revenue-amount"><?php echo format_currency($stats['payments_today_total']); ?></span>
            <span class="dash-revenue-sub"><?php echo $stats['payments_today_count']; ?> <?php echo __('admin_payments'); ?></span>
        </div>
        <div class="dash-revenue-divider"></div>
        <div class="dash-revenue-item">
            <span class="dash-revenue-label"><?php echo __('admin_this_month'); ?></span>
            <span class="dash-revenue-val"><?php echo format_currency($stats['revenue_this_month']); ?></span>
        </div>
        <div class="dash-revenue-divider"></div>
        <div class="dash-revenue-item">
            <span class="dash-revenue-label"><?php echo __('admin_total_revenue_alltime'); ?></span>
            <span class="dash-revenue-val"><?php echo format_currency($stats['total_revenue']); ?></span>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
            <div class="metric-card metric-blue">
                <div class="metric-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="metric-info">
                    <div class="metric-value"><?php echo $stats['orders_today']; ?></div>
                    <div class="metric-label"><?php echo __('admin_new_orders_today'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="metric-card metric-orange metric-clickable" data-bs-toggle="collapse" data-bs-target="#rentalsToday" role="button" aria-expanded="false">
                <div class="metric-icon"><i class="fas fa-key"></i></div>
                <div class="metric-info">
                    <div class="metric-value"><?php echo $stats['rentals_today']; ?></div>
                    <div class="metric-label"><?php echo __('admin_rentals_starting_today'); ?> <i class="fas fa-chevron-down ms-1 metric-toggle-icon"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="metric-card metric-red metric-clickable" data-bs-toggle="collapse" data-bs-target="#returnsDue" role="button" aria-expanded="false">
                <div class="metric-icon"><i class="fas fa-undo"></i></div>
                <div class="metric-info">
                    <div class="metric-value"><?php echo $stats['returns_today'] + $stats['overdue_returns']; ?></div>
                    <div class="metric-label"><?php echo __('admin_returns_due'); ?> <i class="fas fa-chevron-down ms-1 metric-toggle-icon"></i></div>
                </div>
                <?php if ($stats['overdue_returns'] > 0): ?>
                <span class="metric-badge badge-danger"><?php echo $stats['overdue_returns']; ?> <?php echo __('admin_overdue'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="metric-card metric-purple metric-clickable" data-bs-toggle="collapse" data-bs-target="#activeRentals" role="button" aria-expanded="false">
                <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
                <div class="metric-info">
                    <div class="metric-value"><?php echo $stats['approved_orders']; ?></div>
                    <div class="metric-label"><?php echo __('admin_active_rentals'); ?> <i class="fas fa-chevron-down ms-1 metric-toggle-icon"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rentals Starting Today Detail -->
    <div class="collapse mb-4" id="rentalsToday">
        <div class="card border-start border-warning border-3">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0"><i class="fas fa-key text-warning me-2"></i><?php echo __('admin_rentals_starting_today'); ?> (<?php echo count($rentals_today_list); ?>)</h6>
            </div>
            <?php if (!empty($rentals_today_list)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo __('admin_order_id'); ?></th>
                            <th><?php echo __('admin_customer'); ?></th>
                            <th><?php echo __('admin_phone'); ?></th>
                            <th><?php echo __('admin_car'); ?></th>
                            <th><?php echo __('admin_plate'); ?></th>
                            <th><?php echo __('admin_rental_period'); ?></th>
                            <th><?php echo __('admin_amount'); ?></th>
                            <th><?php echo __('admin_status'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals_today_list as $rt): ?>
                        <tr>
                            <td><strong>#<?php echo $rt['id']; ?></strong></td>
                            <td><?php echo sanitize_output($rt['user_name']); ?></td>
                            <td><?php echo sanitize_output($rt['user_phone']); ?></td>
                            <td><?php echo sanitize_output($rt['brand_name'] . ' ' . $rt['car_name']); ?></td>
                            <td><code><?php echo sanitize_output($rt['plate_number'] ?? '-'); ?></code></td>
                            <td><small><?php echo format_date($rt['rental_start_date']) . ' — ' . format_date($rt['rental_end_date']); ?></small></td>
                            <td><strong><?php echo format_currency($rt['total_price']); ?></strong></td>
                            <td><?php echo get_status_badge($rt['status']); ?></td>
                            <td><a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $rt['id']; ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-4"><i class="fas fa-inbox me-1"></i><?php echo __('admin_no_rentals_today'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Returns Due Detail (reuses existing due_returns data) -->
    <div class="collapse mb-4" id="returnsDue">
        <div class="card border-start border-danger border-3">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="mb-0"><i class="fas fa-undo text-danger me-2"></i><?php echo __('admin_cars_due_for_return'); ?> (<?php echo count($due_returns); ?>)</h6>
            </div>
            <?php if (!empty($due_returns)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo __('admin_order_id'); ?></th>
                            <th><?php echo __('admin_customer'); ?></th>
                            <th><?php echo __('admin_phone'); ?></th>
                            <th><?php echo __('admin_car'); ?></th>
                            <th><?php echo __('admin_plate'); ?></th>
                            <th><?php echo __('admin_due_date'); ?></th>
                            <th><?php echo __('admin_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($due_returns as $dr): ?>
                        <tr class="<?php echo $dr['rental_end_date'] < $today ? 'table-danger' : ''; ?>">
                            <td><strong>#<?php echo $dr['id']; ?></strong></td>
                            <td><?php echo sanitize_output($dr['user_name']); ?></td>
                            <td><?php echo sanitize_output($dr['user_phone']); ?></td>
                            <td><?php echo sanitize_output($dr['brand_name'] . ' ' . $dr['car_name']); ?></td>
                            <td><code><?php echo sanitize_output($dr['plate_number']); ?></code></td>
                            <td>
                                <?php echo format_date($dr['rental_end_date']); ?>
                                <?php if ($dr['rental_end_date'] < $today): ?>
                                    <span class="badge bg-danger ms-1"><?php echo __('admin_overdue_badge'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark ms-1"><?php echo __('admin_today_badge'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/order-update.php?id=<?php echo $dr['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i><?php echo __('admin_complete'); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-4"><i class="fas fa-inbox me-1"></i><?php echo __('admin_no_returns_due'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Rentals Detail -->
    <div class="collapse mb-4" id="activeRentals">
        <div class="card border-start border-3" style="border-color: #8b5cf6 !important;">
            <div class="card-header" style="background: rgba(139,92,246,0.08);">
                <h6 class="mb-0"><i class="fas fa-car-side me-2" style="color:#8b5cf6"></i><?php echo __('admin_all_active_rentals'); ?> (<?php echo count($active_rentals_list); ?>)</h6>
            </div>
            <?php if (!empty($active_rentals_list)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th><?php echo __('admin_order_id'); ?></th>
                            <th><?php echo __('admin_customer'); ?></th>
                            <th><?php echo __('admin_phone'); ?></th>
                            <th><?php echo __('admin_car'); ?></th>
                            <th><?php echo __('admin_plate'); ?></th>
                            <th><?php echo __('admin_rental_period'); ?></th>
                            <th><?php echo __('admin_amount'); ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_rentals_list as $ar): ?>
                        <tr class="<?php echo $ar['rental_end_date'] < $today ? 'table-danger' : ($ar['rental_end_date'] == $today ? 'table-warning' : ''); ?>">
                            <td><strong>#<?php echo $ar['id']; ?></strong></td>
                            <td><?php echo sanitize_output($ar['user_name']); ?></td>
                            <td><?php echo sanitize_output($ar['user_phone']); ?></td>
                            <td><?php echo sanitize_output($ar['brand_name'] . ' ' . $ar['car_name']); ?></td>
                            <td><code><?php echo sanitize_output($ar['plate_number'] ?? '-'); ?></code></td>
                            <td>
                                <small><?php echo format_date($ar['rental_start_date']) . ' — ' . format_date($ar['rental_end_date']); ?></small>
                                <?php if ($ar['rental_end_date'] < $today): ?>
                                    <span class="badge bg-danger ms-1"><?php echo __('admin_overdue_badge'); ?></span>
                                <?php elseif ($ar['rental_end_date'] == $today): ?>
                                    <span class="badge bg-warning text-dark ms-1"><?php echo __('admin_today_badge'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo format_currency($ar['total_price']); ?></strong></td>
                            <td><a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $ar['id']; ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-4"><i class="fas fa-inbox me-1"></i><?php echo __('admin_no_active_rentals'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Returned Cars -->
    <div class="card mb-4 border-start border-3" style="border-color: #10b981 !important;">
        <div class="card-header d-flex justify-content-between align-items-center" style="background: rgba(16,185,129,0.08);">
            <h6 class="mb-0"><i class="fas fa-flag-checkered me-2" style="color:#10b981"></i><?php echo __('admin_returned_cars'); ?> (<?php echo count($returned_cars_list); ?>)</h6>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php?status=completed" class="btn btn-sm btn-outline-success"><?php echo __('admin_view_all'); ?></a>
        </div>
        <?php if (!empty($returned_cars_list)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo __('admin_order_id'); ?></th>
                        <th><?php echo __('admin_customer'); ?></th>
                        <th><?php echo __('admin_car'); ?></th>
                        <th><?php echo __('admin_plate'); ?></th>
                        <th><?php echo __('admin_rental_period'); ?></th>
                        <th><?php echo __('admin_amount'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($returned_cars_list as $rc): ?>
                    <tr>
                        <td><strong>#<?php echo $rc['id']; ?></strong></td>
                        <td><?php echo sanitize_output($rc['user_name']); ?></td>
                        <td><?php echo sanitize_output($rc['brand_name'] . ' ' . $rc['car_name']); ?></td>
                        <td><code><?php echo sanitize_output($rc['plate_number'] ?? '-'); ?></code></td>
                        <td><small><?php echo format_date($rc['rental_start_date']) . ' — ' . format_date($rc['rental_end_date']); ?></small></td>
                        <td><strong><?php echo format_currency($rc['total_price']); ?></strong></td>
                        <td><a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $rc['id']; ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="card-body text-center text-muted py-4"><i class="fas fa-inbox me-1"></i><?php echo __('admin_no_returned_cars'); ?></div>
        <?php endif; ?>
    </div>

    <!-- Fleet & Charts Row -->
    <div class="row g-3 mb-4">
        <!-- Fleet Overview -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-car me-2"></i><?php echo __('admin_fleet_overview'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="fleet-stats">
                        <div class="fleet-stat-item">
                            <div class="fleet-stat-icon bg-primary-soft"><i class="fas fa-car text-primary"></i></div>
                            <div class="fleet-stat-info">
                                <span class="fleet-stat-value"><?php echo $stats['total_cars']; ?></span>
                                <span class="fleet-stat-label"><?php echo __('admin_car_models'); ?></span>
                            </div>
                        </div>
                        <div class="fleet-stat-item">
                            <div class="fleet-stat-icon bg-success-soft"><i class="fas fa-check-circle text-success"></i></div>
                            <div class="fleet-stat-info">
                                <span class="fleet-stat-value"><?php echo $stats['available_stock']; ?> <small class="text-muted">/ <?php echo $stats['total_stock']; ?></small></span>
                                <span class="fleet-stat-label"><?php echo __('admin_stock_available'); ?></span>
                            </div>
                            <div class="fleet-progress">
                                <?php $avail_pct = $stats['total_stock'] > 0 ? round(($stats['available_stock'] / $stats['total_stock']) * 100) : 0; ?>
                                <div class="progress" style="height:6px;"><div class="progress-bar bg-success" style="width:<?php echo $avail_pct; ?>%"></div></div>
                            </div>
                        </div>
                        <div class="fleet-stat-item">
                            <div class="fleet-stat-icon bg-warning-soft"><i class="fas fa-clock text-warning"></i></div>
                            <div class="fleet-stat-info">
                                <span class="fleet-stat-value"><?php echo $stats['pending_orders']; ?></span>
                                <span class="fleet-stat-label"><?php echo __('admin_pending_orders'); ?></span>
                            </div>
                        </div>
                        <div class="fleet-stat-item">
                            <div class="fleet-stat-icon bg-info-soft"><i class="fas fa-users text-info"></i></div>
                            <div class="fleet-stat-info">
                                <span class="fleet-stat-value"><?php echo $stats['total_users']; ?></span>
                                <span class="fleet-stat-label"><?php echo __('admin_users'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('admin_revenue_7days'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="dash-chart-wrap" style="height: 300px; width: 100%; position: relative;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Status & Top Cars Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('admin_orders_by_status'); ?></h6>
                </div>
                <div class="card-body">
                    <?php 
                    $status_config = [
                        'pending' => ['color' => '#f59e0b', 'label' => 'Pending'],
                        'approved' => ['color' => '#10b981', 'label' => 'Approved'],
                        'completed' => ['color' => '#06b6d4', 'label' => 'Completed'],
                        'cancelled' => ['color' => '#ef4444', 'label' => 'Cancelled']
                    ];
                    ?>
                    <div class="dash-donut-wrap" style="height: 200px; position: relative;">
                        <canvas id="statusChart"></canvas>
                        <div class="donut-center-label">
                            <span class="donut-total"><?php echo array_sum($order_statuses); ?></span>
                            <span class="donut-total-label">Orders</span>
                        </div>
                    </div>
                    <ul class="dash-status-legend mt-4">
                        <?php 
                        $total_for_pct = max(array_sum($order_statuses), 1);
                        foreach ($status_config as $status => $cfg):
                            $count = $order_statuses[$status] ?? 0;
                            $pct = round(($count / $total_for_pct) * 100);
                        ?>
                        <li>
                            <div class="legend-dot" style="background:<?php echo $cfg['color']; ?>"></div>
                            <div class="legend-label"><?php echo $cfg['label']; ?></div>
                            <div class="legend-count"><?php echo $count; ?></div>
                            <div class="legend-pct"><?php echo $pct; ?>%</div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-trophy me-2"></i><?php echo __('admin_top_rented'); ?></h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th><?php echo __('admin_car'); ?></th>
                                <th class="text-end"><?php echo __('admin_total_revenue_alltime'); ?></th>
                                <th class="text-end pe-3"><?php echo __('admin_rentals'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($top_cars as $i => $tc): ?>
                        <tr>
                            <td class="ps-3"><span class="top-rank rank-<?php echo $i + 1; ?>"><?php echo $i + 1; ?></span></td>
                            <td><strong><?php echo sanitize_output($tc['brand_name'] . ' ' . $tc['name']); ?></strong></td>
                            <td class="text-end text-muted"><?php echo format_currency($tc['total_revenue']); ?></td>
                            <td class="text-end pe-3"><span class="badge bg-primary"><?php echo $tc['rental_count']; ?>x</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_cars)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3"><?php echo __('admin_no_rental_data'); ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Bar -->
    <div class="dash-quick-actions mb-4">
        <a href="<?php echo SITE_URL; ?>/admin/cars.php" class="quick-action-item">
            <i class="fas fa-boxes"></i><span><?php echo __('admin_manage_stock'); ?></span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="quick-action-item">
            <i class="fas fa-shopping-cart"></i><span><?php echo __('admin_all_orders'); ?></span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="quick-action-item">
            <i class="fas fa-users"></i><span><?php echo __('admin_manage_users'); ?></span>
        </a>
        <a href="<?php echo SITE_URL; ?>/admin/export-orders.php" class="quick-action-item">
            <i class="fas fa-file-export"></i><span><?php echo __('admin_export_csv'); ?></span>
        </a>
    </div>

    <!-- Recent Orders Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('admin_recent_orders'); ?></h6>
            <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-sm btn-outline-primary"><?php echo __('admin_view_all'); ?></a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo __('admin_order_id'); ?></th>
                        <th><?php echo __('admin_customer'); ?></th>
                        <th><?php echo __('admin_car'); ?></th>
                        <th><?php echo __('admin_plate'); ?></th>
                        <th><?php echo __('admin_rental_period'); ?></th>
                        <th><?php echo __('admin_amount'); ?></th>
                        <th><?php echo __('admin_payment'); ?></th>
                        <th><?php echo __('admin_status'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_orders)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-inbox me-1"></i><?php echo __('admin_no_orders_yet'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order['id']; ?></strong></td>
                            <td><?php echo sanitize_output($order['user_name']); ?></td>
                            <td><?php echo sanitize_output($order['car_name']); ?></td>
                            <td><code><?php echo sanitize_output($order['plate_number'] ?? '-'); ?></code></td>
                            <td><small><?php echo format_date($order['rental_start_date']) . ' — ' . format_date($order['rental_end_date']); ?></small></td>
                            <td><strong><?php echo format_currency($order['total_price']); ?></strong></td>
                            <td>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="badge bg-success"><?php echo __('admin_paid'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo __('admin_unpaid'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($order['status']); ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-light"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data for Revenue Chart
    const revenueLabels = <?php echo json_encode(array_column($revenue_7days, 'date')); ?>;
    const revenueData = <?php echo json_encode(array_column($revenue_7days, 'total')); ?>;

    // Chart.js default defaults for dark theme
    Chart.defaults.color = '#b8c4d0';
    Chart.defaults.font.family = "'DM Sans', sans-serif";

    // Revenue Chart (Line or Bar)
    const ctxRev = document.getElementById('revenueChart');
    if (ctxRev) {
        new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Revenue',
                    data: revenueData,
                    borderColor: '#c9a84c',
                    backgroundColor: 'rgba(201, 168, 76, 0.2)',
                    borderWidth: 3,
                    pointBackgroundColor: '#c9a84c',
                    pointBorderColor: '#16161f',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1c1c28',
                        titleColor: '#eef2f7',
                        bodyColor: '#b8c4d0',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                let value = context.parsed.y;
                                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: { color: '#7a8899' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: {
                            color: '#7a8899',
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000) + 'k';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Data for Status Chart
    const statusCounts = [
        <?php echo $order_statuses['pending'] ?? 0; ?>,
        <?php echo $order_statuses['approved'] ?? 0; ?>,
        <?php echo $order_statuses['completed'] ?? 0; ?>,
        <?php echo $order_statuses['cancelled'] ?? 0; ?>
    ];
    
    // Status Chart (Doughnut)
    const ctxStat = document.getElementById('statusChart');
    if (ctxStat) {
        new Chart(ctxStat, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Completed', 'Cancelled'],
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#f59e0b', '#10b981', '#06b6d4', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1c1c28',
                        titleColor: '#eef2f7',
                        bodyColor: '#b8c4d0',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        padding: 10
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>