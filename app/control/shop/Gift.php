<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Gift.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

use Next\Core\Model;
class Gift extends \Next\Core\Control {

/*{{{ variable */
    private $user;
/*}}}*/
/*{{{ construct */
    public function __construct() {
        parent::__construct();
        $this->user = $this->app->session->get('user');
    }
/*}}}*/

/*{{{ 列表页 */
    public function index() {
        $out = array();
        $model = new \app\model\Gift($this->app);
        $out['data'] = $model->loadAllForSite();
        $this->display('site/gift.html', $out);
    }
/*}}}*/
/*{{{ 详情页 */
    public function details() {
    	$id = $this->app->request->params('id');
    	$out = array();
    	$model = new \app\model\Gift($this->app);
    	$check = array(
    		'gift_id'=>$id,
    		'user_id'=>$this->user['id'],
    	);
    	$is_changed = $model->checkIsChanged($check);
    	if (!empty($is_changed)) {
    		$out['changed']=1;
    	}
    	$out['data'] = $model->loadById($id);
    	$this->display('site/gift_details.html', $out);
    }
/*}}}*/



	private function buyData($key = null, $val = null) {
		$data = $this->app->session->get('gift_data');
		if (!isset($data)) {
			$data = array();
			$this->app->session->set('gift_data', $data);
		}

		if (!$key) {
			return $data;
		}

		if (!$val) {
			return isset($data[$key])? $data[$key]: null;
		}

		$data[$key] = $val;
		$this->app->session->set('gift_data', $data);

		return true;
	}
	/*}}}*/

	/*{{{ 领取礼品第一步填写礼品串码 */
	public function step1() {
		$id = $this->app->request->params('id');
		$out = array();
		$out['id'] = $id;
		if (!$this->app->request->params('back')) {
			// Reset session
			$this->app->session->del('gift_data');
		}
		$out['data'] = $this->buyData();
		$this->display('site/gift_change_step1.html', $out);
	}
	/*}}}*/

	/*{{{ step2 */
	public function step2() {
		$out = array();
		if ($tmp = $this->app->request->params('gift')) {
			$this->buyData('gift_id', $tmp);
		}
		if ($tmp = $this->app->request->params('serial')) {
			$this->buyData('serial', $tmp);
		}
		//获取session中的数据
		$out['data'] = $this->buyData();
		//送水地址信息
		$model_user = new \app\model\User($this->app);
		$address_id = 0;
		$addrid = $this->app->request->get('useaddr');
		if ($addrid) {
			$address_id = $addrid;
		}elseif (!empty($out['data']['address'])){
			$address_id = $out['data']['address'];
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
		$model_area = new \app\model\Area($this->app);
		if (!empty($address)) {
			$out['addr'] = $address;
			$out['addr']['area'] = $model_area->loadAll($address['area']);
			$out['addr']['auto_id'] =  $this->user['address_id'];
		}
		$this->display('site/gift_change_step2.html', $out);
	}
	/*}}}*/
	/** action 检查用户是否已关注尔冬吉*/
	public function attention(){
		$out = array();
		$out['status'] = 200;
		if (!$this->app->config('debug')) {
			$model_wechat = new \Next\Helper\Wechat();
			$attention = $model_wechat->getUserInfo($this->user['openid']);
			if ($attention && $attention['subscribe'] == '1')  {
				$out['status'] = 200;
			}else{
				$out['status'] = 400;
			}
		}
		$this->rendJSON($out);
	}
	/** action 检查用户使用的代码是否有效*/
	public function check(){
		$post = $this->app->request->post('data');
		$model = new \app\model\Gift();
		$check = $model->checkSerial($post);
		if (!empty($check)) {
			$out['status'] = 200;
		}else{
			$out['status'] = 400;
		}
		$this->rendJSON($out);
	}
	/** action 根据用户提交的信息生成配送信息*/
	public function exchange(){
		$post = $this->app->request->post('data');
		$model = new \app\model\Gift($this->app);
		$model_user = new \app\model\User($this->app);
		$serial = $model->checkSerial($post);
		$activity = $model->loadById($post['gift_id']);
        $user = $this->user;
    	$address = $model_user->loadAddressByID($post['address']);

    	if (empty($serial)) {
    		$out['msg'] = '礼品券代码不存在，请查看您的礼品券代码是否正确。';
    		$out['status']=400;
    		$this->rendJSON($out);
    	}

    	if (empty($activity)) {
    		$out['msg'] = '此活动不存在或活动已结束。';
    		$out['status']=400;
    		$this->rendJSON($out);
    	}

    	if (empty($address)) {
    		$out['msg'] = '您使用的地址有误，请检查您的联系信息是否正确。';
    		$out['status']=400;
    		$this->rendJSON($out);
    	}

    	if (strtotime($activity['start_time'])>time()) {
    		$out['msg'] = '活动还未开始。';
    		$out['status']=400;
    		$this->rendJSON($out);
    	}

    	if (strtotime($activity['end_time'])<time()) {
    		$out['msg'] = '活动已结束。';
    		$out['status']=400;
    		$this->rendJSON($out);
    	}
    	$check_is_changed = array(
    		'gift_id'=>$serial['gift_id'],
    		'user_id'=>$user['id'],
    	);
    	//检测用户是否领取过
    	$is_changed = $model->checkIsChanged($check_is_changed);
    	if (!empty($is_changed)) {
    		$out['status']=400;
    		$this->rendJSON($out);
    	}

    	$data['gift_serial'] = array(
    		'edit'=>array(
    			'user_id'=>$user['id'],
    			'user_name'=>$user['name'],
    			'is_used'=>'1',
    			'change_time*f'=>'now()',
    			'updated*f'=>'now()',
    		),
    		'where'=>array(
    			'id'=>$serial['id'],
    			'gift_id'=>$serial['gift_id'],
    			'serial'=>$serial['serial'],
    			'is_used'=>'0',
    		)
    	);
    	$time = explode (' ', microtime());
    	$sn = str_pad(date("ymdHis").ceil($time[0]*1000),15,'0',STR_PAD_RIGHT);
    	$data['gift_delivery'] = array(
    		'gift_id'=>$serial['gift_id'],
    		'gift_serial_id'=>$serial['id'],
    		'user_id'=>$user['id'],
    		'sn'=>$sn,
    		'gift'=>$activity['gift'],
    		'status'=>'preship',
    		'consignee'=>$address['consignee'],
    		'to_addr'=>$address['area'],
    		'address'=>$address['address'],
    		'mobile'=>$address['mobile'],
    		'created*f'=>'now()',
    		'updated*f'=>'now()',
    	);
    	if ($model->exchange($data)) {
			$out['status'] = 200;
		}else{
			$out['msg'] = '系统忙请稍后再试。。。';
			$out['status'] = 400;
		}
		$this->rendJSON($out);
	}
	public function success(){
		$out = array();
		$this->display('site/gift_change_success.html', $out);
	}
}

?>
