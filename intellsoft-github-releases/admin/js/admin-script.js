(function($) {
    'use strict';
    
    $(document).ready(function() {

// پاک کردن توکن
$('#clear_token').on('click', function(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to clear the GitHub token?')) {
        $('#github_token').val('');
        alert('Token field cleared. Remember to save changes.');
    }
});

// بهبود مدیریت نمایش/پنهان کردن توکن
$('#toggle_token').on('click', function() {
    var $tokenField = $('#github_token');
    var $button = $(this);
    
    if ($tokenField.attr('type') === 'password') {
        $tokenField.attr('type', 'text');
        $button.text(igr_admin.strings.hide);
        $button.addClass('active');
    } else {
        $tokenField.attr('type', 'password');
        $button.text(igr_admin.strings.show);
        $button.removeClass('active');
    }
});

        // مدیریت نمایش/عدم نمایش فیلد توکن
        $('#toggle_token').on('click', function() {
            var $tokenField = $('#github_token');
            var $button = $(this);
            
            if ($tokenField.attr('type') === 'password') {
                $tokenField.attr('type', 'text');
                $button.text(igr_admin.strings.hide);
            } else {
                $tokenField.attr('type', 'password');
                $button.text(igr_admin.strings.show);
            }
        });
        
        // مدیریت نمایش/عدم نمایش فیلد ریپو
        $('#default_repo').on('input', function() {
            var repo = $(this).val();
            if (repo.length > 0) {
                $('#test_repo').val(repo);
            }
        });
        
        // اعتبارسنجی فرم
        $('form').on('submit', function() {
            var repo = $('#default_repo').val();
            if (repo && !repo.match(/^[a-zA-Z0-9_.-]+\/[a-zA-Z0-9_.-]+$/)) {
                alert('Please enter a valid repository in the format: username/repository');
                $('#default_repo').focus();
                return false;
            }
            
            // اعتبارسنجی cache expiry
            var cacheExpiry = $('#cache_expiry').val();
            if (cacheExpiry > 720) {
                alert('Cache expiry cannot exceed 720 hours (30 days).');
                $('#cache_expiry').focus();
                return false;
            }
            
            return true;
        });
        
        // بهینه‌سازی کش
        $('a[href*="action=igr_clear_cache"]').on('click', function(e) {
            if (!confirm('Are you sure you want to clear all cache? This will force fresh data fetching from GitHub.')) {
                e.preventDefault();
            }
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
        
        // تست اتصال
        $('#test_connection').on('click', function() {
            var $button = $(this);
            var $result = $('#test_result');
            
            $button.prop('disabled', true).text(igr_admin.strings.testing);
            $result.html('<p>Testing connection...</p>');
            
            $.ajax({
                url: igr_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'igr_test_connection',
                    nonce: igr_admin.nonce,
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
                    $button.prop('disabled', false).text(igr_admin.strings.test_connection);
                }
            });
        });
        
        // فعال‌سازی رنگ‌پیکر
        $('.igr-color-field').wpColorPicker();
    });
    
})(jQuery);