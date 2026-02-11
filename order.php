<?php
$page_title = 'Rent Car';
require_once __DIR__ . '/includes/header.php';

require_login();

// Get and validate car ID
$car_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$car_id) {
    set_flash_message('danger', 'Invalid car ID.');
    redirect(SITE_URL . '/cars.php');
}

// Get car data using prepared statement
$stmt = $conn->prepare("SELECT c.*, cb.name AS brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.id = ? AND c.is_available = 1");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();
$car = $result->fetch_assoc();
$stmt->close();

if (!$car) {
    set_flash_message('danger', 'Car not found or not available.');
    redirect(SITE_URL . '/cars.php');
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="card-title mb-4"><i class="fas fa-shopping-cart"></i> Confirm Rental</h3>
                
                <div class="alert alert-info mb-4">
                    <strong><i class="fas fa-car"></i> <?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></strong><br>
                    <small><?php echo ucfirst(sanitize_output($car['transmission'])); ?> | <?php echo (int)$car['seats']; ?> Seats</small><br>
                    <strong class="text-primary"><?php echo format_currency($car['price_per_day']); ?> / day</strong>
                </div>

                <form action="<?php echo SITE_URL; ?>/api/orders.php" method="POST" id="orderForm">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="car_id" value="<?php echo (int)$car['id']; ?>">
                    <input type="hidden" id="price_per_day" value="<?php echo (int)$car['price_per_day']; ?>">

                    <div class="mb-3">
                        <label for="rental_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="rental_start_date" name="rental_start_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="duration" class="form-label">Duration (Days) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration" name="duration_days" min="1" max="30" placeholder="Enter number of days..." required>
                    </div>

                    <div class="mb-3">
                        <label for="delivery_option" class="form-label">Delivery Option</label>
                        <select class="form-select" id="delivery_option" name="delivery_option">
                            <option value="pickup">Pick up at Showroom</option>
                            <option value="delivery">Deliver to My Address</option>
                        </select>
                    </div>

                    <div class="mb-3" id="addressField" style="display: none;">
                        <label for="delivery_address" class="form-label">Delivery Address</label>
                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2" placeholder="Enter your delivery address..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special requests..."></textarea>
                    </div>

                    <div class="alert alert-secondary text-center mb-3">
                        <strong>Estimated Total:</strong> <span class="text-primary fs-5" id="total_display">Rp 0</span>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="order_type" value="website" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Confirm Order
                        </button>
                        <button type="submit" name="order_type" value="whatsapp" class="btn btn-success btn-lg">
                            <i class="fab fa-whatsapp"></i> Order via WhatsApp
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>/cars.php" class="text-muted">
                        <i class="fas fa-arrow-left"></i> Back to Cars
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate total price
const durationInput = document.getElementById('duration');
const pricePerDay = parseInt(document.getElementById('price_per_day').value);
const totalDisplay = document.getElementById('total_display');

durationInput.addEventListener('input', function() {
    const days = parseInt(durationInput.value) || 0;
    if (days > 0) {
        const total = days * pricePerDay;
        totalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    } else {
        totalDisplay.innerText = 'Rp 0';
    }
});

// Show/hide delivery address field
const deliveryOption = document.getElementById('delivery_option');
const addressField = document.getElementById('addressField');

deliveryOption.addEventListener('change', function() {
    addressField.style.display = this.value === 'delivery' ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>