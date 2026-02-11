<?php
$page_title = 'My Orders';
require_once __DIR__ . '/includes/header.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get user's orders using prepared statement
$stmt = $conn->prepare("SELECT orders.*, cars.name AS car_name, cars.price_per_day, cb.name AS brand_name
        FROM orders 
        JOIN cars ON orders.car_id = cars.id 
        JOIN car_brands cb ON cars.brand_id = cb.id
        WHERE orders.user_id = ? 
        ORDER BY orders.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h3 class="mb-4"><i class="fas fa-clipboard-list"></i> My Orders</h3>

<?php if (empty($orders)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No orders yet</h5>
            <p class="text-muted">You haven't made any rental orders.</p>
            <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary">
                <i class="fas fa-car"></i> Browse Cars
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Car</th>
                        <th>Rental Period</th>
                        <th>Duration</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Order Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?></strong>
                        </td>
                        <td>
                            <?php echo format_date($order['rental_start_date']); ?> - 
                            <?php echo format_date($order['rental_end_date']); ?>
                        </td>
                        <td><?php echo (int)$order['duration_days']; ?> days</td>
                        <td><strong><?php echo format_currency($order['total_price']); ?></strong></td>
                        <td><?php echo get_status_badge($order['status']); ?></td>
                        <td>
                            <?php if ($order['order_type'] === 'whatsapp'): ?>
                                <span class="badge bg-success"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                            <?php else: ?>
                                <span class="badge bg-primary"><i class="fas fa-globe"></i> Website</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary">
            <i class="fas fa-car"></i> Rent Another Car
        </a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>