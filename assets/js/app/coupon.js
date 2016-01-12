define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {
        
    	$('#coupon button.icon-wx-share').click(function(){
            H5.wxshare('friend');
    	});
        
        $('#coupon button.recover').click(function(){
            if (H5.confirm('您确认要回收吗？')) {
                $.post('/shop/coupon/recover/', {id: $(this).attr('data-id')}, function(resp) {
                    H5.notice(resp.msg);
                    if (resp.status == 200) {
                        location.href = location.href;
                    }
                });
            }
        });
    	
    	$('#coupon-get button.get-coupon').click(function(){
    		var need_attention = $("#coupon-get [name='need_attention']").val();
    		if(need_attention == 1){
    			H5.notice('您未关注微信公众号“龙门饮品”，关注后才能领取分享券！');
    			return false;
    		}
    		var data = {
    			id:$(this).attr('data-id'),
    			from:$("#coupon-get [name='from']").val(),
    			to:$("#coupon-get [name='to']").val()
			};
    		var self = $(this);
    		$.post('/shop/coupon/draw/', {data: data},function(resp) {
    			if (resp.status == 200) {
					H5.goto('/shop/coupon/exchange/');
                }else if(resp.status == 400) {
                	H5.notice('领取失败！');
                }
            }, 'json');
    	});
        
    	$('#coupon-exchange button.article-show').click(function(){
            $(this).hide();
            $('#coupon-exchange button.article-hide').show();
            $('#coupon-exchange .article').show();
        });
        
        $('#coupon-exchange button.article-hide').click(function(e){
            $(this).hide();
            $('#coupon-exchange button.article-show').show();
            $('#coupon-exchange .article').hide();
        });
        
        $('button.go-step2').click(function(){
            var coupon = $('#coupon-exchange .step1 [name="coupon_id"]').val();
            var good = $('#coupon-exchange .step1 [name="id"]:checked').val();
            if(!good){
                H5.notice('请选择您要购买的商品！');
                return false;
            }
            H5.goto('/shop/coupon/step2/?good='+good+'&coupon='+coupon);
        });
        
        $('button.go-step3').click(function(){
            var address = $('#coupon-exchange .step2 [name="addressid"]').val();
            if(!address){
                if(H5.confirm('您还没有选择收货地址呢，请选择您的收货地址')){
                    H5.goto('/shop/address/?back=coupon');
                }
                return false;
            }
            H5.goto('/shop/coupon/step3/?address='+address);
        });
        
        $('button.go-step4').click(function(){
            var num = $('#coupon-exchange .step3 [name="num"]').val();
            var date = $('#coupon-exchange .step3 [name="date"]').val();
            if(!date){
                H5.notice('请选择您的送水时间！');
                return false;
            }
            H5.goto('/shop/coupon/step4/?num='+num+'&date='+date);
        });
        
    	$('#coupon-get button.use-coupon').click(function(){
        	H5.goto('/shop/coupon/exchange/');
    	});
        
    	$('#coupon-exchange .step4 button.wx-pay').click(function(){
            var data = {
                good:$('#coupon-exchange [name="id"]').val(),
                coupon:$('#coupon-exchange [name="coupon"]').val(),
                integral:$('#coupon-exchange [name="integral"]').val(),
                invoice:$('#coupon-exchange [name="invoice"]').val(),
                address:$('#coupon-exchange [name="addressid"]').val(),
                num:$('#coupon-exchange [name="num"]').val(),
                date:$('#coupon-exchange [name="date"]').val(),
                msg:$('#coupon-exchange [name="msg"]').val(),
                type:'wx'
            };
            $.post('/shop/order/add/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/wechat/pay/?showwxpaytitle=1&id=' + resp.id;
                }else if(resp.status == 100 || resp.status == 300) {
                    location.href = '/shop/order/success/?id=' + resp.id;
                }else if(resp.status == 400) {
                    H5.notice(resp.msg);
                }
            },'json');
        });
        
        $('#coupon-exchange .step4 button.delivery-pay').click(function(){
            var data = {
                good:$('#coupon-exchange [name="id"]').val(),
                integral:$('#coupon-exchange [name="integral"]').val(),
                invoice:$('#coupon-exchange [name="invoice"]').val(),
                address:$('#coupon-exchange [name="addressid"]').val(),
                num:$('#coupon-exchange [name="num"]').val(),
                date:$('#coupon-exchange [name="date"]').val(),
                msg:$('#coupon-exchange [name="msg"]').val(),
                coupon:$('#coupon-exchange [name="coupon"]').val(),
                type:'df'
            };
            $.post('/shop/order/add/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/wechat/pay/?showwxpaytitle=1&id=' + resp.id;
                }else if(resp.status == 100 || resp.status == 300) {
                    location.href = '/shop/order/success/?id=' + resp.id;
                }else if(resp.status == 400) {
                    H5.notice(resp.msg);
                }
            },'json');
        });

        // Fix lower version anroid webview can not checked issue
        if ($('#coupon-exchange .step1 [name="id"]').length > 0) {
            $('#coupon-exchange .step1 [name="id"]')[0].checked=true;
        }
        
    	Wxapi.ready(function(Api) {
    		var coupon = $('#coupon [name="coupon"]').val();
    		var from = $('#coupon [name="from"]').val();
    		var appid = $('#coupon [name="appid"]').val();
            var wxData = {
                'appId': appid,
                'imgUrl': H5.root() + '/assets/img/logo.jpg',
                'link': H5.root() + '/shop/coupon/get/?coupon=' + coupon + '&f=' + from,
                'desc': '五大连池火山群，在金代被称之为“尔冬吉”（女真语九座火山）,“尔冬吉”优质珍稀瓶装天然苏打水产品品牌，就源于此历史记载。 尔冬吉天然苏打水采取于地下220米深处，水温常年保持在4℃，属珍贵火山复合型冷矿泉水。',
                'title': '赠送尔冬吉4℃火山岩冷矿泉水'
            };
            var wxCallbacks = {
                confirm : function(resp) {
                    $.post('/shop/coupon/send/?coupon='+coupon, function(resp) {
                    	if( resp.status == 200 ){
                    		location.href = location.href;
                    	}else if( resp.status == 400 ){
                    		H5.notice('系统忙，请稍后再试...');
                    	}
                    }, 'json');
                }
            };
            Api.shareToFriend(wxData,wxCallbacks);
        });

    };
    
    return view;
});
