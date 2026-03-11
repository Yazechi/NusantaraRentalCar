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
            $color = trim($_POST['color'] ?? '');
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
                    $image_filename = null;
                    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = upload_image($_FILES['image_url']);
                        if ($upload_result['success']) {
                            $image_filename = $upload_result['filename'];
                        } else {
                            $error_message = 'Image upload failed.';
                        }
                    }

                    if (empty($error_message)) {
                        $stmt = $conn->prepare("INSERT INTO car_stock (car_id, plate_number, color, image_url, status, notes) VALUES (?, ?, ?, ?, 'available', ?)");
                        $stmt->bind_param("issss", $car_id, $plate_number, $color, $image_filename, $stock_notes);
                        if ($stmt->execute()) {
                            $success_message = "Stock unit $plate_number added successfully.";
                        } else {
                            $error_message = 'Failed to add stock unit.';
                        }
                        $stmt->close();
                    }
                }
                $check->close();
            }
        } elseif ($_POST['action'] === 'update_status') {
            $stock_id = filter_var($_POST['stock_id'] ?? 0, FILTER_VALIDATE_INT);
            $new_status = $_POST['new_status'] ?? '';
            
            if (!in_array($new_status, ['available', 'maintenance'])) {
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
        } elseif ($_POST['action'] === 'edit_stock') {
            $stock_id = filter_var($_POST['stock_id'] ?? 0, FILTER_VALIDATE_INT);
            $plate_number = strtoupper(trim($_POST['plate_number'] ?? ''));
            $color = trim($_POST['color'] ?? '');
            $stock_notes = trim($_POST['stock_notes'] ?? '');

            if (empty($plate_number)) {
                $error_message = 'Plate number is required.';
            } else {
                // Check uniqueness excluding current stock
                $check = $conn->prepare("SELECT id FROM car_stock WHERE plate_number = ? AND id != ?");
                $check->bind_param("si", $plate_number, $stock_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error_message = 'Plate number already exists in the system.';
                } else {
                    $image_query = "";
                    $params = [$plate_number, $color, $stock_notes];
                    $types = "sss";
                    
                    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
                        $upload_result = upload_image($_FILES['image_url']);
                        if ($upload_result['success']) {
                            // Delete old image
                            $stmt_old = $conn->prepare("SELECT image_url FROM car_stock WHERE id = ?");
                            $stmt_old->bind_param("i", $stock_id);
                            $stmt_old->execute();
                            $old_img = $stmt_old->get_result()->fetch_assoc();
                            if ($old_img && !empty($old_img['image_url'])) {
                                @unlink($project_root . '/uploads/cars/' . $old_img['image_url']);
                            }
                            $stmt_old->close();

                            $image_query = ", image_url = ?";
                            $params[] = $upload_result['filename'];
                            $types .= "s";
                        } else {
                            $error_message = 'Image upload failed.';
                        }
                    }

                    if (empty($error_message)) {
                        $params[] = $stock_id;
                        $params[] = $car_id;
                        $types .= "ii";
                        
                        $stmt = $conn->prepare("UPDATE car_stock SET plate_number = ?, color = ?, notes = ? {$image_query} WHERE id = ? AND car_id = ?");
                        $stmt->bind_param($types, ...$params);
                        if ($stmt->execute()) {
                            $success_message = "Stock unit updated successfully.";
                        } else {
                            $error_message = 'Failed to update stock unit.';
                        }
                        $stmt->close();
                    }
                }
                $check->close();
            }
        } elseif ($_POST['action'] === 'delete_stock_image') {
            $stock_id = filter_var($_POST['stock_id'] ?? 0, FILTER_VALIDATE_INT);
            $stmt = $conn->prepare("SELECT image_url FROM car_stock WHERE id = ? AND car_id = ?");
            $stmt->bind_param("ii", $stock_id, $car_id);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($img && !empty($img['image_url'])) {
                @unlink($project_root . '/uploads/cars/' . $img['image_url']);
                $stmt = $conn->prepare("UPDATE car_stock SET image_url = NULL WHERE id = ?");
                $stmt->bind_param("i", $stock_id);
                $stmt->execute();
                $stmt->close();
                $success_message = 'Stock image deleted successfully.';
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
                // Delete physical file
                $stmt_img = $conn->prepare("SELECT image_url FROM car_stock WHERE id = ?");
                $stmt_img->bind_param("i", $stock_id);
                $stmt_img->execute();
                $img_res = $stmt_img->get_result()->fetch_assoc();
                if ($img_res && !empty($img_res['image_url'])) {
                    @unlink($project_root . '/uploads/cars/' . $img_res['image_url']);
                }
                $stmt_img->close();

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
        (SELECT o.id FROM orders o WHERE o.car_stock_id = cs.id AND o.status IN ('pending', 'approved') AND CURDATE() BETWEEN o.rental_start_date AND o.rental_end_date ORDER BY o.created_at DESC LIMIT 1) as active_order_id,
        (SELECT o.rental_end_date FROM orders o WHERE o.car_stock_id = cs.id AND o.status IN ('pending', 'approved') AND CURDATE() BETWEEN o.rental_start_date AND o.rental_end_date ORDER BY o.created_at DESC LIMIT 1) as rental_end_date
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
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>ID</th>
                                <th><?php echo __('admin_plate_number'); ?></th>
                                <th><?php echo __('admin_color'); ?></th>
                                <th><?php echo __('admin_status'); ?></th>
                                <th><?php echo __('admin_action'); ?></th>
                                <th><?php echo __('admin_notes'); ?></th>
                                <th><?php echo __('admin_actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stock_units)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox"></i> <?php echo __('admin_no_stock_units'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($stock_units as $unit): 
                                    $computed_status = $unit['status'];
                                    if ($unit['active_order_id'] && $unit['status'] !== 'maintenance') {
                                        $computed_status = 'rented';
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($unit['image_url'])): ?>
                                                <div class="position-relative d-inline-block">
                                                    <img src="<?php echo SITE_URL . '/uploads/cars/' . sanitize_output($unit['image_url']); ?>" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                                                    <form method="POST" class="position-absolute top-0 start-100 translate-middle p-0 m-0" onsubmit="return confirm('Delete this image?');" style="z-index: 10;">
                                                        <input type="hidden" name="action" value="delete_stock_image">
                                                        <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                        <?php echo csrf_input_field(); ?>
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center shadow" style="width: 20px; height: 20px;" title="Delete Image">
                                                            <i class="fas fa-times" style="font-size: 10px;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-light text-center rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                                                    <i class="fas fa-car text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>#<?php echo $unit['id']; ?></td>
                                        <td><strong><?php echo sanitize_output($unit['plate_number']); ?></strong></td>
                                        <td><?php echo sanitize_output($unit['color'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($computed_status === 'available'): ?>
                                                <span class="badge bg-success"><?php echo __('admin_status_available'); ?></span>
                                            <?php elseif ($computed_status === 'rented'): ?>
                                                <span class="badge bg-warning text-dark"><?php echo __('admin_status_rented'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo __('admin_status_maintenance'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($unit['active_order_id']): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $unit['active_order_id']; ?>" class="text-primary fw-bold">
                                                    Order #<?php echo $unit['active_order_id']; ?>
                                                </a>
                                                <br><small class="text-muted">Until: <?php echo format_date($unit['rental_end_date']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo sanitize_output($unit['notes'] ?? ''); ?></small></td>
                                        <td>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <?php if ($unit['status'] === 'available'): ?>
                                                    <form method="POST" class="m-0">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                        <input type="hidden" name="new_status" value="maintenance">
                                                        <?php echo csrf_input_field(); ?>
                                                        <button type="submit" class="btn btn-sm btn-warning text-dark" title="Set to Maintenance">
                                                            <i class="fas fa-tools me-1"></i> Maintain
                                                        </button>
                                                    </form>
                                                <?php elseif ($unit['status'] === 'maintenance'): ?>
                                                    <form method="POST" class="m-0">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                        <input type="hidden" name="new_status" value="available">
                                                        <?php echo csrf_input_field(); ?>
                                                        <button type="submit" class="btn btn-sm btn-success" title="Mark as Available">
                                                            <i class="fas fa-check me-1"></i> Ready
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if (!$unit['active_order_id']): ?>
                                                    <form method="POST" class="m-0" onsubmit="return confirm('Delete unit <?php echo sanitize_output($unit['plate_number']); ?>?');">
                                                        <input type="hidden" name="action" value="delete_stock">
                                                        <input type="hidden" name="stock_id" value="<?php echo $unit['id']; ?>">
                                                        <?php echo csrf_input_field(); ?>
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-primary edit-stock-btn" 
                                                    data-id="<?php echo $unit['id']; ?>"
                                                    data-plate="<?php echo sanitize_output($unit['plate_number']); ?>"
                                                    data-color="<?php echo sanitize_output($unit['color']); ?>"
                                                    data-notes="<?php echo sanitize_output($unit['notes']); ?>"
                                                    title="Edit Unit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
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
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_stock">
                        <?php echo csrf_input_field(); ?>
                        
                        <div class="mb-3">
                            <label for="plate_number" class="form-label"><?php echo __('admin_plate_number'); ?> *</label>
                            <input type="text" class="form-control" id="plate_number" name="plate_number" 
                                placeholder="B 1234 XYZ" required style="text-transform: uppercase;">
                        </div>
                        
                        <div class="mb-3">
                            <label for="color" class="form-label"><?php echo __('admin_color'); ?></label>
                            <input type="text" class="form-control" id="color" name="color" placeholder="Black, White, etc.">
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">Unit Photo (Optional)</label>
                            <input type="file" class="form-control" id="image_url" name="image_url" accept="image/jpeg,image/png,image/webp">
                        </div>
                        
                        <div class="mb-3">
                            <label for="stock_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="stock_notes" name="stock_notes" rows="2" placeholder="Condition, etc."></textarea>
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
                        $s = $u['status'];
                        if ($u['active_order_id'] && $s !== 'maintenance') $s = 'rented';
                        if ($s === 'available') $avail++;
                        elseif ($s === 'rented') $rent++;
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

<!-- Edit Stock Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('admin_edit'); ?> <?php echo __('admin_unit'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_stock">
                    <input type="hidden" name="stock_id" id="edit_stock_id">
                    <?php echo csrf_input_field(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('admin_plate_number'); ?> *</label>
                        <input type="text" class="form-control" id="edit_plate_number" name="plate_number" required style="text-transform: uppercase;">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('admin_color'); ?></label>
                        <input type="text" class="form-control" id="edit_color" name="color">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('admin_change_image'); ?></label>
                        <input type="file" class="form-control" name="image_url" accept="image/jpeg,image/png,image/webp">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('admin_notes'); ?></label>
                        <textarea class="form-control" id="edit_notes" name="stock_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('admin_cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('admin_save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-stock-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editStockModal'));
    
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_stock_id').value = this.dataset.id;
            document.getElementById('edit_plate_number').value = this.dataset.plate;
            document.getElementById('edit_color').value = this.dataset.color;
            document.getElementById('edit_notes').value = this.dataset.notes;
            editModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
