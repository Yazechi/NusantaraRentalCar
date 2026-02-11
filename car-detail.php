<?php
$page_title = 'Car Details';
require_once __DIR__ . '/includes/header.php';

$car_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$car_id) {
    set_flash_message('danger', 'Invalid car ID.');
    redirect(SITE_URL . '/cars.php');
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
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
        
        <h3 class="text-primary mb-3"><?php echo format_currency($car['price_per_day']); ?> / day</h3>
        
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Specifications</h5>
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-users me-2"></i> <strong>Seats:</strong> <?php echo (int)$car['seats']; ?></li>
                    <li><i class="fas fa-cog me-2"></i> <strong>Transmission:</strong> <?php echo ucfirst(sanitize_output($car['transmission'])); ?></li>
                    <li><i class="fas fa-gas-pump me-2"></i> <strong>Fuel Type:</strong> <?php echo ucfirst(sanitize_output($car['fuel_type'])); ?></li>
                    <?php if (!empty($car['license_plate'])): ?>
                    <li><i class="fas fa-id-card me-2"></i> <strong>License Plate:</strong> <?php echo sanitize_output($car['license_plate']); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <?php if (!empty($car['description'])): ?>
            <h5>Description</h5>
            <p><?php echo nl2br(sanitize_output($car['description'])); ?></p>
        <?php endif; ?>
        
        <?php if ($car['is_available']): ?>
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>/order.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-cart"></i> Rent This Car
                </a>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> This car is currently not available.
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Cars
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
