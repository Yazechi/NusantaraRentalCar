<?php
require_once __DIR__ . '/includes/language.php';
$page_title = __('nav_register');
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    redirect(SITE_URL . '/');
}

$errors = [];
$name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = __('auth_invalid_csrf');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name)) {
            $errors[] = __('auth_name_required');
        } elseif (strlen($name) > 100) {
            $errors[] = __('auth_name_too_long');
        }

        if (empty($email)) {
            $errors[] = __('auth_email_required');
        } elseif (!validate_email($email)) {
            $errors[] = __('auth_invalid_email');
        }

        if (empty($password)) {
            $errors[] = __('auth_password_required');
        } elseif (strlen($password) < 6) {
            $errors[] = __('auth_password_min_6');
        }

        if ($password !== $confirm_password) {
            $errors[] = __('auth_passwords_not_match');
        }

        if (!empty($phone) && strlen($phone) > 20) {
            $errors[] = __('auth_phone_too_long');
        }

        if (empty($errors)) {
            $result = register_user($name, $email, $password, $phone ?: null, $address ?: null);
            if ($result['success']) {
                set_flash_message('success', __('auth_reg_success'));
                redirect(SITE_URL . '/login.php');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="page-header text-center">
            <h1><i class="fas fa-user-plus"></i> <?php echo __('nav_register'); ?></h1>
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
                        <label for="name" class="form-label"><?php echo __('auth_full_name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo sanitize_output($name); ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo __('email'); ?> <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo sanitize_output($email); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label"><?php echo __('password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <div class="form-text"><?php echo __('min_6_chars'); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label"><?php echo __('auth_confirm_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label"><?php echo __('phone'); ?></label>
                        <input type="text" class="form-control" id="phone" name="phone"
                               value="<?php echo sanitize_output($phone); ?>" placeholder="e.g. 081234567890">
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label"><?php echo __('address_label'); ?></label>
                        <textarea class="form-control" id="address" name="address" rows="2"><?php echo sanitize_output($address); ?></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> <?php echo __('nav_register'); ?></button>
                    </div>
                </form>

                <hr>
                <p class="text-center mb-0">
                    <?php echo __('has_account'); ?> <a href="<?php echo SITE_URL; ?>/login.php"><?php echo __('auth_login_here'); ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
