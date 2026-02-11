<?php
// Admin Login Page
$project_root = dirname(__DIR__);

if (!defined('BASE_PATH')) {
    require_once $project_root . '/config/config.php';
    require_once $project_root . '/includes/security.php';
    require_once $project_root . '/includes/auth.php';
    require_once $project_root . '/includes/functions.php';
}

// Jika sudah login sebagai admin, redirect ke dashboard
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    redirect(SITE_URL . '/admin/dashboard.php');
    exit;
}

$error_message = '';
$page_title = 'Admin Login';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Security validation failed. Please try again.';
    } else {
        // Sanitize dan validasi input
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error_message = 'Email and password are required.';
        } elseif (!validate_email($email)) {
            $error_message = 'Invalid email format.';
        } else {
            // Attempt login
            $login_result = login_user($email, $password);

            if ($login_result['success']) {
                // Check if user is admin
                if ($_SESSION['role'] === 'admin') {
                    set_flash_message('success', 'Admin login successful.');
                    redirect(SITE_URL . '/admin/dashboard.php');
                    exit;
                } else {
                    // User tried to login ke admin tapi bukan admin
                    $_SESSION['user_id'] = null;
                    session_destroy();
                    $error_message = 'Only administrators can access this panel.';
                }
            } else {
                $error_message = $login_result['message'] ?? 'Login failed.';
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
        }

        .login-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 5px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .icon-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }

        .icon-wrapper i {
            font-size: 48px;
            color: #667eea;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="icon-wrapper">
                    <i class="fas fa-lock"></i>
                </div>
                <h1>Admin Panel</h1>
                <p><?php echo SITE_NAME; ?></p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo sanitize_output($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php
            $flash = get_flash_message();
            if ($flash):
            ?>
                <div class="alert alert-<?php echo sanitize_output($flash['type']); ?> alert-dismissible fade show" role="alert">
                    <?php echo sanitize_output($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                </div>

                <?php echo csrf_input_field(); ?>

                <button type="submit" class="btn-login">Login</button>
            </form>

            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #666; font-size: 13px; margin: 0;">
                    <a href="<?php echo SITE_URL; ?>/index.php" style="color: #667eea; text-decoration: none;">← Back to Home</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>