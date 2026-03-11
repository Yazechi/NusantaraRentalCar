<?php
require_once __DIR__ . '/includes/language.php';
$page_title = __('nav_login');
require_once __DIR__ . '/includes/header.php';

// If already logged in, redirect to home (unless explicitly trying to switch accounts)
if (is_logged_in() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = __('auth_invalid_csrf');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email)) {
            $errors[] = __('auth_email_required');
        } elseif (!validate_email($email)) {
            $errors[] = __('auth_invalid_email');
        }

        if (empty($password)) {
            $errors[] = __('auth_password_required');
        }

        if (empty($errors)) {
            // Clear any existing session data before new login
            $_SESSION = array();
            session_regenerate_id(true);
            
            $result = login_user($email, $password);
            if ($result['success']) {
                if ($result['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/dashboard.php');
                } else {
                    redirect(SITE_URL . '/');
                }
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="page-header text-center">
            <h1><i class="fas fa-sign-in-alt"></i> <?php echo __('nav_login'); ?></h1>
        </div>
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo sanitize_output($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrf_input_field(); ?>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo __('email'); ?></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo sanitize_output($email); ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo __('password'); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> <?php echo __('nav_login'); ?></button>
                    </div>
                </form>

                        <hr>
                        <p class="text-center mb-0">
                            <?php echo __('no_account'); ?> <a href="<?php echo SITE_URL; ?>/register.php"><?php echo __('auth_register_here'); ?></a><br>
                            <small><a href="<?php echo SITE_URL; ?>/forgot-password.php" class="text-muted"><?php echo __('forgot_password'); ?></a></small>
                        </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
