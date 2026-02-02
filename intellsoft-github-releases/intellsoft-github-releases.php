<?php
/**
 * Plugin Name: IntellSoft GitHub Releases
 * Plugin URI: https://intellsoft.ir/
 * Description: نمایش خودکار آخرین نسخه و تغییرات نرم‌افزار از ریپازیتوری GitHub
 * Version: 1.0.0
 * Author: محمدعلی عباسپور
 * License: GPL v2 or later
 * Text Domain: intellsoft-github-releases
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های افزونه
define('IGR_VERSION', '1.0.0');
define('IGR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IGR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IGR_CACHE_EXPIRY', 6 * HOUR_IN_SECONDS);

// بارگذاری فایل‌های اصلی
require_once IGR_PLUGIN_DIR . 'includes/class-core.php';
require_once IGR_PLUGIN_DIR . 'includes/class-cache.php';
require_once IGR_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once IGR_PLUGIN_DIR . 'includes/class-shortcode.php';

// بارگذاری بخش مدیریتی در صورت نیاز
if (is_admin()) {
    require_once IGR_PLUGIN_DIR . 'includes/class-admin.php';
}

// راه‌اندازی افزونه
function igr_init() {
    // راه‌اندازی ترجمه
    load_plugin_textdomain(
        'intellsoft-github-releases',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
    
    // نمونه‌سازی کلاس اصلی
    $core = new IGR_Core();
    $core->init();
    
    // نمونه‌سازی شورت‌کد
    $shortcode = new IGR_Shortcode();
    $shortcode->init();
    
    // نمونه‌سازی بخش مدیریتی
    if (is_admin()) {
        $admin = new IGR_Admin();
        $admin->init();
    }
}

add_action('plugins_loaded', 'igr_init');

// فعال‌سازی افزونه
register_activation_hook(__FILE__, ['IGR_Core', 'activate']);
// غیرفعال‌سازی افزونه
register_deactivation_hook(__FILE__, ['IGR_Core', 'deactivate']);