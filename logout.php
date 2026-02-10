<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logout_user();
set_flash_message('success', 'You have been logged out.');
redirect(SITE_URL . '/login.php');
