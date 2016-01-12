define(['backbone', 'text!tpl/shop'], function(Backbone, tpl){
    var Item = Backbone.Model.extend({
        url: '/shop/goods/',
    });
    var App = Backbone.View.extend({
        el: $('#content'),
        model: Item,
        template: _.template(_.unescape($('#tpl-shop', tpl).html())),
        initialize: function() {
            _.bindAll(this, 'render');
            this.model = new Item();

            var self = this;
            this.model.fetch({
                'success': function(model, resp) {
                    self.render();
                }
            });
        },
        render: function() {
            this.$el.html(this.template({data: this.model.toJSON()}));
            return this;
        }
    });

    return App;
});
