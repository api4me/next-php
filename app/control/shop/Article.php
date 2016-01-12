<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Article.php
* @touch date Mon 30 Jun 2014 03:57:46 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Article extends \Next\Core\Control {

/*{{{ index */
    public function index() {
        $param = array(
            'id' => $this->app->request->get('id'),
            'type' => $this->app->request->get('type', 'article'),
            'sort' => $this->app->request->get('sort', 'desc'),
            'start' => $this->app->request->get('start', 0),
        );
        if (!in_array($param['sort'], array('asc', 'desc'))) {
            $param['sort'] = 'desc';
        }

        $out = array();
        $config = $this->app->config('wechat');
        $out['appid'] = $config['appid'];
        $out['param'] = $param;

        $model = new \app\model\Article();
        if ($param['id']) {
            $out['backurl'] = sprintf('/shop/article/?type=%s&sort=%s', $param['type'], $param['sort']);
            if ($out['data'] = $model->loadById($param['id'])) {
                $out['data']['short'] = mb_substr(strip_tags($out['data']['content']), 0, 50, 'utf-8');
            }
            $this->display('site/article_detail.html', $out);
            return true;
        }

        // Get data  by type
        if ($tmp = $model->loadByType($param)) {
            $out['data'] = $tmp['data'];
            if ($tmp['total'] > 1) {
                $out['backurl'] = '/shop/home/';
                $this->display('site/article.html', $out);
                return true;
            }

            if ($tmp['total'] == 1) {
                $out['backurl'] = '/shop/home/';
            } else {
                $out['backurl'] = sprintf('/shop/article/?type=%s&sort=%s', $param['type'], $param['sort']);
            }
            $out['data'] = array_shift($out['data']);
            $out['data']['short'] = mb_substr(strip_tags($out['data']['content']), 0, 50, 'utf-8');
            $this->display('site/article_detail.html', $out);
            return true;
        }

        $this->app->redirect('/shop/');
    }
/*}}}*/

}
?>
