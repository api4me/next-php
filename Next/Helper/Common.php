<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Common.php
* @touch date Thu 08 May 2014 02:34:25 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Common {

/*{{{ variable*/
    private $app;
    private $config;
/*}}}*/
/*{{{ construct */
    /**
     * Constructor
     * @param  object  $app
     */
    public function __construct() {
        $this->app = \Slim\Slim::getInstance();
        $config = $this->app->config('common');
    }
/*}}}*/
/*{{{ md5 */
    public function md5($name, $salt = '') {
        return \md5($name.$salt);
    }
/*}}}*/
/*{{{ encryptPwd */
    public function encryptPwd($name) {
        return $this->md5($name, $this->config['salt']);
    }
/*}}}*/
/*{{{ genRandomString */
    /**
     * 生成随机串
     * @param unknown $len
     * @return string
     */
    public function genRandomString($len) {
        $chars = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
            'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w',
            'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5',
            '6', '7', '8', '9'
        );
        $charsLen = count($chars) - 1;
        shuffle($chars);    // 将数组打乱
        $output = "";
        for ($i=0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }
/*}}}*/
/*{{{ unique */
    public function unique($str, $salt = '') {
        return substr($this->md5($str, $salt), 8, 16);
    }
/*}}}*/
/*{{{ uuid */
    public function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
/*}}}*/

/*{{{ sign */
    public function sign($data, $key) {
        if (!is_array($data)) {
            return false;
        }

        // Clone data
        $d = $data;
        unset($d["sign"]);

        ksort($d);
        $str = array();
        foreach ($d as $k => $v) {
            $str[] = sprintf("%s=%s", $k, $v);
        }
        return strtolower(md5(sprintf('%s&key=%s', implode("&", $str), $key)));
    }
/*}}}*/
/*{{{ encode */
    public function encode($data, $key = "") {
        if (!is_array($data)) {
            return false;
        }
        $data["sign"] = $this->sign($data, $key);

        return base64_encode(json_encode($data));
    }
/*}}}*/
/*{{{ decode */
    public function decode($str) {
        return json_decode(base64_decode($str), true);
    }
/*}}}*/

}
