<?php
// Admin Order Detail Page
$page_title = 'Order Detail';

$project_root = dirname(__DIR__);

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
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        u.address as user_address,
        c.id as car_id,
        c.name as car_name,
        c.model as car_model,
        c.price_per_day
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
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
            <h1><i class="fas fa-file-invoice"></i> Order #<?php echo $order['id']; ?></h1>
            <p>View and manage order details.</p>
        </div>
        <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>

    <?php display_flash_message(); ?>

    <div class="row">
        <!-- Order Information -->
        <div class="col-md-8">
            <!-- Order Status -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Current Status</label>
                            <p><?php echo get_status_badge($order['status']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Order Type</label>
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
                            <i class="fas fa-edit"></i> Update Status
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Car Details -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-car"></i> Car Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Car Name</label>
                            <p><strong><?php echo sanitize_output($order['car_name']); ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Model</label>
                            <p><?php echo sanitize_output($order['car_model']); ?></p>
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="form-label text-muted">Price Per Day</label>
                            <p><?php echo format_currency($order['price_per_day']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rental Period -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Rental Period</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Rental Start Date</label>
                            <p><?php echo format_date($order['rental_start_date']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">Rental End Date</label>
                            <p><?php echo format_date($order['rental_end_date']); ?></p>
                        </div>
                        <div class="col-md-6 mb-0">
                            <label class="form-label text-muted">Duration (Days)</label>
                            <p><strong><?php echo $order['duration_days']; ?> days</strong></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-truck"></i> Delivery Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">Delivery Option</label>
                            <p><?php echo ucfirst(sanitize_output($order['delivery_option'])); ?></p>
                        </div>
                        <?php if (!empty($order['delivery_address'])): ?>
                            <div class="col-md-12">
                                <label class="form-label text-muted">Delivery Address</label>
                                <p><?php echo sanitize_output($order['delivery_address']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($order['notes'])): ?>
                            <div class="col-md-12">
                                <label class="form-label text-muted">Notes</label>
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
                    <h5 class="mb-0"><i class="fas fa-user"></i> Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Name</label>
                        <p><?php echo sanitize_output($order['user_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Email</label>
                        <p><a href="mailto:<?php echo sanitize_output($order['user_email']); ?>"><?php echo sanitize_output($order['user_email']); ?></a></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Phone</label>
                        <p><?php echo sanitize_output($order['user_phone']); ?></p>
                    </div>
                    <div>
                        <label class="form-label text-muted small">Address</label>
                        <p><?php echo sanitize_output($order['user_address']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Price Summary -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Price Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Price per day:</span>
                        <strong><?php echo format_currency($order['price_per_day']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Duration:</span>
                        <strong><?php echo $order['duration_days']; ?> days</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="h5 mb-0">Total:</span>
                        <strong class="h5 mb-0"><?php echo format_currency($order['total_price']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>