<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('nav_cars');
require_once __DIR__ . '/includes/header.php';

// Get all brands for filter dropdown
$stmt = $conn->prepare("SELECT * FROM car_brands ORDER BY name ASC");
$stmt->execute();
$brands = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all car types for filter dropdown
$stmt = $conn->prepare("SELECT * FROM car_types ORDER BY name ASC");
$stmt->execute();
$car_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all rental goals for filter dropdown
$stmt = $conn->prepare("SELECT * FROM rental_goals ORDER BY name ASC");
$stmt->execute();
$rental_goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Pre-selected filters from URL
$sel_type = filter_input(INPUT_GET, 'type', FILTER_VALIDATE_INT) ?: '';
$sel_goal = filter_input(INPUT_GET, 'goal', FILTER_VALIDATE_INT) ?: '';
?>

<div class="container mt-4">

    <h2 class="mb-4"><i class="fas fa-car"></i> <?php echo __('available_cars'); ?></h2>

    <!-- FILTER FORM -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">

                <!-- BRAND -->
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('filter_brand'); ?></label>
                    <select name="brand" class="form-select">
                        <option value=""><?php echo __('filter_all_brands'); ?></option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo (int)$brand['id']; ?>"><?php echo sanitize_output($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- TYPE -->
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('filter_type'); ?></label>
                    <select name="type" class="form-select">
                        <option value=""><?php echo __('filter_all_types'); ?></option>
                        <?php foreach ($car_types as $type): ?>
                            <option value="<?php echo (int)$type['id']; ?>" <?php echo $sel_type == $type['id'] ? 'selected' : ''; ?>><?php echo sanitize_output($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- SEATS -->
                <div class="col-md-1">
                    <label class="form-label"><?php echo __('filter_seats'); ?></label>
                    <select name="seats" class="form-select">
                        <option value=""><?php echo __('filter_any'); ?></option>
                        <option value="2">2</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="7">7</option>
                    </select>
                </div>

                <!-- TRANSMISSION -->
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('filter_transmission'); ?></label>
                    <select name="transmission" class="form-select">
                        <option value=""><?php echo __('filter_any'); ?></option>
                        <option value="automatic"><?php echo __('automatic'); ?></option>
                        <option value="manual"><?php echo __('manual'); ?></option>
                    </select>
                </div>

                <!-- PRICE RANGE -->
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('filter_price'); ?></label>
                    <select name="price_range" class="form-select">
                        <option value=""><?php echo __('filter_any_price'); ?></option>
                        <option value="0-300000">&lt; Rp 300K</option>
                        <option value="300000-500000">Rp 300K - 500K</option>
                        <option value="500000-1000000">Rp 500K - 1M</option>
                        <option value="1000000-99999999">&gt; Rp 1M</option>
                    </select>
                </div>

                <!-- RENTAL GOAL -->
                <div class="col-md-2">
                    <label class="form-label"><?php echo __('filter_goal'); ?></label>
                    <select name="goal" class="form-select">
                        <option value=""><?php echo __('filter_all_goals'); ?></option>
                        <?php foreach ($rental_goals as $goal): ?>
                            <option value="<?php echo (int)$goal['id']; ?>" <?php echo $sel_goal == $goal['id'] ? 'selected' : ''; ?>><?php echo sanitize_output($goal['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i></button>
                </div>

            </form>
        </div>
    </div>

    <!-- CARS LIST -->
    <div class="row" id="carsContainer">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted"><?php echo __('loading_cars'); ?></p>
        </div>
    </div>

</div>

<script src="<?php echo SITE_URL; ?>/assets/js/filter.js"></script>

<script>
// Build initial URL with pre-selected filters
let initialUrl = '<?php echo SITE_URL; ?>/api/cars.php';
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('type') || urlParams.get('goal')) {
    initialUrl = '<?php echo SITE_URL; ?>/api/filter.php?' + urlParams.toString();
}

fetch(initialUrl)
    .then(res => res.json())
    .then(data => renderCars(data))
    .catch(err => {
        document.getElementById('carsContainer').innerHTML = 
            '<div class="col-12 text-center py-5"><p class="text-danger">Failed to load cars. Please refresh the page.</p></div>';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
