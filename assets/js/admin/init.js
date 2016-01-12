define(['jquery'], function($) {
    $(document).ajaxStart(function(){
        $("button").attr("disabled","true");
    });
    $(document).ajaxStop(function(){
        $("button").removeAttr("disabled");
    });
    if (navigator.userAgent.match(/msie [678]/i)) {
        $(".layout-page").before('<div class="alert text-center"><strong>注意: </strong>为了获得更好的使用体验，请使用<a href="http://windows.microsoft.com/zh-cn/internet-explorer/download-ie" target="_blank">IE9及以上浏览器</a>，或<a href="http://www.google.com/chrome/" target="_blank">谷歌</a>，<a href="http://firefox.com.cn/" target="_blank">火狐</a>等浏览器。</div>');
    }

    (function () {
        var $backToTopEle = $('<div class="backtop"><a href="javascript:;" title="返回页面顶部"></a></div>').appendTo($('body'));

        $('.backtop').click(function () {
            $("html, body").animate({ scrollTop: 0 }, 200);
        });

        var _w = ($(window).width() - 980) / 2 - 140;

        $backToTopFun = function () {
            var st = $(document).scrollTop(),
            winh = $(window).height();
            $backToTopEle.css("right", _w);
            (st > 100) ? $backToTopEle.fadeIn() : $backToTopEle.fadeOut();
            // For IE6
            if (!window.XMLHttpRequest) {
                $backToTopEle.css("top", st + winh - 100);
            }
        };
        $(window).bind("scroll", $backToTopFun);
        $backToTopFun();
    })();

    $("div.block label.tip").click(function (){
        $(this).hide();
        $(this).prev().focus();
    });
    $("div.block input").focus(function () {
        $(this).next().hide();
    }).blur(function () {
        if ($(this).val() == "") $(this).next().show();
    });

});
