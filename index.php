<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('nav_home');
require_once __DIR__ . '/includes/header.php';

// Fetch featured cars (cars marked as featured with available stock)
$featured_cars = [];
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        c.discount_percent, c.is_featured,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        WHERE (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') > 0
        ORDER BY c.is_featured DESC, c.created_at DESC
        LIMIT 6");
$stmt->execute();
$featured_cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch hot deals (cars with discounts)
$deal_cars = [];
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        c.discount_percent,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        WHERE c.discount_percent > 0
        AND (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') > 0
        ORDER BY c.discount_percent DESC
        LIMIT 4");
$stmt->execute();
$deal_cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch active promotions
$promotions = [];
$stmt = $conn->prepare("SELECT * FROM promotions WHERE is_active = 1 AND (valid_to IS NULL OR valid_to >= CURDATE()) ORDER BY sort_order ASC LIMIT 4");
$stmt->execute();
$promotions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch car types with car count
$car_types = [];
$stmt = $conn->prepare("SELECT ct.*, COUNT(DISTINCT c.id) AS car_count 
        FROM car_types ct 
        LEFT JOIN cars c ON c.type_id = ct.id
        WHERE EXISTS (SELECT 1 FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available')
        GROUP BY ct.id 
        HAVING car_count > 0
        ORDER BY car_count DESC");
$stmt->execute();
$car_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch rental goals with car count
$rental_goals = [];
$stmt = $conn->prepare("SELECT rg.*, COUNT(DISTINCT crg.car_id) AS car_count 
        FROM rental_goals rg 
        LEFT JOIN car_rental_goals crg ON rg.id = crg.rental_goal_id
        LEFT JOIN cars c ON crg.car_id = c.id
        WHERE EXISTS (SELECT 1 FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available')
        GROUP BY rg.id
        HAVING car_count > 0
        ORDER BY car_count DESC");
$stmt->execute();
$rental_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$total_cars = $conn->query("SELECT COUNT(DISTINCT car_id) as c FROM car_stock WHERE status = 'available'")->fetch_assoc()['c'] ?? 0;
$total_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('approved','completed')")->fetch_assoc()['c'] ?? 0;
?>

<!-- Promo Ticker Strip -->
<?php if (!empty($promotions)): ?>
<div class="promo-ticker">
    <i class="fas fa-fire"></i>
    <?php echo sanitize_output($promotions[0]['title']); ?> — <?php echo sanitize_output($promotions[0]['subtitle']); ?>
    <i class="fas fa-fire"></i>
</div>
<?php endif; ?>

<!-- Hero Carousel -->
<div class="hero-carousel">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="<?php echo SITE_URL; ?>/assets/images/carousel/carousel1.jpeg" alt="Travel">
                <div class="carousel-caption">
                    <h1><?php echo __('hero_slide1_title'); ?></h1>
                    <p><?php echo __('hero_slide1_text'); ?></p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="<?php echo SITE_URL; ?>/assets/images/carousel/carousel2.jpeg" alt="Driving">
                <div class="carousel-caption">
                    <h1><?php echo __('hero_slide2_title'); ?></h1>
                    <p><?php echo __('hero_slide2_text'); ?></p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="<?php echo SITE_URL; ?>/assets/images/carousel/carousel3.jpeg" alt="Cars">
                <div class="carousel-caption">
                    <h1><?php echo __('hero_slide3_title'); ?></h1>
                    <p><?php echo __('hero_slide3_text'); ?></p>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Floating Search Box -->
    <div class="hero-search-box">
        <div class="search-inner">
            <i class="fas fa-search text-muted"></i>
            <input type="text" placeholder="<?php echo __('hero_search_placeholder'); ?>" id="heroSearch" 
                   onkeypress="if(event.key==='Enter') window.location.href='<?php echo SITE_URL; ?>/cars.php'">
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn-search">
                <i class="fas fa-car me-1"></i> <?php echo __('hero_search_btn'); ?>
            </a>
        </div>
    </div>
</div>

<!-- Special Offers / Promo Section -->
<?php if (!empty($promotions)): ?>
<?php
// Map promotion titles to image filenames
$promo_images = [
    'Weekend Special' => ['en' => 'weekend bonus(en).png', 'id' => 'weekend bonus(id).png'],
    'First Ride Bonus' => ['en' => 'First Ride Bonus(en).png', 'id' => 'First Ride Bonus(id).png'],
    'Long Trip Deal' => ['en' => 'LONG TRIP DEAL (en).png', 'id' => 'LONG TRIP DEAL (id).png'],
    'Family Package' => ['en' => 'Family package(en).png', 'id' => 'Family package (id).png'],
];
$current_lang = get_current_lang();
?>
<div class="section-full promo-section-bg">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-gift me-2"></i><?php echo __('promo_section_title'); ?></h2>
        <p class="section-subtitle"><?php echo __('promo_section_subtitle'); ?></p>
        <div class="row g-3">
            <?php foreach ($promotions as $promo):
                $img_file = $promo_images[$promo['title']][$current_lang] ?? $promo_images[$promo['title']]['en'] ?? '';
            ?>
            <div class="col-md-6 col-lg-3">
                <?php if (!empty($img_file)): ?>
                <div class="promo-card-img">
                    <img src="<?php echo SITE_URL; ?>/assets/images/promo-card/<?php echo rawurlencode($img_file); ?>" alt="<?php echo sanitize_output($promo['title']); ?>">
                </div>
                <?php else: ?>
                <div class="promo-card" style="background: linear-gradient(135deg, <?php echo sanitize_output($promo['banner_color']); ?>, <?php echo sanitize_output($promo['banner_color']); ?>cc);">
                    <div>
                        <div class="promo-icon"><i class="<?php echo sanitize_output($promo['icon']); ?>"></i></div>
                        <h5><?php echo sanitize_output($promo['title']); ?></h5>
                        <p><?php echo sanitize_output($promo['subtitle']); ?></p>
                    </div>
                    <div>
                        <span class="promo-discount"><?php echo (int)$promo['discount_percent']; ?>% <?php echo __('promo_off'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hot Deals Section -->
<?php if (!empty($deal_cars)): ?>
<div class="section-full section-warm">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-fire-alt me-2" style="color: var(--accent-coral);"></i><?php echo __('hot_deals_title'); ?></h2>
        <p class="section-subtitle"><?php echo __('hot_deals_subtitle'); ?></p>
        <div class="row g-4">
            <?php foreach ($deal_cars as $car): 
                $discounted_price = $car['price_per_day'] * (1 - $car['discount_percent'] / 100);
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="deal-card shadow-sm">
                    <div class="deal-image">
                        <?php if (!empty($car['image_main'])): ?>
                            <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" alt="<?php echo sanitize_output($car['name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-car fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <span class="discount-badge"><i class="fas fa-bolt me-1"></i><?php echo (int)$car['discount_percent']; ?>% <?php echo __('discount_badge'); ?></span>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1"><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h6>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-cog"></i> <?php echo ucfirst(sanitize_output($car['transmission'])); ?>
                            | <i class="fas fa-users"></i> <?php echo (int)$car['seats']; ?>
                            <?php if (!empty($car['type_name'])): ?>
                            | <?php echo sanitize_output($car['type_name']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="mb-2">
                            <span class="price-original"><?php echo format_currency($car['price_per_day']); ?></span>
                            <span class="price-discounted"><?php echo format_currency($discounted_price); ?></span>
                            <small class="text-muted"><?php echo __('per_day'); ?></small>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/car-detail.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-eye me-1"></i><?php echo __('view_details'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Featured Cars Section -->
<?php if (!empty($featured_cars)): ?>
<div class="section-full">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-star me-2" style="color: var(--secondary-color);"></i><?php echo __('featured_title'); ?></h2>
        <p class="section-subtitle"><?php echo __('featured_subtitle'); ?></p>
        <div class="row g-4">
            <?php foreach ($featured_cars as $car): 
                $has_discount = $car['discount_percent'] > 0;
                $discounted_price = $has_discount ? $car['price_per_day'] * (1 - $car['discount_percent'] / 100) : $car['price_per_day'];
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="deal-card shadow-sm h-100">
                    <div class="deal-image">
                        <?php if (!empty($car['image_main'])): ?>
                            <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" alt="<?php echo sanitize_output($car['name']); ?>">
                        <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center h-100">
                                <i class="fas fa-car fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <?php if ($has_discount): ?>
                        <span class="discount-badge"><?php echo (int)$car['discount_percent']; ?>% <?php echo __('discount_badge'); ?></span>
                        <?php endif; ?>
                        <?php if ($car['is_featured']): ?>
                        <span class="featured-badge"><i class="fas fa-star me-1"></i>Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3">
                        <h5 class="fw-bold mb-1"><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h5>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-cog"></i> <?php echo ucfirst(sanitize_output($car['transmission'])); ?> |
                            <i class="fas fa-gas-pump"></i> <?php echo format_fuel_type($car['fuel_type']); ?> |
                            <i class="fas fa-users"></i> <?php echo (int)$car['seats']; ?> <?php echo __('seats'); ?>
                            <?php if (!empty($car['type_name'])): ?>
                            | <i class="fas fa-car-side"></i> <?php echo sanitize_output($car['type_name']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="mb-2">
                            <?php if ($has_discount): ?>
                            <span class="price-original"><?php echo format_currency($car['price_per_day']); ?></span>
                            <span class="price-discounted"><?php echo format_currency($discounted_price); ?></span>
                            <?php else: ?>
                            <span class="price-normal"><?php echo format_currency($car['price_per_day']); ?></span>
                            <?php endif; ?>
                            <small class="text-muted"><?php echo __('per_day'); ?></small>
                        </div>
                        <?php if (!empty($car['available_stock'])): ?>
                        <p class="mb-2"><span class="badge bg-success"><i class="fas fa-check-circle me-1"></i><?php echo (int)$car['available_stock']; ?> <?php echo __('units_available'); ?></span></p>
                        <?php endif; ?>
                        <a href="<?php echo SITE_URL; ?>/car-detail.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-primary btn-sm w-100"><?php echo __('view_details'); ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary btn-lg px-5"><?php echo __('view_all_cars'); ?> <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Browse by Type Section -->
<?php if (!empty($car_types)): ?>
<div class="section-full section-gray">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-th-large me-2"></i><?php echo __('browse_by_type'); ?></h2>
        <p class="section-subtitle"><?php echo __('browse_by_type_desc'); ?></p>
        <div class="row g-3">
            <?php foreach ($car_types as $type): ?>
            <div class="col-lg-2 col-md-3 col-4">
                <a href="<?php echo SITE_URL; ?>/cars.php?type=<?php echo (int)$type['id']; ?>" class="text-decoration-none">
                    <div class="type-card-new shadow-sm">
                        <div class="type-icon">
                            <i class="<?php echo sanitize_output($type['icon']); ?>"></i>
                        </div>
                        <h6><?php echo sanitize_output($type['name']); ?></h6>
                        <small class="text-muted"><?php echo (int)$type['car_count']; ?> <?php echo __('nav_cars'); ?></small>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Rent for Your Occasion Section -->
<?php if (!empty($rental_goals)): ?>
<?php
// Map rental goal names to image files
$goal_images = [
    'Business Trip' => 'businessTrip.jpg',
    'Vacation' => 'vacation.jpg',
    'Honeymoon' => 'honeymoon.jpg',
    'Wedding' => 'wedding.jpg',
    'Family Trip' => 'family trip.jpg',
    'Industrial' => 'indsutrial.jpg',
    'Construction' => 'construction.jpg',
    'Events & Parties' => 'event and parties.jpg',
    'Airport Transfer' => 'airport transfer.jpg',
    'City Tour' => 'city tour.jpg',
    'Adventure & Off-Road' => 'adventure and offroads.jpg',
    'Cargo & Delivery' => 'cargo and delivery.jpg',
];
?>
<div class="section-full">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-bullseye me-2"></i><?php echo __('rent_for_occasion'); ?></h2>
        <p class="section-subtitle"><?php echo __('rent_for_occasion_desc'); ?></p>
        <div class="row g-3">
            <?php foreach ($rental_goals as $goal):
                $img = $goal_images[$goal['name']] ?? '';
            ?>
            <div class="col-lg-3 col-md-4 col-6">
                <a href="<?php echo SITE_URL; ?>/cars.php?goal=<?php echo (int)$goal['id']; ?>" class="text-decoration-none">
                    <div class="goal-card-img">
                        <?php if (!empty($img)): ?>
                        <img src="<?php echo SITE_URL; ?>/assets/images/rental-goals/<?php echo rawurlencode($img); ?>" alt="<?php echo sanitize_output($goal['name']); ?>">
                        <?php else: ?>
                        <div class="goal-card-placeholder"><i class="<?php echo sanitize_output($goal['icon']); ?>"></i></div>
                        <?php endif; ?>
                        <div class="goal-card-overlay">
                            <h6><?php echo sanitize_output($goal['name']); ?></h6>
                            <small><?php echo (int)$goal['car_count']; ?> <?php echo __('nav_cars'); ?></small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Why Choose Us Section -->
<div class="section-full section-gray">
    <div class="container">
        <h2 class="section-title text-center"><?php echo __('why_choose_title'); ?></h2>
        <p class="section-subtitle text-center"><?php echo __('why_choose_subtitle'); ?></p>
        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="why-card h-100">
                    <div class="why-icon" style="background: #e8f5e9; color: #2e7d32;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h5><?php echo __('why_247_title'); ?></h5>
                    <p><?php echo __('why_247_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-card h-100">
                    <div class="why-icon" style="background: #e3f2fd; color: #1565c0;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5><?php echo __('why_insured_title'); ?></h5>
                    <p><?php echo __('why_insured_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-card h-100">
                    <div class="why-icon" style="background: #fff3e0; color: #e65100;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h5><?php echo __('why_flexible_title'); ?></h5>
                    <p><?php echo __('why_flexible_desc'); ?></p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="why-card h-100">
                    <div class="why-icon" style="background: #fce4ec; color: #c62828;">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h5><?php echo __('why_maintained_title'); ?></h5>
                    <p><?php echo __('why_maintained_desc'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Counter Section -->
<div class="section-full section-gradient stats-section">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo max(1200, $total_orders * 10); ?>">0</div>
                    <div class="stat-label"><?php echo __('stats_happy_customers'); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo max(50, $total_cars); ?>">0</div>
                    <div class="stat-label"><?php echo __('stats_cars_available'); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="25">0</div>
                    <div class="stat-label"><?php echo __('stats_cities_covered'); ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item">
                    <div class="stat-number" data-count="8">0</div>
                    <div class="stat-label"><?php echo __('stats_years_experience'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="cta-banner">
    <div class="cta-banner-overlay"></div>
    <div class="container position-relative" style="z-index:2;">
        <h2><?php echo __('cta_title'); ?></h2>
        <p><?php echo __('cta_subtitle'); ?></p>
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-warning btn-lg fw-bold">
            <i class="fas fa-car me-2"></i><?php echo __('cta_btn'); ?>
        </a>
    </div>
</div>

<!-- Stats Counter Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 60;
    
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-count'));
                const increment = Math.ceil(target / speed);
                let current = 0;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target.toLocaleString('id-ID') + '+';
                        clearInterval(timer);
                    } else {
                        counter.textContent = current.toLocaleString('id-ID');
                    }
                }, 30);
                
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(c => observer.observe(c));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
