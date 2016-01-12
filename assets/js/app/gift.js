define(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi'], function($, R, _, H5, Wxapi){
    var view = function() {

        $('button.get-gift').click(function(){
            var id = $(this).attr('data-id');
            $.post('/shop/gift/attention/', function(resp) {
                if (resp.status == 200) {
                    H5.goto('/shop/gift/step1/?id='+id);
                }else if(resp.status == 400){
                    H5.notice('关注我们后才能领奖哦！');
                }
            });
        });
    	
        $('button.gift-go-step2').click(function(){
            var data = {
                'gift_id' : $('#gift-change [name="serial"]').attr('data-gift'),
                'serial' : $.trim($('#gift-change [name="serial"]').val())
            };
            if (data.serial.length == 0) {
                H5.notice('请输入礼券代码');
                return false;
            }
            $.post('/shop/gift/check/', {data: data}, function(resp) {
                if (resp.status == 400) {
                    H5.notice('您输入的礼券代码无效，请仔细核对后再点击下一步！');
                    return false;
                }else if(resp.status == 200) {
                    H5.goto('/shop/gift/step2/?gift='+data.gift_id+'&serial='+data.serial);
                }
            });
        });
        
        $('button.gift-commit').click(function(){
            var data = {
                'gift_id' : $('#gift-change [name="gift_id"]').val(),
                'serial' : $('#gift-change [name="serial"]').val(),
                'address' : $('#gift-change [name="addressid"]').val()
            };
            if(!data.address){
                if(H5.confirm('您还没有选择收货地址呢，请选择您的收货地址')){
                    H5.goto('/shop/address/?back=gift');
                }
                return false;
            }
            $.post('/shop/gift/exchange/', {data: data}, function(resp) {
                if(resp.status == 200) {
                    H5.goto('/shop/gift/success/');
                }else if (resp.status == 400) {
                    H5.notice(resp.msg);
                }
            });
        });
        
    };
    
    return view;
});
