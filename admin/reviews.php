<?php
// Admin - Car Reviews Management
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = 'Car Reviews';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Fetch all reviews with car and user info
$reviews = $conn->query("
    SELECT cr.id, cr.rating, cr.comment, cr.created_at,
        u.name as user_name, u.email as user_email,
        c.name as car_name, cb.name as brand_name, o.id as order_id
    FROM car_reviews cr
    JOIN users u ON cr.user_id = u.id
    JOIN cars c ON cr.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    JOIN orders o ON cr.order_id = o.id
    ORDER BY cr.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Calculate stats
$avg_rating = 0;
$rating_dist = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
foreach ($reviews as $r) {
    $avg_rating += $r['rating'];
    $rating_dist[$r['rating']]++;
}
$total_reviews = count($reviews);
if ($total_reviews > 0) $avg_rating = round($avg_rating / $total_reviews, 1);
?>

<div class="admin-content">
    <div class="container-fluid">
        <h3 class="mb-4"><i class="fas fa-star text-warning me-2"></i> <?php echo __('admin_car_reviews'); ?></h3>

        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm text-center p-4">
                    <div class="display-4 fw-bold text-warning"><?php echo $avg_rating; ?></div>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= round($avg_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="text-muted"><?php echo $total_reviews; ?> <?php echo __('admin_total_reviews'); ?></div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <h6 class="mb-3"><?php echo __('admin_rating_distribution'); ?></h6>
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <?php $pct = $total_reviews > 0 ? round(($rating_dist[$star] / $total_reviews) * 100) : 0; ?>
                        <div class="d-flex align-items-center mb-1">
                            <span class="me-2" style="width:60px;"><?php echo $star; ?> <i class="fas fa-star text-warning small"></i></span>
                            <div class="progress flex-grow-1" style="height: 10px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <span class="ms-2 text-muted small" style="width:40px;"><?php echo $rating_dist[$star]; ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Reviews Table -->
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo __('admin_date'); ?></th>
                            <th><?php echo __('admin_customer'); ?></th>
                            <th><?php echo __('admin_car'); ?></th>
                            <th><?php echo __('admin_rating'); ?></th>
                            <th><?php echo __('admin_comment'); ?></th>
                            <th><?php echo __('admin_order'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td><small><?php echo format_date($rev['created_at']); ?></small></td>
                            <td>
                                <strong><?php echo sanitize_output($rev['user_name']); ?></strong><br>
                                <small class="text-muted"><?php echo sanitize_output($rev['user_email']); ?></small>
                            </td>
                            <td><?php echo sanitize_output($rev['brand_name'] . ' ' . $rev['car_name']); ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $rev['rating'] ? 'text-warning' : 'text-muted'; ?> small"></i>
                                <?php endfor; ?>
                            </td>
                            <td><small><?php echo nl2br(sanitize_output($rev['comment'])); ?></small></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $rev['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    #<?php echo $rev['order_id']; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reviews)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-inbox me-1"></i> <?php echo __('admin_no_reviews_yet'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
