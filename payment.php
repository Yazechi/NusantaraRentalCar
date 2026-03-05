<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('choose_payment');
require_once __DIR__ . '/includes/header.php';

require_login();

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$order_id) {
    set_flash_message('danger', 'Invalid order.');
    redirect(SITE_URL . '/my-orders.php');
}

// Get order details
$stmt = $conn->prepare("SELECT o.*, c.name AS car_name, cb.name AS brand_name, c.image_main
        FROM orders o 
        JOIN cars c ON o.car_id = c.id 
        JOIN car_brands cb ON c.brand_id = cb.id
        WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    set_flash_message('danger', 'Order not found.');
    redirect(SITE_URL . '/my-orders.php');
}

// If already paid, redirect to receipt
if ($order['payment_status'] === 'paid') {
    redirect(SITE_URL . '/receipt.php?order_id=' . $order_id);
}

// Midtrans Sandbox client key
$midtrans_client_key = get_site_setting('midtrans_client_key') ?? 'SB-Mid-client-XXXXXXXXXXXXXXXX';
$midtrans_is_production = false;
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="mb-4"><i class="fas fa-credit-card"></i> <?php echo __('choose_payment'); ?></h3>
                
                <!-- Order Summary -->
                <div class="card mb-4 border">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <?php if (!empty($order['image_main'])): ?>
                                    <img src="<?php echo UPLOAD_URL . sanitize_output($order['image_main']); ?>" class="img-fluid rounded" alt="Car">
                                <?php else: ?>
                                    <div class="bg-secondary d-flex align-items-center justify-content-center rounded" style="height: 80px;">
                                        <i class="fas fa-car fa-2x text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-1"><?php echo sanitize_output($order['brand_name'] . ' ' . $order['car_name']); ?></h5>
                                <small class="text-muted">
                                    <?php echo __('order_id'); ?>: #<?php echo (int)$order['id']; ?><br>
                                    <?php echo format_date($order['rental_start_date']); ?> - <?php echo format_date($order['rental_end_date']); ?>
                                    (<?php echo (int)$order['duration_days']; ?> <?php echo __('days'); ?>)
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php if ($order['discount_percent'] > 0): ?>
                                    <small class="text-muted"><s><?php echo format_currency($order['original_price']); ?></s></small><br>
                                    <span class="badge bg-success mb-1">-<?php echo (int)$order['discount_percent']; ?>% <?php echo __('discount_' . $order['discount_type']); ?></span><br>
                                <?php endif; ?>
                                <h4 class="text-primary mb-0"><?php echo format_currency($order['total_price']); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted mb-4"><?php echo __('payment_instructions'); ?></p>

                <!-- Payment Method Options Display -->
                <div class="mb-4">
                    <h5 class="mb-3"><?php echo __('available_payment_methods'); ?></h5>
                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-4">
                            <div class="payment-method-card p-3 border rounded text-center" data-method="qris">
                                <i class="fas fa-qrcode fa-2x text-primary mb-2"></i>
                                <div class="fw-bold small">QRIS</div>
                                <small class="text-muted">GoPay, OVO, Dana, etc.</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="payment-method-card p-3 border rounded text-center" data-method="bank_transfer">
                                <i class="fas fa-university fa-2x text-primary mb-2"></i>
                                <div class="fw-bold small">Bank Transfer (VA)</div>
                                <small class="text-muted">BCA, BNI, BRI, Mandiri</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="payment-method-card p-3 border rounded text-center" data-method="credit_card">
                                <i class="fas fa-credit-card fa-2x text-primary mb-2"></i>
                                <div class="fw-bold small"><?php echo __('credit_debit_card'); ?></div>
                                <small class="text-muted">Visa, Mastercard, JCB</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="payment-method-card p-3 border rounded text-center" data-method="gopay">
                                <i class="fas fa-wallet fa-2x text-success mb-2"></i>
                                <div class="fw-bold small">GoPay</div>
                                <small class="text-muted">E-Wallet</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="payment-method-card p-3 border rounded text-center" data-method="shopeepay">
                                <i class="fas fa-mobile-alt fa-2x text-warning mb-2"></i>
                                <div class="fw-bold small">ShopeePay</div>
                                <small class="text-muted">E-Wallet</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="payment-method-card p-3 border rounded text-center" data-method="cstore">
                                <i class="fas fa-store fa-2x text-info mb-2"></i>
                                <div class="fw-bold small"><?php echo __('convenience_store'); ?></div>
                                <small class="text-muted">Indomaret, Alfamart</small>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted"><i class="fas fa-info-circle"></i> <?php echo __('payment_method_note'); ?></small>
                </div>

                <!-- Payment Methods via Midtrans Snap -->
                <div class="d-grid gap-2 mb-3">
                    <button id="pay-button" class="btn btn-primary btn-lg">
                        <i class="fas fa-lock me-2"></i> <?php echo __('pay_now'); ?> - <?php echo format_currency($order['total_price']); ?>
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?php echo SITE_URL; ?>/my-orders.php" class="text-muted">
                        <i class="fas fa-arrow-left"></i> <?php echo __('pay_later'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Midtrans Snap JS -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="<?php echo sanitize_output($midtrans_client_key); ?>"></script>

<script>
document.getElementById('pay-button').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

    // Request snap token from server
    fetch('<?php echo SITE_URL; ?>/api/payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: <?php echo (int)$order_id; ?> })
    })
    .then(res => res.json())
    .then(data => {
        if (data.token) {
            window.snap.pay(data.token, {
                onSuccess: function(result) {
                    // Update payment status and redirect to receipt
                    updatePayment(result, 'paid');
                },
                onPending: function(result) {
                    updatePayment(result, 'pending');
                },
                onError: function(result) {
                    updatePayment(result, 'failed');
                },
                onClose: function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-lock me-2"></i> <?php echo __('pay_now'); ?> - <?php echo format_currency($order['total_price']); ?>';
                }
            });
        } else {
            // Midtrans not configured - use simulation
            simulatePayment();
        }
    })
    .catch(err => {
        console.error('Payment error:', err);
        // Fallback to simulation if Midtrans unavailable
        simulatePayment();
    });
});

function simulatePayment() {
    // Show payment method selection modal for simulation
    const methods = [
        { id: 'qris', name: 'QRIS (GoPay, OVO, Dana)', icon: 'fa-qrcode' },
        { id: 'bca_va', name: 'BCA Virtual Account', icon: 'fa-university' },
        { id: 'bni_va', name: 'BNI Virtual Account', icon: 'fa-university' },
        { id: 'bri_va', name: 'BRI Virtual Account', icon: 'fa-university' },
        { id: 'mandiri_va', name: 'Mandiri Virtual Account', icon: 'fa-university' },
        { id: 'credit_card', name: 'Credit/Debit Card', icon: 'fa-credit-card' },
        { id: 'gopay', name: 'GoPay', icon: 'fa-wallet' },
        { id: 'shopeepay', name: 'ShopeePay', icon: 'fa-mobile-alt' },
        { id: 'cstore', name: 'Convenience Store (Indomaret/Alfamart)', icon: 'fa-store' }
    ];

    let modal = document.getElementById('sim-payment-modal');
    if (modal) modal.remove();

    modal = document.createElement('div');
    modal.id = 'sim-payment-modal';
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;display:flex;align-items:center;justify-content:center;';
    
    let html = `<div style="background:#fff;border-radius:12px;max-width:460px;width:90%;max-height:80vh;overflow-y:auto;padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h5 style="margin:0;"><i class="fas fa-credit-card"></i> <?php echo __('choose_payment'); ?></h5>
            <button onclick="closeSimModal()" style="border:none;background:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="alert alert-info py-2"><small><i class="fas fa-flask"></i> <strong>Midtrans Sandbox Simulator</strong> — <?php echo __('payment_simulator_note'); ?></small></div>
        <div style="display:flex;flex-direction:column;gap:8px;">`;
    
    methods.forEach(m => {
        html += `<button class="btn btn-outline-primary text-start d-flex align-items-center gap-3 py-3 sim-method-btn" onclick="completeSimPayment('${m.id}', '${m.name}')">
            <i class="fas ${m.icon} fa-lg" style="width:28px;text-align:center;"></i>
            <span>${m.name}</span>
        </button>`;
    });
    
    html += `</div>
        <div class="text-center mt-3">
            <button onclick="closeSimModal()" class="btn btn-link text-muted"><?php echo __('cancel'); ?></button>
        </div>
    </div>`;
    
    modal.innerHTML = html;
    document.body.appendChild(modal);
    
    const btn = document.getElementById('pay-button');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-lock me-2"></i> <?php echo __('pay_now'); ?> - <?php echo format_currency($order['total_price']); ?>';
}

function closeSimModal() {
    const modal = document.getElementById('sim-payment-modal');
    if (modal) modal.remove();
}

function completeSimPayment(methodId, methodName) {
    closeSimModal();
    
    // Show payment detail screen based on method
    showPaymentDetail(methodId, methodName);
}

function showPaymentDetail(methodId, methodName) {
    let modal = document.getElementById('sim-detail-modal');
    if (modal) modal.remove();

    modal = document.createElement('div');
    modal.id = 'sim-detail-modal';
    modal.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;display:flex;align-items:center;justify-content:center;';

    const orderId = 'ORDER-<?php echo (int)$order_id; ?>';
    const totalAmount = '<?php echo format_currency($order['total_price']); ?>';
    let detailContent = '';

    if (methodId === 'qris') {
        // QR Code for QRIS
        const qrData = encodeURIComponent(orderId + '-' + Date.now());
        detailContent = `
            <div class="text-center">
                <h6 class="mb-3"><i class="fas fa-qrcode"></i> Scan QR Code</h6>
                <div class="border rounded p-3 d-inline-block mb-3 bg-white">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrData}" alt="QRIS QR Code" width="200" height="200">
                </div>
                <p class="text-muted small mb-1"><?php echo __('payment_scan_qris'); ?></p>
                <p class="fw-bold fs-5 text-primary mb-3">${totalAmount}</p>
                <p class="text-muted small"><i class="fas fa-clock"></i> <?php echo __('payment_expires_in'); ?> <span id="qr-timer">15:00</span></p>
            </div>`;
    } else if (methodId.includes('_va') || methodId === 'bank_transfer') {
        // Virtual Account number
        const bankNames = {
            'bca_va': 'BCA', 'bni_va': 'BNI', 'bri_va': 'BRI',
            'mandiri_va': 'Mandiri', 'bank_transfer': 'Bank Transfer'
        };
        const bankName = bankNames[methodId] || 'Bank';
        const vaNumber = generateVANumber(methodId);
        detailContent = `
            <div class="text-center">
                <h6 class="mb-3"><i class="fas fa-university"></i> ${bankName} Virtual Account</h6>
                <p class="text-muted small mb-2"><?php echo __('payment_va_number'); ?></p>
                <div class="bg-light border rounded p-3 mb-3">
                    <span class="fw-bold fs-4 font-monospace" id="va-number">${vaNumber}</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('${vaNumber}')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="fw-bold fs-5 text-primary mb-2">${totalAmount}</p>
                <p class="text-muted small mb-1"><i class="fas fa-info-circle"></i> <?php echo __('payment_va_instruction'); ?></p>
                <p class="text-muted small"><i class="fas fa-clock"></i> <?php echo __('payment_expires_in'); ?> <span id="va-timer">24:00:00</span></p>
            </div>`;
    } else if (methodId === 'credit_card') {
        // Credit card form
        detailContent = `
            <div>
                <h6 class="mb-3"><i class="fas fa-credit-card"></i> <?php echo __('credit_debit_card'); ?></h6>
                <div class="mb-3">
                    <label class="form-label small"><?php echo __('payment_card_number'); ?></label>
                    <input type="text" class="form-control" placeholder="4811 1111 1111 1114" maxlength="19" id="sim-card-number">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small"><?php echo __('payment_card_expiry'); ?></label>
                        <input type="text" class="form-control" placeholder="02/25" maxlength="5" id="sim-card-expiry">
                    </div>
                    <div class="col-6">
                        <label class="form-label small">CVV</label>
                        <input type="text" class="form-control" placeholder="123" maxlength="4" id="sim-card-cvv">
                    </div>
                </div>
                <p class="fw-bold fs-5 text-primary text-center mb-2">${totalAmount}</p>
                <p class="text-muted small text-center"><i class="fas fa-lock"></i> <?php echo __('payment_secure_note'); ?></p>
            </div>`;
    } else if (methodId === 'gopay' || methodId === 'shopeepay') {
        // E-Wallet QR code
        const walletName = methodId === 'gopay' ? 'GoPay' : 'ShopeePay';
        const qrData = encodeURIComponent(walletName + '-' + orderId + '-' + Date.now());
        detailContent = `
            <div class="text-center">
                <h6 class="mb-3"><i class="fas fa-wallet"></i> ${walletName}</h6>
                <div class="border rounded p-3 d-inline-block mb-3 bg-white">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${qrData}" alt="${walletName} QR" width="200" height="200">
                </div>
                <p class="text-muted small mb-1"><?php echo __('payment_scan_ewallet'); ?> ${walletName}</p>
                <p class="fw-bold fs-5 text-primary mb-3">${totalAmount}</p>
                <p class="text-muted small"><i class="fas fa-clock"></i> <?php echo __('payment_expires_in'); ?> <span id="ew-timer">15:00</span></p>
            </div>`;
    } else if (methodId === 'cstore') {
        // Convenience store payment code
        const paymentCode = generatePaymentCode();
        detailContent = `
            <div class="text-center">
                <h6 class="mb-3"><i class="fas fa-store"></i> <?php echo __('convenience_store'); ?></h6>
                <p class="text-muted small mb-2"><?php echo __('payment_cstore_code'); ?></p>
                <div class="bg-light border rounded p-3 mb-3">
                    <span class="fw-bold fs-4 font-monospace" id="cstore-code">${paymentCode}</span>
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('${paymentCode}')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="fw-bold fs-5 text-primary mb-2">${totalAmount}</p>
                <p class="text-muted small mb-1"><i class="fas fa-info-circle"></i> <?php echo __('payment_cstore_instruction'); ?></p>
                <p class="text-muted small"><i class="fas fa-clock"></i> <?php echo __('payment_expires_in'); ?> <span id="cs-timer">24:00:00</span></p>
            </div>`;
    }

    const html = `<div style="background:#fff;border-radius:12px;max-width:460px;width:90%;max-height:85vh;overflow-y:auto;padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h5 style="margin:0;"><i class="fas fa-credit-card"></i> ${methodName}</h5>
            <button onclick="closeDetailModal()" style="border:none;background:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="alert alert-info py-2"><small><i class="fas fa-flask"></i> <strong>Sandbox Simulator</strong> — <?php echo __('payment_simulator_note'); ?></small></div>
        ${detailContent}
        <div class="d-grid gap-2 mt-3">
            <button class="btn btn-primary btn-lg" onclick="processSimPayment('${methodId}', '${methodName}')">
                <i class="fas fa-check-circle me-2"></i> <?php echo __('payment_confirm_btn'); ?>
            </button>
            <button onclick="closeDetailModal()" class="btn btn-link text-muted"><?php echo __('cancel'); ?></button>
        </div>
    </div>`;

    modal.innerHTML = html;
    document.body.appendChild(modal);
}

function generateVANumber(methodId) {
    const prefixes = {
        'bca_va': '190', 'bni_va': '880', 'bri_va': '260',
        'mandiri_va': '700', 'bank_transfer': '500'
    };
    const prefix = prefixes[methodId] || '999';
    let num = prefix;
    for (let i = 0; i < 10; i++) num += Math.floor(Math.random() * 10);
    return num;
}

function generatePaymentCode() {
    let code = '';
    for (let i = 0; i < 16; i++) {
        if (i > 0 && i % 4 === 0) code += ' ';
        code += Math.floor(Math.random() * 10);
    }
    return code;
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text.replace(/\s/g, '')).then(() => {
        const btn = event.target.closest('button');
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => btn.innerHTML = origHtml, 1500);
    });
}

function closeDetailModal() {
    const modal = document.getElementById('sim-detail-modal');
    if (modal) modal.remove();
}

function processSimPayment(methodId, methodName) {
    closeDetailModal();
    const btn = document.getElementById('pay-button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing ' + methodName + '...';
    
    const result = {
        order_id: 'ORDER-<?php echo (int)$order_id; ?>',
        transaction_status: 'settlement',
        payment_type: methodId,
        transaction_id: 'SIM-' + Date.now()
    };
    
    // Simulate brief processing delay
    setTimeout(() => updatePayment(result, 'paid'), 1500);
}

function updatePayment(result, status) {
    fetch('<?php echo SITE_URL; ?>/api/payment-update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            order_id: <?php echo (int)$order_id; ?>,
            payment_status: status,
            payment_method: result.payment_type || 'midtrans',
            payment_id: result.transaction_id || result.order_id || ''
        })
    })
    .then(res => res.json())
    .then(data => {
        if (status === 'paid') {
            window.location.href = '<?php echo SITE_URL; ?>/receipt.php?order_id=<?php echo (int)$order_id; ?>&success=1';
        } else if (status === 'pending') {
            window.location.href = '<?php echo SITE_URL; ?>/my-orders.php';
        } else {
            window.location.href = '<?php echo SITE_URL; ?>/payment.php?order_id=<?php echo (int)$order_id; ?>';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
