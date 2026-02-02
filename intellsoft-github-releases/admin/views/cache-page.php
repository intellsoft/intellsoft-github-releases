<?php
/**
 * صفحه مدیریت کش
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    return;
}

// پیام پاک‌سازی کش
if (isset($_GET['cache_cleared'])) {
    add_settings_error('igr_messages', 'igr_message', __('Cache cleared successfully.', 'intellsoft-github-releases'), 'updated');
}

settings_errors('igr_messages');

// آمار کش
$cache_stats = IGR_Cache::get_cache_stats();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2 class="title"><?php _e('Cache Statistics', 'intellsoft-github-releases'); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Stat', 'intellsoft-github-releases'); ?></th>
                    <th><?php _e('Value', 'intellsoft-github-releases'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Total Cached Items', 'intellsoft-github-releases'); ?></td>
                    <td><?php echo esc_html($cache_stats['total']); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Active Cached Items', 'intellsoft-github-releases'); ?></td>
                    <td><?php echo esc_html($cache_stats['active']); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Expired Cached Items', 'intellsoft-github-releases'); ?></td>
                    <td><?php echo esc_html($cache_stats['expired']); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Caching Enabled', 'intellsoft-github-releases'); ?></td>
                    <td><?php echo $cache_stats['enabled'] ? __('Yes', 'intellsoft-github-releases') : __('No', 'intellsoft-github-releases'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2 class="title"><?php _e('Cache Management', 'intellsoft-github-releases'); ?></h2>
        <p><?php _e('Clearing the cache will force the plugin to fetch fresh data from GitHub on the next request.', 'intellsoft-github-releases'); ?></p>
        <p>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=igr_clear_cache'), 'igr_clear_cache')); ?>" 
               class="button button-primary">
                <?php _e('Clear All Cache', 'intellsoft-github-releases'); ?>
            </a>
        </p>
    </div>
    
    <div class="card">
        <h2 class="title"><?php _e('Cache Settings', 'intellsoft-github-releases'); ?></h2>
        <p>
            <?php _e('Cache settings can be adjusted in the', 'intellsoft-github-releases'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=intellsoft-github-releases')); ?>">
                <?php _e('main settings page', 'intellsoft-github-releases'); ?>
            </a>.
        </p>
    </div>
    
    <div class="card">
        <h2 class="title"><?php _e('Cache Optimization Tips', 'intellsoft-github-releases'); ?></h2>
        <ul>
            <li><?php _e('Set cache expiry to 6-12 hours for optimal performance', 'intellsoft-github-releases'); ?></li>
            <li><?php _e('Clear cache after major releases', 'intellsoft-github-releases'); ?></li>
            <li><?php _e('Monitor cache stats regularly', 'intellsoft-github-releases'); ?></li>
            <li><?php _e('Use a caching plugin for better overall performance', 'intellsoft-github-releases'); ?></li>
        </ul>
    </div>
</div>