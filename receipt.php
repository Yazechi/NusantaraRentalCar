<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('receipt_title');
require_once __DIR__ . '/includes/header.php';

require_login();

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$order_id) {
    set_flash_message('danger', 'Invalid order.');
    redirect(SITE_URL . '/my-orders.php');
}

// Get order with car details
$stmt = $conn->prepare("SELECT o.*, c.name AS car_name, cb.name AS brand_name, c.image_main,
        c.transmission, c.seats, c.fuel_type, c.color, ct.name AS type_name, 
        u.name AS user_name, u.email AS user_email, cs.plate_number
        FROM orders o 
        JOIN cars c ON o.car_id = c.id 
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        JOIN users u ON o.user_id = u.id
        LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    set_flash_message('danger', 'Order not found.');
    redirect(SITE_URL . '/my-orders.php');
}

$show_success = isset($_GET['success']) && $_GET['success'] == '1';

// Payment status display
$payment_badges = [
    'paid' => '<span class="badge bg-success fs-6"><i class="fas fa-check-circle"></i> ' . __('paid') . '</span>',
    'pending' => '<span class="badge bg-warning fs-6"><i class="fas fa-clock"></i> ' . __('payment_pending') . '</span>',
    'unpaid' => '<span class="badge bg-secondary fs-6"><i class="fas fa-times-circle"></i> ' . __('unpaid') . '</span>',
    'failed' => '<span class="badge bg-danger fs-6"><i class="fas fa-exclamation-circle"></i> ' . __('payment_failed') . '</span>',
];
$payment_badge = $payment_badges[$order['payment_status']] ?? $payment_badges['unpaid'];
?>

<?php if ($show_success): ?>
<div class="alert alert-success text-center">
    <h4><i class="fas fa-check-circle"></i> <?php echo __('payment_success'); ?></h4>
    <p class="mb-0"><?php echo __('receipt_note'); ?></p>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm" id="receipt-content">
            <div class="card-body p-4">
                <!-- Receipt Header -->
                <div class="text-center mb-4 border-bottom pb-3">
                    <h2 class="text-primary"><img src="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png" alt="METREV" style="height:40px;width:40px;object-fit:contain;border-radius:50%;margin-right:8px;"><span class="brand-text"><?php echo SITE_NAME; ?></span></h2>
                    <h4><?php echo __('receipt_title'); ?></h4>
                    <p class="text-muted mb-0"><?php echo sanitize_output(get_site_setting('site_address') ?? 'Jakarta, Indonesia'); ?></p>
                </div>

                <!-- Order Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="fw-bold"><?php echo __('order_id'); ?>:</td>
                                <td>#<?php echo (int)$order['id']; ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold"><?php echo __('order_date'); ?>:</td>
                                <td><?php echo format_date($order['created_at']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold"><?php echo __('order_status'); ?>:</td>
                                <td><?php echo get_status_badge($order['status']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr>
                                <td class="fw-bold"><?php echo __('name'); ?>:</td>
                                <td><?php echo sanitize_output($order['user_name']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold"><?php echo __('email'); ?>:</td>
                                <td><?php echo sanitize_output($order['user_email']); ?></td>
                            </tr>
                            <tr>
                                <td class="fw-bold"><?php echo __('payment_status'); ?>:</td>
                                <td><?php echo $payment_badge; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Car Details -->
                <div class="card mb-4 border">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <?php if (!empty($order['image_main'])): ?>
                                    <img src="<?php echo UPLOAD_URL . sanitize_output($order['image_main']); ?>" class="img-fluid rounded" alt="Car">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center rounded" style="height: 100px;">
                                        <i class="fas fa-car fa-2x text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-9">
                                <h5 class="mb-1"><?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?></h5>
                                <small class="text-muted">
                                    <?php echo ucfirst(sanitize_output($order['transmission'])); ?> |
                                    <?php echo (int)$order['seats']; ?> <?php echo __('seats'); ?> |
                                    <?php echo format_fuel_type($order['fuel_type']); ?>
                                    <?php if (!empty($order['color'])): ?> | <?php echo sanitize_output($order['color']); ?><?php endif; ?>
                                    <?php if (!empty($order['type_name'])): ?> | <?php echo sanitize_output($order['type_name']); ?><?php endif; ?>
                                    <?php if (!empty($order['plate_number'])): ?> | <strong><?php echo sanitize_output($order['plate_number']); ?></strong><?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rental Details Table -->
                <table class="table table-bordered mb-4">
                    <tbody>
                        <tr>
                            <td class="fw-bold" width="40%"><?php echo __('rental_period'); ?></td>
                            <td><?php echo format_date($order['rental_start_date']); ?> — <?php echo format_date($order['rental_end_date']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold"><?php echo __('duration'); ?></td>
                            <td><?php echo (int)$order['duration_days']; ?> <?php echo __('days'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold"><?php echo __('delivery'); ?></td>
                            <td>
                                <?php echo $order['delivery_option'] === 'pickup' ? __('pickup_showroom') : __('deliver_address'); ?>
                                <?php if (!empty($order['delivery_address'])): ?>
                                    <br><small class="text-muted"><?php echo sanitize_output($order['delivery_address']); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($order['payment_method'])): ?>
                        <tr>
                            <td class="fw-bold"><?php echo __('payment_method'); ?></td>
                            <td><?php echo ucfirst(sanitize_output(str_replace('_', ' ', $order['payment_method']))); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($order['paid_at'])): ?>
                        <tr>
                            <td class="fw-bold"><?php echo __('paid') . ' ' . __('order_date'); ?></td>
                            <td><?php echo format_date($order['paid_at']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($order['notes'])): ?>
                        <tr>
                            <td class="fw-bold"><?php echo __('notes'); ?></td>
                            <td><?php echo sanitize_output($order['notes']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($order['rental_occasion'])): ?>
                        <tr>
                            <td class="fw-bold"><?php echo __('rental_occasion'); ?></td>
                            <td><?php echo __('occasion_' . $order['rental_occasion']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <?php if ($order['discount_percent'] > 0): ?>
                        <tr>
                            <td class="fw-bold"><?php echo __('original_price'); ?></td>
                            <td><?php echo format_currency($order['original_price']); ?></td>
                        </tr>
                        <tr class="table-success">
                            <td class="fw-bold"><?php echo __('discount_amount'); ?></td>
                            <td><span class="badge bg-success"><?php echo __('discount_' . $order['discount_type']); ?> -<?php echo (int)$order['discount_percent']; ?>%</span></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-primary">
                            <td class="fw-bold fs-5"><?php echo __('total_price'); ?></td>
                            <td class="fw-bold fs-5 text-primary"><?php echo format_currency($order['total_price']); ?></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Footer Note -->
                <div class="text-center text-muted border-top pt-3">
                    <small><?php echo __('receipt_note'); ?></small>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-center gap-3 mt-4">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-1"></i> <?php echo __('print_receipt'); ?>
            </button>
            <?php if ($order['payment_status'] !== 'paid'): ?>
            <a href="<?php echo SITE_URL; ?>/payment.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-success">
                <i class="fas fa-credit-card me-1"></i> <?php echo __('pay_now'); ?>
            </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/my-orders.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> <?php echo __('back_to_orders'); ?>
            </a>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, footer, #chat-widget, .btn, .alert { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    main.container { padding: 0 !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
