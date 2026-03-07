<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/language.php';

check_session_timeout();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_lang = get_current_lang();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? sanitize_output($page_title) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png">
    
    <!-- Modern Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
    @font-face {
        font-family: 'ArchicocoRegular';
        src: url('<?php echo SITE_URL; ?>/assets/fonts/ArchicocoRegular.ttf') format('truetype');
        font-weight: bold;
        font-style: normal;
    }
    </style>
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light sticky-top custom-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>/">
                <img src="<?php echo SITE_URL; ?>/assets/images/meTrevFinal.png" alt="MeTrev" style="height:48px;width:48px;object-fit:contain;margin-right:8px;border-radius:50%;">
                <span class="brand-text"><?php echo SITE_NAME; ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/"><?php echo __('nav_home'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'cars' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/cars.php"><?php echo __('nav_cars'); ?></a>
                    </li>
                    <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'my-orders' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/my-orders.php"><?php echo __('nav_my_orders'); ?></a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'guide' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/guide.php"><?php echo __('nav_guide'); ?></a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <!-- Language Switcher -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-globe"></i> <?php echo $current_lang === 'id' ? 'ID' : 'EN'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item <?php echo $current_lang === 'id' ? 'active' : ''; ?>" href="?lang=id">🇮🇩 <?php echo __('indonesian'); ?></a></li>
                            <li><a class="dropdown-item <?php echo $current_lang === 'en' ? 'active' : ''; ?>" href="?lang=en">🇬🇧 <?php echo __('english'); ?></a></li>
                        </ul>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> <?php echo __('nav_admin'); ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo sanitize_output($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php"><i class="fas fa-user-edit"></i> <?php echo __('nav_profile'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/my-orders.php"><i class="fas fa-clipboard-list"></i> <?php echo __('nav_my_orders'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php?tab=feedback"><i class="fas fa-envelope-open-text"></i> <?php echo __('send_feedback'); ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('nav_logout'); ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'login' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/login.php">
                                <i class="fas fa-sign-in-alt"></i> <?php echo __('nav_login'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'register' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/register.php">
                                <i class="fas fa-user-plus"></i> <?php echo __('nav_register'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="<?php echo ($current_page === 'index') ? 'homepage-main' : 'container py-4'; ?>">
        <?php display_flash_message(); ?>
