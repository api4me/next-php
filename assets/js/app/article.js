define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {
    	$('#article-detail .share .btn').click(function(){
            H5.wxshare();
    	});
    	
    	Wxapi.ready(function(Api) {
    		var appid = $('#article-detail [name="appid"]').val();
            var wxData = {
                'appId': appid,
                'imgUrl': H5.root() + '/assets/img/logo.jpg',
                'link': location.href,
                'desc': $('[name="short"]').val(),
                'title': $('#article-detail header .title').text()
            };
            var wxCallbacks = {
                confirm : function(resp) {
                    $.post('/shop/integral/timeline/', function(resp) {
                    }, 'json');
                }
            };
            Api.shareToTimeline(wxData, wxCallbacks);
        });
    };

    return view;
});
