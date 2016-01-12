require.config({
    baseUrl: '/assets/js/',
    paths: {
        'jquery': 'lib/jquery.min',
        'dragsort': 'lib/jquery.dragsort.min',
        'underscore': 'lib/underscore-min',
        'text': 'lib/text',
        'tpl': '/tpl/admin',
        'k': '../editor/kindeditor-all-min',
        'bootstrap': '../bootstrap/v2/js/bootstrap.min'
    },
    shim: {
        'underscore':{
            exports: '_'
        },
        'k':{
            exports: 'K'
        },
        'dragsort': {
            deps: ['jquery'],
            exports: 'dragsort'
        },
        'bootstrap':  {
            deps: ['jquery'],
            exports: 'bootstrap'
        }
    }
});

require(['jquery', 'bootstrap']);
require(['admin/init']);
require(['admin/k']);

define(['jquery', 'underscore', 'admin/k', 'dragsort'], function($, _, K, Dragsort) {
    jQuery(document).ready(function($) {
        /* Common
        --------------------------*/
        $('table.table-mob .child').hide();
        $('table.table-mob').on('mouseover', 'tr', function() {
            $('.btn-group', $(this)).show();
        }).on('mouseout', 'tr', function() {
            $('.btn-group', $(this)).hide();
        });

        $('div.operate').on('mouseover', 'dt, dd', function() {
            $('.btn-group', $(this)).show();
        }).on('mouseout', 'dt, dd', function() {
            if (!$(this).hasClass('active')) {
                $('.btn-group', $(this)).hide();
            }
        });

        $('[name="form-search"] select').change(function() {
            $('[name="form-search"]').submit();
        });
        /* Menu
        --------------------------*/
        $('#menu .btn.add').click(function() {
            var pid = $(this).attr('data-id');
            var pname = $(this).attr('data-name');
            $('#menu #menuModal')
                .html(_.template($('#tpl-menu').html(), {data: {pid: pid, pname: pname}}))
                .modal({show: true, keyboard: false, backdrop: 'static'});

        });
        $('#menu .menu-list .btn.edit').click(function() {
            var id = $(this).attr('data-id');

            $('#menu #menuModal').modal({show: true, keyboard: false, backdrop: 'static'});
            $.get('/admin/menu/ajax/?id=' + id, function(resp) {
                $('#menu #menuModal').html(_.template($('#tpl-menu').html(), {data: resp.data}));
            }, 'json');
        });
        $('#menu #menuModal').on('click', '.save', function() {
            var data = {
                id: $('#menu #menuModal [name="id"]').val(),
                pid: $('#menu #menuModal [name="pid"]').val(),
                name: $('#menu #menuModal [name="name"]').val()
            }
            $.post('/admin/menu/save/', {data: data}, function(resp) {
                alert(resp.msg);
                if (resp.status == 200) {
                    location.href = '/admin/menu/';
                }
            });
        });
        $('#menu .menu-list .btn.del').click(function() {
            if (confirm('确定删除分类及其子分类？')) {
                var id = $(this).attr('data-id');
                $.post('/admin/menu/del/', {id: id}, function(resp) {
                    alert(resp.msg);
                    if (resp.status == 200) {
                        $('#menu table tr.s-' + id).remove();
                        $('#menu table tr.c-' + id).remove();
                    }
                });
            }
        });
        $('#menu .btn.sort').click(function() {
            var id = $(this).attr('data-id');
            $('#menu #sortModal').modal({show: true, keyboard: false, backdrop: 'static'});
            $.get('/admin/menu/ajax/?m=sort&id=' + (id || 0), function(resp) {
                $('#menu #sortModal .modal-body').html(_.template($('#tpl-sort').html(), {data: resp.data}));
            }, 'json');
        });
        $('#menu #sortModal').on('click', '.save', function() {
            var data = [];
            $('#menu #sortModal [name="sort"]').each(function() {
                data.push({
                    id: $(this).attr('data-id'),
                    sort: $(this).val()
                });
            })
            $.post('/admin/menu/sort/', {data: data}, function(resp) {
                alert(resp.msg);
                if (resp.status == 200) {
                    location.href = '/admin/menu/';
                }
            });
        });
        $('#menu .btn.setting').click(function() {
            $('#menu .menu-list dt, #menu .menu-list dd').removeClass('active');
            $(this).parent().parent().addClass('active')
            $('#menu .menu-list dt, #menu .menu-list dd').trigger('mouseout');

            // Get data
            if ($(this).attr('data-child') && $(this).attr('data-child') != '0') {
                $('#menu .menu-action').html('已有子菜单，无法设置动作');
                return false;
            }

            $.get('/admin/menu/ajax/?id=' + $(this).attr('data-id'), function(resp) {
                // First setting
                if (!resp.data.type) {
                    $('#menu .menu-action').html(_.template($('#tpl-setting').html()));
                    return true;
                }
                if (resp.data.type == 'view') {
                    $('#menu .menu-action').html(_.template($('#tpl-link').html(), {data: resp.data}));
                    return true;
                }

                if (resp.data.type == 'click') {
                    if (resp.data && resp.data.key) {
                        var tmp = resp.data.key.split('-');
                        var data = {
                            type: tmp[0] || 'text',
                            id: tmp[1] || '0'
                        }
                    }
                    $('#menu .menu-action').html(_.template($('#tpl-message').html(), {data: data}));
                    clickAction();
                    return true;
                }
            }, 'json');
        });
        function clickAction() {
            require(['admin/matter'], function(matter) {
                matter.create('#menu .menu-action #wechat');
                $('#menu .menu-action').on('click', '.message [name="save"]', function() {
                    matter.save(function(type, data) {
                        var id = $('#menu .menu-list .active>span>button.setting').attr('data-id');
                        var param = {
                            id: id,
                            type: 'click',
                            key: type + '-' + data.id
                        }
                        $.post('/admin/menu/setting/', {data: param}, function(resp) {
                            alert(resp.msg);
                        });
                    });
                });
            });
        }

        $('#menu .menu-action').on('click', 'a.url', function() {
            $('#menu .menu-action').html(_.template($('#tpl-link').html(), {data: {}}));
        });
        $('#menu .menu-action').on('click', 'a.message', function() {
            $('#menu .menu-action').html(_.template($('#tpl-message').html(), {data: {}}));
            clickAction();
        });

        $('#menu .menu-action').on('click', '[name="back"]', function() {
            $('#menu .menu-action').html(_.template($('#tpl-setting').html()));
        });
        $('#menu .menu-action').on('click', '.link [name="save"]', function() {
            var url = $('#menu .menu-action [name="link"]').val();
            if (!url) {
                return false;
            }

            var param = {
                id: $('#menu .menu-list .active>span>button.setting').attr('data-id'),
                type: 'view',
                url: url 
            };
            $.post('/admin/menu/setting/', {data: param}, function(resp) {
                alert(resp.msg);
            }, 'json');
        });
        /* Product
        --------------------------*/
        /*product-list*/
        $('#product-list btn.search').click(function() {
            $('[name="form-search"]').submit();
        });
        $('#product-list button.up').click(function() {
            var id = $(this).attr('data-id');
            $.post('/admin/product/up/', {id: id}, function(resp) {
                alert(resp.msg);
                if (resp.status == 200) {
                    location.href = '/admin/product/';
                }
            });
        });
        $('#product-list button.down').click(function() {
            var id = $(this).attr('data-id');
            $.post('/admin/product/down/', {id: id}, function(resp) {
                alert(resp.msg);
                if (resp.status == 200) {
                    location.href = '/admin/product/';
                }
            });
        });
        $('#product-list button.trash').click(function() {
            if (confirm('确认将此商品放入回收站？')) {
                var id = $(this).attr('data-id');
                $.post('/admin/product/trash/', {id: id}, function(resp) {
                    alert(resp.msg);
                    if (resp.status == 200) {
                        location.href = '/admin/product/';
                    }
                });
            }
        });
        $('#product-list button.restore').click(function() {
            var id = $(this).attr('data-id');
            $.post('/admin/product/restore/', {id: id}, function(resp) {
                alert(resp.msg);
                if (resp.status == 200) {
                    location.href = '/admin/product/';
                }
            });
        });
        $('#product-list button.del').click(function() {
            if (confirm('确认删除此商品，删除后将不可恢复？')) {
                var id = $(this).attr('data-id');
                $.post('/admin/product/del/', {id: id}, function(resp) {
                    alert(resp.msg);
                    if (resp.status == 200) {
                        location.href = '/admin/product/';
                    }
                });
            }
        });
        $('#product-list button.add').click(function() {
            location.href = '/admin/product/edit/';
        });
        $('#product-list button.edit').click(function() {
            var id = $(this).attr('data-id');
            location.href = '/admin/product/edit/?id=' + id;
        });
        $('#product-list button.sort').click(function() {
            $('#product-list #sortModal').modal({show: true, keyboard: false, backdrop: 'static'});
            $.get('/admin/product/ajax/',  function(resp) {
                $('#product-list #sortModal .modal-body').html(_.template($('#tpl-sort').html(), {data: resp.data}));
            }, 'json');
        });
        $('#product-list #sortModal').on('click', '.save', function() {
            var data = [];
            $('#product-list #sortModal [name="sort"]').each(function() {
                data.push({
                    id: $(this).attr('data-id'),
                    sort: $(this).val()
                });
            })
            $.post('/admin/product/sort/', {data: data}, function(resp) {
                alert(resp.msg);
                if (resp.status == 200) {
                    location.href = '/admin/product/';
                }
            });
        });
        /*product-edit*/
        if ($('#product-edit').length > 0) {
            K.create('textarea[name="desc"]');
        }
        function imageHandle(se) {
            $(se).dragsort('destroy');
            $(se).dragsort({dragSelector: "li", dragBetween: false});
            $('li', se).hover(function() {
                $(this).find('span.del').show();
            }, function() {
                $(this).find('span.del').hide();
            });
            $('li span.del i', se).unbind('click').click(function() {
                if (confirm('是否删除？')) {
                    $(this).parent().parent().remove();
                }
            });
        }
        imageHandle('#product-edit .pic-preview');
        if ($('#product-edit button.upload').length > 0) {
            K.multiupload('button[name="upload"]', '.pic-preview', function() {
                imageHandle('.pic-preview');
            });
        };
        $('#product-edit button.save').click(function() {
            K.get('textarea[name="desc"]').sync();
            var data = {
                'id': $('[name="id"]').val(),
                'name': $('[name="name"]').val(),
                'sn': $('[name="sn"]').val(),
                'short_desc': $('[name="short_desc"]').val(),
                'price': $('[name="price"]').val(),
                'box_num': $('[name="box_num"]').val(),
                'market_price': $('[name="market_price"]').val(),
                'sort': $('[name="sort"]').val(),
                'integral': $('[name="integral"]').val(),
                'coupon': $('[name="coupon"]').val(),
                'on_sale': $('[name="on_sale"]').is(':checked')? 1: 0,
                'is_promote': $('[name="is_promote"]').is(':checked')? 1: 0,
                'is_gift': $('[name="is_gift"]').is(':checked')? 1: 0,
                /*'pic': (function() {
                    var p = [];
                    $('.pic-preview li img').each(function() {
                        p.push($(this).attr('data-name'));
                    });
                    return p;
                })(),*/
                'desc': $('textarea[name="desc"]').val()
            };
            $.post('/admin/product/save/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/admin/product/';
                }else if(resp.status == 400) {
                	alert(resp.msg);
                    location.href = '/admin/product/';
                }
            });
        });
        $('#product-edit button.back').click(function() {
            location.href = '/admin/product/';
        });
        /* Order
        --------------------------*/
        /* Order---list
        --------------------------*/
        $('#order-list button.see').click(function(){
            var id = $(this).attr('data-id');
            location.href = '/admin/order/see/?id=' + id;
        });
        $('#order-list button.cls').click(function(){
            if (confirm('确定将此订单作废？')) {
                var id = $(this).attr('data-id');
                $.post('/admin/order/cls/', {id: id}, function(resp) {
                	if (resp.status == 200) {
                        location.href = '/admin/order/';
                    }else if(resp.status == 400) {
                    	alert(resp.msg);
                    }
                });
            }
        });
        $('#order-list button.refund').click(function(){
            var id = $(this).attr('data-id');
            location.href = '/admin/order/refund/?id=' + id;
        });
        $('#order-list button.receive').click(function(){
            var id = $(this).attr('data-id');
            location.href = '/admin/order/receive/?id=' + id;
        });
        /* Order---see
        --------------------------*/
        $('#order-see button.back').click(function() {
            location.href = '/admin/order/';
        });
        $('#order-see button.edit').click(function() {
            $('#order-see .remark-show').hide();
            $('#order-see .remark-edit').show();
        });
        $('#order-see button.cancel').click(function() {
    		$('#order-see .remark-edit').hide();
            $('#order-see .remark-show').show();
        });
        $('#order-see button.remark-save').click(function() {
            var data = {
        		id:$(this).attr('data-id'),
            	remark:$("#order-see [name='remark']").val()
            };
            $.post('/admin/order/editOrder/', {data: data}, function(resp) {
            	if(resp.status==200){
            		$('#order-see .remark-show span').text(data.remark);
            		$("#order-see [name='remark']").val(data.remark);
            		$('#order-see .remark-edit').hide();
                    $('#order-see .remark-show').show();
            	}else if(resp.status==400){
            		alert('保存失败');
            	}
            });
        });
        /* Order---receive
        --------------------------*/
        $('#order-receive button.save').click(function() {
            var data = {
        		id: $(this).attr('data-id'),
        		money: $('[name="pay-money"]').val(),
        		date: $('[name="pay-date"]').val(),
        		remark: $('[name="pay-remark"]').val()
            };
            $.post('/admin/order/receiveSave/', {data: data}, function(resp) {
                alert(resp.msg);
            });
        });
        $('#order-receive button.back').click(function() {
            location.href = '/admin/order/';
        });
        
        $('#order-receive button.receive').click(function() {
            var data = {
        		id: $(this).attr('data-id'),
        		money: $('[name="pay-money"]').val(),
        		date: $('[name="pay-date"]').val(),
        		remark: $('[name="pay-remark"]').val()
            };
            if (data.money.length == 0) {
                alert('请填写实收金额');
                return false;
            } else {
                if (data.money > $('[name="pay-money"]').attr('data-max')) {
                    alert('实收金额已经超出需支付金额');
                    return false;
                }
            }
            if (data.date.length == 0) {
                alert('请填写收款日期');
                return false;
            } else {
                var pattern = /^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)$/;
                if (!pattern.test(data.date)) {
                    alert('请填写正确的收款日期(格式yyyy-mm-dd)');
                    return false;
                }
            }

            if (confirm('请确认填写的收款信息，收款成功后不可更改。')) {
                $.post('/admin/order/receiveFinish/', {data: data}, function(resp) {
                    alert(resp.msg);
                    if (resp.status == 200) {
                        location.href = '/admin/order/see/?id='+ data.id;
                    }
                });
            }
        });
        /* Order---refund
        --------------------------*/
        $('#order-refund button.back').click(function() {
        	location.href = '/admin/order/';
        });
        $('#order-refund button.edit').click(function() {
            $('#order-refund .remark-show').hide();
            $('#order-refund .remark-edit').show();
        });
        $('#order-refund button.cancel').click(function() {
    		$('#order-refund .remark-edit').hide();
            $('#order-refund .remark-show').show();
        });
        $('#order-refund button.remark-save').click(function() {
            var data = {
        		id:$('#order-refund [name="id"]').val(),
            	remark:$("#order-refund [name='remark']").val()
            };
            $.post('/admin/order/editOrder/', {data: data}, function(resp) {
            	if(resp.status==200){
            		$('#order-refund .remark-show span').text(data.remark);
            		$("#order-refund [name='remark']").val(data.remark);
            		$('#order-refund .remark-edit').hide();
                    $('#order-refund .remark-show').show();
            	}else if(resp.status==400){
            		alert('保存失败')
            	}
            });
        });
        $('#order-refund button.reject').click(function() {
            if (confirm('确定驳回？')) {
                var data = {
                    id: $('#order-refund [name="id"]').val(),
                    user_id: $('#order-refund [name="user_id"]').val(),
                    water: $('#order-refund [name="goods_residue"]').val()
                }
                $.post('/admin/order/rejectRefund/', {data: data}, function(resp) {
                    if (resp.status == 200) {
                        location.href = '/admin/order/see/?id='+ data.id;
                    }else if(resp.status == 400) {
                    	alert('驳回退款失败！');
                    }
                });
            }
        });
        $('#order-refund button.reply').click(function() {
        	if (confirm('确认提交退款信息（信息有误仅有一次修改机会！）？')) {
	            var data = {
	        		id: $('#order-refund [name="id"]').val(),
	                user_id: $('#order-refund [name="user_id"]').val(),
	                water: $('#order-refund [name="goods_residue"]').val(),
	                refund_amount: $('#order-refund [name="refund_amount"]').val()
	            }
	            $.post('/admin/order/commitRefund/', {data: data}, function(resp) {
	            	if (resp.status == 200) {
	                    location.href = '/admin/order/';
	                }else if(resp.status == 400) {
	                	alert(resp.msg);
	                }
	            });
        	}
        });
        $('#order-refund button.change-reply').click(function() {
        	if (confirm('确认后，此订单将不可在修改！！！')) {
	            var data = {
	        		id: $('#order-refund [name="id"]').val(),
	                refund_amount: $('#order-refund [name="refund_amount"]').val(),
	                refund_history_amount: $('#order-refund [name="refund_amount"]').attr('data-amount')
	            }
	            $.post('/admin/order/finishRefund/', {data: data}, function(resp) {
	            	if (resp.status == 200) {
	                    location.href = '/admin/order/see/?id='+ data.id;
	                }else if(resp.status == 400) {
	                	alert(resp.msg);
	                }
	            });
        	}
        });
        /* Delivery
        --------------------------*/
        /* delivery-list
        --------------------------*/
        $('#delivery-list button.see').click(function() {
            var id = $(this).attr('data-id');
            location.href = '/admin/delivery/see/?id=' + id;
        });
        $('#delivery-list button.edit').click(function() {
            var id = $(this).attr('data-id');
            location.href = '/admin/delivery/edit/?id=' + id;
        });
        $('#delivery-list button.edit-remark').click(function() {
            var id = $(this).attr('data-id');
            $('#delivery-list #remarkModal').modal({show: true, keyboard: false, backdrop: 'static'});
            $.get('/admin/delivery/ajax/?id=' + (id || 0), function(resp) {
                $('#delivery-list #remarkModal .modal-body').html(_.template($('#tpl-remark').html(), {data: resp.data}));
            }, 'json');
        });
        $('#delivery-list #remarkModal button.save').click(function() {
            var data = {
            	id:$('#delivery-list #remarkModal [name="id"]').val(),
            	remark:$('#delivery-list #remarkModal [name="remark"]').val()
            };
            $.post('/admin/delivery/editDeliveryRemark/', {data: data}, function(resp) {
            	if(resp.status==200){
            		location.href = '/admin/delivery/';
            	}else if(resp.status==400){
            		alert('保存失败')
            	}
            });
        });
        $('#delivery-list button.comment').click(function() {
            var id = $(this).attr('data-id');
            location.href = '/admin/delivery/comment/?id=' + id;
        });
        
        $('#delivery-list button.cls').click(function() {
            var order = $(this).attr('data-order-id');
            var data = {
            	id:$(this).attr('data-id'),
            	result_comment:''
            };
            var msg = '确认作废此单？作废后此单状态将改为已作废，并归还用户的水量';
            if(order){
                msg = '这是客户购买商品时的预约单，取消该预约单，客户的相关订单也会被取消，确认要取消么？';
            }
        	if(confirm(msg)){
        		$.post('/admin/delivery/shipCancel/', {data: data}, function(resp) {
                	if (resp.status == 200) {
                        location.href = '/admin/delivery/';
                    }else if(resp.status == 400) {
                    	alert('操作失败！！！');
                    }
                });
        	}
        });
        $('#delivery-list button.statement').click(function() {
            var id = $(this).attr('data-id');
            if(confirm('确认将此单结单？')){
            	$.post('/admin/delivery/finish/', {id: id}, function(resp) {
                	if (resp.status == 200) {
                        location.href = '/admin/delivery/';
                    }else if(resp.status == 400) {
                    	alert('操作失败！！！');
                    }
                });
        	}
        });
        $('#delivery-list button.cancle-sign').click(function() {
            var id = $(this).attr('data-id');
            if(confirm('确认将此单状态改为配送中？')){
            	$.post('/admin/delivery/cancleSign/', {id: id}, function(resp) {
                	if (resp.status == 200) {
                        location.href = '/admin/delivery/';
                    }else if(resp.status == 400) {
                    	alert('操作失败！！！');
                    }
                });
        	}
        });
        $('#delivery-list [name="checkall"]').click(function() {
        	SelectAll("orderid[]");
        });
        
        $('#delivery-list button.J-all').click(function() {
            var data = {
            	'action':$(this).attr('action'),
                'ids': (function() {
                    var p = [];
                    $('#delivery-list [name="orderid[]"]:checked').each(function() {
                        p.push($(this).val());
                    });
                    return p;
                })()
            };
            if(data.ids.length > 0 ){
            	var message = '';
            	if (data.action == 'logistics'){
            		message = '确认将选中的单子采用物流配送?';
            	}else if(data.action == 'self'){
            		message = '确认将选中的单子自己配送?';
            	}else if(data.action == 'ship'){
            		message = '确认将选中的单子状态改为配送中?';
            	}else if(data.action == 'sign'){
            		message = '确认将选中的单子状态改为已送达?';
            	}
            	if(confirm(message)){
            		$.post('/admin/delivery/allLogistics/', {data: data}, function(resp) {
                        if (resp.status == 200) {
                            location.href = '/admin/delivery/';
                        }else if(resp.status == 400) {
                        	alert('操作失败！！！');
                            location.href = '/admin/delivery/';
                        }
                    });
            	}
            }else{
            	alert('还没选择要发货的单子呢');
            }
        });
        /* delivery-see
        --------------------------*/
        $('#delivery-see button.back').click(function() {
            location.href = '/admin/delivery/';
        });
        /* delivery-edit
        --------------------------*/
        $('#delivery-edit button.back').click(function() {
            location.href = '/admin/delivery/';
        });
        $('#delivery-edit button.save').click(function() {
            var data = {
            	id:$('#delivery-edit [name="id"]').val(),
            	remark:$('#delivery-edit [name="remark"]').val(),
            	shipping_time:$('#delivery-edit [name="shipping_time"]').val(),
    			type:$("#delivery-edit [name='type']").val(),
    			consignee:$("#delivery-edit [name='consignee']").val(),
            	mobile:$("#delivery-edit [name='mobile']").val(),
            	province:$("#delivery-edit [name='province']").val(),
            	city:$("#delivery-edit [name='city']").val(),
            	district:$("#delivery-edit [name='district']").val(),
            	address:$("#delivery-edit [name='address']").val()
            };
            $.post('/admin/delivery/editSave/', {data: data}, function(resp) {
            	if (resp.status == 200) {
                    location.href = '/admin/delivery/';
                }else if(resp.status == 400) {
                	alert('操作失败！！！');
                    location.href = '/admin/delivery/edit?id='+data.id;
                }
            });
        });
        /* delivery-comment
        --------------------------*/
        $('#delivery-comment button.back').click(function() {
            location.href = '/admin/delivery/';
        });
        $('#delivery-comment button.success').click(function() {
            var data = {
            	id:$('#delivery-comment [name="id"]').val(),
            	result_comment:$('#delivery-comment [name="result_comment"]').val()
            }
            if(confirm('确认将此单状态改为已送达？')){
            	$.post('/admin/delivery/shipSuccess/', {data: data}, function(resp) {
                	if (resp.status == 200) {
                        location.href = '/admin/delivery/';
                    }else if(resp.status == 400) {
                    	alert('操作失败！！！');
                        location.href = '/admin/delivery/comment/?id='+data.id;
                    }
                });
        	}
        });
        $('#delivery-comment button.failure').click(function() {
            var data = {
            	id:$('#delivery-comment [name="id"]').val(),
            	result_comment:$('#delivery-comment [name="result_comment"]').val()
            }
            if(data.result_comment==''||data.result_comment.length==0){
            	alert('请填写结果说明');
            }else{
            	if(confirm('确认提交失败信息？')){
            		$.post('/admin/delivery/shipFailure/', {data: data}, function(resp) {
                    	if (resp.status == 200) {
                            location.href = '/admin/delivery/';
                        }else if(resp.status == 400) {
                        	alert('操作失败！！！');
                            location.href = '/admin/delivery/comment/?id='+data.id;
                        }
                    });
            	}
            }
        });
        $('#delivery-comment button.cancel').click(function() {
            var order = $('#delivery-comment [name="order"]').val();
            var need_pay = $('#delivery-comment [name="need_pay"]').val();
            var data = {
            	id:$('#delivery-comment [name="id"]').val(),
            	result_comment:$('#delivery-comment [name="result_comment"]').val()
            }
            if(data.result_comment==''||data.result_comment.length==0){
            	alert('请填写结果说明');
            }else{
                var msg = '确认作废此单？作废后此单状态将改为已作废，并归还用户的水量!';
                if(need_pay==1){
                    msg = '这是客户购买商品时的预约单，取消该预约单，客户的相关订单也会被取消，确认要取消么？';
                }
            	if(confirm(msg)){
            		$.post('/admin/delivery/shipCancel/', {data: data}, function(resp) {
                    	if (resp.status == 200) {
                            location.href = '/admin/delivery/';
                        }else if(resp.status == 400) {
                        	alert('操作失败！！！');
                            location.href = '/admin/delivery/comment/?id='+data.id;
                        }
                    });
            	}
            }
        });
        $('#delivery-comment button.b-remark-edit').click(function() {
            $('#delivery-comment .remark-show').hide();
            $('#delivery-comment .remark-edit').show();
        });
        $('#delivery-comment button.b-remark-cancel').click(function() {
    		$('#delivery-comment .remark-edit').hide();
            $('#delivery-comment .remark-show').show();
        });
        $('#delivery-comment button.b-remark-save').click(function() {
            var data = {
        		id:$(this).attr('data-id'),
            	remark:$("#delivery-comment [name='remark']").val()
            };
            $.post('/admin/delivery/editDeliveryRemark/', {data: data}, function(resp) {
            	if(resp.status==200){
            		$('#delivery-comment .remark-show span').text(data.remark);
            		$("#delivery-comment [name='remark']").val(data.remark);
            		$('#delivery-comment .remark-edit').hide();
                    $('#delivery-comment .remark-show').show();
            	}else if(resp.status==400){
            		alert('保存失败')
            	}
            });
        });
        /* User
        --------------------------*/
        /* user-list
        --------------------------*/
        $('#user-list button.see').click(function() {
            var id = $(this).attr('data-id');
            location.href = '/admin/user/see/?id=' + id;
        });
        $('#user-list button.level-up').click(function(){
        	var id = $(this).attr('data-id');
			if(confirm('确认将此用户升级为创始会员？')){
				$.post('/admin/user/lvUp/', {id: id}, function(resp) {
					if(resp.status == 200){
						location.href = '/admin/user/';
					}else if(resp.status == 400){
						alert('用户升级失败');
					}
				});
			}
		});
		$('#user-list button.level-down').click(function(){
			var id = $(this).attr('data-id');
			if(confirm('确认将此用户降级为普通会员？')){
				$.post('/admin/user/lvDown/', {id: id}, function(resp) {
					if(resp.status == 200){
						location.href = '/admin/user/';
					}else if(resp.status == 400){
						alert('用户升级失败');
					}
				});
			}
		});
		$('#user-list button.black-list').click(function(){
			var id = $(this).attr('data-id');
			if(confirm('确认将此用户拉入黑名单？')){
	           	$.post('/admin/user/blackList/', {id: id}, function(resp) {
					if(resp.status == 200){
						location.href = '/admin/user/';
					}else if(resp.status == 400){
						alert('用户升级失败');
					}
				});
			}
		});
		$('#user-list button.white-list').click(function(){
			var id = $(this).attr('data-id');
			if(confirm('确认将此用户移出黑名单？')){
				$.post('/admin/user/whiteList/', {id: id}, function(resp) {
					if(resp.status == 200){
						location.href = '/admin/user/';
					}else if(resp.status == 400){
						alert('用户升级失败');
					}
				});
			}
		});
        /* user-see
        --------------------------*/
        $('#user-see button.back').click(function() {
            location.href = '/admin/user/';
        });
        $('#user-see').on('click','button.level-up',function(){
        	 var id = $('#user-see [name="uid"]').val();
            if(confirm('确认将此用户升级为创始会员？')){
            	$.post('/admin/user/lvUp/', {id: id}, function(resp) {
	            	if(resp.status == 200){
	            		$('#user-see .J-type').html('创始会员  <button class="btn btn-link btn-mini level-down" title="点击降级为普通会员"><i class="icon-star"></i></button>');
	            	}else if(resp.status == 400){
	            		alert('用户升级失败');
	            	}
	            });
            }
        });
        $('#user-see').on('click','button.level-down',function(){
        	 var id = $('#user-see [name="uid"]').val();
            if(confirm('确认将此用户从创始会员降为普通会员？')){
            	$.post('/admin/user/lvDown/', {id: id}, function(resp) {
	            	if(resp.status == 200){
	            		$('#user-see .J-type').html('会员  <button class="btn btn-link btn-mini level-up" title="点击升级为创始会员"><i class="icon-star-empty"></i></button>');
	            	}else if(resp.status == 400){
	            		alert('用户降级失败');
	            	}
	            });
            }
        });
        
        $('#user-see').on('click','button.blackList',function(){
       	 var id = $('#user-see [name="uid"]').val();
           if(confirm('确认将此用户拉入黑名单？')){
           	$.post('/admin/user/blackList/', {id: id}, function(resp) {
	            	if(resp.status == 200){
	            		$('#user-see .J-status').html('黑户<button class="btn btn-link btn-mini whiteList" title="点击移出黑名单"><i class="icon-repeat"></i></button>');
	            	}else if(resp.status == 400){
	            		alert('拉黑用户失败');
	            	}
	            });
           }
        });
		$('#user-see').on('click','button.whiteList',function(){
			var id = $('#user-see [name="uid"]').val();
			if(confirm('确认将此用户移出黑名单？')){
				$.post('/admin/user/whiteList/', {id: id}, function(resp) {
					if(resp.status == 200){
						$('#user-see .J-status').html('正常 <button class="btn btn-link btn-mini blackList" title="点击升级为创始会员"><i class="icon-remove"></i></button>');
					}else if(resp.status == 400){
						alert('移出黑名单失败');
					}
				});
			}
		});
		$('#user-see button.nickname-edit').click(function(){
			$('#user-see .J-nickname').hide();
			$('#user-see .J-nickname-edit').show();
		});
		$('#user-see button.nickname-commit').click(function(){
			var data = {
				id : $('#user-see [name="uid"]').val(),
				nickname : $('#user-see [name="nickname"]').val()
			}
			$.post('/admin/user/nicknameEdit/', {data: data}, function(resp) {
				if(resp.status == 200){
					$('#user-see [name="nickname"]').val(data.nickname);
					$('#user-see .J-nickname-edit').hide();
					$('#user-see .nickname').text(data.nickname);
					$('#user-see .J-nickname').show();
				}else if(resp.status == 400){
					alert('移出黑名单失败');
				}
			});
		});
		$('#user-see button.coupon-edit').click(function(){
			$('#user-see .J-coupon').hide();
			$('#user-see .J-coupon-edit').show();
		});
		$('#user-see button.coupon-commit').click(function(){
			var have_coupon = $('#user-see [name="coupon"]').attr('date-have');
			var data = {
				id : $('#user-see [name="uid"]').val(),
				coupon : $('#user-see [name="coupon"]').val()
			}
			if(parseInt(have_coupon) > parseInt(data.coupon)){
				$('#user-see [name="coupon"]').val(have_coupon);
				alert('您修改的值小于用户之前的coupon数量！');
			}else{
				if(confirm('确认修改么？')){
					data['addnum'] = data['coupon'] - have_coupon;
					$.post('/admin/user/couponEdit/', {data: data}, function(resp) {
						if(resp.status == 200){
							location.href = '/admin/user/see/?id='+data.id;
						}else if(resp.status == 400){
							alert('移出黑名单失败');
						}
					});
				}
			}
		});
		$('#integral-list button.back').click(function() {
			var id = $(this).attr('uid');
            location.href = '/admin/user/see/?id='+id;
        });
		/* feedback-list
        --------------------------*/
		$('#feedback-list button.delete').click(function() {
			var ids = {0:$(this).attr('data-id')};
			if(confirm("确定要删除这条留言么？")){
	    		$.post('/admin/feedback/delete/', {ids: ids}, function(resp) {
	                if (resp.status == 200) {
	                    location.href = '/admin/feedback/';
	                }else if(resp.status == 400) {
	                	alert('删除失败！！！');
	                    location.href = '/admin/feedback/';
	                }
	            });
			}
        });
		
		$('#feedback-list [name="checkall"]').click(function() {
			SelectAll("feedback_id[]");
        });
		
		$('#feedback-list button.delete-all').click(function() {
			var data = {
                'ids': (function() {
                    var p = [];
                    $('#feedback-list [name="feedback_id[]"]:checked').each(function() {
                        p.push($(this).val());
                    });
                    return p;
                })()
            };
            if(data.ids.length > 0 ){
            	if(confirm("确定要删除这些留言么？")){
            		$.post('/admin/feedback/delete/', {ids: data.ids}, function(resp) {
                        if (resp.status == 200) {
                            location.href = '/admin/feedback/';
                        }else if(resp.status == 400) {
                        	alert('删除失败！！！');
                            location.href = '/admin/feedback/';
                        }
                    });
            	}
            }else{
            	alert('您还没有选择要删除的留言呢');
            }
        });
		/* article-list
        --------------------------*/
		$('#article-list button.edit').click(function() {
			var id = $(this).attr('data-id');
			location.href = '/admin/article/edit/?id='+id;
        });
		$('#article-list button.add').click(function() {
			location.href = '/admin/article/edit/';
        });
		$('#article-list button.delete').click(function() {
			var id = $(this).attr('data-id');
			if(confirm("确定要删除这篇文章么？")){
	    		$.post('/admin/article/delete/', {id: id}, function(resp) {
	                if (resp.status == 200) {
	                    location.href = '/admin/article/';
	                }else if(resp.status == 400) {
	                	alert('删除失败！！！');
	                }
	            });
			}
        });
        $('#article-list button.see').click(function() {
            var id = $(this).attr('data-id');
            $('#article-list .J-article-href-'+id).removeClass('hide');
            $(this).hide();
        });
        $('#article-list i.close').click(function() {
            var id = $(this).attr('data-id');
            $('#article-list .J-article-href-'+id).addClass('hide');
            $('#article-list button.see').show();
        });
		/* article-edit
        --------------------------*/
		$('#article-edit button.save').click(function() {
			K.get('textarea[name="content"]').sync();
			var data = {
				id:$('#article-edit [name="id"]').val(),
				name:$('#article-edit [name="name"]').val(),
				content:$('textarea[name="content"]').val()
			};
			$.post('/admin/article/save/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/admin/article/';
                }else if(resp.status == 400) {
                	alert('保存失败,请检查您的日期格式，以及是否已有添加过的日期！！！');
                }
            });
        });
		$('#article-edit button.back').click(function() {
			location.href = '/admin/article/';
        });
		if ($('#article-edit').length > 0) {
            K.create('textarea[name="content"]');
        }
		/* shiptime    start
        --------------------------*/
       $('#shiptime-list button.del').click(function() {
            var id = $(this).attr('data-id');
            if(confirm("确定要删除么？")){
                $.post('/admin/shiptime/del/', {id: id}, function(resp) {
                    if (resp.status == 200) {
                        location.href = '/admin/shiptime/';
                    }else if(resp.status == 400) {
                        alert('删除失败！！！');
                    }
                });
            }
        });
        $('#shiptime-edit button.back').click(function() {
            history.back();
        });
       $('#shiptime-edit button.save').click(function() {
            var data = {
                id:$('#shiptime-edit [name="id"]').val(),
                except_date:$('#shiptime-edit [name="except_date"]').val(),
                remark:$('#shiptime-edit textarea[name="remark"]').val()
            };
            $.post('/admin/shiptime/save/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/admin/shiptime/';
                }else if(resp.status == 400) {
                    alert('保存失败！！！');
                }
            });
        });
       /* shiptime    end
        --------------------------*/
       /* activity    start
        --------------------------*/
       /* activity-list
        --------------*/
        $('#activity-list button.add-activity').click(function() {
            $('#activity-list #addModal').modal({show: true, keyboard: false, backdrop: 'static'});
        });
        $('#activity-list button.del').click(function() {
            var id = $(this).attr('data-id');
            if (confirm("确定要删除这个活动？")) {
                $.post('/admin/activity/delActivity/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        location.href = '/admin/activity/';
                    }
                    else if (resp.status == 400) {
                        alert('删除失败');
                    }
                }, 'json');
            }
        });
        $('#activity-list #addModal button.save').click(function() {
            var data = {
                title:$('#activity-list #addModal [name="title"]').val(),
                num:$('#activity-list #addModal [name="num"]').val(),
                end_time:$('#activity-list #addModal [name="end_time"]').val()
            };
            if(data.title.length==0){
                alert('活动标题为必填项');
                return false;
            }
            if(!isPInt(data.num)||data.num.length==0){
                alert('赠券的数量为必填项，且为正整数');
                return false;
            }
            if(data.end_time.length==0){
                alert("还没有填写截止日期，默认有效时间为一年。");
            }
            $.post('/admin/activity/add/', {data: data}, function(resp) {
                if(resp.status==200){
                    location.href = '/admin/activity/';
                }else if(resp.status==400){
                    alert('保存失败')
                }
            });
        });
        /* activity-edit
         --------------*/
        $('#activity-edit button.del-activity').click(function() {
            var id = $('#activity-edit [name="id"]').val();
            if (confirm("确定要删除这个活动？")) {
                $.post('/admin/activity/delActivity/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        location.href = '/admin/activity/';
                    }
                    else if (resp.status == 400) {
                        alert('删除失败');
                    }
                }, 'json');
            }
        });
        $('#activity-edit button.back').click(function() {
            location.href = '/admin/activity/';
        });
        $('#activity-edit button.remove-user').click(function() {
            var id = $(this).attr('data-id');
            if (confirm("确定要去掉此人么？")) {
                $.post('/admin/activity/delUser/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        $('#activity-edit .J-have-user-' + id).remove();
                    }
                    else if (resp.status == 400) {
                        alert('删除失败')
                    }
                }, 'json');
            }
        });
        $('#activity-edit button.search').click(function() {
            var data = {
                type : $('#activity-edit [name="type"]').val(),
                name : $('#activity-edit [name="name"]').val(),
                id : $('#activity-edit [name="id"]').val()
            };
            $.post('/admin/activity/ajaxLoadUsers/',{data: data}, function(resp) {
                $('#activity-edit .J-all-user-show').html(_.template($('#tpl-user-li').html(), {data: resp.data}));
            }, 'json');
        });
        $('#activity-edit').on('click', '.J-adduser-select', function() {
            var data={
                id:$(this).attr('data-id'),
                name:$(this).text()
            };
            var html = '<li class="J-'+data.id+' clearfix" data-id="'+data.id+'">'+data.name;
            html+='<button class="btn btn-link pull-right" data-id="'+data.id+'"><i class="icon-trash"></i></button></li>';
            $(this).children('i').addClass('icon-ok');
            $(this).children('i').removeClass('icon-arrow-right');
            $(this).removeClass('J-adduser-select');
            $(this).addClass('J-adduser-cancel');
            $('#activity-edit .J-selected-user-show').append(html);
            var nums = getLiNums('#activity-edit .J-selected-user-show');
            $('#activity-edit .J-selected-user-num').text(nums);
        });
        $('#activity-edit').on('click', '.J-adduser-cancel', function() {
            var id = $(this).attr('data-id');
            $(this).children('i').removeClass('icon-ok');
            $(this).children('i').addClass('icon-arrow-right');
            $(this).addClass('J-adduser-select');
            $(this).removeClass('J-adduser-cancel');
            $('#activity-edit .J-selected-user-show .J-'+id).remove();
            var nums = getLiNums('#activity-edit .J-selected-user-show');
            $('#activity-edit .J-selected-user-num').text(nums);
        });
        $('#activity-edit .J-selected-user-show').on('click', 'button', function() {
            var id = $(this).attr('data-id');
            $('#activity-edit .J-all-user-show .J-'+id).children('i').removeClass('icon-ok');
            $('#activity-edit .J-all-user-show .J-'+id).children('i').addClass('icon-arrow-right');
            $('#activity-edit .J-all-user-show .J-'+id).addClass('J-adduser-select');
            $('#activity-edit .J-all-user-show .J-'+id).removeClass('J-adduser-cancel');
            $('#activity-edit .J-selected-user-show .J-'+id).remove();
            var nums = getLiNums('#activity-edit .J-selected-user-show');
            $('#activity-edit .J-selected-user-num').text(nums);
        });
        $('#activity-edit button.save').click(function() {
            var data = {
                'id': $('#activity-edit [name="id"]').val(),
                'title': $('#activity-edit [name="title"]').val(),
                'num': $('#activity-edit [name="num"]').val(),
                'end_time': $('#activity-edit [name="end_time"]').val(),
                'users': (function() {
                    var u = [];
                    $('#activity-edit .J-selected-user-show li').each(function() {
                        u.push({
                            user_id: $(this).attr('data-id'),
                            user_name: $(this).text()
                        });
                    });
                    return u;
                })()
            };
            $.post('/admin/activity/save/', {data: data}, function(resp) {
                if(resp.status==200){
                    location.href = '/admin/activity/edit/?id='+data.id;
                }else if(resp.status==400){
                    alert('保存失败')
                }
            });
        });
        $('#activity-edit button.perform').click(function() {
            var id = $('#activity-edit [name="id"]').val();
            if(confirm('确定要执行么？执行后所有的数据将不可修改')){
                $.post('/admin/activity/perform/', {id: id}, function(resp) {
                    if(resp.status==200){
                        location.href = '/admin/activity/edit/?id='+id;
                    }else if(resp.status==400){
                        alert('执行失败')
                    }
                });
            }
        });
        /* activity    end
        --------------------------*/
        /* awards    start
        --------------------------*/
        /* awards-list
        --------------*/
        $('#awards-list button.add-awards').click(function() {
            $('#awards-list #addModal').modal({show: true, keyboard: false, backdrop: 'static'});
        });
        $('#awards-list button.del').click(function() {
            var id = $(this).attr('data-id');
            if (confirm("确定要删除这个抽奖信息么？")) {
                $.post('/admin/awards/delawards/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        location.href = '/admin/awards/';
                    }
                    else if (resp.status == 400) {
                        alert('删除失败');
                    }
                }, 'json');
            }
        });
        $('#awards-list #addModal button.save').click(function() {
            var data = {
                title:$('#awards-list #addModal [name="title"]').val(),
                content:$('#awards-list #addModal textarea[name="content"]').val()
            };
            if(data.title.length==0){
                alert('抽奖标题不可为空');
                return false;
            }
            if(data.content.length==0){
                alert('抽奖详情不可为空');
                return false;
            }
            $.post('/admin/awards/add/', {data: data}, function(resp) {
                if(resp.status==200){
                    location.href = '/admin/awards/';
                }else if(resp.status==400){
                    alert('保存失败')
                }
            });
        });
        /* awards-edit
         --------------*/
        $('#awards-edit button.del-awards').click(function() {
            var id = $('#awards-edit [name="id"]').val();
            if (confirm("确定要删除这个抽奖信息？")) {
                $.post('/admin/awards/delawards/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        location.href = '/admin/awards/';
                    }
                    else if (resp.status == 400) {
                        alert('删除失败');
                    }
                }, 'json');
            }
        });
        $('#awards-edit button.back').click(function() {
            location.href = '/admin/awards/';
        });
        $('#awards-edit button.remove-user').click(function() {
            var id = $(this).attr('data-id');
            if (confirm("确定要去掉此人么？")) {
                $.post('/admin/awards/delUser/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        $('#awards-edit .J-have-user-' + id).remove();
                    }
                    else if (resp.status == 400) {
                        alert('删除失败')
                    }
                }, 'json');
            }
        });
        $('#awards-edit button.search').click(function() {
            var data = {
                type : $('#awards-edit [name="type"]').val(),
                name : $('#awards-edit [name="name"]').val(),
                id : $('#awards-edit [name="id"]').val()
            };
            $.post('/admin/awards/ajaxLoadUsers/',{data: data}, function(resp) {
                $('#awards-edit .J-all-user-show').html(_.template($('#tpl-user-li').html(), {data: resp.data}));
            }, 'json');
        });
        $('#awards-edit').on('click', '.J-adduser-select', function() {
            var data={
                id:$(this).attr('data-id'),
                name:$(this).text()
            };
            var html = '<li class="J-'+data.id+' clearfix" data-id="'+data.id+'">'+data.name;
            html+='<button class="btn btn-link pull-right" data-id="'+data.id+'"><i class="icon-trash"></i></button></li>';
            $(this).children('i').addClass('icon-ok');
            $(this).children('i').removeClass('icon-arrow-right');
            $(this).removeClass('J-adduser-select');
            $(this).addClass('J-adduser-cancel');
            $('#awards-edit .J-selected-user-show').append(html);
            var nums = getLiNums('#awards-edit .J-selected-user-show');
            $('#awards-edit .J-selected-user-num').text(nums);
        });
        $('#awards-edit').on('click', '.J-adduser-cancel', function() {
            var id = $(this).attr('data-id');
            $(this).children('i').removeClass('icon-ok');
            $(this).children('i').addClass('icon-arrow-right');
            $(this).addClass('J-adduser-select');
            $(this).removeClass('J-adduser-cancel');
            $('#awards-edit .J-selected-user-show .J-'+id).remove();
            var nums = getLiNums('#awards-edit .J-selected-user-show');
            $('#awards-edit .J-selected-user-num').text(nums);
        });
        $('#awards-edit .J-selected-user-show').on('click', 'button', function() {
            var id = $(this).attr('data-id');
            $('#awards-edit .J-all-user-show .J-'+id).children('i').removeClass('icon-ok');
            $('#awards-edit .J-all-user-show .J-'+id).children('i').addClass('icon-arrow-right');
            $('#awards-edit .J-all-user-show .J-'+id).addClass('J-adduser-select');
            $('#awards-edit .J-all-user-show .J-'+id).removeClass('J-adduser-cancel');
            $('#awards-edit .J-selected-user-show .J-'+id).remove();
            var nums = getLiNums('#awards-edit .J-selected-user-show');
            $('#awards-edit .J-selected-user-num').text(nums);
        });
        $('#awards-edit button.save').click(function() {
            var data = {
                'id': $('#awards-edit [name="id"]').val(),
                'title': $('#awards-edit [name="title"]').val(),
                'content': $('textarea[name="content"]').val(),
                'users': (function() {
                    var u = [];
                    $('#awards-edit .J-selected-user-show li').each(function() {
                        u.push({
                            user_id: $(this).attr('data-id'),
                            user_name: $(this).text()
                        });
                    });
                    return u;
                })()
            };
            $.post('/admin/awards/save/', {data: data}, function(resp) {
                if(resp.status==200){
                    location.href = '/admin/awards/edit/?id='+data.id;
                }else if(resp.status==400){
                    alert('保存失败')
                }
            });
        });
        $('#awards-edit button.perform').click(function() {
            var id = $('#awards-edit [name="id"]').val();
            if(confirm('确定要发布么？发布后所有已发布的数据将不可修改')){
                $.post('/admin/awards/perform/', {id: id}, function(resp) {
                    if(resp.status==200){
                        location.href = '/admin/awards/edit/?id='+id;
                    }else if(resp.status==400){
                        alert('执行失败')
                    }
                });
            }
        });
        /* awards    end
        --------------------------*/
       /* gift   start
        --------------------------*/
       
       $('#gift-list button.del').click(function() {
            var id = $(this).attr('data-id');
            if (confirm("确定要删除这个活动？")) {
                $.post('/admin/gift/del/', {id: id}, function(resp){
                    if (resp.status == 200) {
                        location.href = '/admin/gift/';
                    }
                    else if (resp.status == 400) {
                        alert('删除失败');
                    }
                }, 'json');
            }
        });
       
       $('#gift-list button.perform, #gift-edit button.perform').click(function() {
            var data = {
                id : $(this).attr('data-id'),
                num : $(this).attr('data-num')
            }
            if (confirm("执行后，活动的券码数量及兑换时间等信息将不能修改。\n系统会自动生成"+data.num+"个券码，确定要执行吗？")) {
                $.post('/admin/gift/perform/', {data: data}, function(resp){
                    if (resp.status == 200) {
                        location.href = '/admin/gift/';
                    }
                    else if (resp.status == 400) {
                        alert('删除失败');
                    }
                }, 'json');
            }
        });
       
        if ($('#gift-edit button.upload').length > 0) {
            K.singleupload('button[name="upload"]', '.pic-preview img');
        };

        if ($('#gift-edit').length > 0) {
            K.create('textarea[name="details"]');
        };
        
        $('#gift-edit button.save').click(function() {
            K.get('textarea[name="details"]').sync();
            var data = {
                'id': $('[name="id"]').val(),
                'logo': $('.pic-preview img').attr('data-name'),
                'title': $.trim($('[name="title"]').val()),
                'gift': $.trim($('[name="gift"]').val()),
                'num': $.trim($('[name="num"]').val()),
                'start_time': $.trim($('[name="start_time"]').val()),
                'end_time': $.trim($('[name="end_time"]').val()),
                'short_desc': $('textarea[name="short_desc"]').val(),
                'details': $('textarea[name="details"]').val()
            };
            if (data.gift.length == 0) {
                alert('亲，礼品描述还没有填写呢！');
                return false;
            }
            if (data.num.length == 0) {
                alert('亲，礼券数量还没有填写呢！');
                return false;
            } else {
                if (!isPInt(data.num)) {
                    alert('亲，礼券数量格式有误(需正整数)');
                    return false;
                }
            }
            if (data.start_time.length == 0) {
                alert('亲，开始时间还没有填写呢！');
                return false;
            } else {
                if (!DateCheck(data.start_time)) {
                    alert('亲，开始时间格式有误，请检查一下！');
                    return false;
                }
            }
            if (data.end_time.length == 0) {
                alert('亲，结束时间还没有填写呢！');
                return false;
            } else {
                if (!DateCheck(data.end_time)) {
                    alert('亲，结束时间格式有误，请检查一下！');
                    return false;
                }
            }
            if (data.end_time < data.start_time) {
                alert('亲，开始时间>结束时间了，请检查一下！');
                return false;
            }

            $.post('/admin/gift/save/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/admin/gift/';
                }else if(resp.status == 400) {
                    alert('保存失败！');
                }
            });
        });
        
        $('#gift-edit button.back').click(function() {
            location.href = '/admin/gift/';
        });
        
        $('#gift-see button.back').click(function() {
            location.href = '/admin/gift/';
        });
        
        $('#gift-delivery-edit button.back').click(function() {
            location.href = '/admin/gift/delivery/';
        });
        
        $('#gift-delivery-list button.edit-remark').click(function() {
            var id = $(this).attr('data-id');
            $('#gift-delivery-list #remarkModal').modal({show: true, keyboard: false, backdrop: 'static'});
            $.get('/admin/gift/deliveryAjax/?id=' + (id || 0), function(resp) {
                $('#gift-delivery-list #remarkModal .modal-body').html(_.template($('#tpl-remark').html(), {data: resp.data}));
            }, 'json');
        });
        
        $('#gift-delivery-list #remarkModal button.save').click(function() {
            var data = {
                id:$('#gift-delivery-list #remarkModal [name="id"]').val(),
                remark:$('#gift-delivery-list #remarkModal [name="remark"]').val()
            };
            $.post('/admin/gift/editDeliveryRemark/', {data: data}, function(resp) {
                if(resp.status==200){
                    location.href = '/admin/gift/delivery/';
                }else if(resp.status==400){
                    alert('保存失败')
                }
            });
        });
        
        $('#gift-delivery-list [name="checkall"]').click(function() {
            SelectAll("delivery[]");
        });
        
        $('#gift-delivery-list button.ship').click(function() {
            var data = {
                'ids': (function() {
                    var p = [];
                    $('#gift-delivery-list [name="delivery[]"]:checked').each(function() {
                        p.push($(this).val());
                    });
                    return p;
                })()
            };
            if(data.ids.length > 0 ){
                var message = '确认将选中的单子状态改为已配送?';
                if(confirm(message)){
                    $.post('/admin/gift/allDelivery/', {data: data}, function(resp) {
                        if (resp.status == 200) {
                            location.href = '/admin/gift/delivery/';
                        }else if(resp.status == 400) {
                            alert('操作失败！！！');
                            location.href = '/admin/gift/delivery/';
                        }
                    });
                }
            }else{
                alert('还没选择要发货的单子呢');
            }
        });
        
        $('#gift-delivery-edit button.save').click(function() {
            var data = {
                'id': $('[name="id"]').val(),
                'remark': $('[name="remark"]').val(),
                'consignee': $('[name="consignee"]').val(),
                'mobile': $('[name="mobile"]').val(),
                'province': $('[name="province"]').val(),
                'city': $('[name="city"]').val(),
                'district': $('[name="district"]').val(),
                'address': $('[name="address"]').val()
            };
            $.post('/admin/gift/deliverySave/', {data: data}, function(resp) {
                if (resp.status == 200) {
                    location.href = '/admin/gift/delivery/';
                }else if(resp.status == 400) {
                    alert('保存失败！');
                }
            });
        });
       /* gift    end
        --------------------------*/
        
        
		if ($('#area').length > 0) {
		    var data_province = $('#area').attr('province');        
		    var data_city = $('#area').attr('city');
		    var data_district = $('#area').attr('district');
		    require(['admin/area'], function(area) {
		        area.create('#area',data_province,data_city,data_district);
		    });
		}
        if ($('#greeting #wechat').length > 0) {
            require(['admin/matter'], function(wechat) {
                wechat.create('#wechat');
                $('#greeting button.save').click(function() {
                    wechat.save(function(type, data) {
                        var param = {
                            id: $('#greeting #wechat').attr('data-id'),
                            key: type + '-' + data.id
                        }
                        $.post('/admin/greeting/save/', {data: param}, function(resp) {
                            alert(resp.msg);
                            if (resp.status == 200) {
                                location.href = '/admin/greeting/';
                            }
                        });
                    });
                });
            });
        }
        $.get('/admin/gift/ajaxRquest/', function(resp) {
            if(resp.status==200){
                $('.remind').show();
            }
        }, 'json');
        
        $('#export [name="type"]').change(function(){
            checkExportType();
        });
        
        function checkExportType(){
            var type = $('#export [name="type"]').val();
            if(type=="order"){
                $('#export [name="date"]').hide();
                $('#export [name="start"]').show();
                $('#export [name="end"]').show();
            }else{
                $('#export [name="date"]').show();
                $('#export [name="start"]').hide();
                $('#export [name="end"]').hide();
            }
        }
        function isPInt(str) {
            var g = /^[1-9]*[1-9][0-9]*$/;
            return g.test(str);
        }
        function getLiNums(ul) {
            var num = $(ul+' li').length;
            return num;
        } 
        /*** 复选框全选反选 ***/
        function SelectAll(name) {
            var checkboxs=document.getElementsByName(name);
            for (var i=0;i<checkboxs.length;i++) {
                var e=checkboxs[i];
                e.checked=!e.checked;
            }
        }
        /*** 检测日期类型 ***/
        function DateCheck(date) {
            var result = date.match(/^(\d{1,4})(-|\/)(\d{1,2})\2(\d{1,2})$/);
            if (result == null) {
                return false;
            }
            var d = new Date(result[1], result[3] - 1, result[4]);
            return (d.getFullYear() == result[1] && (d.getMonth() + 1) == result[3] && d.getDate() == result[4]);
        }
    });
});
