<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename config.php
* @touch date Wed 07 May 2014 12:29:38 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
defined('IN_NEXT') or die('Access Denied');

ini_set('display_errors', 1);

/*
|--------------------------------------------------------------------------
| Setting
|--------------------------------------------------------------------------
|
| Setting for core framework Slim.
|
*/
// Application
$setting['mode'] = 'development';
// Debugging
$setting['debug'] = true;
// Logging
$setting['log.writer'] = new \Next\Helper\Filelog(array('path' => './app/log/'));
$setting['log.level'] = \Slim\Log::DEBUG;
$setting['log.enabled'] = true;
// View
$setting['templates.path'] = './app/view';
$setting['view'] = '\Slim\View';
// Cookies
$setting['cookies.encrypt'] = false;
$setting['cookies.lifetime'] = '20 minutes';
$setting['cookies.path'] = '/';
$setting['cookies.domain'] = null;
$setting['cookies.secure'] = false;
$setting['cookies.httponly'] = false;
// Encryption
$setting['cookies.secret_key'] = 'CHANGE_ME';
$setting['cookies.cipher'] = MCRYPT_RIJNDAEL_256;
$setting['cookies.cipher_mode'] = MCRYPT_MODE_CBC;
// HTTP
$setting['http.version'] = '1.1';
// Routing
$setting['routes.case_sensitive'] = true;

/*
|--------------------------------------------------------------------------
| Common
|--------------------------------------------------------------------------
|
| Common configure, such as name, time.
|
*/
$config['common']['salt'] = 'MwMHvPYAs28Z7wtz';
$config['common']['domain'] = 'http://127.0.0.1:2042/';

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
|
| Session configure, such as name, time.
|
*/
$config['session']['name'] = 'suid';
$config['session']['time'] = '3600';

/*
|--------------------------------------------------------------------------
| MySQL
|--------------------------------------------------------------------------
|
| MySQL configure, such as host, name, port, pwd, db, charset, dbcollat.
|
*/
$config['mysql']['dsn'] = 'mysql:host=localhost;dbname=next;port=3306;charset=utf8';
$config['mysql']['username'] = 'next';
$config['mysql']['password'] = 'dnuPCBV7NdATNpp3';

/*
|--------------------------------------------------------------------------
| Redis
|--------------------------------------------------------------------------
|
| Redis configure, such as host, port, timeout, reserved, password
| pconnected.
|
*/
$config['redis']['host'] = '127.0.0.1';
$config['redis']['port'] = 6379;
$config['redis']['timeout'] = 0;
$config['redis']['reserved'] = null;
$config['redis']['password'] = null;
$config['redis']['pconnected'] = false;

/*
|--------------------------------------------------------------------------
| Weixin
|--------------------------------------------------------------------------
|
| Weixin api account
|
*/
$config['wechat']['token'] = 'haichuang';
$config['wechat']['appid'] = 'wx0fbe10a5655ec574';
$config['wechat']['appsecret'] = '5d7e0f09937184d720c59f76caa8b1cf';

$config['wxpay']['appid'] = 'wx0fbe10a5655ec574';
$config['wxpay']['mchid'] = '1275202601';
$config['wxpay']['sk'] = '9234f0e67bf84b9aa73598bd41b1f887';
$config['wxpay']['sslcert'] = './app/cert/apiclient_cert.pem';
$config['wxpay']['sslkey'] = './app/cert/apiclient_key.pem';
$config['wxpay']['pxhost'] = '0.0.0.0';
$config['wxpay']['pxport'] = 0;


/*
|--------------------------------------------------------------------------
| SMS
|--------------------------------------------------------------------------
|
| SMS key setting
|
*/
$config["sms"]["ak"] = "njzxxx";
$config["sms"]["sk"] = "851116";

/*
|--------------------------------------------------------------------------
| Twig
|--------------------------------------------------------------------------
|
| Twig setting
|
*/
$config['twig']['cache'] = './app/cache/twig';

/*
|--------------------------------------------------------------------------
| Autorun
|--------------------------------------------------------------------------
|
| Autorun Helper, Middleware, Hook
|
*/
$config['auto']['helper'] = array('common', 'session', 'redis', 'db');
$config['auto']['middleware'] = array('nocache');
$config['auto']['hook'] = array();

/*
|--------------------------------------------------------------------------
| Route
|--------------------------------------------------------------------------
|
| URI requests to default module
|
*/
$config['route']['module'] = "admin";

/*
|--------------------------------------------------------------------------
| Upload
|--------------------------------------------------------------------------
|
| Upload File setting
|
*/
$config['upload']['image'] = array('jpg', 'jpeg', 'png');
$config['upload']['save_path'] = './assets/upload/';
$config['upload']['save_url'] = $config['common']['domain'] . 'assets/upload/';
$config['upload']['max_size'] = 1000000;

/*
|--------------------------------------------------------------------------
| Pagenation
|--------------------------------------------------------------------------
|
| Pagenation tool setting
|
*/
$config['pagination']['per_page'] = 12;

/*
|--------------------------------------------------------------------------
| Google
|--------------------------------------------------------------------------
|
| Google api setting
|
*/
$config['google']['map'] = "AIzaSyD7RH9MO-Wu3Unii6lNJlImiVqcyPwQMOI";

/*
|--------------------------------------------------------------------------
| Mail
|--------------------------------------------------------------------------
|
| Mail setting
|
*/
$config['mail']['host'] = "smtp.exmail.qq.com";
$config['mail']['smtp_auth'] = true;
$config['mail']['username'] = "auto@api4.me";
$config['mail']['pwd'] = "Auto@123";
$config['mail']['smtp_secure'] = "ssl";
$config['mail']['port'] = "465";
$config['mail']['from'] = "auto@api4.me";
$config['mail']['from_name'] = "Logiclink";
$config['mail']['max_times'] = 5;
$config['mail']['max_process'] = 5;

/*
|--------------------------------------------------------------------------
| Api
|--------------------------------------------------------------------------
|
| Api key setting
|
*/
$config["api"]["ak"] = "logiclink";
$config["api"]["sk"] = "zLtJm3qYJBnBYTVU";

/*
|--------------------------------------------------------------------------
| Sign
|--------------------------------------------------------------------------
|
| The key of sign
|
*/
$config['sign']['key'] = "MdV4UdjneWFcqGtL";
