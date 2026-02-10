<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';

require_login();

// Fetch featured cars (available cars, newest first, limit 6)
$featured_cars = [];
$sql = "SELECT c.*, cb.name AS brand_name
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE c.is_available = 1
        ORDER BY c.created_at DESC
        LIMIT 6";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $featured_cars = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Hero Section -->
<div class="bg-dark text-white rounded-3 p-5 mb-4">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="fw-bold mb-3">Rent Your Perfect Car</h1>
            <p class="lead mb-4">Quality vehicles at affordable prices. Explore our wide selection of cars for your travel needs across Indonesia.</p>
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-light btn-lg">
                <i class="fas fa-car"></i> Browse Cars
            </a>
        </div>
        <div class="col-md-4 text-center d-none d-md-block">
            <i class="fas fa-car-side" style="font-size: 8rem; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="row mb-5">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-tags fa-2x text-dark mb-3"></i>
                <h5>Affordable Prices</h5>
                <p class="text-muted mb-0">Competitive daily rates for all vehicle types.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-shield-alt fa-2x text-dark mb-3"></i>
                <h5>Safe & Reliable</h5>
                <p class="text-muted mb-0">Well-maintained vehicles for a smooth journey.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center p-4">
                <i class="fas fa-truck fa-2x text-dark mb-3"></i>
                <h5>Delivery Option</h5>
                <p class="text-muted mb-0">Pick up at our location or get the car delivered.</p>
            </div>
        </div>
    </div>
</div>

<!-- Featured Cars Section -->
<?php if (!empty($featured_cars)): ?>
<h3 class="mb-3"><i class="fas fa-star"></i> Featured Cars</h3>
<div class="row mb-4">
    <?php foreach ($featured_cars as $car): ?>
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <?php if (!empty($car['image_main'])): ?>
                <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" class="card-img-top" alt="<?php echo sanitize_output($car['name']); ?>" style="height: 200px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                    <i class="fas fa-car fa-3x text-white"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <h5 class="card-title"><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h5>
                <p class="text-muted mb-1">
                    <small>
                        <i class="fas fa-cog"></i> <?php echo ucfirst(sanitize_output($car['transmission'])); ?> |
                        <i class="fas fa-gas-pump"></i> <?php echo ucfirst(sanitize_output($car['fuel_type'])); ?> |
                        <i class="fas fa-users"></i> <?php echo (int)$car['seats']; ?> seats
                    </small>
                </p>
                <p class="fw-bold text-dark mb-2"><?php echo format_currency($car['price_per_day']); ?> / day</p>
                <a href="<?php echo SITE_URL; ?>/car-detail.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-dark btn-sm">View Details</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="text-center mb-4">
    <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-outline-dark">View All Cars <i class="fas fa-arrow-right"></i></a>
</div>
<?php else: ?>
<div class="text-center py-5">
    <i class="fas fa-car fa-3x text-muted mb-3"></i>
    <h4 class="text-muted">No cars available yet</h4>
    <p class="text-muted">Check back soon for our vehicle listings.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
