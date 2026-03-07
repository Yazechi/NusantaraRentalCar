<?php
$page_title = 'My Profile';
require_once __DIR__ . '/includes/header.php';

require_login();

$user = get_logged_in_user();
$errors = [];
$password_errors = [];
$feedback_errors = [];
$active_tab = $_GET['tab'] ?? 'profile';

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
        } elseif ($action === 'send_feedback') {
            $active_tab = 'feedback';
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if (empty($subject)) $feedback_errors[] = 'Subject is required.';
            if (empty($message)) $feedback_errors[] = 'Message is required.';

            if (empty($feedback_errors)) {
                $stmt = $conn->prepare("INSERT INTO admin_feedback (user_id, subject, message) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $_SESSION['user_id'], $subject, $message);
                if ($stmt->execute()) {
                    set_flash_message('success', 'Feedback sent to admin! Thank you.');
                    redirect(SITE_URL . '/profile.php');
                } else {
                    $feedback_errors[] = 'Failed to send feedback.';
                }
                $stmt->close();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h3 class="mb-4"><i class="fas fa-user-edit"></i> <?php echo __('my_profile'); ?></h3>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>"
                   data-bs-toggle="tab" href="#profile-tab"><?php echo __('profile_information'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'password' ? 'active' : ''; ?>"
                   data-bs-toggle="tab" href="#password-tab"><?php echo __('change_password'); ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab === 'feedback' ? 'active' : ''; ?>"
                   data-bs-toggle="tab" href="#feedback-tab"><?php echo __('send_feedback'); ?></a>
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
                                <label for="name" class="form-label"><?php echo __('full_name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="<?php echo sanitize_output($user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('email_address'); ?></label>
                                <input type="email" class="form-control" id="email"
                                       value="<?php echo sanitize_output($user['email']); ?>" disabled>
                                <div class="form-text"><?php echo __('email_cannot_change'); ?></div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label"><?php echo __('phone_number'); ?></label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       value="<?php echo sanitize_output($user['phone'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label"><?php echo __('address_label'); ?></label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo sanitize_output($user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?php echo __('member_since'); ?></label>
                                <p class="form-control-plaintext"><?php echo format_date($user['created_at']); ?></p>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo __('update_profile'); ?></button>
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
                                <label for="current_password" class="form-label"><?php echo __('current_password'); ?> <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?php echo __('new_password'); ?> <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                <div class="form-text"><?php echo __('min_6_chars'); ?></div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label"><?php echo __('confirm_password'); ?> <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> <?php echo __('update_password'); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?php echo $active_tab === 'feedback' ? 'show active' : ''; ?>" id="feedback-tab">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><i class="fas fa-envelope-open-text me-2"></i> <?php echo __('send_feedback_admin'); ?></h5>
                        <p class="text-muted small"><?php echo __('feedback_subtitle'); ?></p>
                        
                        <?php if (!empty($feedback_errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($feedback_errors as $error): ?>
                                        <li><?php echo sanitize_output($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php echo csrf_input_field(); ?>
                            <input type="hidden" name="action" value="send_feedback">

                            <div class="mb-3">
                                <label class="form-label"><?php echo __('subject'); ?></label>
                                <input type="text" name="subject" class="form-control" placeholder="<?php echo __('subject_placeholder'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?php echo __('message_label'); ?></label>
                                <textarea name="message" class="form-control" rows="5" placeholder="<?php echo __('message_placeholder'); ?>" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> <?php echo __('send_feedback'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
