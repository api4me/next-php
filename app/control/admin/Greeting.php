<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Greeting.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Greeting extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $out = array();
        $model = new \app\model\Message();
        $out['data'] = $model->loadForSubscribe();
        $this->display('admin/greeting.html', $out);
    }
/*}}}*/
/*{{{ save */
    public function save() {
        $out = array();
        $model = new \app\model\Message();
        $data = $this->app->request->post('data');
        if ($tmp = $model->saveForSubscribe($data)) {
            $out['status'] = 200;
            $out['msg'] = '保存成功';
            $out['data']['id'] = $tmp;
        } else {
            $out['status'] = 400;
            $out['msg'] = '保存失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ del */
    public function del() {
        $out = array();
        $model = new \app\model\Message();
        if ($model->delForSubscribe()) {
            $out['status'] = 200;
            $out['msg'] = '删除成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '删除失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/

}
