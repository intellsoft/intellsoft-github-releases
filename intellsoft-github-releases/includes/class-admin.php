<?php
/**
 * بخش مدیریت افزونه
 */

if (!defined('ABSPATH')) {
    exit;
}

class IGR_Admin {
    
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_igr_clear_cache', [$this, 'clear_cache']);
        add_action('wp_ajax_igr_test_connection', [$this, 'test_connection_ajax']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // افزودن لینک تنظیمات در صفحه افزونه‌ها
        add_filter('plugin_action_links_' . plugin_basename(IGR_PLUGIN_DIR . 'intellsoft-github-releases.php'), 
            [$this, 'add_settings_link']);
        
        // ثبت هوک‌های هفتگی برای پاکسازی کش قدیمی
        add_action('igr_weekly_cleanup', ['IGR_Cache', 'cleanup_old_cache']);
        
        // برنامه‌ریزی رویداد هفتگی اگر وجود ندارد
        if (!wp_next_scheduled('igr_weekly_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'igr_weekly_cleanup');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('GitHub Releases', 'intellsoft-github-releases'),
            __('GitHub Releases', 'intellsoft-github-releases'),
            'manage_options',
            'intellsoft-github-releases',
            [$this, 'settings_page'],
            'dashicons-github',
            80
        );
        
        add_submenu_page(
            'intellsoft-github-releases',
            __('Settings', 'intellsoft-github-releases'),
            __('Settings', 'intellsoft-github-releases'),
            'manage_options',
            'intellsoft-github-releases',
            [$this, 'settings_page']
        );
        
        add_submenu_page(
            'intellsoft-github-releases',
            __('Cache Management', 'intellsoft-github-releases'),
            __('Cache', 'intellsoft-github-releases'),
            'manage_options',
            'intellsoft-github-releases-cache',
            [$this, 'cache_page']
        );
        
        add_submenu_page(
            'intellsoft-github-releases',
            __('Documentation', 'intellsoft-github-releases'),
            __('Documentation', 'intellsoft-github-releases'),
            'manage_options',
            'intellsoft-github-releases-docs',
            [$this, 'docs_page']
        );
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'intellsoft-github-releases'));
        }
        
        // نمایش صفحه تنظیمات به صورت inline
        $this->render_settings_page_inline();
    }
    
    public function cache_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'intellsoft-github-releases'));
        }
        
        $this->render_cache_page_inline();
    }
    
    public function docs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'intellsoft-github-releases'));
        }
        
        $this->render_docs_page_inline();
    }
    
    public function register_settings() {
        register_setting('igr_settings_group', 'igr_settings', [$this, 'sanitize_settings']);
        
        // بخش اصلی
        add_settings_section(
            'igr_main_section',
            __('Main Settings', 'intellsoft-github-releases'),
            [$this, 'main_section_callback'],
            'intellsoft-github-releases'
        );
        
        add_settings_field(
            'github_token',
            __('GitHub Token', 'intellsoft-github-releases'),
            [$this, 'token_field_callback'],
            'intellsoft-github-releases',
            'igr_main_section'
        );
        
        add_settings_field(
            'default_repo',
            __('Default Repository', 'intellsoft-github-releases'),
            [$this, 'repo_field_callback'],
            'intellsoft-github-releases',
            'igr_main_section'
        );
        
        // بخش نمایش
        add_settings_section(
            'igr_display_section',
            __('Display Settings', 'intellsoft-github-releases'),
            [$this, 'display_section_callback'],
            'intellsoft-github-releases'
        );
        
        add_settings_field(
            'show_update_badge',
            __('Show Update Badge', 'intellsoft-github-releases'),
            [$this, 'update_badge_callback'],
            'intellsoft-github-releases',
            'igr_display_section'
        );
        
        add_settings_field(
            'date_format',
            __('Date Format', 'intellsoft-github-releases'),
            [$this, 'date_format_callback'],
            'intellsoft-github-releases',
            'igr_display_section'
        );
        
        add_settings_field(
            'background_color',
            __('Background Color', 'intellsoft-github-releases'),
            [$this, 'background_color_callback'],
            'intellsoft-github-releases',
            'igr_display_section'
        );
        
        add_settings_field(
            'text_color',
            __('Text Color', 'intellsoft-github-releases'),
            [$this, 'text_color_callback'],
            'intellsoft-github-releases',
            'igr_display_section'
        );
        
        add_settings_field(
            'link_color',
            __('Link Color', 'intellsoft-github-releases'),
            [$this, 'link_color_callback'],
            'intellsoft-github-releases',
            'igr_display_section'
        );
        
        add_settings_field(
            'border_color',
            __('Border Color', 'intellsoft-github-releases'),
            [$this, 'border_color_callback'],
            'intellsoft-github-releases',
            'igr_display_section'
        );
        
        // بخش کش
        add_settings_section(
            'igr_cache_section',
            __('Cache Settings', 'intellsoft-github-releases'),
            [$this, 'cache_section_callback'],
            'intellsoft-github-releases'
        );
        
        add_settings_field(
            'enable_caching',
            __('Enable Caching', 'intellsoft-github-releases'),
            [$this, 'cache_enable_callback'],
            'intellsoft-github-releases',
            'igr_cache_section'
        );
        
        add_settings_field(
            'cache_expiry',
            __('Cache Expiry (hours)', 'intellsoft-github-releases'),
            [$this, 'cache_expiry_callback'],
            'intellsoft-github-releases',
            'igr_cache_section'
        );
        
        add_settings_field(
            'auto_refresh',
            __('Auto Refresh Cache', 'intellsoft-github-releases'),
            [$this, 'auto_refresh_callback'],
            'intellsoft-github-releases',
            'igr_cache_section'
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = [];
        $current_settings = get_option('igr_settings', []);
        
        // توکن GitHub
        $sanitized['github_token'] = sanitize_text_field($input['github_token'] ?? '');
        
        // اگر توکن تغییر کرده، کش را پاک کن
        if (isset($current_settings['github_token']) && 
            $current_settings['github_token'] !== $sanitized['github_token']) {
            IGR_Cache::clear_all_cache();
        }
        
        // ریپازیتوری
        $sanitized['default_repo'] = sanitize_text_field($input['default_repo'] ?? '');
        
        // تنظیمات نمایش
        $sanitized['show_update_badge'] = isset($input['show_update_badge']) ? true : false;
        $sanitized['date_format'] = sanitize_text_field($input['date_format'] ?? 'human');
        
        // رنگ‌ها
        $sanitized['background_color'] = $this->sanitize_hex_color($input['background_color'] ?? '');
        $sanitized['text_color'] = $this->sanitize_hex_color($input['text_color'] ?? '');
        $sanitized['link_color'] = $this->sanitize_hex_color($input['link_color'] ?? '');
        $sanitized['border_color'] = $this->sanitize_hex_color($input['border_color'] ?? '');
        
        // تنظیمات کش
        $sanitized['enable_caching'] = isset($input['enable_caching']) ? true : false;
        
        // محدود کردن مقدار cache_expiry بین 1 تا 720
        $cache_expiry = absint($input['cache_expiry'] ?? 6);
        $sanitized['cache_expiry'] = min($cache_expiry, 720); // حداکثر 30 روز
        $sanitized['cache_expiry'] = max($sanitized['cache_expiry'], 1); // حداقل 1 ساعت
        
        $sanitized['auto_refresh'] = isset($input['auto_refresh']) ? true : false;
        
        // اعتبارسنجی فرمت ریپو
        if (!empty($sanitized['default_repo'])) {
            $pattern = '/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/';
            if (!preg_match($pattern, $sanitized['default_repo'])) {
                add_settings_error(
                    'igr_settings',
                    'invalid_repo',
                    __('Invalid repository format. Please use: username/repository', 'intellsoft-github-releases'),
                    'error'
                );
            }
        }
        
        return $sanitized;
    }
    
    /**
     * سانی‌تایز کردن رنگ hex
     */
    private function sanitize_hex_color($color) {
        $color = sanitize_text_field($color);
        
        if (empty($color)) {
            return '';
        }
        
        // بررسی فرمت hex (با یا بدون #)
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }
        
        // اگر با # شروع نشده بود، اضافه کن
        if (preg_match('/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return '#' . $color;
        }
        
        return '';
    }
    
    // ==================== Callback Methods ====================
    
    public function main_section_callback() {
        echo '<p>' . __('Configure the main settings for GitHub Releases.', 'intellsoft-github-releases') . '</p>';
    }
    
    public function display_section_callback() {
        echo '<p>' . __('Configure how release information is displayed on your site.', 'intellsoft-github-releases') . '</p>';
    }
    
    public function cache_section_callback() {
        echo '<p>' . __('Configure caching settings to optimize performance and reduce API calls.', 'intellsoft-github-releases') . '</p>';
    }
    
public function token_field_callback() {
    $settings = get_option('igr_settings');
    $token = $settings['github_token'] ?? '';
    
    // مقدار پیش‌فرض خالی - همیشه فیلد خالی نشان داده می‌شود
    // فقط اگر توکن وجود دارد، در نمایش نشان می‌دهیم
    $token_display = !empty($token) ? '••••••••' . substr($token, -4) : __('(empty)', 'intellsoft-github-releases');
    
    echo '<input type="password" id="github_token" name="igr_settings[github_token]" 
         value="" class="regular-text" placeholder="' . __('Leave empty for public repositories', 'intellsoft-github-releases') . '" />
         <button type="button" id="toggle_token" class="button button-small">' . __('Show/Hide', 'intellsoft-github-releases') . '</button>
         <p class="description">' . __('Token currently set:', 'intellsoft-github-releases') . ' <code>' . esc_html($token_display) . '</code></p>
         <p class="description">' . __('To update token, enter new value. To clear token, leave empty and save.', 'intellsoft-github-releases') . '</p>
         <p><a href="https://github.com/settings/tokens/new?scopes=repo&description=WordPress+GitHub+Releases" 
               target="_blank" class="button button-small">' . __('Generate Token', 'intellsoft-github-releases') . '</a></p>';
}

    
    public function repo_field_callback() {
        $settings = get_option('igr_settings');
        $repo = $settings['default_repo'] ?? '';
        
        echo '<input type="text" id="default_repo" name="igr_settings[default_repo]" 
             value="' . esc_attr($repo) . '" class="regular-text" placeholder="username/repository" />
             <p class="description">' . __('Default repository to use in shortcodes if not specified (format: username/repository)', 'intellsoft-github-releases') . '</p>';
    }
    
    public function update_badge_callback() {
        $settings = get_option('igr_settings');
        $enabled = $settings['show_update_badge'] ?? true;
        
        echo '<label><input type="checkbox" id="show_update_badge" name="igr_settings[show_update_badge]" value="1" ' . 
             checked($enabled, true, false) . '> ' . 
             __('Show "Recently Updated" badge on new releases', 'intellsoft-github-releases') . '</label>
             <p class="description">' . __('Shows a badge for releases published within the last 7 days', 'intellsoft-github-releases') . '</p>';
    }
    
    public function date_format_callback() {
        $settings = get_option('igr_settings');
        $format = $settings['date_format'] ?? 'human';
        
        echo '<select id="date_format" name="igr_settings[date_format]">
                <option value="human" ' . selected($format, 'human', false) . '>' . __('Human readable (e.g., 2 days ago)', 'intellsoft-github-releases') . '</option>
                <option value="full" ' . selected($format, 'full', false) . '>' . __('Full date', 'intellsoft-github-releases') . '</option>
                <option value="both" ' . selected($format, 'both', false) . '>' . __('Both formats', 'intellsoft-github-releases') . '</option>
              </select>
              <p class="description">' . __('Choose how dates are displayed in the changelog', 'intellsoft-github-releases') . '</p>';
    }
    
    public function background_color_callback() {
        $settings = get_option('igr_settings');
        $color = $settings['background_color'] ?? '#f8f9fa';
        
        echo '<input type="text" id="background_color" name="igr_settings[background_color]" 
             value="' . esc_attr($color) . '" class="igr-color-field" data-default-color="#f8f9fa" />
             <p class="description">' . __('Default: #f8f9fa', 'intellsoft-github-releases') . '</p>';
    }
    
    public function text_color_callback() {
        $settings = get_option('igr_settings');
        $color = $settings['text_color'] ?? '#495057';
        
        echo '<input type="text" id="text_color" name="igr_settings[text_color]" 
             value="' . esc_attr($color) . '" class="igr-color-field" data-default-color="#495057" />
             <p class="description">' . __('Default: #495057', 'intellsoft-github-releases') . '</p>';
    }
    
    public function link_color_callback() {
        $settings = get_option('igr_settings');
        $color = $settings['link_color'] ?? '#0d6efd';
        
        echo '<input type="text" id="link_color" name="igr_settings[link_color]" 
             value="' . esc_attr($color) . '" class="igr-color-field" data-default-color="#0d6efd" />
             <p class="description">' . __('Default: #0d6efd', 'intellsoft-github-releases') . '</p>';
    }
    
    public function border_color_callback() {
        $settings = get_option('igr_settings');
        $color = $settings['border_color'] ?? '#e9ecef';
        
        echo '<input type="text" id="border_color" name="igr_settings[border_color]" 
             value="' . esc_attr($color) . '" class="igr-color-field" data-default-color="#e9ecef" />
             <p class="description">' . __('Default: #e9ecef', 'intellsoft-github-releases') . '</p>';
    }
    
    public function cache_enable_callback() {
        $settings = get_option('igr_settings');
        $enabled = $settings['enable_caching'] ?? true;
        
        echo '<label><input type="checkbox" id="enable_caching" name="igr_settings[enable_caching]" value="1" ' . 
             checked($enabled, true, false) . '> ' . 
             __('Enable caching to reduce API calls and improve performance', 'intellsoft-github-releases') . '</label>
             <p class="description">' . __('Recommended for production sites', 'intellsoft-github-releases') . '</p>';
    }
    
    public function cache_expiry_callback() {
        $settings = get_option('igr_settings');
        $expiry = $settings['cache_expiry'] ?? 6;
        
        echo '<input type="number" id="cache_expiry" name="igr_settings[cache_expiry]" 
             value="' . esc_attr($expiry) . '" class="small-text" min="1" max="720" /> ' . 
             __('hours', 'intellsoft-github-releases') . '
             <p class="description">' . __('How long to cache GitHub API responses. Maximum: 720 hours (30 days).', 'intellsoft-github-releases') . '</p>
             <div class="igr-cache-slider">
                <input type="range" min="1" max="24" value="' . esc_attr(min($expiry, 24)) . '" class="slider" id="cache_slider">
                <span id="slider_value">' . esc_html(min($expiry, 24)) . 'h</span>
             </div>';
    }
    
    public function auto_refresh_callback() {
        $settings = get_option('igr_settings');
        $enabled = $settings['auto_refresh'] ?? false;
        
        echo '<label><input type="checkbox" id="auto_refresh" name="igr_settings[auto_refresh]" value="1" ' . 
             checked($enabled, true, false) . '> ' . 
             __('Automatically refresh cache on page visit after expiry', 'intellsoft-github-releases') . '</label>
             <p class="description">' . __('Visitors will see cached data while fresh data loads in background', 'intellsoft-github-releases') . '</p>';
    }
    
    // ==================== Other Methods ====================
    
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
        
        wp_enqueue_script(
            'igr-admin-script',
            IGR_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery', 'wp-util', 'wp-color-picker'],
            IGR_VERSION,
            true
        );
        
        // انتقال داده به جاوااسکریپت
        wp_localize_script('igr-admin-script', 'igr_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('igr_admin_nonce'),
            'strings' => [
                'show' => __('Show', 'intellsoft-github-releases'),
                'hide' => __('Hide', 'intellsoft-github-releases'),
                'testing' => __('Testing...', 'intellsoft-github-releases'),
                'test_connection' => __('Test Connection', 'intellsoft-github-releases'),
            ]
        ]);
    }
    
    public function clear_cache() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'igr_clear_cache')) {
            wp_die(__('Security check failed', 'intellsoft-github-releases'));
        }
        
        IGR_Cache::clear_all_cache();
        
        wp_redirect(add_query_arg([
            'page' => 'intellsoft-github-releases-cache',
            'cache_cleared' => '1'
        ], admin_url('admin.php')));
        exit;
    }
    
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=intellsoft-github-releases'),
            __('Settings', 'intellsoft-github-releases')
        );
        $cache_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=intellsoft-github-releases-cache'),
            __('Cache', 'intellsoft-github-releases')
        );
        array_unshift($links, $cache_link, $settings_link);
        return $links;
    }
    
    public function test_connection_ajax() {
        check_ajax_referer('igr_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'intellsoft-github-releases')]);
        }
        
        $repo = sanitize_text_field($_POST['repo'] ?? '');
        $token = sanitize_text_field($_POST['token'] ?? '');
        
        if (empty($repo)) {
            wp_send_json_error(['message' => __('Repository is required', 'intellsoft-github-releases')]);
        }
        
        $api = new IGR_API_Handler($token);
        $result = $api->get_latest_release($repo);
        
        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ]);
        } else {
            // بررسی rate limit
            $rate_limit = $api->get_rate_limit();
            
            $response_data = [
                'message' => __('Connection successful!', 'intellsoft-github-releases'),
                'version' => $result['tag_name'] ?? 'N/A',
                'date' => $result['published_at_formatted'] ?? 'N/A',
                'name' => $result['name'] ?? $result['tag_name'] ?? 'N/A',
            ];
            
            if (!is_wp_error($rate_limit)) {
                $response_data['rate_limit'] = $rate_limit['rate']['remaining'] . '/' . $rate_limit['rate']['limit'];
            }
            
            wp_send_json_success($response_data);
        }
    }
    
    // ==================== Inline Page Renderers ====================
    
    private function render_settings_page_inline() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (isset($_GET['settings-updated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully.', 'intellsoft-github-releases'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="igr-admin-container">
                <div class="igr-admin-main">
                    <div class="card">
                        <h2 class="title"><?php _e('Quick Setup', 'intellsoft-github-releases'); ?></h2>
                        <ol>
                            <li><?php _e('Create a GitHub Personal Access Token (optional for public repos)', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Enter the token and repository details below', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Use the shortcode on your product pages', 'intellsoft-github-releases'); ?></li>
                        </ol>
                    </div>
                    
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('igr_settings_group');
                        do_settings_sections('intellsoft-github-releases');
                        submit_button();
                        ?>
                    </form>
                    
                    <div class="card">
                        <h3><?php _e('Test Connection', 'intellsoft-github-releases'); ?></h3>
                        <p><?php _e('Test the connection to GitHub API:', 'intellsoft-github-releases'); ?></p>
                        <input type="text" id="test_repo" class="regular-text" placeholder="username/repository" value="<?php echo esc_attr(get_option('igr_settings')['default_repo'] ?? ''); ?>">
                        <button type="button" id="test_connection" class="button button-primary">
                            <?php _e('Test Connection', 'intellsoft-github-releases'); ?>
                        </button>
                        <div id="test_result" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
                <div class="igr-admin-sidebar">
                    <div class="card">
                        <h3><?php _e('Shortcode Examples', 'intellsoft-github-releases'); ?></h3>
                        <p><strong><?php _e('Basic Usage:', 'intellsoft-github-releases'); ?></strong></p>
                        <code>[intellsoft_changelog repo="username/repository"]</code>
                        
                        <p><strong><?php _e('Multiple releases:', 'intellsoft-github-releases'); ?></strong></p>
                        <code>[intellsoft_changelog repo="username/repository" limit="5"]</code>
                        
                        <p><strong><?php _e('Show GitHub button only for latest:', 'intellsoft-github-releases'); ?></strong></p>
                        <code>[intellsoft_changelog repo="username/repository" limit="5" show_github_all="false"]</code>
                        
                        <p><strong><?php _e('Custom colors:', 'intellsoft-github-releases'); ?></strong></p>
                        <code>[intellsoft_changelog repo="username/repository" background_color="#f0f8ff" text_color="#333" link_color="#0066cc"]</code>
                        
                        <p><strong><?php _e('Show only version:', 'intellsoft-github-releases'); ?></strong></p>
                        <code>[intellsoft_latest_version repo="username/repository"]</code>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Need Help?', 'intellsoft-github-releases'); ?></h3>
                        <p><?php _e('Check the documentation for more examples and troubleshooting.', 'intellsoft-github-releases'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=intellsoft-github-releases-docs'); ?>" class="button button-secondary"><?php _e('View Documentation', 'intellsoft-github-releases'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // فعال‌سازی رنگ‌پیکر
            $('.igr-color-field').wpColorPicker();
            
            // مدیریت نمایش/پنهان توکن
            $('#toggle_token').on('click', function() {
                var $tokenField = $('#github_token');
                var $button = $(this);
                
                if ($tokenField.attr('type') === 'password') {
                    $tokenField.attr('type', 'text');
                    $button.text('<?php _e('Hide', 'intellsoft-github-releases'); ?>');
                } else {
                    $tokenField.attr('type', 'password');
                    $button.text('<?php _e('Show', 'intellsoft-github-releases'); ?>');
                }
            });
            
            // تست اتصال
            $('#test_connection').on('click', function() {
                var $button = $(this);
                var $result = $('#test_result');
                
                $button.prop('disabled', true).text('<?php _e('Testing...', 'intellsoft-github-releases'); ?>');
                $result.html('<p>Testing connection...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'igr_test_connection',
                        nonce: '<?php echo wp_create_nonce("igr_admin_nonce"); ?>',
                        repo: $('#test_repo').val(),
                        token: $('#github_token').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + 
                                response.data.message + '<br>' +
                                '<strong>Version:</strong> ' + response.data.version + '<br>' +
                                '<strong>Date:</strong> ' + response.data.date +
                                (response.data.rate_limit ? '<br><strong>Rate Limit:</strong> ' + response.data.rate_limit : '') +
                                '</p></div>');
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + 
                                response.data.message + '</p></div>');
                        }
                    },
                    error: function() {
                        $result.html('<div class="notice notice-error"><p>An error occurred.</p></div>');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('<?php _e('Test Connection', 'intellsoft-github-releases'); ?>');
                    }
                });
            });
            
            // اسلایدر کش
            var cacheSlider = document.getElementById('cache_slider');
            var cacheInput = document.getElementById('cache_expiry');
            var sliderValue = document.getElementById('slider_value');
            
            if (cacheSlider && cacheInput) {
                cacheSlider.addEventListener('input', function() {
                    sliderValue.textContent = this.value + 'h';
                    cacheInput.value = this.value;
                });
                
                cacheInput.addEventListener('input', function() {
                    var value = parseInt(this.value);
                    if (value > 24) value = 24;
                    if (value < 1) value = 1;
                    cacheSlider.value = value;
                    sliderValue.textContent = value + 'h';
                });
            }
        });
        </script>
        <?php
    }
    
    private function render_cache_page_inline() {
        $cache_stats = IGR_Cache::get_cache_stats();
        
        if (isset($_GET['cache_cleared'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Cache cleared successfully.', 'intellsoft-github-releases') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Cache Management', 'intellsoft-github-releases'); ?></h1>
            
            <div class="igr-admin-container">
                <div class="igr-admin-main">
                    <div class="card">
                        <h2><?php _e('Cache Statistics', 'intellsoft-github-releases'); ?></h2>
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
                        <h2><?php _e('Cache Actions', 'intellsoft-github-releases'); ?></h2>
                        <p><?php _e('Clearing the cache will force the plugin to fetch fresh data from GitHub on the next request.', 'intellsoft-github-releases'); ?></p>
                        <p>
                            <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=igr_clear_cache'), 'igr_clear_cache'); ?>" 
                               class="button button-primary" onclick="return confirm('<?php _e('Are you sure you want to clear all cache?', 'intellsoft-github-releases'); ?>')">
                                <?php _e('Clear All Cache', 'intellsoft-github-releases'); ?>
                            </a>
                        </p>
                    </div>
                </div>
                
                <div class="igr-admin-sidebar">
                    <div class="card">
                        <h3><?php _e('Cache Settings', 'intellsoft-github-releases'); ?></h3>
                        <p>
                            <?php _e('Cache settings can be adjusted in the', 'intellsoft-github-releases'); ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=intellsoft-github-releases')); ?>">
                                <?php _e('main settings page', 'intellsoft-github-releases'); ?>
                            </a>.
                        </p>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Cache Optimization Tips', 'intellsoft-github-releases'); ?></h3>
                        <ul>
                            <li><?php _e('Set cache expiry to 6-12 hours for optimal performance', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Clear cache after major releases', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Monitor cache stats regularly', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Use a caching plugin for better overall performance', 'intellsoft-github-releases'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_docs_page_inline() {
        ?>
        <div class="wrap">
            <h1><?php _e('Documentation', 'intellsoft-github-releases'); ?></h1>
            
            <div class="igr-admin-container">
                <div class="igr-admin-main">
                    <div class="card">
                        <h2><?php _e('Shortcodes', 'intellsoft-github-releases'); ?></h2>
                        
                        <h3><?php _e('Basic Changelog', 'intellsoft-github-releases'); ?></h3>
                        <code>[intellsoft_changelog repo="username/repository"]</code>
                        <p><?php _e('Displays the latest release with full changelog.', 'intellsoft-github-releases'); ?></p>
                        
                        <h3><?php _e('Multiple Releases', 'intellsoft-github-releases'); ?></h3>
                        <code>[intellsoft_changelog repo="username/repository" limit="5"]</code>
                        <p><?php _e('Displays multiple releases (up to the specified limit).', 'intellsoft-github-releases'); ?></p>
                        
                        <h3><?php _e('GitHub Button Control', 'intellsoft-github-releases'); ?></h3>
                        <code>[intellsoft_changelog repo="username/repository" limit="5" show_github_all="false"]</code>
                        <p><?php _e('Shows GitHub button only for the latest release when displaying multiple releases.', 'intellsoft-github-releases'); ?></p>
                        
                        <h3><?php _e('Custom Colors', 'intellsoft-github-releases'); ?></h3>
                        <code>[intellsoft_changelog repo="username/repository" background_color="#f0f8ff" text_color="#333" link_color="#0066cc" border_color="#ccc"]</code>
                        <p><?php _e('Customize the appearance with your own colors.', 'intellsoft-github-releases'); ?></p>
                        
                        <h3><?php _e('Version Only', 'intellsoft-github-releases'); ?></h3>
                        <code>[intellsoft_latest_version repo="username/repository"]</code>
                        <p><?php _e('Displays only the latest version number.', 'intellsoft-github-releases'); ?></p>
                        
                        <h3><?php _e('Release History', 'intellsoft-github-releases'); ?></h3>
                        <code>[intellsoft_release_history repo="username/repository" limit="10"]</code>
                        <p><?php _e('Displays a timeline of releases.', 'intellsoft-github-releases'); ?></p>
                    </div>
                    
                    <div class="card">
                        <h2><?php _e('GitHub Token Setup', 'intellsoft-github-releases'); ?></h2>
                        <ol>
                            <li><?php _e('Go to GitHub → Settings → Developer Settings → Personal Access Tokens', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Click "Generate new token"', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Select "repo" scope for private repositories', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Copy the generated token and paste it in the plugin settings', 'intellsoft-github-releases'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="card">
                        <h2><?php _e('Troubleshooting', 'intellsoft-github-releases'); ?></h2>
                        
                        <h3><?php _e('Connection Errors', 'intellsoft-github-releases'); ?></h3>
                        <ul>
                            <li><?php _e('Check your GitHub token permissions', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Verify repository name format: username/repository', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Ensure repository exists and is accessible', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Check rate limits in the Test Connection section', 'intellsoft-github-releases'); ?></li>
                        </ul>
                        
                        <h3><?php _e('Cache Issues', 'intellsoft-github-releases'); ?></h3>
                        <ul>
                            <li><?php _e('Clear cache from Cache Management page', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Increase cache expiry time for better performance', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Disable caching temporarily for debugging', 'intellsoft-github-releases'); ?></li>
                        </ul>
                        
                        <h3><?php _e('Display Issues', 'intellsoft-github-releases'); ?></h3>
                        <ul>
                            <li><?php _e('Check that your theme supports the CSS classes used by the plugin', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Try clearing your browser cache', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Test with default colors first, then customize', 'intellsoft-github-releases'); ?></li>
                        </ul>
                    </div>
                </div>
                
                <div class="igr-admin-sidebar">
                    <div class="card">
                        <h3><?php _e('Need More Help?', 'intellsoft-github-releases'); ?></h3>
                        <p><?php _e('If you continue to experience issues, please:', 'intellsoft-github-releases'); ?></p>
                        <ol>
                            <li><?php _e('Check the error logs in your WordPress dashboard', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Temporarily disable other plugins to check for conflicts', 'intellsoft-github-releases'); ?></li>
                            <li><?php _e('Test with a default WordPress theme', 'intellsoft-github-releases'); ?></li>
                        </ol>
                    </div>
                    
                    <div class="card">
                        <h3><?php _e('Plugin Information', 'intellsoft-github-releases'); ?></h3>
                        <p><strong><?php _e('Version:', 'intellsoft-github-releases'); ?></strong> <?php echo IGR_VERSION; ?></p>
                        <p><strong><?php _e('Cache System:', 'intellsoft-github-releases'); ?></strong> <?php _e('Enabled by default', 'intellsoft-github-releases'); ?></p>
                        <p><strong><?php _e('GitHub API:', 'intellsoft-github-releases'); ?></strong> <?php _e('Supports public and private repositories', 'intellsoft-github-releases'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public static function deactivate() {
        // حذف رویداد زمان‌بندی شده
        wp_clear_scheduled_hook('igr_weekly_cleanup');
    }
}