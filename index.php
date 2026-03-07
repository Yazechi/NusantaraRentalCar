<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('nav_home');
require_once __DIR__ . '/includes/header.php';

// Helper for stars
function get_stars_html($avg, $count) {
    if ($count == 0) return '<div class="text-muted small mb-2"><i class="far fa-star me-1"></i>New Car</div>';
    $html = '<div class="text-warning small mb-2">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="' . ($i <= round($avg) ? 'fas' : 'far') . ' fa-star"></i>';
    }
    $html .= ' <span class="text-muted">(' . (int)$count . ')</span></div>';
    return $html;
}

// Fetch featured cars with ratings
$featured_cars = [];
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        c.discount_percent, c.is_featured,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock,
        (SELECT AVG(rating) FROM car_reviews cr WHERE cr.car_id = c.id) as avg_rating,
        (SELECT COUNT(*) FROM car_reviews cr WHERE cr.car_id = c.id) as review_count
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        ORDER BY c.is_featured DESC, c.created_at DESC
        LIMIT 6");
$stmt->execute();
$featured_cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch hot deals with ratings
$deal_cars = [];
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name, ct.name AS type_name,
        c.discount_percent,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock,
        (SELECT AVG(rating) FROM car_reviews cr WHERE cr.car_id = c.id) as avg_rating,
        (SELECT COUNT(*) FROM car_reviews cr WHERE cr.car_id = c.id) as review_count
        FROM cars c
        JOIN car_brands cb ON c.brand_id = cb.id
        LEFT JOIN car_types ct ON c.type_id = ct.id
        WHERE c.discount_percent > 0
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
        GROUP BY ct.id 
        HAVING car_count > 0
        ORDER BY car_count DESC");
$stmt->execute();
$car_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch rental goals
$rental_goals = [];
$stmt = $conn->prepare("SELECT rg.*, COUNT(DISTINCT crg.car_id) AS car_count 
        FROM rental_goals rg 
        LEFT JOIN car_rental_goals crg ON rg.id = crg.rental_goal_id
        GROUP BY rg.id
        HAVING car_count > 0
        ORDER BY car_count DESC");
$stmt->execute();
$rental_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Stats
$total_cars = $conn->query("SELECT COUNT(*) as c FROM cars WHERE is_available = 1")->fetch_assoc()['c'] ?? 0;
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

<!-- Special Offers -->
<?php if (!empty($promotions)): ?>
<?php
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
                <div class="promo-card-img">
                    <img src="<?php echo SITE_URL; ?>/assets/images/promo-card/<?php echo rawurlencode($img_file); ?>" alt="<?php echo sanitize_output($promo['title']); ?>">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hot Deals -->
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
                <div class="deal-card shadow-sm h-100 <?php echo $car['available_stock'] == 0 ? 'opacity-75' : ''; ?>">
                    <div class="deal-image">
                        <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" alt="<?php echo sanitize_output($car['name']); ?>">
                        <span class="discount-badge"><i class="fas fa-bolt me-1"></i><?php echo (int)$car['discount_percent']; ?>% <?php echo __('discount_badge'); ?></span>
                        <div style="position:absolute;top:10px;right:10px;">
                            <?php if($car['available_stock'] > 0): ?>
                                <span class="badge bg-success shadow-sm"><?php echo __('available'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger shadow-sm"><?php echo __('unavailable'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1"><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h6>
                        <?php echo get_stars_html($car['avg_rating'], $car['review_count']); ?>
                        <div class="mb-2">
                            <span class="price-original"><?php echo format_currency($car['price_per_day']); ?></span>
                            <span class="price-discounted"><?php echo format_currency($discounted_price); ?></span>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/car-detail.php?id=<?php echo (int)$car['id']; ?>" class="btn <?php echo $car['available_stock'] > 0 ? 'btn-primary' : 'btn-outline-secondary'; ?> w-100">
                            <?php echo __('view_details'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Featured Cars -->
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
                <div class="deal-card shadow-sm h-100 <?php echo $car['available_stock'] == 0 ? 'opacity-75' : ''; ?>">
                    <div class="deal-image">
                        <img src="<?php echo UPLOAD_URL . sanitize_output($car['image_main']); ?>" alt="<?php echo sanitize_output($car['name']); ?>">
                        <?php if ($has_discount): ?>
                        <span class="discount-badge"><?php echo (int)$car['discount_percent']; ?>% <?php echo __('discount_badge'); ?></span>
                        <?php endif; ?>
                        <div style="position:absolute;top:10px;right:10px;">
                            <?php if($car['available_stock'] > 0): ?>
                                <span class="badge bg-success shadow-sm"><?php echo __('available'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger shadow-sm"><?php echo __('unavailable'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <h5 class="fw-bold mb-1"><?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h5>
                        <?php echo get_stars_html($car['avg_rating'], $car['review_count']); ?>
                        <p class="text-muted small mb-2">
                            <i class="fas fa-cog"></i> <?php echo ucfirst(sanitize_output($car['transmission'])); ?> |
                            <i class="fas fa-users"></i> <?php echo (int)$car['seats']; ?> <?php echo __('seats'); ?>
                        </p>
                        <div class="mb-2">
                            <?php if ($has_discount): ?>
                            <span class="price-original"><?php echo format_currency($car['price_per_day']); ?></span>
                            <span class="price-discounted"><?php echo format_currency($discounted_price); ?></span>
                            <?php else: ?>
                            <span class="price-normal"><?php echo format_currency($car['price_per_day']); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo SITE_URL; ?>/car-detail.php?id=<?php echo (int)$car['id']; ?>" class="btn <?php echo $car['available_stock'] > 0 ? 'btn-primary' : 'btn-outline-secondary'; ?> w-100"><?php echo __('view_details'); ?></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Browse by Type -->
<?php if (!empty($car_types)): ?>
<div class="section-full section-gray">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-th-large me-2"></i><?php echo __('browse_by_type'); ?></h2>
        <div class="row g-3">
            <?php foreach ($car_types as $type): ?>
            <div class="col-lg-2 col-md-3 col-4">
                <a href="<?php echo SITE_URL; ?>/cars.php?type=<?php echo (int)$type['id']; ?>" class="text-decoration-none">
                    <div class="type-card-new shadow-sm">
                        <div class="type-icon"><i class="<?php echo sanitize_output($type['icon']); ?>"></i></div>
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

<!-- Browse by Occasion -->
<?php if (!empty($rental_goals)): ?>
<div class="section-full">
    <div class="container">
        <h2 class="section-title"><i class="fas fa-calendar-check me-2"></i><?php echo __('rent_for_occasion'); ?></h2>
        <div class="row g-3">
            <?php foreach ($rental_goals as $goal): ?>
            <div class="col-lg-3 col-md-4 col-6">
                <a href="<?php echo SITE_URL; ?>/cars.php?goal=<?php echo (int)$goal['id']; ?>" class="text-decoration-none">
                    <div class="occasion-card shadow-sm">
                        <?php if (!empty($goal['image'])): ?>
                        <div class="occasion-img" style="background-image: url('<?php echo SITE_URL; ?>/assets/images/rental-goals/<?php echo rawurlencode($goal['image']); ?>')"></div>
                        <?php endif; ?>
                        <div class="occasion-info">
                            <?php $g_key = 'goal_' . str_replace([' & ', '-', ' '], ['_', '', '_'], strtolower($goal['name'])); ?>
                            <h6><i class="<?php echo sanitize_output($goal['icon']); ?> me-1"></i> <?php echo sanitize_output(__($g_key) !== $g_key ? __($g_key) : $goal['name']); ?></h6>
                            <small class="text-muted"><?php echo (int)$goal['car_count']; ?> <?php echo __('nav_cars'); ?></small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats, CTA, and Scripts remain same -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
