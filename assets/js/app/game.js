require.config({
    baseUrl: '/assets/js/',
    paths: {
        'zepto': 'lib/zepto.min'
    },
    shim: {
        'zepto':{
            exports: '$'
        }
    }
});

require(['zepto', 'app/wxapi'], function($, Wxapi){
    var view = function() {

        var H5 = {
            'root': function() {
                return location.protocol + '//' + location.host;
            },
            'wxshare': function(who) {
                $('.wxshare').click(function(){
                    $(this).hide();
                });
                if (who == 'friend') {
                    $('.wxshare-text').text('请点击右上角，将它发送给指定的朋友');
                } else if (who == 'subscribe') {
                    $('.wxshare-text').text('请点击右上角，"查看公众号"，关注我们');
                } else {
                    $('.wxshare-text').text('请点击右上角，将它分享到朋友圈');
                }
                $('.wxshare').show();
            }
        };
        
        var test = [
            {
                'title':'1.健康成年人体内水分含量？(10分)',
                'options':[
                    'A．90%',
                    'B．80%',
                    'C．70%',
                    'D．50%'
                ],
                'answer':2
            },
            {
                'title':'2.以下哪种水最适宜饮用，且有益人体健康？(10分)',
                'options':[
                    'A．矿物质水',
                    'B．白开水',
                    'C．天然矿泉水',
                    'D．纯净水'
                ],
                'answer':2
            },
            {
                'title':'3.找出与另外三种属性不同的选项。(10分)',
                'options':[
                    'A．可乐',
                    'B．矿泉水',
                    'C．冰红茶',
                    'D．果汁饮品'
                ],
                'answer':1
            },
            {
                'title':'4.以下哪种对水在人的生命行程中的描述是错误的？(10分)',
                'options':[
                    'A．新陈代谢的能源',
                    'B．血液中几乎不含水',
                    'C．关节、肌肉和体腔的润滑液和避震器',
                    'D．流动的恒温空调'
                    ],
                'answer':1
            },
            {
                'title':'5.以下哪种说法正确？(10分)',
                'options':[
                    'A．喝饮料可以代替喝水',
                    'B．水主要就是解渴，喝什么水都差不多',
                    'C．饮用天然矿泉水可以加速排出代谢废物',
                    'D．白开水比天然水更有益健康'
                ],
                'answer':2
            },
            {
                'title':'6.以下哪种说法错误？(10分)',
                'options':[
                    'A．边吃饭边喝水是不好的习惯',
                    'B．运动后应该马上喝水',
                    'C．不要等到口渴再喝水',
                    'D．应喝天然弱碱性水'
                ],
                'answer':1
            },
            {
                'title':'7.“药补不如食补，食补不如水补”，是哪位医学家说的？(10分)',
                'options':[
                    'A．李时珍',
                    'B．张仲景',
                    'C．华佗',
                    'D．孙思邈'
                ],
                'answer':0
            },
            {
                'title':'8.尔冬吉火山岩矿泉水经过一万八千多年_____天然过滤矿化形成的天然水体，才使得入口润滑甘甜？(10分)',
                'options':[
                    'A．石灰岩',
                    'B．玄武岩与砂砾岩',
                    'C．石英岩',
                    'D．大理岩'
                ],
                'answer':1
            },
            {
                'title':'9.尔冬吉火山岩矿泉水含人体必须的18种微量元素中的13种，其中的硒（Se）有什么作用？(10分)',
                'options':[
                    'A．抗氧化、抗癌',
                    'B．促进牙齿珐琅质',
                    'C．促进骨骼生长，维持糖和脂肪代谢',
                    'D．降低血压、预防心脏病'
                ],
                'answer':0
            },
            {
                'title':'10.自然界大部分水都是大分子团水，小分子团水（半峰宽在90Hz内，5-7水分子组成）世所罕有，您知道以下哪种水是小分子团水？(10分)',
                'options':[
                    'A．自来水',
                    'B．矿物质水',
                    'C．火山岩冷矿泉水',
                    'D．纯净水'
                ],
                'answer':2
            },            
        ];
        var test_count = test.length;
        var score = 0;
        var i = 0;
        
        $(".start-test").click(function(){
            var html = testhtml();
            $("#knowledge").html(html);
            $(".game-start").remove();
        });
        
        $('#knowledge').on('click', '.J-check', function(e){
            var question = test[i];
            var answer = $(this).attr('data-val');
            if(parseInt(answer) == parseInt(question.answer)){
                score = score + 10;
            }
            i = i + 1;
            if( i == test_count){
                var html = showScore();
                $("#knowledge").html(html);
            }else{
                var html = testhtml();
                $("#knowledge").html(html);
            }
        });
        function testhtml(){
            if(parseInt(i)<=0){
                i=0;
            }
            if( i == test_count){
                var html = showScore();
                return html;
            }else{
                var question = test[i];
                var html = "<div class='game-title'></div>";
                html = html + "<div class='game-body'>";
                html = html + "<div class='question-title'>" + question.title + "</div>";
                html = html + "<ul>";
                for( j=0;j < question.options.length; j++){
                    html = html + "<li class='J-check' data-val='"+j+"'>" + question.options[j] +"</li>";
                }
                html = html + "</ul>";
                html = html + "<div class='question-num'>< "+ (i+1) +"/"+ test_count +" ></div>";
                html = html + "</div>";
                html = html + "<div class='back'></div>";
                return html;
            }
        }
        
        function showScore(){
            var garde = '';
            var recover = '';
            if (score > 100 || score <= 60){
                recover = "<div class='recover go-on'>再接<br>再厉</div>";
                garde = '水盲';
            } 
            if(score == 100){
                recover = "<div class='recover good'>赞</div>";
                garde = '水知识大师';
            }
            if(score == 90){
                recover = "<div class='recover good'>赞</div>";
                garde = '水知识专家';
            }
            if(score == 80){
                recover = "<div class='recover good'>赞</div>";
                garde = '水知识高手';
            }
            if(score == 70){
                recover = "<div class='recover go-on'>再接<br>再厉</div>";
                garde = '水知识新手';
            }
            var html = "<div class='game-title'></div>";
            html = html + "<div class='game-body end'>";
            html = html + recover;
            html = html + "<div class='score'>您的得分为："+ score +"分</div>";
            html = html + "<div class='grade'>您的水知识评级为："+ garde +"</div>";       
            html = html + "<div class='game-notice'>得分为80分及以上的水专家，请发送“截图+姓名+联系方式”到尔冬吉微信平台（点击下方一键关注微信号：ertungey），即有机会获得4℃火山岩冷矿泉水1箱（价值￥230元），中奖名单敬请关注每周末推送新闻。</div>";
            html = html + "<div class='down'></div>";
            html = html + "<div class='wx-button'><button class='btn icon-wx-share btn-game'>我要分享</button>";
            html = html + "<a href='http://mp.weixin.qq.com/s?__biz=MzA3ODgwNTExMQ==&mid=200744048&idx=1&sn=302ae27eb8734dcce7dbdc272aae65ce#rd' class='btn pull-right btn-game'>一键关注尔冬吉</a>";
            html = html + "</div></div>";
            html = html + "<div class='back'></div>";
            score = 0;
            i = 0;
            return html;
        }
        $('#knowledge').on('click', '.icon-wx-share', function(){
            H5.wxshare();
        });
        
        Wxapi.ready(function(Api) {
            var appid = $('[name="appid"]').val();
            var wxData = {
                'appId': appid,
                'imgUrl': H5.root() + '/assets/img/logo.1.2.jpg',
                'link': H5.root() + '/shop/game/knowledge/',
                'desc': '尔冬吉健康社区水知识闯关活动开始啦，参与即有机会获得活动好礼，还等什么赶紧来闯关吧！',
                'title': '尔冬吉健康社区水知识闯关'
            };
            Api.shareToTimeline(wxData);
            Api.shareToFriend(wxData);
        });

    };
    
    $(function() {
        new view();
    });
});
