<?php
// Admin Car Stock Management
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_manage_stock');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$success_message = '';

$car_id = filter_var($_GET['car_id'] ?? 0, FILTER_VALIDATE_INT);
if ($car_id <= 0) {
    set_flash_message('danger', 'Invalid car ID.');
    redirect(SITE_URL . '/admin/cars.php');
    exit;
}

// Get car info
$stmt = $conn->prepare("SELECT c.*, cb.name as brand_name FROM cars c JOIN car_brands cb ON c.brand_id = cb.id WHERE c.id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    set_flash_message('danger', 'Car not found.');
    redirect(SITE_URL . '/admin/cars.php');
    exit;
}

// Handle add stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security validation failed.';
    } else {
        if ($_POST['action'] === 'add_stock') {
            $plate_number = strtoupper(trim($_POST['plate_number'] ?? ''));
            $stock_notes = trim($_POST['stock_notes'] ?? '');
            
            if (empty($plate_number)) {
                $error_message = 'Plate number is required.';
            } else {
                // Check uniqueness
                $check = $conn->prepare("SELECT id FROM car_stock WHERE plate_number = ?");
                $check->bind_param("s", $plate_number);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error_message = 'Plate number already exists in the system.';
                } else {
                    $stmt = $conn->prepare("INSERT INTO car_stock (car_id, plate_number, status, notes) VALUES (?, ?, 'available', ?)");
                    $stmt->bind_param("iss", $car_id, $plate_number, $stock_notes);
                    if ($stmt->execute()) {
                        $success_message = "Stock unit $plate_number added successfully.";
                    } else {
                        $error_message = 'Failed to add stock unit.';
                    }
                    $stmt->close();
                }
                $check->close();
            }
        } elseif ($_POST['action'] === 'update_status') {
            $stock_id = filter_var($_POST['stock_id'] ?? 0, FILTER_VALIDATE_INT);
            $new_status = $_POST['new_status'] ?? '';
            
            if (!in_array($new_status, ['available', 'rented', 'maintenance'])) {
                $error_message = 'Invalid status.';
            } else {
                $stmt = $conn->prepare("UPDATE car_stock SET status = ? WHERE id = ? AND car_id = ?");
                $stmt->bind_param("sii", $new_status, $stock_id, $car_id);
                if ($stmt->execute()) {
                    $success_message = "Stock status updated to $new_status.";
                } else {
                    $error_message = 'Failed to update status.';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete_stock') {
            $stock_id = filter_var($_POST['stock_id'] ?? 0, FILTER_VALIDATE_INT);
            
            // Check if stock unit has active orders
            $check = $conn->prepare("SELECT id FROM orders WHERE car_stock_id = ? AND status IN ('pending', 'approved')");
            $check->bind_param("i", $stock_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error_message = 'Cannot delete: this unit has active orders.';
            } else {
                $stmt = $conn->prepare("DELETE FROM car_stock WHERE id = ? AND car_id = ?");
                $stmt->bind_param("ii", $stock_id, $car_id);
                if ($stmt->execute()) {
                    $success_message = 'Stock unit deleted.';
                } else {
                    $error_message = 'Failed to delete stock unit.';
                }
                $stmt->close();
            }
            $check->close();
        }
    }
}

// Get all stock units for this car
$stmt = $conn->prepare("SELECT cs.*, 
        (SELECT o.id FROM orders o WHERE o.car_stock_id = cs.id AND o.status IN ('pending', 'approved') ORDER BY o.created_at DESC LIMIT 1) as active_order_id,
        (SELECT o.rental_end_date FROM orders o WHERE o.car_stock_id = cs.id AND o.status IN ('pending', 'approved') ORDER BY o.created_at DESC LIMIT 1) as rental_end_date
    FROM car_stock cs WHERE cs.car_id = ? ORDER BY cs.status, cs.plate_number");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$stock_units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$csrf_token = generate_csrf_token();
?>

<div class="admin-content">
    <div class="content-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-boxes"></i> <?php echo __('admin_stock_for'); ?> <?php echo sanitize_output($car['brand_name'] . ' ' . $car['name']); ?></h1>
            <p><?php echo __('admin_manage_stock_units_title'); ?></p>
        </div>
        <a href="<?php echo SITE_URL; ?>/admin/cars.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo __('admin_back_to_cars'); ?>
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> <?php echo sanitize_output($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo sanitize_output($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php display_flash_message(); ?>

    <div class="row">
        <!-- Stock List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Stock Units (<?php echo count($stock_units); ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th><?php echo __('admin_plate_number'); ?></th>
                                <th><?php echo __('admin_status'); ?></th>
                                <th><?php echo __('admin_action'); ?></th>
                                <th><?php echo __('admin_notes'); ?></th>
                                <th><?php echo __('admin_actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stock_units)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox"></i> <?php echo __('admin_no_stock_units'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($stock_units as $unit): ?>
                                    <tr>
                                        <td>#<?php echo $unit['id']; ?></td>
                                        <td><strong><?php echo sanitize_output($unit['plate_number']); ?></strong></td>
                                        <td>
                                            <?php if ($unit['status'] === 'available'): ?>
                                                <span class="badge bg-success"><?php echo __('admin_status_available'); ?></span>
                                            <?php elseif ($unit['status'] === 'rented'): ?>
                                                <span class="badge bg-warning"><?php echo __('admin_status_rented'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo __('admin_status_maintenance'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($unit['active_order_id']): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $unit['active_order_id']; ?>" class="text-primary">
                                                    Order #<?php echo $unit['active_order_id']; ?>
                                                </a>
                                                <br><small class="text-muted">Until: <?php echo format_date($unit['rental_end_date']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo sanitize_output($unit['notes'] ?? ''); ?></small></td>
                                        <td>
                                            <!-- Status toggle buttons -->
                                            <?php if ($unit['status'] === 'rented' && !$unit['active_order_id']): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                    <input type="hidden" name="new_status" value="available">
                                                    <?php echo csrf_input_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-success" title="Mark as Available (Returned)">
                                                        <i class="fas fa-undo"></i> Return
                                                    </button>
                                                </form>
                                            <?php elseif ($unit['status'] === 'available'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                    <input type="hidden" name="new_status" value="maintenance">
                                                    <?php echo csrf_input_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Set to Maintenance">
                                                        <i class="fas fa-tools"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($unit['status'] === 'maintenance'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                    <input type="hidden" name="new_status" value="available">
                                                    <?php echo csrf_input_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-success" title="Mark as Available">
                                                        <i class="fas fa-check"></i> Ready
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($unit['status'] !== 'rented'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete unit <?php echo sanitize_output($unit['plate_number']); ?>?');">
                                                    <input type="hidden" name="action" value="delete_stock">
                                                    <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                    <?php echo csrf_input_field(); ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Stock Form -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> <?php echo __('admin_add_stock_unit'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_stock">
                        <?php echo csrf_input_field(); ?>
                        
                        <div class="mb-3">
                            <label for="plate_number" class="form-label"><?php echo __('admin_plate_number'); ?> *</label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" 
                                placeholder="B 1234 XYZ" required style="text-transform: uppercase;">
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="stock_notes" name="stock_notes" rows="2" placeholder="Color variant, condition, etc."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> <?php echo __('admin_add_unit'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stock Summary -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Stock Summary</h5>
                </div>
                <div class="card-body">
                    <?php
                    $avail = 0; $rent = 0; $maint = 0;
                    foreach ($stock_units as $u) {
                        if ($u['status'] === 'available') $avail++;
                        elseif ($u['status'] === 'rented') $rent++;
                        else $maint++;
                    }
                    ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-circle text-success"></i> <?php echo __('admin_status_available'); ?></span>
                        <strong><?php echo $avail; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-circle text-warning"></i> <?php echo __('admin_status_rented'); ?></span>
                        <strong><?php echo $rent; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-circle text-secondary"></i> <?php echo __('admin_status_maintenance'); ?></span>
                        <strong><?php echo $maint; ?></strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span><strong>Total</strong></span>
                        <strong><?php echo count($stock_units); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Status Guide -->
            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?php echo __('admin_status_guide'); ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><span class="badge bg-success"><?php echo __('admin_status_available'); ?></span> — <?php echo __('admin_ready_to_rent'); ?></p>
                    <p class="mb-2"><span class="badge bg-warning"><?php echo __('admin_status_rented'); ?></span> — <?php echo __('admin_with_customer'); ?></p>
                    <p class="mb-0"><span class="badge bg-secondary"><?php echo __('admin_status_maintenance'); ?></span> — <?php echo __('admin_under_repair'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
