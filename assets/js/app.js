require.config({
    baseUrl: '/assets/js/',
    paths: {
        'underscore': 'lib/underscore-min',
        'zepto': 'lib/zepto.min',
        'iscroll': 'lib/iscroll',
        'hack': 'lib/hack',
        'area': 'lib/area',
        'text': 'lib/text',
        'ratchet': '../ratchet/js/ratchet'
    },
    shim: {
        'underscore':{
            exports: '_'
        },
        'zepto':{
            exports: '$'
        },
        'iscroll':{
            exports: 'iscroll'
        },
        'ratchet': {
            deps: ['hack'],
            exports: 'ratchet'
        }
    }
});

require(['zepto', 'ratchet', 'underscore', 'app/h5', 'app/wxapi', 'iscroll'], function($, R, _, H5, Wxapi, Iscroll) {
    "use strict";

    sessionStorage.clear();
    $(document).on('ajaxBeforeSend', function(){
        $("button").attr("disabled","true");
        $('.loading').show();
    });
    $(document).on('ajaxStop', function(){
        $("button").removeAttr("disabled");
        $('.loading').hide();
    });

    var myScroll = new IScroll('#content', {
        scrollbars: false,
        mouseWheel: true,
        interactiveScrollbars: true,
        shrinkScrollbars: 'scale',
        fadeScrollbars: true
    }); 

    window.addEventListener('popstate', function(e) {
        var href = $('header a.pull-left').attr('href');
        if (e.state && href) {
            location.href = href;
        }
        e.stopPropagation();
    });

    H5.goto(location.href);
    window.addEventListener('push', function(e) {
        var url = e.detail.state.url;
        var start = url.indexOf('/', 7);
        var end = url.indexOf('?');
        if (end == -1) {
            url = url.substr(start);
        } else {
            url = url.substring(start, end);
        }
        console.log(url);

        switch(url) {
            case '/shop/':
            case '/shop/home/':
                require(['app/home'], function(Home) {
                    return new Home();
                });
                break;
            case '/shop/water/':
                require(['app/water'], function(Water) {
                    return new Water();
                });
                break;
            case '/shop/delivery/':
            case '/shop/delivery/reserve/':
                require(['app/delivery'], function(Delivery) {
                    return new Delivery();
                });
                break;
            case '/shop/order/':
            case '/shop/order/check/':
                require(['app/order'], function(Order) {
                    return new Order();
                });
                break;
            case '/shop/goods/':
            case '/shop/goods/buy/':
            case '/shop/goods/step1/':
            case '/shop/goods/step2/':
            case '/shop/goods/step3/':
            case '/shop/goods/step4/':
                require(['app/goods'], function(Goods) {
                    return new Goods();
                });
                break;
            case '/shop/address/':
            case '/shop/address/add/':
                require(['app/address'], function(Address) {
                    return new Address();
                });
                break;
            case '/shop/help/':
            case '/shop/help/feedback/':
            case '/shop/help/apply-refund/':
                require(['app/help'], function(Help) {
                    return new Help();
                });
                break;
            case '/shop/coupon/':
            case '/shop/coupon/get/':
            case '/shop/coupon/exchange/':
            case '/shop/coupon/step1/':
            case '/shop/coupon/step2/':
            case '/shop/coupon/step3/':
            case '/shop/coupon/step4/':
                require(['app/coupon'], function(Coupon) {
                    return new Coupon();
                });
                break;
            case '/shop/help/aboutus/':
            case '/shop/article/':
                require(['app/article'], function(Article) {
                    return new Article();
                });
                break;
            case '/shop/invite/':
                require(['app/invite'], function(Invite) {
                    return new Invite();
                });
                break;
            case '/shop/gift/':
            case '/shop/gift/details/':
            case '/shop/gift/step1/':
            case '/shop/gift/step2/':
                require(['app/gift'], function(Gift) {
                    return new Gift();
                });
                break;
            case '/shop/game/':
            case '/shop/game/knowledge/':
                require(['app/game'], function(Game) {
                    return new Game();
                });
                break;
        }
        
    });
});
