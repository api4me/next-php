<?php
namespace app\control\shop;

class Order extends \Next\Core\Control {
    private $user;

    public function __construct() {
        parent::__construct();
        $this->user = $this->app->session->get('user');
    }

/*{{{ index */
/** page 订单列表 **/
    public function index() {
        $out = array();
        $model = new \app\model\Order($this->app);
        $hide = $this->app->request->get('hide');
        if ($hide) {
        	$out['hide']=1;
        	$out['data'] = $model->loadByUserIdHide($this->user['id'],1);
        }else{
        	$out['data'] = $model->loadByUserIdHide($this->user['id'],0);
        }
        $this->display('site/order.html', $out);
    }
/*}}}*/
/*{{{ check */
/** page 查看订单 **/
    public function check() {
    	$out = array();
    	$model = new \app\model\Order($this->app);
    	$model_area = new \app\model\Area($this->app);
    	$model_delivery = new \app\model\Delivery($this->app);
    	$id = $this->app->request->get('id');
    	$out['order'] = $model->loadById($id);
    	$out['delivery'] = $model_delivery->getOrderFirst($id);
    	$out['edit'] = $this->app->request->get('edit');
    	$order_status = array(
    		'prepay' => '待付款',
    		'paid' => '已付款',
    		'refund' => '退款中',
    		'finish' => '已退款',
    		'cancel' => '已作废',
    	);
    	$delivery_status = array(
    		'prepay' => '待配送',
    		'preship' => '待配送',
    		'shipped' => '配送中',
    		'sign' => '已送达',
    		'finish' => '已完成',
    		'cancel' => '已作废',
    	);
    	$out['order']['status'] = $order_status[$out['order']['status']];
    	if (!empty($out['delivery'])) {
    		$out['delivery']['status'] = $delivery_status[$out['delivery']['status']];
    		$out['delivery']['to_addr_str'] = $model_area->loadAll($out['delivery']['to_addr']);
    		$out['province'] = substr($out['delivery']['to_addr'], 0,2);
    		$out['city'] = substr($out['delivery']['to_addr'], 2,2);
    		$out['district'] = substr($out['delivery']['to_addr'], 4,2);
    	}
    	$model_shiptime = new \app\model\Shiptime($this->app);
    	$out['except_date'] = $model_shiptime->loadMonth();
    	//配送日期
    	$now = time();
    	if (date('H:i:s', $now) > '16:00:00') {
    		$out['start_time'] = strtotime('+2 day', $now);
    		$out['end_time'] = strtotime('+32 day', $now);
    	} else {
    		$out['start_time'] = strtotime('+1 day', $now);
    		$out['end_time'] = strtotime('+31 day', $now);
    	}
    	$this->display('site/order_check.html', $out);
    }
/*}}}*/
/*{{{ delivery */
/** action 编辑配送 **/
    public function delivery(){
    	$model = new \app\model\Delivery($this->app);
    	$model_shiptime = new \app\model\Shiptime($this->app);
    	//
    	$post = $this->app->request->post('data');
    	$to_addr = $post['province'].$post['city'].$post['district'];
    	$delivery = $model->loadDelivery($post['id']);
    	$delivery_good = $model->loadDeliveryGoods($post['id']);
    	$except_date = $model_shiptime->loadMonth();
    	//数据监测
    	if (empty($delivery)) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	if (!in_array($delivery['status'], array('preship','prepay'))) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	if ($post['order']!=$delivery['order_id']) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	if (count($delivery_good)<1) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//配送日期验证
    	if (in_array($post['shipping_time'], $except_date)) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	$data['delivery']=array(
    		'edit'=>array(
    			'to_addr'=>$to_addr,
    			'consignee'=>$post['consignee'],
    			'mobile'=>$post['mobile'],
    			'address'=>$post['address'],
    			'num'=>$post['num'],
    			'shipping_time'=>$post['shipping_time'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$post['id'],
    		),
    	);
    	$data['delivery_goods'][]=array(
    		'edit'=>array(
    			'num'=>$post['num'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$delivery_good[0]['id'],
    		),
    	);
    	//订单剩余数量修改
    	$data['order'][]=array(
    		'edit'=>array(
    			'goods_residue*f'=>'goods_num-'.$post['num'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$post['order'],
    		),
    	);
    	//订单详情剩余数量修改
    	$data['order_goods'][]=array(
    		'edit'=>array(
    			'goods_residue*f'=>'box_num-'.$post['num'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'order_id'=>$delivery_good[0]['order_id'],
    			'id'=>$delivery_good[0]['order_goods_id'],
    		),
    	);
    	if ($model->edit($data)) {
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ add */
/** action 添加订单 **/
    public function add() {
        $post = $this->app->request->post('data');
        $model_user = new \app\model\User($this->app);
        $model_product = new \app\model\Product($this->app);
        /*********** step 1 获取用户、商品、配送地址的信息 *************/
        $user = $model_user->loadById($this->user['id']);
    	$good = $model_product->loadById($post['good']);
    	$address = $model_user->loadAddressByID($post['address']);
    	$model_shiptime = new \app\model\Shiptime($this->app);
    	$except_date = $model_shiptime->loadMonth();
        /*********** step 2 检测提交的信息 *************/
    	//收货地址验证。
    	if (empty($address)) {
    		$out['msg'] = '您的收货地址有误！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//商品验证。
    	if (empty($good)) {
    		$out['msg'] = '请选择要购买的商品！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//体验装购买资格验证。
    	if ( $good['is_promote']==1 && $user['used_experience']==1 ) {
    		$out['msg'] = '此商品仅能购买一次，您已购买过此商品！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//积分验证。
    	if ($post['integral'] > $user['integral']) {
    		$out['msg'] = '您的积分不足！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	if ( $post['integral'] > $good['price']/100 ) {
    		$out['msg'] = '您的积分可兑换的金额超出套票价格了！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//配送数量验证。
    	if ($post['num'] > $good['box_num']) {
    		$out['msg'] = '请查看您的预约水量！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//配送日期验证
    	if (in_array($post['date'], $except_date)) {
    		$out['msg'] = '你预约的定水日期有误！';
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//监测商品是否是赠品
    	if($good['is_gift']==1){
    		$model_coupon = new \app\model\Coupon();
    		$coupon = $model_coupon->checkIsReceive($this->user['id']);
    		$post['coupon'] = $coupon['id'];
    	}
        /************ step 3 根据使用积分判断是否需要付款 ************/
    	$integral_money = $post['integral'] * 100;
    	if ($integral_money == $good['price']) {
    		$post['type'] = 'free';
    	}
        /********** step 4 根据支付type选择订单生成类型  **************/
        switch ($post['type']){
        	case 'wx':
        		$result = $this->wxPay($post,$user,$good,$address);
        		break;
        	case 'df':;
        		$result = $this->deliveryPay($post,$user,$good,$address);
        		break;
        	case 'free':
        		$result = $this->freePay($post,$user,$good,$address);
        		break;
        }
        if ($result) {
        	if ($post['type']=="free") {
        		$out['status'] = 300;
        		$this->rendJSON($out);
        	}elseif($post['type']=="df"){
        		$out['status'] = 100;
        		$out['id'] = $result;
        		$this->rendJSON($out);
        	}else{
        		$out['status'] = 200;
        		$out['id'] = $result;
        		$this->rendJSON($out);
        	}
        }else{
        	$out['status'] = 400;
        	$out['msg'] = '提交订单失败';
        	$this->rendJSON($out);
        }
    }
/*}}}*/
/*{{{ wxPay */
    /**
     * 微信支付新增订单
     * @param unknown $post
     * @param unknown $user
     * @param unknown $good
     * @param unknown $address
     * @return Ambigous <boolean, string>
     */
    private function wxPay($post,$user,$good,$address){
    	// step 1 订单信息
    	$time = explode (' ', microtime());
    	$order_sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['order'] = array(
    		'sn'=>$order_sn,
    		'user_id'=>$user['id'],
    		'user_name'=>$user['name'],
    		'status'=>'prepay',
    		'pay_id'=>'1',
    		'pay_name'=>'微信支付',
    		'need_pay'=>$good['price']-$post['integral']*100,
    		'invoice'=>$post['invoice'],
    		'message'=>$post['msg'],
    		'use_integral'=>$post['integral'],
    		'integral'=>$good['integral'],
    		'coupon'=>$good['coupon'],
    		'is_normal'=>($good['is_gift'] || $good['is_promote'])? 0: 1,
    		'goods_num'=>$good['box_num'],
    		'goods_residue'=>$good['box_num'] - $post['num'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	//step 2 订单详情
    	$data['order_goods'][]=array(
    		'goods_id'=>$good['id'],
    		'goods_name'=>$good['name'],
    		'unin_price'=>ceil($good['price']/$good['box_num']),
    		'goods_num'=>'1',
    		'box_num'=>$good['box_num'],
    		'goods_residue'=>$good['box_num'] - $post['num'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	//step 3 配送单
    	$time = explode (' ', microtime());
    	$delivery_sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['delivery'] = array(
    		'sn'=>$delivery_sn,
			'user_id'=>$user['id'],
			'status' => 'prepay',
			'consignee'=>$address['consignee'],
			'to_addr'=>$address['area'],
			'address'=>$address['address'],
			'mobile'=>$address['mobile'],
			'shipping_time'=>$post['date'],
			'num' => $post['num'],
    		'order_sn'=>$order_sn,
			'created*f' => 'now()',
    		'updated*f'=>'now()',
    	);
    	//step 4 配送单详情
    	$data['delivery_goods']=array(
    		'num'=>$post['num'],
    		'created*f' => 'now()',
    		'updated*f'=>'now()',
    	);
    	//step 5 用户修改
    	$data['user']=array(
    		'edit'=>array(
    			'used_experience'=>$good['is_promote']==1?1:0,
    			'integral*f'=>'integral-'.$post['integral'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$user['id'],
    		),
    	);
    	if (isset($post['coupon'])) {
    		if ($post['coupon'] > 0) {
    			$model_coupon = new \app\model\Coupon();
    			$coupon = $model_coupon->loadCoupon($post['coupon']);
    			if (empty($coupon)) {
    				return false;
    			}
    			if ($coupon['is_used']==1) {
    				return false;
    			}
				$data['user']['edit']['used_coupon']=1;
				$data['order']['used_coupon_id']=$post['coupon'];
				//coupon修改
				$data['coupon_edit']=array(
					'edit'=>array(
						'is_used'=>1,
						'updated*f'=>'now()',
					),
					'where'=>array(
						'id'=>$post['coupon'],
						'to_user'=>$user['id'],
					),
				);
    		}
    	}
    	// step 6 积分明细
    	if ($post['integral'] > 0) {
    		$data['integral']=array(
    			'type'=>2,
    			'user_id'=>$this->user['id'],
    			'num'=>0-$post['integral'],
    			'remark'=>'购买商品 '.$good['name'].' 使用',
    			'gift_by'=>$user['id'],
    			'created*f'=>'now()',
    			'updated*f'=>'now()',
    		);
    	}
    	//step 7  数据录入数据库
    	$model = new \app\model\Order($this->app);
    	return $model->add($data);
    }
/*}}}*/
/*{{{ deliveryPay */
    /**
     * 货到付款新增订单
     * @param unknown $post
     * @param unknown $user
     * @param unknown $good
     * @param unknown $address
     * @return Ambigous <boolean, string>
     */
    private function deliveryPay($post,$user,$good,$address){
    	// step 1 订单信息
    	$time = explode (' ', microtime());
    	$order_sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['order'] = array(
    		'sn'=>$order_sn,
    		'user_id'=>$user['id'],
    		'user_name'=>$user['name'],
    		'status'=>'prepay',
    		'pay_id'=>'2',
    		'pay_name'=>'货到付款',
    		'need_pay'=>$good['price']-$post['integral']*100,
    		'invoice'=>$post['invoice'],
    		'message'=>$post['msg'],
    		'use_integral'=>$post['integral'],
    		'integral'=>$good['integral'],
    		'coupon'=>$good['coupon'],
    		'is_normal'=>($good['is_gift'] || $good['is_promote'])? 0: 1,
    		'goods_num'=>$good['box_num'],
    		'goods_residue'=>$good['box_num'] - $post['num'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	//step 2 订单详情
    	$data['order_goods'][]=array(
    		'goods_id'=>$good['id'],
    		'goods_name'=>$good['name'],
    		'unin_price'=>ceil($good['price']/$good['box_num']),
    		'goods_num'=>'1',
    		'box_num'=>$good['box_num'],
    		'goods_residue'=>$good['box_num'] - $post['num'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	//step 3 配送单
    	$time = explode (' ', microtime());
    	$delivery_sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['delivery'] = array(
    		'sn'=>$delivery_sn,
    		'user_id'=>$user['id'],
    		'status' => 'preship',
    		'consignee'=>$address['consignee'],
    		'to_addr'=>$address['area'],
    		'address'=>$address['address'],
    		'mobile'=>$address['mobile'],
    		'shipping_time'=>$post['date'],
    		'num' => $post['num'],
    		'order_sn'=>$order_sn,
    		'need_pay'=>'1',
    		'pay_money'=>$good['price']-$post['integral']*100,
    		'created*f' => 'now()',
    		'updated*f'=>'now()',
    	);
    	//step 4 配送单详情
    	$data['delivery_goods']=array(
    		'num'=>$post['num'],
    		'created*f' => 'now()',
    		'updated*f'=>'now()',
    	);
    	//step 5 用户修改
    	$data['user']=array(
    		'edit'=>array(
    			'used_experience'=>$good['is_promote']==1?1:0,
    			'integral*f'=>'integral-'.$post['integral'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$user['id'],
    		),
    	);
    	if (isset($post['coupon'])) {
    		if ($post['coupon'] > 0) {
    			$model_coupon = new \app\model\Coupon();
    			$coupon = $model_coupon->loadCoupon($post['coupon']);
    			if (empty($coupon)) {
    				return false;
    			}
    			if ($coupon['is_used']==1) {
    				return false;
    			}
    			$data['user']['edit']['used_coupon']=1;
    			$data['order']['used_coupon_id']=$post['coupon'];
    			//step 6  coupon修改
    			$data['coupon_edit']=array(
    				'edit'=>array(
    					'is_used'=>1,
    					'updated*f'=>'now()',
    				),
    				'where'=>array(
    					'id'=>$post['coupon'],
    					'to_user'=>$user['id'],
    				),
    			);
    		}
    	}
    	// step 6 积分明细
    	if ($post['integral'] > 0) {
    		$data['integral']=array(
    			'type'=>2,
    			'user_id'=>$this->user['id'],
    			'num'=>0-$post['integral'],
    			'remark'=>'购买商品 '.$good['name'].' 使用',
    			'gift_by'=>$user['id'],
    			'created*f'=>'now()',
    			'updated*f'=>'now()',
    		);
    	}
    	//step 7  数据录入数据库
    	$model = new \app\model\Order($this->app);
    	return $model->add($data);
    }
/*}}}*/
/*{{{ freePay */
    /**
     * 使用积分兑换免付款
     * @param unknown $post
     * @param unknown $user
     * @param unknown $good
     * @param unknown $address
     * @return Ambigous <boolean, string>
     */
    private function freePay($post,$user,$good,$address){
    	// step 1 订单信息
    	$time = explode (' ', microtime());
    	$order_sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['order'] = array(
    		'sn'=>$order_sn,
    		'user_id'=>$user['id'],
    		'user_name'=>$user['name'],
    		'status'=>'paid',
    		'pay_id'=>'1',
    		'pay_name'=>'微信支付',
    		'need_pay'=>'0',
    		'total_fee' => '0',
    		'pay_time' => date('Y-m-d H:i:s'),
    		'invoice'=>$post['invoice'],
    		'message'=>$post['msg'],
    		'use_integral'=>$post['integral'],
    		'integral'=>$good['integral'],
    		'coupon'=>$good['coupon'],
    		'is_normal'=>($good['is_gift'] || $good['is_promote'])? 0: 1,
    		'goods_num'=>$good['box_num'],
    		'goods_residue'=>$good['box_num'] - $post['num'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	//step 2 订单详情
    	$data['order_goods'][]=array(
    		'goods_id'=>$good['id'],
    		'goods_name'=>$good['name'],
    		'unin_price'=>ceil($good['price']/$good['box_num']),
    		'goods_num'=>'1',
    		'box_num'=>$good['box_num'],
    		'goods_residue'=>$good['box_num'] - $post['num'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	//step 3 配送单
    	$time = explode (' ', microtime());
    	$delivery_sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['delivery'] = array(
    		'sn'=>$delivery_sn,
    		'user_id'=>$user['id'],
    		'status' => 'preship',
    		'consignee'=>$address['consignee'],
    		'to_addr'=>$address['area'],
    		'address'=>$address['address'],
    		'mobile'=>$address['mobile'],
    		'shipping_time'=>$post['date'],
    		'num' => $post['num'],
    		'order_sn'=>$order_sn,
    		'created*f' => 'now()',
    		'updated*f'=>'now()',
    	);
    	//step 4 配送单详情
    	$data['delivery_goods']=array(
    		'num'=>$post['num'],
    		'created*f' => 'now()',
    		'updated*f'=>'now()',
    	);
    	//step 5 用户修改
    	$data['user']=array(
    		'edit'=>array(
    			'used_experience'=>$good['is_promote']==1?1:0,
    			'integral*f'=>'integral-'.$post['integral'],
    			'have_water*f'=>'have_water+'.$data['order']['goods_residue'],
    			'total_water*f'=>'total_water+'.$data['order']['goods_num'],
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$user['id'],
    		),
    	);
    	//step 6 用户积分详情修改
    	if ($post['integral'] > 0) {
    		$data['integral']=array(
    			'type'=>2,
    			'user_id'=>$this->user['id'],
    			'num'=>0-$post['integral'],
    			'remark'=>'购买商品 '.$good['name'].' 使用',
    			'gift_by'=>$user['id'],
    			'created*f'=>'now()',
    			'updated*f'=>'now()',
    		);
    	}
    	//step 7  赠送用户父级积分
    	if ($user['farther_id'] && $good['integral'] > 0) {
    		$data['user_father'] = array (
    			'edit' => array (
    				'integral*f' => 'integral+'.$good['integral'],
    				'updated*f' => 'now()',
    			),
    			'where' => array (
    				'id' => $user['farther_id'],
    			),
    		);
    		$data['integral_father'] = array (
    			'user_id' => $user['farther_id'],
    			'type' => 3, // integral from child user
    			'num' => $good['integral'],
    			'remark' => sprintf('您的好友 %s 订水回赠', $user['name']),
    			'gift_by' => $user['id'],
    			'created*f' => 'now()',
    			'updated*f' => 'now()',
    		);
    	}
    	// step 8 Change user type
    	if ($user['type'] < 2) {
    		// Coupon number is exists or not is equal water type
    		if ($data['order']['is_normal']) {
    			$data['user']['edit']['type'] = 2;
    		} else  {
    			$data['user']['edit']['type'] = 1;
    		}
    	}
    	// step 9 Send coupon
    	if ($data['order']['coupon']) {
    		$data['user']['edit']['coupon*f'] = sprintf('coupon+' . $data['order']['coupon']);
    		for ($i = 0; $i < $data['order']['coupon']; $i++) {
    			$data['coupon'][$i] = array(
    				'serial' => $this->app->common->genRandomString(12),
    				'from_user' => $data['order']['user_id'],
    				'validity_time*f'=>'now()+interval 365 day',
    				'created*f' => 'now()',
    				'updated*f' => 'now()',
    			);
    		}
    	}
    	// step 10 Add coupon log
    	if ($data['order']['coupon']) {
    		$data['coupon_log'] = array(
    			'user_id'=>$data['order']['user_id'],
    			'num'=>$data['order']['coupon'],
    			'info'=>'购水获得',
    			'validity_time*f'=>'now()+interval 365 day',
    			'created*f' => 'now()',
    		);
    	}
    	//step   数据录入数据库
    	$model = new \app\model\Order($this->app);
    	return $model->add($data);
    }
/*}}}*/
/*{{{ cancel */
/** action 取消订单 */
    public function cancel() {
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
	        		'result_comment' => '用户'.$this->user['name'].'取消',
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
    		$out['msg'] = '系统忙，取消订单失败！';
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ success */
    public function success() {
        $out = array();

        $out['id'] = $this->app->request->params('id');

        $this->display('site/order_success.html', $out);
    }
/*}}}*/

}
?>
