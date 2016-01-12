define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {
        $('#goods-buy .water-content .table-view-cell').click(function(e){
            if (e.target.type != 'radio') {
                $(this).find('[name="id"]')[0].checked = true;
                $(this).find('[name="id"]').trigger('change');
            }
        });

        $('#goods-buy .segment-header button.btn').click(function(){
            var $c = $(this).parent().parent().next('.segment-content');
            if ($c.css('display') == 'none') {
                $(this).find('span.text').text('收起');
                $(this).find('span.icon').removeClass('icon-down').addClass('icon-up');
                $c.show();
            } else {
                $(this).find('span.text').text('展开');
                $(this).find('span.icon').removeClass('icon-up').addClass('icon-down');
                $c.hide();
            }
        });
        
        $('button.go-step2').click(function(){
            var good = $('#goods-buy .step1 [name="id"]:checked').val();
            if(!good){
                H5.notice('请选择您要购买的商品！');
                return false;
            }
            H5.goto('/shop/goods/step2/?good='+good);
        });
        
        $('button.go-step3').click(function(){
            var address = $('#goods-buy .step2 [name="addressid"]').val();
            if(!address){
                if(H5.confirm('您还没有选择收货地址呢，请选择您的收货地址')){
                    H5.goto('/shop/address/?back=buy');
                }
                return false;
            }
            H5.goto('/shop/goods/step3/?address='+address);
        });
        
         $('button.go-step4').click(function(){
            var num = $('#goods-buy .step3 [name="num"]').val();
            var date = $('#goods-buy .step3 [name="date"]').val();
            if(!date){
                H5.notice('请选择您的送水时间！');
                return false;
            }
            H5.goto('/shop/goods/step4/?num='+num+'&date='+date);
        });
        
        $('#goods-buy .step4 [name="integral"]').change(function() {
            // Integral
            var price = $('#goods-buy .step4 [name="id"]').attr('data-price') || 0;
            var integral = $(this).val() || 0;
            var totalIntegral = $('#goods-buy .have-integral').attr('data-integral') || 0;
            var canUse = _.min([parseInt(price), parseInt(totalIntegral)]);
            
            if (parseInt(integral) < 0) {
                integral = 0;
                $(this).val(0);
            }
            if (integral > canUse) {
                $(this).val(canUse);
                integral = canUse;
            }
            $('#goods-buy .step4 .have-integral').text(totalIntegral - integral);
            var needPay = price? price - integral: '-';
            $('#goods-buy .step4 .need-pay .red').html('&yen;' + needPay);
        });
        
    	$('#goods-buy .step4 button.wx-pay').click(function(){
    		var data = {
    			good:$('#goods-buy [name="id"]').val(),
    			integral:$('#goods-buy [name="integral"]').val() || 0,
    			invoice:$('#goods-buy [name="invoice"]').val(),
                address:$('#goods-buy [name="addressid"]').val(),
                num:$('#goods-buy [name="num"]').val(),
                date:$('#goods-buy [name="date"]').val(),
                msg:$('#goods-buy [name="msg"]').val(),
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
        
        $('#goods-buy .step4 button.delivery-pay').click(function(){
            var data = {
                good:$('#goods-buy [name="id"]').val(),
                integral:$('#goods-buy [name="integral"]').val() || 0,
                invoice:$('#goods-buy [name="invoice"]').val(),
                address:$('#goods-buy [name="addressid"]').val(),
                num:$('#goods-buy [name="num"]').val(),
                date:$('#goods-buy [name="date"]').val(),
                msg:$('#goods-buy [name="msg"]').val(),
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
        
        if ($('#goods').length > 0) {
            $('.goods .subscribe').click(function() {
                H5.wxshare('subscribe');
            });
            Wxapi.ready(function(Api) {
                var appid = $('#goods [name="appid"]').val();
                var wxData = {
                    'appId': appid,
                    'imgUrl': H5.root() + '/assets/img/logo.jpg',
                    'link': location.href,
                    'desc': $('[name="short"]').val(),
                    'title': $('[name="title"]').val()
                };
                var wxCallbacks = {
                    confirm : function(resp) {
                        $.post('/shop/integral/timeline/', function(resp) {
                        }, 'json');
                    }
                };
                Api.shareToTimeline(wxData, wxCallbacks);
            });
        }
    };
    return view;
});
