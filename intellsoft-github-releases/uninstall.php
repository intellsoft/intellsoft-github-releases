<?php
/**
 * فایل حذف افزونه
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// حذف تمام داده‌های افزونه
$options = [
    'igr_settings',
    'igr_cache_keys',
    'igr_repos_cache',
];

foreach ($options as $option) {
    delete_option($option);
}

// حذف تمام ترانزینت‌ها
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options} 
    WHERE option_name LIKE '_transient_igr_%' 
    OR option_name LIKE '_transient_timeout_igr_%'"
);