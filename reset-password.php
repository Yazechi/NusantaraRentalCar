<?php
require_once __DIR__ . '/includes/language.php';
$page_title = __('reset_password');
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(SITE_URL . '/');
}

$errors = [];
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_id = null;

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        $valid_token = true;
        $user_id = $user['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = __('auth_invalid_csrf');
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password)) {
            $errors[] = __('auth_password_required');
        } elseif (strlen($new_password) < 6) {
            $errors[] = __('auth_password_min_6');
        }

        if ($new_password !== $confirm_password) {
            $errors[] = __('auth_passwords_not_match');
        }

        if (empty($errors)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                set_flash_message('success', __('reset_password_success'));
                redirect(SITE_URL . '/login.php');
            } else {
                $stmt->close();
                $errors[] = __('reset_password_failed');
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="page-header text-center">
            <h1><i class="fas fa-lock"></i> <?php echo __('reset_password'); ?></h1>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <?php if (!$valid_token): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo __('invalid_reset_link'); ?>
                    </div>
                    <div class="text-center">
                        <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="btn btn-primary">
                            <?php echo __('request_new_link'); ?>
                        </a>
                    </div>
                <?php else: ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo sanitize_output($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4"><?php echo __('enter_new_password'); ?></p>

                    <form method="POST" action="">
                        <?php echo csrf_input_field(); ?>

                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?php echo __('new_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" autofocus>
                            <div class="form-text"><?php echo __('min_6_chars'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?php echo __('auth_confirm_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> <?php echo __('reset_password'); ?>
                            </button>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
