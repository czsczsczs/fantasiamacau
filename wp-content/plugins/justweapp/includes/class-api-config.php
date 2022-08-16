<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Config_Controller extends WP_REST_Controller{

    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'config';
    }

    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_data'),
                'args'                => array(),
                'permission_callback' => array( $this, 'permission_check' )
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_data($request) {
        $options = WWA_options();
        // $platform = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : '');
        // if(!$platform && preg_match('/baiduboxapp/i', $_SERVER['HTTP_USER_AGENT'])) $platform = 'swan';

        $res = array('max_upload_size' => wp_max_upload_size());
        $res['slides'] = array();
        $res['slide_type'] = isset($options['slider_type']) ? $options['slider_type'] : 0;
        $res['slide_interval'] = isset($options['slider_interval']) && $options['slider_interval'] ? (int)$options['slider_interval'] : 4000;
        $res['slide_duration'] = isset($options['slider_duration']) && $options['slider_duration'] ? (int)$options['slider_duration'] : 500;
        $slider_num = isset($options['slider_num']) && is_numeric($options['slider_num']) ? $options['slider_num'] : 5;

        if($slider_num){
            // 幻灯片
            $posts = get_posts('posts_per_page='.$slider_num.'&meta_key=_wwa_slide&meta_value=1&post_type=post');
            if($posts){ global $post;foreach ( $posts as $post ) { setup_postdata( $post );
                $res['slides'][] = array(
                    'id' => $post->ID,
                    'img' => WWA_thumbnail_url( $post->ID ),
                    'title' => get_the_title()
                );
            } wp_reset_postdata(); }
        }

        // 首页标题
        $res['app_title'] = isset($options['app_title']) && $options['app_title'] ? $options['app_title'] : get_option('blogname');

        $res['static_path'] = WWA_URI;


        // 首页tab栏目
        $res['cats'] = array();
        $cats = isset($options['cats_id']) && $options['cats_id'] ? $options['cats_id'] : array();
        if($cats){
            foreach($cats as $cat){
                $res['cats'][] = array(
                    'id' => $cat,
                    'name' => get_cat_name($cat)
                );
            }
        }

        // 金刚区
        $res['nav_cols'] = isset($options['nav_cols']) && $options['nav_cols'] ? $options['nav_cols'] : 5;
        $navs = array();
        if(isset($options['nav_title']) && $options['nav_title']){
            foreach ($options['nav_title'] as $i => $title) {
                $navs[] = array(
                    'title' => $title,
                    'path' => isset($options['nav_path']) && $options['nav_path'][$i] ? $options['nav_path'][$i] : '',
                    'icon' => isset($options['nav_icon']) && $options['nav_icon'][$i] ? wp_get_attachment_url($options['nav_icon'][$i]) : '',
                    'platform' => isset($options['nav_pf']) && $options['nav_pf'][$i] ? $options['nav_pf'][$i] : array('all')
                );
            }
        }
        $res['navs'] = $navs;

        // 搜索
        $res['search_placeholder'] = isset($options['search_text']) ? $options['search_text'] : '';
        $res['search_kws'] = isset($options['search_kw']) ? $options['search_kw'] : '';

        $res['related_title'] = isset($options['related_title']) ? $options['related_title'] : '猜你喜欢';

        // 专题
        $res['zt_title'] = isset($options['zt_title']) ? $options['zt_title'] : '专题';
        $res['zt_desc'] = isset($options['zt_desc']) ? $options['zt_desc'] : '';

        // 快讯
        $res['kx_title'] = isset($options['kx_title']) ? $options['kx_title'] : '快讯';
        $res['kx_desc'] = isset($options['kx_desc']) ? $options['kx_desc'] : '';

        // 颜色
        $res['color'] = isset($options['color']) && $options['color'] ? $options['color'] : '#3ca5f6';

        // 页头风格
        $res['head_style'] = isset($options['head_style']) ? $options['head_style'] : 1;
        $res['head_style'] = $res['head_style'] ? 1 : 0;

        // 文章页
        $res['single_indent'] = isset($options['single_indent']) ? $options['single_indent'] : 0;
        $res['single_justify'] = isset($options['single_justify']) ? $options['single_justify'] : 0;
        $res['single_banner'] = isset($options['single_banner']) && $options['single_banner']=='0' ? false : true;

        // 开启评论
        if(isset($options['comment']) && is_array($options['comment'])){
            $res['comment'] = $options['comment'];
        }else if(!isset($options['comment'])){
            $res['comment'] = true;
        }else{
            $res['comment'] = false;
        }
        $res['comment_order'] = get_option('comment_order');

        // 文章关注
        $res['post_like'] = isset($options['post_like']) && $options['post_like']=='0' ? false : true;

        // 返回首页
        $res['back_home'] = isset($options['back_home']) && $options['back_home']=='1' ? true : false;
        $res['back_top'] = isset($options['back_top']) && $options['back_top']=='1' ? true : false;

        // 用户关注
        $res['follow_enable'] = isset($options['follow_enable']) && $options['follow_enable'] == '0' ? false : true;
        // 文章作者
        $res['post_author'] = !!(isset($options['post_author']) ? $options['post_author'] : 1) && $res['follow_enable'];
        // 文章摘要
        $res['post_excerpt'] = isset($options['post_excerpt']) && $options['post_excerpt'] ? true : false;

        // 意见反馈
        $res['contact'] = isset($options['contact']) ? $options['contact'] : 1;

        $res['loading_img'] = isset($options['loading_img']) && $options['loading_img'] ? wp_get_attachment_image_url($options['loading_img'], 'full') : '';


        // 列表风格
        $res['list_tpl'] = isset($options['list_tpl']) && $options['list_tpl'] ? $options['list_tpl'] : '0';
        $res['img_align'] = isset($options['img_align']) && $options['img_align'] ? $options['img_align'] : '0';
        $res['img_style'] = isset($options['img_style']) && $options['img_style'] ? $options['img_style'] : '0';

        // 百度索引页分页
        $res['sitemap_perpage'] = isset($options['swan-sitemap-perpage']) && $options['swan-sitemap-perpage'] ? $options['swan-sitemap-perpage'] : 100;

        // QQ 隐私页
        // if(isset($options['qq-privacy']) && $options['qq-privacy']){
        //     $res['qq_privacy'] = $options['qq-privacy'];
        // }

        // QAPress
        if(defined('QAPress_VERSION')){
            global $qa_options;
            $res['qa_cats'] = array();
            $qa_cats = isset($qa_options['category']) && $qa_options['category'] ? $qa_options['category'] : array();
            if($qa_cats && $qa_cats[0]){
                foreach ($qa_cats as $cid) {
                    $c = get_term(trim($cid), 'qa_cat');
                    if($c){
                        $res['qa_cats'][] = array(
                            'id' => $c->term_id,
                            'name' => $c->name
                        );
                    }
                }
            }
            $res['qa_answer_order'] = isset($qa_options['answers_order']) && $qa_options['answers_order'] ? 'desc' : 'asc';
        }
        $res['qa_enable'] = isset($options['qa_enable']) && $options['qa_enable']=='0' ? false : true; // 默认开启
        if(!isset($res['qa_cats']) && $res['qa_enable']) $res['qa_enable'] = false; // 开启了也需要判断是否有安装问答插件才行

        // 复制链接
        $res['copy_link'] = isset($options['copy_link']) && $options['copy_link']=='0' ? false : true; // 默认开启

        // 选项卡
        $tabbar = array();
        if($options['url_type']){
            foreach ($options['url_type'] as $i => $type) {
                $type = $type ? $type : '0';
                $url = isset($options['url']) && isset($options['url'][$i]) && $options['url'][$i] ? $options['url'][$i] : '';
                $id = '';
                $origin = '';
                $title = '';
                $item = array();
                switch ($type) {
                    case '1':
                        $page = get_post($options['url_page'][$i]);
                        $url = 'mpage';
                        $id = $options['url_page'][$i];
                        $title = $options['title'][$i] ? $options['title'][$i] : $page->post_title;
                        break;
                    case '2':
                        $term = get_term($options['url_cat'][$i], 'category');
                        $url = 'mterm';
                        $id = $options['url_cat'][$i];
                        $title = $options['title'][$i] ? $options['title'][$i] : (isset($term->name) ? $term->name : '');
                        break;
                    case '3':
                        $term = get_term_by('name', $options['url_tag'][$i], 'post_tag');
                        $url = 'mterm';
                        $id = $term->term_id;
                        $title = $options['title'][$i] ? $options['title'][$i] : $term->name;
                        break;
                    case '4':
                        $id = 0;
                        $title = $options['title'][$i] ? $options['title'][$i] : ' ';
                        break;
                    case '5':
                        $id = $options['to_appid'][$i] ? $options['to_appid'][$i] : '';
                        $title = $options['title'][$i] ? $options['title'][$i] : ' ';
                        break;
                    case '0':
                    default:
                        if($url=='kuaixun') $title = '快讯';
                        if($url=='specials') $title = '专题';
                        if($url=='qapress') $title = '问答';
                        if($options['title'][$i]) $title = $options['title'][$i];
                        break;
                }
                if($url=='kuaixun' || $url=='specials' || $url=='qapress') {
                    $id = $url;
                    $origin = 'others';
                }
                $item = array(
                    'url' => $url,
                    'text' => $title,
                    'id' => $id,
                    'type' => $type,
                    'platform' => isset($options['url_platform']) && $options['url_platform'][$i] ? $options['url_platform'][$i] : array('all'),
                    'origin' => $origin,
                    'iconPath' => isset($options['icon']) && $options['icon'][$i] ? $options['icon'][$i] : '',
                    'selectedIconPath' => isset($options['icon_active']) && $options['icon_active'][$i] ? $options['icon_active'][$i] : ''
                );
                if($url === 'profile'){
                    $item['text'] = '我的';
                    $item['iconName'] = 'profile';
                    $item['selectedIconName'] = 'profilehover';
                }else if($origin === 'others'){
                    if ($url === 'kuaixun') {
                        $item['iconName'] = 'kuaixun';
                        $item['selectedIconName'] = 'kuaixunhover';
                    }else if ($url === 'specials') {
                        $item['iconName'] = 'specials';
                        $item['selectedIconName'] = 'specialshover';
                    }else if ($url === 'qapress') {
                        $item['iconName'] = 'question';
                        $item['selectedIconName'] = 'questionhover';
                    }
                }
                $tabbar[] = $item;
            }
        }
        $res['tabbar'] = $tabbar;

        $ad = array();
        if(isset($options['ad_type']) && $options['ad_type']){
            foreach ($options['ad_type'] as $x => $ad_type) {
                if(!isset($ad[$ad_type])) $ad[$ad_type] = array();
                if(isset($options['ad_id'][$x])) $ad[$ad_type][$options['ad_id'][$x]] = $options['ad_code'][$x];
            }
        }
        $res['ad'] = $ad;

        $res['seo'] = WWA_seo('home');

        // 登录页
        $res['login_logo'] = isset($options['login_logo']) && $options['login_logo'] ? wp_get_attachment_image_url( $options['login_logo'], 'full' ) : '';
        $res['phone_login'] = isset($options['phone_login']) && $options['phone_login'] ? $options['phone_permission'] : array();
        $res['account_login'] = isset($options['account_login']) ? $options['account_login'] : 1;
        $res['miniapp_login'] = isset($options['miniapp_login']) ? $options['miniapp_login'] : 1;

        $res = rest_ensure_response($res);
        if(!is_wp_error($res)) $res->set_headers(array('Cache-Control' => 'max-age=86400'));
        return $res;
    }
    function permission_check(){
        return true;
    }
}

class WWA_REST_Config2_Controller extends WWA_REST_Config_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'config';
    }
}