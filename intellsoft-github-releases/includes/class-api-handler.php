<?php
/**
 * مدیریت ارتباط با GitHub API
 */

if (!defined('ABSPATH')) {
    exit;
}

class IGR_API_Handler {
    
    private $token;
    private $timeout = 15;
    private $retry_attempts = 3;
    
    public function __construct($token = '') {
        $this->token = $token ?: $this->get_default_token();
    }
    
    /**
     * دریافت آخرین انتشار
     */
    public function get_latest_release($repo) {
        // بررسی کش
        $cache_key = 'latest_release_' . $repo;
        $cached = IGR_Cache::get($cache_key, $repo);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = "https://api.github.com/repos/{$repo}/releases/latest";
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = $this->process_response($response);
        
        // ذخیره در کش
        if (!is_wp_error($data)) {
            IGR_Cache::set($cache_key, $data, $repo);
        }
        
        return $data;
    }
    
    /**
     * دریافت تمام انتشارها
     */
    public function get_all_releases($repo, $per_page = 10) {
        $cache_key = 'all_releases_' . $per_page . '_' . $repo;
        $cached = IGR_Cache::get($cache_key, $repo);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = "https://api.github.com/repos/{$repo}/releases?per_page={$per_page}";
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = $this->process_response($response);
        
        if (!is_wp_error($data)) {
            IGR_Cache::set($cache_key, $data, $repo);
        }
        
        return $data;
    }
    
    /**
     * ارسال درخواست به GitHub API
     */
    private function make_request($url) {
        $args = [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/IntellSoft-GitHub-Releases'
            ],
            'timeout' => $this->timeout,
            'sslverify' => true,
            'redirection' => 5,
        ];
        
        // افزودن توکن اگر وجود دارد
        if ($this->token) {
            $args['headers']['Authorization'] = 'token ' . $this->token;
        }
        
        // سیستم retry
        for ($attempt = 1; $attempt <= $this->retry_attempts; $attempt++) {
            $response = wp_remote_get($url, $args);
            
            if (!is_wp_error($response)) {
                $status_code = wp_remote_retrieve_response_code($response);
                
                if ($status_code === 200) {
                    return $response;
                } elseif ($status_code === 403) {
                    // ممکن است محدودیت rate limit باشد
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (isset($body['message']) && strpos($body['message'], 'rate limit') !== false) {
                        $reset_time = wp_remote_retrieve_header($response, 'x-ratelimit-reset');
                        if ($reset_time) {
                            $wait = max(1, $reset_time - time());
                            sleep($wait);
                            continue;
                        }
                    }
                }
                
                // خطاهای دیگر
                return new WP_Error(
                    'github_api_error',
                    sprintf(
                        __('GitHub API Error: %s (Status: %d)', 'intellsoft-github-releases'),
                        wp_remote_retrieve_response_message($response),
                        $status_code
                    )
                );
            }
            
            // اگر خطای شبکه باشد، کمی صبر می‌کنیم
            if ($attempt < $this->retry_attempts) {
                sleep(1);
            }
        }
        
        return $response;
    }
    
    /**
     * پردازش پاسخ API
     */
private function process_response($response) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error(
            'json_parse_error',
            __('Failed to parse JSON response from GitHub', 'intellsoft-github-releases')
        );
    }
    
    if (isset($data['message'])) {
        // اگر خطای خاصی مانند "Not Found" یا "rate limit"
        $error_message = $data['message'];
        
        // اضافه کردن اطلاعات بیشتر برای دیباگ
        if (strpos($error_message, 'rate limit') !== false) {
            $error_message .= '. ' . __('GitHub API rate limit exceeded. Please try again later.', 'intellsoft-github-releases');
        } elseif (strpos($error_message, 'Not Found') !== false) {
            $error_message .= '. ' . __('Repository not found. Please check the repository name.', 'intellsoft-github-releases');
        }
        
        return new WP_Error(
            'github_error',
            $error_message
        );
    }
    
    // پردازش آرایه‌ای از ریلیزها (برای get_all_releases)
    if (isset($data[0])) {
        $processed = [];
        foreach ($data as $release) {
            $processed[] = $this->process_single_release($release);
        }
        return $processed;
    }
    
    // پردازش یک ریلیز
    return $this->process_single_release($data);
}

  
/**
 * پردازش یک ریلیز واحد
 */
private function process_single_release($release_data) {
    if (empty($release_data)) {
        return $release_data;
    }
    
    // تمیز کردن داده‌ها
    $clean_data = [
        'id' => $release_data['id'] ?? 0,
        'tag_name' => $release_data['tag_name'] ?? '',
        'name' => $release_data['name'] ?? $release_data['tag_name'] ?? '',
        'body' => $release_data['body'] ?? '',
        'html_url' => $release_data['html_url'] ?? '',
        'zipball_url' => $release_data['zipball_url'] ?? '',
        'tarball_url' => $release_data['tarball_url'] ?? '',
        'published_at' => $release_data['published_at'] ?? '',
        'author' => $release_data['author']['login'] ?? '',
        'prerelease' => $release_data['prerelease'] ?? false,
        'draft' => $release_data['draft'] ?? false,
        'assets' => $release_data['assets'] ?? [],
    ];
    
    // فرمت‌دهی تاریخ
    if (!empty($clean_data['published_at'])) {
        $clean_data['published_at_formatted'] = date_i18n(
            get_option('date_format') . ' ' . get_option('time_format'),
            strtotime($clean_data['published_at'])
        );
        
        $clean_data['published_at_human'] = human_time_diff(
            strtotime($clean_data['published_at']),
            current_time('timestamp')
        ) . ' ' . __('ago', 'intellsoft-github-releases');
    }
    
    // اگر assets وجود دارد، حجم کل را محاسبه کن
    if (!empty($clean_data['assets'])) {
        $total_size = 0;
        foreach ($clean_data['assets'] as $asset) {
            $total_size += $asset['size'] ?? 0;
        }
        $clean_data['total_size'] = $total_size;
    }
    
    return $clean_data;
}
  
    /**
     * تبدیل Markdown به HTML
     */
    private function markdown_to_html($markdown) {
        if (!function_exists('wpmarkdown_markdown_to_html')) {
            // اگر افزونه Markdown نصب نباشد، از تابع ساده استفاده می‌کنیم
            $markdown = esc_html($markdown);
            $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
            $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);
            $markdown = preg_replace('/### (.*?)(\n|$)/', '<h3>$1</h3>', $markdown);
            $markdown = preg_replace('/## (.*?)(\n|$)/', '<h2>$1</h2>', $markdown);
            $markdown = preg_replace('/# (.*?)(\n|$)/', '<h1>$1</h1>', $markdown);
            $markdown = nl2br($markdown);
            return $markdown;
        }
        
        return wpmarkdown_markdown_to_html($markdown);
    }
    
    /**
     * دریافت توکن پیش‌فرض از تنظیمات
     */
    private function get_default_token() {
        $settings = get_option('igr_settings', []);
        return $settings['github_token'] ?? '';
    }
    
    /**
     * بررسی اعتبار توکن
     */
    public function validate_token($token) {
        if (empty($token)) {
            return true; // توکن خالی برای ریپوی عمومی قابل قبول است
        }
        
        $url = 'https://api.github.com/user';
        $this->token = $token;
        $response = $this->make_request($url);
        
        return !is_wp_error($response);
    }
    
    /**
     * دریافت محدودیت rate limit
     */
    public function get_rate_limit() {
        $url = 'https://api.github.com/rate_limit';
        $response = $this->make_request($url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
}