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
                <i class="fas fa-chart-line"></i> <?php echo __('admin_dashboard'); ?>
            </a>

            <!-- Cars Management -->
            <div class="nav-section">
                <div class="section-title">
                    <i class="fas fa-car"></i> <?php echo __('admin_cars_management'); ?>
                </div>
                <a class="nav-link ms-3 <?php echo $current_page === 'cars.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/cars.php">
                    <i class="fas fa-list"></i> <?php echo __('admin_list_cars'); ?>
                </a>
                <a class="nav-link ms-3 <?php echo $current_page === 'car-add.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/car-add.php">
                    <i class="fas fa-plus"></i> <?php echo __('admin_add_car'); ?>
                </a>
                <a class="nav-link ms-3 <?php echo $current_page === 'car-stock.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/cars.php">
                    <i class="fas fa-boxes"></i> <?php echo __('admin_car_stock'); ?>
                </a>
            </div>

            <!-- Orders Management -->
            <div class="nav-section">
                <div class="section-title">
                    <i class="fas fa-shopping-cart"></i> <?php echo __('admin_orders_management'); ?>
                </div>
                <a class="nav-link ms-3 <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/orders.php">
                    <i class="fas fa-list"></i> <?php echo __('admin_all_orders'); ?>
                </a>
            </div>

            <!-- Users Management -->
            <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>"
                href="<?php echo SITE_URL; ?>/admin/users.php">
                <i class="fas fa-users"></i> <?php echo __('admin_users'); ?>
            </a>

            <!-- Support & Feedback -->
            <div class="nav-section">
                <div class="section-title">
                    <i class="fas fa-headset"></i> <?php echo __('admin_support_interaction'); ?>
                </div>
                <a class="nav-link ms-3 <?php echo $current_page === 'emergencies.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/emergencies.php">
                    <i class="fas fa-ambulance"></i> <?php echo __('admin_emergency_sos'); ?>
                </a>
                <a class="nav-link ms-3 <?php echo $current_page === 'feedback.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/feedback.php">
                    <i class="fas fa-comment-dots"></i> <?php echo __('admin_user_feedback'); ?>
                </a>
                <a class="nav-link ms-3 <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>"
                    href="<?php echo SITE_URL; ?>/admin/reviews.php">
                    <i class="fas fa-star"></i> <?php echo __('admin_car_reviews'); ?>
                </a>
            </div>

            <!-- Settings -->
            <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>"
                href="<?php echo SITE_URL; ?>/admin/settings.php">
                <i class="fas fa-cog"></i> <?php echo __('admin_settings'); ?>
            </a>
        </nav>
    </div>
</div>