define(['zepto', 'ratchet', 'underscore', 'app/h5', 'area'], function($, R, _, H5,area){
    var view = function() {
    	$("#order button.cancel").click(function(){
    		var id = $(this).attr('data-id');
            if (parseInt(id) <= 0) {
                return false;
            }
            var msg = (parseInt($(this).attr('data-integral')) > 0)? '取消后，您已使用的积分将不会返还。确定需要取消订单吗？': '确定需要取消订单吗？'
    		if(confirm(msg)){
    			$.post('/shop/order/cancel/', {id: id}, function(resp) {
                    H5.notice(resp.msg);
                	if (resp.status == 200) {
                		H5.goto( '/shop/order/' );
                    }else if(resp.status == 400) {
                    	H5.goto( '/shop/order/' );
                    }
                },'json');
    		}
    	});
    	$("#order button.pay").click(function(){
    		var id = $(this).attr('data-id');
            location.href = '/wechat/pay/?showwxpaytitle=1&id=' + id;
    	});
        
        $("#order-check button.edit").click(function(){
            $("#order-check .J-delivery").hide();
            $("#order-check .J-delivery-edit").show();
        });
        
        $("#order-check button.cancel").click(function(){
            $("#order-check .J-delivery").show();
            $("#order-check .J-delivery-edit").hide();
        });
        
        $("#order-check button.save").click(function(){
            var data={
                order:$('#order-check [name="order"]').val(),
                id:$('#order-check [name="delivery"]').val(),
                province:$('#order-check [name="province"]').val(),
                city:$('#order-check [name="city"]').val(),
                district:$('#order-check [name="district"]').val(),
                consignee:$('#order-check [name="consignee"]').val(),
                mobile:$('#order-check [name="mobile"]').val(),
                address:$('#order-check [name="address"]').val(),
                num:$('#order-check [name="num"]').val(),
                shipping_time:$('#order-check [name="date"]').val()
            }
            $.post('/shop/order/delivery/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    H5.goto('/shop/order/check/?id='+data.order);
                }else if(resp.status == 400) {
                    H5.notice('编辑失败！');
                    H5.goto('/shop/order/check/?id='+data.order);
                }
            },'json');
        });
        
        if ($('#order-check #area').length > 0) {
            var data_province = $('#order-check #area').attr('province');        
            var data_city = $('#order-check #area').attr('city');
            var data_district = $('#order-check #area').attr('district');
            area.create('#order-check #area',data_province,data_city,data_district);
            $("#order-check [name='province'] option").each(function(){
                if($(this).val() != 31){
                    $(this).remove();
                }
            });
            $("#order-check [name='city']").hide();
        }
    };
    return view;
});
