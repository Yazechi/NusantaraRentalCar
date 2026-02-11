<?php
// Admin Orders List Page
$page_title = 'Orders Management';

$project_root = dirname(__DIR__);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Get filter and pagination
$status_filter = trim($_GET['status'] ?? '');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
if ($page < 1) $page = 1;

$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query with prepared statements
$valid_statuses = ['pending', 'approved', 'cancelled', 'completed'];
$has_filter = !empty($status_filter) && in_array($status_filter, $valid_statuses);

// Get total orders
if ($has_filter) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders o WHERE o.status = ?");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $total_orders = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
} else {
    $total_result = $conn->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = $total_result->fetch_assoc()['count'];
}
$total_pages = ceil($total_orders / $per_page);

// Get orders
if ($has_filter) {
    $orders_query = "
        SELECT 
            o.id, 
            o.status,
            o.total_price,
            o.rental_start_date,
            o.rental_end_date,
            o.created_at,
            u.name as user_name,
            u.email as user_email,
            c.name as car_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN cars c ON o.car_id = c.id
        WHERE o.status = ?
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("sii", $status_filter, $per_page, $offset);
} else {
    $orders_query = "
        SELECT 
            o.id, 
            o.status,
            o.total_price,
            o.rental_start_date,
            o.rental_end_date,
            o.created_at,
            u.name as user_name,
            u.email as user_email,
            c.name as car_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN cars c ON o.car_id = c.id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get status stats using prepared statement
$stats = [];
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
foreach ($valid_statuses as $st) {
    $stmt->bind_param("s", $st);
    $stmt->execute();
    $stats[$st] = $stmt->get_result()->fetch_assoc()['count'];
}
$stmt->close();
?>

<div class="admin-content">
    <div class="content-header d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
            <p>Manage all rental orders.</p>
        </div>
        <div>
            <a href="<?php echo SITE_URL; ?>/admin/export-orders.php?status=<?php echo urlencode($status_filter); ?>&format=csv" class="btn btn-success">
                <i class="fas fa-file-export"></i> Export to CSV
            </a>
        </div>
    </div>

    <?php display_flash_message(); ?>

    <!-- Status Filter Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <a href="?status=" class="card-link <?php echo empty($status_filter) ? 'active' : ''; ?>">
                <div class="card stat-card stat-card-blue">
                    <div class="card-body text-center">
                        <h5 class="text-primary mb-0"><?php echo $total_orders; ?></h5>
                        <p class="mb-0 small">All Orders</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="?status=pending" class="card-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                <div class="card stat-card stat-card-orange">
                    <div class="card-body text-center">
                        <h5 class="text-warning mb-0"><?php echo $stats['pending']; ?></h5>
                        <p class="mb-0 small">Pending</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="?status=approved" class="card-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                <div class="card stat-card stat-card-green">
                    <div class="card-body text-center">
                        <h5 class="text-success mb-0"><?php echo $stats['approved']; ?></h5>
                        <p class="mb-0 small">Approved</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="?status=completed" class="card-link <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                <div class="card stat-card stat-card-blue">
                    <div class="card-body text-center">
                        <h5 class="text-info mb-0"><?php echo $stats['completed']; ?></h5>
                        <p class="mb-0 small">Completed</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Car</th>
                        <th>Rental Period</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox"></i> No orders found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td>
                                    <div><?php echo sanitize_output($order['user_name']); ?></div>
                                    <small class="text-muted"><?php echo sanitize_output($order['user_email']); ?></small>
                                </td>
                                <td><?php echo sanitize_output($order['car_name']); ?></td>
                                <td>
                                    <?php
                                    echo format_date($order['rental_start_date']) . '<br>' .
                                        '<small class="text-muted">to ' . format_date($order['rental_end_date']) . '</small>';
                                    ?>
                                </td>
                                <td><strong><?php echo format_currency($order['total_price']); ?></strong></td>
                                <td><?php echo get_status_badge($order['status']); ?></td>
                                <td><small><?php echo format_date($order['created_at']); ?></small></td>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/admin/order-detail.php?id=<?php echo $order['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<style>
    .card-link {
        text-decoration: none;
        color: inherit;
    }

    .card-link.active .card {
        border: 2px solid currentColor;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>