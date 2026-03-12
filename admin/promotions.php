<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/language.php';
$page_title = __('admin_manage_promotions');
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Fetch promotions
$stmt = $conn->prepare("SELECT * FROM promotions ORDER BY sort_order ASC, created_at DESC");
$stmt->execute();
$promotions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-gift me-2 text-primary"></i> <?php echo __('admin_manage_promotions'); ?></h2>
        <a href="promotion-add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> <?php echo __('admin_add_promotion'); ?></a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th><?php echo __('admin_promo_images_col'); ?></th>
                            <th><?php echo __('admin_promo_title'); ?></th>
                            <th><?php echo __('admin_promo_disc_req_col'); ?></th>
                            <th><?php echo __('admin_promo_valid_from'); ?></th>
                            <th><?php echo __('admin_promo_status'); ?></th>
                            <th><?php echo __('admin_promo_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($promotions)): ?>
                            <tr><td colspan="7" class="text-center py-4"><?php echo __('admin_promo_no_promo'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($promotions as $promo): ?>
                            <tr>
                                <td><?php echo (int)$promo['id']; ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if (!empty($promo['image_en'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/promo-card/<?php echo sanitize_output($promo['image_en']); ?>" alt="EN" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;" title="English">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 35px;" title="No English Image">EN</div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($promo['image_id'])): ?>
                                            <img src="<?php echo SITE_URL; ?>/assets/images/promo-card/<?php echo sanitize_output($promo['image_id']); ?>" alt="ID" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;" title="Indonesian">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 35px;" title="No Indonesian Image">ID</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo sanitize_output($promo['title']); ?></div>
                                    <div class="small text-muted"><?php echo sanitize_output($promo['subtitle']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-success mb-1"><?php echo (int)$promo['discount_percent']; ?>% OFF</span><br>
                                    <small class="text-muted">Min: <?php echo (int)$promo['min_duration_days']; ?> <?php echo __('day'); ?></small>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php echo $promo['valid_from'] ? format_date($promo['valid_from']) : 'Always'; ?> - 
                                        <?php echo $promo['valid_to'] ? format_date($promo['valid_to']) : 'Always'; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($promo['is_active']): ?>
                                        <span class="badge bg-primary"><?php echo __('admin_promo_active'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo __('admin_promo_inactive'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="promotion-edit.php?id=<?php echo (int)$promo['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <a href="promotion-delete.php?id=<?php echo (int)$promo['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this promotion?');"><i class="fas fa-trash"></i></a>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>