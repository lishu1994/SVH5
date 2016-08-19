<?php
    include_once("php/config/init.php");
    if(!$_GET['code']){
        if(!$_COOKIE['weixinToCode_123']){
            weixinCode($_GET['code']);
        }else{
            getCode($_GET['code']);
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0,user-scalable=no">
    <title>ITerGet硅谷站</title>
    <meta charset="utf-8" />
    <link type="text/css" rel="stylesheet" href="./css/swiper-3.3.1.min.css">
    <link type="text/css" rel="stylesheet" href="./css/index.css">
    <script type="text/javascript" src="js/zepto.min.js"></script>
    <script type="text/javascript" src="js/swiper-3.3.1.jquery.min.js"></script>
    <script type="text/javascript" src="./js/prefixfree.min.js"></script>
    <script src="./js/fastClick.js"></script>
    <script type="text/javascript" src="js/index.js"></script>
</head>
<body>
<section class="swiper-container">
    <div class="swiper-wrapper">
        <div class="swiper-slide pageFir" trueId="page1">
            <div class="banner">
                <div class="title">干货分享</div>
                <div class="subTitle">海外投资新气象</div>
                <div class="txtImg">
                    <img src="./img/txtImg.png">
                </div>
                <div class="logo"></div>
                <div class="footer">
                    <span class="loopIcon icon1"></span>
                    <p>2016.8 北京</p>
                    <span class="loopIcon icon2"></span>
                </div>
                <div class="arrow"></div>
            </div>
        </div>
        <div class="swiper-slide pageSec" trueId="page2">
            <div class="banner1 ban">50家国内投资机构<br>在硅谷的成功率为零?</div>
            <div class="banner2 ban">硅谷华人创业者<br>对国内投资人存在偏见?</div>
            <div class="banner3 ban">硅谷华人工程师眼里<br>小红书胜过今日头条?</div>
            <div class="banner4">不了解硅谷人才<br>怎能做好海外投资!</div>
            <div class="arrow2"></div>
        </div>
        <div class="swiper-slide pageThird" trueId="page3">
            <div class="pageTitle"></div>
            <div class="blk blk1"></div>
            <div class="blk blk2"></div>
            <div class="arrow3"></div>
        </div>
        <div class="swiper-slide pageFourth" trueId="page4">
            <div class="bannerPage4">
                <div class="item item-1">【活动时间】:8月27日 14:30-16:30</div>
                <div class="item item-2">【活动地点】:酒仙桥恒通商务园区东门</div>
                <div class="item item-3"> Running Cafe</div>
                <div class="item item-4">【活动形式】:免费线下分享会</div>
                <div class="content">
                    <span class="dot dot1"></span>
                    <span class="line line1"></span>
                    <span class="line line2"></span>
                    <span class="line line3"></span>
                    <span class="line line4"></span>
                    <span class="dot dot2"></span>
                    <p class="p p1">14:30-15:00<br>签到入场+茶点</p>
                    <p class="p p3">15:00-16:00<br>分享环节</p>
                    <p class="p p2">16:00-16:30交流环节</p>
                </div>
            </div>
            <div class="arrow"></div>
        </div>
        <div class="swiper-slide pageFifth" trueId="page5">
            <div class="bannerPage5">
                <div class="titlePage5">干货分享</div>
                <div class="subTitlePage5">海外投资新气象</div>
                <span class="igLogo"></span>
                <ul class="txtArea">
                    <li>本活动仅为投资人提供(限报50人)</li>
                    <li>鉴于席位有限,报名需经过主办方审核。</li>
                    <li>审核通过后,我们会将邀请函发送至您的邮箱。</li>
                    <li>活动咨询:Joelle(郭女士) 15959997171</li>
                </ul>
            </div>
            <a class="signUpBtn" href="http://wechat.iterget.com/index.php?s=/addon/LittleFrom/LittleFrom/index">报名入口</a>
        </div>
    </div>
</section>
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
    var base = "http://event.iterget.com/SVH5/";
    var wxconfig1 = {
        title: "硅谷正在吸引中国资本，参与or错过？",
        link: base,
        imgUrl: base + "img/share.jpg",
        success: function(){
            $.get("php/View/?service=share",{type:1});
        }
    };
    var wxconfig2 = {
        title: "硅谷正在吸引中国资本，参与or错过？",
        desc: "坐在办公室里，是憋不出未来的！不了解1000位硅谷IT人才，怎能做好海外投资？",
        link: base,
        imgUrl: base + "img/share.jpg",
        success: function(){
            $.get("php/View/?service=share",{type:0});
        }
    };

    var uri = location.href.split("#")[0];
    var path = uri.split("?")[0];
    var query = uri.split("?")[1];
    uri = path + (query ? ("?" + encodeURI(query)) : "");

    $.post("php/jsapi/index.php", {
        uri: uri
    }, function (data) {
        data = eval("(" + data + ")");
        var apilist = [
            'onMenuShareTimeline',
            'onMenuShareAppMessage'
        ];
        wx.config({
            debug: false,
            appId: data.appid,
            timestamp: data.timestamp,
            nonceStr: data.noncestr,
            signature: data.signature,
            jsApiList: apilist
        });
        wx.error(function (res) {
            alert(JSON.stringify(res));
        });
    });
    wx.ready(function () {
        wx.onMenuShareTimeline(wxconfig1);
        wx.onMenuShareAppMessage(wxconfig2);
    });

    var _hmt = _hmt || [];
    (function () {
        var hm = document.createElement("script");
        hm.src = "//hm.baidu.com/hm.js?1c1f0607e1df9bac8bb7407b211de5a2";
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(hm, s);
    })();
</script>
</body>
</html>
