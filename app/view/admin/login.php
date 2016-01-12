<?php defined('IN_NEXT') or die('Access Denied'); ?>
<!DOCTYPE html>
<html lang="utf-8">
    <head>
        <meta charset="utf-8">
        <title>登录 - 尔冬吉微商城</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <link href="/assets/bootstrap/v2/css/bootstrap.min.css" rel="stylesheet">
        <link href="/assets/css/admin.css" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <div id="login">
                <h2>请登录</h2>
                <div class="control-group">
                   <label class="control-label" for="inputUser">用户名</label>
                   <div class="controls">
                        <input type="text" id="inputUser" class="input-xlarge" name="user">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" for="inputPwd">密码</label>
                    <div class="controls">
                        <input type="password" id="inputPwd" class="input-xlarge" name="pwd">
                    </div>
                </div>
                <div class="control-group">
                    <button class="btn btn-success btn-large login">登录</button>
                </div>
                <div class="text-center">
                    &copy;<?php echo date('Y') ?> 尔冬吉微商城
                </div>
            </div>
        </div>
        <!-- content -->
        <!--[if lt IE 9]>
        <script src="/assets/js/lib/html5shiv.js"></script>
        <![endif]-->
        <script src="/assets/js/lib/require.js" data-main="/assets/js/login"></script>
     </body>
</html>
