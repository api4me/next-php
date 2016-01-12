<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Help.php
* @touch date Mon 30 Jun 2014 03:57:46 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Help extends \Next\Core\Control {

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
        $this->display('site/help.html', $out);
    }
/*}}}*/
/*{{{ refund */
    public function refund() {
        $out = array();
        
        $model = new \app\model\Order();
        $out['data'] = $model->loadRefund($this->user['id']);

        $this->display('site/help_refund.html', $out);
    }
/*}}}*/
/*{{{ applyRefund */
    public function applyRefund() {
        $out = array();
        $out['id'] = $this->app->request->get('id');
        $this->display('site/help_apply_refund.html', $out);
    }
/*}}}*/
    /*{{{ commitRefund */
    public function commitRefund() {
    	$post = $this->app->request->post('data');
    	$model = new \app\model\Order();
    	$order = $model->loadOrder($post['id']);
    	$model_user = new \app\model\User();
    	$refund_info = array(
    		'name'=>$post['name'],
    		'mobile'=>$post['mobile'],
    		'card_name'=>$post['card_name'],
    		'card_num'=>$post['card_num'],
    		'card_bank'=>$post['card_bank'],
    	);
    	$refund_info = json_encode($refund_info);
    	if($order['id']&&$order['status']=='paid'){
    		$data = array(
    			'edit'=>array(
    				'status'=>'refund',
    				'refund_reason'=>$post['refund_reason'],
    				'refund_info'=>$refund_info,
    				'refund_invoice'=>$post['refund_invoice'],
    				'refund_pre_status'=>'paid',
    				'updated*f'=>'now()',
    			),
    			'where'=>array(
    				'id'=>$order['id'],
    				'status'=>'paid',
    				'user_id'=>$order['user_id'],
    			),
    		);
    		if ($model->edit($data['edit'], $data['where'])) {
    			$out['status']=200;
    		}else{
    			$out['status']=400;
    		}
    	}else{
    		$out['status']=400;
    	}
    	$this->rendJSON($out);
    }
    /*}}}*/
/*{{{ feedback */
    public function feedback() {
        $out = array();
        if ($this->app->request->isGet()) {
            $this->display('site/help_feedback.html', $out);
            return true;
        }

        // Add feedback
        $model = new \app\model\Feedback();
        $post = $this->app->request->post('data');
        $param = array(
            'user_id' => $this->user['id'],
            'contacts' => strip_tags($post['contacts']),
        	'mobile' => strip_tags($post['mobile']),
            'content' => strip_tags($post['content']),
        );
        if ($model->add($param)) {
            $out['status'] = 200;
            $this->rendJSON($out);
        }

        $out['status'] = 400;
        $this->rendJSON($out);
    }
/*}}}*/

/*{{{ aboutus */
    public function aboutus() {
        $out = array();

        $config = $this->app->config('wechat');
        $out['appid'] = $config['appid'];

        $type = 'aboutus';
        $model = new \app\model\Article();
        $out['backurl'] = '/shop/help/';
        $out['data'] = $model->loadOneByType($type);
        $out['data']['short'] = mb_substr(strip_tags($out['data']['content']), 0, 50, 'utf-8');

        $this->display('site/article_detail.html', $out);
    }
/*}}}*/
/*{{{ clause */
    public function clause() {
        $out = array();

        $type = 'clause';
        $model = new \app\model\Article();
        $out['backurl'] = '/shop/help/';
        $out['data'] = $model->loadOneByType($type);

        $this->display('site/article_detail.html', $out);
    }
/*}}}*/

}
?>
