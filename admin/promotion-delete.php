<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/language.php';
require_once __DIR__ . '/includes/header.php'; // Includes auth check

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    set_flash_message('danger', 'Invalid promotion ID.');
    redirect('promotions.php');
}

// Get the promotion details to delete the image if it exists
$stmt = $conn->prepare("SELECT image FROM promotions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$promo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($promo) {
    if (!empty($promo['image'])) {
        $image_path = BASE_PATH . '/assets/images/promo-card/' . $promo['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $stmt = $conn->prepare("DELETE FROM promotions WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Promotion deleted successfully.');
    } else {
        set_flash_message('danger', 'Failed to delete promotion.');
    }
    $stmt->close();
} else {
    set_flash_message('danger', 'Promotion not found.');
}

redirect('promotions.php');
