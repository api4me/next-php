<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Coupon.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Coupon extends \Next\Core\Control {

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
        $model = new \app\model\Coupon($this->app);
        $out['data'] = $model->loadForSite($this->user['id']);
        $config = $this->app->config('wechat');
        $out['appid'] = $config['appid'];
        $out['logs'] = $model->loadLogs($this->user['id']);
        $this->display('site/coupon.html', $out);
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

        $model = new \app\model\Coupon($this->app);
        $out['status'] = 200;
        $out['data'] = $model->loadForSite($this->user['id'], $start);

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ send */
	public function send(){
		$model = new \app\model\Coupon($this->app);
		$coupon_id = $this->app->request->get('coupon');
		$check = $model->checkIsCouldSend($coupon_id);
		if ($check) {
			$data['coupon'] = array(
				'edit' => array('set_time*f'=>'now()','updated*f'=>'now()',),
				'where' => array(
					'id' => $coupon_id,
				),
			);
			$data['user'] = array(
				'edit' => array('coupon*f'=>'coupon-1','updated*f'=>'now()',),
				'where' => array(
					'id' => $this->user['id'],
				),
			);
			if ($model->getCoupon($data)) {
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
/*{{{ page 领取界面 */
	public function get(){
        $out = array();

        if (!$this->app->config('debug')) {
            $model_wechat = new \Next\Helper\Wechat();
            $attention = $model_wechat->getUserInfo($this->user['openid']);
            $out['need_attention']=1;
            if ($attention && $attention['subscribe'] == '1')  {
                $out['need_attention']=0;
            }
        }

        $param = array(
            'coupon' => $this->app->request->get('coupon'),
            'from' => $this->app->request->get('f'),
        );
		$model = new \app\model\Coupon($this->app);
		$coupon = $model->loadByCoupon($param);
		if ($coupon) {
			//已被领取
			if ($coupon['to_user'] > 0) {
				if ($coupon['to_user'] == $this->user['id'] && !$coupon['is_used']) {
					$out['data']['use_coupon']=$coupon['id'];
				}elseif ($coupon['to_user'] == $this->user['id'] && $coupon['is_used'] == '1'){
					$out['notice']='您已经兑换过赠券了！';
				}else{
					$out['notice']='来晚了，已经是别人的了！';
				}
			}else{
				//未被领取
				//验证用户是否已经领取过
				$user_coupon = $model->checkIsReceive($this->user['id']);
				if(!empty($user_coupon)){
					$out['notice']='您已经领过一次了！';
				}elseif($coupon['from_user']==$this->user['id']){
					$out['notice']='不可以领取自己赠送的赠券哦！';
				}else{
					$out['data']['get_coupon'] = $coupon['id'];
				}
			}
		}else{
			$out['notice']='无效或已过期的赠券！';
		}
		$out['data']['from'] = $param['from'];
		$out['data']['to'] = $this->user['id'];
		$this->display('site/coupon_get.html', $out);
	}
/*}}}*/
/*{{{ action 领取 */
	public function draw(){
		$model = new \app\model\Coupon($this->app);
		$model_user = new \app\model\User();
		$user = $model_user->loadById($this->user['id']);
		$post = $this->app->request->post('data');
		$data['coupon'] = array(
			'edit' => array(
				'to_user' => $this->user['id'],
				'get_time*f' => 'now()',
				'updated*f' => 'now()',
			),
			'where'=>array(
				'id' => $post['id'],
				'from_user' => $post['from'],
			)
		);
		if (!$user['farther_id']) {
        	$data['user']['edit']['farther_id'] = $post['from'];
        	$data['user']['edit']['updated*f'] = 'now()';
        	$data['user']['where']['id'] = $user['id'];
        }
		if ($model->getCoupon($data)) {
			$out['status'] = 200;
		} else {
			$out['status'] = 400;
		}
		$this->rendJSON($out);
	}
    /*}}}*/

	/* {{{ page 兑换并领水 */
	public function exchange(){
		$this->step1();
	}

	private function buyData($key = null, $val = null) {
		$data = $this->app->session->get('coupon_data');
		if (!isset($data)) {
			$data = array();
			$this->app->session->set('coupon_data', $data);
		}

		if (!$key) {
			return $data;
		}

		if (!$val) {
			return isset($data[$key])? $data[$key]: null;
		}

		$data[$key] = $val;
		$this->app->session->set('coupon_data', $data);

		return true;
	}
	/*}}}*/

	/*{{{ step1 */
	public function step1() {
		$model_coupon = new \app\model\Coupon($this->app);
		$out = array();
		$coupon = $model_coupon->checkIsReceive($this->user['id']);
		if ($coupon) {
			if ($coupon['is_used']=='1' || $coupon['expired']=='1' || strtotime($coupon['validity_time']) < time()) {
				$coupon = array();
			}
		}
		$out['coupon'] = $coupon;

		if (!$this->app->request->params('back')) {
			// Reset session
			$this->app->session->del('coupon_data');
		}

		$model = new \app\model\Product();
		$out['good'] = $model->loadGift();

		$modelArticle = new \app\model\Article();
		$out['article'] = $modelArticle->loadOneByType('detail');

		$this->display('site/coupon_exchange_step1.html', $out);
	}
	/*}}}*/

	/*{{{ step2 */
	public function step2() {
		$out = array();
		if ($tmp = $this->app->request->params('good')) {
			$this->buyData('good', $tmp);
		}
		if ($tmp = $this->app->request->params('coupon')) {
			$this->buyData('coupon', $tmp);
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
		$this->display('site/coupon_exchange_step2.html', $out);
	}
	/*}}}*/

	/*{{{ step3 */
	public function step3() {
		$out = array();
		if ($tmp = $this->app->request->params('address')) {
			$this->buyData('address', $tmp);
		}
		$out['data'] = $this->buyData();
		$model = new \app\model\Product();
		$good = $model->loadByIdForSite($out['data']['good']);
		$out['good_num'] = $good['box_num'];
		//不可配送日期
		$model_shiptime = new \app\model\Shiptime($this->app);
		$out['except_date'] = $model_shiptime->loadMonth();
		//配送日期
		$now = time();
		if (date('H:i:s', $now) > '16:00:00') {
			$out['start_time'] = strtotime('+2 day', $now);
			$out['end_time'] = strtotime('+32 day', $now);
		} else {
			$out['start_time'] = strtotime('+1 day', $now);
			$out['end_time'] = strtotime('+31 day', $now);
		}

		$this->display('site/coupon_exchange_step3.html', $out);
	}
	/*}}}*/

	/*{{{ step4 */
	public function step4() {
		$out = array();
		if ($tmp = $this->app->request->params('num')) {
			$this->buyData('num', $tmp);
		}
		if ($tmp = $this->app->request->params('date')) {
			$this->buyData('date', $tmp);
		}

		$out['data'] = $this->buyData();
		$model_user = new \app\model\User($this->app);
		$out['user'] = $model_user->loadById($this->user['id']);

		$model_good = new \app\model\Product();
		$out['good'] = $model_good->loadByIdForSite($out['data']['good']);
		$this->display('site/coupon_exchange_step4.html', $out);
	}
	/*}}}*/
/*{{{ recover */
    public function recover() {
        $out = array();

        $id = $this->app->request->params('id');
        if (!$id || !is_numeric($id)) {
            $out['status'] = 400;
            $out['msg'] = '系统忙，请稍后再试';
            $this->rendJSON($out);
        }

        $model = new \app\model\Coupon();
        if ($model->recover($id, $this->user['id'])) {
            $out['status'] = 200;
            $out['msg'] = '回收成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '回收失败, 分享券或已经被使用，请返回后再查看。';
        }

        $this->rendJSON($out);
    }
/*}}}*/

}

?>
