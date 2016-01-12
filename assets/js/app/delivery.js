define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {
    	$('#delivery button.cancel').click(function() {
    		var id = $(this).attr('data-id');
            var order = $(this).attr('data-order');
            var msg = '确认要取消么？';
            if(parseInt(order)>0){
                msg = '这是您购买商品时的预约单，取消该预约单，您的相关订单也会被取消，确认要取消么？'
            }
        	if(confirm(msg)){
        		$.post('/shop/delivery/cancel/', {id: id}, function(resp) {
                	if (resp.status == 200) {
                		H5.goto('/shop/delivery/');
                    }else if(resp.status == 400) {
                    	H5.notice('取消失败！失败原因：已经配送或取消。');
                    	H5.goto('/shop/delivery/');
                    }
                },'json');
        	}
        });
    	
    	$('#delivery button.sign').click(function() {
    		var id = $(this).attr('data-id');
        	if(confirm('确认要签收么？')){
        		$.post('/shop/delivery/sign/', {id: id}, function(resp) {
                	if (resp.status == 200) {
                		H5.goto('/shop/delivery/');
                    }else if(resp.status == 400) {
                    	H5.notice('签收失败！失败原因：已经签收或取消。');
                    	H5.goto('/shop/delivery/');
                    }
                },'json');
        	}
        });
    	
    	$('#delivery-reserve button.save').click(function(){
    		var data = {
    			address:$('#delivery-reserve [name="addressid"]').val(),
    			num:$('#delivery-reserve [name="num"]').val(),
    			date:$('#delivery-reserve [name="date"]').val()
    		};
            if (!data.address) {
    			if(H5.confirm('您还没有选择收货地址呢，请选择您的收货地址')){
    				H5.goto('/shop/address/?back=delivery');
    			}
                return false;
            }

            $.post('/shop/delivery/add/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    H5.notice('预约成功，我们将尽快给您安排发货，敬请等待。');
                    H5.goto('/shop/');
                } else if(resp.status == 400){
                    H5.notice('预约失败!');
                }
            },'json');
    	});
    };
    return view;
});
