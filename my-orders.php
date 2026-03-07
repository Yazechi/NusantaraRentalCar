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

<h3 class="mb-4"><i class="fas fa-clipboard-list"></i> <?php echo __('my_orders'); ?></h3>

<?php if (empty($orders)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h5 class="text-muted"><?php echo __('no_orders'); ?></h5>
            <p class="text-muted"><?php echo __('no_orders_desc'); ?></p>
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary">
                <i class="fas fa-car"></i> <?php echo __('browse_cars'); ?>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('car'); ?></th>
                        <th><?php echo __('rental_period'); ?></th>
                        <th><?php echo __('duration'); ?></th>
                        <th><?php echo __('total_price'); ?></th>
                        <th><?php echo __('status'); ?></th>
                        <th><?php echo __('payment_status'); ?></th>
                        <th><?php echo __('action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?></strong>
                            <?php if (!empty($order['plate_number'])): ?>
                                <br><small class="text-muted"><i class="fas fa-id-card"></i> <?php echo sanitize_output($order['plate_number']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo format_date($order['rental_start_date']); ?> - 
                            <?php echo format_date($order['rental_end_date']); ?>
                        </td>
                        <td><?php echo (int)$order['duration_days']; ?> <?php echo __('days'); ?></td>
                        <td>
                            <strong><?php echo format_currency($order['total_price']); ?></strong>
                            <?php if ($order['discount_percent'] > 0): ?>
                                <br><span class="badge bg-success" style="font-size:0.7em;"><i class="fas fa-tag"></i> -<?php echo (int)$order['discount_percent']; ?>%</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo get_status_badge($order['status']); ?></td>
                        <td>
                            <?php
                            $ps = $order['payment_status'] ?? 'unpaid';
                            $ps_badges = [
                                'paid' => '<span class="badge bg-success"><i class="fas fa-check"></i> ' . __('paid') . '</span>',
                                'pending' => '<span class="badge bg-warning"><i class="fas fa-clock"></i> ' . __('pending') . '</span>',
                                'unpaid' => '<span class="badge bg-secondary"><i class="fas fa-times"></i> ' . __('unpaid') . '</span>',
                                'failed' => '<span class="badge bg-danger"><i class="fas fa-exclamation"></i> ' . __('payment_failed') . '</span>',
                            ];
                            echo $ps_badges[$ps] ?? $ps_badges['unpaid'];
                            ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo SITE_URL; ?>/receipt.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-outline-primary" title="<?php echo __('view_receipt'); ?>">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <?php if (($order['payment_status'] ?? 'unpaid') !== 'paid' && $order['status'] !== 'cancelled'): ?>
                                <a href="<?php echo SITE_URL; ?>/payment.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-outline-success" title="<?php echo __('pay_now'); ?>">
                                    <i class="fas fa-credit-card"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] === 'approved'): ?>
                                <button type="button" class="btn btn-danger" onclick="openSOSModal(<?php echo (int)$order['id']; ?>, '<?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?>')" title="<?php echo __('emergency_sos'); ?>">
                                    <i class="fas fa-ambulance"></i> SOS
                                </button>
                                <?php endif; ?>

                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php if ($order['review_id']): ?>
                                    <button class="btn btn-secondary" title="<?php echo __('already_reviewed'); ?>" disabled>
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <?php else: ?>
                                    <a href="<?php echo SITE_URL; ?>/review.php?order_id=<?php echo (int)$order['id']; ?>" class="btn btn-warning" title="<?php echo __('rate_and_review'); ?>">
                                        <i class="fas fa-star"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary">
            <i class="fas fa-car"></i> <?php echo __('rent_another'); ?>
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