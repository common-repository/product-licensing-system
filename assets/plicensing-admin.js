jQuery(document).ready(function($) {

    function hasError(selector) {
        var hasError = false;
        $(selector + ' .required').each(function() {
            $(this).removeClass('error');

            if ($.trim($(this).val()) == '') {
                hasError = true;
                $(this).addClass('error');
            } 
            else if ($(this).hasClass('email')) {
                var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
                if (!emailReg.test($.trim($(this).val()))) {
                    hasError = true;
                    $(this).addClass('error');
                }
            }
        });
        return hasError;
    }

    $("#save_license").click(function() {
        if (!hasError('.addedit-license-table')) {
            $('#posts-filter-form').submit();
            return true;
        }
        return false;
    });
    
    $("#send_email").click(function() {
        if (!hasError('.mailsend-license-table')) {
            $('#posts-filter-form').attr('action', document.URL);
            $('#posts-filter-form').submit();
            return true;
        }
        return false;
    });
    
    $("#regenerate_license").click(function() {
        $('.plic-loading-icon').show();
        
        $.post(plicense_data.admin_ajax_url, {"action": "plicense_getlicense"}, function(response) {
            $("#license_key").val(response);
            $('.plic-loading-icon').hide();
        });
    });
});