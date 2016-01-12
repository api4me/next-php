require.config({
    baseUrl: '/assets/js/',
    paths: {
        'jquery': 'lib/jquery.min'
    }
});

require(['jquery', 'lib/md5'], function($, md5){
    "use strict";
    jQuery(document).ready(function($) {
        $('#login button.login').click(function() {
            var data = {
                user: $('[name="user"]').val() || '',
                pwd: $('[name="pwd"]').val() || '',
                captcha: ''
            }
            data.hmac = md5(data.user+data.pwd);
            $.post('/login/', data, function(resp) {
                if (resp.status == 200) {
                    location.href = '/admin/order/';
                }else if(resp.status == 403){
                    alert(resp.msg);
                }
            }, 'json');
        });
    });
});
