<?php
// Admin Dashboard - Statistik ringkas
$page_title = 'Dashboard';

$project_root = dirname(__DIR__);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Get statistics
$stats = [];

// Total cars
$result = $conn->query("SELECT COUNT(*) as count FROM cars");
$stats['total_cars'] = $result->fetch_assoc()['count'];

// Available cars
$result = $conn->query("SELECT COUNT(*) as count FROM cars WHERE is_available = 1");
$stats['available_cars'] = $result->fetch_assoc()['count'];

// Total orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders_query = "
    SELECT 
        o.id, 
        o.rental_start_date, 
        o.rental_end_date,
        o.status,
        u.name as user_name,
        c.name as car_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN cars c ON o.car_id = c.id
    ORDER BY o.created_at DESC
    LIMIT 5
";
$recent_orders = $conn->query($recent_orders_query)->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
        <p>Welcome back, <?php echo sanitize_output($_SESSION['user_name']); ?>! Here's your overview.</p>
    </div>

    <?php display_flash_message(); ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card stat-card-blue">
                <div class="stat-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total_cars']; ?></div>
                    <div class="stat-label">Total Cars</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card stat-card-green">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['available_cars']; ?></div>
                    <div class="stat-label">Available</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card stat-card-orange">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card stat-card-red">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="stat-card stat-card-purple">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group-actions">
                        <a href="<?php echo SITE_URL; ?>/admin/car-add.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Car
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/cars.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-list"></i> Manage Cars
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/orders.php" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-shopping-cart"></i> View Orders
                        </a>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Orders</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Car</th>
                                <th>Rental Period</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox"></i> No orders yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                                        <td><?php echo sanitize_output($order['user_name']); ?></td>
                                        <td><?php echo sanitize_output($order['car_name']); ?></td>
                                        <td>
                                            <?php
                                            echo format_date($order['rental_start_date']) . ' - ' . format_date($order['rental_end_date']);
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo get_status_badge($order['status']); ?>
                                        </td>
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>