<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename auth.php
* @touch date Sun 11 Jan 2015 02:31:35 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
defined('IN_NEXT') or die('Access Denined');

$auth = array();
$auth['home'] = function() {
    return true;
};

?>
