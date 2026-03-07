<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $req_id = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'processed', 'completed'])) {
        $stmt = $conn->prepare("UPDATE emergency_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $req_id);
        $stmt->execute();
        $stmt->close();
        set_flash_message('success', 'SOS Request status updated.');
    }
}

// Fetch SOS requests
$stmt = $conn->prepare("SELECT er.*, u.name as user_name, u.phone, c.name as car_name, cb.name as brand_name, cs.plate_number
    FROM emergency_requests er
    JOIN users u ON er.user_id = u.id
    JOIN orders o ON er.order_id = o.id
    JOIN cars c ON o.car_id = c.id
    JOIN car_brands cb ON c.brand_id = cb.id
    LEFT JOIN car_stock cs ON o.car_stock_id = cs.id
    ORDER BY er.created_at DESC");
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="admin-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3><i class="fas fa-ambulance text-danger me-2"></i> <?php echo __('admin_emergency_requests'); ?></h3>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo __('admin_time'); ?></th>
                            <th><?php echo __('admin_customer'); ?></th>
                            <th><?php echo __('admin_car_plate'); ?></th>
                            <th><?php echo __('admin_location'); ?></th>
                            <th><?php echo __('admin_issue'); ?></th>
                            <th><?php echo __('admin_status'); ?></th>
                            <th><?php echo __('admin_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr class="<?php echo $req['status'] === 'pending' ? 'table-danger' : ''; ?>">
                            <td><small><?php echo format_date($req['created_at']); ?><br><?php echo date('H:i', strtotime($req['created_at'])); ?></small></td>
                            <td>
                                <strong><?php echo sanitize_output($req['user_name']); ?></strong><br>
                                <a href="tel:<?php echo $req['phone']; ?>" class="btn btn-sm btn-link p-0 text-decoration-none"><i class="fas fa-phone"></i> <?php echo $req['phone']; ?></a>
                            </td>
                            <td>
                                <small><?php echo sanitize_output($req['brand_name'] . ' ' . $req['car_name']); ?></small><br>
                                <span class="badge bg-dark"><?php echo sanitize_output($req['plate_number'] ?? 'N/A'); ?></span>
                            </td>
                            <td><small><?php echo nl2br(sanitize_output($req['location_details'])); ?></small></td>
                            <td><small><?php echo nl2br(sanitize_output($req['message'])); ?></small></td>
                            <td>
                                <?php
                                $status_colors = ['pending' => 'danger', 'processed' => 'warning', 'completed' => 'success'];
                                ?>
                                <span class="badge bg-<?php echo $status_colors[$req['status']]; ?>"><?php echo __($req['status']); ?></span>
                            </td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                    <select name="status" class="form-select form-select-sm" style="width: 110px;">
                                        <option value="pending" <?php echo $req['status'] === 'pending' ? 'selected' : ''; ?>><?php echo __('pending'); ?></option>
                                        <option value="processed" <?php echo $req['status'] === 'processed' ? 'selected' : ''; ?>><?php echo __('approved'); /* or translated 'processed' - using approved for simplicity or define 'processed' */ ?></option>
                                        <option value="completed" <?php echo $req['status'] === 'completed' ? 'selected' : ''; ?>><?php echo __('completed'); ?></option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted"><?php echo __('admin_no_emergencies_found'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
