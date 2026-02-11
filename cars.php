<?php
$page_title = "Cars";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">

    <h2 class="mb-4">Available Cars</h2>

    <!-- FILTER FORM -->
    <form id="filterForm" class="row g-3 mb-4">

        <!-- BRAND -->
        <div class="col-md-3">
            <select name="brand" class="form-control">
                <option value="" disabled selected>Brand</option>
                <?php
                require_once __DIR__ . '/config/database.php';
                $brands = $conn->query("SELECT * FROM car_brands");
                while($b = $brands->fetch_assoc()){
                    echo "<option value='{$b['id']}'>{$b['name']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- SEATS -->
        <div class="col-md-2">
            <select name="seats" class="form-control">
                <option value="" disabled selected>Seats</option>
                <option value="2">2 Seats</option>
                <option value="4">4 Seats</option>
                <option value="5">5 Seats</option>
                <option value="7">7 Seats</option>
            </select>
        </div>

        <!-- TRANSMISSION -->
        <div class="col-md-2">
            <select name="transmission" class="form-control">
                <option value="" disabled selected>Transmission</option>
                <option value="Automatic">Automatic</option>
                <option value="Manual">Manual</option>
            </select>
        </div>

        <!-- PRICE RANGE -->
        <div class="col-md-3">
            <select name="price_range" class="form-control">
                <option value="" disabled selected>Price Range</option>
                <option value="0-50">$0 - $50</option>
                <option value="50-100">$50 - $100</option>
                <option value="100-200">$100 - $200</option>
                <option value="200-9999">$200+</option>
            </select>
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

    </form>

    <!-- CARS LIST -->
    <div class="row" id="carsContainer"></div>

</div>

<script src="assets/js/filter.js"></script>

<script>
/* AUTO LOAD CARS SAAT HALAMAN DIBUKA */
fetch('api/cars.php')
    .then(res => res.json())
    .then(data => renderCars(data));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
