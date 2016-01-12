define(['zepto', 'ratchet', 'underscore', 'app/h5'], function($, R, _, H5){
    var view = function() {

        $('#address button.delete').click(function(){
            var id=$(this).attr('data-id');
        	if(H5.confirm('确定要删除么？')){
        		$.post('/shop/address/delete/', {id: id}, function(resp) {
                	if (resp.status == 200) {
                		H5.goto('/shop/address/?self=1');
                    }else if(resp.status == 400) {
                    	H5.notice('删除失败！');
                    	H5.goto('/shop/address/?self=1');
                    }
                },'json');
        	}
        });
        
        $('#address button.edit').click(function(){
            var id=$(this).attr('data-id');
            H5.goto('/shop/address/add/?id='+id);
        });
        
        $('#address-add button.commit').click(function(){
            var data={
            	id:$('#address-add [name="id"]').val(),
            	consignee:$('#address-add [name="consignee"]').val(),
            	mobile:$('#address-add [name="mobile"]').val(),
            	province:$('#address-add [name="province"]').val(),
            	city:$('#address-add [name="city"]').val(),
            	district:$('#address-add [name="district"]').val(),
            	address:$('#address-add [name="address"]').val(),
            	is_default:$('#address-add [name="is_default"]:checked').val()
            };
            if (data.consignee.length == 0) {
                H5.notice('请填写联系人');
                return false;
            }
            if (data.mobile.length == 0) {
                H5.notice('请填写您的联系方式');
                return false;
            }
            if (data.address.length == 0) {
                H5.notice('请填写您的详细地址');
                return false;
            }
            if(!H5.mobilecheck(data.mobile)){
            	H5.notice('亲，电话号码格式不对哦！');
            	return false;
            }
    		$.post('/shop/address/save/', {data: data}, function(resp) {
            	if (resp.status == 200) {
            		H5.goto('/shop/address/?self=1');
                }else if(resp.status == 400) {
                	H5.notice('添加失败！');
                	H5.goto('/shop/address/?self=1');
                }
            },'json');
        });
        
        $('#address .J-address-radio').click(function(e){
            var back = $(this).attr('back-url');
            var addr = $(this).attr('addr');
            
            if( back=='buy' ){
                H5.goto('/shop/goods/step2/?useaddr='+addr);
            }else if( back=='delivery' ){
                H5.goto('/shop/delivery/reserve/?address='+addr);
            }else if( back=='coupon' ){
                H5.goto('/shop/coupon/step2/?useaddr='+addr);
            }else if( back=='gift' ){
                H5.goto('/shop/gift/step2/?useaddr='+addr);
            }
        });
        
        $("#address-add input,textarea").focus(function(){
            $(".J-append").show();
        });
        
        $("#address-add input,textarea").blur(function(){
            $(".J-append").hide();
        });
        
	};

    return view;
});
