define(['zepto', 'ratchet'], function($, R) {
    return {
        'root': function() {
            return location.protocol + '//' + location.host;
        },
        'goto': function(url) {
            if (url.length <= 7 || url.substr(0, 7) != 'http://' && url.substr(0, 8) != 'https://')  {
                url = location.protocol + '//' + location.host + url;
            }
            PUSH({url: url});
        },
        'notice': function(msg) {
            alert(msg);
        },
        'confirm': function(msg) {
            return confirm(msg);
        },
        'error': function(msg) {
           alert(msg);
        },
        'wxshare': function(who) {
            $('.wxshare').click(function(){
                $(this).hide();
            });
            if (who == 'friend') {
                $('.wxshare-text').text('请点击右上角，将它发送给指定的朋友');
            } else if (who == 'subscribe') {
                $('.wxshare-text').text('请点击右上角，"查看公众号"，关注我们');
            } else {
                $('.wxshare-text').text('请点击右上角，将它分享到朋友圈');
            }
            $('.wxshare').show();
        },
        'mobilecheck':function (tel){
			var mobile = /^1[3|5|8]\d{9}$/ , phone = /^0\d{2,3}-?\d{7,8}$/;
			return mobile.test(tel) || phone.test(tel);
        }
    }
});
