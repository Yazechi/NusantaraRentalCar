<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/language.php';
$page_title = 'Add Promotion';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $banner_color = trim($_POST['banner_color'] ?? '#667eea');
    $icon = trim($_POST['icon'] ?? 'fas fa-tag');
    $discount_percent = (int)($_POST['discount_percent'] ?? 0);
    $min_duration_days = (int)($_POST['min_duration_days'] ?? 1);
    $valid_from = !empty($_POST['valid_from']) ? $_POST['valid_from'] : null;
    $valid_to = !empty($_POST['valid_to']) ? $_POST['valid_to'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $requires_first_order = isset($_POST['requires_first_order']) ? 1 : 0;
    $is_weekend_only = isset($_POST['is_weekend_only']) ? 1 : 0;
    $required_occasion = trim($_POST['required_occasion'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $image_en = null;
    $image_id = null;

    if (empty($title)) $errors[] = "Title is required.";
    if ($discount_percent < 0 || $discount_percent > 100) $errors[] = "Discount must be between 0 and 100.";
    if ($min_duration_days < 1) $errors[] = "Minimum duration must be at least 1 day.";

    $upload_dir = BASE_PATH . '/assets/images/promo-card/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // Handle Image EN Upload
    if (isset($_FILES['image_en']) && $_FILES['image_en']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['image_en']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $image_name = uniqid('promo_en_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['image_en']['tmp_name'], $upload_dir . $image_name)) {
                $image_en = $image_name;
            } else {
                $errors[] = "Failed to upload English image.";
            }
        } else {
            $errors[] = "Invalid English image format.";
        }
    }

    // Handle Image ID Upload
    if (isset($_FILES['image_id']) && $_FILES['image_id']['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($_FILES['image_id']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $image_name = uniqid('promo_id_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['image_id']['tmp_name'], $upload_dir . $image_name)) {
                $image_id = $image_name;
            } else {
                $errors[] = "Failed to upload Indonesian image.";
            }
        } else {
            $errors[] = "Invalid Indonesian image format.";
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO promotions (title, subtitle, description, banner_color, icon, image_en, image_id, discount_percent, min_duration_days, requires_first_order, is_weekend_only, required_occasion, valid_from, valid_to, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssiiiisssii", $title, $subtitle, $description, $banner_color, $icon, $image_en, $image_id, $discount_percent, $min_duration_days, $requires_first_order, $is_weekend_only, $required_occasion, $valid_from, $valid_to, $is_active, $sort_order);
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Promotion added successfully.');
            redirect('promotions.php');
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle me-2 text-primary"></i> <?php echo __('admin_add_promotion'); ?></h2>
        <a href="promotions.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> <?php echo __('admin_promo_back'); ?></a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error) echo "<li>" . sanitize_output($error) . "</li>"; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white pb-0 border-0">
            <h5 class="mb-0 text-primary"><i class="fas fa-info-circle me-2"></i> <?php echo __('admin_promo_general'); ?></h5>
            <hr>
        </div>
        <div class="card-body pt-0 p-4">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('admin_promo_title'); ?></label>
                        <input type="text" name="title" class="form-control" required value="<?php echo sanitize_output($_POST['title'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('admin_promo_subtitle'); ?></label>
                        <input type="text" name="subtitle" class="form-control" value="<?php echo sanitize_output($_POST['subtitle'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('admin_promo_valid_from'); ?></label>
                        <input type="date" name="valid_from" class="form-control" value="<?php echo sanitize_output($_POST['valid_from'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('admin_promo_valid_to'); ?></label>
                        <input type="date" name="valid_to" class="form-control" value="<?php echo sanitize_output($_POST['valid_to'] ?? ''); ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <h5 class="mb-0 text-primary"><i class="fas fa-cogs me-2"></i> <?php echo __('admin_promo_rules'); ?></h5>
                        <hr>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('admin_promo_discount'); ?></label>
                        <input type="number" name="discount_percent" class="form-control" min="0" max="100" required value="<?php echo sanitize_output($_POST['discount_percent'] ?? '0'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('admin_promo_min_duration'); ?></label>
                        <input type="number" name="min_duration_days" class="form-control" min="1" required value="<?php echo sanitize_output($_POST['min_duration_days'] ?? '1'); ?>" title="Number of days required to trigger this discount">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('admin_promo_req_occasion'); ?></label>
                        <select name="required_occasion" class="form-select">
                            <option value=""><?php echo __('admin_promo_any_occ'); ?></option>
                            <option value="business" <?php echo (($_POST['required_occasion'] ?? '') === 'business') ? 'selected' : ''; ?>>Business Trip</option>
                            <option value="family" <?php echo (($_POST['required_occasion'] ?? '') === 'family') ? 'selected' : ''; ?>>Family Trip</option>
                            <option value="vacation" <?php echo (($_POST['required_occasion'] ?? '') === 'vacation') ? 'selected' : ''; ?>>Vacation</option>
                            <option value="daily" <?php echo (($_POST['required_occasion'] ?? '') === 'daily') ? 'selected' : ''; ?>>Daily Use</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-flex flex-column gap-2 mt-3">
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" name="requires_first_order" id="requires_first_order" <?php echo isset($_POST['requires_first_order']) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="requires_first_order"><?php echo __('admin_promo_first_order'); ?></label>
                            <div class="small text-muted"><?php echo __('admin_promo_first_desc'); ?></div>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex flex-column gap-2 mt-3">
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" name="is_weekend_only" id="is_weekend_only" <?php echo isset($_POST['is_weekend_only']) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold" for="is_weekend_only"><?php echo __('admin_promo_weekend_only'); ?></label>
                            <div class="small text-muted"><?php echo __('admin_promo_week_desc'); ?></div>
                        </div>
                    </div>

                    <div class="col-12 mt-4">
                        <h5 class="mb-0 text-primary"><i class="fas fa-paint-brush me-2"></i> <?php echo __('admin_promo_visuals'); ?></h5>
                        <hr>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('admin_promo_image_en'); ?></label>
                        <input type="file" name="image_en" class="form-control" accept="image/*">
                        <small class="text-muted"><?php echo __('admin_promo_img_en_desc'); ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('admin_promo_image_id'); ?></label>
                        <input type="file" name="image_id" class="form-control" accept="image/*">
                        <small class="text-muted"><?php echo __('admin_promo_img_id_desc'); ?></small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('admin_promo_banner_color'); ?></label>
                        <input type="color" name="banner_color" class="form-control form-control-color w-100" value="<?php echo sanitize_output($_POST['banner_color'] ?? '#667eea'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('admin_promo_icon'); ?></label>
                        <input type="text" name="icon" class="form-control" value="<?php echo sanitize_output($_POST['icon'] ?? 'fas fa-tag'); ?>" placeholder="e.g. fas fa-star">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('admin_promo_sort_order'); ?></label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo sanitize_output($_POST['sort_order'] ?? '0'); ?>" title="Lower numbers appear first on the homepage">
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label"><?php echo __('admin_promo_description'); ?></label>
                        <textarea name="description" class="form-control" rows="3"><?php echo sanitize_output($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="col-12 d-flex align-items-center mt-3 p-3 bg-light rounded">
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label fw-bold text-success" for="is_active"><?php echo __('admin_promo_active'); ?></label>
                        </div>
                    </div>

                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save me-2"></i> <?php echo __('admin_promo_save'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>