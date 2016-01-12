<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Product.php
* @touch date Sat 10 May 2014 03:54:20 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\admin;

class Product extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $out = array();

        // Default
        $param = array(
            'trashed' => 0,
            'on_sale' => 1,
            'name' => null,
        );
        $post = $this->app->request->post();
        if ($post) {
            if (!isset($post['status'])) {
                $param['on_sale'] = 1; 
            } else {
                switch ($post['status']) {
                    case 'down':
                        $param['on_sale'] = 0;
                        break;
                    case 'trash':
                        $param['trashed'] = 1;
                        break;
                    case 'sale':
                    default:
                        $param['on_sale'] = 1;
                        break;
                }
                $param['status'] = $post['status'];
            }
            $param['name'] = $post['name'];
            $this->app->session->set('product_search', $param);
        } else {
        	$tmp = $this->app->session->get('product_search');
            if ($tmp) {
                $param = $tmp;
            }
        }
        $out['search'] = $param;
        $out['option']['status'] = array(
            'sale' => '在售中',
            'down' => '已下架',
            'trash' => '回收站',
        );
        $model = new \app\model\Product($this->app);
        $out['data'] = $model->loadAll($param);
        $this->display('admin/product_index.html', $out);
    }
/*}}}*/
/*{{{ edit */
    public function edit() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $id = $this->app->request->get('id');
        if ($id) {
            $out['data'] = $model->loadById($id);
        }
        $this->display('admin/product_edit.html', $out);
    }
/*}}}*/
/*{{{ ajax */
    public function ajax() {
        $out = array();
        $model = new \app\model\Product($this->app);

        // Default
        $param = array(
            'trashed' => 0,
            'on_sale' => 1,
            'name' => null,
            'category' => null,
            'is_new' => null,
            'is_promote' => null,
            'is_hot' => null,
        );
        $tmp = $this->app->session->get('product_search');
        if ($tmp) {
            $param = $tmp;
        }

        $out['data'] = $model->loadForSort($param);
        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ save */
    public function save() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $post = array();
        $post = $this->app->request->post('data');
        if (empty($post['id'])) {
        	unset($post['id']);
        	$result = $model->add($post);
        }else{
        	$where['id'] = $post['id'];
        	unset($post['id']);
        	$result = $model->update($post,$where);
        }
        if ($result) {
            $out['status'] = 200;
            $out['data']['id'] = $result;
        } else {
            $out['status'] = 400;
            $out['msg'] = '保存失败';
        }
        $this->rendJSON($out);
    }
/*}}}*/

/*{{{ up */
    public function up() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $data = $this->app->request->post('id');
        if ($model->up($data)) {
            $out['status'] = 200;
            $out['msg'] = '上架成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '上架失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ down */
    public function down() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $data = $this->app->request->post('id');
        if ($model->down($data)) {
            $out['status'] = 200;
            $out['msg'] = '下架成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '下架失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ trash */
    public function trash() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $data = $this->app->request->post('id');
        if ($model->trash($data)) {
            $out['status'] = 200;
            $out['msg'] = '成功移至回收站';
        } else {
            $out['status'] = 400;
            $out['msg'] = '操作失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ restore */
    public function restore() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $data = $this->app->request->post('id');
        if ($model->restore($data)) {
            $out['status'] = 200;
            $out['msg'] = '恢复成功';
        } else {
            $out['status'] = 400;
            $out['msg'] = '恢复失败';
        }

        $this->rendJSON($out);
    }
/*}}}*/
/*{{{ del */
    public function del() {
        $out = array();
        $model = new \app\model\Product($this->app);
        $data = $this->app->request->post('id');
        if ($model->del($data)) {
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
        $model = new \app\model\Product($this->app);
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
/*{{{ 检索商品 */
    public function checkGoods() {
        $out = array();
        $model = new \Next\Helper\Guanyi();
        $check['name'] = $this->app->request->post('name');
        $outdatas = $model->goodsList($check);
        if ($outdatas) {
            $out['status'] = 200;
            $out['msg'] = '排序成功';
            $out['datas'] = $outdatas;
        } else {
            $out['status'] = 400;
            $out['msg'] = '排序失败';
        }
        $this->rendJSON($out);
    }
/*}}}*/
}
