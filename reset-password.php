<?php
$page_title = 'Reset Password';
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
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                set_flash_message('success', 'Password reset successful! Please login with your new password.');
                redirect(SITE_URL . '/login.php');
            } else {
                $stmt->close();
                $errors[] = 'Failed to reset password. Please try again.';
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4"><i class="fas fa-lock"></i> Reset Password</h3>

                <?php if (!$valid_token): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Invalid or expired reset link. Please request a new password reset.
                    </div>
                    <div class="text-center">
                        <a href="<?php echo SITE_URL; ?>/forgot-password.php" class="btn btn-primary">
                            Request New Link
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

                    <p class="text-muted mb-4">Enter your new password below.</p>

                    <form method="POST" action="">
                        <?php echo csrf_input_field(); ?>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" autofocus>
                            <div class="form-text">Minimum 6 characters.</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Reset Password
                            </button>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
