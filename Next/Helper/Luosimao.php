<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Luosimao.php
* @touch date Tue 23 Feb 2016 08:36:51 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Luosimao {

    /*{{{ variable */
    private $app;
    private $appkey;
    /*}}}*/
    /*{{{ construct */
    public function __construct() {
        if (!extension_loaded('curl')) {
           throw new \RuntimeException('curl module not loaded.');
        }
        $this->app = \Slim\Slim::getInstance();

        $config = $this->app->config('sms');
        $this->appkey = $config['key'];
    }
    /*}}}*/
    /*{{{ send */
    public function send($mobile, $content) {
        /* Luosimao */
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);

        curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD  , 'api:key-'.$this->appkey);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $mobile,'message' => $content));

        $res = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if ($status['http_code'] != 200) {
            return false;
        }

        $out = json_decode($res, true);
        if (!$out || $out['error']) {
            $this->app->log->error(sprintf('%s: %s', $mobile, $content));
            return false;
        }
        
        return $out;
    }
    /*}}}*/

}
