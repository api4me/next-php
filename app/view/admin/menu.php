<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename menu.php
* @touch date Thu 08 May 2014 02:16:19 PM CST
* @author: Fred<fred.zhou@foxmail.com>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
defined('IN_NEXT') or die('Access Denied');
?>

<div class="well well-small">
    <div>
        <h3>自定义菜单</h3>
        <div>可创建最多3个一级菜单，第个一级菜单下可创建5个二级菜单。</div>
    </div>
    <div class="row">
        <div class="pull-right span6"></div>
        <div class="pull-left span2">
            <div class="operate">
                <button class="btn add">添加</button>
                <h4 class="pull-left">菜单管理</h4>
            </div>
            <div class="menu">
                <ul class="nav nav-tabs nav-stacked">
                    <% _.each(data.menu, function(item) { %>
                        <li></li>
                    <% }); %>
                </ul>
            </div>
        </div>
    </div>

    <div id="menuItem" class="hide">
        <div class="item clearfix">
            <div class="tool hide pull-right">
                <% if (data.pid == 0) { %>
                <span class="add"><i class="icon-plus"></i></span>
                <% } %>
                <span class="edit"><i class="icon-edit"></i></span>
                <span class="del"><i class="icon-trash"></i></span>
            </div>
            <div class="pull-left"><%= data.name %></div>
        </div>
    </div>

    <div id="menuModal" class="modal hide fade">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3>输入提示框</h3>
        </div>
        <div class="modal-body">
            <div class="offset1">
                <p>菜单名称名字不多于8个汉字或16个字母:</p>
                <div><input class="input-xlarge" name="menu-name" value="" /></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary save">确认</button>
            <button class="btn" data-dismiss="modal" aria-hidden="true">关闭</button>
        </div>
    </div>
</div>
