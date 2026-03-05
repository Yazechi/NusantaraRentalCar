<?php
// Admin Settings & Profile Page
$project_root = dirname(__DIR__);
if (!session_id()) session_start();
require_once $project_root . '/includes/language.php';
$page_title = __('admin_settings');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$error_message = '';
$success_message = '';

// Get admin data
$admin_data = get_logged_in_user();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    if ($_POST['type'] === 'profile' && isset($_POST['profile_submit'])) {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $error_message = 'Security validation failed.';
        } else {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($name)) {
                $error_message = 'Name is required.';
            } else {
                $result = update_user_profile($_SESSION['user_id'], $name, $phone, $address);

                if ($result['success']) {
                    $_SESSION['user_name'] = $name;
                    $admin_data = get_logged_in_user();
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            }
        }
    }

    // Handle password change
    if ($_POST['type'] === 'password' && isset($_POST['password_submit'])) {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $error_message = 'Security validation failed.';
        } else {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = 'All password fields are required.';
            } elseif ($new_password !== $confirm_password) {
                $error_message = 'New passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $error_message = 'New password must be at least 6 characters.';
            } else {
                $result = change_password($_SESSION['user_id'], $current_password, $new_password);

                if ($result['success']) {
                    $success_message = $result['message'];
                } else {
                    $error_message = $result['message'];
                }
            }
        }
    }
    
    // Handle site settings update
    if ($_POST['type'] === 'site' && isset($_POST['site_submit'])) {
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $error_message = 'Security validation failed.';
        } else {
            $whatsapp = trim($_POST['whatsapp_number'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $gemini_key = trim($_POST['gemini_api_key'] ?? '');
            $midtrans_client = trim($_POST['midtrans_client_key'] ?? '');
            $midtrans_server = trim($_POST['midtrans_server_key'] ?? '');
            
            // Update site settings
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            
            $stmt->bind_param("sss", $key, $whatsapp, $whatsapp);
            $key = 'whatsapp_number';
            $stmt->execute();
            
            $stmt->bind_param("sss", $key, $admin_email, $admin_email);
            $key = 'admin_email';
            $stmt->execute();
            
            $stmt->bind_param("sss", $key, $gemini_key, $gemini_key);
            $key = 'gemini_api_key';
            $stmt->execute();
            
            $stmt->bind_param("sss", $key, $midtrans_client, $midtrans_client);
            $key = 'midtrans_client_key';
            $stmt->execute();
            
            $stmt->bind_param("sss", $key, $midtrans_server, $midtrans_server);
            $key = 'midtrans_server_key';
            $stmt->execute();
            
            $stmt->close();
            
            $success_message = 'Site settings updated successfully!';
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-cog"></i> <?php echo __('admin_settings'); ?></h1>
        <p>Manage your admin account and site settings.</p>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo sanitize_output($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo sanitize_output($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php display_flash_message(); ?>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                <i class="fas fa-user-circle"></i> <?php echo __('admin_profile_info'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                <i class="fas fa-lock"></i> <?php echo __('admin_change_password'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="site-tab" data-bs-toggle="tab" data-bs-target="#site" type="button" role="tab">
                <i class="fas fa-cogs"></i> <?php echo __('admin_site_config'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                <i class="fas fa-server"></i> System Info
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Profile Tab -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-user-edit"></i> <?php echo __('admin_profile_info'); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="type" value="profile">

                                <div class="mb-3">
                                    <label for="name" class="form-label"><?php echo __('admin_full_name'); ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?php echo sanitize_output($admin_data['name'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label"><?php echo __('admin_email_readonly'); ?></label>
                                    <input type="email" class="form-control" id="email" disabled
                                        value="<?php echo sanitize_output($admin_data['email'] ?? ''); ?>">
                                    <small class="text-muted">Email cannot be changed.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label"><?php echo __('admin_phone_label'); ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo sanitize_output($admin_data['phone'] ?? ''); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label"><?php echo __('admin_address_label'); ?></label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo sanitize_output($admin_data['address'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3 text-muted small">
                                    <i class="fas fa-info-circle"></i>
                                    Account created on: <?php echo format_date($admin_data['created_at'] ?? ''); ?>
                                </div>

                                <?php echo csrf_input_field(); ?>

                                <button type="submit" name="profile_submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo __('admin_save_profile'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Password Tab -->
        <div class="tab-pane fade" id="password" role="tabpanel">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-key"></i> <?php echo __('admin_change_password'); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="type" value="password">

                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    For security reasons, you'll need to enter your current password to set a new one.
                                </div>

                                <div class="mb-3">
                                    <label for="current_password" class="form-label"><?php echo __('admin_current_password'); ?> *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label"><?php echo __('admin_new_password'); ?> *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password"
                                        placeholder="Minimum 6 characters" required>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label"><?php echo __('admin_confirm_password'); ?> *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>

                                <?php echo csrf_input_field(); ?>

                                <button type="submit" name="password_submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> <?php echo __('admin_update_password'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site Settings Tab -->
        <div class="tab-pane fade" id="site" role="tabpanel">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-cogs"></i> <?php echo __('admin_site_config'); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="type" value="site">

                                <div class="mb-3">
                                    <label for="whatsapp_number" class="form-label"><?php echo __('admin_whatsapp'); ?></label>
                                    <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number"
                                        value="<?php echo sanitize_output(get_site_setting('whatsapp_number') ?? ''); ?>"
                                        placeholder="6281234567890">
                                    <small class="text-muted">Format: Country code + number (e.g., 6281234567890 for Indonesia)</small>
                                </div>

                                <div class="mb-3">
                                    <label for="admin_email" class="form-label"><?php echo __('admin_admin_email'); ?></label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_email"
                                        value="<?php echo sanitize_output(get_site_setting('admin_email') ?? ''); ?>"
                                        placeholder="admin@example.com">
                                    <small class="text-muted">Email for order notifications</small>
                                </div>

                                <hr>

                                <h6 class="mb-3"><i class="fas fa-robot"></i> <?php echo __('admin_ai_config'); ?></h6>
                                
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Google Gemini AI (Free Tier)</strong><br>
                                    Get your free API key from: <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a><br>
                                    <strong>Rate Limits:</strong> 15 requests/minute, 1,500 requests/day<br>
                                    <strong>Model:</strong> gemini-1.5-flash (fast and efficient)<br>
                                    <small class="text-muted">Leave empty to use keyword-based fallback only</small>
                                </div>

                                <div class="mb-3">
                                    <label for="gemini_api_key" class="form-label">Gemini API Key</label>
                                    <input type="text" class="form-control font-monospace" id="gemini_api_key" name="gemini_api_key"
                                        value="<?php echo sanitize_output(get_site_setting('gemini_api_key') ?? ''); ?>"
                                        placeholder="AIzaSy...">
                                    <small class="text-muted">
                                        Status: 
                                        <?php if (!empty(get_site_setting('gemini_api_key'))): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Configured (AI chat enabled)</span>
                                        <?php else: ?>
                                            <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Not configured (using keyword fallback)</span>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <hr>

                                <h6 class="mb-3"><i class="fas fa-credit-card"></i> <?php echo __('admin_midtrans_config'); ?></h6>
                                
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Midtrans Sandbox (Testing)</strong><br>
                                    Get your sandbox keys from: <a href="https://dashboard.sandbox.midtrans.com/settings/config_info" target="_blank">Midtrans Dashboard</a><br>
                                    <small class="text-muted">Leave empty to use payment simulator mode</small>
                                </div>

                                <div class="mb-3">
                                    <label for="midtrans_client_key" class="form-label">Midtrans Client Key</label>
                                    <input type="text" class="form-control font-monospace" id="midtrans_client_key" name="midtrans_client_key"
                                        value="<?php echo sanitize_output(get_site_setting('midtrans_client_key') ?? ''); ?>"
                                        placeholder="SB-Mid-client-...">
                                </div>

                                <div class="mb-3">
                                    <label for="midtrans_server_key" class="form-label">Midtrans Server Key</label>
                                    <input type="text" class="form-control font-monospace" id="midtrans_server_key" name="midtrans_server_key"
                                        value="<?php echo sanitize_output(get_site_setting('midtrans_server_key') ?? ''); ?>"
                                        placeholder="SB-Mid-server-...">
                                    <small class="text-muted">
                                        Status: 
                                        <?php if (!empty(get_site_setting('midtrans_server_key'))): ?>
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Configured (Midtrans payment enabled)</span>
                                        <?php else: ?>
                                            <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Not configured (using payment simulator)</span>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <?php echo csrf_input_field(); ?>

                                <button type="submit" name="site_submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> <?php echo __('admin_save_site_config'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info Tab -->
        <div class="tab-pane fade" id="system" role="tabpanel">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-server"></i> System Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Site Name</label>
                                    <p><strong class="brand-text"><?php echo SITE_NAME; ?></strong></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Site URL</label>
                                    <p><?php echo SITE_URL; ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted">PHP Version</label>
                                    <p><?php echo phpversion(); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Server Software</label>
                                    <p><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                </div>
                            </div>

                            <hr>

                            <h6 class="text-muted mb-3">Database Statistics</h6>

                            <?php
                            $db_stats = [];

                            // Count records
                            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
                            $db_stats['users'] = $result->fetch_assoc()['count'];

                            $result = $conn->query("SELECT COUNT(*) as count FROM cars");
                            $db_stats['cars'] = $result->fetch_assoc()['count'];

                            $result = $conn->query("SELECT COUNT(*) as count FROM orders");
                            $db_stats['orders'] = $result->fetch_assoc()['count'];

                            $result = $conn->query("SELECT COUNT(*) as count FROM car_brands");
                            $db_stats['brands'] = $result->fetch_assoc()['count'];
                            ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Total Users</label>
                                    <p><?php echo $db_stats['users']; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Total Cars</label>
                                    <p><?php echo $db_stats['cars']; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Total Orders</label>
                                    <p><?php echo $db_stats['orders']; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Car Brands</label>
                                    <p><?php echo $db_stats['brands']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>