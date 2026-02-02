<?php
/**
 * تمپلیت نمایش تغییرات
 */

if (!defined('ABSPATH')) {
    exit;
}

// اطمینان از ایمنی داده‌ها
$release = isset($release) ? $release : [];
$atts = isset($atts) ? $atts : [];

if (is_wp_error($release) || empty($release)) {
    return;
}

$multiple = isset($release[0]); // آیا آرایه‌ای از انتشارها داریم؟
?>

<div class="igr-changelog <?php echo esc_attr($atts['class']); ?>">
    
    <?php if (!$multiple): // نمایش آخرین انتشار ?>
    
    <div class="igr-release latest">
        <?php if ($atts['show_title'] === 'true'): ?>
        <h3 class="igr-release-title">
            <?php echo esc_html($release['name'] ?? $release['tag_name']); ?>
            <?php if ($atts['show_date'] === 'true' && isset($release['published_at_human'])): ?>
            <span class="igr-release-date"><?php echo esc_html($release['published_at_human']); ?></span>
            <?php endif; ?>
        </h3>
        <?php endif; ?>
        
        <?php if ($atts['show_body'] === 'true' && !empty($release['body_html'])): ?>
        <div class="igr-release-body">
            <?php echo wp_kses_post($release['body_html']); ?>
        </div>
        <?php endif; ?>
        
        <div class="igr-release-meta">
            <?php if (isset($release['html_url'])): ?>
            <a href="<?php echo esc_url($release['html_url']); ?>" 
               target="_blank" 
               rel="noopener noreferrer"
               class="igr-github-link">
                <?php _e('View on GitHub', 'intellsoft-github-releases'); ?>
            </a>
            <?php endif; ?>
            
            <?php if (isset($release['zipball_url'])): ?>
            <a href="<?php echo esc_url($release['zipball_url']); ?>" 
               class="igr-download-link">
                <?php _e('Download Source', 'intellsoft-github-releases'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: // نمایش چندین انتشار ?>
    
    <div class="igr-release-history">
        <?php foreach ($release as $index => $item): ?>
        <div class="igr-release <?php echo $index === 0 ? 'latest' : 'older'; ?>">
            <h4 class="igr-release-title">
                <?php echo esc_html($item['tag_name']); ?>
                <?php if ($atts['show_dates'] === 'true' && isset($item['published_at_formatted'])): ?>
                <span class="igr-release-date"><?php echo esc_html($item['published_at_formatted']); ?></span>
                <?php endif; ?>
            </h4>
            
            <?php if (!empty($item['body_html'])): ?>
            <div class="igr-release-body">
                <?php echo wp_kses_post($item['body_html']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php endif; ?>
    
</div>