define(['jquery', 'k', 'text!../../editor/themes/default/default.css'], function($) {
    return {
        collect: {},
        wechat: function(se) {
            var options = {
                allowPreviewEmoticons : false,
                allowFileManager: false,
                pasteType : 1,
                resizeType : 0,
                width : '100%',
                height: '300px',
                items: [
                    'emoticons'
                ]
            };
            if (arguments[1]) {
                var option = arguments[1];
                options.afterChange = function() {
                    var sel = KindEditor(option.count);
                    if (sel) {
                        sel.html(this.count('text'));
                    }
                    var sel = KindEditor(option.msg);
                    if (sel) {
                        sel.html(this.html() || '回复内容');
                    }
                }
            }
            if ($(se).length > 0) {
                var edit = KindEditor.create(se, options);
                this.collect[se] = edit;
            }
        },
        create: function(se) {
            var options = {
                resizeType : 1,
                allowPreviewEmoticons : false,
                uploadJson: '/admin/upload/json/',
                allowFileManager: true,
                items: [
                    'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline',
                    'removeformat', '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
                    'insertunorderedlist', '|', 'emoticons', 'image', 'multiimage', 'link'
                ]
            };
            if ($(se).length > 0) {
                var edit = KindEditor.create(se, options);
                this.collect[se] = edit;
            }
        },
        get: function(se) {
            return this.collect[se] || false;
        },
        multiupload: function(se, view, callback) {
            var name = '_multiupload';
            var editor = this.get(name) || KindEditor.editor({
                uploadJson: '/admin/upload/json/',
                allowFileManager: true
            });
            KindEditor(se).click(function() {
                editor.loadPlugin('multiimage', function() {
                    editor.plugin.multiImageDialog({
                        clickFn : function(urlList) {
                            var div = KindEditor(view);
                            KindEditor.each(urlList, function(i, data) {
                                div.append('<li><img src="' + data.url + '" class="img-polaroid" data-name="' + data.file  + '" /><span class="hide del"><i class="icon-remove icon-white"></i></li>');
                            });
                            if (callback && typeof(callback) === "function") { 
                                callback(urlList); 
                            }
                            editor.hideDialog();
                        }
                    });
                });
            });
        },
        singleupload: function(se, view) {
            var name = '_singleupload';
            var editor = this.get(name) || KindEditor.editor({
                uploadJson: '/admin/upload/json/',
                allowFileManager: false
            });
            KindEditor(se).click(function() {
                editor.loadPlugin('image', function() {
                    editor.plugin.imageDialog({
                        showRemote: false,
                        imageUrl: KindEditor(view).attr('data-name'),
                        clickFn: function(url, title) {
                            KindEditor(view).attr('src', url).attr('data-name', title);
                            editor.hideDialog();
                        }
                    });
                });
            });
        }
    };
});
