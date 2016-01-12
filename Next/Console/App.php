<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename App.php
* @touch date Mon 07 Dec 2015 11:55:20 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Console;

class App{

    private $option;
/*{{{ __construct */
    public function __construct($option) {
        $this->option = $option;
    }
/*}}}*/
/*{{{ run */
    public function run() {
        // Control
        $c = new \Next\Console\Control($this->option);
        $c->run();

        // Model
        $m = new \Next\Console\Model($this->option);
        $m->run();

        // View
        $v = new \Next\Console\View($this->option);
        $v->run();
    }
/*}}}*/

}

?>
