<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Matter.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Matter extends \Next\Core\Control {

/*{{{ variable */
    private $type = array('text', 'news');
/*}}}*/
/*{{{ index */
    public function index() {
    }
/*}}}*/
/*{{{ ajax */
    public function ajax() {
        $out = array();
        $type = $this->app->request->get('type');
        if (!in_array($type, $this->type)) {
            $out['status'] = 400;
            $out['msg'] = '参数有误';
            $this->rendJSON($out);
            return false;
        }

        $id = $this->app->request->get('id');
        $model = new \app\model\Matter();
        if ($type == 'text') {
            $out['status'] = 200;
            $out['data'] = $model->loadTextById($id);
        } else {
            $out['status'] = 200;
            $out['data'] = $model->loadNewsById($id);
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ save */
    public function save() {
        $out = array();
        $model = new \app\model\Matter();
        $data = $this->app->request->post('data');

        $type = $this->app->request->post('type');
        $method = $type == 'text' ? 'saveText': 'saveNews';
        if ($tmp = $model->$method($data)) {
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
        $model = new \app\model\Matter($this->app);
        $type = $this->app->request->post('type');
        $data = $this->app->request->post('id');
        $method = $type == 'text' ? 'delText': 'delNews';
        if ($model->$method($data)) {
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
