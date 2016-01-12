<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename View.php
* @touch date Mon 07 Dec 2015 11:55:20 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Console;

class View{

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

        $view = explode(",", $this->option["v"]);
        $c = $this->option["c"];
        foreach ($view as $v) {
            if (!in_array($v, array("base", "index", "edit"))) {
                continue;
            }

            $out["data"] = array(
                "date" => date("D d M Y H:i:s A T"),
                "module" => $this->option["a"],
                "file" => $c,
                "control" => $c,
                "table" => $help->columns($this->option["t"]),
            ); 
            $content = $help->rend(sprintf("view_%s.html", $v), $out);
            $path = sprintf("./app/view/%s/%s_%s.html", $this->option["a"], $c, $v);

            if (!$help->write($path, $content)) {
                return false;
            }
        }

        return true;
    }
/*}}}*/

}

?>
