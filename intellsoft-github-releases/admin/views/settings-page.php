<?php
/**
 * صفحه تنظیمات افزونه
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی اینکه کاربر مجوز لازم را دارد
if (!current_user_can('manage_options')) {
    return;
}

// اگر فرم ذخیره شده، پیام موفقیت را نشان بده
if (isset($_GET['settings-updated'])) {
    add_settings_error('igr_messages', 'igr_message', __('Settings Saved', 'intellsoft-github-releases'), 'updated');
}

// نمایش خطاها
settings_errors('igr_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2 class="title"><?php _e('Quick Setup', 'intellsoft-github-releases'); ?></h2>
        <p><?php _e('Follow these steps to set up the plugin:', 'intellsoft-github-releases'); ?></p>
        <ol>
            <li><?php _e('Create a GitHub Personal Access Token', 'intellsoft-github-releases'); ?></li>
            <li><?php _e('Enter the token and repository details below', 'intellsoft-github-releases'); ?></li>
            <li><?php _e('Use the shortcode on your product pages', 'intellsoft-github-releases'); ?></li>
        </ol>
        <p>
            <a href="https://github.com/settings/tokens/new?scopes=repo&description=WordPress+GitHub+Releases" 
               target="_blank" class="button button-primary">
                <?php _e('Generate GitHub Token', 'intellsoft-github-releases'); ?>
            </a>
        </p>
    </div>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('igr_settings_group');
        do_settings_sections('intellsoft-github-releases');
        submit_button();
        ?>
    </form>
    
    <div class="card">
        <h2 class="title"><?php _e('Shortcode Examples', 'intellsoft-github-releases'); ?></h2>
        <p><strong><?php _e('Basic Usage:', 'intellsoft-github-releases'); ?></strong></p>
        <code>[intellsoft_changelog repo="username/repository"]</code>
        
        <p><strong><?php _e('Show only version:', 'intellsoft-github-releases'); ?></strong></p>
        <code>[intellsoft_latest_version repo="username/repository"]</code>
        
        <p><strong><?php _e('Multiple releases:', 'intellsoft-github-releases'); ?></strong></p>
        <code>[intellsoft_changelog repo="username/repository" limit="5"]</code>
        
        <p><strong><?php _e('With custom class:', 'intellsoft-github-releases'); ?></strong></p>
        <code>[intellsoft_changelog repo="username/repository" class="my-custom-class"]</code>
    </div>
    
    <div class="card">
        <h2 class="title"><?php _e('Test Connection', 'intellsoft-github-releases'); ?></h2>
        <p><?php _e('Test the connection to GitHub API with your token and repository.', 'intellsoft-github-releases'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="test_repo"><?php _e('Repository', 'intellsoft-github-releases'); ?></label>
                </th>
                <td>
                    <input type="text" id="test_repo" class="regular-text" 
                           placeholder="username/repository" value="<?php echo esc_attr(get_option('igr_settings')['default_repo'] ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="test_token"><?php _e('GitHub Token (optional)', 'intellsoft-github-releases'); ?></label>
                </th>
                <td>
                    <input type="password" id="test_token" class="regular-text" 
                           value="<?php echo esc_attr(get_option('igr_settings')['github_token'] ?? ''); ?>">
                    <p class="description"><?php _e('Leave empty for public repositories', 'intellsoft-github-releases'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="button" id="test_connection" class="button button-primary">
                <?php _e('Test Connection', 'intellsoft-github-releases'); ?>
            </button>
        </p>
        
        <div id="test_result" style="display: none;"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#test_connection').on('click', function() {
        var $button = $(this);
        var $result = $('#test_result');
        
        $button.prop('disabled', true).text('<?php _e('Testing...', 'intellsoft-github-releases'); ?>');
        $result.hide().empty();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'igr_test_connection',
                nonce: '<?php echo wp_create_nonce('igr_nonce'); ?>',
                repo: $('#test_repo').val(),
                token: $('#test_token').val()
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p>' +
                        '<p><strong><?php _e('Latest Version:', 'intellsoft-github-releases'); ?></strong> ' + response.data.version + '</p>' +
                        '<p><strong><?php _e('Release Date:', 'intellsoft-github-releases'); ?></strong> ' + response.data.date + '</p></div>').show();
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>').show();
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p><?php _e('An error occurred. Please try again.', 'intellsoft-github-releases'); ?></p></div>').show();
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php _e('Test Connection', 'intellsoft-github-releases'); ?>');
            }
        });
    });
});
</script>