<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Handle mark as read
if (isset($_GET['read'])) {
    $id = filter_var($_GET['read'], FILTER_VALIDATE_INT);
    $stmt = $conn->prepare("UPDATE admin_feedback SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch feedback
$stmt = $conn->prepare("SELECT af.*, u.name as user_name, u.email as user_email, u.phone 
    FROM admin_feedback af
    JOIN users u ON af.user_id = u.id
    ORDER BY af.is_read ASC, af.created_at DESC");
$stmt->execute();
$feedback_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="admin-content">
    <div class="container-fluid">
        <h3 class="mb-4"><i class="fas fa-comment-dots text-primary me-2"></i> <?php echo __('admin_user_feedback'); ?></h3>

        <div class="row">
            <?php foreach ($feedback_list as $fb): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm <?php echo !$fb['is_read'] ? 'border-primary border-start border-4' : ''; ?>">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><?php echo sanitize_output($fb['subject']); ?></h6>
                        <span class="small text-muted"><?php echo format_date($fb['created_at']); ?></span>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 fst-italic">"<?php echo nl2br(sanitize_output($fb['message'])); ?>"</p>
                        <hr>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="user-info small">
                                <i class="fas fa-user me-1 text-muted"></i> <strong><?php echo sanitize_output($fb['user_name']); ?></strong><br>
                                <i class="fas fa-envelope me-1 text-muted"></i> <?php echo sanitize_output($fb['user_email']); ?><br>
                                <?php if($fb['phone']): ?>
                                <i class="fas fa-phone me-1 text-muted"></i> <?php echo sanitize_output($fb['phone']); ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if (!$fb['is_read']): ?>
                                <a href="?read=<?php echo $fb['id']; ?>" class="btn btn-sm btn-outline-primary"><?php echo __('admin_mark_as_read'); ?></a>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo __('admin_read'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($feedback_list)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-ghost fa-3x mb-3"></i><br><?php echo __('admin_no_feedback_received'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
