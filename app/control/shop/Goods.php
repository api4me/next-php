<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Goods.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Goods extends \Next\Core\Control {

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

        $config = $this->app->config('wechat');
        $out['appid'] = $config['appid'];

        $model = new \app\model\Article();
        if ($out['data'] = $model->loadOneByType('intro')) {
            $out['data']['short'] = mb_substr(strip_tags($out['data']['content']), 0, 50, 'utf-8');
        }

        $this->display('site/goods.html', $out);
    }
/*}}}*/
/*{{{ buy */
    public function buy() {
        $this->step1();
    }
/*}}}*/

/*{{{ buyData */
    private function buyData($key = null, $val = null) {
        $data = $this->app->session->get('buy_data');
        if (!isset($data)) {
            $data = array();
            $this->app->session->set('buy_data', $data);
        }

        if (!$key) {
            return $data;
        }

        if (!$val) {
            return isset($data[$key])? $data[$key]: null;
        }

        $data[$key] = $val;
        $this->app->session->set('buy_data', $data);

        return true;
    }
/*}}}*/
/*{{{ step1 */
    public function step1() {
        $out = array();
        if (!$this->app->request->params('back')) {
            // Reset session
            $this->app->session->del('buy_data');
        }

        $model = new \app\model\Product();
        $out['goods'] = $model->loadListForSite();
        $modelArticle = new \app\model\Article();
        $out['article'] = $modelArticle->loadOneByType('detail');

        $out['data'] = $this->buyData();
        $this->display('site/goods_buy_step1.html', $out);
    }
/*}}}*/
/*{{{ step2 */
    public function step2() {
        $out = array();
        if ($tmp = $this->app->request->params('good')) {
            $this->buyData('good', $tmp);
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
        $this->display('site/goods_buy_step2.html', $out);
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

        $this->display('site/goods_buy_step3.html', $out);
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

        $this->display('site/goods_buy_step4.html', $out);
    }
/*}}}*/

}
?>