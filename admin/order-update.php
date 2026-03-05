<?php
// Admin Order Update Status Page
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_update_order_status');

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
                
                // If completed or cancelled, mark the stock unit as available again
                if (in_array($new_status, ['completed', 'cancelled'])) {
                    $stock_stmt = $conn->prepare("UPDATE car_stock SET status = 'available' WHERE id = (SELECT car_stock_id FROM orders WHERE id = ?)");
                    $stock_stmt->bind_param("i", $order_id);
                    $stock_stmt->execute();
                    $stock_stmt->close();
                }
                // If approved, ensure stock unit is marked as rented
                if ($new_status === 'approved') {
                    $stock_stmt = $conn->prepare("UPDATE car_stock SET status = 'rented' WHERE id = (SELECT car_stock_id FROM orders WHERE id = ?)");
                    $stock_stmt->bind_param("i", $order_id);
                    $stock_stmt->execute();
                    $stock_stmt->close();
                }
                
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
            <h1><i class="fas fa-edit"></i> <?php echo __('admin_update_order_status'); ?></h1>
            <p><?php echo __('admin_change_status'); ?> #<?php echo $order['id']; ?></p>
        </div>
        <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('admin_back_to_orders'); ?>
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
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('admin_order_detail'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted"><?php echo __('admin_customer'); ?></label>
                            <p><?php echo sanitize_output($order_info['user_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted"><?php echo __('admin_car'); ?></label>
                            <p><?php echo sanitize_output($order_info['car_name']); ?></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label text-muted"><?php echo __('admin_start_date'); ?></label>
                            <p><?php echo format_date($order_info['rental_start_date']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted"><?php echo __('admin_end_date'); ?></label>
                            <p><?php echo format_date($order_info['rental_end_date']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Update Form -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-tasks"></i> <?php echo __('admin_update_order'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="status" class="form-label"><?php echo __('admin_new_status'); ?> *</label>
                            <p class="text-muted mb-2">Current Status: <strong><?php echo get_status_badge($order['status']); ?></strong></p>
                            <select class="form-select" id="status" name="status" required>
                                <option value=""><?php echo __('admin_select_status'); ?></option>
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
                            <label for="update_notes" class="form-label"><?php echo __('admin_update_notes'); ?></label>
                            <textarea class="form-control" id="update_notes" name="update_notes" rows="4" placeholder="Add any notes about this status update..."></textarea>
                            <small class="text-muted">These notes will be appended to the order notes.</small>
                        </div>

                        <?php echo csrf_input_field(); ?>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> <?php echo __('admin_confirm_update'); ?>
                            </button>
                            <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> <?php echo __('admin_cancel'); ?>
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
                    <h5 class="mb-0"><i class="fas fa-circle-info"></i> <?php echo __('admin_status_guide_title'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="mb-2"><strong class="badge bg-warning"><?php echo __('admin_pending'); ?></strong></p>
                        <small class="text-muted"><?php echo __('admin_pending_desc'); ?></small>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <p class="mb-2"><strong class="badge bg-success"><?php echo __('admin_approved'); ?></strong></p>
                        <small class="text-muted"><?php echo __('admin_approved_desc'); ?></small>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <p class="mb-2"><strong class="badge bg-danger"><?php echo __('admin_cancelled'); ?></strong></p>
                        <small class="text-muted"><?php echo __('admin_cancelled_desc'); ?></small>
                    </div>
                    <hr>
                    <div>
                        <p class="mb-2"><strong class="badge bg-info"><?php echo __('admin_completed'); ?></strong></p>
                        <small class="text-muted"><?php echo __('admin_completed_desc'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>