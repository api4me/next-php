<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Order.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Order extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $out = array();
        // Default
        $param = array(
            'status' => 0,
            'sn' => null,
            'user' => null,
            'pay_method' => null,
        	'start_date' => null,
        	'end_date' => null,
        );
        $post = $this->app->request->post();
        $export = '';
        if ($post) {
            $param['status'] = $post['status'];
            $param['pay_method'] = $post['pay_method'];
            $param['sn'] = $post['sn'];
            $param['user'] = $post['user'];
            $param['start_date'] = $post['start_date'];
            $param['end_date'] = $post['end_date'];
            $this->app->session->set('order_search', $param);
            $this->app->session->set('order_page', 1);
            $out['count_show']=1;
            if (isset($post['export'])) {
            	switch ($post['export']){
            		case 'order':
            			$this->exportOrder($param);
            			break;
            		case 'refund':
            			$this->exportRefund($param);
            			break;
            	}
            }
        } else {
            if ($tmp = $this->app->session->get('order_search')) {
                $param = $tmp;
            }
        }
        $out['search'] = $param;
        $out['option']['status'] = array(
            '0' => '--订单状态--',
            'prepay' => '待付款',
            'paid' => '已付款',
            'refund' => '退款中',
        	'finish' => '已退款',
            'cancel' => '已作废',
            'history' => '三个月前',
        );
        $out['option']['pay_method'] = array(
            '0' => '--付款方式--',
            '1' => '微信支付',
            '2' => '货到付款',
        );
        $user_type = array(
        	"-1"=> '其他',
			"0" => '关注好友',
			"1" => '体验好友',
			"2" => '会员',
			"3" => '创始会员',
        );
        $model = new \app\model\Order($this->app);
        $model_user = new \app\model\User($this->app);
        $page = 1;
        $getpage = $this->app->request->get('page');
        if ($getpage > 0) {
        	$this->app->session->set('order_page', $getpage);
        	$page = $getpage;
        } else{
        	$getpage = $this->app->session->get('order_page');
        	if ($getpage>0) {
        		$page = $getpage;
        	}
        }
        $pagination = $this->app->config('pagination');
        $start = ($page-1)*$pagination['per_page'];
        $out['data'] = $model->loadAll($param,$start,$pagination['per_page']);
        if (isset($out['data']['order'])) {
        	$user_ids = array();
	        foreach ($out['data']['order'] as $_order){
	        	$user_ids[] = $_order['user_id'];
	        }
	        $users = $model_user->loadUsersType($user_ids);
	        foreach ($out['data']['order'] as $key=>$_order){
	        	$out['data']['order'][$key]['user_type'] = $user_type[$users[$_order['user_id']]];
	        }
        }
        $config = array(
        	'total' => $out['data']['count'],
        	'url' => '/admin/order/index/?page=',
        	'page' => $page,
        	'per_page' => $pagination['per_page'],
        );
        // Generate pagination
        $pagination = new \Next\Helper\Pagination($config);
        $out['pagination'] = $pagination->get_links();
        $this->display('admin/order_index.html', $out);
    }
/*}}}*/
/*{{{ see */
    public function see() {
        $model = new \app\model\Order($this->app);
        $status = array(
            'prepay' => '待付款',
            'paid' => '已付款',
            'refund' => '退款中',
            'cancel' => '已作废',
            'finish' => '已退款',
        );
        $out = array();
        $id = $this->app->request->get('id');
        if ($id) {
            $out['data'] = $model->loadOrder($id);
        }
        $out['data']['status'] = $status[$out['data']['status']];
        $out['data']['refund_info'] = json_decode($out['data']['refund_info'],TRUE);
        $out['data']['refund_history'] = json_decode($out['data']['refund_history'],TRUE);
        $this->display('admin/order_see.html', $out);
    }
/*}}}*/
/*{{{ receive */
    public function receive() {
        $id = $this->app->request->get('id');
        if (!$id) {
            $this->app->redirect('/admin/order/');
            return false;
        }

        $model = new \app\model\Order();
        if (!$tmp = $model->loadOrder($id)) {
            $this->app->redirect('/admin/order/');
            return false;
        }
        // Must no pay and cash when delivery
        if ($tmp['status'] != 'prepay' || $tmp['pay_id'] != 2) {
            $this->app->redirect('/admin/order/');
            return false;
        }

        $out = array();
        $out['data'] = $tmp;
        $this->display('admin/order_receive.html', $out);
    }
/*}}}*/
/*{{{ refund */
    public function refund() {
    	$model = new \app\model\Order($this->app);
    	$status = array(
    		'prepay' => '待付款',
            'paid' => '已付款',
            'refund' => '退款中',
            'cancel' => '已作废',
            'finish' => '已退款',
    	);
    	$out = array();
    	$id = $this->app->request->get('id');
    	$out['data'] = $model->loadOrder($id);
    	$out['data']['status'] = $status[$out['data']['status']];
    	$out['data']['refund_info'] = json_decode($out['data']['refund_info'],TRUE);
    	$this->display('admin/order_refund.html', $out);
    }
/*}}}*/
/*{{{ editOrder */
    public function editOrder(){
    	$model = new \app\model\Order($this->app);
    	$post = $this->app->request->post('data');
    	$edit=array(
    		'remark' => $post['remark'],
    		'updated*f' => 'now()',
    	);
    	$where['id'] = $post['id'];
    	if ($model->edit($edit, $where)) {
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ 作废订单 */
    public function cls() {
    	$id = $this->app->request->post('id');
        $model = new \app\model\Order($this->app);
        $model_product = new \app\model\Product($this->app);
        $model_delivery = new \app\model\Delivery($this->app);
        // 取数据
        $order = $model->loadById($id);
        $delivery = $model_delivery->getOrderFirst($order['id']);
        //检测
        $order = $model->loadById($id);
        if ($order['status']!='prepay') {
        	$out['msg'] = '此订单无法取消！';
        	$out['status'] = 400;
        	$this->rendJSON($out);
        }
        if ($order['pay_id']==2&&$delivery['status']!='preship') {
        	$out['msg'] = '您预约的水已经在配送中了，暂时无法取消该订单。';
        	$out['status'] = 400;
        	$this->rendJSON($out);
        }
        $data = array();
        $data['order'] = array(
        	'edit'=>array(
        		'status' => 'cancel',
        		'updated*f' => 'now()',
        	),
        	'where'=>array(
        		'id' => $order['id'],
        		'status' => 'prepay',
        	),
        );
        //检测是否是coupon订单
        if ($order['used_coupon_id'] > 0) {
        	$data['coupon_edit']=array(
        		'edit'=>array(
        			'is_used'=>0,
        			'updated*f'=>'now()',
        		),
        		'where'=>array(
        			'id'=>$order['used_coupon_id'],
        		),
        	);
        }
        if (!empty($delivery)) {
        	$data['delivery'] = array(
	        	'edit'=>array(
	        		'status'=>'cancel',
	        		'result_status' => '0',
	        		'result_comment' => '已被管理员取消',
	        		'updated*f' => 'now()',
	        	),
	        	'where'=>array(
	        		'id'=>$delivery['id'],
	        		'order_id'=>$order['id'],
	        	),
	        );
        }
        //用户编辑
        $order_goods = $model->loadOrdersGoods($id);
        foreach ($order_goods as $_good){
        	$goods[] = $_good['goods_id'];
        }
        $products = $model_product->loadByIds($goods);
        foreach ( $products as $good){
        	if ($good['is_promote']==1) {
        		$data['user']['edit']['used_experience'] = 0;
        	}
        	if ($good['is_gift']==1) {
        		$data['user']['edit']['used_coupon'] = 0;
        	}
        }
        if (isset($data['user']['edit'])) {
        	$data['user']['edit']['updated*f']='now()';
        	$data['user']['where']['id'] = $order['user_id'];
        }
    	if ($model->cancel($data)) {
    		$out['msg'] = '取消成功！';
    		$out['status'] = 200;
    	}else{
    		$out['msg'] = '取消订单失败！';
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ rejectRefund */
    public function rejectRefund(){
    	$model = new \app\model\Order($this->app);
    	$post = $this->app->request->post('data');
    	$order = array(
    		'edit' => array(
    			'status' => 'paid',
    			'updated*f' => 'now()',
		    ),
	    	'where'=>array(
	    		'id'=>$post['id'],
	    	),
    	);
    	if ($model->edit($order['edit'], $order['where'])) {
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ commitRefund */
    public function commitRefund(){
    	$model = new \app\model\Order($this->app);
    	$post = $this->app->request->post('data');
    	$check = $model->checkHaveDelivery($post['id']);
    	if ($check) {
    		$out['status'] = 400;
    		$out['msg']="此订单下还有正在配送或申请配送的水，暂时无法执行退款操作";
    		$this->rendJSON($out);
    	}
    	$datas = array(
    		'order' => array(
    			'edit' => array(
    				'status' => 'finish',
    				'refund_amount' => $post['refund_amount']*100,
    				'updated*f' => 'now()',
    			),
    			'where'=>array(
    				'id'=>$post['id'],
    			),
    		),
    		'user' => array(
    			'edit' => array(
    				'total_water*f' => 'total_water - '.$post['water'],
    				'have_water*f' => 'have_water - '.$post['water'],
    				'updated*f' => 'now()',
    			),
    			'where' => array(
    				'id' => $post['user_id']
    			),
    		),
    	);
    	if ($model->commitRefund($datas)) {
    		$out['status'] = 200;
    	}else{
    		$out['msg']="退款失败";
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ finishRefund */
    public function finishRefund(){
    	$model = new \app\model\Order($this->app);
    	$post = $this->app->request->post('data');
    	$check = $model->checkHaveDelivery($post['id']);
    	if ($check) {
    		$out['status'] = 400;
    		$out['msg']="此订单下还有正在配送或申请配送的水，暂时无法执行退款操作";
    		$this->rendJSON($out);
    	}
    	$history = array(
    		'amount' => $post['refund_amount'],
    		'history_amount' => $post['refund_history_amount'],
    		'edit_time' => date('Y-m-d H:i:s'),
    	);
    	$history = json_encode($history);
    	$datas = array(
    		'order' => array(
    			'edit' => array(
    				'refund_history' => $history,
    				'refund_amount' => $post['refund_amount']*100,
    				'updated*f' => 'now()',
    			),
    			'where'=>array(
    				'id'=>$post['id'],
    			),
    		),
    	);
    	if ($model->commitRefund($datas)) {
    		$out['status'] = 200;
    	}else{
    		$out['msg']="退款失败";
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ receiveSave */
    public function receiveSave() {
        $tmp = $this->app->request->params('data');

        $data = array(
            'total_fee' => intval($tmp['money']) * 100,
            'pay_time' => $tmp['date'],
            'pay_remark' => $tmp['remark'],
            'updated*f' => 'now()',
        );
        $where = array(
            'id' => $tmp['id'],
            'status' => 'prepay',
            'pay_id' => 2,
        );

        $out = array();
        $model = new \app\model\Order();
        if ($model->edit($data, $where)) {
            $out['status'] = 200;
            $out['msg'] = '保存成功';
            $this->rendJSON($out);
        }

        $out['status'] = 400;
        $out['msg'] = '保存失败';
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ receiveFinish */
    public function receiveFinish() {
        $tmp = $this->app->request->params('data');

        // Pay success flow
        // ----------------------------
        $out = array();
    	$model = new \app\model\Order();
    	if (!$order = $model->loadById($tmp['id'])) {
            $out['status'] = 400;
            $out['msg'] = '查无此订单，请返回列表查询';
            $this->rendJSON($out);
        }
        if ($order['status']!='prepay') {
        	$out['status'] = 400;
        	$out['msg'] = '订单信息有误，请返回列表查询';
        	$this->rendJSON($out);
        }
        //检测配送是否已送达或已签收
        $model_delivery = new \app\model\Delivery($this->app);
        $delivery = $model_delivery->getOrderFirst($tmp['id']);
        if(!$delivery){
        	$out['status'] = 400;
        	$out['msg'] = '订单信息有误，请返回列表查询';
        	$this->rendJSON($out);
        }
        if ($delivery['need_pay']!=1 || $delivery['need_pay']<=0 ) {
        	$out['status'] = 400;
        	$out['msg'] = '订单信息有误，请返回列表查询';
        	$this->rendJSON($out);
        }
        if (!in_array($delivery['status'], array('sign','finish'))) {
        	$out['status'] = 400;
        	$out['msg'] = '货物还木有送达呢，不可以结账哦！';
        	$this->rendJSON($out);
        }
        $data = array();
        // 1. Set order
    	$data['order'] = array(
    		'edit' => array(
    			'status' => 'paid',
                'total_fee' => intval($tmp['money']) * 100,
                'pay_time' => $tmp['date'],
                'pay_remark' => $tmp['remark'],
                'updated*f' => 'now()',
    		),
    		'where' => array(
                'id' => $tmp['id'],
    			'status' => 'prepay',
                'pay_id' => 2,
    		),
    	);
        // 2. Set user water
    	$data['user'] = array(
    		'edit' => array(
    			'total_water*f' => 'total_water+' . $order['goods_num'],
    			'have_water*f' => 'have_water+' . $order['goods_residue'],
    			'updated*f' => 'now()',
    		),
    		'where' => array(
    			'id' => $order['user_id'],
    		),
    	);

    	// 3. Send integral
    	$modelUser = new \app\model\User();
    	$user = $modelUser->loadById($order['user_id']);
        if ($user['farther_id'] && $order['integral'] > 0) {
        	$father = $modelUser->loadById($user['farther_id']);
        	if ($father['type'] > 1) {
        		$data['integral_user'] = array (
        			'edit' => array (
        				'integral*f' => 'integral+' . $order['integral'],
        				'updated*f' => 'now()',
        			),
        			'where' => array (
        				'id' => $user['farther_id'],
        			),
        		);
        		$data['integral'] = array (
        			'user_id' => $user['farther_id'],
        			'type' => 3, // integral from child user
        			'num' => $order['integral'],
        			'remark' => sprintf('您的好友 %s 订水回赠', $user['name']),
        			'gift_by' => $user['id'],
        			'created*f' => 'now()',
        		);
        	}
        }
        // 4. Change user type
        if ($user['type'] < 2) {
            // Coupon number is exists or not is equal water type
            if ($order['is_normal']) {
                $data['user']['edit']['type'] = 2;
            } else  {
                $data['user']['edit']['type'] = 1;
            }
        }
        if ($order['coupon']) {
            // 5. Send coupon
            $data['user']['edit']['coupon*f'] = 'coupon+' . $order['coupon'];
            for ($i = 0; $i < $order['coupon']; $i++) {
                $data['coupon'][$i] = array(
                    'serial' => $this->app->common->genRandomString(12),
                    'from_user' => $order['user_id'],
                	'validity_time*f'=>'now()+interval 365 day',
                    'created*f' => 'now()',
                    'updated*f' => 'now()',
                );
            }

            // 6. Add coupon log
        	$data['coupon_log'] = array(
        		'user_id' => $order['user_id'],
    			'num' => $order['coupon'],
    			'info' => '购水获得',
    			'validity_time*f' => 'now()+interval 365 day',
    			'created*f' => 'now()',
        	);
        }
        // 7. Edit delivery
        $data['delivery']=array(
        	'edit'=>array(
        		'need_pay'=>0,
        		'updated*f' => 'now()',
        	),
        	'where'=>array(
        		'id'=>$delivery['id'],
        		'order_id'=>$order['id'],
        	)
        );
        // ----------------------------
        $this->app->log->debug(json_encode($data));
        if (!$model->paySuccess($data)) {
            $out['status'] = 400;
            $out['msg'] = '结算失败';
            $this->rendJSON($out);
        }

        $out['status'] = 200;
        $out['msg'] = '结算成功';
        $this->rendJSON($out);
    }
/*}}}*/
    private function exportOrder($param){
    	$model = new \app\model\Order($this->app);
    	$model_export = new \app\model\Export($this->app);
    	if (empty($param['start_date'])) {
    		$param['start_date'] = date('Y-m-01');
    	}
    	if (empty($param['end_date'])) {
    		$param['end_date'] = date('Y-m-d');
    	}
    	$orders = $model->exportOrder($param);
    	$first_line = array('开始时间',$param['start_date'],'结束时间',$param['end_date']);
        $export = $this->app->config('export');
    	$filename = $export['path'].'export-order-'.date("Y-m-d").'.xlsx';
    	$status = array(
    			'prepay' => '待付款',
    			'paid' => '已付款',
    			'refund' => '退款中',
    			'finish' => '已退款',
    			'cancel' => '已作废',
    			'history' => '三个月前',
    	);
    	$header = array('序号','订单号','下单人/ID','下单时间','订单总额','实际付款','使用积分','支付方式','订单状态','订单水量');
    	$datas = array();
    	foreach ($orders as $key=>$order){
    		$datas[$key][] = $key+1;//序号
    		$datas[$key][] = '`'.$order['sn'];//单号
    		$datas[$key][] = $order['user_name'].'/'.str_pad($order['user_id'],7,'0',STR_PAD_LEFT);//下单人/ID
    		$datas[$key][] = date('Y-m-d',strtotime($order['created']));//下单时间
    		$datas[$key][] = $order['need_pay']/100+$order['use_integral'];//订单总额
    		$datas[$key][] = $order['total_fee']/100;//实际付款
    		$datas[$key][] = $order['use_integral'];//使用积分
    		$datas[$key][] = $order['pay_name'];//支付方式
    		$datas[$key][] = $status[$order['status']];//配订单状态
    		$datas[$key][] = $order['goods_num'];//订单水量
    	}
    	$makefile = $model_export->doExcelArr($datas,$header,$filename,$first_line);
    	if ($makefile) {
    		return $makefile;
    	}else{
    		return false;
    	}
    }
    //TODO 订单导出
    private function exportRefund($param){
    	$model = new \app\model\Order($this->app);
    	$model_export = new \app\model\Export($this->app);
    	if (empty($param['start_date'])) {
    		$param['start_date'] = date('Y-m-01');
    	}
    	if (empty($param['end_date'])) {
    		$param['end_date'] = date('Y-m-d');
    	}
    	$orders = $model->exportRefund($param);
    	$first_line = array('开始时间',$param['start_date'],'结束时间',$param['end_date']);
        $export = $this->app->config('export');
    	$filename = $export['path'].'export-refund-'.date("Y-m-d").'.xlsx';
    	$header = array('序号','订单号','下单人/ID','下单时间','订单总额','实际付款','使用积分','支付方式','订单水量','退款原因',
    			'联系人','联系电话','持卡人姓名','卡号','开户行','发票编号','应退金额','实际退款','备注');
    	$datas = array();
    	foreach ($orders as $key=>$order){
    		$datas[$key][] = $key+1;//序号
    		$datas[$key][] = '`'.$order['sn'];//单号
    		$datas[$key][] = $order['user_name'].'/'.str_pad($order['user_id'],7,'0',STR_PAD_LEFT);//下单人/ID
    		$datas[$key][] = date('Y-m-d',strtotime($order['created']));//下单时间
    		$money = $order['need_pay']/100+$order['use_integral'];//订单总价
    		$datas[$key][] = $money;//订单总额
    		$datas[$key][] = $order['total_fee']/100;//实际付款
    		$datas[$key][] = $order['use_integral'];//使用积分
    		$datas[$key][] = $order['pay_name'];//支付方式
    		$datas[$key][] = $order['goods_num'];//订单水量
    		$datas[$key][] = $order['refund_reason'];//退款原因
    		$refund = json_decode($order['refund_info'],TRUE);
    		$datas[$key][] = $refund['name'];//联系人
    		$datas[$key][] = $refund['mobile'];//联系电话
    		$datas[$key][] = $refund['card_name'];//持卡人姓名
    		$datas[$key][] = $refund['card_num'];//卡号
    		$datas[$key][] = $refund['card_bank'];//开户行
    		$datas[$key][] = $order['refund_invoice'];//发票编号
    		$used = $order['goods_num'] - $order['goods_residue'];//使用的水量
    		$price = $money / $order['goods_num'];//每箱水的价格
    		$need_refund = $order['total_fee']/100 - $used*$price;
    		$datas[$key][] = $need_refund;//应退金额
    		$datas[$key][] = $order['refund_amount'];//实际退款
    		$datas[$key][] = $order['remark'];//备注
    	}
    	$makefile = $model_export->doExcelArr($datas,$header,$filename,$first_line);
    	if ($makefile) {
    		return $makefile;
    	}else{
    		return false;
    	}
    }
}
