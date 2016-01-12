<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Menu.php
* @touch date Sat 07 Jun 2014 09:18:49 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Menu extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $out = array();
        $model = new \app\model\Menu();
        $out['data'] = $model->loadAll();
        $this->display('admin/menu.html', $out);
    }
/*}}}*/
/*{{{ ajax */
    public function ajax() {
        $out = array();
        $model = new \app\model\Menu();

        $id = $this->app->request->get('id');
        $m = $this->app->request->get('m');
        switch ($m) {
            case 'sort':
                $out['data'] = $model->loadForSort($id);
                break;
            case 'edit':
            default:
                if ($id) {
                    $out['data'] = $model->loadById($id);
                }
                break;
        }
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ save */
    public function save() {
        $out = array();
        $model = new \app\model\Menu();
        $data = $this->app->request->post('data');
        if ($tmp = $model->save($data)) {
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
/*{{{ setting */
    public function setting() {
        $out = array();
        $model = new \app\model\Menu();
        $data = $this->app->request->post('data');
        if ($tmp = $model->setting($data)) {
            $out['status'] = 200;
            $out['msg'] = '设定成功';
            $out['data']['id'] = $tmp;
        } else {
            $out['status'] = 400;
            $out['msg'] = '设定失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ del */
    public function del() {
        $out = array();
        $id = $this->app->request->post('id');
        $model = new \app\model\Menu();
        if ($model->del($id)) {
            $out['status'] = 200;
            $out['msg'] = '删除成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '删除失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ sort */
    public function sort() {
        $out = array();
        $model = new \app\model\Menu($this->app);
        $data = $this->app->request->post('data');
        if ($model->sort($data)) {
            $out['status'] = 200;
            $out['msg'] = '排序成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '排序失败';
        }
        $this->rendJSON($out);
    }
/*}}}*/

}
