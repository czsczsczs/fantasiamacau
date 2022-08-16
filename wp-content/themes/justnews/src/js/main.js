require('../../../Themer/src/js/bootstrap');

require('../../../Themer/src/js/swiper.jquery');

import Share from '../../../Themer/src/js/social-share';

require('../../../Themer/src/js/common');

import Message from '../../../Themer/src/js/message';

import Notification from '../../../Themer/src/js/notification';

import Follow from '../../../Themer/src/js/follow';

import UserCard from '../../../Themer/src/js/user-card';

import Html2Canvas from '../../../Themer/src/js/html2canvas';

import Smilies from '../../../Themer/src/js/smilies';

import HiddenContent from '../../../Themer/src/js/hidden-content';

const Macy = require('macy');





jQuery(function($) {

    const $win = $(window);

    let winHeight = $win.height();

    let webp = typeof _wpcom_js.webp !== 'undefined' && _wpcom_js.webp ? _wpcom_js.webp : null;

    let $navbar = $('.navbar-toggle').is(':hidden');

    let $header = $('header.header');



    let swiper_args = {

        onInit: function(el){

            $(el.container[0]).on('click', '.swiper-button-next', function () {

                el.slideNext();

            }).on('click', '.swiper-button-prev', function () {

                el.slidePrev();

            }).find('.j-lazy').lazyload({

                webp: webp

            });

            setTimeout(function () {

                jQuery(window).trigger('scroll');

            }, 800);

        },

        pagination: '.swiper-pagination',

        paginationClickable: true,

        simulateTouch: false,

        loop: true,

        autoplay: _wpcom_js.slide_speed ? _wpcom_js.slide_speed : 5000,

        effect: 'slide',

        onSlideChangeEnd: function(){

            jQuery(window).trigger('scroll');

        }

    };



    Message.init();

    Notification.init();

    Follow.init();

    UserCard.init();

    Html2Canvas.init();

    Smilies.init();

    HiddenContent.init();



    window.kx_share = function(el){

        let $el = $(el).closest('.kx-item');

        if($el.length && $el.hasClass('entry-footer')) {

            $el = $('.entry');

            return {

                title: $.trim($el.find('.entry-title').text()),

                description: $.trim($el.find('.entry-content').text()).replace('[原文链接]', ''),

                url: window.location.href,

                image: $el.find('.entry-content img').attr('src')

            }

        }else if($el.length){

            let title = $el.find('.kx-title').length ? $el.find('.kx-title').text() : $el.find('.kx-content h2').text();

            let _title = title.match(/^\s*([^\s]+)\s*$/);

            return {

                title: _title && _title[1] ? _title[1] : '',

                description: $.trim($el.find('.kx-content p').text()).replace('[原文链接]', ''),

                url: $el.find('.kx-meta').data('url'),

                image: $el.find('.kx-content img').length ? $el.find('.kx-content img').attr('src') : ''

            }

        }

    }



    window.zt_share = function(el){

        let $el = $(el).closest('.special-item');

        if($el.length) {

            return {

                title: $.trim($el.find('.special-item-title h2').text()),

                description: $.trim($el.find('.special-item-desc').text()),

                url: $el.find('.special-item-more').attr('href'),

                image: $el.find('.special-item-thumb img').attr('src')

            }

        }

    }



    mobile_dropdown_menu();

    $win.on('resize', function(){

        $navbar = $('.navbar-toggle').is(':hidden');

        winHeight = $win.height();

        $('body').trigger('DOMNodeInserted');

        mobile_dropdown_menu();

    });



    new Swiper($('.swiper-container').not('.main-slider,.entry-content-slider'), Object.assign({}, swiper_args));

    new Swiper('.main-slider', Object.assign({ breakpoints: { 767: { autoHeight: true } } }, swiper_args));



    const $video = $('.entry .entry-video');

    if($video.length){

        $video.height(parseInt($video.width()/(860/(typeof _wpcom_js.video_height !== 'undefined' ? _wpcom_js.video_height : 483))));

    }

    const $sidebar = $('.sidebar');



    function mobile_dropdown_menu(){

        if(!$navbar){

            var $dropdown = $('header li.dropdown');

            for(var i=0;i<$dropdown.length;i++){

                var $item =  $($dropdown[i]);

                if($item.find('.m-dropdown').length==0) $item.append('<div class="m-dropdown"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-arrow-down"></use></svg></i></div>');

            }

        }

    }



    $('.modules-navs').each(function (i, el) {

        var $el = $(el), maxHeight = 0, $item = $el.find('.list-navs>.navs-link');

        $item.outerHeight('');

        $item.each(function (x, item) {

            var h = $(item).outerHeight();

            if( h>maxHeight ) maxHeight = h;

        });

        $item.outerHeight(maxHeight);

    });



    const $wrap = $('#wrap');

    const $footer = $('footer.footer');



    if ($wrap.find('.post-loop-masonry').length) {

        $wrap.find('.post-loop-masonry').each(function (i, el) {

            let loaded = 0;

            $(el).find('.item-thumb img').one('load', function () {

                if (loaded === 1) { // 需要有一张图片加载完成以后再渲染，通常第一张图片是 lazyload替代图

                    init_macy(el);

                } else if (loaded === 2) { // 重发重新渲染，第二张图片才能确定是文章图片，主要针对iOS优化

                    $(el).trigger('masonry_reload');

                }

                loaded++;

            });

        });

    }



    $('body').on('DOMSubtreeModified', 'img', function() {

        if($wrap.length) $wrap.css('min-height', winHeight-$wrap.offset().top-$footer.outerHeight());

    }).on('DOMNodeInserted', function() {

        if($wrap.length) $wrap.css('min-height', winHeight-$wrap.offset().top-$footer.outerHeight());

    }).on('click', '.kx-new', function(){

        window.location.href = window.location.href;

    }).on('click', '.widget-kx-list .kx-title', function(){

        var $this = $(this);

        $this.parent().find('.kx-content').slideToggle('fast');

        $this.closest('.kx-item').toggleClass('active');

        $win.trigger('scroll');

    }).on('DOMNodeInserted', '.navbar-action', function () {

        mobile_dropdown_menu();

    }).on('click', '.j-post-tab', function (){

        let $this = $(this);

        let $el = $this.closest('.widget'), index = $this.index(), $wrap = $el.find('.j-post-tab-wrap');

        $el.find('.j-post-tab').removeClass('active');

        $this.addClass('active');

        $wrap.removeClass('active').eq(index).addClass('active');

        $win.trigger('scroll');

    }).trigger('DOMNodeInserted');





    if(_wpcom_js.fixed_sidebar && _wpcom_js.fixed_sidebar == '1' && $sidebar.length && $sidebar.find('.widget').length && $win.width() > 991) {

        for(let i=0;i<$sidebar.length;i++){

            fixedSidebar($($sidebar[i]));

        }

    }



    const $kx_list = $('.kx-list');

    if($kx_list.length && !$kx_list.closest('.tab-wrap').length) {

        window.kxDate = $kx_list.find('.kx-date');

        let header_top;

        header_top = $header.outerHeight() + $header.position().top;

        let first_top = kxDate.first().offset().top;

        let cur_date = {'$el': null};

        let $nkx = $('.kx-new');

        let kxh = kxDate.first().outerHeight();

        $win.on('scroll', function () {

            let top = $win.scrollTop();

            let dateLen = kxDate.length-1;

            $.each( kxDate, function (i, el) {

                var $el = $(el);

                var cur_offset = $el.offset().top-top-header_top;



                if( cur_offset > 0 && cur_date.$el && cur_date.top < 0 ){

                    kxDate.removeClass('fixed').css({'width': 'auto', 'top': 'auto'});

                    cur_date.$el.addClass('fixed').css('top', header_top).css('width', $kx_list.outerWidth());

                    $nkx.addClass('fixed').css({'top': header_top+51, 'width': $kx_list.outerWidth()});

                    $kx_list.css('padding-top', kxh);

                    return;

                }else if(i === 0 && cur_offset<=0){

                    if(first_top-header_top>=top){

                        kxDate.removeClass('fixed').css({'width': 'auto', 'top': 'auto'});

                        $nkx.removeClass('fixed').css('width', 'auto');

                        $kx_list.css('padding-top', '');

                    }else{

                        kxDate.removeClass('fixed').css({'width': 'auto', 'top': 'auto'});

                        $el.addClass('fixed').css('top', header_top).css('width', $kx_list.outerWidth());

                        $nkx.addClass('fixed').css({'top': header_top+51, 'width': $kx_list.outerWidth()});

                        $kx_list.css('padding-top', kxh);

                    }

                    cur_date.$el = $el;

                    cur_date.top = cur_offset;

                    return;

                }else if(i === dateLen && cur_offset<=0){

                    kxDate.removeClass('fixed').css({'width': 'auto', 'top': 'auto'});

                    $el.addClass('fixed').css('top', header_top).css('width', $kx_list.outerWidth());

                    $nkx.addClass('fixed').css({'top': header_top+51, 'width': $kx_list.outerWidth()});

                    $kx_list.css('padding-top', kxh);

                }else if(i===0 && cur_offset > 0 && kxDate.hasClass('fixed')) {

                    kxDate.removeClass('fixed').css({'width': 'auto', 'top': 'auto'});

                    $nkx.removeClass('fixed').css('width', 'auto');

                    $kx_list.css('padding-top', '');

                }

                cur_date.$el = $el;

                cur_date.top = cur_offset;



            });

        });



        setInterval(function () {

            let id = $('.kx-item').first().data('id');

            $.ajax({

                url: _wpcom_js.ajaxurl,

                data: {id: id, action: 'wpcom_new_kuaixun'},

                method: 'POST',

                dataType: 'text',

                success: function (data) {

                    if(data){

                        $('.kx-new').html(data).show();

                    }

                }

            });

        }, 10000);

    }



    $('.kx-list,.widget-kx-list,.entry-footer,.tab-wrap').on('click', '.share-icon', function () {

        const $this = $(this);

        const data = kx_share(this);

        if(data && $this.hasClass('copy')){

            if( typeof document.execCommand !== 'undefined' ){

                var text = data.title + "\r\n" + data.description + "\r\n" + decodeURIComponent(data.url);

                var t = document.createElement('textarea');

                t.value = text;

                $('body').append(t);

                t.style.position = 'fixed';

                t.style.height = 0;

                t.select();

                document.execCommand('copy');

                t.remove();

                wpcom_alert(_wpcom_js.js_lang.copy_done);

            }else{

                wpcom_alert(_wpcom_js.js_lang.copy_fail);

            }

        }

    });



    $('.navbar-search').on('keydown', '.navbar-search-input', function(){

        $(this).closest('.navbar-search').removeClass('warning');

    }).on('submit', function () {

        var $el = $(this);

        if($.trim($el.find('.navbar-search-input').val())==''){

            $el.addClass('warning');

            $el.find('.navbar-search-input').focus();

            return false;

        }

    });



    $(document).on('click', function (e) {

        const $target = $(e.target);

        if ($navbar && $target.closest('.navbar-search').length === 0 && $target.closest('.j-navbar-search').length === 0) {

            $header.find('.navbar-search').fadeOut(300, function () {

                $header.find('.primary-menu').fadeIn(300);

                $header.find('.j-navbar-search').fadeIn(300).css("display", "inline-block");

                $header.removeClass('is-search');

            });

        }

    }).on('click', '.j-navbar-search', function () {

        $header.find('.j-navbar-search').fadeOut(300);

        $header.find('.primary-menu').fadeOut(300, function () {

            $header.find('.navbar-search').removeClass('warning').fadeIn(300, function () {

                $('.navbar-search-input').focus();

            });

            $header.addClass('is-search');

        });

    }).on('click', '.navbar-search-close', function () {

        $header.find('.navbar-search').fadeOut(300, function () {

            $header.find('.primary-menu').fadeIn(300);

            $header.find('.j-navbar-search').fadeIn(300).css("display", "inline-block");

            $header.removeClass('is-search');

        });

    }).on('click', '#j-reading-back', function () {

        $('body').removeClass('reading');

        $(this).remove();

        $win.trigger('scroll');

    }).on('click', '#j-reading', function () {

        $('body').addClass('reading').append('<div class="reading-back" id="j-reading-back"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-back"></use></svg></i></div>');

    });



    $('.entry').on('click', '.btn-zan', function(){

        var $this = $(this);

        if($this.hasClass('liked')) return;

        var id = $this.data('id');

        $.ajax({

            type: 'POST',

            url: _wpcom_js.ajaxurl,

            data: {action: 'wpcom_like_it', id: id},

            dataType: 'json',

            success: function(data) {

                if (data.result==0) {

                    $this.addClass('liked').find('span').html('('+data.likes+')');

                }else if (data.result==-2) {

                    $this.addClass('liked');

                }else{



                }

            }

        });

    }).on('click', '.j-heart', function(){

        const $this = $(this);

        let id = $this.data('id');

        $.ajax({

            type: 'POST',

            url: _wpcom_js.ajaxurl,

            data: {action: 'wpcom_heart_it', id: id},

            dataType: 'json',

            success: function(data) {

                if (data.result==0) {

                    $this.addClass('stared').find('span').html(data.favorites);

                    $this.find('.wi').removeClass('wi-star').addClass('wi-star-fill');

                }else if (data.result==1) {

                    $this.removeClass('stared').find('span').html(data.favorites);

                    $this.find('.wi').removeClass('wi-star-fill').addClass('wi-star');

                }else if(data.result==-1){

                    $('#login-modal').modal();

                }

            }

        });

    });



    $('#commentform').on('submit', function(){

        var $content = $('.comment-form-comment textarea'), err = 0, focus = 0;

        var $input = $(this).find('input.required');

        if($.trim($content.val())==''){

            $content.addClass('error').focus();

            focus = 1;

            err = 1;

        }

        $input.each(function(i, el){

            var $el = $(el);

            if($.trim($el.val())==''){

                $el.addClass('error');

                if(focus==0){

                    $el.focus();

                    focus = 1;

                }

                err = 1;

            }

        });



        if(err){

            return false;

        }

    }).on('keydown', '.required', function () {

        $(this).removeClass('error');

    });



    $('#comments, #reviews').on('click', '.comment-must-login,#must-submit,.comment-reply-login', function () {

        $('#login-modal').modal();

        return false;

    });



    var $entryBar = $('.entry-bar');

    if( $entryBar.length && $win.width() > 767 ) {

        entryBar();

        $win.on('scroll', function () {

            entryBar();

        });

    }



    function entryBar(){

        var itemOffsetTop = $entryBar.offset().top;

        var itemOuterHeight = $entryBar.outerHeight();

        var winScrollHeight = $win.scrollTop();

        if(itemOffsetTop+itemOuterHeight > winScrollHeight+winHeight){

            $entryBar.addClass('fixed');

            $entryBar.find('.entry-bar-inner').css('width', $('.main').width());

        }else{

            $entryBar.removeClass('fixed');

        }

    }



    function fixedSidebar($sidebar) {

        const $parent = $sidebar.parent();

        const $main = $sidebar.closest('.container').find('.main');

        let top = $parent.offset().top;

        let soh = 0;

        let ftop = 0;

        let cheight = 0;



        setTimeout(function () {

            top = $parent.offset().top + parseInt($parent.css('paddingTop'));

            soh = $sidebar.outerHeight();

        }, 2000);



        if($main.length) {

            $('body').on('DOMSubtreeModified', 'img', handler)

                .on('DOMNodeInserted', handler)

                .on('masonry_reload', '.post-loop-masonry', function (){

                    handler();

                })

                .on('DOMNodeInserted', handler);



            function handler() {

                soh = $sidebar.outerHeight();

                cheight = $main.outerHeight();

                top = $parent.offset().top + parseInt($parent.css('paddingTop'));

                ftop = $main.offset().top + cheight;

            }



            $win.on('scroll', function () {

                if (cheight <= soh) return;

                let stop = $win.scrollTop();

                if (winHeight - top > soh) { // 边栏高度不够高的情况

                    if (stop + soh + top > ftop) {

                        $sidebar.removeClass('fixed').addClass('abs').css({bottom: 0, top: 'auto'});

                    } else {

                        $sidebar.removeClass('abs').addClass('fixed').css({bottom: 'auto', top: top});

                    }

                } else {

                    if (stop + winHeight > ftop) { // 滚动过了底部

                        $sidebar.addClass('abs').removeClass('fixed');

                    } else if (stop + winHeight > top + soh) { // 滚动过了边栏底部

                        $sidebar.addClass('fixed').removeClass('abs');

                    } else {

                        $sidebar.removeClass('fixed').removeClass('abs');

                    }

                }

            });

        }

    }



    let _u_timer = null;

    $('#wrap').on('click', '.j-newslist .tab', function () {

        let $this = $(this), $el = $this.parent(), $content = $this.closest('.main-list').find('.tab-wrap');

        $el.find('.tab').removeClass('active');

        $this.addClass('active');

        $content.removeClass('active');

        $content.eq($this.index()).addClass('active');

        let id = $this.find('a').data('id');

        if(!id || $this.data('loaded')==1 || $this.index()===0) {

            $content.eq($this.index()).find('.post-loop-masonry').trigger('masonry_reload');

            return;

        }

        $content.eq($this.index()).addClass('loading');

        let type = $el.data('type'), per_page = $el.data('per_page');

        $.ajax({

            type: 'POST',

            url: _wpcom_js.ajaxurl,

            data: {action: 'wpcom_load_posts', id: id, type:type?type:'default', per_page:per_page},

            dataType: 'html',

            success: function(data) {

                $content.eq($this.index()).removeClass('loading');

                if(data=='0'){

                    $content.eq($this.index()).find('.post-loop').html('<li class="item"><p style="text-align: center;color:#999;margin:10px 0;">'+_wpcom_js.js_lang.no_content+'</p></li>');

                }else{

                    const $data = $(data);

                    $content.eq($this.index()).find('.post-loop').html($data);

                    const $loader = $data.parent().find('.load-more-wrap');

                    if($loader.length){

                        let loader = $loader.prop('outerHTML');

                        $content.eq($this.index()).append(loader);

                        $loader.remove();

                    }

                    ajax_img_lazyload($data.find('.j-lazy'));



                    if($data.find('.swiper-container').length){

                        new Swiper($data.find('.swiper-container'), Object.assign({}, swiper_args));

                    }



                    $win.trigger('scroll');

                    if($content.eq($this.index()).find('.post-loop').hasClass('post-loop-masonry')){

                        $content.eq($this.index()).find('.post-loop').trigger('masonry_reload');

                    }

                }

                $this.data('loaded', 1);

            },

            error: function(){

                $content.eq($this.index()).find('.post-loop').html('<li class="item"><p style="text-align: center;color:#999;margin:10px 0;">'+_wpcom_js.js_lang.load_failed+'</p></li>');

                $content.eq($this.index()).removeClass('loading');

            }

        });

    }).on('click', '.j-mix-tabs .tab', function () {

        let $this = $(this), $el = $this.parent(), $content = $this.closest('.mix-tabs').find('.tab-wrap');

        $el.find('.tab').removeClass('active');

        $this.addClass('active');

        $content.removeClass('active');

        $content.eq($this.index()).addClass('active');

        if($this.data('loaded')==1 || $this.index()===0) {

            $content.eq($this.index()).find('.post-loop-masonry').trigger('masonry_reload');

            return;

        }

        $content.eq($this.index()).addClass('loading');



        let script = $el.closest('.wpcom-modules').find('script').html();

        script = JSON.parse(script);

        const tab = script[$this.index()];



        if(tab){

            $.ajax({

                type: 'POST',

                url: _wpcom_js.ajaxurl,

                data: Object.assign(tab, {action: 'wpcom_load_mix_tabs'}),

                dataType: 'html',

                success: function(data) {

                    $content.eq($this.index()).removeClass('loading');

                    const $data = $(data);

                    $content.eq($this.index()).html($data);

                    if($content.eq($this.index()).find('.post-loop-masonry').length){

                        init_macy($content.eq($this.index()).find('.post-loop-masonry')[0]);

                    }

                    ajax_img_lazyload($data.find('.j-lazy'));

                    if(tab.type==2) Share.init();

                    $win.trigger('scroll');

                    $this.data('loaded', 1);

                    if($content.eq($this.index()).find('.post-loop').hasClass('post-loop-masonry')){

                        $content.eq($this.index()).find('.post-loop').trigger('masonry_reload');

                    }

                },

                error: function(){

                    $content.eq($this.index()).html('<li class="item"><p style="text-align: center;color:#999;margin:10px 0;">'+_wpcom_js.js_lang.load_failed+'</p></li>');

                    $content.eq($this.index()).removeClass('loading');

                }

            });

        }

    }).on('mouseenter', '.j-newslist > li, .j-mix-tabs > li', function (){

        clearTimeout(_u_timer);

        const $el = $(this), $wrap = $el.closest('ul'), $_ = $wrap.find('.tab-underscore');

        const padding = $wrap.find('>li').first().position().left;

        let left = $el.position().left - padding;

        $_.css({'transform': 'translateX('+left+'px)', 'width': $el.width()});

    }).on('mouseleave', '.j-newslist > li, .j-mix-tabs > li', function (){

        const _this = this;

        clearTimeout(_u_timer);

        _u_timer = setTimeout(function (){

            const $wrap = $(_this).closest('ul');

            const $el = $wrap.find('.active'), $_ = $wrap.find('.tab-underscore');

            const padding = $wrap.find('>li').first().position().left;

            let left = $el.position().left - padding;

            $_.css({'transform': 'translateX('+left+'px)', 'width': $el.width()});

        }, 300);

    }).on('click', '.j-load-more, .j-load-kx', function(){
        console.log("cc");

        const $this = $(this);

        if($this.hasClass('disabled') || $this.hasClass('loading')) return;



        let data = null;

        let page = $this.data('page');

        page = page!==undefined ? page + 1 : 2;



        if( $this.hasClass('j-load-kx') ){

            data = {action: 'wpcom_load_kuaixun', page:page};

        }else{

            let id = $this.data('id');

            let exclude = $this.data('exclude');

            const $el = $this.closest('.main-list').find('.j-newslist');

            let type = $el.data('type'), per_page = $el.data('per_page');

            type = type ? type : $this.closest('.main-list').data('type');

            data = {action: 'wpcom_load_posts', id: id, page:page, type:type?type:'default', per_page:per_page, exclude:exclude};

        }



        $this.loading(1);



        $.ajax({

            type: 'POST',

            url: _wpcom_js.ajaxurl,

            data: data,

            dataType: 'html',

            success: function(data, t, r) {

                if(data=='0'){

                    $this.addClass('disabled').text(_wpcom_js.js_lang.page_loaded);

                    if($this.hasClass('j-user-followers')){

                        const $wrap = $this.closest('.profile-tab-content');

                        if($wrap.find('.follow-items-loading').length){

                            $wrap.find('.follow-items-loading').remove();

                            $wrap.find('.profile-no-content').show();

                        }

                    }

                }else{

                    const $data = $(data);

                    if( $this.hasClass('j-load-more') ){

                        $this.closest('.tab-wrap').find('.post-loop').append($data);

                        if ($this.closest('.tab-wrap').find('.post-loop').hasClass('post-loop-masonry')) {

                            $this.closest('.tab-wrap').find('.post-loop').trigger('masonry_reload');

                        }

                    }else if( $this.hasClass('j-load-kx') ){

                        if($($data[0]).text() == $('.kx-list .kx-date:last').text()){

                            $data.first().hide();

                        }

                        $this.parent().before($data);

                        $this.parent().parent().find('.kx-date:hidden').remove();

                        window.kxDate = $('.kx-list .kx-date');

                        Share.init();

                    }

                    ajax_img_lazyload($data.find('.j-lazy'));

                    if($data.find('.swiper-container').length){

                        new Swiper($data.find('.swiper-container'), Object.assign({}, swiper_args));

                    }

                    $this.data('page', page);

                    $win.trigger('scroll');

                }

                $this.loading(0);

            },

            error: function(){

                $this.loading(0);

            }

        });

        return false;

    }).on('click', '.j-mix-tabs-more', function(){

        const $this = $(this), $tab = $this.closest('.tab-wrap'), $wrap = $this.closest('.wpcom-modules');

        if($this.hasClass('disabled') || $this.hasClass('loading')) return;

        let script = $wrap.find('script').html();

        script = JSON.parse(script);

        let page = $this.data('page');

        page = page ? page : 2;

        let index = $tab.data('index');

        if(script && script[index]){

            $this.loading(1);

            const tab = script[index];

            tab.page = page;

            $.ajax({

                type: 'POST',

                url: _wpcom_js.ajaxurl,

                data: Object.assign(tab, {action: 'wpcom_load_mix_tabs'}),

                dataType: 'html',

                success: function(data) {

                    const $data = $($(data).html());

                    if($data && $data.length){

                        if(tab.type==0){ // post

                            $tab.find('.post-loop').append($data);

                            if($tab.find('.post-loop').hasClass('post-loop-masonry')){

                                $tab.find('.post-loop').trigger('masonry_reload');

                            }

                        }else if(tab.type==1){ // special

                            $tab.find('.topic-list').append($data);

                        }else if(tab.type==3){ // qapress

                            $tab.find('.q-content').append($data);

                        }else{ // kuaixun

                            $tab.find('.kx-list').append($data);

                            Share.init();

                        }

                        ajax_img_lazyload($data.find('.j-lazy'));

                        $win.trigger('scroll');

                        $this.data('page', page+1);

                    }else{

                        $this.addClass('disabled');

                        $this.text(_wpcom_js.js_lang.page_loaded);

                    }



                    $this.loading(0);

                },

                error: function(){

                    $this.loading(0);

                }

            });

        }

    }).on('click', '.j-load-archive', function (){

        const $this = $(this);

        if($this.hasClass('disabled') || $this.hasClass('loading')) return;



        let page = $this.data('page');

        page = page!==undefined ? page + 1 : 2;



        $this.loading(1);



        const $dom = $this.closest('.sec-panel-body').find(' > .post-loop');

        let _class = $dom.attr('class');

        let _match = _class.match(/post-loop-([a-z0-9_-]+)/i);



        let attr = getQueryString('attr'), order = getQueryString('order');



        load_archive({

            $dom: $dom,

            data: {

                action: 'wpcom_load_posts',

                page: page,

                taxonomy: $this.data('tax'),

                id: $this.data('id'),

                type: _match && _match[1] ? _match[1] : 'default',

                attr: attr ? attr : '',

                order: order ? order : ''

            },

            callback: function (){

                $this.data('page', page);

                $this.loading(0);

            },

            error: function (){

                $this.loading(0);

            }

        });

        return false;

    });



    $('.special-wrap').on('click', '.load-more', function(){

        var $this = $(this);

        if($this.hasClass('disabled') || $this.hasClass('loading')) return;

        var page = $this.data('page');

        page = page ? page + 1 : 2;

        $this.loading(1);

        $.ajax({

            type: 'POST',

            url: _wpcom_js.ajaxurl,

            data: {action: 'wpcom_load_special', page:page},

            dataType: 'html',

            success: function(data) {

                if(data=='0'){

                    $this.addClass('disabled').text(_wpcom_js.js_lang.page_loaded);

                }else{

                    var $data = $(data);

                    $this.closest('.special-wrap').find('.special-list').append($data);

                    ajax_img_lazyload($data.find('.j-lazy'));

                    $this.data('page', page);

                    $win.trigger('scroll');

                    Share.init();

                }

                $this.loading(0);

            },

            error: function(){

                $this.loading(0);

            }

        });

    });



    const $loader = $('.load-more-wrap > .scroll-loader');

    if($loader.length){

        let $loadWrap = $loader.parent();

        $win.on('scroll', function (){

            if($win.scrollTop() + winHeight > $loadWrap.offset().top - 50){

                if($loader.hasClass('disabled') || $loader.hasClass('loading')) return;



                let page = $loader.data('page');

                page = page!==undefined ? page + 1 : 2;



                $loader.addClass('loading');

                const $dom = $loader.closest('.sec-panel-body').find(' > .post-loop');

                let _class = $dom.attr('class');

                let _match = _class.match(/post-loop-([a-z0-9_-]+)/i);

                let attr = getQueryString('attr'), order = getQueryString('order');



                load_archive({

                    $dom: $dom,

                    data: {

                        action: 'wpcom_load_posts',

                        page: page,

                        taxonomy: $loader.data('tax'),

                        id: $loader.data('id'),

                        type: _match && _match[1] ? _match[1] : 'default',

                        attr: attr ? attr : '',

                        order: order ? order : ''

                    },

                    callback: function (res){

                        $loader.data('page', page);

                        if(res == '0'){

                            $loader.removeClass('loading').addClass('disabled').text(_wpcom_js.js_lang.page_loaded)

                        }else{

                            $loader.removeClass('loading');

                        }

                    },

                    error: function (){

                        $loader.removeClass('loading').addClass('disabled');

                    }

                });

            }

        });

    }



    function ajax_img_lazyload($imgs) {

        if ($imgs.length) {

            if ($imgs.eq(0).is('img')) { // 图片元素

                $imgs.eq(0).one('load', function () {

                    $imgs.lazyload({

                        webp: webp

                    });

                });

            } else { // 背景图片

                const img = document.createElement("img")

                let _src = window.getComputedStyle($imgs[0]).getPropertyValue('background-image');

                if (_src) {

                    img.src = _src.slice(4, -1).replace(/['"]/g, '');

                    img.onload = () => {

                        $imgs.lazyload({

                            webp: webp

                        });

                    }

                } else {

                    setTimeout(function () {

                        img.onload = () => {

                            $imgs.lazyload({

                                webp: webp

                            });

                        }

                    }, 300);

                }

            }

        }

    }



    function load_archive(args){

        $.ajax({

            type: 'POST',

            url: _wpcom_js.ajaxurl,

            data: args.data,

            dataType: 'html',

            success: function (data) {

                if(data=='0'){

                    args.$dom.parent().find('.load-more').addClass('disabled').text(_wpcom_js.js_lang.page_loaded);

                }else{

                    const $data = $(data);

                    args.$dom.append($data);

                    ajax_img_lazyload($data.find('.j-lazy'));

                    if($data.find('.swiper-container').length){

                        new Swiper($data.find('.swiper-container'), Object.assign({}, swiper_args));

                    }

                    $win.trigger('scroll');

                    args.$dom.trigger('masonry_reload');

                }

                if(args.callback) args.callback(data);

            },

            error: function () {

                if(args.error) args.error();

            }

        });

    }



    function init_macy(el){

        const $el = $(el);

        let _class = $el.attr('class');

        let _match = _class.match(/cols-([\d]+)/i);

        let macy = Macy({

            container: el,

            columns: _match && _match[1] ? _match[1] : 3,

            breakAt: {

                1024: 3,

                767: 2

            }

        });

        let _t = null;

        $el.on('lazy_loaded', 'img', function (){

            if(_t) clearTimeout(_t);

            _t = setTimeout(function (){

                macy.recalculate(true);

            }, 60);

        }).on('masonry_reload', function (){

            macy.recalculate(true);

        });



        macy.on(macy.constants.EVENT_RECALCULATED, function () {

            setTimeout(function (){

                $win.trigger('scroll');

            }, 100);

        });

    }



    function getQueryString(name) {

        let reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");

        let r = window.location.search.substr(1).match(reg);

        if (r != null) {

            return decodeURIComponent(r[2]);

        };

        return null;

    }

});