<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Delivery.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Delivery extends \Next\Core\Control {

/*{{{ variable */
    private $user;
/*}}}*/
/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->user = $this->app->session->get('user');
    }
/*}}}*/

/*{{{ index */
    public function index() {
        $out = array();
        $model = new \app\model\Delivery();
        $all = $this->app->request->get('all');
        if ($all) {
        	$out['all']=1;
        	$out['data'] = $model->loadAllForSite($this->user['id'],$all);
        }else{
        	$out['data'] = $model->loadAllForSite($this->user['id']);
        }
        $this->display('site/delivery.html', $out);
    }
/*}}}*/
/*{{{ reserve */
    public function reserve() {
    	$model_user = new \app\model\User($this->app);
    	$model_area = new \app\model\Area($this->app);
        $out = array();
        //水
        $out['have_water'] = $model_user->loadUserCouldUseWater($this->user['id']);
        if ($out['have_water'] > 0 ) {
        	//送水地址信息
        	$address_id = 0;
        	$addrid = $this->app->request->get('address');
        	if ($addrid) {
        		$address_id = $addrid;
        	}else{
        		$address_id = $this->user['address_id'];
        	}
	        if($address_id){
	        	$address = $model_user->loadAddressByID($address_id);
	        	if (!$address) {
		        	$addresses = $model_user->loadUserAddress($this->user['id']);
		        	if (!empty($addresses)) {
		        		$address = $addresses[0];
		        	}
	        	}
	        }else{
	        	$addresses = $model_user->loadUserAddress($this->user['id']);
	        	if (!empty($addresses)) {
	        		$address = $addresses[0];;
	        	}
	        }
	        if (!empty($address)) {
	        	$out['addr'] = $address;
	        	$out['addr']['area'] = $model_area->loadAll($address['area']);
	        	$out['addr']['auto_id'] =  $this->user['address_id'];
	        }else{
	        	$out['null_addr'] = 1;
	        }
	        //不可配送日期
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
        }else{
        	$out['have_water'] = 0;
        }
        $this->display('site/delivery_reserve.html', $out);
    }
/*}}}*/
	public function add(){
		$post = $this->app->request->post('data');
		$num = $post['num'];
		//地址信息获取
		$model_user = new \app\model\User($this->app);
		$address = $model_user->loadAddressByID($post['address']);
		$model_order = new \app\model\Order($this->app);
		$model_delivery = new \app\model\Delivery($this->app);
		//配送日期验证
		$model_shiptime = new \app\model\Shiptime($this->app);
		$except_date = $model_shiptime->loadMonth();
		if (in_array($post['date'], $except_date)) {
			$out['msg'] = '你预约的定水日期有误！';
			$out['status'] = 400;
			$this->rendJSON($out);
		}
		//配送单基本信息
		$time = explode (' ', microtime());
		$sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
		$data['delivery']= array(
			'sn'=>$sn,
			'user_id'=>$this->user['id'],
			'status' => 'preship',
			'consignee'=>$address['consignee'],
			'to_addr'=>$address['area'],
			'address'=>$address['address'],
			'mobile'=>$address['mobile'],
			'shipping_time'=>$post['date'],
			'num' => $num,
			'created*f' => 'now()',
		);
		//生成配送明细
		$all_order = $model_order->loadByUserId($this->user['id'],1);
		$orderid_array=array();
		foreach ($all_order as $order){
			$orderid_array[] = $order['id'];
		}
		$all_goods = $model_order->loadOrdersGoods($orderid_array,1);
		foreach ($all_goods as $_good){
			if ($_good['goods_residue']>=$num){
				$_good['goods_residue'] = $_good['goods_residue'] - $num;
				$data['order_goods'][$_good['id']] = array(
					'edit'=>array(
						'goods_residue' => $_good['goods_residue'],
						'updated*f' => 'now()',
					),
					'where' => array(
						'id' => $_good['id'],
					),
				);
				$data['delivery_goods'][] = array(
					'order_id' => $_good['order_id'],
					'order_goods_id' => $_good['id'],
					'num' => $num,
					'created*f' => 'now()',
				);
				$data['order'][$_good['order_id']] = array(
					'edit'=>array(
						'goods_residue' => $all_order[$_good['order_id']]['goods_residue'] - $num,
						'updated*f' => 'now()',
					),
					'where'=>array(
						'id' => $_good['order_id'],
					)
				);
				break;
			}else{
				$num = $num - $_good['goods_residue'];
				$data['delivery_goods'][] = array(
					'order_id' => $_good['order_id'],
					'order_goods_id' => $_good['id'],
					'num' => $_good['goods_residue'],
					'created*f' => 'now()',
				);
				$data['order'][$_good['order_id']] = array(
					'edit'=>array(
						'goods_residue' => $all_order[$_good['order_id']]['goods_residue'] - $_good['goods_residue'],
						'updated*f' => 'now()',
					),
					'where'=>array(
						'id' => $_good['order_id'],
					),
				);
				$all_order[$_good['order_id']]['goods_residue'] = $all_order[$_good['order_id']]['goods_residue'] - $_good['goods_residue'];
				$_good['goods_residue'] = 0;
				$data['order_goods'][$_good['id']] = array(
					'edit'=>array(
						'goods_residue' => $_good['goods_residue'],
						'updated*f' => 'now()',
					),
					'where' => array(
						'id' => $_good['id'],
					),
				);
			}
		}
		$data['user']=array(
			'edit'=>array(
				'have_water*f'=>'have_water-'.$post['num'],
				'updated*f' => 'now()',
			),
			'where'=>array(
				'id'=>$this->user['id'],
			)
		);
		if ($model_delivery->add($data)) {
			$user = $model_user->loadById($this->user['id']);
			$this->app->session->set('user', $user);
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
	}
	/*{{{ 取消配送 */
	public function cancel(){
		$delivery_id = $this->app->request->post('id');
		$model = new \app\model\Delivery($this->app);
		$model_user = new \app\model\User($this->app);
		$delivery = $model->loadDelivery($delivery_id);
		if (!in_array($delivery['status'], array('prepay','preship'))) {
			$out['status'] = 400;
			$this->rendJSON($out);
		}
		if ($delivery['order_id']) {
			if ($delivery['need_pay']!=1 && $delivery['status']=='preship') {
				$data = $this->cancelNormal($delivery);
			}else{
				$data = $this->cancelFirst($delivery);
			}
		}else{
			$data = $this->cancelNormal($delivery);
		}
		if($model->edit($data,1)){
			$user = $model_user->loadById($this->user['id']);
			$this->app->session->set('user', $user);
			$out['status'] = 200;
		} else {
			$out['status'] = 400;
		}
		$this->rendJSON($out);
	}
	private function cancelFirst($delivery){
		$model = new \app\model\Order($this->app);
		$data['order'][] = array(
			'edit'=>array(
				'status' => 'cancel',
				'updated*f' => 'now()',
			),
			'where'=>array(
				'id' => $delivery['order_id'],
				'status' => 'prepay',
			),
		);
		$data['delivery'] = array(
			'edit'=>array(
				'status'=>'cancel',
				'result_status' => '0',
				'result_comment' => '用户'.$this->user['name'].'取消',
				'updated*f' => 'now()',
			),
			'where'=>array(
				'id'=>$delivery['id'],
				'order_id'=>$delivery['order_id'],
			),
		);
		//用户编辑
		$order_goods = $model->loadOrdersGoods($delivery['order_id']);
		$model_product = new \app\model\Product($this->app);
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
			$data['user']['where']['id'] = $this->user['id'];
		}
		return $data;
	}
	private function cancelNormal($delivery){
		$model = new \app\model\Delivery($this->app);
		$data = array();
		$data['delivery']=array(
			'edit'=>array(
				'status' => 'cancel',
				'result_status' => '0',
				'result_comment' => '用户'.$this->user['name'].'取消',
				'updated*f' => 'now()',
			),
			'where' => array(
				'id' => $delivery['id'],
				'status' => 'preship',
			),
		);
		$delivery_goods = $model->loadDeliveryGoods($delivery['id']);
		$data['user']=array(
			'edit'=>array(
				'have_water*f' => 'have_water+'.$delivery['num'],
				'updated*f' => 'now()',
			),
			'where'=>array(
				'id'=>$delivery['user_id'],
			),
		);
		foreach ($delivery_goods as $_goods){
			$data['order'][]=array(
				'edit'=> array(
					'goods_residue*f'=>'goods_residue+'.$_goods['num'],
					'updated*f' => 'now()',
				),
				'where' => array(
					'id'=>$_goods['order_id'],
				),
			);
			$data['order_goods'][] = array(
				'edit'=>array(
					'goods_residue*f'=>'goods_residue+'.$_goods['num'],
					'updated*f' => 'now()',
				),
				'where' => array(
					'id'=>$_goods['order_goods_id'],
				),
			);
		}
		return $data;
	}
	/*}}}*/
	public function sign(){
		$model = new \app\model\Delivery($this->app);
		$id = $this->app->request->post('id');
		$data['delivery']=array(
				'edit'=>array(
						'result_status' => '1',
						'status' => 'finish',
						'sign_time*f' => 'now()',
						'updated*f' => 'now()',
				),
				'where' => array(
						'id' => $id,
				),
		);
		if($model->sign($data)){
			$out['status'] = 200;
		} else {
			$out['status'] = 400;
		}
		$this->rendJSON($out);
	}
}

?>
