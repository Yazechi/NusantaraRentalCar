<?php
$page_title = 'My Profile';
require_once __DIR__ . '/includes/header.php';

require_login();

$user = get_logged_in_user();
$errors = [];
$password_errors = [];
$active_tab = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $active_tab = 'profile';
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($name)) {
                $errors[] = 'Name is required.';
            } elseif (strlen($name) > 100) {
                $errors[] = 'Name must be 100 characters or less.';
            }

            if (!empty($phone) && strlen($phone) > 20) {
                $errors[] = 'Phone number is too long.';
            }

            if (empty($errors)) {
                $result = update_user_profile($_SESSION['user_id'], $name, $phone ?: null, $address ?: null);
                if ($result['success']) {
                    set_flash_message('success', $result['message']);
                    redirect(SITE_URL . '/profile.php');
                } else {
                    $errors[] = $result['message'];
                }
            }

            $user['name'] = $name;
            $user['phone'] = $phone;
            $user['address'] = $address;

        } elseif ($action === 'change_password') {
            $active_tab = 'password';
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_new_password'] ?? '';

            if (empty($current_password)) {
                $password_errors[] = 'Current password is required.';
            }

            if (empty($new_password)) {
                $password_errors[] = 'New password is required.';
            } elseif (strlen($new_password) < 6) {
                $password_errors[] = 'New password must be at least 6 characters.';
            }

            if ($new_password !== $confirm_password) {
                $password_errors[] = 'New passwords do not match.';
            }

            if (empty($password_errors)) {
                $result = change_password($_SESSION['user_id'], $current_password, $new_password);
                if ($result['success']) {
                    set_flash_message('success', $result['message']);
                    redirect(SITE_URL . '/profile.php');
                } else {
                    $password_errors[] = $result['message'];
                }
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h3 class="mb-4"><i class="fas fa-user-edit"></i> My Profile</h3>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"
                   data-bs-toggle="tab" href="#profile-tab">Profile Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'password' ? 'active' : ''; ?>"
                   data-bs-toggle="tab" href="#password-tab">Change Password</a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade <?php echo $active_tab === 'profile' ? 'show active' : ''; ?>" id="profile-tab">
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
                            <input type="hidden" name="action" value="update_profile">

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo sanitize_output($user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email"
                                       value="<?php echo sanitize_output($user['email']); ?>" disabled>
                                <div class="form-text">Email cannot be changed.</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="<?php echo sanitize_output($user['phone'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo sanitize_output($user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Member Since</label>
                                <p class="form-control-plaintext"><?php echo format_date($user['created_at']); ?></p>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?php echo $active_tab === 'password' ? 'show active' : ''; ?>" id="password-tab">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <?php if (!empty($password_errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($password_errors as $error): ?>
                                        <li><?php echo sanitize_output($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php echo csrf_input_field(); ?>
                            <input type="hidden" name="action" value="change_password">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                <div class="form-text">Minimum 6 characters.</div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
