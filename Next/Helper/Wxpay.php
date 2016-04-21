<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Wxpay.php
* @touch date Wed 07 May 2016 03:10:59 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Wxpay {

/*{{{ const */
    const PAY_URL = 'https://api.mch.weixin.qq.com/';
/*}}}*/
/*{{{ variable */
    private $app;

    private $appid;
    private $mchid;
    private $sk;
    private $sslcert;
    private $sslkey;
    private $pxhost;
    private $pxport;
/*}}}*/
/*{{{ construct */
    public function __construct() {
        if (!extension_loaded('curl')) {
           throw new \RuntimeException('curl module not loaded.');
        }
        $this->app = \Slim\Slim::getInstance();
        $config = $this->app->config('wxpay');

        $this->appid = $config['appid'];
        $this->mchid = $config['mchid'];
        $this->sk = $config['sk'];
        $this->sslcert = $config['sslcert'];
        $this->sslkey = $config['sslkey'];
        $this->pxhost = $config['pxhost'];
        $this->pxport = $config['pxport'];
    }
/*}}}*/

/*{{{ jsPayParam */
    /**
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_7&index=6
     */
    public function jsPayParam($param) {
        $nonceStr = $this->nonceStr();
        $param["nonce_str"] = $nonceStr;
        if (!$uo = $this->unifiedorder($param)) {
            return false;
        }

        $out = array(
            "appId" => $this->appid,
            "timeStamp" => strval(time()),
            "nonceStr" => $nonceStr,
            "package" => sprintf("prepay_id=%s", $uo["prepay_id"]),
            "signType" => "MD5",
        );
        $out["paySign"] = $this->sign($out);

        return $out;
    }
/*}}}*/
/*{{{ unifiedorder */
    /**
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
     */
    public function unifiedorder($param) {
        $default = array(
            'appid' => $this->appid,
            'mch_id' => $this->mchid,
            'device_info' => null,
            'nonce_str' => '',
            'body' => '',
            'detail' => null,
            'attach' => null,
            'out_trade_no' => '',
            'fee_type' => null,
            'total_fee' => '',
            'spbill_create_ip' => $this->app->request->getIp(),
            'time_start' => null,
            'time_expire' => null,
            'goods_tag' => null,
            'notify_url' => '',
            'trade_type' => '',
            'product_id' => null,
            'limit_pay' => null,
            'openid' => null,
        );
        $data = array_merge($default, $param);
        $data = array_filter($data, function($val) {
            return isset($val);
        });
        $data["sign"] = $this->sign($data);

        if (!$out = $this->invoke("pay/unifiedorder", $data)) {
            $this->app->log->error("Wxpay unifiedorder: net invoke fail");
            return false;
        }
        if ($out["return_code"] != "SUCCESS") {
            $this->app->log->error("Wxpay unifiedorder: " . $out["return_msg"]);
            return false;
        }
        if ($out["result_code"] != "SUCCESS") {
            $this->app->log->error(sprintf("Wxpay unifiedorder: %s(%s)", $out["err_code_des"], $out["err_code"]));
            return false;
        }

        return $out;
    }
/*}}}*/
/*{{{ refund */
    /**
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
     */
    public function refund($param) {
        $default = array(
            'appid' => $this->appid,
            'mch_id' => $this->mchid,
            'device_info' => null,
            'nonce_str' => $this->nonceStr(),
            'transaction_id' => null,
            'out_trade_no' => null,
            'out_refund_no' => '',
            'total_fee' => '',
            'refund_fee' => '',
            'refund_fee_type' => null,
            'op_user_id' => $this->appid,
        );
        $data = array_merge($default, $param);
        $data = array_filter($data, function($val) {
            return isset($val);
        });
        $data["sign"] = $this->sign($data);

        if (!$out = $this->invoke("secapi/pay/refund", $data, true)) {
            $this->app->log->error("Wxpay refund: net invoke fail");
            return false;
        }
        if ($out["return_code"] != "SUCCESS") {
            $this->app->log->error("Wxpay refund: " . $out["return_msg"]);
            return false;
        }
        if ($out["result_code"] != "SUCCESS") {
            $this->app->log->error(sprintf("Wxpay refund: %s(%s)", $out["err_code_des"], $out["err_code"]));
            return false;
        }

        return $out;
    }
/*}}}*/
/*{{{ notify */
    /**
     * https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7
     */
    public function notify() {
        if (!$xml = file_get_contents('php://input')) {
            return false;
        }

        $data = $this->fromXml($xml);
        if ($data["return_code"] != 'SUCCESS') {
            $this->app->log->error("Wxpay notify: " . $data["return_msg"]);
            return false;
        }
        if ($this->sign($data) != $data['sign']) {
            $this->app->log->error("Wxpay notify: sign error");
            return false;
        }

        return $data;
    }
/*}}}*/

/*{{{ nonceStr */
    public function nonceStr($length = 16) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }
/*}}}*/
/*{{{ sign */
    public function sign($data) {
        if (isset($data["sign"])) {
            unset($data["sign"]);
        }

        ksort($data);
        $arr = array_map(function($k, $v) {
            return sprintf('%s=%s', $k, $v);
        }, array_keys($data), array_values($data));
        $str = implode('&', $arr);

        return strtoupper(md5(sprintf('%s&key=%s', $str, $this->sk)));
    }
/*}}}*/
/*{{{ toXml */
    /**
     * 输出xml字符
     * @throws WxPayException
    **/
    public function toXml($data) {
        if(!is_array($data) || count($data) <= 0) {
            throw new \Exception('array data incorrect.');
        }
        
        $xml = "<xml>";
        foreach ($data as $key=>$val) {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml; 
    }
/*}}}*/
/*{{{ fromXml */
    /**
     * 将xml转为array
     * @param string $xml
     * @throws Exception
     */
    public function fromXml($xml) {   
        if(!$xml){
            throw new \Exception('xml data incorrect.');
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
    }
/*}}}*/

/*{{{ invoke */
    /**
     * 以post方式提交xml到对应的接口url
     * 
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private function invoke($uri, $data, $useCert = false, $second = 30) {       
        $url = self::PAY_URL . $uri;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        if ($this->pxhost != "0.0.0.0" && $this->pxport != 0){
            curl_setopt($ch,CURLOPT_PROXY, $this->pxhost);
            curl_setopt($ch,CURLOPT_PROXYPORT, $this->pxport);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
        if ($useCert == true){
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $this->sslcert);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $this->sslkey);
        }
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->toXml($data));

        $out = curl_exec($ch);
        if(!$out){
            $error = curl_errno($ch);
            curl_close($ch);
            $this->app->log->error("WxPay curl error: $error");
            return false;
        }

        curl_close($ch);
        return $this->fromXml($out);
    }
/*}}}*/

}

