define(['underscore', 'backbone', 'text!tpl/autoreply'], function(_, Backbone, tpl){
    var Item = Backbone.Model.extend({
        url: '/admin/autoreply/',
        defaults: {
            id: null,
            content: ''
        },
        parse: function(resp) {
            return resp.data || resp;
        }
    });
    var App = Backbone.View.extend({
        el: $('#data'),
        template: _.template(tpl),
        events: {
            'click button.save': 'save',
            'click button.del': 'del'
        },
        initialize: function() {
            _.bindAll(this, 'render', 'save', 'del');
            this.model = new Item();
            this.model.bind('save', this.save);

            var that = this;
            this.model.fetch({
                'success': function(model, res) {
                    that.render();
                }
            });
        },
        render: function() {
            $(this.el).html(this.template({data: this.model.toJSON()}));
            return this;
        },
        save: function() {
            this.model.set('content', $('[name="content"]', this.el).val());
            this.model.save(null, {
                success: function(model, res) {
                    alert('保存成功');
                },
                error: function(model, res) {
                    alert('保存失败');
                }
            });
        },
        del: function() {
            if (confirm('是否删除?')) {
                var that = this;
                this.model.destroy({
                    success: function(model, res) {
                        that.model.clear();
                        that.render();
                        alert('删除成功');
                    },
                    error: function(model, res) {
                        alert('删除失败');
                    }
                });
            }
        }
    });

    return App;
});
