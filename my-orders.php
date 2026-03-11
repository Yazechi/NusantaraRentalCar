<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('my_orders');
require_once __DIR__ . '/includes/header.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get user's orders using prepared statement
$stmt = $conn->prepare("SELECT orders.*, cars.name AS car_name, cars.price_per_day, cb.name AS brand_name, cs.plate_number,
        (SELECT id FROM car_reviews WHERE order_id = orders.id LIMIT 1) as review_id
        FROM orders 
        JOIN cars ON orders.car_id = cars.id 
        JOIN car_brands cb ON cars.brand_id = cb.id
        LEFT JOIN car_stock cs ON orders.car_stock_id = cs.id
        WHERE orders.user_id = ? 
        ORDER BY orders.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-history"></i> <?php echo __('my_orders'); ?></h1>
</div>

<?php if (empty($orders)): ?>
    <div class="card card-refined">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3 opacity-50"></i>
            <h5 class="text-muted fw-bold"><?php echo __('no_orders'); ?></h5>
            <p class="text-muted"><?php echo __('no_orders_desc'); ?></p>
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary px-4 mt-2">
                <i class="fas fa-car me-2"></i> <?php echo __('browse_cars'); ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($orders as $order): 
            $ps = $order['payment_status'] ?? 'unpaid';
            $ps_badges = [
                'paid' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> ' . __('paid') . '</span>',
                'pending' => '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i> ' . __('pending') . '</span>',
                'unpaid' => '<span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i> ' . __('unpaid') . '</span>',
                'failed' => '<span class="badge bg-danger"><i class="fas fa-exclamation-circle me-1"></i> ' . __('payment_failed') . '</span>',
            ];
        ?>
        <div class="col-12">
            <div class="card card-refined border-0">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Left Info -->
                        <div class="col-md-8 p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <h5 class="fw-bold mb-0 text-dark">
                                        <?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?>
                                    </h5>
                                    <?php if (!empty($order['plate_number'])): ?>
                                        <small class="text-muted text-uppercase letter-spacing-1"><?php echo sanitize_output($order['plate_number']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end d-none d-md-block">
                                    <?php echo get_status_badge($order['status']); ?>
                                </div>
                            </div>

                            <div class="row text-muted small mb-3">
                                <div class="col-sm-6 mb-2">
                                    <i class="fas fa-calendar-alt me-2 text-primary"></i> 
                                    <?php echo format_date($order['rental_start_date']); ?> - <?php echo format_date($order['rental_end_date']); ?>
                                </div>
                                <div class="col-sm-6 mb-2">
                                    <i class="fas fa-hourglass-half me-2 text-primary"></i> 
                                    <?php echo (int)$order['duration_days']; ?> <?php echo __('days'); ?>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light rounded px-3 py-2">
                                    <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.65rem;"><?php echo __('total_price'); ?></small>
                                    <span class="fw-bold text-dark fs-5"><?php echo format_currency($order['total_price']); ?></span>
                                </div>
                                <div class="ps-2 border-start">
                                    <small class="text-muted d-block text-uppercase fw-bold mb-1" style="font-size: 0.65rem;"><?php echo __('payment_status'); ?></small>
                                    <?php echo $ps_badges[$ps] ?? $ps_badges['unpaid']; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="col-md-4 bg-light border-start p-4 d-flex flex-column justify-content-center">
                            <div class="d-grid gap-2">
                                <a href="<?php echo SITE_URL; ?>/receipt.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-outline-dark border-2 fw-bold">
                                    <i class="fas fa-receipt me-2"></i> <?php echo __('view_details'); ?>
                                </a>
                                
                                <?php if (($order['payment_status'] ?? 'unpaid') !== 'paid' && $order['status'] !== 'cancelled'): ?>
                                <a href="<?php echo SITE_URL; ?>/payment.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card me-2"></i> <?php echo __('pay_now'); ?>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'approved'): ?>
                                <button type="button" class="btn btn-danger" onclick="openSOSModal(<?php echo (int)$order['id']; ?>, '<?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?>')">
                                    <i class="fas fa-ambulance me-2"></i> EMERGENCY SOS
                                </button>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php if ($order['review_id']): ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-check-circle me-2"></i> <?php echo __('already_reviewed'); ?>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>/review.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-warning fw-bold">
                                        <i class="fas fa-star me-2"></i> <?php echo __('rate_and_review'); ?>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3 d-md-none">
                                <?php echo get_status_badge($order['status']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-5">
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary px-5 py-3 rounded-pill shadow-sm">
            <i class="fas fa-plus-circle me-2"></i> <?php echo __('rent_another'); ?>
        </a>
    </div>
<?php endif; ?>

<!-- SOS Modal -->
<div class="modal fade" id="sosModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-ambulance me-2"></i> <?php echo __('emergency_sos_help'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/api/emergency.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="sos_order_id">
                    <p><?php echo __('requesting_assistance_for'); ?> <strong id="sos_car_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('current_location_details'); ?></label>
                        <textarea name="location_details" class="form-control" placeholder="<?php echo __('location_placeholder'); ?>" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('problem_description'); ?></label>
                        <textarea name="message" class="form-control" placeholder="<?php echo __('problem_placeholder'); ?>" required></textarea>
                    </div>
                    <div class="alert alert-warning small">
                        <i class="fas fa-info-circle me-1"></i> <?php echo __('sos_admin_note'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-danger px-4"><?php echo __('send_sos_request'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSOSModal(orderId, carName) {
    document.getElementById('sos_order_id').value = orderId;
    document.getElementById('sos_car_name').innerText = carName;
    new bootstrap.Modal(document.getElementById('sosModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>