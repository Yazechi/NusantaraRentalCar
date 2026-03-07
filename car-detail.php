<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('car_details');
require_once __DIR__ . '/includes/header.php';

$car_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$car_id) {
    set_flash_message('danger', 'Invalid car ID.');
    redirect(SITE_URL . '/cars.php');
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock,
        (SELECT AVG(rating) FROM car_reviews cr WHERE cr.car_id = c.id) as avg_rating,
        (SELECT COUNT(*) FROM car_reviews cr WHERE cr.car_id = c.id) as review_count
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        WHERE c.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    set_flash_message('danger', 'Car not found.');
    redirect(SITE_URL . '/cars.php');
}

// Get rental goals for this car
$rental_goals = [];
$stmt = $conn->prepare("SELECT rg.* FROM rental_goals rg 
        JOIN car_rental_goals crg ON rg.id = crg.rental_goal_id 
        WHERE crg.car_id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$rental_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get reviews
$stmt = $conn->prepare("SELECT cr.*, u.name as user_name FROM car_reviews cr JOIN users u ON cr.user_id = u.id WHERE cr.car_id = ? ORDER BY cr.created_at DESC");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$available_stock = (int)$car['available_stock'];
?>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <?php if (!empty($car['image_main'])): ?>
                <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" class="card-img-top rounded" alt="<?php echo sanitize_output($car['name']); ?>">
            <?php else: ?>
                <div class="bg-secondary d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                    <i class="fas fa-car fa-5x text-white"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviews Section -->
        <div class="mt-4">
            <h4 class="mb-3"><i class="fas fa-star text-warning me-2"></i><?php echo __('customer_reviews'); ?> (<?php echo (int)$car['review_count']; ?>)</h4>
            <?php if (empty($reviews)): ?>
                <div class="alert alert-light border text-muted">
                    <?php echo __('no_reviews_yet'); ?>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <strong class="text-primary"><?php echo sanitize_output($rev['user_name']); ?></strong>
                            <div class="text-warning small">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="<?php echo $i <= $rev['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="mb-1 fst-italic">"<?php echo nl2br(sanitize_output($rev['comment'])); ?>"</p>
                        <small class="text-muted"><?php echo format_date($rev['created_at']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h2 class="fw-bold mb-0"><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h2>
                    <p class="text-muted"><?php echo sanitize_output($car['model']); ?> <?php echo !empty($car['year']) ? '(' . (int)$car['year'] . ')' : ''; ?></p>
                </div>
                <?php if ($car['review_count'] > 0): ?>
                <div class="text-center bg-warning text-white p-2 rounded shadow-sm">
                    <div class="fw-bold fs-4"><?php echo number_format($car['avg_rating'], 1); ?></div>
                    <div class="small">/ 5.0</div>
                </div>
                <?php endif; ?>
            </div>
            
            <?php 
            $discount = isset($car['discount_percent']) ? (int)$car['discount_percent'] : 0;
            $discounted_price = $discount > 0 ? $car['price_per_day'] * (1 - $discount / 100) : $car['price_per_day'];
            ?>
            
            <div class="mb-4">
                <?php if ($discount > 0): ?>
                    <span class="badge bg-danger fs-6 me-2 mb-2"><i class="fas fa-bolt me-1"></i><?php echo $discount; ?>% OFF</span>
                    <div>
                        <span class="price-original fs-5 text-decoration-line-through text-muted"><?php echo format_currency($car['price_per_day']); ?></span>
                        <span class="price-discounted fs-2 text-primary fw-bold"><?php echo format_currency($discounted_price); ?></span>
                        <span class="text-muted">/ day</span>
                    </div>
                <?php else: ?>
                    <span class="price-normal fs-2 text-primary fw-bold"><?php echo format_currency($car['price_per_day']); ?></span>
                    <span class="text-muted">/ day</span>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <?php if ($available_stock > 0): ?>
                    <span class="badge bg-success py-2 px-3 shadow-sm"><i class="fas fa-check-circle me-1"></i> <?php echo __('in_stock'); ?> (<?php echo $available_stock; ?> <?php echo __('units_available'); ?>)</span>
                <?php else: ?>
                    <span class="badge bg-danger py-2 px-3 shadow-sm"><i class="fas fa-times-circle me-1"></i> <?php echo __('out_of_stock'); ?></span>
                <?php endif; ?>
            </div>

            <!-- Features -->
            <div class="bg-light p-3 rounded mb-4">
                <div class="row text-center g-2">
                    <div class="col-4 border-end">
                        <i class="fas fa-users text-muted mb-1"></i>
                        <div class="small fw-bold"><?php echo (int)$car['seats']; ?> Seats</div>
                    </div>
                    <div class="col-4 border-end">
                        <i class="fas fa-cog text-muted mb-1"></i>
                        <div class="small fw-bold"><?php echo ucfirst(sanitize_output($car['transmission'])); ?></div>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-gas-pump text-muted mb-1"></i>
                        <div class="small fw-bold">Pertamax</div>
                    </div>
                </div>
            </div>

            <!-- Added Services Showcase -->
            <div class="card border-primary mb-4 bg-light border-opacity-25">
                <div class="card-body p-3 small">
                    <h6 class="fw-bold mb-2"><i class="fas fa-plus-circle me-1 text-primary"></i> <?php echo __('available_addons'); ?></h6>
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-user-tie me-1 text-muted"></i> <?php echo __('professional_driver'); ?></span>
                        <span class="text-primary fw-bold">+ Rp 150.000 /<?php echo trim(__('per_day'), '/ '); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="fas fa-tools me-1 text-muted"></i> <?php echo __('maintenance_toolkit'); ?></span>
                        <span class="text-primary fw-bold">+ Rp 50.000</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="fas fa-ambulance me-1 text-muted"></i> <?php echo __('sos_support'); ?></span>
                        <span class="text-success fw-bold"><?php echo __('free'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!empty($rental_goals)): ?>
            <div class="mb-4">
                <h6 class="fw-bold small mb-2 text-muted"><?php echo __('perfect_for'); ?></h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($rental_goals as $goal): ?>
                        <?php $g_key = 'goal_' . str_replace([' & ', '-', ' '], ['_', '', '_'], strtolower($goal['name'])); ?>
                        <span class="badge bg-white text-dark border shadow-sm"><i class="<?php echo sanitize_output($goal['icon']); ?> me-1 text-primary"></i> <?php echo sanitize_output(__($g_key) !== $g_key ? __($g_key) : $goal['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-auto pt-3">
                <?php if ($available_stock > 0): ?>
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/order.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-primary btn-lg shadow">
                            <i class="fas fa-shopping-cart me-2"></i> <?php echo __('rent_this_car'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle me-1"></i> <?php echo __('car_not_available'); ?>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-outline-secondary btn-lg">
                             <?php echo __('explore_other_cars'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 mb-5">
    <a href="<?php echo SITE_URL; ?>/cars.php" class="text-decoration-none text-muted">
        <i class="fas fa-arrow-left me-1"></i> <?php echo __('back_to_all_cars'); ?>
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
