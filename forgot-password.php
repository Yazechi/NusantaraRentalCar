<?php
$page_title = 'Forgot Password';
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(SITE_URL . '/');
}

$errors = [];
$success = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!validate_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $stmt->bind_param("ssi", $reset_token, $reset_expires, $user['id']);
                $stmt->execute();
                $stmt->close();

                // Send reset email
                require_once __DIR__ . '/includes/email.php';
                send_password_reset_email($email, $user['name'], $reset_token);
            }

            // Always show success message (security: don't reveal if email exists)
            $success = true;
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="card-title text-center mb-4"><i class="fas fa-key"></i> Forgot Password</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        If an account exists with that email, we've sent password reset instructions.
                        Please check your email inbox (and spam folder).
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Login
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

                    <p class="text-muted mb-4">Enter your email address and we'll send you a link to reset your password.</p>

                    <form method="POST" action="">
                        <?php echo csrf_input_field(); ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo sanitize_output($email); ?>" required autofocus>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Reset Link
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="<?php echo SITE_URL; ?>/login.php" class="text-muted">
                                <i class="fas fa-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
