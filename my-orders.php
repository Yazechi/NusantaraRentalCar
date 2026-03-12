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

<div class="page-header text-center mb-5">
    <div class="section-eyebrow mb-2">History & Management</div>
    <h1 class="mb-0 fw-bold"><i class="fas fa-history text-gold me-2"></i> <?php echo __('my_orders'); ?></h1>
</div>

<?php if (empty($orders)): ?>
    <div class="card luxury-dark-glass">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <i class="fas fa-shopping-cart fa-4x text-gold opacity-50"></i>
            </div>
            <h4 class="fw-bold mb-3"><?php echo __('no_orders'); ?></h4>
            <p class="text-muted mb-4 mx-auto" style="max-width: 500px;"><?php echo __('no_orders_desc'); ?></p>
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary btn-lg px-5 shadow-gold">
                <i class="fas fa-car me-2"></i> <?php echo __('browse_cars'); ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-5">
        <?php foreach ($orders as $order): 
            $ps = $order['payment_status'] ?? 'unpaid';
            $ps_badges = [
                'paid' => '<span class="badge bg-success-subtle border border-success text-success"><i class="fas fa-check-circle me-1"></i> ' . __('paid') . '</span>',
                'pending' => '<span class="badge bg-warning-subtle border border-warning text-warning"><i class="fas fa-clock me-1"></i> ' . __('pending') . '</span>',
                'unpaid' => '<span class="badge bg-secondary-subtle border border-secondary text-secondary"><i class="fas fa-times-circle me-1"></i> ' . __('unpaid') . '</span>',
                'failed' => '<span class="badge bg-danger-subtle border border-danger text-danger"><i class="fas fa-exclamation-circle me-1"></i> ' . __('payment_failed') . '</span>',
            ];
        ?>
        <div class="col-12">
            <div class="card luxury-dark-glass overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Left Info -->
                        <div class="col-lg-8 p-4 p-md-5">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                                <div>
                                    <h4 class="fw-bold mb-1" style="font-family: 'Syne', sans-serif;">
                                        <?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?>
                                    </h4>
                                    <?php if (!empty($order['plate_number'])): ?>
                                        <div class="badge border border-gold text-gold bg-dark-car mt-2">
                                            <i class="fas fa-hashtag me-1"></i> <?php echo sanitize_output($order['plate_number']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end mt-3 mt-md-0">
                                    <div class="small text-muted text-uppercase letter-spacing-1 mb-1">Booking Status</div>
                                    <?php echo get_status_badge($order['status']); ?>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-dark-car rounded-circle d-flex align-items-center justify-content-center border-gold border" style="width: 48px; height: 48px;">
                                            <i class="fas fa-calendar-alt text-gold"></i>
                                        </div>
                                        <div>
                                            <div class="small text-muted text-uppercase letter-spacing-1">Rental Period</div>
                                            <div class="fw-bold"><?php echo format_date($order['rental_start_date']); ?></div>
                                            <div class="small opacity-75">to <?php echo format_date($order['rental_end_date']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-dark-car rounded-circle d-flex align-items-center justify-content-center border-gold border" style="width: 48px; height: 48px;">
                                            <i class="fas fa-hourglass-half text-gold"></i>
                                        </div>
                                        <div>
                                            <div class="small text-muted text-uppercase letter-spacing-1">Duration</div>
                                            <div class="fw-bold"><?php echo (int)$order['duration_days']; ?> <?php echo __('days'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-4 bg-dark-car bg-opacity-50 p-3 rounded-md border border-secondary border-opacity-25">
                                <div>
                                    <div class="small text-muted text-uppercase letter-spacing-1 mb-1"><?php echo __('total_price'); ?></div>
                                    <div class="fw-bold fs-4 text-gold" style="font-family: 'Syne', sans-serif;">
                                        <?php echo format_currency($order['total_price']); ?>
                                    </div>
                                </div>
                                <div class="border-start border-secondary border-opacity-50 ps-4">
                                    <div class="small text-muted text-uppercase letter-spacing-1 mb-2"><?php echo __('payment_status'); ?></div>
                                    <?php echo $ps_badges[$ps] ?? $ps_badges['unpaid']; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Actions -->
                        <div class="col-lg-4 bg-light p-4 p-md-5 d-flex flex-column justify-content-center border-start border-secondary border-opacity-10">
                            <div class="d-grid gap-3">
                                <a href="<?php echo SITE_URL; ?>/receipt.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-outline-dark fw-bold btn-lg">
                                    <i class="fas fa-receipt me-2"></i> <?php echo __('view_details'); ?>
                                </a>
                                
                                <?php if (($order['payment_status'] ?? 'unpaid') !== 'paid' && $order['status'] !== 'cancelled'): ?>
                                <a href="<?php echo SITE_URL; ?>/payment.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-success fw-bold btn-lg shadow-sm">
                                    <i class="fas fa-credit-card me-2"></i> <?php echo __('pay_now'); ?>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'approved'): ?>
                                <button type="button" class="btn btn-danger fw-bold btn-lg position-relative overflow-hidden shadow-sm" onclick="openSOSModal(<?php echo (int)$order['id']; ?>, '<?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?>')">
                                    <span class="position-absolute top-0 start-0 w-100 h-100 bg-white opacity-25 animate-pulse rounded"></span>
                                    <i class="fas fa-ambulance me-2"></i> EMERGENCY SOS
                                </button>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php if ($order['review_id']): ?>
                                    <button class="btn btn-secondary fw-bold btn-lg opacity-75" disabled>
                                        <i class="fas fa-check-circle me-2 text-white"></i> <?php echo __('already_reviewed'); ?>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>/review.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-warning fw-bold btn-lg text-dark shadow-sm" style="background-color: #f59e0b; border-color: #f59e0b;">
                                        <i class="fas fa-star me-2"></i> <?php echo __('rate_and_review'); ?>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-5 pt-3">
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary btn-lg px-5 py-3 shadow-gold" style="border-radius: 30px;">
            <i class="fas fa-plus-circle me-2"></i> <?php echo __('rent_another'); ?>
        </a>
    </div>
<?php endif; ?>

<!-- SOS Modal (Updated for Dark Theme Compatibility) -->
<div class="modal fade" id="sosModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content luxury-dark-glass">
            <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
                <h5 class="modal-title text-danger fw-bold"><i class="fas fa-ambulance me-2"></i> <?php echo __('emergency_sos_help'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/api/emergency.php" method="POST">
                <div class="modal-body py-4">
                    <input type="hidden" name="order_id" id="sos_order_id">
                    <div class="alert bg-danger bg-opacity-10 border-danger border-opacity-25 text-white mb-4">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                        <?php echo __('requesting_assistance_for'); ?> <strong id="sos_car_name" class="text-gold"></strong>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted text-uppercase small letter-spacing-1"><?php echo __('current_location_details'); ?></label>
                        <textarea name="location_details" class="form-control bg-dark-car border-secondary text-white" rows="3" placeholder="<?php echo __('location_placeholder'); ?>" required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted text-uppercase small letter-spacing-1"><?php echo __('problem_description'); ?></label>
                        <textarea name="message" class="form-control bg-dark-car border-secondary text-white" rows="4" placeholder="<?php echo __('problem_placeholder'); ?>" required></textarea>
                    </div>
                    <div class="d-flex align-items-start gap-2 small text-muted">
                        <i class="fas fa-info-circle text-gold mt-1"></i> 
                        <span><?php echo __('sos_admin_note'); ?></span>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25 pt-3">
                    <button type="button" class="btn btn-outline-light-car px-4" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-danger px-5 shadow"><i class="fas fa-paper-plane me-2"></i> <?php echo __('send_sos_request'); ?></button>
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
