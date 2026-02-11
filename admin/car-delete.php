<?php
// Admin Car Delete Handler (Optional - dapat dipanggil dari cars.php juga)
// Untuk saat ini, delete sudah ditangani via Modal di cars.php
// Halaman ini bisa diabaikan atau digunakan untuk delete confirmation standalone

// Redirectkan ke cars.php
$project_root = dirname(dirname(__DIR__));
require_once $project_root . '/config/config.php';

redirect(SITE_URL . '/admin/cars.php');
exit;
