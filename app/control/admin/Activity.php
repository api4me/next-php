<?php
namespace app\control\admin;

use Next\Core\Model;
class Activity extends \Next\Core\Control {
/*{{{ index */
    public function index() {
        $out = array();
    	// Default
    	$param = array('title' => null,'num' => null,'date' => null,);
    	$post = $this->app->request->post();
    	if ($post) {
    		$param['title'] = trim($post['title']);
    		$param['num'] = trim($post['num']);
    		$param['date'] = trim($post['date']);
    		$this->app->session->set('activity_search', $param);
    		$this->app->session->set('activity_page', 1);
    	} else {
    		$tmp = $this->app->session->get('activity_search');
    		if ($tmp) {
    			$param = $tmp;
    		}
    	}
    	$out['search'] = $param;
    	$model = new \app\model\Activity($this->app);
    	$page = 1;
    	$getpage = $this->app->request->get('page');
    	if ($getpage > 0) {
    		$this->app->session->set('activity_page', $getpage);
    		$page = $getpage;
    	} else{
    		$getpage = $this->app->session->get('activity_page');
    		if ($getpage>0) {
    			$page = $getpage;
    		}
    	}
        $pagination = $this->app->config('pagination');
        $start = ($page-1)*$pagination['per_page'];
        if ($tmp =  $model->loadAll($param,$start,$pagination['per_page'])) {
        	$out['data'] = $tmp;
	        $config = array(
	        	'total' => $out['data']['count'],
	        	'url' => '/admin/activity/index/?page=',
	        	'page' => $page,
	        	'per_page' => $pagination['per_page'],
	        );
	        // Generate pagination
	        $pagination = new \Next\Helper\Pagination($config);
	        $out['pagination'] = $pagination->get_links();
        }
        $this->display('admin/activity.html', $out);
    }
/*}}}*/
/*{{{ edit */
    public function edit() {
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Activity($this->app);
    	$out = array();
    	$temp = 'activity_edit.html';
    	if ($id) {
    		$out['data'] = $model->loadById($id);
    		$out['users'] = $model->loadInfoById($id);
    		if($out['data']['perform']==1){
    			$temp = 'activity_edit_user.html';
    		}
    	}
    	$this->display('admin/'.$temp, $out);
    }
/*}}}*/
    /*{{{ add */
    public function add() {
    	$post = $this->app->request->post('data');
    	if (empty($post['end_time'])) {
    		$time = date('Y-m-d');
    		$post['end_time']=date('Y-m-d', strtotime($time.'1year'));
    	}
    	$model = new \app\model\Activity($this->app);
    	$data = array(
    		'type' => 'coupon',
    		'desc' => $post['title'],
    		'title' => $post['title'],
    		'num' => $post['num'],
    		'end_time' => $post['end_time'].' 23:59:59',
    		'created*f' => 'now()',
    		'updated*f' => 'now()',
    	);
    	if ($model->add($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    public function save() {
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Activity($this->app);
    	if (isset($post['title'])&&$post['num']&&$post['end_time']) {
    		$data['activity'] = array(
	    		'edit'=>array(
	    			'title'=>$post['title'],
	    			'num'=>$post['num'],
	    			'end_time'=>$post['end_time']." 23:59:59",
	    			'updated*f'=>'now()',
	    		),
	    		'where'=>array(
	    			'id'=>$post['id'],
	    		),
	    	);
    	}
    	if (count($post['users'])>0) {
	    	foreach ($post['users'] as $key=>$_u){
	    		$data['users'][$key] = $_u;
	    		$data['users'][$key]['activity_id'] = $post['id'];
	    		$data['users'][$key]['created*f'] = 'now()';
	    		$data['users'][$key]['updated*f'] = 'now()';
	    	}
    	}
    	if ($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    public function ajaxLoadUsers() {
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Activity($this->app);
    	$out = array();
    	$param = $post;
    	unset($param['id']);
    	$users = $model->loadAllUsers($post);
    	$have_users = $model->loadInfoById($post['id']);
    	foreach ($have_users as $_user){
    		unset($users[$_user['user_id']]);
    	}
    	$out['data'] = $users;
        $this->rendJSON($out);
    }
    public function delUser(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Activity($this->app);
    	if ($model->delActivityUser($id)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    public function delActivity(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Activity($this->app);
    	$activity = $model->loadById($id);
    	$users = $model->loadInfoById($id);
    	$del_user = 0;
    	if (count($users)>0) {
    		$del_user = 1;
    	}
    	//活动已执行，不可被删除
    	if ($activity['perform']==1) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//删除活动
    	if ($model->delActivity($id,$del_user)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    public function perform(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Activity($this->app);
    	//获取活动信息
    	$activity = $model->loadById($id);
    	//获取活动未发放赠券的用户信息
    	$users = $model->loadInfoByIdNo($id);
    	$data['num'] = $activity['num'];
    	if ($data['num']<=0) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	$data['end_time'] = $activity['end_time'];
    	$data['title'] = $activity['title'];
    	if ($activity['perform']!=1) {
    		$data['activity'] = array(
	    		'edit'=>array(
	    			'perform'=>1,
	    			'updated*f'=>'now()',
	    		),
	    		'where'=>array(
	    			'id'=>$id,
	    		),
	    	);
    	}
    	if (count($users)>0) {
    		foreach ($users as $_user){
    			$data['users'][]=array(
    				'edit'=>array(
    					'user_id'=>$_user['user_id'],
    					'get_coupon'=>1,
    					'updated*f'=>'now()',
    				),
    				'where'=>array(
    					'id'=>$_user['id'],
    				),
    			);
    		}
    	}
    	if ($model->preform($data)) {
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
}
