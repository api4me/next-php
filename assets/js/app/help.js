define(['zepto', 'ratchet', 'underscore', 'app/h5'], function($, R, _, H5){
    var view = function() {
    	
    	$("#help-apply-refund button.commit").click(function(){
    		var data={
    			id:	$("#help-apply-refund [name='id']").val(),
    			refund_reason:	$("#help-apply-refund [name='refund_reason']").val(),
    			name:$("#help-apply-refund [name='name']").val(),
    			mobile:$("#help-apply-refund [name='mobile']").val(),
    			card_name:$("#help-apply-refund [name='card_name']").val(),
    			card_num:$("#help-apply-refund [name='card_num']").val(),
    			card_bank:$("#help-apply-refund [name='card_bank']").val(),
    			refund_invoice:	$("#help-apply-refund [name='refund_invoice']").val()
    		};
    		 if (data.refund_reason.length == 0) {
    			 H5.notice('亲，退货原因还没有填写呢！');
                 return false;
             }
    		 if (data.name.length == 0) {
    			 H5.notice('亲，联系人还没有填写呢！');
                 return false;
             }
    		 if (data.mobile.length == 0) {
    			 H5.notice('亲，联系电话还没有填写呢！');
                 return false;
             }
    		 if (data.card_name.length == 0) {
    			 H5.notice('亲，持卡人姓名还没有填写呢！');
                 return false;
             }
    		 if (data.card_num.length == 0) {
    			 H5.notice('亲，银行卡号还没有填写呢！');
                 return false;
             }
    		 if (data.card_bank.length == 0) {
    			 H5.notice('亲，开户行还没有填写呢！');
                 return false;
             }
    		if(!H5.mobilecheck(data.mobile)){
            	H5.notice('亲，电话号码格式不对哦！');
            	return false;
            }
			if(H5.confirm('请仔细核对您填写的信息，确认无误后点击确认提交。）')){
				$.post('/shop/help/commit-refund/', {data: data}, function(resp) {
		        	if (resp.status == 200) {
		        		H5.notice('申请成功，我们的服务人员会在第一时间联系您，退款成功后您可以在我的订单中查看这条订单信息。');
		        		H5.goto('/shop/help/refund/');
		            }else if(resp.status == 400) {
		            	H5.notice('提交申请失败！失败原因：您已申请或该订单不可退款');
		            	H5.goto('/shop/help/refund/');
		            }
		        },'json');
			}
    	});

        $('#help-feedback .feedback').click(function(){
            var data = {
            	contacts: $.trim($('[name="contacts"]').val()),
            	content: $.trim($('[name="content"]').val()),
            	mobile:	$.trim($('[name="mobile"]').val())
            };
            if (data.contacts.length == 0) {
                H5.notice('请填写联系人');
                return false;
            }
            if (data.mobile.length == 0) {
                H5.notice('请填写您的联系方式');
                return false;
            }
            if (data.content.length == 0) {
                H5.notice('请填写反馈的内容');
                return false;
            }
            if(!H5.mobilecheck(data.mobile)){
            	H5.notice('亲，电话号码格式不对哦！');
            	return false;
            }
            $.post('/shop/help/feedback/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    H5.notice('我们非常重视您的意见，我们会仔细研究并尽快给予反馈');
                    H5.goto('/shop/help/');
                } else if(resp.status == 400) {
                    H5.notice('系统忙，请稍后再试');
                }
            },'json');
        });
    	$("input,textarea").focus(function(){
            $(".J-append").show();
        });
        $("input,textarea").blur(function(){
            $(".J-append").hide();
        });
    };
    return view;
});
