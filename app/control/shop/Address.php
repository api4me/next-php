<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Address.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Address extends \Next\Core\Control {
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
    	$model_area = new \app\model\Area($this->app);
        $model_user = new \app\model\User($this->app);
        $all_address = $model_user->loadUserAddress($this->user['id']);
        //使用地址按钮信息
        $self = $this->app->request->get('self');
        $back = $this->app->request->get('back');
        if ($self != 1 && !empty($back)) {
	        $back_url = $this->app->request->get('back');
	        $back_id = $this->app->request->get('id');
	        $session_button['url'] = $back_url;
	        $session_button['id'] = $back_id;
	        $this->app->session->set('button',$session_button);
        }
        $out['button'] = $this->app->session->get('button');
        if (count($all_address)>0) {
        	foreach ($all_address as $key => $address) {
	        	$out['data'][$key] = $address;
	        	$area = $model_area->loadAll($address['area']);
	        	$out['data'][$key]['area'] = $area;
	        }
        }else{
        	$this->app->redirect('/shop/address/add/');
        }

        $this->display('site/address.html', $out);
    }
/*}}}*/
/*{{{ addAddressTable */
    public function add() {
    	$id = $this->app->request->get('id');
    	$out = array();
    	if($id){
    		$model = new \app\model\User($this->app);
    		$address = $model->loadAddressByID($id);
    		$out = $address;
    		$out['province'] = substr($address['area'], 0,2);
    		$out['city'] = substr($address['area'], 2,2);
    		$out['district'] = substr($address['area'], 4,2);
    		$out['is_default'] = $this->user['address_id']==$id?1:0;
    	}
        $this->display('site/add_address.html', $out);
    }
/*}}}*/
/*{{{ changeAddress */
    public function changeAddress() {
        $id = $this->app->request->post('id');
        $user = $this->app->session->get('user');
        $user['address_id'] = $id;
        $this->app->session->set('user',$user);
        $user = $this->app->session->get('user');
    	if ($user['address_id'] == $id) {
    		$out['status'] = 200;
        } else {
            $out['status'] = 400;
        }
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ addAddress */
    public function save() {
        $data = $this->app->request->post('data');
        $model = new \app\model\User($this->app);
        $lib_datas = array(
        	'consignee'=>$data['consignee'],
        	'mobile'=>$data['mobile'],
        	'area'=>$data['province'].$data['city'].$data['district'],
        	'address'=>$data['address'],
        );
        if ($data['id']) {
        	$address['edit']=$lib_datas;
        	$address['edit']['updated*f'] = 'now()';
        	$address['where'] = array(
        		'id'=>$data['id'],
        		'user_id' => $this->user['id'],
        	);
        	$result = $model->editAddress($address,$data['is_default'],$this->user['address_id']);
        }else{
        	$lib_datas['created*f'] = 'now()';
        	$lib_datas['user_id' ]= $this->user['id'];
        	$result = $model->addAddress($lib_datas, $data['is_default']);
        }
        if ($result) {
        	$user = $model->loadById($this->user['id']);
        	$this->app->session->set('user', $user);
            $out['status'] = 200;
        }else {
            $out['status'] = 400;
        }
    	$this->rendJSON($out);
    }
/*}}}*/
/*{{{ delete */
    public function delete() {
        $id = $this->app->request->post('id');
        $model = new \app\model\User($this->app);
        if ($model->delAddress($id)) {
            $out['status'] = 200;
        }else {
            $out['status'] = 400;
        }
    	$this->rendJSON($out);
    }
/*}}}*/
}

?>
