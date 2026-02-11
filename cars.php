<?php
$page_title = "Cars";
require_once __DIR__ . '/includes/header.php';

// Get all brands for filter dropdown
$stmt = $conn->prepare("SELECT * FROM car_brands ORDER BY name ASC");
$stmt->execute();
$brands = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container mt-4">

    <h2 class="mb-4"><i class="fas fa-car"></i> Available Cars</h2>

    <!-- FILTER FORM -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">

                <!-- BRAND -->
                <div class="col-md-3">
                    <label class="form-label">Brand</label>
                    <select name="brand" class="form-select">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo (int)$brand['id']; ?>"><?php echo sanitize_output($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- SEATS -->
                <div class="col-md-2">
                    <label class="form-label">Seats</label>
                    <select name="seats" class="form-select">
                        <option value="">Any</option>
                        <option value="2">2 Seats</option>
                        <option value="4">4 Seats</option>
                        <option value="5">5 Seats</option>
                        <option value="7">7 Seats</option>
                    </select>
                </div>

                <!-- TRANSMISSION -->
                <div class="col-md-2">
                    <label class="form-label">Transmission</label>
                    <select name="transmission" class="form-select">
                        <option value="">Any</option>
                        <option value="automatic">Automatic</option>
                        <option value="manual">Manual</option>
                    </select>
                </div>

                <!-- PRICE RANGE -->
                <div class="col-md-3">
                    <label class="form-label">Price Range (per day)</label>
                    <select name="price_range" class="form-select">
                        <option value="">Any Price</option>
                        <option value="0-300000">Under Rp 300,000</option>
                        <option value="300000-500000">Rp 300,000 - Rp 500,000</option>
                        <option value="500000-1000000">Rp 500,000 - Rp 1,000,000</option>
                        <option value="1000000-99999999">Over Rp 1,000,000</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
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
            <p class="mt-3 text-muted">Loading amazing cars for you...</p>
        </div>
    </div>

</div>

<script src="<?php echo SITE_URL; ?>/assets/js/filter.js"></script>

<script>
// Auto load cars when page opens
fetch('<?php echo SITE_URL; ?>/api/cars.php')
    .then(res => res.json())
    .then(data => renderCars(data))
    .catch(err => {
        document.getElementById('carsContainer').innerHTML = 
            '<div class="col-12 text-center py-5"><p class="text-danger">Failed to load cars. Please refresh the page.</p></div>';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
