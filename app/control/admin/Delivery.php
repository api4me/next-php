<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Delivery.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Delivery extends \Next\Core\Control {

/*{{{ index */
    public function index() {
    	$out = array();
    	// Default
    	$param = array(
    			'status' => 0,
    			'type' => null,
    			'sn' => null,
    			'consignee' => null,
    			'start_date' => null,
    			'end_date' => null,
    	);
    	$post = $this->app->request->post();
    	if ($post) {
    		$param['status'] = $post['status'];
    		$param['type'] = $post['type'];
    		$param['sn'] = $post['sn'];
    		$param['consignee'] = $post['consignee'];
    		$param['start_date'] = $post['start_date'];
    		$param['end_date'] = $post['end_date'];
    		$this->app->session->set('delivery_search', $param);
    		$this->app->session->set('delivery_page', 1);
    		$out['count_show']=1;
    		if (!empty($post['export'])) {
    			$this->export($param);
    		}
    	} else {
    		$tmp = $this->app->session->get('delivery_search');
    		if ($tmp) {
    			$param = $tmp;
    		}
    	}
    	$out['search'] = $param;
    	$out['option']['status'] = array(
    			'0' => '--派送单状态--',
    			'prepay' => '待付款',
    			'preship' => '待配送',
    			'shipped' => '配送中',
    			'sign' => '已送达',
    			'finish' => '已完成',
    			'cancel' => '已作废',
    			'history' => '三个月前',
    	);
    	$model = new \app\model\Delivery($this->app);
    	$model_user = new \app\model\User($this->app);
    	$page = 1;
    	$getpage = $this->app->request->get('page');
    	if ($getpage > 0) {
    		$this->app->session->set('delivery_page', $getpage);
    		$page = $getpage;
    	} else{
    		$getpage = $this->app->session->get('delivery_page');
    		if ($getpage>0) {
    			$page = $getpage;
    		}
    	}
        $pagination = $this->app->config('pagination');
        $start = ($page-1)*$pagination['per_page'];

        if ($tmp =  $model->loadAll($param,$start,$pagination['per_page'])) {
        	$out['data'] = $tmp;
        	$user_ids = array();
	        foreach ($out['data']['delivery'] as $_delivery){
	        	$user_ids[] = $_delivery['user_id'];
	        }
	        $users = $model_user->loadUsersNames($user_ids);
	        foreach ($out['data']['delivery'] as $key=>$_delivery){
	        	$out['data']['delivery'][$key]['user_name'] = $users[$_delivery['user_id']];
	        }
	        $config = array(
	        		'total' => $out['data']['count'],
	        		'url' => '/admin/delivery/index/?page=',
	        		'page' => $page,
	        		'per_page' => $pagination['per_page'],
	        );
	        // Generate pagination
	        $pagination = new \Next\Helper\Pagination($config);
	        $out['pagination'] = $pagination->get_links();
        }
        $this->display('admin/delivery_index.html', $out);
    }
/*}}}*/
/*{{{ see */
    public function see() {
        $model = new \app\model\Delivery($this->app);
        $area = new \app\model\Area($this->app);
        $model_user = new \app\model\User($this->app);
        $status = array(
        	'prepay' => '待付款',
            'preship' => '待配送',
            'shipped' => '配送中',
            'sign' => '已送达',
        	'finish' => '已完成',
        	'cancel' => '已作废',
        );
        $out = array();
        $id = $this->app->request->get('id');
        if ($id) {
            $out['data'] = $model->loadDelivery($id);
        }
        $out['data']['to_addr'] = $area-> loadAll($out['data']['to_addr']);
        $out['data']['status'] = $status[$out['data']['status']];
        $out['data']['user'] = $model_user->loadById($out['data']['user_id']);
        $this->display('admin/delivery_see.html', $out);
    }
/*}}}*/
/*{{{ edit(编辑配送单页面) */
    public function edit() {
    	$model = new \app\model\Delivery($this->app);
    	$model_area = new \app\model\Area($this->app);
        $model_user = new \app\model\User($this->app);
    	$out = array();
    	$id = $this->app->request->get('id');
    	$out['data'] = $model->loadDelivery($id);
    	$out['data']['province'] = substr($out['data']['to_addr'], 0,2);
    	$out['data']['city'] = substr($out['data']['to_addr'], 2,2);
    	$out['data']['district'] = substr($out['data']['to_addr'], 4,2);
        $out['data']['user'] = $model_user->loadById($out['data']['user_id']);
    	$this->display('admin/delivery_edit.html', $out);
    }
/*}}}*/
/*{{{ editSave(编辑配送单保存) */
    public function editSave(){
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Delivery($this->app);
    	$data['delivery']=array(
    		'edit'=>array(
    			'remark' => $post['remark'],
    			'shipping_time' => $post['shipping_time'],
    			'type' => $post['type'],
    			'consignee' => $post['consignee'],
    			'mobile' => $post['mobile'],
    			'to_addr' => $post['province'].$post['city'].$post['district'],
    			'address' => $post['address'],
    			'updated*f' => 'now()',
    		),
    		'where' => array(
    			'id' => $post['id'],
    		),
    	);
    	if($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ comment(配送单物流反馈结果说明页) */
    public function comment(){
    	$model = new \app\model\Delivery($this->app);
        $area = new \app\model\Area($this->app);
        $status = array(
            'shipped' => '配送中',
        );
        $out = array();
        $id = $this->app->request->get('id');
        if ($id) {
            $out['data'] = $model->loadDelivery($id);
        }
        $out['data']['to_addr'] = $area-> loadAll($out['data']['to_addr']);
        $out['data']['status'] = $status[$out['data']['status']];
        $this->display('admin/delivery_comment.html', $out);
    }
    /*{{{ shipSuccess(配送单物流反馈结果说明页---配送成功--->已送达) */
    public function shipSuccess(){
    	$model = new \app\model\Delivery($this->app);
    	$post = $this->app->request->post('data');
    	$data['delivery']=array(
	    	'edit'=>array(
	    		'result_status' => '1',
	    		'status' => 'sign',
	    		'sign_time*f' => 'now()',
	    		'result_comment' => $post['result_comment'],
	    		'updated*f' => 'now()',
	    	),
	    	'where' => array(
    			'id' => $post['id'],
    		),
    	);
    	if($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /*{{{ shipSuccess(配送单物流反馈结果说明页---配送失败) */
    public function shipFailure(){
    	$model = new \app\model\Delivery($this->app);
    	$post = $this->app->request->post('data');
    	$data['delivery']=array(
    		'edit'=>array(
    			'result_status' => '0',
    			'result_comment' => $post['result_comment'],
    			'updated*f' => 'now()',
    		),
    		'where' => array(
    			'id' => $post['id'],
    		),
    	);
    	if($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /*{{{ shipSuccess(配送单物流反馈结果说明页---配送失败-->取消配送) */
    public function shipCancel(){
    	$model = new \app\model\Delivery($this->app);
    	$model_user = new \app\model\User($this->app);

    	$post = $this->app->request->post('data');
    	$delivery_id = $post['id'];
    	$delivery = $model->loadDelivery($delivery_id);

    	//首单
    	if ($delivery['order_id']) {
    		//微信未付款
    		if ($delivery['status']=='prepay') {
    			$data = $this->cancelFirst($delivery);
    		}elseif($delivery['need_pay']==1){
    		//货到付款未付款
    			$data = $this->cancelFirst($delivery);
    		}else{
    			$data = $this->cancelNormal($delivery_id,$post);
    		}
    	}else{
    		//正常
    		$data = $this->cancelNormal($delivery_id,$post);
    	}
    	if($model->edit($data,1)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /*** action 取消第一单 ***/
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
    			'result_comment' => '管理员取消',
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
    /*** action 普通配送单取消  ***/
    private function cancelNormal($delivery_id,$post){
    	$model = new \app\model\Delivery($this->app);
    	$data['delivery']=array(
    		'edit'=>array(
    			'status' => 'cancel',
    			'result_status' => '0',
    			'result_comment' => $post['result_comment'],
    			'updated*f' => 'now()',
    		),
    		'where' => array(
    			'id' => $delivery_id,
    		),
    	);
    	$delivery = $model->loadDelivery($delivery_id);
    	$delivery_goods = $model->loadDeliveryGoods($delivery_id);
    	$data['user']=array(
    		'edit'=>array(
    			'have_water*f' => 'have_water+'.$delivery['num'],
    			'updated*f' => 'now()',
    		),
    		'where'=>array(
    			'id'=>$delivery['user_id'],
    		)
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
    /*{{{ finish(配送单结单) */
    public function finish(){
    	$model = new \app\model\Delivery($this->app);
    	$id = $this->app->request->post('id');
    	$data['delivery']=array(
    		'edit'=>array(
    			'status' => 'finish',
    			'updated*f' => 'now()',
    		),
    		'where' => array(
    			'id' => $id,
    		),
    	);
    	if($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }

    public function cancleSign(){
    	$model = new \app\model\Delivery($this->app);
    	$id = $this->app->request->post('id');
    	$data['delivery']=array(
    		'edit'=>array(
    			'result_status'=>'',
    			'status' => 'shipped',
    			'updated*f' => 'now()',
    		),
    		'where' => array(
    			'id' => $id,
    		),
    	);
    	if($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /*{{{ finish(配送单列表页多选操作。)
     * 1.选择配送方式
    * 2.全部配送
    * 3.全部签单
    * */
    public function allLogistics(){
    	$post = $this->app->request->post('data');
    	switch ($post['action']){
    		case 'logistics':
    			$ship_type = 1;
    			break;
    		case 'self':
    			$ship_type = 2;
    			break;
    		case 'ship':
    			$status = 'shipped';
    			break;
    		case 'sign':
    			$status = 'sign';
    			break;
    	}
    	foreach ($post['ids'] as $key => $id){
    		if(isset($ship_type)){
    			$data[$key]['edit'] = array(
    					'type' => $ship_type,
    					'updated*f' => 'now()',
    			);
    		}elseif(isset($status)){
    			$data[$key]['edit'] = array(
    					'status' => $status,
    					'updated*f' => 'now()',
    			);
    			if ($status == 'sign') {
    				$data[$key]['edit']['result_status']='1';
    				$data[$key]['edit']['sign_time*f']='now()';
    			}
    		}
    		$data[$key]['where'] = array('id'=>$id);
    	}
    	$model = new \app\model\Delivery($this->app);

    	if ($model->allEdit($data)) {
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /*}}}*/
    public function ajax(){

    	$model = new \app\model\Delivery($this->app);
    	$id = $this->app->request->get('id');
    	$out['data'] = $model->loadDelivery($id);
    	$this->rendJSON($out);
    }
    public function editDeliveryRemark(){

    	$model = new \app\model\Delivery($this->app);
    	$post = $this->app->request->post('data');
    	$data['delivery']['edit']=array(
    			'remark' => $post['remark'],
    			'updated*f' => 'now()',
    	);
    	$data['delivery']['where']['id'] = $post['id'];
    	if($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }

    private function export($param){
    	$model = new \app\model\Delivery($this->app);
    	$model_export = new \app\model\Export($this->app);
    	$model_user = new \app\model\User($this->app);
    	$model_area = new \app\model\Area($this->app);
    	if (empty($param['start_date'])) {
    		$param['start_date'] = date('Y-m-01');
    	}
    	if (empty($param['end_date'])) {
    		$param['end_date'] = date('Y-m-d');
    	}
     	$deliverys = $model->export($param);
     	$status = array(
     		'prepay'=>'待付款',
     		'preship' => '待配送',
     		'shipped' => '配送中',
     		'sign' => '已送达',
     		'finish' => '已完成',
     		'cancel' => '已作废',
     	);
     	$types = array('--未指定--','物流','自己');
     	$first_line = array('开始时间',$param['start_date'],'结束时间',$param['end_date']);
        $export = $this->app->config('export');
     	$filename = $export['path'].'export-delivery-'.date("Y-m-d").'.xlsx';
     	$header = array('序号','配送单号','下单人/ID','箱数','预约时间','收货人','收货电话','收货地址',
     					'配送方式','配送单状态','客户留言','备注','待收款');
     	$user_ids = array();
     	foreach ($deliverys as $val){
        	$user_ids[] = $val['user_id'];
        }
        $users = $model_user->loadUsersNames($user_ids);
        $datas = array();
        foreach ($deliverys as $key=>$_delivery){
        	$datas[$key][] = $key+1;//序号
        	$datas[$key][] = '`'.$_delivery['sn'];//配送单号
        	$datas[$key][] = $users[$_delivery['user_id']].'/'.str_pad($_delivery['user_id'],7,'0',STR_PAD_LEFT);//下单人/ID
        	$datas[$key][] = $_delivery['num'];//箱数
        	$datas[$key][] = date('Y-m-d',strtotime($_delivery['shipping_time']));//预约时间
        	$datas[$key][] = $_delivery['consignee'];//收货人
        	$datas[$key][] = $_delivery['mobile'];//收货电话
        	$address = $model_area->loadAll($_delivery['to_addr']);
        	$datas[$key][] = $address.$_delivery['address'];//收货地址
        	$datas[$key][] = $types[$_delivery['type']];//配送方式
        	$datas[$key][] = $status[$_delivery['status']];//配送单状态
        	$datas[$key][] = $_delivery['comment'];//客户留言
        	$datas[$key][] = $_delivery['remark'];//备注
        	if ($_delivery['need_pay']==1 && $_delivery['pay_money']>0 && $_delivery['status']!='cancel'){
        		$datas[$key][] = $_delivery['pay_money']/100;//备注
        	}else{
        		$datas[$key][] = 0;
        	}
        }
        $makefile = $model_export->doExcelArr($datas,$header,$filename,$first_line);
        if ($makefile) {
        	return $makefile;
        }else{
        	return false;
        }
    }
}
