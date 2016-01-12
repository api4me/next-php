define(['jquery', 'underscore', 'admin/k', 'text!tpl/matter'], function($, _, K, tpl) {
    return {
        type: 'text',
        data: {
            text: {},
            news: [],
        },
        create: function(se) {
            var type = $(se).attr('data-type');
            var id = $(se).attr('data-id');
            var self = this;

            $.get('/admin/matter/ajax/', {type: type, id: id}, function(resp) {
                $(se).html(_.template($('.main', tpl).html()));
                if (resp.status = 200) {
                    if (resp.data) {
                        self.data[type] = resp.data;
                    }
                    $('.wechat [name="type"]').val(type);
                }
                $('.wechat [name="type"]').trigger('change');
            }, 'json');

            $(se).on('change', '[name="type"]', function() {
                if ($(this).val() == 'text') {
                    self.type = 'text';
                    $('.wechat .message-content').html(_.template(_.unescape($('#tpl-text', tpl).html()), {data: self.data.text}));
                    K.wechat('.wechat .editor', {count: '.wechat .count', msg: '.wechat .msg'});
                    $('.wechat .editor').change(function() {
                        self.data.text.content = $(this).val();
                    });
                    $('.wechat .mod-msg-editor').mouseout(function() {
                        K.get('.wechat .editor').sync();
                        $('.wechat .editor').trigger('change');
                    });
                } else {
                    self.type = 'news';
                    // Init frame
                    $('.wechat .message-content').html(_.template(_.unescape($('#tpl-news', tpl).html())));
                    _.each(self.data.news, function(v) {
                        if (!_.isEmpty(v.pid) && v.pid != '0') {
                            $('.wechat .add').before(_.template(_.unescape($('#tpl-news-item', tpl).html())));
                        }
                    });
                    $('.wechat #news-value').html(_.template(_.unescape($('#tpl-news-value', tpl).html()), {data: _.first(self.data.news) || {}, idx: 0}));
                    K.wechat('.wechat .editor');
                    K.singleupload('button[name="upload"]', '.pic-preview img');

                    // Event
                    $('.wechat .add').click(function() {
                        // TODO
                        if (self.data.news.length >= 9) {
                            alert('亲，图文信息最多10项');
                        }
                        $('.wechat #news-value').trigger('mouseout');
                        $(this).before(_.template(_.unescape($('#tpl-news-item', tpl).html())));
                        $('.wechat #news-value').html(_.template(_.unescape($('#tpl-news-value', tpl).html()), {data: {}, idx: self.data.news.length}));
                        var obj = {
                            'title': '',
                            'author': '',
                            'pic_url': '',
                            'desc': '' 
                        }
                        self.data.news.push(obj);

                        $('.wechat .mod-msg-arrow').css('top', (132 + (self.data.news.length - 1) * 98) + 'px');
                    });
                    $('.wechat #news-value').mouseout(function() {
                        // Save
                        var idx = $('.wechat #news-value').find('[name="idx"]').val();
                        K.get('.wechat .editor').sync();
                        var obj = {
                            'id': $('.wechat #news-value [name="id"]').val(),
                            'title': $('.wechat #news-value [name="title"]').val(),
                            'author': $('.wechat #news-value [name="author"]').val(),
                            'pic_url': $('.wechat #news-value .pic-preview img:first').attr('data-name'),
                            'desc': $('.wechat #news-value .editor').val()
                        }
                        if (self.data.news.length > parseInt(idx)) {
                            self.data.news[idx] = obj;
                        } else {
                            self.data.news.push(obj);
                        }
                    });
                    $('.wechat').on('mouseover', '.mod-msg.multi>div', function() {
                        $('.edit-msg-cover').hide();
                        $('.edit-msg-cover', $(this)).show(); 
                    }).on('mouseout', '.mod-msg.multi>div', function(){
                        $('.edit-msg-cover').hide();
                    });
                    $('.wechat').on('click', '.edit', function() {
                        // Change
                        var idx = $('.wechat .edit').index($(this));
                        $('.wechat #news-value').html(_.template(_.unescape($('#tpl-news-value', tpl).html()), {data: self.data.news[idx], idx: idx}));
                        K.wechat('.wechat .editor');
                        K.singleupload('button[name="upload"]', '.pic-preview img');
                        $('.wechat .mod-msg-arrow').css('top', (132 + idx * 98) + 'px');
                    });
                }
            });
        },
        save: function(callback) {
            var data;
            if (this.type == 'text') {
                data = this.data.text;
            } else {
                data = this.data.news
            }
            var self = this;

            $.post('/admin/matter/save/', {type: this.type, data: data}, function(resp) {
                if (resp.status == 400) {
                    alert(resp.msg);
                } 
                if (resp.status == 200 && typeof callback === 'function') {
                    callback(self.type, resp.data);
                }
            }, 'json');
        }
    }

});
