<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Control.php
* @touch date Mon 07 Dec 2015 11:55:20 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Console;

class Control{

    private $option;
/*{{{ __construct */
    public function __construct($option) {
        $this->option = $option;
    }
/*}}}*/
/*{{{ run */
    public function run() {
        $out = array();

        $help = new \Next\Console\Helper();

        $c = ucfirst($this->option["c"]);
        $m = ucfirst($this->option["m"]);
        $out["data"] = array(
            "date" => date("D d M Y H:i:s A T"),
            "module" => $this->option["a"],
            "file" => $c,
            "control" => $c,
            "model" => $m,
            "table" => $help->columns($this->option["t"]),
        ); 

        $content = $help->rend("control.html", $out);
        $path = sprintf("./app/control/%s/%s.php", $this->option["a"], $c);

        return $help->write($path, $content);
    }
/*}}}*/

}

?>
