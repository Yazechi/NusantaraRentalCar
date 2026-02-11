<?php
// Admin Users Management Page
$page_title = 'Users Management';

$project_root = dirname(__DIR__);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$success_message = '';

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security validation failed.';
    } else {
        $user_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);

        if ($user_id > 0 && $user_id !== $_SESSION['user_id']) {
            // Delete user orders first
            $stmt_orders = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt_orders->bind_param("i", $user_id);
            $stmt_orders->execute();
            $stmt_orders->close();

            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $success_message = 'User deleted successfully.';
            } else {
                $error_message = 'Failed to delete user.';
            }
            $stmt->close();
        } elseif ($user_id === $_SESSION['user_id']) {
            $error_message = 'Cannot delete your own account.';
        }
    }
}

// Get pagination
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
if ($page < 1) $page = 1;

$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total regular users (not admin)
$total_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$total_users = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_users / $per_page);

// Get users with their order count
$users_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.phone,
        u.address,
        u.role,
        u.created_at,
        COUNT(o.id) as total_orders,
        SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($users_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get admin statistics
$admin_stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$admin_stats['total_users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$admin_stats['total_orders'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT SUM(total_price) as total FROM orders");
$row = $result->fetch_assoc();
$admin_stats['total_revenue'] = $row['total'] ?? 0;
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-users"></i> Users Management</h1>
        <p>Manage all registered users.</p>
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

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-blue">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Users</p>
                    <h3 class="mb-0"><?php echo $admin_stats['total_users']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-green">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Orders</p>
                    <h3 class="mb-0"><?php echo $admin_stats['total_orders']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card stat-card-orange">
                <div class="card-body">
                    <p class="text-muted mb-1 small">Total Revenue</p>
                    <h3 class="mb-0"><?php echo format_currency($admin_stats['total_revenue']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Orders</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox"></i> No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td><?php echo sanitize_output($user['name']); ?></td>
                                <td><a href="mailto:<?php echo sanitize_output($user['email']); ?>"><?php echo sanitize_output($user['email']); ?></a></td>
                                <td><?php echo sanitize_output($user['phone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $user['total_orders']; ?></span>
                                    <span class="text-muted" title="Completed Orders">
                                        (<?php echo $user['completed_orders'] ?? 0; ?> completed)
                                    </span>
                                </td>
                                <td><small><?php echo format_date($user['created_at']); ?></small></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-info" data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-info text-white">
                                            <h5 class="modal-title">User Details</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Name</label>
                                                <p><?php echo sanitize_output($user['name']); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Email</label>
                                                <p><?php echo sanitize_output($user['email']); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Phone</label>
                                                <p><?php echo sanitize_output($user['phone'] ?? '-'); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Address</label>
                                                <p><?php echo sanitize_output($user['address'] ?? '-'); ?></p>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label text-muted small">Total Orders</label>
                                                <p><?php echo $user['total_orders']; ?></p>
                                            </div>
                                            <div>
                                                <label class="form-label text-muted small">Joined</label>
                                                <p><?php echo format_date($user['created_at']); ?></p>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title">Delete User</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to delete <strong><?php echo sanitize_output($user['name']); ?></strong>?</p>
                                            <p class="text-warning" style="font-size: 13px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                This will also delete all orders associated with this user. This action cannot be undone.
                                            </p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <?php echo csrf_input_field(); ?>
                                                <button type="submit" class="btn btn-danger">Delete</button>
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