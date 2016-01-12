<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename tool.php
* @touch date Sun 11 Jan 2015 05:57:04 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control;

class Tool extends \Next\Core\Control {
    public function index() {
        $out = array();
        $this->display('tool/dev.html', $out);
    }
}

$app->get('/~/tool/dev', function() use($app) {
    $tool = new \app\control\Tool();
    $tool->index();
    //$app->render('tool/dev.php');
});

$app->post('/~/tool/dev', function() use($app) {
    $control = $app->request->params('control');
    $table = $app->request->params('table');

    // TODO
    // Create control
    // Table
});

?>
