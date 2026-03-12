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
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available' AND cs.id NOT IN (SELECT car_stock_id FROM orders WHERE status IN ('pending', 'approved') AND rental_end_date >= CURDATE())) AS available_stock,
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

// Get additional images
$stmt = $conn->prepare("SELECT image_path FROM car_images WHERE car_id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$additional_images_res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get stock unit details
$stmt = $conn->prepare("SELECT plate_number, color, image_url FROM car_stock WHERE car_id = ? AND status = 'available' AND id NOT IN (SELECT car_stock_id FROM orders WHERE status IN ('pending', 'approved') AND rental_end_date >= CURDATE())");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$stock_units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$all_images = [];
if (!empty($car['image_main'])) {
    $all_images[] = $car['image_main'];
}
foreach ($additional_images_res as $img) {
    if (!in_array($img['image_path'], $all_images)) {
        $all_images[] = $img['image_path'];
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-end mb-5">
    <div>
        <div class="section-eyebrow mb-2"><?php echo sanitize_output($car['brand_name']); ?></div>
        <h1 class="mb-0 fw-bold"><?php echo sanitize_output($car['name']); ?></h1>
    </div>
    <div class="d-none d-md-block">
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-2"></i> <?php echo __('back_to_fleet'); ?>
        </a>
    </div>
</div>

<div class="row g-5">
    <!-- Left Column: Visuals & Tech Specs -->
    <div class="col-lg-7">
        <div class="car-visuals-container mb-5">
            <!-- Main Preview Card -->
            <div class="preview-card bg-white shadow-lg overflow-hidden position-relative">
                <div id="mainImageLoader" class="position-absolute top-50 start-50 translate-middle d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <?php if (!empty($all_images)): ?>
                    <img id="mainCarImage" src="<?php echo UPLOAD_URL . sanitize_output($all_images[0]); ?>" 
                         alt="<?php echo sanitize_output($car['name']); ?>" 
                         class="img-fluid w-100" style="object-fit: contain; max-height: 450px;">
                <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center h-100 w-100 py-5">
                        <i class="fas fa-car fa-5x text-muted opacity-25"></i>
                    </div>
                <?php endif; ?>
                
                <div class="position-absolute bottom-0 end-0 p-3">
                    <span class="badge bg-dark-car text-gold border-gold">
                        <i class="fas fa-expand-arrows-alt me-1"></i> <?php echo __('preview_mode'); ?>
                    </span>
                </div>
            </div>

            <!-- Thumbnail Gallery Grid -->
            <?php if (count($all_images) > 0): ?>
            <div class="gallery-card mt-3">
                <h6 class="small text-uppercase fw-bold letter-spacing-1 text-muted mb-3"><?php echo __('vehicle_gallery'); ?></h6>
                <div class="gallery-grid">
                    <?php foreach ($all_images as $index => $img): ?>
                        <div class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                             onmouseenter="previewImage(this, '<?php echo UPLOAD_URL . sanitize_output($img); ?>')"
                             onclick="selectImage(this, '<?php echo UPLOAD_URL . sanitize_output($img); ?>')">
                            <img src="<?php echo UPLOAD_URL . sanitize_output($img); ?>" alt="Gallery Image <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Technical Specifications -->
        <div class="info-section">
            <div class="info-header">
                <h5><i class="fas fa-microchip text-gold me-2"></i><?php echo __('tech_specs'); ?></h5>
            </div>
            <div class="specs-grid-v2">
                <div class="spec-pill">
                    <i class="fas fa-users"></i>
                    <div>
                        <div class="text-muted small"><?php echo __('capacity'); ?></div>
                        <div class="fw-bold"><?php echo (int)$car['seats']; ?> <?php echo __('seater'); ?></div>
                    </div>
                </div>
                <div class="spec-pill">
                    <i class="fas fa-cog"></i>
                    <div>
                        <div class="text-muted small"><?php echo __('transmission'); ?></div>
                        <div class="fw-bold text-capitalize"><?php echo sanitize_output($car['transmission']); ?></div>
                    </div>
                </div>
                <div class="spec-pill">
                    <i class="fas fa-gas-pump"></i>
                    <div>
                        <div class="text-muted small"><?php echo __('fuel_type'); ?></div>
                        <div class="fw-bold"><?php echo sanitize_output(format_fuel_type($car['fuel_type'] ?? 'pertamax')); ?></div>
                    </div>
                </div>
                <div class="spec-pill">
                    <i class="fas fa-car-side"></i>
                    <div>
                        <div class="text-muted small"><?php echo __('body_type'); ?></div>
                        <div class="fw-bold"><?php echo sanitize_output($car['type_name'] ?: 'Standard'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features / Perfect For -->
        <?php if (!empty($rental_goals)): ?>
        <div class="info-section">
            <div class="info-header">
                <h5><i class="fas fa-star text-gold me-2"></i><?php echo __('perfect_for'); ?></h5>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($rental_goals as $goal): ?>
                    <div class="card-refined bg-white p-3 rounded-md d-flex align-items-center gap-3 border shadow-sm" style="min-width: 160px;">
                        <i class="<?php echo sanitize_output($goal['icon']); ?> text-gold fs-5"></i>
                        <?php $g_key = 'goal_' . str_replace([' & ', '-', ' '], ['_', '', '_'], strtolower($goal['name'])); ?>
                        <span class="fw-bold small"><?php echo sanitize_output(__($g_key) !== $g_key ? __($g_key) : $goal['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reviews Section -->
        <div class="info-section">
            <div class="info-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-comments text-gold me-2"></i><?php echo __('customer_reviews'); ?></h5>
                <?php if ($car['review_count'] > 0): ?>
                <div class="badge bg-secondary-subtle text-secondary"><?php echo $car['review_count']; ?> <?php echo __('reviews'); ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="p-5 text-center bg-white rounded-lg border border-dashed">
                    <i class="far fa-comment-alt fa-3x text-muted mb-3 opacity-25"></i>
                    <p class="text-muted mb-0"><?php echo __('no_reviews_yet'); ?></p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($reviews as $rev): ?>
                    <div class="col-md-6">
                        <div class="review-item h-100">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="fw-bold"><?php echo sanitize_output($rev['user_name']); ?></div>
                                <div class="text-warning small">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?php echo $i <= $rev['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="small text-muted mb-3 italic">"<?php echo nl2br(sanitize_output($rev['comment'])); ?>"</p>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                <i class="far fa-calendar-alt me-1"></i> <?php echo format_date($rev['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Booking Card -->
    <div class="col-lg-5">
        <div class="booking-card-v2 sticky-top" style="top: 100px; z-index: 10;">
            <div class="booking-price-header">
                <div class="small text-uppercase opacity-75 letter-spacing-1 mb-1"><?php echo __('daily_rental_rate'); ?></div>
                <?php 
                $discount = isset($car['discount_percent']) ? (int)$car['discount_percent'] : 0;
                $discounted_price = $discount > 0 ? $car['price_per_day'] * (1 - $discount / 100) : $car['price_per_day'];
                ?>
                <div class="price-value">
                    <?php echo format_currency($discounted_price); ?>
                    <span class="fs-6 text-white opacity-50 fw-normal">/ <?php echo __('day'); ?></span>
                </div>
                <?php if ($discount > 0): ?>
                    <div class="mt-2">
                        <span class="text-decoration-line-through text-white opacity-50 me-2"><?php echo format_currency($car['price_per_day']); ?></span>
                        <span class="badge bg-danger animate-pulse"><?php echo __('save_percent'); ?> <?php echo $discount; ?>%</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="booking-options shadow-lg">
                <div class="mb-4">
                    <label class="form-label d-flex justify-content-between">
                        <span><?php echo __('vehicle_status'); ?></span>
                        <?php if ($available_stock > 0): ?>
                            <span class="text-success"><i class="fas fa-check-circle me-1"></i> <?php echo __('ready_to_drive'); ?></span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle me-1"></i> <?php echo __('fully_booked'); ?></span>
                        <?php endif; ?>
                    </label>
                    <div class="p-3 bg-light rounded-md border d-flex align-items-center gap-3">
                        <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                            <i class="fas fa-hashtag text-gold"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small text-muted"><?php echo __('availability'); ?></div>
                            <div class="fw-bold"><?php echo $available_stock; ?> <?php echo __('units_in_stock'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Unit Selection if multiple available -->
                <?php if ($available_stock > 0 && count($stock_units) > 0): ?>
                <div class="mb-4">
                    <label class="form-label"><?php echo __('choose_color_unit'); ?></label>
                    <select id="plateSelect" class="form-select border-gold border-opacity-25 py-3">
                        <?php foreach ($stock_units as $unit): 
                            $plate_text = $unit['plate_number'] . (!empty($unit['color']) ? ' - ' . $unit['color'] : '');
                            $stock_img = !empty($unit['image_url']) ? UPLOAD_URL . sanitize_output($unit['image_url']) : '';
                        ?>
                            <option value="<?php echo sanitize_output($unit['plate_number']); ?>" data-image="<?php echo $stock_img; ?>">
                                <?php echo sanitize_output($plate_text); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="mb-4">
                    <h6 class="small fw-bold text-uppercase text-muted mb-3 border-bottom pb-2"><?php echo __('inclusive_features'); ?></h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 small d-flex align-items-center gap-2">
                            <i class="fas fa-check text-success"></i> <?php echo __('comprehensive_insurance'); ?>
                        </li>
                        <li class="mb-2 small d-flex align-items-center gap-2">
                            <i class="fas fa-check text-success"></i> <?php echo __('roadside_assistance'); ?>
                        </li>
                        <li class="mb-2 small d-flex align-items-center gap-2">
                            <i class="fas fa-check text-success"></i> <?php echo __('regular_maintenance'); ?>
                        </li>
                        <li class="small d-flex align-items-center gap-2">
                            <i class="fas fa-check text-success"></i> <?php echo __('clean_disinfected'); ?>
                        </li>
                    </ul>
                </div>

                <div class="d-grid gap-3">
                    <?php if ($available_stock > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/order.php?id=<?php echo (int)$car['id']; ?>" class="btn btn-primary btn-lg py-3 shadow-gold">
                            <i class="fas fa-bolt me-2"></i> <?php echo __('instant_booking'); ?>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg py-3" disabled>
                            <i class="fas fa-calendar-times me-2"></i> <?php echo __('out_of_stock'); ?>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-outline-dark-car btn-sm py-2">
                        <i class="fas fa-share-alt me-2"></i> <?php echo __('share_this_car'); ?>
                    </button>
                </div>

                <div class="text-center mt-3">
                    <div class="small text-muted d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-shield-alt text-primary"></i> <?php echo __('secure_checkout'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let lastSelectedThumb = document.querySelector('.gallery-thumb.active');
const mainImage = document.getElementById('mainCarImage');
const mainLoader = document.getElementById('mainImageLoader');

/**
 * Updates the main preview image when hovering over a thumbnail
 */
function previewImage(el, src) {
    if (!mainImage || !src) return;
    
    // Smooth fade transition
    mainImage.style.transform = 'scale(0.98)';
    mainImage.style.opacity = '0.7';
    
    setTimeout(() => {
        mainImage.src = src;
        mainImage.style.transform = 'scale(1)';
        mainImage.style.opacity = '1';
    }, 150);

    // Temp visual update for thumbs
    document.querySelectorAll('.gallery-thumb').forEach(t => t.style.borderColor = 'transparent');
    el.style.borderColor = 'var(--primary-color)';
}

/**
 * Permanently selects an image (on click)
 */
function selectImage(el, src) {
    previewImage(el, src);
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    lastSelectedThumb = el;
}

// Restore last selected image on gallery mouseleave
document.querySelector('.gallery-grid').addEventListener('mouseleave', function() {
    if (lastSelectedThumb) {
        const src = lastSelectedThumb.querySelector('img').src;
        previewImage(lastSelectedThumb, src);
        document.querySelectorAll('.gallery-thumb').forEach(t => {
            t.style.borderColor = t.classList.contains('active') ? 'var(--primary-color)' : 'transparent';
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const plateSelect = document.getElementById('plateSelect');
    
    if (plateSelect && mainImage) {
        plateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const newImage = selectedOption.getAttribute('data-image');
            
            if (newImage) {
                // Deactivate thumbs as we are showing a specific unit image
                document.querySelectorAll('.gallery-thumb').forEach(t => {
                    t.classList.remove('active');
                    t.style.borderColor = 'transparent';
                });
                
                mainImage.style.opacity = '0';
                mainLoader.classList.remove('d-none');
                
                const imgLoader = new Image();
                imgLoader.src = newImage;
                imgLoader.onload = function() {
                    mainImage.src = newImage;
                    mainImage.style.opacity = '1';
                    mainLoader.classList.add('d-none');
                };
            }
        });
    }
    
    // Add hover scale effect to preview-card
    const previewCard = document.querySelector('.preview-card');
    if (previewCard) {
        previewCard.addEventListener('mouseenter', () => {
            mainImage.style.transform = 'scale(1.05)';
        });
        previewCard.addEventListener('mouseleave', () => {
            mainImage.style.transform = 'scale(1)';
        });
    }
});
</script>

<style>
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 12px;
}

.gallery-thumb {
    height: 60px;
    border-radius: var(--radius-sm);
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: var(--transition-fast);
    background: #f8f9fa;
}

.gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.gallery-thumb:hover img {
    transform: scale(1.1);
}

.gallery-thumb.active {
    border-color: var(--primary-color);
}

.letter-spacing-1 { letter-spacing: 1px; }

.italic { font-style: italic; }

.shadow-gold {
    box-shadow: 0 10px 25px rgba(201, 168, 76, 0.3);
}

#mainCarImage {
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
}

.animate-pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
