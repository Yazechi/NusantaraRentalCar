<?php
// Admin Cars List Page
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_cars_management');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$success_message = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security validation failed.';
    } else {
        $car_id = filter_var($_POST['car_id'] ?? 0, FILTER_VALIDATE_INT);

        if ($car_id > 0) {
            // Delete car images first
            $stmt_img = $conn->prepare("DELETE FROM car_images WHERE car_id = ?");
            $stmt_img->bind_param("i", $car_id);
            $stmt_img->execute();
            $stmt_img->close();

            // Delete car
            $stmt = $conn->prepare("DELETE FROM cars WHERE id = ?");
            $stmt->bind_param("i", $car_id);

            if ($stmt->execute()) {
                $success_message = 'Car deleted successfully.';
            } else {
                $error_message = 'Failed to delete car.';
            }
            $stmt->close();
        }
    }
}

// Get pagination
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
if ($page < 1) $page = 1;

$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total cars
$total_result = $conn->query("SELECT COUNT(*) as count FROM cars");
$total_cars = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_cars / $per_page);

// Get cars with brand info and stock counts
$cars_query = "
    SELECT 
        c.id, 
        c.name, 
        c.model, 
        c.year,
        c.price_per_day,
        b.name as brand_name,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id) AS total_stock,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'available') AS available_stock,
        (SELECT COUNT(*) FROM car_stock cs WHERE cs.car_id = c.id AND cs.status = 'rented') AS rented_stock
    FROM cars c
    JOIN car_brands b ON c.brand_id = b.id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($cars_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$cars = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="admin-content">
    <div class="content-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-car"></i> <?php echo __('admin_cars_management'); ?></h1>
            <p><?php echo __('admin_manage_cars'); ?></p>
        </div>
        <a href="<?php echo SITE_URL; ?>/admin/car-add.php" class="btn btn-success">
            <i class="fas fa-plus"></i> <?php echo __('admin_add_new_car'); ?>
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo sanitize_output($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo sanitize_output($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php display_flash_message(); ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th><?php echo __('admin_brand'); ?></th>
                        <th><?php echo __('admin_car_name'); ?></th>
                        <th><?php echo __('admin_model'); ?></th>
                        <th><?php echo __('admin_year'); ?></th>
                        <th><?php echo __('admin_price_day'); ?></th>
                        <th><?php echo __('admin_stock'); ?></th>
                        <th><?php echo __('admin_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cars)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox"></i> <?php echo __('admin_no_cars_found'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cars as $car): ?>
                            <tr>
                                <td><strong>#<?php echo $car['id']; ?></strong></td>
                                <td><?php echo sanitize_output($car['brand_name']); ?></td>
                                <td><?php echo sanitize_output($car['name']); ?></td>
                                <td><?php echo sanitize_output($car['model']); ?></td>
                                <td><?php echo $car['year']; ?></td>
                                <td><strong><?php echo format_currency($car['price_per_day']); ?></strong></td>
                                <td>
                                    <span class="badge bg-success"><?php echo (int)$car['available_stock']; ?> <?php echo __('admin_avail'); ?></span>
                                    <?php if ($car['rented_stock'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo (int)$car['rented_stock']; ?> <?php echo __('admin_rented'); ?></span>
                                    <?php endif; ?>
                                    <br><small class="text-muted">Total: <?php echo (int)$car['total_stock']; ?></small>
                                    <a href="<?php echo SITE_URL; ?>/admin/car-stock.php?car_id=<?php echo $car['id']; ?>" class="btn btn-sm btn-outline-secondary ms-1" title="<?php echo __('admin_manage_stock_units'); ?>">
                                        <i class="fas fa-boxes"></i>
                                    </a>
                                </td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/admin/car-edit.php?id=<?php echo $car['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> <?php echo __('admin_edit'); ?>
                                    </a>
                                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#deleteModal<?php echo $car['id']; ?>">
                                        <i class="fas fa-trash"></i> <?php echo __('admin_delete'); ?>
                                    </button>
                                </td>
                            </tr>

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $car['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><?php echo __('admin_delete'); ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete <strong><?php echo sanitize_output($car['name']); ?></strong>?</p>
                                            <p class="text-muted small">This action cannot be undone.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('admin_cancel'); ?></button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                                <?php echo csrf_input_field(); ?>
                                                <button type="submit" class="btn btn-danger"><?php echo __('admin_delete'); ?></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>