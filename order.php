<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('confirm_rental');
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

// Check if this is the user's first order
$stmt_first = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE user_id = ? AND status IN ('approved','completed')");
$stmt_first->bind_param("i", $_SESSION['user_id']);
$stmt_first->execute();
$is_first_order = $stmt_first->get_result()->fetch_assoc()['cnt'] == 0;
$stmt_first->close();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="card-title mb-4"><i class="fas fa-shopping-cart"></i> <?php echo __('confirm_rental'); ?></h3>
                
                <div class="alert alert-info mb-4">
                    <strong><i class="fas fa-car"></i> <?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></strong><br>
                    <small><?php echo ucfirst(sanitize_output($car['transmission'])); ?> | <?php echo (int)$car['seats']; ?> <?php echo __('seats'); ?></small><br>
                    <strong class="text-primary"><?php echo format_currency($car['price_per_day']); ?> <?php echo __('per_day'); ?></strong>
                </div>

                <form action="<?php echo SITE_URL; ?>/api/orders.php" method="POST" id="orderForm">
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="car_id" value="<?php echo (int)$car['id']; ?>">
                    <input type="hidden" id="price_per_day" value="<?php echo (int)$car['price_per_day']; ?>">

                    <div class="mb-3">
                        <label for="rental_start_date" class="form-label"><?php echo __('start_date'); ?> <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="rental_start_date" name="rental_start_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="duration" class="form-label"><?php echo __('duration_days'); ?> <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration" name="duration_days" min="1" max="30" placeholder="<?php echo __('duration_placeholder'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="delivery_option" class="form-label"><?php echo __('delivery_option'); ?></label>
                        <select class="form-select" id="delivery_option" name="delivery_option">
                            <option value="pickup"><?php echo __('pickup_showroom'); ?></option>
                            <option value="delivery"><?php echo __('deliver_address'); ?></option>
                        </select>
                    </div>

                    <div class="mb-3" id="addressField" style="display: none;">
                        <label for="delivery_address" class="form-label"><?php echo __('delivery_address'); ?></label>
                        <textarea class="form-control" id="delivery_address" name="delivery_address" rows="2" placeholder="<?php echo __('delivery_addr_placeholder'); ?>"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="rental_occasion" class="form-label"><?php echo __('rental_occasion'); ?></label>
                        <select class="form-select" id="rental_occasion" name="rental_occasion">
                            <option value=""><?php echo __('occasion_placeholder'); ?></option>
                            <option value="business"><?php echo __('occasion_business'); ?></option>
                            <option value="family"><?php echo __('occasion_family'); ?></option>
                            <option value="vacation"><?php echo __('occasion_vacation'); ?></option>
                            <option value="daily"><?php echo __('occasion_daily'); ?></option>
                            <option value="other"><?php echo __('occasion_other'); ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label"><?php echo __('notes'); ?></label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="<?php echo __('notes_placeholder'); ?>"></textarea>
                    </div>

                    <!-- Discount Preview -->
                    <div id="discountPreview" class="mb-3" style="display:none;">
                        <div class="alert alert-success py-2 mb-0">
                            <i class="fas fa-tag"></i> <strong id="discountLabel"></strong>
                            <span id="discountDesc" class="small"></span>
                        </div>
                    </div>

                    <!-- Service Add-ons -->
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-2"><i class="fas fa-plus-circle me-1"></i> <?php echo __('extra_services'); ?></h6>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="has_driver" id="has_driver" value="1">
                                <label class="form-check-label d-flex justify-content-between" for="has_driver">
                                    <span><?php echo __('driver_service'); ?></span>
                                    <span class="text-primary fw-bold">+ <?php echo format_currency(get_site_setting('driver_daily_fee')); ?>/<?php echo trim(__('per_day'), '/ '); ?></span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_tools" id="has_tools" value="1">
                                <label class="form-check-label d-flex justify-content-between" for="has_tools">
                                    <span><?php echo __('tool_kit_service'); ?></span>
                                    <span class="text-primary fw-bold">+ <?php echo format_currency(get_site_setting('tool_kit_fee')); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-secondary text-center mb-3">
                        <div id="originalPriceRow" style="display:none;">
                            <small class="text-muted"><s id="originalPriceDisplay">Rp 0</s></small><br>
                        </div>
                        <strong><?php echo __('estimated_total'); ?>:</strong> <span class="text-primary fs-5" id="total_display">Rp 0</span>
                    </div>

                    <input type="hidden" id="driver_daily_fee" value="<?php echo (int)get_site_setting('driver_daily_fee'); ?>">
                    <input type="hidden" id="tool_kit_fee" value="<?php echo (int)get_site_setting('tool_kit_fee'); ?>">

                    <small class="text-muted d-block mb-3"><i class="fas fa-info-circle"></i> <?php echo __('best_discount_note'); ?></small>

                    <div class="d-grid gap-2">
                        <button type="submit" name="order_type" value="website" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> <?php echo __('confirm_order'); ?>
                        </button>
                        <button type="submit" name="order_type" value="whatsapp" class="btn btn-success btn-lg">
                            <i class="fab fa-whatsapp"></i> <?php echo __('order_via_wa'); ?>
                        </button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>/cars.php" class="text-muted">
                        <i class="fas fa-arrow-left"></i> <?php echo __('back_to_cars'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const durationInput = document.getElementById('duration');
const startDateInput = document.getElementById('rental_start_date');
const occasionSelect = document.getElementById('rental_occasion');
const pricePerDay = parseInt(document.getElementById('price_per_day').value);
const totalDisplay = document.getElementById('total_display');
const originalPriceDisplay = document.getElementById('originalPriceDisplay');
const originalPriceRow = document.getElementById('originalPriceRow');
const discountPreview = document.getElementById('discountPreview');
const discountLabel = document.getElementById('discountLabel');
const discountDesc = document.getElementById('discountDesc');
const isFirstOrder = <?php echo $is_first_order ? 'true' : 'false'; ?>;

const discountDefs = {
    weekend:    { pct: 25, label: '<?php echo __("discount_weekend"); ?>', desc: '<?php echo __("discount_weekend_desc"); ?>' },
    first_order:{ pct: 15, label: '<?php echo __("discount_first_order"); ?>', desc: '<?php echo __("discount_first_order_desc"); ?>' },
    long_rental:{ pct: 20, label: '<?php echo __("discount_long_rental"); ?>', desc: '<?php echo __("discount_long_rental_desc"); ?>' },
    family:     { pct: 10, label: '<?php echo __("discount_family"); ?>', desc: '<?php echo __("discount_family_desc"); ?>' }
};

function isAllWeekend(startDate, days) {
    if (!startDate || days < 1) return false;
    var d = new Date(startDate + 'T00:00:00');
    for (var i = 0; i < days; i++) {
        var day = d.getDay();
        if (day !== 0 && day !== 6) return false;
        d.setDate(d.getDate() + 1);
    }
    return true;
}

function calcDiscount() {
    var days = parseInt(durationInput.value) || 0;
    var startDate = startDateInput.value;
    var occasion = occasionSelect.value;
    var hasDriver = document.getElementById('has_driver').checked;
    var hasTools = document.getElementById('has_tools').checked;
    var driverDailyFee = parseInt(document.getElementById('driver_daily_fee').value);
    var toolKitFee = parseInt(document.getElementById('tool_kit_fee').value);

    if (days < 1) {
        totalDisplay.innerText = 'Rp 0';
        originalPriceRow.style.display = 'none';
        discountPreview.style.display = 'none';
        return;
    }
    
    var originalTotal = days * pricePerDay;
    var bestKey = null, bestPct = 0;

    // Check weekend discount
    if (startDate && isAllWeekend(startDate, days)) {
        bestKey = 'weekend'; bestPct = 25;
    }
    // Check long rental
    if (days >= 7 && discountDefs.long_rental.pct > bestPct) {
        bestKey = 'long_rental'; bestPct = 20;
    }
    // Check first order
    if (isFirstOrder && discountDefs.first_order.pct > bestPct) {
        bestKey = 'first_order'; bestPct = 15;
    }
    // Check family package
    if (occasion === 'family' && discountDefs.family.pct > bestPct) {
        bestKey = 'family'; bestPct = 10;
    }

    var totalAfterDiscount = originalTotal;
    if (bestKey) {
        totalAfterDiscount = Math.round(originalTotal * (1 - bestPct / 100));
        originalPriceDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(originalTotal);
        originalPriceRow.style.display = '';
        discountLabel.innerText = discountDefs[bestKey].label + ' (-' + bestPct + '%)';
        discountDesc.innerText = ' — ' + discountDefs[bestKey].desc;
        discountPreview.style.display = '';
    } else {
        originalPriceRow.style.display = 'none';
        discountPreview.style.display = 'none';
    }

    // Add extra services
    var finalTotal = totalAfterDiscount;
    if (hasDriver) finalTotal += (driverDailyFee * days);
    if (hasTools) finalTotal += toolKitFee;

    totalDisplay.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(finalTotal);
}

durationInput.addEventListener('input', calcDiscount);
startDateInput.addEventListener('change', calcDiscount);
occasionSelect.addEventListener('change', calcDiscount);
document.getElementById('has_driver').addEventListener('change', calcDiscount);
document.getElementById('has_tools').addEventListener('change', calcDiscount);

// Show/hide delivery address field
const deliveryOption = document.getElementById('delivery_option');
const addressField = document.getElementById('addressField');
deliveryOption.addEventListener('change', function() {
    addressField.style.display = this.value === 'delivery' ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>