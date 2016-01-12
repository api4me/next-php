<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Integral.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Integral extends \Next\Core\Control {

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
        $modelUser = new \app\model\User(); 
        $out['user'] = $modelUser->loadById($this->user['id']);

        $model = new \app\model\Integral();
        $out['data'] = $model->loadForSite($this->user['id']);

        $this->display('site/integral.html', $out);
    }
/*}}}*/
/*{{{ more */
    public function more() {
        $out = array();

        $start = $this->app->request->params('start');
        if (!$start || !is_numeric($start)) {
            $out['status'] = 400;
            $out['msg'] = '系统忙，请稍后再试'; 
            $this->rendJSON($out);
        }

        $model = new \app\model\Integral();
        $out['status'] = 200;
        $out['data'] = $model->loadForSite($this->user['id'], $start);

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ timeline */
    public function timeline(){
    	$model = new \app\model\User(); 
    	$is_add = $model->checkUserWeekIntegral($this->user['id']);
    	if (!$is_add){
    		$data['integral'] = array(
    			'type'=>'1',
    			'user_id'=>$this->user['id'],
    			'num'=>'2',
    			'remark'=>'分享获得',
    			'created*f'=>'now()',
    		);
    		$data['user'] = array(
    			'edit'=>array(
    				'integral*f'=>'integral+2',
    				'updated*f'=>'now()',
    			),
    			'where'=>array(
    				'id'=>$this->user['id'],
    			),
    		);
    		if($model->shareAddIntegral($data)){
    			$user = $model->loadById($this->user['id']);
    			$this->app->session->set('user', $user);
    			$out['status']=200;
    		}else{
    			$out['status']=400;
    		}
    	}else {
    		$out['status']=200;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ state */
    public function state() {
        $out = array();

        $type = 'integral';
        $model = new \app\model\Article();
        $out['backurl'] = '/shop/integral/';
        $out['data'] = $model->loadOneByType($type);

        $this->display('site/article_detail.html', $out);
    }
/*}}}*/

}

?>
