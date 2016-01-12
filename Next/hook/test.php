<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename test.php
* @touch date Wed 07 May 2014 07:56:31 AM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

$app->hook('slim.before', function() {
    echo 'hook: test.php';
});
?>
