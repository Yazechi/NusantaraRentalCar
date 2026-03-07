<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = 'Rate & Review';
require_once __DIR__ . '/includes/header.php';

require_login();

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    redirect(SITE_URL . '/my-orders.php');
}

// Fetch order and car details
$stmt = $conn->prepare("SELECT o.*, c.name as car_name, cb.name as brand_name, c.id as car_id
    FROM orders o 
    JOIN cars c ON o.car_id = c.id 
    JOIN car_brands cb ON c.brand_id = cb.id
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    set_flash_message('danger', 'Order not eligible for review.');
    redirect(SITE_URL . '/my-orders.php');
}

// Check if already reviewed
$stmt = $conn->prepare("SELECT id FROM car_reviews WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    set_flash_message('info', 'You have already reviewed this rental.');
    redirect(SITE_URL . '/my-orders.php');
}
$stmt->close();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4"><i class="fas fa-star text-warning me-2"></i> <?php echo __('rate_your_experience'); ?></h3>
                <p class="text-center text-muted mb-4"><?php echo __('how_was_rental'); ?> <strong><?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?></strong>?</p>

                <form action="<?php echo SITE_URL; ?>/api/review.php" method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                    <input type="hidden" name="car_id" value="<?php echo $order['car_id']; ?>">
                    
                    <div class="text-center mb-4">
                        <div class="rating-input">
                            <input type="radio" name="rating" value="5" id="5" required><label for="5">☆</label>
                            <input type="radio" name="rating" value="4" id="4"><label for="4">☆</label>
                            <input type="radio" name="rating" value="3" id="3"><label for="3">☆</label>
                            <input type="radio" name="rating" value="2" id="2"><label for="2">☆</label>
                            <input type="radio" name="rating" value="1" id="1"><label for="1">☆</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('tell_us_more'); ?></label>
                        <textarea name="comment" class="form-control" rows="4" placeholder="<?php echo __('review_placeholder'); ?>" required></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning btn-lg fw-bold"><?php echo __('submit_review'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
}
.rating-input input {
    display: none;
}
.rating-input label {
    position: relative;
    width: 1.1em;
    font-size: 3rem;
    color: #ffc107;
    cursor: pointer;
}
.rating-input label::before {
    content: "★";
    position: absolute;
    opacity: 0;
}
.rating-input label:hover:before,
.rating-input label:hover ~ label:before,
.rating-input input:checked ~ label:before {
    opacity: 1;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
