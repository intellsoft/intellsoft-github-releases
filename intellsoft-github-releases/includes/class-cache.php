<?php
/**
 * سیستم کش پیشرفته
 */

if (!defined('ABSPATH')) {
    exit;
}

class IGR_Cache {
    
    private static $cache_prefix = 'igr_cache_';
    
    /**
     * دریافت داده از کش
     */
    public static function get($key, $repo = '') {
        if (!self::is_caching_enabled()) {
            return false;
        }
        
        $cache_key = self::generate_key($key, $repo);
        $data = get_transient($cache_key);
        
        // لاگ برای دیباگ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('IGR Cache GET: ' . $cache_key . ' - ' . ($data ? 'HIT' : 'MISS'));
        }
        
        return $data;
    }
    
    /**
     * ذخیره داده در کش
     */
    public static function set($key, $data, $repo = '', $expiry = null) {
        if (!self::is_caching_enabled()) {
            return false;
        }
        
        if (null === $expiry) {
            $settings = get_option('igr_settings', []);
            $expiry = $settings['cache_expiry'] ?? IGR_CACHE_EXPIRY;
        }
        
        $cache_key = self::generate_key($key, $repo);
        $result = set_transient($cache_key, $data, $expiry);
        
        // ذخیره کلیدهای کش برای مدیریت بهتر
        self::track_cache_key($cache_key);
        
        return $result;
    }
    
    /**
     * پاک‌سازی کش خاص
     */
    public static function delete($key, $repo = '') {
        $cache_key = self::generate_key($key, $repo);
        return delete_transient($cache_key);
    }
    
    /**
     * پاک‌سازی تمام کش‌های افزونه
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        $cache_keys = get_option('igr_cache_keys', []);
        
        foreach ($cache_keys as $key) {
            delete_transient($key);
        }
        
        // پاک‌سازی از جدول options
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE %s 
                OR option_name LIKE %s",
                '_transient_igr_cache_%',
                '_transient_timeout_igr_cache_%'
            )
        );
        
        delete_option('igr_cache_keys');
        
        return true;
    }
    
    /**
     * بهینه‌سازی کش قدیمی
     */
    public static function cleanup_old_cache() {
        global $wpdb;
        
        $time = current_time('timestamp') - (30 * DAY_IN_SECONDS);
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_igr_cache_%' 
                AND option_value < %d",
                $time
            )
        );
    }
    
    /**
     * بررسی فعال بودن کش
     */
    private static function is_caching_enabled() {
        $settings = get_option('igr_settings', []);
        return $settings['enable_caching'] ?? true;
    }
    
    /**
     * تولید کلید یکتا برای کش
     */
    private static function generate_key($key, $repo = '') {
        $hash = md5($repo . $key);
        return self::$cache_prefix . $hash;
    }
    
    /**
     * ردیابی کلیدهای کش برای مدیریت بهتر
     */
    private static function track_cache_key($key) {
        $keys = get_option('igr_cache_keys', []);
        
        if (!in_array($key, $keys)) {
            $keys[] = $key;
            update_option('igr_cache_keys', $keys, false);
        }
    }
    
    /**
     * دریافت وضعیت کش
     */
    public static function get_cache_stats() {
        global $wpdb;
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                WHERE option_name LIKE %s",
                '_transient_igr_cache_%'
            )
        );
        
        $expired = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_igr_cache_%' 
                AND option_value < %d",
                current_time('timestamp')
            )
        );
        
        return [
            'total' => (int) $total,
            'expired' => (int) $expired,
            'active' => (int) $total - $expired,
            'enabled' => self::is_caching_enabled()
        ];
    }
}