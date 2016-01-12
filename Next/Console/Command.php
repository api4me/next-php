<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Command.php
* @touch date Mon 07 Dec 2015 09:32:16 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Console;

class Command {

    private $cmd;
    private $option = array();
/*{{{ run */
    public function run() {
        $argv = $_SERVER['argv'];
        array_shift($argv);

        if (!$argv) {
            echo $this;
            return;
        }

        // Get command
        $cmd = array_shift($argv);
        $cmdArr = array(
            "app" => "App",
            "app:m" => "Model",
            "app:v" => "View",
            "app:c" => "Control",
        );
        if (!array_key_exists($cmd, $cmdArr)) {
            $cmd = "app";
        }

        // Parse option
        $this->parse($argv);

        $cmd = sprintf("\Next\Console\%s", $cmdArr[$cmd]);
        $obj = new $cmd($this->option);

        return $obj->run();
    }
/*}}}*/
/*{{{ __toString */
    public function __toString() {
        $usage = <<<EOF
Na is a tool for generate php source code for next framework.

Usage:

    na command [arguments]

The commands are:

    app            create CURD include control model view
    app:m          create model 
    app:v          create view 
    app:c          create control 

Sample:
    na app --app=shop --control=home --model=user --view=index,edit --table=buz_user
    na app -a=shop -c=home -m=user -v=index,edit -t=buz_user
    na app:c -a=shop -c=home -m=user -v=index,edit -t=buz_user
    na app:m -m=user -t=buz_user
    na app:v -a=shop -c=home -v=index,edit -t=buz_user


EOF;
        return $usage;
    }
/*}}}*/

/*{{{ parse */
    private function parse($argv) {
        if ($argv) {
            foreach ($argv as $val) {
                list($k, $v) = explode("=", $val);
                if (!$k || !$v) {
                    continue;
                }

                if (strpos($k, "--") === 0)  {
                    if ($k = substr($k, 2, 1)) {
                        $this->option[$k] = $v;
                    }
                } else if (strpos($k, "-") === 0) {
                    if ($k = substr($k, 1, 1)) {
                        $this->option[$k] = $v;
                    }
                }
            }
        }
    }
/*}}}*/

}

?>
