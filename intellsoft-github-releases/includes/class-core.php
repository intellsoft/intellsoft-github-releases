<?php
/**
 * کلاس اصلی افزونه
 */

if (!defined('ABSPATH')) {
    exit;
}

class IGR_Core {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        // بارگذاری استایل‌ها و اسکریپت‌ها
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // هوک‌های دیگر
        add_action('init', [$this, 'register_shortcodes']);
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'igr-public-style',
            IGR_PLUGIN_URL . 'public/css/public-style.css',
            [],
            IGR_VERSION
        );
        
        wp_enqueue_script(
            'igr-public-script',
            IGR_PLUGIN_URL . 'public/js/public-script.js',
            ['jquery'],
            IGR_VERSION,
            true
        );
        
        // انتقال داده به جاوااسکریپت
        wp_localize_script('igr-public-script', 'igr_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('igr_nonce')
        ]);
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'intellsoft-github-releases') === false) {
            return;
        }
        
        wp_enqueue_style(
            'igr-admin-style',
            IGR_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            IGR_VERSION
        );
        
        // اضافه کردن color picker وردپرس
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_script(
            'igr-admin-script',
            IGR_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery', 'wp-util', 'wp-color-picker'],
            IGR_VERSION,
            true
        );
    }
    
    public function register_shortcodes() {
        // شورت‌کدها در کلاس مربوطه ثبت می‌شوند
    }
    
    public static function activate() {
        // افزودن گزینه‌های پیش‌فرض
        $default_options = [
            'cache_expiry' => 6, // 6 ساعت
            'github_token' => '',
            'default_repo' => '',
            'enable_caching' => true,
            'refresh_on_save' => false,
            'background_color' => '#f8f9fa',
            'text_color' => '#495057',
            'link_color' => '#0d6efd',
            'border_color' => '#e9ecef',
        ];
        
        if (!get_option('igr_settings')) {
            add_option('igr_settings', $default_options);
        }
        
        // افزودن نقش‌های کاربری لازم
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        // پاک‌سازی کش
        if (class_exists('IGR_Cache')) {
            IGR_Cache::clear_all_cache();
        }
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        // حذف گزینه‌ها
        delete_option('igr_settings');
        delete_option('igr_repos_cache');
        
        // پاک‌سازی تمام کش‌ها
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%igr_cache_%'"
        );
    }
}