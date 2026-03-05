<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/language.php';
$page_title = __('guide_title');
require_once __DIR__ . '/includes/header.php';
?>

<div class="guide-page">
    <!-- Header -->
    <div class="guide-header text-center">
        <h2><i class="fas fa-book-open me-2"></i><?php echo __('guide_title'); ?></h2>
        <p><?php echo __('guide_subtitle'); ?></p>
    </div>

    <!-- Steps Timeline -->
    <div class="guide-timeline">
        <?php
        $steps = [
            ['icon' => 'fas fa-search',        'color' => '#0064d2', 'title' => __('guide_step1_title'), 'desc' => __('guide_step1_desc')],
            ['icon' => 'fas fa-eye',            'color' => '#0064d2', 'title' => __('guide_step2_title'), 'desc' => __('guide_step2_desc')],
            ['icon' => 'fas fa-shopping-cart',   'color' => '#28a745', 'title' => __('guide_step3_title'), 'desc' => __('guide_step3_desc')],
            ['icon' => 'fas fa-credit-card',     'color' => '#28a745', 'title' => __('guide_step4_title'), 'desc' => __('guide_step4_desc')],
            ['icon' => 'fas fa-receipt',          'color' => '#17a2b8', 'title' => __('guide_step5_title'), 'desc' => __('guide_step5_desc')],
            ['icon' => 'fas fa-robot',            'color' => '#17a2b8', 'title' => __('guide_step6_title'), 'desc' => __('guide_step6_desc')],
        ];
        foreach ($steps as $i => $step): $num = $i + 1; ?>
        <div class="guide-step">
            <div class="guide-step-num" style="background:<?php echo $step['color']; ?>"><?php echo $num; ?></div>
            <div class="guide-step-content">
                <div class="guide-step-icon" style="color:<?php echo $step['color']; ?>"><i class="<?php echo $step['icon']; ?>"></i></div>
                <div>
                    <h6><?php echo $step['title']; ?></h6>
                    <p><?php echo $step['desc']; ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tips -->
    <div class="guide-tips">
        <h5><i class="fas fa-lightbulb me-2"></i><?php echo __('guide_tips_title'); ?></h5>
        <div class="guide-tips-grid">
            <div class="guide-tip"><i class="fas fa-check-circle"></i> <?php echo __('guide_tip1'); ?></div>
            <div class="guide-tip"><i class="fas fa-check-circle"></i> <?php echo __('guide_tip2'); ?></div>
            <div class="guide-tip"><i class="fas fa-check-circle"></i> <?php echo __('guide_tip3'); ?></div>
            <div class="guide-tip"><i class="fas fa-check-circle"></i> <?php echo __('guide_tip4'); ?></div>
        </div>
    </div>

    <!-- CTA -->
    <div class="text-center mt-4">
        <a href="<?php echo SITE_URL; ?>/cars.php" class="btn btn-primary btn-lg rounded-pill px-5">
            <i class="fas fa-car me-2"></i><?php echo __('browse_cars'); ?>
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
