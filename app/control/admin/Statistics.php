<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Statistics.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

use Next\Core\Model;
use app\control\shop\Coupon;
class Statistics extends \Next\Core\Control {

    public function __construct() {
        parent::__construct();
        $tmp = $this->app->config('export');
        $this->export = $tmp['path'];
    }

	public function index(){
		$out = array();
		$post = $this->app->request->post();
		if ($post) {
			switch ($post['type']){
				case 'user':
					$this->downloadUser($post);
					break;
				case 'order':
					$this->downloadOrder($post);
					break;
				case 'ticket':
					$this->downloadTicket($post);
					break;
			}
		}
		$this->display('admin/statistics_index.html', $out);
	}
	//下载用户统计文件
	private function downloadUser($post){
		$model = $model_export = new \app\model\Export();
		if (empty($post['date']) || strtotime($post['date'])>strtotime(date('Y-m-d',(time()-86400))) ) {
			$post['date'] = date('Y-m-d',(time()-86400));
		}
		$file = $this->export.'user/export-user-'.$post['date'].'.xlsx';
		if(file_exists($file)){
			$model->downloadFile($file);
		}else{
			$data = $this->usersDate();
			$this->writeUserXls($data,'1');
		}
	}
	//下载销售统计文件
	private function downloadOrder($post){
		$model = $model_export = new \app\model\Export();
		if (empty($post['start'])) {
			$start = date('Y-m-01 00:00:00');
			$post['start'] = date('Y-m-01');
		}else{
			$start = $post['start'].' 00:00:00';
		}
		if (empty($post['end'])) {
			$end = date('Y-m-d 23:59:59');
			$post['end'] = date('Y-m-d');
		}else{
			$end = $post['end'].' 23:59:59';
		}
		$file = $this->export.'order/'.date('Y.m.d',strtotime($post["start"])).'-'.date('Y.m.d',strtotime($post["end"])).'.xlsx';
		if(file_exists($file)){
			$model->downloadFile($file);
		}else{
			$user_type = array(
				"-1"=> '其他',
				"0" => '关注好友',
				"1" => '体验好友',
				"2" => '会员',
				"3" => '创始会员',
			);
			$orders = $model->loadOrderNow($start, $end);
			foreach ($orders as $key=>$_order){
				$data[] = array(
					'user_id' => $_order['user_id'],
					'user_name' => $_order['user_name'],
					'user_type' => $user_type[$_order['user_type']],
					'total_money' => $_order['total_fee'],
					'goods_num' => $_order['goods_num'],
					'refund_money' => empty($_order['refund_amount'])?0:$_order['refund_amount'],
					'use_integral' => $_order['use_integral'],
				);
			}
			$this->writeOrderXls($data, $post['start'], $post['end'],1);
		}
	}
	//下载券统计文件
	private function downloadTicket($post){
		$model = $model_export = new \app\model\Export();
		if (empty($post['date']) || strtotime($post['date'])>strtotime(date('Y-m-d',(time()-86400))) ) {
			$post['date'] = date('Y-m-d',(time()-86400));
		}
		$file = $this->export.'ticket/export-ticket-'.$post['date'].'.xlsx';
		if(file_exists($file)){
			$model->downloadFile($file);
		}else{
			$data = $model->loadTicket($post['date']);
			$this->writeTicketXls($data,'1');
		}
	}
	/**
	 * 将之前的订单全部刷入数据库
	 */
	public function brush(){
        die(); // Only run once

		$model_export = new \app\model\Export();
		$model_order = new \app\model\Order();
		$model_user = new \app\model\User();
		$user_type = array(
			"-1"=> '其他',
			"0" => '关注好友',
			"1" => '体验好友',
			"2" => '会员',
			"3" => '创始会员',
		);
		$orders = $model_order->brushOrder();//获取一天的订单
		$data = array();
		if (count($orders)>0) {
			$user_ids = array();
			foreach ($orders as $_order){
				$user_ids[] = $_order['user_id'];
			}
			$users = $model_user->loadUsersType($user_ids);
			foreach ($orders as $key=>$_order){
				$data[] = array(
					'user_id' => $_order['user_id'],
					'user_name' => $_order['user_name'],
					'user_type' => $user_type[$users[$_order['user_id']]],
					'total_money' => $_order['total_fee'],
					'goods_num' => $_order['goods_num'],
					'refund_money' => empty($_order['refund_amount'])?0:$_order['refund_amount'],
					'use_integral' => $_order['use_integral'],
					'created' => $_order['created'],
				);
			}
			if (!$model_export->writeOrderDb($data)) {
				$this->app->log->error('录入数据库失败');
				die('system error -->insert mysql ');
			}
		}
	}
	/**
	 * 订单信息整合
	 */
	public function order(){
		$model_export = new \app\model\Export();
		$model_order = new \app\model\Order();
		$model_user = new \app\model\User();
		$user_type = array(
			"-1"=> '其他',
			"0" => '关注好友',
			"1" => '体验好友',
			"2" => '会员',
			"3" => '创始会员',
		);
		$orders = $model_order->exportStatisticalOrder();//获取一天的订单
		$data = array();
		if (count($orders)>0) {
			$user_ids = array();
			foreach ($orders as $_order){
				$user_ids[] = $_order['user_id'];
			}
			$users = $model_user->loadUsersType($user_ids);
			foreach ($orders as $key=>$_order){
				$data[] = array(
					'user_id' => $_order['user_id'],
					'user_name' => $_order['user_name'],
					'user_type' => $user_type[$users[$_order['user_id']]],
					'total_money' => $_order['total_fee'],
					'goods_num' => $_order['goods_num'],
					'refund_money' => empty($_order['refund_amount'])?0:$_order['refund_amount'],
					'use_integral' => $_order['use_integral'],
					'created' => $_order['created'],
				);
			}
			if (!$model_export->writeOrderDb($data)) {
				$this->app->log->error('录入数据库失败');
				die('system error -->insert mysql ');
			}
		}
	}
	/**
	 * 生成订单统计文件
	 * @param unknown $data
	 * @param unknown $download
	 * @return Ambigous <boolean, string>|boolean
	 */
	private function writeOrderXls($data,$start_date,$end_date,$download="1"){
		$model_export = new \app\model\Export($this->app);
		if (empty($start_date)) {
			$start_date = date("Y-m-01");
		}
		if (empty($end_date)) {
			$start_date = date("Y-m-d");
		}
		$first_line = array('开始日期',$start_date,'结束日期',$end_date);
		$filename = $this->export.'order/'.date('Y.m.d',strtotime($start_date)).'-'.date('Y.m.d',strtotime($end_date)).'.xlsx';
		$header = array('编号', '用户名', '会员类型', '销售金额', '购水量', '核定退款金额', '使用积分');
		$datas = array();
		$i=0;
		$users = array();
		$money = 0;
		$water = 0;
		$refund = 0;
		$integral = 0;
		foreach ($data as $order){
			$datas[$i][] = '`'.str_pad($order['user_id'],7,'0',STR_PAD_LEFT);;
			$datas[$i][] = $order['user_name'];
			$datas[$i][] = $order['user_type'];
			$datas[$i][] = $order['total_money']/100;
			$money = $money+$order['total_money'];//总金额
			$datas[$i][] = $order['goods_num'];
			$water = $water+$order['goods_num'];//总水量
			$datas[$i][] = $order['refund_money']/100;
			$refund = $refund+$order['refund_money'];//总退款金额
			$datas[$i][] = $order['use_integral'];
			$integral = $integral+$order['use_integral'];//总使用积分
			if (!in_array($order['user_id'], $users)) {
				$users[] = $order['user_id'];
			}
			$i++;
		}
		$datas[$i]=array('人数统计',count($users),'',$money/100,$water,$refund/100,$integral);
		$makefile = $model_export->doExcelArr($datas,$header,$filename,$first_line,$download);
		if ($makefile) {
			return $makefile;
		}else{
			return false;
		}
	}
	/**
	 * 券统计刷入整合
	 */
	public function ticket(){
		$model_export = new \app\model\Export();
		$model_coupon = new \app\model\Coupon();
		$coupon = $model_coupon->allCouponOrderUsers();
		$coupon_count = count($coupon);//coupon已领取数量
		$gift = $model_export->usedGiftNums();//礼品码已领
		$data = array();
		$data['coupon']=array(
			'type'=>'coupon',
			'num'=>$coupon_count,
			'created'=>date('Y-m-d',time()-86400),
		);
		$data['gift']=array(
			'type'=>'gift',
			'num'=>$gift,
			'created'=>date('Y-m-d',time()-86400),
		);
		if ($model_export->writeTicketDb($data)) {
			$this->writeTicketXls($data,'0');
    	}else{
    		$this->app->log->error('录入数据库失败');
    		die('system error -->insert mysql ');
    	}
	}
	/**
	 * 生成券统计文件
	 * @param unknown $data
	 * @param unknown $download
	 * @return Ambigous <boolean, string>|boolean
	 */
	private function writeTicketXls($data,$download){
		$model_export = new \app\model\Export($this->app);
		$first_line = array('统计日期'.date('Y-m-d',(time()-86400)));
		$filename = $this->export.'ticket/export-ticket-'.date('Y-m-d',(time()-86400)).'.xlsx';
		$header = array('种类', '已领取');
		$datas = array();
		$i=0;
		foreach ($data as $ticket){
			$datas[$i][] = $ticket['type']=="gift"?'礼品券':'分享券';
			$datas[$i][] = $ticket['num'];
			$i++;
		}
		$makefile = $model_export->doExcelArr($datas,$header,$filename,$first_line,$download);
		if ($makefile) {
			return $makefile;
		}else{
			return false;
		}
	}
	/**
	 * 用户统计信息整合
	 */
    public function users() {
    	ini_set('memory_limit','512M');
    	$model_export = new \app\model\Export();
    	$model_user = new \app\model\User();
    	$model_coupon = new \app\model\Coupon();
    	$all_users = $model_user->loadAllUsersForExport();//获取所有用户
    	$total_coupon_num = $model_coupon->allUsersGetCoupon();//用户领取的coupon数量
    	$total_residue_coupon_num = $model_coupon->allUsersResidueCoupon();//用户剩余有效的coupon数量
    	$total_issue_coupon_num = $model_coupon->allUsersIssueCoupon();//用户发放的coupon数量
    	$total_child_num = $model_user->allUsersChildrenNum();//用户发展的子会员的数量
    	$all_coupon_user = $model_coupon->allUsedCouponUsers();//所有使用过coupon的用户
    	$all_order_coupon_user = $model_coupon->allCouponOrderUsers();//所有成功兑换coupon的用户
    	foreach ($all_coupon_user as $key=>$_u){
    		if (!in_array($key, $all_order_coupon_user)) {
    			unset($all_coupon_user[$key]);
    		}
    	}
    	$user_issue_used_coupon = array();//用户发放并被成功使用的赠券数
    	foreach ($all_coupon_user as $_user){
    		if (isset($user_issue_used_coupon[$_user])){
    			$user_issue_used_coupon[$_user]+=1;
    		}else{
    			$user_issue_used_coupon[$_user] = 1;
    		}
    	}
    	$data = array();
    	$type = array(
    		"-1"=> '其他',
			"0" => '关注好友',
			"1" => '体验好友',
			"2" => '会员',
			"3" => '创始会员',
    	);
    	foreach ($all_users as $key=>$_user){
    		$user = array(
    			'0'=>'`'.str_pad($_user['id'],7,'0',STR_PAD_LEFT),
    			'1'=>empty($_user['nickname'])?$_user['name']:$_user['nickname'],
    			'2'=>$type[$_user['type']],
    			'3'=>$_user['total_water'],
    			'4'=>$_user['total_water']-$_user['have_water'],
    			'5'=>$_user['have_water'],
    			'6'=>isset($total_coupon_num[$_user['id']])?$total_coupon_num[$_user['id']]:0,
    			'7'=>isset($total_residue_coupon_num[$_user['id']])?$total_residue_coupon_num[$_user['id']]:0,
    			'8'=>isset($total_issue_coupon_num[$_user['id']])?$total_issue_coupon_num[$_user['id']]:0,
    			'9'=>isset($user_issue_used_coupon[$_user['id']])?$user_issue_used_coupon[$_user['id']]:0,
    			'10'=>$_user['integral'],
    			'11'=>isset($all_users[$_user['farther_id']])?$all_users[$_user['farther_id']]['name']:'',
    			'12'=>isset($total_child_num[$_user['id']])?$total_child_num[$_user['id']]:0,
    			'13'=>$_user['used_experience']=='1'?'是':'否',
    			'14'=>$_user['used_coupon']=='1'?'是':'否',
    		);
    		$data[] = $user;
    	}
    	$this->writeUserXls($data,'0');
    }
    /**
     * 用户统计信息整合
     */
    private function usersDate() {
    	ini_set('memory_limit','512M');
    	$model_export = new \app\model\Export();
    	$model_user = new \app\model\User();
    	$model_coupon = new \app\model\Coupon();
    	$all_users = $model_user->loadAllUsersForExport();//获取所有用户
    	$total_coupon_num = $model_coupon->allUsersGetCoupon();//用户领取的coupon数量
    	$total_residue_coupon_num = $model_coupon->allUsersResidueCoupon();//用户剩余有效的coupon数量
    	$total_issue_coupon_num = $model_coupon->allUsersIssueCoupon();//用户发放的coupon数量
    	$total_child_num = $model_user->allUsersChildrenNum();//用户发展的子会员的数量
    	$all_coupon_user = $model_coupon->allUsedCouponUsers();//所有使用过coupon的用户
    	$all_order_coupon_user = $model_coupon->allCouponOrderUsers();//所有成功兑换coupon的用户
    	foreach ($all_coupon_user as $key=>$_u){
    		if (!in_array($key, $all_order_coupon_user)) {
    			unset($all_coupon_user[$key]);
    		}
    	}
    	$user_issue_used_coupon = array();//用户发放并被成功使用的赠券数
    	foreach ($all_coupon_user as $_user){
    		if (isset($user_issue_used_coupon[$_user])){
    			$user_issue_used_coupon[$_user]+=1;
    		}else{
    			$user_issue_used_coupon[$_user] = 1;
    		}
    	}
    	$data = array();
    	$type = array(
    			"-1"=> '其他',
    			"0" => '关注好友',
    			"1" => '体验好友',
    			"2" => '会员',
    			"3" => '创始会员',
    	);
    	foreach ($all_users as $key=>$_user){
    		$user = array(
    			'0'=>'`'.str_pad($_user['id'],7,'0',STR_PAD_LEFT),
    			'1'=>empty($_user['nickname'])?$_user['name']:$_user['nickname'],
    			'2'=>$type[$_user['type']],
    			'3'=>$_user['total_water'],
    			'4'=>$_user['total_water']-$_user['have_water'],
    			'5'=>$_user['have_water'],
    			'6'=>isset($total_coupon_num[$_user['id']])?$total_coupon_num[$_user['id']]:0,
    			'7'=>isset($total_residue_coupon_num[$_user['id']])?$total_residue_coupon_num[$_user['id']]:0,
    			'8'=>isset($total_issue_coupon_num[$_user['id']])?$total_issue_coupon_num[$_user['id']]:0,
    			'9'=>isset($user_issue_used_coupon[$_user['id']])?$user_issue_used_coupon[$_user['id']]:0,
    			'10'=>$_user['integral'],
    			'11'=>isset($all_users[$_user['farther_id']])?$all_users[$_user['farther_id']]['name']:'',
    			'12'=>isset($total_child_num[$_user['id']])?$total_child_num[$_user['id']]:0,
    			'13'=>$_user['used_experience']=='1'?'是':'否',
    			'14'=>$_user['used_coupon']=='1'?'是':'否',
    		);
    		$data[] = $user;
    	}
    	return $data;
    }
    /**
     * 用户信息写入文件
     * @param unknown $data
     * @param unknown $download
     * @return Ambigous <boolean, string>|boolean
     */
    private function writeUserXls($data,$download){
    	$model_export = new \app\model\Export($this->app);

     	$first_line = array('统计日期',date('Y-m-d',(time()-86400)));
     	$filename = $this->export.'user/export-user-'.date('Y-m-d',(time()-86400)).'.xlsx';
     	$header = array('编号', '用户名', '会员类型', '购水总量', '已送水量', '持水量', '获得的分享券', '剩余的有效分享券',
     			 '发放的分享券', '发放被使用的分享券', '积分', '推荐人', '推荐好友数量', '体验装购买与否', '是否使用分享券');
        $datas = $data;
        $makefile = $model_export->doExcelArr($datas,$header,$filename,$first_line,$download);
        if ($makefile) {
        	return $makefile;
        }else{
        	return false;
        }
    }
}
