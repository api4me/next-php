<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Map.php
* @touch date Wed 07 May 2014 05:55:38 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Map {

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
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('curl module not loaded.');
        }

        $this->app = \Slim\Slim::getInstance();
        $this->config = $this->app->config('baidu');
    }
/*}}}*/
/*{{{ location */
    public function location($lat, $lng) {
        $params = array(
            'output' => 'json',
            'ak' => $this->config['ak'],
            'coordtype' => 'wgs84ll',
            'location' => $lat . ',' . $lng,
            'pois' => 0
        );
        $resp = $this->invoke('/geocoder/v2/', $params, false);

        return array(
            'address' => $resp['result']['formatted_address'],
            'province' => $resp['result']['addressComponent']['province'],
            'city' => $resp['result']['addressComponent']['city'],
            'street' => $resp['result']['addressComponent']['street'],
            'street_number' => $resp['result']['addressComponent']['street_number'],
            'city_code' => $resp['result']['addressComponent']['adcode'],
            'lng' => $resp['result']['location']['lng'],
            'lat' => $resp['result']['location']['lat']
        );
    }
/*}}}*/

/*{{{ sn */
    private function sn($uri, $data) {
        if (isset($data["sn"])) {
            unset($data["sn"]);
        }

        ksort($data);  
        $str = http_build_query($data);  

        return md5(urlencode($uri.'?'.$str.$this->config["sk"]));  
    }
/*}}}*/
/*{{{ invoke */
    /**
     * Commmunicate with Wechat server 
     * @param string $url
     */
    private function invoke($uri, $data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.map.baidu.com" . $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_REFERER, '百度地图referer');
        // curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X; en-us) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53');
        if ($data) {
            $data["sn"] = $this->sn($data);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $out = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if ($status['http_code'] != 200) {
            return false;
        }

        // Check response
        $out = json_decode($out, true);
        if (!$out || $out['status']) {
            // http://lbsyun.baidu.com/index.php?title=webapi/guide/webservice-geocoding#8..E8.BF.94.E5.9B.9E.E7.A0.81.E7.8A.B6.E6.80.81.E8.A1.A8
            $this->app->log->error(sprintf("Baidu api fail, error %s(%s)," . @$out["message"], @$out['status']));
            return false;
        }

        return $out;
    }
/*}}}*/

}
