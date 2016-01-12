<?php defined('IN_NEXT') or die('Access Denied'); ?>
<div>
    <div class="main">
        <div class="wechat clearfix">
            <div>
                <label class="control-label">回复类型
                <select name="type" class="span2">
                    <option value="text">文本</option>
                    <option value="news">图文</option>
                </select>
                </label>
            </div>
            <div class="message-content">
            </div>
        </div>
    </div>
<div>

<script type="text/template" id="tpl-text">
<div class="type-text">
    <div class="mod-msg">
        <div class="mod-msg-txt">
        <div class="user" data-id="logo">        
            <span class="mod-default-avatar"></span></div>
            <div class="content">
                <span class="arrow"></span>
                <div class="msg empty" data-id="show-text">回复内容</div>
            </div>
        </div>
    </div>
    
    <div class="mod-msg-editor">
        <div class="mod-msg-arrow" style="top: 50px;"></div>
        <div class="mod-msg-rich-editor">
            已经输入 <span class="count star"></span> 个字 / 仅限600个字
            <textarea class="editor"><%= data.content %></textarea>
        </div>
    </div>
</div>
</script>

<script type="text/template" id="tpl-news">
<div class="news">
    <div class="mod-msg multi">
        <div class="multi-first mod-msg-cover active">
            <div style="width:272px; height:155px; overflow:hidden">
                <div style="background:#ececec; color:#c0c0c0; font-size:20px; text-align:center; line-height:155px">封面图片</div>
            </div>
            <h2>标题</h2>
            <div class="mod-msg-options edit-msg-cover">
                <p><span class="edit">编辑</span></p>
            </div>
        </div>

        <div class="add" id="addMsgItem"><span class="plus">+</span></div>
    </div>
    
    <div class="mod-msg-editor">
        <div class="mod-msg-arrow" style="top: 90px;"></div>
        <div id="news-value"></div>
    </div>
</div>
</script>

<script type="text/template" id="tpl-news-item">
    <div class="mod-msg-item clearfix">
        <div style="float:right; width:70px; background:#ececec; color:#c0c0c0; font-size:14px; text-align:center; line-height:70px">缩略图</div>
        <h2></h2>
        <div class="mod-msg-options edit-msg-cover">
            <p><span class="edit">编辑</span></p>
            <p><span class="del">删除</span></p>
        </div>
    </div>
</script>

<script type="text/template" id="tpl-news-value">
    <input type="hidden" name="idx" value="<%= idx %>" />
    <input type="hidden" name="id" value="<%= data.id %>" />
    <div class="control-group">
        <label class="control-label">标题<i class="icon-star-empty"></i></label>
        <div class="controls">
            <input type="text" value="<%= data.title %>" name="title" placeholder="请输入图文回复的标题">
            <em>限64个字</em>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label">作者</label>
        <div class="controls">
            <input type="text" value="<%= data.author %>" name="author" placeholder="请输入图文回复的作者">
            <em>限8个字</em>
        </div>
    </div>
    <div class="control-group msg-description" style="display: none;">
        <a href="javascript:void(0);">添加摘要</a>
    </div>
    <div class="control-group msg-description" style="display: none;">
        <label class="control-label">摘要</label>
        <div class="controls">
            <textarea value="" name="description" placeholder="请输入图文回复的摘要">
            </textarea>
            <em>限120个字</em>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label">封面<i class="icon-star-empty"></i></label>
        <div class="controls">
            <div class="pic-wrapper" style="float:left">
            </div>
            <button class="btn upload" name="upload">上传图片</button> <em>(提示：小图片建议尺寸：200像素 * 200像素)</em>
            <div class="pic-preview">
                <img src="/assets/upload/<%= data.pic_url %>" class="img-polaroid" data-name="<%= data.pic_url %>" />
                <span class="hide del"><i class="icon-remove icon-white"></i></span>
            </div>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label">正文<i class="icon-star-empty"></i></label>
        <div class="controls">
            <textarea class="editor"><%= data.desc %></textarea>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label">原文链接</label>
        <div class="controls">
            <input><%= data.url %></input>
        </div>
    </div>
</script>
    
