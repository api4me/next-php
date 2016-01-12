<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Control.php
* @touch date Sat 10 May 2014 03:05:37 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Core;

abstract class Control{

/*{{{ variable*/
    protected $app;
    protected $common;
    protected $view;
/*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct() {
        $this->app = \Slim\Slim::getInstance();
        $this->common = array(
            "config" => $this->app->config('common'),
            "user" => $this->app->session->get("user"),
            "uri" => $this->app->request->getPathInfo(),
        );    

        $this->view = new \Next\Helper\Twig();
    }
/*}}}*/
/*{{{ render */
    public function render($template, $data = array()) {
        if (!isset($data['common'])) {
            $data['common'] = $this->common;
        }
        return $this->view->render($template, $data);
    }
/*}}}*/
/*{{{ display */
    public function display($template, $data = array()) {
        if (!isset($data['common'])) {
            $data['common'] = $this->common;
        }
        $this->view->display($template, $data);
    }
/*}}}*/
/*{{{ rendJSON */
    public function rendJSON($data) {
        $this->app->response->headers->set('Content-Type', 'application/json');
        $this->app->response->write(json_encode($data));
        $this->app->stop();
    }
/*}}}*/

}
?>

