<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/** 
* @filename Article.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Article extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $out = array();
        // Default
        $param = array(
        	'type' => null,	
            'name' => null,
        );
        $post = $this->app->request->post();
        if ($post) {
            $param['type'] = $post['type'];
            $param['name'] = $post['name'];
            $this->app->session->set('article_search', $param);
        } else {
        	$tmp = $this->app->session->get('article_search');
            if ($tmp) {
                $param = $tmp;
            }
        }
        $out['search'] = $param;
        
        $model = new \app\model\Article($this->app);
        $out['data'] = $model->loadAll($param);
		$out['type']=array(
			'intro'=>'引导页',
			'brand'=>'品牌故事',
			'product'=>'产品介绍',
			'detail'=>'我要订水-产品详情',
			'aboutus'=>'走进尔冬吉',
			'article'=>'健康饮水',
			'integral'=>'积分规则',
			'guide'=>'操作指引',
		);
        $this->display('admin/article_index.html', $out);
    }
/*}}}*/
/*{{{ edit */
    public function edit(){
    	$id = $this->app->request->get('id');
    	$out = array();
    	if ($id) {
    		$model = new \app\model\Article($this->app);
    		$out = $model->loadById($id);
    	}
    	$this->display('admin/article_edit.html', $out);
    }
/*}}}*/
/*{{{ save */
    public function save(){
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Article($this->app);
    	if (!empty($post['id'])) {
    		$article = array(
    			'edit'=>array(
    				'name'=>$post['name'],
    				'content'=>$post['content'],
    				'updated*f'=>'now()',
    			),
    			'where'=>array(
    				'id'=>$post['id'],
    			),
    		);
    		$result = $model->edit($article);
    	}else{
    		$article = array(
    			'type'=>'article',
    			'type_name'=>'健康饮水',
    			'name'=>$post['name'],
    			'content'=>$post['content'],
    			'created*f'=>'now()',
    			'updated*f'=>'now()',
    		);
    		$result = $model->add($article);
    	}
    	if($result){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ delete */
    public function delete(){
    	$id = $this->app->request->post('id');
    	$model = new \app\model\Article($this->app);
    	if (!$id) {
    		$out['status'] = 400;
    		$this->rendJSON($out);
    	}
    	if($model->delete($id)){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/

}
