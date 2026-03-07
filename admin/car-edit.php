<?php
// Admin Edit Car Page
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_edit') . ' ' . __('admin_car');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$car = null;

// Get car ID from URL
$car_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if ($car_id <= 0) {
    set_flash_message('danger', 'Invalid car ID.');
    redirect(SITE_URL . '/admin/cars.php');
    exit;
}

// Get car data
$stmt = $conn->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    set_flash_message('danger', 'Car not found.');
    redirect(SITE_URL . '/admin/cars.php');
    exit;
}

// Get car brands
$brands_result = $conn->query("SELECT id, name FROM car_brands ORDER BY name ASC");
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);

// Get car types
$types_result = $conn->query("SELECT id, name FROM car_types ORDER BY name ASC");
$car_types = $types_result->fetch_all(MYSQLI_ASSOC);

// Get rental goals
$goals_result = $conn->query("SELECT id, name FROM rental_goals ORDER BY name ASC");
$rental_goals = $goals_result->fetch_all(MYSQLI_ASSOC);

// Get current car's rental goals
$car_goals = [];
$goals_stmt = $conn->prepare("SELECT rental_goal_id FROM car_rental_goals WHERE car_id = ?");
$goals_stmt->bind_param("i", $car_id);
$goals_stmt->execute();
$goals_res = $goals_stmt->get_result();
while ($row = $goals_res->fetch_assoc()) {
    $car_goals[] = $row['rental_goal_id'];
}
$goals_stmt->close();

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security validation failed.';
    } else {
        // Sanitize input
        $brand_id = filter_var($_POST['brand_id'] ?? 0, FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $year = filter_var($_POST['year'] ?? 0, FILTER_VALIDATE_INT);
        $seats = filter_var($_POST['seats'] ?? 0, FILTER_VALIDATE_INT);
        $transmission = trim($_POST['transmission'] ?? '');
        $fuel_type = trim($_POST['fuel_type'] ?? '');
        $is_electric = isset($_POST['is_electric']) ? 1 : 0;
        if ($is_electric) $fuel_type = 'electric';
        $color = trim($_POST['color'] ?? '');
        $price_per_day = filter_var($_POST['price_per_day'] ?? 0, FILTER_VALIDATE_FLOAT);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');
        $type_id = filter_var($_POST['type_id'] ?? 0, FILTER_VALIDATE_INT) ?: null;
        $discount_percent = filter_var($_POST['discount_percent'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $selected_goals = $_POST['rental_goals'] ?? [];

        // Validasi
        $errors = [];
        if ($brand_id <= 0) $errors[] = __('admin_brand_label') . ' is required.';
        if (empty($name)) $errors[] = __('admin_car_name') . ' is required.';
        if ($year < 1900 || $year > date('Y') + 1) $errors[] = 'Invalid year.';
        if ($seats <= 0) $errors[] = __('admin_seats_label') . ' must be greater than 0.';
        if (!in_array($transmission, ['manual', 'automatic'])) $errors[] = 'Invalid transmission type.';
        if (!$is_electric && !in_array($fuel_type, ['pertalite', 'pertamax', 'pertamax_turbo', 'solar', 'dexlite', 'pertamina_dex', 'hybrid', 'electric'])) $errors[] = 'Invalid fuel type.';
        if ($price_per_day <= 0) $errors[] = 'Price must be greater than 0.';
        if ($discount_percent < 0 || $discount_percent > 100) $errors[] = 'Discount must be between 0-100%.';

        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            // Update car
            $stmt = $conn->prepare("
                UPDATE cars SET 
                    brand_id = ?, type_id = ?, name = ?, model = ?, year = ?, 
                    seats = ?, transmission = ?, fuel_type = ?, is_electric = ?, color = ?,
                    price_per_day = ?, is_available = ?, description = ?,
                    discount_percent = ?, is_featured = ?
                WHERE id = ?
            ");
            $stmt->bind_param("iississsisdssiii", $brand_id, $type_id, $name, $model, $year, $seats, $transmission, $fuel_type, $is_electric, $color, $price_per_day, $is_available, $description, $discount_percent, $is_featured, $car_id);

            if ($stmt->execute()) {
                $stmt->close();

                // Update rental goals
                $conn->query("DELETE FROM car_rental_goals WHERE car_id = $car_id");
                if (!empty($selected_goals)) {
                    $goal_stmt = $conn->prepare("INSERT INTO car_rental_goals (car_id, rental_goal_id) VALUES (?, ?)");
                    foreach ($selected_goals as $goal_id) {
                        $gid = (int)$goal_id;
                        $goal_stmt->bind_param("ii", $car_id, $gid);
                        $goal_stmt->execute();
                    }
                    $goal_stmt->close();
                }

                // Handle image upload
                if (isset($_FILES['image_main']) && $_FILES['image_main']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = upload_image($_FILES['image_main']);
                    if ($upload_result['success']) {
                        $filename = $upload_result['filename'];
                        $update_stmt = $conn->prepare("UPDATE cars SET image_main = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $filename, $car_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                }

                set_flash_message('success', 'Car updated successfully.');
                redirect(SITE_URL . '/admin/cars.php');
                exit;
            } else {
                $error_message = 'Failed to update car. Please try again.';
                $stmt->close();
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-edit"></i> <?php echo __('admin_edit') . ' ' . __('admin_car'); ?></h1>
        <p><?php echo __('admin_edit_details'); ?></p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Brand -->
                    <div class="col-md-6 mb-3">
                        <label for="brand_id" class="form-label"><?php echo __('admin_brand_label'); ?> *</label>
                        <select class="form-select" id="brand_id" name="brand_id" required>
                            <option value=""><?php echo __('admin_select_type'); ?></option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $brand['id'] == $car['brand_id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize_output($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label"><?php echo __('admin_car_name'); ?> *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize_output($car['name']); ?>" required>
                    </div>

                    <!-- Model -->
                    <div class="col-md-4 mb-3">
                        <label for="model" class="form-label"><?php echo __('admin_model'); ?></label>
                        <input type="text" class="form-control" id="model" name="model" value="<?php echo sanitize_output($car['model']); ?>">
                    </div>

                    <!-- Year -->
                    <div class="col-md-4 mb-3">
                        <label for="year" class="form-label"><?php echo __('admin_year'); ?> *</label>
                        <input type="number" class="form-control" id="year" name="year" min="1900" max="2099" value="<?php echo $car['year']; ?>" required>
                    </div>

                    <!-- Color -->
                    <div class="col-md-4 mb-3">
                        <label for="color" class="form-label"><?php echo __('admin_color'); ?></label>
                        <input type="text" class="form-control" id="color" name="color" value="<?php echo sanitize_output($car['color'] ?? ''); ?>">
                    </div>

                    <!-- Car Type -->
                    <div class="col-md-6 mb-3">
                        <label for="type_id" class="form-label"><?php echo __('admin_car_type'); ?></label>
                        <select class="form-select" id="type_id" name="type_id">
                            <option value=""><?php echo __('admin_select_type'); ?></option>
                            <?php foreach ($car_types as $type): 
                                $type_lang_key = 'type_' . strtolower(str_replace([' ', '-'], '_', $type['name']));
                                $translated_type = __($type_lang_key);
                                $type_name_display = ($translated_type !== $type_lang_key) ? $translated_type : $type['name'];
                            ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo ($car['type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize_output($type_name_display); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Seats -->
                    <div class="col-md-6 mb-3">
                        <label for="seats" class="form-label"><?php echo __('admin_seats_label'); ?> *</label>
                        <input type="number" class="form-control" id="seats" name="seats" min="1" max="50" value="<?php echo $car['seats']; ?>" required>
                    </div>

                    <!-- Transmission -->
                    <div class="col-md-6 mb-3">
                        <label for="transmission" class="form-label"><?php echo __('admin_transmission_label'); ?> *</label>
                        <select class="form-select" id="transmission" name="transmission" required>
                            <option value="manual" <?php echo $car['transmission'] === 'manual' ? 'selected' : ''; ?>><?php echo __('admin_manual'); ?></option>
                            <option value="automatic" <?php echo $car['transmission'] === 'automatic' ? 'selected' : ''; ?>><?php echo __('admin_automatic'); ?></option>
                        </select>
                    </div>

                    <!-- Fuel Type -->
                    <div class="col-md-4 mb-3">
                        <label for="fuel_type" class="form-label"><?php echo __('admin_fuel_type'); ?> *</label>
                        <select class="form-select" id="fuel_type" name="fuel_type" required>
                            <option value="pertalite" <?php echo $car['fuel_type'] === 'pertalite' ? 'selected' : ''; ?>>Pertalite (RON 90)</option>
                            <option value="pertamax" <?php echo $car['fuel_type'] === 'pertamax' ? 'selected' : ''; ?>>Pertamax (RON 92)</option>
                            <option value="pertamax_turbo" <?php echo $car['fuel_type'] === 'pertamax_turbo' ? 'selected' : ''; ?>>Pertamax Turbo (RON 98)</option>
                            <option value="solar" <?php echo $car['fuel_type'] === 'solar' ? 'selected' : ''; ?>>Solar</option>
                            <option value="dexlite" <?php echo $car['fuel_type'] === 'dexlite' ? 'selected' : ''; ?>>Dexlite</option>
                            <option value="pertamina_dex" <?php echo $car['fuel_type'] === 'pertamina_dex' ? 'selected' : ''; ?>>Pertamina Dex</option>
                            <option value="hybrid" <?php echo $car['fuel_type'] === 'hybrid' ? 'selected' : ''; ?>><?php echo __('admin_hybrid'); ?></option>
                        </select>
                    </div>

                    <!-- Electric Vehicle -->
                    <div class="col-md-2 mb-3">
                        <label class="form-label"><?php echo __('admin_electric_vehicle'); ?></label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_electric" name="is_electric" <?php echo !empty($car['is_electric']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_electric">
                                <i class="fas fa-bolt text-success"></i> <?php echo __('admin_electric'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Price Per Day -->
                    <div class="col-md-4 mb-3">
                        <label for="price_per_day" class="form-label"><?php echo __('admin_price_per_day'); ?> *</label>
                        <input type="number" class="form-control" id="price_per_day" name="price_per_day" step="1" min="0" value="<?php echo $car['price_per_day']; ?>" required>
                    </div>

                    <!-- Discount -->
                    <div class="col-md-4 mb-3">
                        <label for="discount_percent" class="form-label"><?php echo __('discount_badge'); ?> (%)</label>
                        <input type="number" class="form-control" id="discount_percent" name="discount_percent" min="0" max="100" value="<?php echo (int)($car['discount_percent'] ?? 0); ?>">
                    </div>

                    <!-- Featured + Available -->
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" <?php echo !empty($car['is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                <i class="fas fa-star text-warning"></i> Featured
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" <?php echo $car['is_available'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_available">
                                <?php echo __('admin_available_for_rent'); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Rental Goals -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo __('admin_rental_goals'); ?></label>
                        <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                            <?php foreach ($rental_goals as $goal): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rental_goals[]" value="<?php echo $goal['id']; ?>" id="goal_<?php echo $goal['id']; ?>"
                                    <?php echo in_array($goal['id'], $car_goals) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="goal_<?php echo $goal['id']; ?>"><?php echo sanitize_output($goal['name']); ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6 mb-3">
                        <label for="image_main" class="form-label"><?php echo __('admin_change_image'); ?></label>
                        <input type="file" class="form-control" id="image_main" name="image_main" accept="image/jpeg,image/png,image/webp">
                        <?php if (!empty($car['image_main'])): ?>
                            <small class="d-block mt-2"><i class="fas fa-image me-1"></i><?php echo __('admin_current_image'); ?>: <?php echo sanitize_output($car['image_main']); ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label"><?php echo __('admin_description'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo sanitize_output($car['description']); ?></textarea>
                    </div>
                </div>

                <?php echo csrf_input_field(); ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> <?php echo __('admin_update_car'); ?>
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/cars.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> <?php echo __('admin_cancel'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const elCheckbox = document.getElementById('is_electric');
    const fuelSelect = document.getElementById('fuel_type');
    function toggleFuel() {
        if (elCheckbox.checked) {
            fuelSelect.disabled = true;
            fuelSelect.required = false;
            fuelSelect.value = '';
            fuelSelect.closest('.col-md-4').style.opacity = '0.5';
        } else {
            fuelSelect.disabled = false;
            fuelSelect.required = true;
            fuelSelect.closest('.col-md-4').style.opacity = '1';
        }
    }
    elCheckbox.addEventListener('change', toggleFuel);
    toggleFuel();
    fuelSelect.closest('form').addEventListener('submit', function() {
        fuelSelect.disabled = false;
    });
});
</script>