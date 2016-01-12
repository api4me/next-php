define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {
    	$('#invite button.commit').click(function(){
            var invite = $('#invite [name="invite"]').val();
            $.post('/shop/invite/commit/', {invite: invite},function(resp) {
    			if(resp.status==200){
    				H5.notice(resp.msg);
    				H5.goto('/shop/invite/');
    			}else if(resp.status==400){
    				H5.notice(resp.msg);
    			}
            }, 'json');
    	});
    };

    return view;
});
