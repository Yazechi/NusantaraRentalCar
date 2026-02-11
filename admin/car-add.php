<?php
// Admin Add Car Page
$page_title = 'Add New Car';

$project_root = dirname(__DIR__);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$success_message = '';

// Get car brands
$brands_result = $conn->query("SELECT id, name FROM car_brands ORDER BY name ASC");
$brands = $brands_result->fetch_all(MYSQLI_ASSOC);

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
        $license_plate = trim($_POST['license_plate'] ?? '');
        $seats = filter_var($_POST['seats'] ?? 0, FILTER_VALIDATE_INT);
        $transmission = trim($_POST['transmission'] ?? '');
        $fuel_type = trim($_POST['fuel_type'] ?? '');
        $price_per_day = filter_var($_POST['price_per_day'] ?? 0, FILTER_VALIDATE_FLOAT);
        $description = trim($_POST['description'] ?? '');
        $specifications = trim($_POST['specifications'] ?? '');

        // Validasi
        $errors = [];
        if ($brand_id <= 0) $errors[] = 'Please select a brand.';
        if (empty($name)) $errors[] = 'Car name is required.';
        if ($year < 1900 || $year > date('Y') + 1) $errors[] = 'Invalid year.';
        if (empty($license_plate)) $errors[] = 'License plate is required.';
        if ($seats <= 0) $errors[] = 'Seats must be greater than 0.';
        if (!in_array($transmission, ['manual', 'automatic'])) $errors[] = 'Invalid transmission type.';
        if (!in_array($fuel_type, ['petrol', 'diesel', 'electric', 'hybrid'])) $errors[] = 'Invalid fuel type.';
        if ($price_per_day <= 0) $errors[] = 'Price must be greater than 0.';

        // Check license plate uniqueness
        $stmt = $conn->prepare("SELECT id FROM cars WHERE license_plate = ?");
        $stmt->bind_param("s", $license_plate);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'License plate already exists.';
        }
        $stmt->close();

        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        } else {
            // Insert car
            $stmt = $conn->prepare("
                INSERT INTO cars (brand_id, name, model, year, license_plate, seats, transmission, fuel_type, price_per_day, description, specifications, is_available)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param("issisisssss", $brand_id, $name, $model, $year, $license_plate, $seats, $transmission, $fuel_type, $price_per_day, $description, $specifications);

            if ($stmt->execute()) {
                $car_id = $stmt->insert_id;
                $stmt->close();

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

                set_flash_message('success', 'Car added successfully.');
                redirect(SITE_URL . '/admin/cars.php');
                exit;
            } else {
                $error_message = 'Failed to add car. Please try again.';
                $stmt->close();
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-plus"></i> Add New Car</h1>
        <p>Fill in all the details to add a new car to the system.</p>
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
                        <label for="brand_id" class="form-label">Brand *</label>
                        <select class="form-select" id="brand_id" name="brand_id" required>
                            <option value="">Select a brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>">
                                    <?php echo sanitize_output($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Name -->
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Car Name *</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="e.g. Avanza" required>
                    </div>

                    <!-- Model -->
                    <div class="col-md-6 mb-3">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" class="form-control" id="model" name="model" placeholder="e.g. 1.5 S MT">
                    </div>

                    <!-- Year -->
                    <div class="col-md-6 mb-3">
                        <label for="year" class="form-label">Year *</label>
                        <input type="number" class="form-control" id="year" name="year" min="1900" max="2099" placeholder="2024" required>
                    </div>

                    <!-- License Plate -->
                    <div class="col-md-6 mb-3">
                        <label for="license_plate" class="form-label">License Plate *</label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" placeholder="e.g. B 1234 ABC" required>
                    </div>

                    <!-- Seats -->
                    <div class="col-md-6 mb-3">
                        <label for="seats" class="form-label">Seats *</label>
                        <input type="number" class="form-control" id="seats" name="seats" min="1" max="8" placeholder="5" required>
                    </div>

                    <!-- Transmission -->
                    <div class="col-md-6 mb-3">
                        <label for="transmission" class="form-label">Transmission *</label>
                        <select class="form-select" id="transmission" name="transmission" required>
                            <option value="">Select transmission</option>
                            <option value="manual">Manual</option>
                            <option value="automatic">Automatic</option>
                        </select>
                    </div>

                    <!-- Fuel Type -->
                    <div class="col-md-6 mb-3">
                        <label for="fuel_type" class="form-label">Fuel Type *</label>
                        <select class="form-select" id="fuel_type" name="fuel_type" required>
                            <option value="">Select fuel type</option>
                            <option value="petrol">Petrol</option>
                            <option value="diesel">Diesel</option>
                            <option value="electric">Electric</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>

                    <!-- Price Per Day -->
                    <div class="col-md-6 mb-3">
                        <label for="price_per_day" class="form-label">Price Per Day (Rp) *</label>
                        <input type="number" class="form-control" id="price_per_day" name="price_per_day" step="0.01" min="0" placeholder="500000" required>
                    </div>

                    <!-- Image -->
                    <div class="col-md-6 mb-3">
                        <label for="image_main" class="form-label">Main Image (JPG, PNG, Max 2MB)</label>
                        <input type="file" class="form-control" id="image_main" name="image_main" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <!-- Description -->
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Add detailed description about this car"></textarea>
                    </div>

                    <!-- Specifications -->
                    <div class="col-12 mb-3">
                        <label for="specifications" class="form-label">Specifications (JSON)</label>
                        <textarea class="form-control" id="specifications" name="specifications" rows="3" placeholder='{"color":"Black","ac":"Yes"}'></textarea>
                    </div>
                </div>

                <?php echo csrf_input_field(); ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Add Car
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/cars.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>