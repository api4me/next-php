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

class Code{

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

        $n = $this->option["n"];
        $t = $this->option["t"];
        $u = $this->option["u"];

        $f = sprintf("%sVo.%s", $n, $t);

        if (!$data = json_decode(file_get_contents($u), true)) {
            echo "Get data fail\n";
            return false;
        }

        $out["data"] = array(
            "date" => date("D d M Y H:i:s A T"),
            "file" => $f,
            "package" => $this->option["p"],
            "data" => $this->x($n, $data),
        ); 

        $content = $help->rend(sprintf("code_%s.html", $t), $out);
        $path = sprintf("./app/code/%s", $f);

        if (!$help->write($path, $content)) {
            return false;
        }

        return true;
    }
/*}}}*/
/*{{{ x */
    private function x($name, $data) {
        if (isset($data["debug"])) {
            unset($data["debug"]);
        }
        $out = $this->parse($name, $data);
        $out = array_reverse($out);

        $ret = array();
        foreach ($out as $val) {
            list($cls, $var) = explode(";", $val);
            $o = array(
                "class" => substr($cls, 6),
                "var" => array(),
            );
            $tmp = explode("&", substr($var, 4));
            foreach ($tmp as $v) {
                list($n, $t) = explode("=", $v);
                $o["var"][] = array(
                    "type" => $t,
                    "name" => $n,
                );
            }
            $ret[] = $o;
        }
        return $ret;
    }
    private function parse($name, $data) {
        $out = array();

        $var = array();
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $cls = sprintf("%s%s", $name, ucfirst($key));
                if (is_numeric(array_pop(array_keys($val)))) {
                    $out = array_merge($out, $this->parse($cls, $val[0]));
                    $var[] = sprintf("%s=List<%sVo>", $key, $cls);
                    continue;
                }
                $out = array_merge($out, $this->parse($cls, $val));
                $var[] = sprintf("%s=%sVo", $key, $cls);
            }
            $var[] = sprintf("%s=%s", $key, "String");
        }
        $out[] = sprintf("class:%sVo;var:%s", $name, implode("&", $var));

        return $out;
    }
/*}}}*/

}

?>
