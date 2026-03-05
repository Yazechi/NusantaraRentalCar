<?php
// Admin Order Detail Page
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_order_detail');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Get order ID from URL
$order_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if ($order_id <= 0) {
    set_flash_message('danger', 'Invalid order ID.');
    redirect(SITE_URL . '/admin/orders.php');
    exit;
}

// Get order detail
$order_query = "
    SELECT 
        o.id,
        o.status,
        o.order_type,
        o.rental_start_date,
        o.rental_end_date,
        o.duration_days,
        o.delivery_option,
        o.delivery_address,
        o.total_price,
        o.notes,
        o.created_at,
        o.updated_at,
        o.payment_status,
        o.payment_method,
        o.paid_at,
        o.rental_occasion,
        o.discount_type,
        o.discount_percent,
        o.original_price,
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        u.address as user_address,
        c.id as car_id,
        c.name as car_name,
        c.model as car_model,
        c.price_per_day,
        cs.plate_number,
        cs.status as stock_status
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    WHERE o.id = ?
";

$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    set_flash_message('danger', 'Order not found.');
    redirect(SITE_URL . '/admin/orders.php');
    exit;
}
?>

<div class="admin-content">
    <div class="content-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-file-invoice"></i> <?php echo __('admin_order_id'); ?> #<?php echo $order['id']; ?></h1>
            <p><?php echo __('admin_view_manage_order'); ?></p>
        </div>
        <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('admin_back_to_orders'); ?>
        </a>
    </div>

    <?php display_flash_message(); ?>

    <div class="row">
        <!-- Order Information -->
        <div class="col-md-8">
            <!-- Order Status -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('admin_order_status'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_current_status_label'); ?></label>
                            <p><?php echo get_status_badge($order['status']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_order_type'); ?></label>
                            <p><strong><?php echo ucfirst(sanitize_output($order['order_type'])); ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="form-label text-muted">Created</label>
                            <p><?php echo format_date($order['created_at']); ?></p>
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="form-label text-muted">Last Updated</label>
                            <p><?php echo format_date($order['updated_at']); ?></p>
                        </div>
                    </div>

                    <!-- Status Change Button -->
                    <?php if ($order['status'] !== 'completed' && $order['status'] !== 'cancelled'): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/order-update.php?id=<?php echo $order['id']; ?>"
                            class="btn btn-primary btn-sm mt-3">
                            <i class="fas fa-edit"></i> <?php echo __('admin_update_order'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Car Details -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-car"></i> <?php echo __('admin_car_details'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_car_name'); ?></label>
                            <p><strong><?php echo sanitize_output($order['car_name']); ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_model'); ?></label>
                            <p><?php echo sanitize_output($order['car_model']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_price_per_day'); ?></label>
                            <p><?php echo format_currency($order['price_per_day']); ?></p>
                        </div>
                        <?php if (!empty($order['plate_number'])): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_plate_number'); ?></label>
                            <p><strong class="text-primary"><?php echo sanitize_output($order['plate_number']); ?></strong>
                                <?php if ($order['stock_status'] === 'rented'): ?>
                                    <span class="badge bg-warning"><?php echo __('admin_status_rented'); ?></span>
                                <?php elseif ($order['stock_status'] === 'available'): ?>
                                    <span class="badge bg-success"><?php echo __('admin_status_available'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-credit-card"></i> <?php echo __('admin_payment_info'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_payment_status'); ?></label>
                            <p>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php elseif ($order['payment_status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unpaid</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_payment_method'); ?></label>
                            <p><?php echo !empty($order['payment_method']) ? sanitize_output(ucfirst(str_replace('_', ' ', $order['payment_method']))) : '-'; ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_paid_at'); ?></label>
                            <p><?php echo !empty($order['paid_at']) ? format_date($order['paid_at']) : '-'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental Period -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> <?php echo __('admin_rental_period_label'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_start_date'); ?></label>
                            <p><?php echo format_date($order['rental_start_date']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_end_date'); ?></label>
                            <p><?php echo format_date($order['rental_end_date']); ?></p>
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="form-label text-muted"><?php echo __('admin_duration'); ?></label>
                            <p><strong><?php echo $order['duration_days']; ?> <?php echo __('admin_days'); ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-truck"></i> <?php echo __('admin_delivery_info'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted"><?php echo __('admin_delivery_type'); ?></label>
                            <p><?php echo ucfirst(sanitize_output($order['delivery_option'])); ?></p>
                        </div>
                        <?php if (!empty($order['delivery_address'])): ?>
                            <div class="col-md-12">
                                <label class="form-label text-muted"><?php echo __('admin_delivery_address'); ?></label>
                                <p><?php echo sanitize_output($order['delivery_address']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($order['notes'])): ?>
                            <div class="col-md-12">
                                <label class="form-label text-muted"><?php echo __('admin_notes'); ?></label>
                                <p><?php echo sanitize_output($order['notes']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Information & Summary -->
        <div class="col-md-4">
            <!-- Customer Info -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-user"></i> <?php echo __('admin_customer_info'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small"><?php echo __('admin_name'); ?></label>
                        <p><?php echo sanitize_output($order['user_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small"><?php echo __('admin_email'); ?></label>
                        <p><a href="mailto:<?php echo sanitize_output($order['user_email']); ?>"><?php echo sanitize_output($order['user_email']); ?></a></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small"><?php echo __('admin_phone'); ?></label>
                        <p><?php echo sanitize_output($order['user_phone']); ?></p>
                    </div>
                    <div>
                        <label class="form-label text-muted small"><?php echo __('admin_address'); ?></label>
                        <p><?php echo sanitize_output($order['user_address']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> <?php echo __('admin_price_summary'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo __('admin_price_per_day'); ?>:</span>
                        <strong><?php echo format_currency($order['price_per_day']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo __('admin_duration'); ?>:</span>
                        <strong><?php echo $order['duration_days']; ?> <?php echo __('admin_days'); ?></strong>
                    </div>
                    <?php if (!empty($order['rental_occasion'])): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo __('rental_occasion'); ?>:</span>
                        <strong><?php echo __('occasion_' . $order['rental_occasion']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['discount_percent'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo __('original_price'); ?>:</span>
                        <span class="text-muted"><s><?php echo format_currency($order['original_price']); ?></s></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo __('discount_amount'); ?>:</span>
                        <span class="badge bg-success"><?php echo __('discount_' . $order['discount_type']); ?> -<?php echo (int)$order['discount_percent']; ?>%</span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="h5 mb-0"><?php echo __('admin_total'); ?>:</span>
                        <strong class="h5 mb-0"><?php echo format_currency($order['total_price']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>