<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/** 
* @filename Feedback.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Feedback extends \Next\Core\Control {
/*{{{ index */
    public function index() {
    	$model = new \app\model\Feedback($this->app);
        $out = array();
        $page = $this->app->request->get('page', 1);
        $pagination = $this->app->config('pagination');
        $start = ($page-1)*$pagination['per_page'];
		$out['data'] = $model->loadAllWithName($start,$pagination['per_page']);
		$config = array(
				'total' => $out['data']['count'],
				'url' => '/admin/user/index/?page=',
				'page' => $page,
				'per_page' => $pagination['per_page'],
		);
		// Generate pagination
		$pagination = new \Next\Helper\Pagination($config);
		$out['pagination'] = $pagination->get_links();
		$this->display('admin/feedback.html', $out);
    }
    public function delete(){
    	$model = new \app\model\Feedback($this->app);
    	$post = $this->app->request->post('ids');
    	if (is_array($post)) {
    		if ($model->delete($post)) {
    			$out['status']=200;
    		}else{
    			$out['status']=400;
    		}
    	}else{
    		$out['status']=400;
    	}
    	$this->rendJSON($out);
    }
}
