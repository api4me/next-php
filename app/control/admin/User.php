<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename User.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

use Next\Core\Model;
class User extends \Next\Core\Control {
/*{{{ index */
    public function index() {
        $out = array();
        // Default
        $param = array(
            'status' => 1,
        	'type' => null,
            'name' => null,
        	'integral' => null,
        	'have_water' => null,
        );
        $post = $this->app->request->post();
        if ($post) {
            $param['status'] = $post['status'];
            $param['type'] = $post['type'];
            $param['name'] = $post['name'];
            $param['integral'] = $post['integral'];
            $param['have_water'] = $post['have_water'];
            $this->app->session->set('user_search', $param);
            $this->app->session->set('user_page', 1);
            $out['count_show']=1;
        } else {
        	$tmp = $this->app->session->get('user_search');
            if ($tmp) {
                $param = $tmp;
            }
        }
        $out['search'] = $param;

        $model = new \app\model\User($this->app);
        $page = 1;
        $getpage = $this->app->request->get('page');
        if ($getpage > 0) {
        	$this->app->session->set('user_page', $getpage);
        	$page = $getpage;
        } else{
        	$getpage = $this->app->session->get('user_page');
        	if ($getpage>0) {
        		$page = $getpage;
        	}
        }
        $pagination = $this->app->config('pagination');
        $start = ($page-1)*$pagination['per_page'];
        if ($tmp = $model->loadAll($param,$start,$pagination['per_page'])) {
            $out['data'] = $tmp;
            $user_ids = array();
            foreach ($out['data']['user'] as $key => $_user){
                $user_ids[] = $_user['id'];
                $out['data']['user'][$key]['coupon'] = 0;
            }
            $out['coupon'] = $model->loadUserCouponSum($user_ids);
            foreach ($out['coupon'] as $key => $_coupon){
                $out['data']['user'][$key]['coupon'] = $_coupon;
            }
            $config = array(
                'total' => $out['data']['count'],
                'url' => '/admin/user/index/?page=',
                'page' => $page,
                'per_page' => $pagination['per_page'],
            );
            // Generate pagination
            $pagination = new \Next\Helper\Pagination($config);
            $out['pagination'] = $pagination->get_links();
        }

        $this->display('admin/user_index.html', $out);
    }
/*}}}*/
/*{{{ see */
    public function see() {
    	$out = array();
    	$model = new \app\model\User($this->app);
    	$model_coupon = new \app\model\Coupon($this->app);
    	$area = new \app\model\Area($this->app);
    	$userid = $this->app->request->get('id');
		$out['user'] = $model->loadById($userid);
		//获取用户的推荐人
		$out['farther'] = $model->loadUserFather($out['user']['farther_id']);
		//获取用户发展的下属会员
		$out['children'] = $model->loadUserChild($userid);
		//获取用户的收货信息
		$out['address'] = $model->loadUserAddress($userid);
		foreach ($out['address'] as $key=>$_address){
			$out['address'][$key]['area'] = $area->loadAll($_address['area']);
		}

		$out['couponinfo'] = $model_coupon->loadUserCoupon($userid);
		$out['recycle'] = $model_coupon->loadUserRecycleCoupon($userid);
		$out['couponinfo']['recycle'] = count($out['recycle']);
		if (empty($out['couponinfo']['expire'])) {
			$out['couponinfo']['expire']=0;
		}
		if (empty($out['couponinfo']['invalid'])) {
			$out['couponinfo']['invalid']=0;
		}
		if (empty($out['couponinfo']['available'])) {
			$out['couponinfo']['available']=0;
		}

    	$this->display('admin/user_see.html', $out);
    }
/*}}}*/
/*{{{ integral */
    public function integral() {
    	$out = array();
    	$model = new \app\model\User($this->app);
    	$userid = $this->app->request->get('id');
    	$out['data'] = $model->loadUserIntegral($userid);
    	$out['uid'] = $userid;
    	$this->display('admin/integral.html', $out);
    }
/*}}}*/
/*{{{ 用户升级 */
    public function lvUp() {
    	$model = new \app\model\User($this->app);
    	$user['id'] = $this->app->request->post('id');
        $couponNum = 0;
        $append = 0;
        if ($tmp = $model->loadById($user['id'])) {
            if ($couponNum - $tmp['coupon'] > 0) {
                $append = $couponNum - $tmp['coupon'];
            } else {
                $couponNum = $tmp['coupon'];
            }
        }
    	$edit=array(
    		'type' => '3',
            'coupon*f' => 'coupon'+0,
    		'updated*f' => 'now()',
    	);
    	if($model->editUser($edit, $user, 0)){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ 用户降级 */
    public function lvDown() {
    	$user['id'] = $this->app->request->post('id');

        $type = 0;
        $orderModel = new \app\model\Order();
        if ($tmp = $orderModel->loadByUserIdForDown($user['id'])) {
            $type = 1;
            foreach ($tmp as $val) {
                if ($val['is_normal']) {
                    $type = 2;
                    break;
                }
            }
        }

    	$edit = array(
            'type' => $type,
            'updated*f' => 'now()',
    	);
    	$model = new \app\model\User();
    	if($model->editUser($edit,$user)){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ 拉黑用户 */
    public function blackList() {
    	$model = new \app\model\User($this->app);
    	$user['id'] = $this->app->request->post('id');
    	$edit=array(
    			'status'=>'2',
    			'updated*f'=>'now()',
    	);
    	if($model->editUser($edit,$user)){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{将用户移除黑名单 */
    public function whiteList() {
    	$model = new \app\model\User($this->app);
    	$user['id'] = $this->app->request->post('id');
    	$edit=array(
    			'status'=>'1',
    			'updated*f'=>'now()',
    	);
    	if($model->editUser($edit,$user)){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{修改用户coupon */
    public function couponEdit() {
    	$model = new \app\model\User($this->app);
    	$post = $this->app->request->post('data');
    	$edit=array(
    		'coupon'=>$post['coupon'],
    		'updated*f'=>'now()',
    	);
    	$user['id'] = $post['id'];
    	if($model->editUser($edit,$user,$post['addnum'])){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
/*}}}*/
    /*{{{修改用户备注 */
    public function nicknameEdit() {
    	$model = new \app\model\User($this->app);
    	$post = $this->app->request->post('data');
    	$edit=array(
    			'nickname'=>$post['nickname'],
    			'updated*f'=>'now()',
    	);
    	$user['id'] = $post['id'];
    	if($model->editUser($edit,$user)){
    		$out['status'] = 200;
    	}else{
    		$out['status'] = 400;
    	}
    	$this->rendJSON($out);
    }
    /*}}}*/
    private function export($param){
    	$model_export = new \app\model\Export($this->app);
    	$model_user = new \app\model\User($this->app);
    	$users = $model_user->export($param);
    	$user_type = array(
        	"-1"=> '其他',
			"0" => '关注好友',
			"1" => '体验好友',
			"2" => '会员',
			"3" => '创始会员',
        );
    	$filename = 'users';
    	//获取用户的推荐人、推荐好友数量，以及用户的赠券详情
    	$all_users = $model_user->loadAllUsers();
    	$father_child = array();
    	foreach ($all_users as $_user){
    		$father_child[$_user['father']][] = $_user;
    	}
    	$header = array('编号','用户名/ID','会员类型','购水总量','已送水量','持水量','获得的分享券',
    			'有效的分享券','发放的分享券','积分','推荐人','推荐好友数量','体验装购买与否','是否使用分享券');
    	foreach ($users as $key=>$_user){
    		$datas[$key][] = $key+1;//序号
    		$datas[$key][] = $_user['name'].'('.str_pad($_user['id'],7,'0',STR_PAD_LEFT).')';//用户名/ID
    		$datas[$key][] = isset($user_type[$_user['type']])?$user_type[$_user['type']]:'其他';//会员类型
    		$datas[$key][] = $_user['total_water'];//购水总量
    		$datas[$key][] = $_user['total_water']-$_user['have_water'];//已送水量
    		$datas[$key][] = $_user['have_water'];//持水量
//     		$datas[$key][] = $_user[''];//
//     		$datas[$key][] = $_user[''];//
//     		$datas[$key][] = $_user[''];//
    		$datas[$key][] = $_user['integral'];//积分
    		$datas[$key][] = isset($all_users[$_user['farther_id']])?$all_users[$_user['farther_id']]['name']:'';//推荐人
    		$datas[$key][] = isset($father_child[$_user['id']])?count($father_child[$_user['id']]):0;//推荐好友数量
    		$datas[$key][] = $_user['used_experience']==1?'是':'否';//体验装购买与否
    		$datas[$key][] = $_user['used_coupon']==1?'是':'否';//是否使用分享券
    	}
    	print_r($datas);die();
    	$makefile = $model_export->doExcelArr($datas,$header,$filename);
    	if ($makefile) {
    		return $makefile;
    	}else{
    		return false;
    	}
    }
}
