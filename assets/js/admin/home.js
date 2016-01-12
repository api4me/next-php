define(['backbone'], function(Backbone){
    var App = Backbone.View.extend({
        el: $('#content'),
        initialize: function() {
            _.bindAll(this, 'render');

            this.render();
        },
        render: function() {
            $.aj
            $(this.el).html('<div>home</div>');
            return this;
        }
    });

    return App;
});
