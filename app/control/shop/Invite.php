<?php
/**
* @filename Invite.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Invite extends \Next\Core\Control {

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
    	$model = new \app\model\User($this->app);
        $out = array();
        $out['user'] = $model->loadById($this->user['id']);
        $out['children'] = $model->loadUserChild($this->user['id']);
        $this->display('site/invite.html', $out);
    }
/*}}}*/
/*{{{ commit */
	public function commit(){
		$post = $this->app->request->post('invite');
		//step0  查看是否是自己的邀请码
		if ($this->user['invite'] == $post) {
			$out['status'] = 400 ;
			$out['msg'] = '不可以用自己的邀请码哦！';
			$this->rendJSON($out);
		}
		//step1 检测邀请码是否存在
		$model = new \app\model\User($this->app);
		$user_farther = $model->checkInvite($post);
		if (!$user_farther) {
			$out['status'] = 400 ;
			$out['msg'] = '您填写的邀请码有误，请填写正确的邀请码！';
			$this->rendJSON($out);
		}
		//step2 查看是否已有father_id
		if ($this->user['farther_id'] > 0) {
			$out['status'] = 400 ;
			$out['msg'] = '您已经有邀请人了，不能在填写了！';
			$this->rendJSON($out);
		}
		
		//step3 建立关系并未双方各加一分积分
		$data['user_farther'] = array(
			'edit'=>array(
				'integral*f' =>'integral+1',
			),
			'where'=>array(
				'id'=>$user_farther['id'],
			),
		);
		$data['user'] = array(
			'edit'=>array(
				'farther_id'=>$user_farther['id'],
				'integral*f' =>'integral+1',
			),
			'where'=>array(
				'id'=>$this->user['id'],
			),
		);

        $data['integral_farther'] = array(
            'user_id' => $user_farther['id'],
            'type' => 4, // invite with somebody 
            'num' => 1,
            'remark' => sprintf('邀请好友 %s 成功获得', $this->user['name']),
            'gift_by' => $this->user['id'],
            'created*f' => 'now()',
        );
        $data['integral'] = array (
            'user_id' => $this->user['id'],
            'type' => 4, // invite from sombody 
            'num' => 1,
            'remark' => sprintf('您的好友 %s 邀请成功赠送', $user_farther['name']),
            'gift_by' => $user_farther['id'],
            'created*f' => 'now()',
        );
		if ($model->commitInvite($data)) {
			$user_change = $model->loadById($this->user['id']);
			if ($user_change) {
				$this->app->session->set('user', $user_change);
			}
			$out['status'] = 200 ;
			$out['msg'] = '恭喜您，邀请码验证通过,您和您的邀请人都获得一分积分！';
			$this->rendJSON($out);
		}else{
			$out['status'] = 400 ;
			$out['msg'] = '系统忙，请再试一下！';
			$this->rendJSON($out);
		}
	}
/*}}}*/

}

?>
