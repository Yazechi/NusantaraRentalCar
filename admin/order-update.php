<?php
// Admin Order Update Status Page
$page_title = 'Update Order Status';

$project_root = dirname(__DIR__);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$success_message = '';

// Get order ID
$order_id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if ($order_id <= 0) {
    set_flash_message('danger', 'Invalid order ID.');
    redirect(SITE_URL . '/admin/orders.php');
    exit;
}

// Get order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    set_flash_message('danger', 'Order not found.');
    redirect(SITE_URL . '/admin/orders.php');
    exit;
}

// Valid status transitions
$valid_statuses = ['pending', 'approved', 'cancelled', 'completed'];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security validation failed.';
    } else {
        $new_status = trim($_POST['status'] ?? '');
        $update_notes = trim($_POST['update_notes'] ?? '');

        if (!in_array($new_status, $valid_statuses)) {
            $error_message = 'Invalid status.';
        } elseif ($new_status === $order['status']) {
            $error_message = 'Please select a different status.';
        } else {
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_status, $update_notes, $order_id);

            if ($stmt->execute()) {
                $stmt->close();
                set_flash_message('success', 'Order status updated successfully.');
                redirect(SITE_URL . '/admin/order-detail.php?id=' . $order_id);
                exit;
            } else {
                $error_message = 'Failed to update order status.';
                $stmt->close();
            }
        }
    }
}

$csrf_token = generate_csrf_token();

// Get car and customer info
$order_query = "
    SELECT 
        c.name as car_name,
        u.name as user_name,
        o.rental_start_date,
        o.rental_end_date
    FROM orders o
    JOIN cars c ON o.car_id = c.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<div class="admin-content">
    <div class="content-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-edit"></i> Update Order Status</h1>
            <p>Change the status of order #<?php echo $order['id']; ?></p>
        </div>
        <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Order
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo sanitize_output($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Customer</label>
                            <p><?php echo sanitize_output($order_info['user_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Car</label>
                            <p><?php echo sanitize_output($order_info['car_name']); ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Rental Start</label>
                            <p><?php echo format_date($order_info['rental_start_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Rental End</label>
                            <p><?php echo format_date($order_info['rental_end_date']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Update Form -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Update Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status *</label>
                            <p class="text-muted mb-2">Current Status: <strong><?php echo get_status_badge($order['status']); ?></strong></p>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select new status</option>
                                <?php foreach ($valid_statuses as $st): ?>
                                    <?php if ($st !== $order['status']): ?>
                                        <option value="<?php echo $st; ?>">
                                            <?php echo ucfirst($st); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="update_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="update_notes" name="update_notes" rows="4" placeholder="Add any notes about this status update..."></textarea>
                            <small class="text-muted">These notes will be appended to the order notes.</small>
                        </div>

                        <?php echo csrf_input_field(); ?>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Update Status
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Status Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-circle-info"></i> Status Guide</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="mb-2"><strong class="badge bg-warning">Pending</strong></p>
                        <small class="text-muted">Initial status when order is first placed.</small>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <p class="mb-2"><strong class="badge bg-success">Approved</strong></p>
                        <small class="text-muted">Order has been approved and is ready for rental.</small>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <p class="mb-2"><strong class="badge bg-danger">Cancelled</strong></p>
                        <small class="text-muted">Order has been cancelled.</small>
                    </div>
                    <hr>
                    <div>
                        <p class="mb-2"><strong class="badge bg-info">Completed</strong></p>
                        <small class="text-muted">Rental has been completed.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>