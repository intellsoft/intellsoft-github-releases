<?php
/**
 * شورت‌کدهای افزونه
 */

if (!defined('ABSPATH')) {
    exit;
}

class IGR_Shortcode {
    
    public function init() {
        add_shortcode('intellsoft_changelog', [$this, 'changelog_shortcode']);
        add_shortcode('intellsoft_latest_version', [$this, 'version_shortcode']);
        add_shortcode('intellsoft_release_history', [$this, 'history_shortcode']);
    }
    
    /**
     * شورت‌کد نمایش تغییرات
     */
    public function changelog_shortcode($atts) {
        $atts = shortcode_atts([
            'repo' => '',
            'show_title' => 'true',
            'show_date' => 'true',
            'show_body' => 'true',
            'limit' => '1',
            'class' => '',
            'show_download' => 'true',
            'show_github' => 'true',
            'show_github_all' => 'false', // پارامتر جدید: نمایش دکمه GitHub برای همه یا فقط آخرین
            'background_color' => '',
            'text_color' => '',
            'link_color' => '',
            'border_color' => '',
        ], $atts, 'intellsoft_changelog');
        
        // دیباگ: چک کردن پارامترها
        error_log('IGR Shortcode Original Params: ' . print_r($atts, true));
        
        if (empty($atts['repo'])) {
            $settings = get_option('igr_settings', []);
            $atts['repo'] = $settings['default_repo'] ?? '';
        }
        
        if (empty($atts['repo'])) {
            return '<div class="igr-error">' . 
                   __('Repository not specified', 'intellsoft-github-releases') . 
                   '</div>';
        }
        
        // **اصلاح اصلی: تمیز کردن مقدار repo**
        $repo = $this->clean_repository_name($atts['repo']);
        
        error_log('IGR Cleaned Repo: ' . $repo);
        
        // اعتبارسنجی فرمت ریپو
        if (!$this->validate_repository_format($repo)) {
            // برای دیباگ بیشتر
            error_log('IGR: Repository validation failed. Cleaned repo: ' . $repo);
            error_log('IGR: Original repo: ' . $atts['repo']);
            
            return '<div class="igr-error">' . 
                   sprintf(
                       __('Invalid repository format: "%s". Use: username/repository', 'intellsoft-github-releases'),
                       esc_html($repo)
                   ) . 
                   '</div>';
        }
        
        // جایگزینی مقدار اصلاح شده
        $atts['repo'] = $repo;
        
        $api_handler = new IGR_API_Handler();
        
        // اگر limit بیشتر از 1 است، همه ریلیزها را بگیر
        $limit = intval($atts['limit']);
        if ($limit > 1) {
            $releases = $api_handler->get_all_releases($atts['repo'], $limit);
            
            if (is_wp_error($releases)) {
                error_log('IGR API Error (all releases): ' . $releases->get_error_message());
                return $this->render_error($releases);
            }
            
            // اگر آرایه خالی باشد
            if (empty($releases)) {
                return '<div class="igr-no-releases">' . 
                       __('No releases found', 'intellsoft-github-releases') . 
                       '</div>';
            }
            
            // برای نمایش چندین ریلیز
            $output = '<div class="igr-changelog-multiple ' . esc_attr($atts['class']) . '">';
            
            $count = 0;
            foreach ($releases as $release) {
                $count++;
                $is_latest = ($count === 1); // اولین ریلیز (آخرین) را به عنوان latest علامت بزن
                // اگر show_github_all false باشد، فقط برای اولین ریلیز (آخرین) دکمه GitHub را نشان بده
                $show_github = ($atts['show_github_all'] === 'true') ? true : $is_latest;
                $release_atts = $atts;
                $release_atts['show_github'] = $show_github ? 'true' : 'false';
                $output .= $this->render_single_release($release, $release_atts, $is_latest);
            }
            $output .= '</div>';
            
            // اضافه کردن استایل‌های داینامیک برای این شورت‌کد
            $this->add_inline_styles_for_shortcode($atts);
            
            return $output;
        } else {
            // فقط آخرین ریلیز
            $release = $api_handler->get_latest_release($atts['repo']);
            
            if (is_wp_error($release)) {
                error_log('IGR API Error (latest release): ' . $release->get_error_message());
                return $this->render_error($release);
            }
            
            // اضافه کردن استایل‌های داینامیک برای این شورت‌کد
            $this->add_inline_styles_for_shortcode($atts);
            
            return $this->render_single_release($release, $atts, true);
        }
    }
    
    /**
     * تمیز کردن نام ریپازیتوری از تگ‌های HTML و کاراکترهای اضافی
     */
    private function clean_repository_name($repo) {
        // اگر null یا خالی است
        if (empty($repo)) {
            return '';
        }
        
        // تبدیل به string در صورت نیاز
        $repo = (string) $repo;
        
        // دیباگ: مقدار اولیه
        error_log('IGR Clean Repo - Initial: ' . $repo);
        
        // حذف تمام تگ‌های HTML
        $repo = wp_strip_all_tags($repo);
        error_log('IGR Clean Repo - After strip_tags: ' . $repo);
        
        // تبدیل HTML entities به کاراکترهای عادی
        $repo = html_entity_decode($repo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        error_log('IGR Clean Repo - After html_entity_decode: ' . $repo);
        
        // حذف کاراکترهای کنترل و فاصله‌های اضافی
        $repo = preg_replace('/[\x00-\x1F\x7F]/u', '', $repo);
        $repo = trim($repo);
        error_log('IGR Clean Repo - After trim: ' . $repo);
        
        // حذف کوتیشن‌ها و اسلش‌های اضافی
        $repo = str_replace(['"', "'", '`'], '', $repo);
        $repo = trim($repo, '/');
        error_log('IGR Clean Repo - After quote removal: ' . $repo);
        
        // نرمال‌سازی اسلش‌ها
        $repo = preg_replace('#/+#', '/', $repo);
        
        return $repo;
    }
    
    /**
     * اعتبارسنجی فرمت ریپازیتوری
     */
    private function validate_repository_format($repo) {
        if (empty($repo)) {
            return false;
        }
        
        // الگوی اصلاح شده برای نام ریپازیتوری GitHub
        // اجازه می‌دهد: حروف، اعداد، نقطه، خط تیره، زیرخط
        $pattern = '/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/';
        
        $is_valid = preg_match($pattern, $repo) === 1;
        
        // دیباگ اعتبارسنجی
        error_log('IGR Validation - Repo: ' . $repo);
        error_log('IGR Validation - Pattern: ' . $pattern);
        error_log('IGR Validation - Result: ' . ($is_valid ? 'Valid' : 'Invalid'));
        
        return $is_valid;
    }
    
    /**
     * رندر یک ریلیز
     */
    private function render_single_release($release, $atts, $is_latest = true) {
        if (!is_array($release) || empty($release)) {
            return '';
        }
        
        $show_title = filter_var($atts['show_title'], FILTER_VALIDATE_BOOLEAN);
        $show_date = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        $show_body = filter_var($atts['show_body'], FILTER_VALIDATE_BOOLEAN);
        $show_download = filter_var($atts['show_download'], FILTER_VALIDATE_BOOLEAN);
        $show_github = filter_var($atts['show_github'], FILTER_VALIDATE_BOOLEAN);
        
        $additional_class = $is_latest ? 'igr-latest-release' : 'igr-older-release';
        
        ob_start();
        ?>
        <div class="igr-release <?php echo esc_attr($additional_class); ?>">
            <?php if ($show_title && isset($release['name'])): ?>
            <h3 class="igr-release-title">
                <?php echo esc_html($release['name']); ?>
                <?php if ($show_date && isset($release['published_at'])): ?>
                <span class="igr-release-date">
                    <?php 
                    $settings = get_option('igr_settings', []);
                    $date_format = $settings['date_format'] ?? 'human';
                    
                    if ($date_format === 'human' && isset($release['published_at_human'])) {
                        echo esc_html($release['published_at_human']);
                    } elseif ($date_format === 'full' && isset($release['published_at_formatted'])) {
                        echo esc_html($release['published_at_formatted']);
                    } elseif ($date_format === 'both') {
                        if (isset($release['published_at_formatted']) && isset($release['published_at_human'])) {
                            echo esc_html($release['published_at_formatted'] . ' (' . $release['published_at_human'] . ')');
                        }
                    }
                    ?>
                </span>
                <?php endif; ?>
                
                <?php // نشان برای ریلیزهای جدید (کمتر از 7 روز) ?>
                <?php if ($is_latest && isset($release['published_at'])): ?>
                    <?php 
                    $published_time = strtotime($release['published_at']);
                    $week_ago = time() - (7 * 24 * 60 * 60);
                    if ($published_time > $week_ago): ?>
                    <span class="igr-new-badge">🆕 <?php _e('New', 'intellsoft-github-releases'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </h3>
            <?php endif; ?>
            
            <?php if ($show_body && !empty($release['body'])): ?>
            <div class="igr-release-body">
                <?php echo $this->process_markdown($release['body']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($show_github || $show_download): ?>
            <div class="igr-release-meta">
                <?php if ($show_github && isset($release['html_url'])): ?>
                <a href="<?php echo esc_url($release['html_url']); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="igr-github-link">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('View on GitHub', 'intellsoft-github-releases'); ?>
                </a>
                <?php endif; ?>
                
                <?php if ($show_download && isset($release['zipball_url'])): ?>
                <a href="<?php echo esc_url($release['zipball_url']); ?>" 
                   class="igr-download-link"
                   download>
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download Source', 'intellsoft-github-releases'); ?>
                    <?php if (isset($release['total_size'])): ?>
                    <span class="igr-download-size">(<?php echo size_format($release['total_size'], 1); ?>)</span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * پردازش مارک‌داون و تبدیل لینک‌ها
     */
    private function process_markdown($text) {
        if (empty($text)) {
            return '';
        }
        
        // تبدیل لینک‌های مارک‌داون به HTML
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function($matches) {
                $url = esc_url($matches[2]);
                $text = esc_html($matches[1]);
                return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
            },
            $text
        );
        
        // تبدیل سرتیترها
        $text = preg_replace('/^### (.*)$/m', '<h4>$1</h4>', $text);
        $text = preg_replace('/^## (.*)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^# (.*)$/m', '<h2>$1</h2>', $text);
        
        // تبدیل bold و italic
        $text = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $text);
        
        // تبدیل کد inline
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        
        // تبدیل لیست‌ها
        $lines = explode("\n", $text);
        $in_list = false;
        $list_type = ''; // 'ul' یا 'ol'
        $output = [];
        
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            
            if (preg_match('/^[\*\-\+] (.*)/', $trimmed_line, $matches)) {
                if (!$in_list || $list_type !== 'ul') {
                    if ($in_list) {
                        $output[] = $list_type === 'ul' ? '</ul>' : '</ol>';
                    }
                    $output[] = '<ul>';
                    $in_list = true;
                    $list_type = 'ul';
                }
                $output[] = '<li>' . esc_html($matches[1]) . '</li>';
            } elseif (preg_match('/^\d+\. (.*)/', $trimmed_line, $matches)) {
                if (!$in_list || $list_type !== 'ol') {
                    if ($in_list) {
                        $output[] = $list_type === 'ul' ? '</ul>' : '</ol>';
                    }
                    $output[] = '<ol>';
                    $in_list = true;
                    $list_type = 'ol';
                }
                $output[] = '<li>' . esc_html($matches[1]) . '</li>';
            } else {
                if ($in_list) {
                    $output[] = $list_type === 'ul' ? '</ul>' : '</ol>';
                    $in_list = false;
                    $list_type = '';
                }
                $output[] = $line;
            }
        }
        
        if ($in_list) {
            $output[] = $list_type === 'ul' ? '</ul>' : '</ol>';
        }
        
        $text = implode("\n", $output);
        
        // تبدیل خطوط جدید به <br> (فقط برای خطوطی که داخل بلوک‌های کد نیستند)
        $text = preg_replace('/\n(?!\s*[<>])/', "<br>\n", $text);
        
        // اطمینان از امنیت خروجی
        return wp_kses_post($text);
    }
    
    /**
     * شورت‌کد نمایش نسخه
     */
    public function version_shortcode($atts) {
        $atts = shortcode_atts([
            'repo' => '',
            'show_prefix' => 'true',
            'link' => 'true',
            'show_date' => 'false',
        ], $atts, 'intellsoft_latest_version');
        
        if (empty($atts['repo'])) {
            $settings = get_option('igr_settings', []);
            $atts['repo'] = $settings['default_repo'] ?? '';
        }
        
        // تمیز کردن نام ریپازیتوری
        $repo = $this->clean_repository_name($atts['repo']);
        
        if (!$this->validate_repository_format($repo)) {
            return '<span class="igr-error-small">' . 
                   __('Invalid repository', 'intellsoft-github-releases') . 
                   '</span>';
        }
        
        $api_handler = new IGR_API_Handler();
        $release = $api_handler->get_latest_release($repo);
        
        if (is_wp_error($release)) {
            return '<span class="igr-error-small">' . 
                   __('Unable to load version', 'intellsoft-github-releases') . 
                   '</span>';
        }
        
        $version = $release['tag_name'] ?? '';
        $version = ltrim($version, 'vV');
        
        if ($atts['link'] === 'true' && isset($release['html_url'])) {
            $version_html = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                esc_url($release['html_url']),
                esc_html($version)
            );
        } else {
            $version_html = esc_html($version);
        }
        
        $output = '';
        if ($atts['show_prefix'] === 'true') {
            $output .= __('Version', 'intellsoft-github-releases') . ' ';
        }
        
        $output .= $version_html;
        
        if ($atts['show_date'] === 'true' && isset($release['published_at_formatted'])) {
            $output .= ' <span class="igr-version-date">(' . esc_html($release['published_at_formatted']) . ')</span>';
        }
        
        return '<span class="igr-version">' . $output . '</span>';
    }
    
    /**
     * شورت‌کد نمایش تاریخچه
     */
    public function history_shortcode($atts) {
        $atts = shortcode_atts([
            'repo' => '',
            'limit' => '10',
            'show_dates' => 'true',
            'collapse_old' => 'true',
            'show_summary' => 'true',
        ], $atts, 'intellsoft_release_history');
        
        if (empty($atts['repo'])) {
            $settings = get_option('igr_settings', []);
            $atts['repo'] = $settings['default_repo'] ?? '';
        }
        
        // تمیز کردن نام ریپازیتوری
        $repo = $this->clean_repository_name($atts['repo']);
        
        if (!$this->validate_repository_format($repo)) {
            return '<div class="igr-error">' . 
                   __('Invalid repository format', 'intellsoft-github-releases') . 
                   '</div>';
        }
        
        $api_handler = new IGR_API_Handler();
        $releases = $api_handler->get_all_releases($repo, intval($atts['limit']));
        
        if (is_wp_error($releases) || empty($releases)) {
            return '<div class="igr-no-releases">' . 
                   __('No release history found', 'intellsoft-github-releases') . 
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="igr-release-history">
            <?php if ($atts['show_summary'] === 'true'): ?>
            <div class="igr-history-summary">
                <p>
                    <?php 
                    printf(
                        __('Showing %d of %d releases', 'intellsoft-github-releases'),
                        count($releases),
                        count($releases)
                    );
                    ?>
                </p>
            </div>
            <?php endif; ?>
            
            <div class="igr-history-timeline">
                <?php foreach ($releases as $index => $release): ?>
                <div class="igr-history-item <?php echo $index === 0 ? 'latest' : ''; ?>">
                    <div class="igr-history-version">
                        <strong><?php echo esc_html($release['tag_name']); ?></strong>
                        <?php if ($atts['show_dates'] === 'true' && isset($release['published_at_formatted'])): ?>
                        <span class="igr-history-date"><?php echo esc_html($release['published_at_formatted']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($release['name'])): ?>
                    <div class="igr-history-title"><?php echo esc_html($release['name']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($release['body'])): ?>
                    <div class="igr-history-excerpt">
                        <?php 
                        $excerpt = wp_trim_words($release['body'], 30);
                        echo esc_html($excerpt);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($release['html_url'])): ?>
                    <a href="<?php echo esc_url($release['html_url']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="igr-history-link">
                        <?php _e('View Details', 'intellsoft-github-releases'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * نمایش خطا
     */
    private function render_error($error) {
        $error_message = $error->get_error_message();
        
        // ذخیره خطا در لاگ
        error_log('IGR Shortcode Error: ' . $error_message);
        
        // برای مدیران نمایش خطای کامل
        if (current_user_can('manage_options')) {
            $admin_message = sprintf(
                '<div class="igr-error admin-notice"><strong>%s:</strong> %s<br><small>%s</small></div>',
                __('Admin Debug Info', 'intellsoft-github-releases'),
                esc_html($error_message),
                __('This message is only visible to administrators.', 'intellsoft-github-releases')
            );
            
            return $admin_message;
        }
        
        // برای کاربران عادی پیام عمومی
        $public_message = '<div class="igr-error">' . 
               __('Unable to load release information at this time. Please try again later.', 'intellsoft-github-releases') . 
               '</div>';
        
        return $public_message;
    }
    
    /**
     * اضافه کردن استایل‌های اینلاین برای یک شورت‌کد خاص
     */
    private function add_inline_styles_for_shortcode($atts) {
        static $style_added = false;
        
        // اگر استایل‌های داینامیک از طریق پارامترهای شورت‌کد ارائه شده‌اند
        if (!empty($atts['background_color']) || !empty($atts['text_color']) || 
            !empty($atts['link_color']) || !empty($atts['border_color'])) {
            
            $style_id = 'igr-shortcode-styles-' . md5(serialize($atts));
            
            if (!$style_added) {
                echo '<style id="' . esc_attr($style_id) . '">';
                echo '.igr-changelog, .igr-changelog-multiple {';
                if (!empty($atts['background_color'])) {
                    echo 'background-color: ' . esc_attr($atts['background_color']) . ';';
                }
                if (!empty($atts['text_color'])) {
                    echo 'color: ' . esc_attr($atts['text_color']) . ';';
                }
                if (!empty($atts['border_color'])) {
                    echo 'border-color: ' . esc_attr($atts['border_color']) . ';';
                }
                echo '}';
                
                if (!empty($atts['link_color'])) {
                    echo '.igr-release-body a, .igr-history-link, .igr-github-link, .igr-download-link {';
                    echo 'color: ' . esc_attr($atts['link_color']) . ';';
                    echo '}';
                }
                
                echo '</style>';
                $style_added = true;
            }
        }
    }
}