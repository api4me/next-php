<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Shiptime.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

use Next\Core\Model;
class Shiptime extends \Next\Core\Control {
/*{{{ index */
    public function index() {
        $out = array();
    	// Default
    	$param = array('date' => null,);
    	$post = $this->app->request->post();
    	if ($post) {
    		$param['date'] = trim($post['date']);
    		$this->app->session->set('shiptime_search', $param);
    		$this->app->session->set('shiptime_page', 1);
    	} else {
    		$tmp = $this->app->session->get('shiptime_search');
    		if ($tmp) {
    			$param = $tmp;
    		}
    	}
    	$out['search'] = $param;
    	$model = new \app\model\Shiptime($this->app);
    	$page = 1;
    	$getpage = $this->app->request->get('page');
    	if ($getpage > 0) {
    		$this->app->session->set('shiptime_page', $getpage);
    		$page = $getpage;
    	} else{
    		$getpage = $this->app->session->get('shiptime_page');
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
	        	'url' => '/admin/shiptime/index/?page=',
	        	'page' => $page,
	        	'per_page' => $pagination['per_page'],
	        );
	        // Generate pagination
	        $pagination = new \Next\Helper\Pagination($config);
	        $out['pagination'] = $pagination->get_links();
        }
        $this->display('admin/shiptime.html', $out);
    }
/*}}}*/
/*{{{ edit */
    public function edit() {
    	$id = $this->app->request->get('id');
    	$model = new \app\model\Shiptime($this->app);
    	$out = array();
    	if ($id) {
    		$out['data'] = $model->loadById($id);
    	}
    	$this->display('admin/shiptime_edit.html', $out);
    }
/*}}}*/
    /*{{{ save */
    public function save() {
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Shiptime($this->app);
    	$data = array();
    	if (empty($post['except_date'])) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	if ($post['id']) {
    		$data['edit'] = array(
    			'except_date' =>$post['except_date'],
                'remark' =>$post['remark'],
                'updated*f' =>'now()',
    		);
    		$data['where'] = array('id'=>$post['id']);
    		$result = $model->edit($data);
    	}else{
    		$date = explode(',', $post['except_date']);
    		foreach ($date as $key=>$_date){
    			$data[$key]['except_date'] = $_date;
    			$data[$key]['remark'] = $post['remark'];
    			$data[$key]['created*f'] = 'now()';
    			$data[$key]['updated*f'] = 'now()';
    		}
    		$result = $model->add($data);
    	}
    	if ($result) {
    		$out['status'] = 200;
    		$this->rendJSON($out);
    	}else{
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    }
    /*}}}*/
    /*{{{ save */
    public function del() {
    	$id = $this->app->request->post('id');

    	if (!$id) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	$model = new \app\model\Shiptime($this->app);
    	if ($model->del($id)) {
    		$out['status'] = 200;
    		$this->rendJSON($out);
    	}else{
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    }
    /*}}}*/
}
