<?php
namespace app\control\admin;

class Gift extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $out = array();
    	// Default
    	$param = array('title' => null);
    	$post = $this->app->request->post();
    	if ($post) {
    		$param['title'] = trim($post['title']);
    		$this->app->session->set('gift_search', $param);
    		$this->app->session->set('gift_page', 1);
    	} else {
    		$tmp = $this->app->session->get('gift_search');
    		if ($tmp) {
    			$param = $tmp;
    		}
    	}
    	$out['search'] = $param;
    	$model = new \app\model\Gift();
    	$page = 1;
    	$getpage = $this->app->request->get('page');
    	if ($getpage > 0) {
    		$this->app->session->set('gift_page', $getpage);
    		$page = $getpage;
    	} else{
    		$getpage = $this->app->session->get('gift_page');
    		if ($getpage>0) {
    			$page = $getpage;
    		}
    	}
        $pagination = $this->app->config('pagination');
        $start = ($page-1)*$pagination['per_page'];
        $tmp =  $model->loadAll($param,$start,$pagination['per_page']);
        if ($tmp) {
        	$out['data'] = $tmp;
	        $config = array(
	        	'total' => $out['data']['count'],
	        	'url' => '/admin/gift/index/?page=',
	        	'page' => $page,
	        	'per_page' => $pagination['per_page'],
	        );
	        // Generate pagination
	        $pagination = new \Next\Helper\Pagination($config);
	        $out['pagination'] = $pagination->get_links();
        }
        $this->display('admin/gift.html', $out);
    }
/*}}}*/
/*{{{ edit */
    public function edit() {
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Gift();
    	$out = array();
    	if ($id) {
    		$out['data'] = $model->loadById($id);
    	}
    	$this->display('admin/gift_edit.html', $out);
    }
/*}}}*/
/*{{{ save */
    /** action  edit/add */
    public function save() {
    	$model = new \app\model\Gift();
    	$post = $this->app->request->post('data');

    	if ($post['id']) {
    		$data = array(
    			'edit'=>array(
    				'title'=>trim($post['title']),
    				'gift'=>trim($post['gift']),
    				'num'=>intval($post['num']),
    				'start_time'=>$post['start_time'].' 00:00:00',
    				'end_time'=>$post['end_time'].' 23:59:59',
    				'short_desc'=>$post['short_desc'],
    				'details'=>$post['details'],
    				'logo'=>$post['logo'],
    				'updated*f'=>'now()'
    			),
    			'where'=>array(
    				'id'=>$post['id'],
    			),
    		);
    		$result = $model->edit($data);
    	}else{
    		$data = array(
    			'title'=>trim($post['title']),
    			'gift'=>trim($post['gift']),
    			'num'=>intval($post['num']),
    			'start_time'=>$post['start_time'].' 00:00:00',
    			'end_time'=>$post['end_time'].' 23:59:59',
    			'short_desc'=>$post['short_desc'],
    			'details'=>$post['details'],
    			'logo'=>$post['logo'],
    			'status'=>'draft',
    			'created*f'=>'now()',
    			'updated*f'=>'now()',
    		);
    		$result = $model->add($data);
    	}
    	if ($result){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ del */
    /** action del */
    public function del(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Gift();
    	//删除活动
    	if ($model->del($id)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ perform */
    /** action perform*/
    public function perform(){
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Gift();
    	//获取活动信息
    	$serial_array = array();
    	$data['gift']=array(
    		'edit'=>array(
    			'status'=>'executed',
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$post['id'],
    		),
    	);
    	$i=0;
    	while ($i<$post['num']){
    		$serial = $this->app->common->genRandomString(12);
    		if (!in_array($serial, $serial_array)) {
    			$serial_array[]=$serial;
    			$i++;
    		}
    	}
    	$data['gift_serial']=array();
    	foreach ($serial_array as $val){
    		$data['gift_serial'][]=array(
    			'gift_id'=>$post['id'],
    			'serial'=>$val,
    			'created*f'=>'now()',
    			'updated*f'=>'now()',
    		);
    	}
    	if ($model->preform($data)) {
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ see */
    public function see(){
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Gift();
    	$out = array();
    	if ($id) {
    		$out['data'] = $model->loadById($id);
    		$out['serials'] = $model->loadInfoById($id);
    	}
    	$this->display('admin/gift_see.html', $out);
    }
/*}}}*/
/*{{{ ajax */
    public function ajax() {
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Gift();
    	$out = array();
    	$out['data'] = $model->loadInfoById($id);;
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ exportSerial */
    public function exportSerial() {
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Gift();

        $out = array();
        if ($tmp = $model->loadById($id)) {
            $out[] = array('活动名称', $tmp['title']);
            $out[] = array('数量', $tmp['num']);
            $out[] = array('', '');
            if ($tmp = $model->loadInfoById($id)) {
                foreach ($tmp as $key => $val) {
                    $out[] = array($key + 1, $val['serial']);
                }
            }
        }

        $file = sprintf('/tmp/gift-%s.xlsx', date('Ymdhis'));
        $write = new \Next\Helper\XLSXWriter();
        $write->writeSheet($out, 'Sheet1');
        $write->writeToFile($file);

        if (ob_get_level() !== 0) {
            ob_clean();
        }
        $mime = 'application/force-download';
        header('Pragma: public');       // required
        header('Expires: 0');           // no cache
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private',false);
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: close');
        readfile($file);           // push it out
        exit();
    }
/*}}}*/
/*{{{ delivery */
    public function delivery(){
    	$out = array();
    	// Default
    	$param = array('status' => '');
    	$post = $this->app->request->post();
    	if ($post) {
    		$param['status'] = trim($post['status']);
    		$this->app->session->set('gift_delivery_search', $param);
    		$this->app->session->set('gift_delivery_page', 1);
    	} else {
    		$tmp = $this->app->session->get('gift_delivery_search');
    		if ($tmp) {
    			$param = $tmp;
    		}
    	}
    	$out['search'] = $param;
    	$model = new \app\model\Gift();
    	$model_area = new \app\model\Area($this->app);
    	$page = 1;
    	$getpage = $this->app->request->get('page');
    	if ($getpage > 0) {
    		$this->app->session->set('gift_delivery_page', $getpage);
    		$page = $getpage;
    	} else{
    		$getpage = $this->app->session->get('gift_delivery_page');
    		if ($getpage>0) {
    			$page = $getpage;
    		}
    	}
    	$pagination = $this->app->config('pagination');
    	$start = ($page-1)*$pagination['per_page'];
    	$tmp =  $model->loadAllDelivery($param,$start,$pagination['per_page']);
    	if ($tmp) {
    		foreach ($tmp['delivery'] as $key => $val){
    			$tmp['delivery'][$key]['to_addr'] = $model_area->loadAll($val['to_addr']);
    		}
    		$out['data'] = $tmp;
    		$config = array(
    				'total' => $out['data']['count'],
    				'url' => '/admin/gift/delivery/?page=',
    				'page' => $page,
    				'per_page' => $pagination['per_page'],
    		);
    		// Generate pagination
    		$pagination = new \Next\Helper\Pagination($config);
    		$out['pagination'] = $pagination->get_links();
    	}
    	$this->display('admin/gift_delivery.html', $out);
    }
/*}}}*/
/*{{{ deliveryEdit */
    public function deliveryEdit(){
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Gift($this->app);
    	$out = array();
    	$out['data'] = $model->loadDelivery($id);
    	$out['data']['province'] = substr($out['data']['to_addr'], 0,2);
    	$out['data']['city'] = substr($out['data']['to_addr'], 2,2);
    	$out['data']['district'] = substr($out['data']['to_addr'], 4,2);
    	$this->display('admin/gift_delivery_edit.html', $out);
    }
/*}}}*/
/*{{{ deliveryAjax */
    public function deliveryAjax(){
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Gift($this->app);
    	$out = array();
    	$out['data'] = $model->loadDelivery($id);
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ editDeliveryRemark */
    public function editDeliveryRemark(){
    	$model = new \app\model\Gift($this->app);
    	$post = $this->app->request->post('data');
    	$data = array(
    		'edit'=>array(
    			'remark'=>trim($post['remark']),
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$post['id'],
    		),
    	);
    	if ($model->editDelivery($data)) {
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ allDelivery */
    public function allDelivery(){
    	$model = new \app\model\Gift($this->app);
    	$post = $this->app->request->post('data');
    	if (count($post['ids'])>0){
    		if ($model->allDelivery($post['ids'])) {
    			$out['status'] = 200;
    		}else{
    			$out['status'] = 400;
    		}
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ deliverySave */
    public function deliverySave(){
    	$model = new \app\model\Gift();
    	$post = $this->app->request->post('data');
    	$data = array(
    		'edit'=>array(
    			'remark'=>trim($post['remark']),
    			'consignee'=>trim($post['consignee']),
    			'mobile'=>intval($post['mobile']),
    			'to_addr'=>$post['province'].$post['city'].$post['district'],
    			'address'=>$post['address'],
    			'updated*f'=>'now()'
    		),
    		'where'=>array(
    			'id'=>$post['id'],
    		),
    	);
    	$result = $model->editDelivery($data);
    	if ($result){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ ajaxRquest */
    public function ajaxRquest(){
    	$model = new \app\model\Gift();
    	$count = $model->checkPreship();
    	if ($count>0) {
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/

}
