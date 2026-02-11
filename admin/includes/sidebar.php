<?php
// Admin sidebar navigation
// File ini harus diinclude SETELAH header.php

// Get current page untuk active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="admin-sidebar">
    <div class="sidebar-content">
        <nav class="nav flex-column nav-pills">
            <!-- Dashboard -->
            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>"
                href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>

            <!-- Cars Management -->
            <div class="nav-section">
                <div class="section-title">
                    <i class="fas fa-car"></i> Cars Management
                </div>
                <a class="nav-link ms-3 <?php echo $current_page === 'cars.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/cars.php">
                    <i class="fas fa-list"></i> List Cars
                </a>
                <a class="nav-link ms-3 <?php echo $current_page === 'car-add.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/car-add.php">
                    <i class="fas fa-plus"></i> Add Car
                </a>
            </div>

            <!-- Orders Management -->
            <div class="nav-section">
                <div class="section-title">
                    <i class="fas fa-shopping-cart"></i> Orders Management
                </div>
                <a class="nav-link ms-3 <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/orders.php">
                    <i class="fas fa-list"></i> All Orders
                </a>
            </div>

            <!-- Users Management -->
            <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>"
                href="<?php echo SITE_URL; ?>/admin/users.php">
                <i class="fas fa-users"></i> Users
            </a>

            <!-- Settings -->
            <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>"
                href="<?php echo SITE_URL; ?>/admin/settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
        </nav>
    </div>
</div>