<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename home.php
* @touch date Wed 07 May 2014 02:23:50 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace app\control\shop;

class Home extends \Next\Core\Control {

	/*{{{ construct */
	public function __construct() {
		parent::__construct();
	}
	/*}}}*/
/*{{{ index */
    public function index() {
        $out = array();
        $this->display('site/home.html', $out);
    }
/*}}}*/
}

?>
