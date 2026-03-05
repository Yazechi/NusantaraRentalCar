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
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name
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
// Get available stock count for this car
$stock_stmt = $conn->prepare("SELECT COUNT(*) as available_stock FROM car_stock WHERE car_id = ? AND status = 'available'");
$stock_stmt->bind_param("i", $car_id);
$stock_stmt->execute();
$available_stock = $stock_stmt->get_result()->fetch_assoc()['available_stock'];
$stock_stmt->close();

// Get available stock units with plate numbers
$plates_stmt = $conn->prepare("SELECT id, plate_number, status FROM car_stock WHERE car_id = ? AND status = 'available' ORDER BY plate_number ASC");
$plates_stmt->bind_param("i", $car_id);
$plates_stmt->execute();
$available_plates = $plates_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$plates_stmt->close();
?>

<div class="row">
    <div class="col-md-6">
        <?php if (!empty($car['image_main'])): ?>
            <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" class="img-fluid rounded" alt="<?php echo sanitize_output($car['name']); ?>">
        <?php else: ?>
            <div class="bg-secondary d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                <i class="fas fa-car fa-5x text-white"></i>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h2><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h2>
        <?php if (!empty($car['model'])): ?>
            <p class="text-muted"><?php echo sanitize_output($car['model']); ?> <?php echo !empty($car['year']) ? '(' . (int)$car['year'] . ')' : ''; ?></p>
        <?php endif; ?>
        
        <?php 
        $discount = isset($car['discount_percent']) ? (int)$car['discount_percent'] : 0;
        $discounted_price = $discount > 0 ? $car['price_per_day'] * (1 - $discount / 100) : $car['price_per_day'];
        ?>
        <?php if ($discount > 0): ?>
        <div class="mb-3">
            <span class="badge bg-danger fs-6 me-2"><i class="fas fa-bolt me-1"></i><?php echo $discount; ?>% OFF</span>
            <span class="price-original fs-5"><?php echo format_currency($car['price_per_day']); ?></span>
            <span class="price-discounted fs-3"><?php echo format_currency($discounted_price); ?></span>
            <span class="text-muted"><?php echo __('per_day'); ?></span>
        </div>
        <?php else: ?>
        <h3 class="text-primary mb-3"><?php echo format_currency($car['price_per_day']); ?> <?php echo __('per_day'); ?></h3>
        <?php endif; ?>

        <p class="mb-3">
            <?php if ($available_stock > 0): ?>
                <span class="badge bg-success"><i class="fas fa-check-circle"></i> <?php echo __('in_stock'); ?>: <?php echo $available_stock; ?> <?php echo __('units_available'); ?></span>
            <?php else: ?>
                <span class="badge bg-danger"><i class="fas fa-times-circle"></i> <?php echo __('out_of_stock'); ?></span>
            <?php endif; ?>
        </p>

        <?php if (!empty($available_plates)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-id-card me-1"></i> <?php echo __('license_label'); ?></h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($available_plates as $plate): ?>
                        <span class="badge bg-dark fs-6"><i class="fas fa-car me-1"></i> <?php echo sanitize_output($plate['plate_number']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('specifications'); ?></h5>
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-users me-2"></i> <strong><?php echo __('seats_label'); ?>:</strong> <?php echo (int)$car['seats']; ?></li>
                    <li><i class="fas fa-cog me-2"></i> <strong><?php echo __('transmission_label'); ?>:</strong> <?php echo ucfirst(sanitize_output($car['transmission'])); ?></li>
                    <li><i class="fas fa-gas-pump me-2"></i> <strong><?php echo __('fuel_label'); ?>:</strong> <?php echo format_fuel_type($car['fuel_type']); ?><?php if (!empty($car['is_electric'])): ?> <span class="badge bg-success"><i class="fas fa-bolt"></i> EV</span><?php endif; ?></li>
                    <?php if (!empty($car['color'])): ?>
                    <li><i class="fas fa-palette me-2"></i> <strong><?php echo __('color_label'); ?>:</strong> <?php echo sanitize_output($car['color']); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($car['type_name'])): ?>
                    <li><i class="fas fa-car-side me-2"></i> <strong><?php echo __('type_label'); ?>:</strong> <?php echo sanitize_output($car['type_name']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($rental_goals)): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo __('suitable_for'); ?></h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($rental_goals as $goal): ?>
                        <span class="badge bg-primary"><i class="<?php echo sanitize_output($goal['icon']); ?> me-1"></i> <?php echo sanitize_output($goal['name']); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($car['description'])): ?>
            <h5><?php echo __('description'); ?></h5>
            <p><?php echo nl2br(sanitize_output($car['description'])); ?></p>
        <?php endif; ?>
        
        <?php if ($available_stock > 0): ?>
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>/order.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-cart"></i> <?php echo __('rent_this_car'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> <?php echo __('car_not_available'); ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo __('back_to_cars'); ?>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
