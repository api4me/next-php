<?php
namespace app\control\admin;

use Next\Core\Model;
class Awards extends \Next\Core\Control {
/*{{{ index */
	/**列表页**/
    public function index() {
        $out = array();
    	// Default
    	$param = array('title' => null,'date'=>null,);
    	$post = $this->app->request->post();
    	if ($post) {
    		$param['title'] = trim($post['title']);
    		$param['date'] = trim($post['date']);
    		$this->app->session->set('awards_search', $param);
    		$this->app->session->set('awards_page', 1);
    	} else {
    		$tmp = $this->app->session->get('awards_search');
    		if ($tmp) {
    			$param = $tmp;
    		}
    	}
    	$out['search'] = $param;
    	$model = new \app\model\Awards($this->app);
    	$page = 1;
    	$getpage = $this->app->request->get('page');
    	if ($getpage > 0) {
    		$this->app->session->set('awards_page', $getpage);
    		$page = $getpage;
    	} else{
    		$getpage = $this->app->session->get('awards_page');
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
	        	'url' => '/admin/awards/index/?page=',
	        	'page' => $page,
	        	'per_page' => $pagination['per_page'],
	        );
	        // Generate pagination
	        $pagination = new \Next\Helper\Pagination($config);
	        $out['pagination'] = $pagination->get_links();
        }
        $this->display('admin/awards.html', $out);
    }
/*}}}*/
    /**编辑/设置页**/
    public function edit() {
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Awards($this->app);
    	$out = array();
    	$temp = 'awards_edit.html';
    	if ($id) {
    		$out['data'] = $model->loadById($id);
    		$out['users'] = $model->loadInfoById($id);
    		if($out['data']['perform']==1){
    			$temp = 'awards_edit_user.html';
    		}
    	}
    	$this->display('admin/'.$temp, $out);
    }
/*}}}*/
    /** ajax action 增加抽奖信息**/
    public function add() {
    	$post = $this->app->request->post('data');
    	if (empty($post['end_time'])) {
    		$time = date('Y-m-d');
    		$post['end_time']=date('Y-m-d', strtotime($time.'1year'));
    	}
    	$model = new \app\model\Awards($this->app);
    	$data = array(
    		'title' => trim($post['title']),
    		'content' => trim($post['content']),
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
    /** ajax action 编辑保存抽奖信息**/
    public function save() {
    	$post = $this->app->request->post('data');

    	$model = new \app\model\Awards($this->app);
    	if (isset($post['title'])&&$post['content']) {
    		$data['awards'] = array(
	    		'edit'=>array(
	    			'title'=>$post['title'],
	    			'content'=>$post['content'],
	    			'updated*f'=>'now()',
	    		),
	    		'where'=>array(
	    			'id'=>$post['id'],
	    		),
	    	);
    	}
    	if (isset($post['users'])) {
	    	if (count($post['users'])>0) {
		    	foreach ($post['users'] as $key=>$_u){
		    		$data['users'][$key] = $_u;
		    		$data['users'][$key]['awards_id'] = $post['id'];
		    		$data['users'][$key]['created*f'] = 'now()';
		    		$data['users'][$key]['updated*f'] = 'now()';
		    	}
	    	}
    	}
    	if ($model->edit($data)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /** ajax action 编辑页载入查询的用户**/
    public function ajaxLoadUsers() {
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Awards($this->app);
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
    /** ajax action 编辑删除添加的用户**/
    public function delUser(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Awards($this->app);
    	if ($model->delawardsUser($id)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /** ajax action 删除抽奖信息**/
    public function delawards(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Awards($this->app);
    	$awards = $model->loadById($id);
    	$users = $model->loadInfoById($id);
    	$del_user = 0;
    	if (count($users)>0) {
    		$del_user = 1;
    	}
    	//已执行，不可被删除
    	if ($awards['perform']==1) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	//删除
    	if ($model->delawards($id,$del_user)){
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /** ajax action 执行抽奖信息**/
    public function perform(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Awards($this->app);
    	//获取活动信息
    	$awards = $model->loadById($id);
    	if ($awards['perform']!=1) {
    		$data['awards'] = array(
	    		'edit'=>array(
	    			'perform'=>1,
	    			'updated*f'=>'now()',
	    		),
	    		'where'=>array(
	    			'id'=>$id,
	    		),
	    	);
    	}
    	if ($model->preform($data)) {
    		$out['status'] = 200;
    	} else {
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
}
