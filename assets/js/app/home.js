define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {
    	Wxapi.ready(function(Api) {
    		var appid = $('#home [name="appid"]').val();
            var wxData = {
                'appId': appid,
                'imgUrl': H5.root() + '/assets/img/logo.jpg',
                'link': H5.root() + '/shop/goods/',
                'desc': '五大连池火山群，在金代被称之为“尔冬吉”（女真语九座火山）,“尔冬吉”优质珍稀瓶装天然苏打水产品品牌，就源于此历史记载。 尔冬吉天然苏打水采取于地下220米深处，水温常年保持在4℃，属珍贵火山复合型冷矿泉水。',
                'title': '尔冬吉4℃火山岩冷矿泉水'
            };
            var wxCallbacks = {
                confirm : function(resp) {
                    $.post('/shop/integral/timeline/', function(resp) {
                    }, 'json');
                }
            };
            Api.shareToTimeline(wxData, wxCallbacks);
        });
        $('#home .icon-share').parent().click(function(e) {
            e.preventDefault();
            Wxapi.showOptionMenu();
            H5.wxshare();
        });

        if ($('#home .icon-order').length > 0) {
            $.get('/api/notice/order/', function(resp) {
                if (resp.status == 200 && resp.num > 0) {
                    $('.icon-order').parent().append('<span class="notice">&nbsp;</span>');
                }
            });
        }
    };

    return view;
});
