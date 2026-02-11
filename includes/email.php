<?php
// ============================================
// Email Functions
// ============================================

/**
 * Send email using PHPMailer or mail() function
 * For production, configure SMTP settings in config.php
 */
function send_email($to, $to_name, $subject, $body, $is_html = true) {
    // Check if PHPMailer is available (recommended for production)
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return send_email_smtp($to, $to_name, $subject, $body, $is_html);
    } else {
        // Fallback to PHP mail() function
        return send_email_simple($to, $to_name, $subject, $body, $is_html);
    }
}

/**
 * Send email using SMTP (PHPMailer)
 * Run: composer require phpmailer/phpmailer
 */
function send_email_smtp($to, $to_name, $subject, $body, $is_html = true) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to, $to_name);
        
        // Content
        $mail->isHTML($is_html);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        if ($is_html) {
            $mail->AltBody = strip_tags($body);
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        error_log('Email error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Failed to send email: ' . $mail->ErrorInfo];
    }
}

/**
 * Send email using PHP mail() function (simple fallback)
 */
function send_email_simple($to, $to_name, $subject, $body, $is_html = true) {
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
    
    if ($is_html) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    $result = mail($to, $subject, $body, $headers);
    
    if ($result) {
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } else {
        error_log('Email error: mail() function failed');
        return ['success' => false, 'message' => 'Failed to send email.'];
    }
}

/**
 * Send new order notification to admin
 */
function send_order_notification_admin($order_id, $user_name, $car_name, $total_price) {
    $admin_email = 'admin@nusantararental.com'; // Update with real admin email
    
    $subject = "New Order #$order_id - Nusantara Rental Car";
    
    $body = get_email_template('order_admin', [
        'order_id' => $order_id,
        'user_name' => $user_name,
        'car_name' => $car_name,
        'total_price' => format_currency($total_price),
        'admin_link' => SITE_URL . '/admin/order-detail.php?id=' . $order_id
    ]);
    
    return send_email($admin_email, 'Admin', $subject, $body);
}

/**
 * Send order confirmation to user
 */
function send_order_confirmation_user($email, $user_name, $order_id, $car_name, $rental_start, $rental_end, $total_price) {
    $subject = "Order Confirmation #$order_id - Nusantara Rental Car";
    
    $body = get_email_template('order_user', [
        'user_name' => $user_name,
        'order_id' => $order_id,
        'car_name' => $car_name,
        'rental_start' => format_date($rental_start),
        'rental_end' => format_date($rental_end),
        'total_price' => format_currency($total_price),
        'orders_link' => SITE_URL . '/my-orders.php'
    ]);
    
    return send_email($email, $user_name, $subject, $body);
}

/**
 * Send order status update notification
 */
function send_order_status_update($email, $user_name, $order_id, $status, $car_name) {
    $status_text = ucfirst($status);
    $subject = "Order #$order_id $status_text - Nusantara Rental Car";
    
    $body = get_email_template('order_status', [
        'user_name' => $user_name,
        'order_id' => $order_id,
        'status' => $status_text,
        'car_name' => $car_name,
        'orders_link' => SITE_URL . '/my-orders.php'
    ]);
    
    return send_email($email, $user_name, $subject, $body);
}

/**
 * Send password reset email
 */
function send_password_reset_email($email, $user_name, $reset_token) {
    $subject = "Password Reset Request - Nusantara Rental Car";
    
    $reset_link = SITE_URL . '/reset-password.php?token=' . $reset_token;
    
    $body = get_email_template('password_reset', [
        'user_name' => $user_name,
        'reset_link' => $reset_link
    ]);
    
    return send_email($email, $user_name, $subject, $body);
}

/**
 * Send email verification
 */
function send_verification_email($email, $user_name, $verification_token) {
    $subject = "Verify Your Email - Nusantara Rental Car";
    
    $verification_link = SITE_URL . '/verify-email.php?token=' . $verification_token;
    
    $body = get_email_template('email_verification', [
        'user_name' => $user_name,
        'verification_link' => $verification_link
    ]);
    
    return send_email($email, $user_name, $subject, $body);
}

/**
 * Get email template
 */
function get_email_template($template_name, $data = []) {
    $templates = [
        'order_admin' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">New Order Received</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #333;">Order Details</h2>
                    <p><strong>Order ID:</strong> #' . $data['order_id'] . '</p>
                    <p><strong>Customer:</strong> ' . $data['user_name'] . '</p>
                    <p><strong>Car:</strong> ' . $data['car_name'] . '</p>
                    <p><strong>Total:</strong> ' . $data['total_price'] . '</p>
                    <p style="margin-top: 30px;">
                        <a href="' . $data['admin_link'] . '" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Order Details</a>
                    </p>
                </div>
            </div>
        ',
        'order_user' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">Order Confirmed!</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px;">
                    <p>Hi ' . $data['user_name'] . ',</p>
                    <p>Thank you for your order! Your booking has been confirmed.</p>
                    <h3 style="color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px;">Order Details</h3>
                    <p><strong>Order ID:</strong> #' . $data['order_id'] . '</p>
                    <p><strong>Car:</strong> ' . $data['car_name'] . '</p>
                    <p><strong>Rental Period:</strong> ' . $data['rental_start'] . ' - ' . $data['rental_end'] . '</p>
                    <p><strong>Total Amount:</strong> ' . $data['total_price'] . '</p>
                    <p style="margin-top: 30px;">
                        <a href="' . $data['orders_link'] . '" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View My Orders</a>
                    </p>
                    <p style="margin-top: 20px; color: #666; font-size: 14px;">We will contact you shortly to confirm the details.</p>
                </div>
            </div>
        ',
        'order_status' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">Order Status Update</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px;">
                    <p>Hi ' . $data['user_name'] . ',</p>
                    <p>Your order status has been updated.</p>
                    <p><strong>Order ID:</strong> #' . $data['order_id'] . '</p>
                    <p><strong>Car:</strong> ' . $data['car_name'] . '</p>
                    <p><strong>New Status:</strong> <span style="color: #667eea; font-weight: bold;">' . $data['status'] . '</span></p>
                    <p style="margin-top: 30px;">
                        <a href="' . $data['orders_link'] . '" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">View Order Details</a>
                    </p>
                </div>
            </div>
        ',
        'password_reset' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">Password Reset</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px;">
                    <p>Hi ' . $data['user_name'] . ',</p>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <p style="margin-top: 30px;">
                        <a href="' . $data['reset_link'] . '" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
                    </p>
                    <p style="margin-top: 20px; color: #666; font-size: 14px;">This link will expire in 1 hour. If you didn\'t request this, please ignore this email.</p>
                </div>
            </div>
        ',
        'email_verification' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">Welcome to Nusantara Rental Car!</h1>
                </div>
                <div style="background: white; padding: 30px; border-radius: 0 0 10px 10px;">
                    <p>Hi ' . $data['user_name'] . ',</p>
                    <p>Thank you for registering! Please verify your email address by clicking the button below:</p>
                    <p style="margin-top: 30px;">
                        <a href="' . $data['verification_link'] . '" style="background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email</a>
                    </p>
                    <p style="margin-top: 20px; color: #666; font-size: 14px;">This link will expire in 24 hours.</p>
                </div>
            </div>
        '
    ];
    
    return $templates[$template_name] ?? '';
}
