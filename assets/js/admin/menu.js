define(['underscore', 'backbone', 'text!tpl/menu'], function(_, Backbone, tpl){
    var Item = Backbone.Model.extend({
        defaults: {
            type: '',
            name: '',
            key: '',
            url: '',
            sort: 1,
            pid: 0
        },
        parse: function(resp) {
            return resp.data || resp;
        }
    });

    var List = Backbone.Collection.extend({
        url: '/admin/menu/',
        model: Item
    });

    var ItemView = Backbone.View.extend({
        tagName: 'li',
        template: _.template(_.unescape($('#menuItem', tpl).html())),
        pview: null,
        events: {
            'mouseover': 'mouseover',
            'mouseout': 'mouseout',
            'click span.add': 'add',
            'click span.edit': 'edit',
            'click span.del': 'del'
        },
        initialize: function(op) {
            _.bindAll(this, 'render', 'unrender', 'mouseover', 'mouseout', 'edit', 'del');

            if (op.pview) {
                this.pview = op.pview;
            }

            this.model.bind('change', this.render);
            this.model.bind('del', this.del);
        },
        render: function() {
            this.$el.html(this.template({data: this.model.toJSON()}));
            return this;
        },
        unrender: function() {
            $(this.el).remove();
        },
        mouseover: function() {
            $('.tool', this.el).show();
        },
        mouseout: function() {
            $('.tool', this.el).hide();
        },
        add: function() {
            var item = new Item({pid: this.model.id});
            var modal = new MenuModal({
                model: item 
            });
            modal.render();

            var self = this;
            this.listenTo(item, 'change', function() {
                self.pview.collection.create(item);
            });
        },
        edit: function() {
            var modal = new MenuModal({
                model: this.model
            });
            modal.render();
        },
        del: function() {
            this.model.destroy();
            this.remove();
        }
    });

    var MenuModal = Backbone.View.extend({
        className: 'modal hide fade',
        events: {
            'click button.save': 'save'
        },
        initialize: function() {
            _.bindAll(this, 'render', 'save');
            this.model.bind('save', this.save);

            this.render();
        },
        render: function() {
            this.$el.html($('#menuModal', tpl).html());
            $('[name="menu-name"]', this.el).val(this.model.get('name'));
            this.$el.modal({show: true, keyboard: false});
            return this;
        },
        save: function() {
            this.model.set('name', $('[name="menu-name"]', this.el).val());
            this.$el.modal('hide');
        }
    });

    var App = Backbone.View.extend({
        el: $('#data'),
        template: _.template(tpl),
        events: {
            'click button.add': 'addItem',
            'click button.save': 'saveItem'
        },
        initialize: function() {
            _.bindAll(this, 'render', 'addItem', 'appendItem');
            this.collection = new List();
            this.collection.bind('add', this.appendItem);
            this.curmenu = null;

            var self = this;
            this.collection.fetch({
                'success': function(model, res) {
                    self.render();
                }
            });
        },
        render: function() {
            $(this.el).html(this.template({data: this.collection.toJSON()}));

            return this;
        },
        addItem: function() {
            var item = new Item();
            var itemView = new ItemView({
                model: item,
                pview: this
            });
            itemView.add();
        },
        appendItem: function(item) {
            var itemView = new ItemView({
                model: item,
                pview: this
            });
            $('ul', this.el).append(itemView.render().el);
        }
    });

    return App;
});
