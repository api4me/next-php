({
    baseUrl: '../../assets/js',
    name: 'app',
    out: 'dist/product.js',
    paths: {
        'underscore': 'lib/underscore-min',
        'zepto': 'lib/zepto.min',
        'iscroll': 'lib/iscroll',
        'hack': 'lib/hack',
        'area': 'lib/area',
        'text!/api/area/': 'empty:',
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
    },
    stubModules : ['text'],
    include: [
        'app/home',
        'app/delivery',
        // 'app/order',
        'app/goods',
        'app/address',
        'app/help',
        'app/coupon',
        'app/article',
        'app/invite',
        'app/gift'
    ],
    optimize: 'none'
})
