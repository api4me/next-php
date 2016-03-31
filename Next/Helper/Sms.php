<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Sms.php
* @touch date Tue 23 Feb 2016 08:36:51 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Sms {

    /*{{{ variable */
    private $app;
    private $ak;
    private $sk;
    /*}}}*/
    /*{{{ construct */
    public function __construct() {
        if (!extension_loaded('curl')) {
           throw new \RuntimeException('curl module not loaded.');
        }
        $this->app = \Slim\Slim::getInstance();

        $config = $this->app->config('sms');
        $this->ak = $config['ak'];
        $this->sk = $config['sk'];
    }
    /*}}}*/
    /*{{{ send */
    public function send($mobile, $content) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.sms8080.com/smssend.asp");

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);

        $data = array(
            'UserID' => $this->ak,
            'UserKey' => $this->sk,
            'PhoneNumber' => $mobile,
            'SmsContent' => mb_convert_encoding($content, 'GBK', 'UTF-8'),
        );
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $out = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if ($status['http_code'] != 200) {
            return false;
        }

        $right = array("00", "1", "00/1");
        if (!in_array($out, $right)) {
            $this->app->log->error(sprintf('%s: %s(code: %s)', $mobile, $content, $out));
            return false;
        }
        
        return true;
    }
    /*}}}*/

}
