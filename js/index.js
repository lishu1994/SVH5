$(function () {
    FastClick.attach(document.body);
    (function (desW) {
        var winW = document.documentElement.clientWidth;
        document.documentElement.style.fontSize = winW/desW * 100 + 'px';
    })(750);

    var divList = null;
    var swp = new Swiper('.swiper-container',{
        loop:true,
        direction:'vertical',
        onSlidePrevEnd:function (swp) {
            change(swp);
        },
        onSlideNextEnd:function (swp) {
            change(swp);
        },

    });
    function change(obj) {
        divList = $('.swiper-slide');
        [].forEach.call(divList,function (curDiv,index) {
            curDiv.id = index === obj.activeIndex? curDiv.getAttribute('trueId') : null;
        })
    }
});





