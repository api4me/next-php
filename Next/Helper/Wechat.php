<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Wechat.php
* @touch date Wed 07 May 2014 03:10:59 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Wechat {

    /*{{{ const */
    const MSG_TEXT = 'text';
    const MSG_IMAGE = 'image';
    const MSG_VOICE = 'voice';
    const MSG_VIDEO = 'video';
    const MSG_LOCATION = 'location';
    const MSG_LINK = 'link';
    const MSG_MUSIC = 'music';
    const MSG_NEWS = 'news';
    const MSG_EVENT = 'event';
    const EVT_SUBSCRIBE = 'subscribe';
    const EVT_UNSUBSCRIBE = 'unsubscribe';
    const EVT_SCAN = 'SCAN';
    const EVT_LOCATION = 'LOCATION';
    const EVT_CLICK = 'CLICK';
    const EVT_VIEW = 'VIEW';

    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';

    const QR_SCENE = 0;
    const QR_LIMIT_SCENE = 1;
    const QRCODE_IMG_URL = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=';
    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_TOKEN_PREFIX = 'https://api.weixin.qq.com/sns/oauth2';
    const OAUTH_USERINFO_URL = 'https://api.weixin.qq.com/sns/userinfo?';
    const PAY_DELIVERNOTIFY = 'https://api.weixin.qq.com/pay/delivernotify?';
    const PAY_ORDERQUERY = 'https://api.weixin.qq.com/pay/orderquery?';
     /*}}}*/
    /*{{{ variable */
    private $app;
    private $token;
    private $appid;
    private $appsecret;
    private $accessToken;
    private $userToken;
    private $partnerid;
    private $partnerkey;
    private $paysignkey;
    private $request;

    public $errCode = 40001;
    public $errMsg = "no access";
     /*}}}*/
/*{{{ construct */
    public function __construct() {
        if (!extension_loaded('curl')) {
           throw new \RuntimeException('curl module not loaded.');
        }
        $this->app = \Slim\Slim::getInstance();
        if (!isset($this->app->redis)) {
           throw new \RuntimeException('Redis must init before wechat module.');
        }

        $config = $this->app->config('wechat');

        $this->token = $config['token'];
        $this->appid = $config['appid'];
        $this->appsecret = $config['appsecret'];

        $this->partnerid = $config['partnerid'];
        $this->partnerkey = $config['partnerkey'];
        $this->paysignkey = $config['paysignkey'];
    }
/*}}}*/

/*{{{ valid */
    /**
     * For weixin server validation
     */
    public function valid() {
        $signature = isset($_GET['signature']) ? $_GET['signature'] : '';
        $timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : '';
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        }
        return false;
    }
/*}}}*/
    /*{{{ accessToken */
    /**
     * 通用auth验证方法，暂时仅用于菜单更新操作
     * @param string $appid
     * @param string $appsecret
     */
    public function accessToken($appid = '', $appsecret = '') {
        if ($tmp = $this->app->redis->get('tk')) {
            $this->accessToken = $tmp;

            return $this->accessToken;
        }

        if (!$appid || !$appsecret) {
            $appid = $this->appid;
            $appsecret = $this->appsecret;
        }
        $resp = $this->invoke(self::API_URL_PREFIX . '/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $appsecret);
        if ($resp) {
            $this->accessToken = $resp['access_token'];
            $expire = $resp['expires_in'] ? intval($resp['expires_in']) - 100 : 3600;
            $this->app->redis->set('tk', $resp['access_token'], $expire);

            return $this->accessToken;
        }
        return false;
    }
     /*}}}*/

/*{{{ request */
    /**
     * 获取微信服务器发来的信息
     */
    public function request() {
        if ($tmp = file_get_contents("php://input")) {
            $this->request = (array)simplexml_load_string($tmp, 'SimpleXMLElement', LIBXML_NOCDATA);
            // TODO Set data to redis queue
            $openId = $this->request['FromUserName'];
            $uid = sprintf('u:%s', $openId);
            if (!$this->app->redis->exists($uid) || !array_pop($this->app->redis->hmGet($uid, array('subscribe')))) {
                if ($tmp = $this->getUserInfo($openId)) {
                    $this->app->redis->hMset($uid, $tmp);
                }
                // file_put_contents('/tmp/w.txt', json_encode($tmp));
            }
        }
        return $this;
    }
/*}}}*/
    /*{{{ response */
    /**
     * Send message to weixin api 
     */
    public function response($type, $data) {
        if (!$this->request) {
            return false;
        }

        $out = array();
        $out['ToUserName'] = $this->request['FromUserName'];
        $out['FromUserName'] = $this->request['ToUserName'];
        $out['@CreateTime'] = time();
        $out['MsgType'] = $type;
        switch ($type) {
            case self::MSG_TEXT:
                $out['Content'] = $data;
                break;
            case self::MSG_IMAGE:
                $out['Image']['MediaId'] = $data;
                break;
            case self::MSG_VOICE:
                $out['Voice']['MediaId'] = $data;
                break;
            case self::MSG_VIDEO:
                $out['Video'] = array(
                    'MediaId' => $data['MediaId'],
                    'Title' => $data['Title'],
                    'Description' => $data['Description'],
                );
                break;
            case self::MSG_MUSIC:
                $out['Music'] = array(
                    'Title' => $data['Title'],
                    'Description' => $data['Description'],
                    'MusicUrl' => $data['MusicUrl'],
                    'HQMusicUrl' => $data['HQMusicUrl'],
                    'ThumbMediaId' => $data['ThumbMediaId'],
                );
                break;
            case self::MSG_NEWS:
                $out['@ArticleCount'] = count($data);
                foreach ($data as $val) {
                    $out['Articles'][] = array(
                        'Title' => $val['Title'],
                        'Description' => $val['Description'],
                        'PicUrl' => $val['PicUrl'],
                        'Url' => $val['Url'],
                    );
                }
                break;
        }

        $xml = new SimpleXMLExtended('<xml />');
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml->toXml($out);
        die;
    }
     /*}}}*/
    /*{{{ receive */
    /**
     * 获取微信服务器发来的信息
     */
    public function receive() {
        if ($this->request) {
            $request = $this->request;

            $out = array();
            $out['ToUserName'] = $request['ToUserName'];
            $out['FromUserName'] = $request['FromUserName'];
            $out['CreateTime'] = $request['CreateTime'];
            $out['MsgType'] = $request['MsgType'];
            switch ($request['MsgType']) {
                case self::MSG_TEXT:
                    $out['MsgId'] = $request['MsgId'];
                    $out['Content'] = $request['Content'];
                    break;
                case self::MSG_IMAGE:
                    $out['MsgId'] = $request['MsgId'];
                    $out['PicUrl'] = $request['PicUrl'];
                    $out['MediaId'] = $request['MediaId'];
                    break;
                case self::MSG_VOICE:
                    $out['MsgId'] = $request['MsgId'];
                    $out['MediaId'] = $request['MediaId'];
                    $out['Format'] = $request['Format'];
                    if (isset($request['Recognition'])) {
                        $out['Recognition'] = $request['Recognition'];
                    }
                    break;
                case self::MSG_VIDEO:
                    $out['MsgId'] = $request['MsgId'];
                    $out['MediaId'] = $request['MediaId'];
                    $out['ThumbMediaId'] = $request['ThumbMediaId'];
                    break;
                case self::MSG_LOCATION:
                    $out['MsgId'] = $request['MsgId'];
                    $out['Location_X'] = $request['Location_X'];
                    $out['Location_Y'] = $request['Location_Y'];
                    $out['Scale'] = $request['Scale'];
                    $out['Label'] = $request['Label'];
                    break;
                case self::MSG_LOCATION:
                    $out['MsgId'] = $request['MsgId'];
                    $out['Title'] = $request['Title'];
                    $out['Description'] = $request['Description'];
                    $out['Url'] = $request['Url'];
                    break;

                case self::MSG_EVENT:
                    $out['MsgId'] = md5($request['FromUserName'] . $request['CreateTime']);
                    $out['Event'] = $request['Event'];
                    switch ($request['Event']) {
                        case self::EVT_SUBSCRIBE:
                            if (isset($request['EventKey'])) {
                                $out['EventKey'] = $request['EventKey'];
                                $out['Ticket'] = $request['Ticket'];
                            }
                            break;
                        case self::EVT_UNSUBSCRIBE:
                            break;
                        case self::EVT_SCAN:
                            $out['EventKey'] = $request['EventKey'];
                            $out['Ticket'] = $request['Ticket'];
                            break;
                        case self::EVT_LOCATION:
                            $out['Latitude'] = $request['Latitude'];
                            $out['Longitude'] = $request['Longitude'];
                            $out['Precision'] = $request['Precision'];
                            break;
                        case self::EVT_CLICK:
                        case self::EVT_VIEW:
                            $out['EventKey'] = $request['EventKey'];
                            break;

                    }
                    // TODO
                    // $this->event();
                    break;
            }
            return $out;
        }

        return false;
    }
     /*}}}*/

    /*{{{ sendCustomMessage */
    /**
     * 发送客服消息
     * @param array $data 消息结构{"touser":"OPENID","msgtype":"news","news":{...}}
     * @return boolean|array
     */
    public function sendCustomMessage($data) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }

        return $this->invoke(self::API_URL_PREFIX . '/message/custom/send?access_token=' . $this->accessToken, $data);
    }
     /*}}}*/

    /*{{{ createMenu */
    /**
     * 创建菜单
     * @param array $data 菜单数组数据
     * example: http://mp.weixin.qq.com/wiki/index.php?title=%E8%87%AA%E5%AE%9A%E4%B9%89%E8%8F%9C%E5%8D%95%E5%88%9B%E5%BB%BA%E6%8E%A5%E5%8F%A3
     */
    public function createMenu($data) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        return $this->invoke(self::API_URL_PREFIX . '/menu/create?access_token=' . $this->accessToken, $data);
    }
/*}}}*/
    /*{{{ getMenu */
    /**
     * 获取菜单
     * @return array('menu'=>array(....s))
     */
    public function getMenu() {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        return $this->invoke(self::API_URL_PREFIX . '/menu/get?access_token=' . $this->accessToken);
    }
/*}}}*/
/*{{{ deleteMenu */
    /**
     * 删除菜单
     * @return boolean
     */
    public function deleteMenu() {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        return $this->invoke(self::API_URL_PREFIX . '/menu/delete?access_token=' . $this->accessToken);
    }
/*}}}*/

    /*{{{ getMedia */
    /**
     * 根据媒体文件ID获取媒体文件
     * @param string $media_id 媒体文件id
     * @return raw data
     */
    public function getMedia($media_id) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        return $this->invoke(self::API_URL_PREFIX . '/media/get?access_token=' . $this->accessToken . '&media_id=' . $media_id);
    }
     /*}}}*/
    /*{{{ getQRCode */
    /**
     * 创建二维码ticket
     * @param int $scene_id 自定义追踪id
     * @param int $type 0:临时二维码；1:永久二维码(此时expire参数无效)
     * @param int $expire 临时二维码有效期，最大为1800秒
     * @return array('ticket'=>'qrcode字串','expire_seconds'=>1800)
     */
    public function getQRCode($scene_id, $type = 0, $expire = 1800) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        $data = array(
            'action_name' => $type ? "QR_LIMIT_SCENE" : "QR_SCENE",
            'expire_seconds' => $expire,
            'action_info' => array(
                'scene' => array(
                    'scene_id' => $scene_id
                )
            )
        );
        if ($type == 1) {
            unset($data['expire_seconds']);
        }

        return $this->invoke(self::API_URL_PREFIX . '/qrcode/create?access_token=' . $this->accessToken, $data);
    }
     /*}}}*/
    /*{{{ getQRUrl */
    /**
     * 获取二维码图片
     * @param string $ticket 传入由getQRCode方法生成的ticket参数
     * @return string url 返回http地址
     */
    public function getQRUrl($ticket) {
        return self::QRCODE_IMG_URL . $ticket;
    }
     /*}}}*/
/*{{{ getUserList */
    /**
     * 批量获取关注用户列表
     * @param unknown $next_openid
     */
    public function getUserList($next_openid = '') {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        return $this->invoke(self::API_URL_PREFIX . '/user/get?access_token=' . $this->accessToken . '&next_openid=' . $next_openid);
    }
/*}}}*/
    /*{{{ getUserInfo */
    /**
     * 获取关注者详细信息
     * @param string $openid
     * @return array
     */
    public function getUserInfo($openid) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }

        return $this->invoke(self::API_URL_PREFIX . '/user/info?access_token=' . $this->accessToken . '&openid=' . $openid);
    }
     /*}}}*/
/*{{{ getGroup */
    /**
     * 获取用户分组列表
     * @return boolean|array
     */
    public function getGroup() {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        return $this->invoke(self::API_URL_PREFIX . '/groups/get?access_token=' . $this->accessToken);
    }
/*}}}*/
/*{{{ createGroup */
    /**
     * 新增自定分组
     * @param string $name 分组名称
     * @return boolean|array
     */
    public function createGroup($name) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        $data = array(
            'group' => array(
                'name' => $name
            )
        );
        return $this->invoke(self::API_URL_PREFIX . '/groups/create?access_token=' . $this->accessToken, $data);
    }
/*}}}*/
/*{{{ updateGroup */
    /**
     * 更改分组名称
     * @param int $groupid 分组id
     * @param string $name 分组名称
     * @return boolean|array
     */
    public function updateGroup($groupid, $name) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        $data = array(
            'group' => array(
                'id' => $groupid,
                'name' => $name
            )
        );
        return $this->invoke(self::API_URL_PREFIX . '/groups/update?access_token=' . $this->accessToken, $data);
    }
/*}}}*/
/*{{{ updateGroupMembers */
    /**
     * 移动用户分组
     * @param int $groupid 分组id
     * @param string $openid 用户openid
     * @return boolean|array
     */
    public function updateGroupMembers($groupid, $openid) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }
        $data = array(
            'openid' => $openid,
            'to_groupid' => $groupid,
        );
        return $this->invoke(self::API_URL_PREFIX . '/groups/members/update?access_token=' . $this->accessToken, $data);
    }
/*}}}*/

    /*{{{ oauth2Url */
    /**
     * oauth 授权跳转接口
     * @param string $callback 回调URI
     * @return string
     */
    public function oauth2Url($callback, $state = '', $scope = 'snsapi_userinfo') {
        return self::OAUTH_PREFIX . '/authorize?appid=' . $this->appid . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
    }
     /*}}}*/
    /*{{{ oauth2AccessToken */
    /*
     * 通过code获取Access Token
     * @return array {access_token,expires_in,refresh_token,openid,scope}
    */
    public function oauth2AccessToken() {
        $code = isset($_GET['code'])? $_GET['code']: '';
        if (!$code) {
            return false;
        }

        $resp = $this->invoke(self::OAUTH_TOKEN_PREFIX . '/access_token?appid=' . $this->appid . '&secret=' . $this->appsecret . '&code=' . $code . '&grant_type=authorization_code');
        if ($resp) {
            $this->userToken = $resp['access_token'];
            return $resp;
        }
        return false;
    }
     /*}}}*/
    /*{{{ oauth2RefreshToken */
    /**
     * 刷新access token并续期
     * @param string $refresh_token
     * @return boolean|mixed
     */
    public function oauth2RefreshToken($refresh_token) {
        $resp = $this->invoke(self::OAUTH_TOKEN_PREFIX . '/refresh_token?appid=' . $this->appid . '&grant_type=refresh_token&refresh_token=' . $refresh_token);
        if ($resp) {
            $this->userToken = $resp['access_token'];
            return $resp;
        }
        return false;
    }
     /*}}}*/
/*{{{ oauth2Userinfo */
    /**
     * 获取授权后的用户资料
     * @param string $access_token
     * @param string $openid
     * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege}
     */
    public function oauth2Userinfo($access_token, $openid) {
        return $this->invoke(self::OAUTH_USERINFO_URL . 'access_token=' . $access_token . '&openid=' . $openid);
    }
/*}}}*/

/*{{{ payNonceStr */
    /**
     * 生成随机字串
     * @param number $length 长度，默认为16，最长为32字节
     * @return string
     */
    public function payNonceStr($length = 16) {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $str;
    }
/*}}}*/
    /*{{{ payPackage */
    /**
     * 生成订单package字符串
     *   body             商品描述, 128 字节以下
     *   out_trade_no     商户订单号, 32 字节以下
     *   total_fee        订单总金额, 订单总金额,单位为分
     *   notify_url       通知 URL, 在支付完成后,接收微信通知支付结果的URL, 255 字节以下
     *   spbill_create_ip 订单生成的机器IP, 指用户浏览器端IP
     *
     *   以下是可选项
     *   attach           附加数据,字符串类型, 128 字节以下
     *   time_start       交易起始时间, yyyyMMddHHmmss
     *   time_expire      交易结束时间, yyyyMMddHHmmss
     *   transport_fee    物流费用, 位为分. 如有值,必须保证 transport_fee + product_fee=total_fee;
     *   product_fee      商品费用, 位为分. 如有值,必须保证 transport_fee + product_fee=total_fee;
     *   goods_tag        商品标记, 优惠券时可能用到
     * 
     * @return string
     */
    public function payPackage($param) {
        $default = array(
            'bank_type' => 'WX',
            'body' => '',
            'attach' => null,
            'partner' => $this->partnerid,
            'out_trade_no' => '',
            'total_fee' => '',
            'fee_type' => '1',
            'notify_url' => '',
            'spbill_create_ip' => '',
            'time_start' => null,
            'time_expire' => null,
            'transport_fee' => null,
            'product_fee' => null,
            'goods_tag' => null,
            'input_charset' => 'UTF-8',
        );
        $data = array_merge($default, $param);
        $data = array_filter($data, function($val) {
            return isset($val);
        });

        ksort($data);
        $signValue = strtoupper(md5(sprintf('%s&key=%s', $this->paySignStr($data), $this->partnerkey)));
        $out = str_replace('+', '%20', http_build_query($data)) . '&sign=' . $signValue;

        return $out;
    }
/*}}}*/
/*{{{ paySign */
    /**
     * 支付签名(paySign)生成方法
     * @param string $package 订单详情字串
     * @param string $timeStamp 当前时间戳（需与JS输出的一致）
     * @param string $nonceStr 随机串（需与JS输出的一致）
     * @return string 返回签名字串
     */
    public function paySign($package, $timeStamp, $nonceStr) {
        $data = array(
            'appid' => $this->appid,
            'timestamp' => $timeStamp,
            'noncestr' => $nonceStr,
            'package' => $package,
            'appkey' => $this->paysignkey,
        );

        return $this->payGenerateSign($data);
    }
/*}}}*/
    /*{{{ payCheckBackSign */
    /**
     * 回调通知签名验证
     * @param array $orderxml 返回的orderXml的数组表示，留空则自动从post数据获取
     * @return boolean
     */
    public function payCheckBackSign($orderxml = '') {
        if (!$orderxml) {
            $postStr = file_get_contents('php://input');
            if (empty($postStr)) {
                return false;
            }
            $orderxml = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        }

        $data = array(
            'appid' => $orderxml['AppId'],
            'appkey' => $this->paysignkey,
            'timestamp' => $orderxml['TimeStamp'],
            'noncestr' => $orderxml['NonceStr'],
            'openid' => $orderxml['OpenId'],
            'issubscribe' => $orderxml['IsSubscribe']
        );
        $paySign = $this->payGenerateSign($data);

        return $paySign == $orderxml['AppSignature'];
    }
     /*}}}*/
/*{{{ payDeliverNotify */
    /**
     * 发货通知
     * @param string $openid 用户open_id
     * @param string $transid 交易单号
     * @param string $out_trade_no 第三方订单号
     * @param int $status 0:发货失败；1:已发货
     * @param string $msg 失败原因
     * @return boolean|array
     */
    public function payDeliverNotify($openid, $transid, $out_trade_no, $status = 1, $msg = 'ok') {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }

        $data = array(
            'appid' => $this->appid,
            'appkey' => $this->paysignkey,
            'openid' => $openid,
            'transid' => strval($transid) ,
            'out_trade_no' => strval($out_trade_no) ,
            'deliver_timestamp' => strval(time()) ,
            'deliver_status' => strval($status) ,
            'deliver_msg' => $msg,
        );
        $data['app_signature'] = $this->payGenerateSign($data);
        $data['sign_method'] = 'sha1';
        unset($data['appkey']);

        return $this->invoke(self::PAY_DELIVERNOTIFY . 'access_token=' . $this->accessToken, $data);
    }
     /*}}}*/
/*{{{ payOrder */
    /*
     * 查询订单信息
     * @param string $tradeNo 订单号
     * @return boolean|array
    */
    public function payOrder($tradeNo) {
        if (!$this->accessToken && !$this->accessToken()) {
            return false;
        }

        $sign = strtoupper(md5("out_trade_no=$tradeNo&partner={$this->partnerid}&key={$this->partnerkey}"));
        $postdata = array(
            'appid' => $this->appid,
            'appkey' => $this->paysignkey,
            'package' => "out_trade_no={$tradeNo}&partner={$this->partnerid}&sign={$sign}",
            'timestamp' => strval(time()) ,
        );
        $postdata['app_signature'] = $this->payGenerateSign($postdata);
        $postdata['sign_method'] = 'sha1';
        unset($postdata['appkey']);

        return $this->invoke(self::PAY_ORDERQUERY . 'access_token=' . $this->accessToken, $postdata);
    }
/*}}}*/
    /*{{{ payAddrSign */
    /**
     * 获取收货地址JS的签名
     * @param string $appId
     * @param string $url
     * @param int $timeStamp
     * @param string $nonceStr
     * @param string $userToken
     * @return Ambigous <boolean, string>
     */
    public function payAddrSign($url, $timeStamp, $nonceStr, $userToken = '') {
        if (!$userToken) {
            $userToken = $this->userToken;
        }
        if (!$userToken) {
            $this->errMsg = 'No user access token found!';
            return false;
        }

        $url = htmlspecialchars_decode($url);
        $arrdata = array(
            'appid' => $this->appid,
            'url' => $url,
            'timestamp' => strval($timeStamp),
            'noncestr' => $nonceStr,
            'accesstoken' => $userToken,
        );

        return $this->payGenerateSign($arrdata);
    }
/*}}}*/

/*{{{ invoke */
    /**
     * Commmunicate with Wechat server 
     * @param string $url
     */
    private function invoke($url, $data = null) {
        $ch = curl_init();
        if (stripos($url, 'https://') !== FALSE) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($data) {
            array_walk_recursive($data, function(&$val, $key){
                if (is_string($val)) {
                    $val = urlencode($val);
                }
            });
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode(json_encode($data)));
        }

        $out = curl_exec($ch);
        $status = curl_getinfo($ch);
        curl_close($ch);
        if ($status['http_code'] != 200) {
            return false;
        }

        // Check response
        $out = json_decode($out, true);
        if (!$out || !empty($out['errcode'])) {
            $this->errCode = $out['errcode'];
            $this->errMsg = $out['errmsg'];
            return false;
        }

        return $out;
    }
/*}}}*/
/*{{{ paySignStr */
    private function paySignStr($data) {
        $arr = array_map(function($k, $v) {
            return sprintf('%s=%s', $k, $v);
        }, array_keys($data), array_values($data));

        return implode('&', $arr);
    }
/*}}}*/
/*{{{ payGenerateSign */
    /**
     * 获取签名
     * @param array $data 签名数组
     * @return boolean|string 签名值
     */
    private function payGenerateSign($data) {
        ksort($data);
        return sha1($this->paySignStr($data));
    }
/*}}}*/

}

/*{{{ SimpleXMLExtended */
class SimpleXMLExtended extends \SimpleXMLElement {
    
    public function buildXml($data, &$xml) {
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $key = 'item';
            }

            if (is_array($val)) {
                $node = $xml->addChild($key);
                $this->buildXml($val, $node);
            } else {
                if (substr($key, 0, 1) == '@') {
                    $xml->addChild(substr($key, 1), $val);
                } else {
                    $xml->addChild($key);
                    $xml->$key->addCData($val);
                }
            }
        }
    }

    public function addCData($text) {
        $node = dom_import_simplexml($this); 
        $no   = $node->ownerDocument; 
        $text = preg_replace('/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/', '', $text);
        $node->appendChild($no->createCDATASection($text)); 
    } 

    public function toXml($data) {
        $this->buildXml($data, $this);
        return $this->asXml();
    }

}
/*}}}*/
