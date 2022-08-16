<?php
defined( 'ABSPATH' ) || exit;

add_action('wp_enqueue_scripts', 'WWA_register_scripts');
function WWA_register_scripts(){
    if(is_single()){
        $options = WWA_options();
        wp_enqueue_style('wwa', WWA_URI . 'css/style.css', false, WWA_VERSION);
        if(!wp_script_is('jquery')) wp_register_script('jquery',includes_url('js/jquery/jquery.min.js'), array(), WWA_VERSION);
        wp_enqueue_script('wwa', WWA_URI . 'js/script.js', array('jquery'), WWA_VERSION, true);
        $rewarded = false;
        if(isset($options['rewarded-wx']) && $options['rewarded-wx']=='1' && $options['appid'] && $options['secret']){
            $rewarded = true;
        }
        $wwa_js = array(
            'ajaxurl' => admin_url( 'admin-ajax.php'),
            'post_id' => get_queried_object_id(),
            'rewarded' => $rewarded ? 'wx' : ''
        );
        wp_localize_script( 'wwa', '_wwa_js', $wwa_js );
    }
}

add_action( 'admin_menu', 'WWA_enqueue_admin_style');
function WWA_enqueue_admin_style (){
    wp_enqueue_style( "wwa-admin", WWA_URI . "css/admin.css", false, WWA_VERSION, "all");
}

add_action( 'admin_menu', 'WWA_enqueue_admin_js');
function WWA_enqueue_admin_js (){
    global $pagenow;
    if ( $pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == $GLOBALS['WWA']->plugin_slug ) {
        wp_enqueue_script("wwa-admin", WWA_URI . "js/admin.js", array('jquery'), WWA_VERSION, true);
    }
}

add_action( 'wp_ajax_WWA_commit', 'WWA_commit' );
function WWA_commit(){
    global $WWA;
    $options = WWA_options();
    $api_url = 'http://www.wpcom.cn/weixin-open/commit/'.$WWA->info['plugin_id'];
    $tabbar = array();
    if($options['url']){
        foreach ($options['url'] as $i => $url) {
            $tabbar[] = array(
                'url' => $url,
                'title' => $options['title'][$i]
            );
        }
    }
    $body = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token'),
        'admin_email' => get_option('admin_email')
    );
    $result = wp_remote_request($api_url, array('method' => 'POST', 'timeout' => 30, 'body' => $body));
    if(is_array($result)){
        echo $result['body'];
    }else{
        print_r($result);
    }
    exit;
}

add_action( 'wp_ajax_WWA_submit_audit', 'WWA_submit_audit' );
function WWA_submit_audit(){
    global $WWA;
    $api_url = 'http://www.wpcom.cn/weixin-open/submitaudit/'.$WWA->info['plugin_id'];
    $body = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token')
    );
    $result = wp_remote_request($api_url, array('method' => 'POST', 'timeout' => 30, 'body' => $body));
    if(is_array($result)){
        echo $result['body'];
    }else{
        print_r($result);
    }
    exit;
}

add_action( 'wp_ajax_WWA_release', 'WWA_release' );
function WWA_release(){
    global $WWA;
    $api_url = 'http://www.wpcom.cn/weixin-open/release/'.$WWA->info['plugin_id'];
    $body = array(
        'home' => get_option('siteurl'),
        'email' => get_option($WWA->plugin_slug . '_email'),
        'token' => get_option($WWA->plugin_slug . '_token')
    );
    $result = wp_remote_request($api_url, array('method' => 'POST', 'timeout' => 30, 'body' => $body));
    if(is_array($result)){
        echo $result['body'];
    }else{
        print_r($result);
    }
    exit;
}

add_action( 'wp_ajax_WWA_swan_sitemap', 'WWA_swan_sitemap' );
function WWA_swan_sitemap(){
    if(current_user_can('edit_theme_options')){
        header("Content-Disposition: attachment; filename=" . urlencode('sitemap.txt'));
        header("Content-Type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");
        $categories = get_terms(array('taxonomy' => 'category'));
        if( $categories && !is_wp_error($categories) ) {
            foreach ($categories as $cat) {
                echo 'pages/term/index?id=' . $cat->term_id . "\r\n";
            }
        }
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 50000,
            'post_mime_type' => 'empty'
        );
        $options = WWA_options();
        $hide = isset($options['cats_hide']) && $options['cats_hide'] ? $options['cats_hide'] : array();
        if($hide) $args['category__not_in'] = $hide;

        $posts_query  = new WP_Query();
        $posts = $posts_query->query( $args );
        if( $posts && !is_wp_error($posts) ) {
            foreach ($posts as $p) {
                echo 'pages/single/index?id=' . $p->ID . "\r\n";
            }
        }
        exit;
    }
}

add_action( 'wp_ajax_WWA_swan_clear_cache', 'WWA_ajax_swan_clear_cache' );
function WWA_ajax_swan_clear_cache(){
    if(current_user_can('edit_theme_options')){
        if($req = WWA_swan_clear_cache()){
            echo $req;
        }else{
            echo 'ACCESS_TOKEN Error';
        }
    }
    exit;
}

add_action( 'justweapp_options_updated', 'WWA_swan_clear_cache' );
function WWA_swan_clear_cache(){
    $options = WWA_options();
    $appkey = isset($options['swan-key']) ? $options['swan-key'] : '';
    if($appkey && $access_token = WWA_swan_get_access_token()){
        $appkey = isset($options['swan-key']) ? $options['swan-key'] : '';
        $str = WWA_http_request('https://openapi.baidu.com/rest/2.0/smartapp/storage/component/reset?appkey='.$appkey.'&access_token='.$access_token, array(), 'POST');
        return json_encode($str);
    }
}

add_action( 'wp_ajax_WWA_get_package', 'WWA_get_package' );
function WWA_get_package(){
    if(current_user_can('edit_theme_options') && isset($_GET['file']) && $_GET['file']){
        global $WWA;
        $auth_query = array(
            'home' => get_option('siteurl'),
            'email' => get_option($WWA->plugin_slug . '_email'),
            'token' => get_option($WWA->plugin_slug . '_token'),
            'file' => $_GET['file']
        );
        $server = 'https://www.wpcom.cn/weixin-open';
        $url = $server . '/down/'. $WWA->info['plugin_id'] . '?' . http_build_query($auth_query);
        wp_redirect($url);
        exit;
    }
}


add_action( 'plugins_loaded', 'WWA_wptexturize', 1 );
function WWA_wptexturize(){
    if(WWA_is_rest()) add_filter( 'run_wptexturize', '__return_false', 20 );
}

if(!function_exists('wpcom_mime_types')){
    add_filter( 'upload_mimes', 'WWA_mime_types' );
    function WWA_mime_types( $mimes = array() ){
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    add_filter( 'wp_check_filetype_and_ext', 'WWA_svgs_upload_check', 10, 4 );
    function WWA_svgs_upload_check( $checked, $file, $filename, $mimes ) {
        if ( ! $checked['type'] ) {
            $check_filetype     = wp_check_filetype( $filename, $mimes );
            $ext                = $check_filetype['ext'];
            $type               = $check_filetype['type'];
            $proper_filename    = $filename;

            if ( $type && 0 === strpos( $type, 'image/' ) && $ext !== 'svg' ) {
                $ext = $type = false;
            }

            $checked = compact( 'ext','type','proper_filename' );
        }
        return $checked;
    }
}

add_action('rest_api_init', 'WWA_rest_api' );
function WWA_rest_api(){
    global $WWA;
    $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : '');
    if(!$type) return false;
    if($WWA->is_active()){
        register_rest_field( 'post',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_post_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'page',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_page_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'kuaixun',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_kuaixun_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'special',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_special_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'qa_post',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_qapost_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'comment',
            'reply_to',
            array(
                'get_callback'    => 'WWA_rest_comment_reply_to',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'comment',
            'reply_post',
            array(
                'get_callback'    => 'WWA_rest_comment_reply_post',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'qacomment',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_qacomments_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'category',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_cat_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );

        register_rest_field( 'user',
            'wpcom_metas',
            array(
                'get_callback'    => 'WWA_rest_user_metas',
                'update_callback' => null,
                'schema'          => null,
            )
        );
    }
}

function WWA_rest_post_metas($object, $field_name, $request){
    global $options, $_wwa_follow; // 兼容主题
    $wwa_options = WWA_options();
    $post_id = isset($object['id']) && $object['id'] ? $object['id'] : 0;
    $metas = array();
    // 缩略图
    $img_url = WWA_thumbnail_url($post_id, 'full');
    if($img_url) {
        $metas['cover'] = $img_url;
        $metas['thumb'] = WWA_thumbnail_url($post_id, 'post-thumbnail');
    }

    $post = get_post($post_id);
    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', apply_filters( 'the_content', $post->post_content ), $matches);
    $multimage = get_post_meta($post_id, 'wpcom_item_style', true);
    $multimage = $multimage=='' ? (isset($wwa_options['list_multimage']) ? $wwa_options['list_multimage'] : '') : $multimage;

    // 小程序未指定列表风格，则获取主题的设置
    if($multimage=='' && function_exists('wpcom_setup')){
        $multimage = get_post_meta($post_id, 'wpcom_multimage', true);
        $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;
    }

    $metas['item_style'] = $multimage;
    if(isset($matches[1]) && isset($matches[1][3]) && ($multimage==1||$multimage==3)) {
        $metas['thumbs'] = array_slice($matches[1], 0, 5);
    }

    if( function_exists('the_views') ) {
        $views = get_post_meta($post_id, 'views', true);
        $views = $views ? $views : 1;
        if($post_id && isset($request['id']) && $request['id'] == $post_id) {
            $views = $views+1;
            update_post_meta($post_id, 'views', $views);
        }
        $metas['views'] = $views;
    }

    $metas['comments'] = get_comments_number($post_id);

    $video = get_post_meta($post_id, 'wpcom_video', true);
    if($video){
        $metas['video'] = $video;
    }

    $webview = get_post_meta($post_id, 'wpcom_webview', true);
    if($webview){
        $metas['webview'] = $webview;
    }


    // 文章详情页
    if($post_id && isset($request['id']) && $request['id'] == $post_id) {
        $post_author = !!(isset($wwa_options['post_author']) ? $wwa_options['post_author'] : 1);
        $user = wp_get_current_user();
        if(!$post_author && $object['categories']){
            $cats = array();
            foreach ($object['categories'] as $cat) {
                $cats[] = array(
                    'id' => $cat,
                    'name' => get_cat_name($cat)
                );
            }
            $metas['cats'] = $cats;
        }
        if($post_author){
            $metas['author_name'] = get_the_author_meta('display_name', $object['author']);
            $metas['author_avatar'] = get_avatar_url( $object['author'] );

            // 用户关注
            $is_follow = $_wwa_follow->is_followed($object['author']);
            $is_follow = $is_follow ? 1 : 0;
            if($user->ID && $is_follow===1 && $_wwa_follow->is_followed($user->ID, $object['author'])){ // 互相关注
                $is_follow = 2;
            }
            $metas['is_follow'] = $is_follow;
        }

        $metas['seo'] = WWA_seo('single', $post_id);

        if($post->post_excerpt){
            $metas['excerpt'] = apply_filters( 'the_excerpt', get_the_excerpt() );
        }

        //  判断是否为小程序不显示的文章
        $cats_hide = isset($wwa_options['cats_hide']) && $wwa_options['cats_hide'] ? $wwa_options['cats_hide'] : array();
        if(!empty($cats_hide)) $hide = has_category($cats_hide, $post_id);
        $metas['hide'] = isset($hide) && $hide ? true : false;
        if(!$metas['hide']){ // 分类排除通过，再检查文章是否单独排除
            // @todo: 兼容旧版本数据过渡
            $hide = get_post_meta($post_id, '_wwa_hide', true);
            $post_mime_type = '';
            if(isset($hide) && $hide=='1'){
                $post_mime_type = 'wwa/hide';
            }
            if($post->post_mime_type !== $post_mime_type){
                $data = array(
                    'ID' => $post_id,
                    'post_mime_type' => $post_mime_type
                );
                wp_update_post( $data );
            }
            $metas['hide'] = $post_mime_type !== '';
        }
    }else{
        if($object['categories']){
            $cats = array();
            foreach ($object['categories'] as $cat) {
                $cats[] = array(
                    'id' => $cat,
                    'name' => get_cat_name($cat)
                );
            }
            $metas['cats'] = $cats;
        }
    }

    return $metas;
}

function WWA_rest_user_metas($object, $field_name, $request){
    global $options, $wpdb, $_wwa_follow;
    $metas = array();
    $user = get_user_by('ID', $object['id']);

    // 文章数量
    $posts = apply_filters('wpcom_posts_count', $user->{$wpdb->get_blog_prefix() . 'posts_count'}, $user->ID);
    if ($posts >= 1000) $posts = sprintf("%.1f", $posts / 1000) . 'K';
    $metas['posts'] = $posts;

    // 评论数量
    $comments = apply_filters('wpcom_comments_count', $user->{$wpdb->get_blog_prefix() . 'comments_count'}, $user->ID);
    if ($comments >= 1000) $comments = sprintf("%.1f", $comments / 1000) . 'K';
    $metas['comments'] = $comments;

    // 问答信息
    if(defined('QAPress_VERSION')){
        $questions = apply_filters('wpcom_questions_count', $user->{$wpdb->get_blog_prefix() . 'questions_count'}, $user->ID);
        if ($questions >= 1000) $questions = sprintf("%.1f", $questions / 1000) . 'K';
        $metas['questions'] = $questions;

        $answers = apply_filters('wpcom_answers_count', $user->{$wpdb->get_blog_prefix() . 'answers_count'}, $user->ID);
        if ($answers >= 1000) $answers = sprintf("%.1f", $answers / 1000) . 'K';
        $metas['answers'] = $answers;
    }

    // 粉丝数量
    $followers = apply_filters('wpcom_followers_count', $user->{$wpdb->get_blog_prefix() . 'followers_count'}, $user->ID);
    if ($followers >= 1000) $followers = sprintf("%.1f", $followers / 1000) . 'K';
    $metas['followers'] = $followers;

    // 关注状态
    $is_follow = 0;
    $cid = get_current_user_id();
    if(!empty( $cid ) && $cid != $user->ID){
        $is_follow = $_wwa_follow->is_followed($user->ID, $cid);
        $is_follow = $is_follow ? 1 : 0;
        if($user->ID && $is_follow===1 && $_wwa_follow->is_followed($cid, $user->ID)){ // 互相关注
            $is_follow = 2;
        }
    }
    $metas['is_follow'] = $is_follow;

    if ( !empty( $cid ) && $cid === $user->ID) {
        // 关注数量
        $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
        $table = _get_meta_table('user');
        $follows = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM $table WHERE meta_key = %s AND user_id = %d", $option_name, $cid));
        if(!$follows || is_wp_error($follows)) $follows = 0;
        if ($follows >= 1000) $follows = sprintf("%.1f", $follows / 1000) . 'K';
        $metas['follows'] = $follows;

        // 收藏文章数
        $favorites = get_user_meta($cid, 'wpcom_favorites', true);
        $favorites = $favorites && is_array($favorites) ? count($favorites) : 0;
        if ($favorites >= 1000) $favorites = sprintf("%.1f", $favorites / 1000) . 'K';
        $metas['favorites'] = $favorites;
    }
    return $metas;
}

function WWA_rest_qapost_metas($object, $field_name, $request){
    $wwa_options = WWA_options();
    $post_id = isset($object['id']) && $object['id'] ? $object['id'] : 0;
    $metas = array();
    $post = get_post($post_id);
    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', apply_filters( 'the_content', $post->post_content ), $matches);

    if(isset($matches[1]) && isset($matches[1][0])) {
        $metas['thumbs'] = array_slice($matches[1], 0, 3);
    }

    if( function_exists('the_views') ) {
        $views = get_post_meta($post_id, 'views', true);
        $views = $views ? $views : 1;
        if($post_id && isset($request['id']) && $request['id'] == $post_id) {
            $views = $views+1;
            update_post_meta($post_id, 'views', $views);
        }
        $metas['views'] = $views;
    }

    $metas['comments'] = get_comments_number($post_id);

    $metas['author_name'] = get_the_author_meta('display_name', $object['author']);
    $metas['author_avatar'] = get_avatar_url($object['author']);

    $cats = get_the_terms($post->ID, 'qa_cat');

    if($cats){
        $metas['cat'] = array('name'=>$cats[0]->name,'id' => $cats[0]->term_id);
    }

    $metas['sticky'] = $post->menu_order;

    if($post_id) {
        $metas['seo'] = WWA_seo('single', $post_id);
    }
    return $metas;
}

function WWA_rest_qacomments_metas($object, $field_name, $request){
    $metas = array();
    $comment = get_comment($object['id']);
    $metas['comments'] = $comment ? $comment->comment_karma : 0;
    $metas['post_title'] = get_the_title($object['post']);
    return $metas;
}

function WWA_rest_page_metas($object, $field_name, $request){
    $metas = array();
    $img = get_the_post_thumbnail_url($object['id'], 'full');
    if($img) $metas['cover'] = $img;
    $metas['seo'] = WWA_seo('single', $object['id']);
    $webview = get_post_meta($object['id'], 'wpcom_webview', true);
    if($webview){
        $metas['webview'] = $webview;
    }
    return $metas;
}

function WWA_get_related_posts($post, $showposts=5){
    if($showposts==0) return;
    $options = WWA_options();

    $args = array(
        'post__not_in' => array($post),
        'posts_per_page' => $showposts,
        'ignore_sticky_posts' => 1,
        'orderby' => 'rand',
        'post_mime_type' => 'empty'
        // 'meta_query' => array(
        //     'relation' => 'OR',
        //     array(
        //         'key'     => '_wwa_hide',
        //         'value'   => '1',
        //         'compare' => '!=',
        //     ),
        //     array(
        //         'key'     => '_wwa_hide',
        //         'compare' => 'NOT EXISTS'
        //     ),
        // )
    );

    if(isset($options['related_by']) && $options['related_by']=='1'){
        $tag_list = array();
        $tags = get_the_tags($post);
        if($tags) {
            foreach ($tags as $tag) {
                $tid = $tag->term_id;
                if (!in_array($tid, $tag_list)) {
                    $tag_list[] = $tid;
                }
            }
        }
        $args['tag__in'] = $tag_list;
        $cats_hide = isset($options['cats_hide']) && $options['cats_hide'] ? $options['cats_hide'] : array();
        if(!empty($cats_hide)) $args['category__not_in'] = $cats_hide;
    }else{
        $cat_list = array();
        $categories = get_the_category($post);
        if($categories) {
            foreach ($categories as $category) {
                $cid = $category->term_id;
                if (!in_array($cid, $cat_list)) {
                    $cat_list[] = $cid;
                }
            }
        }
        $args['cat'] = join(',', $cat_list);
    }

    $posts_query  = new WP_Query();
    $posts = $posts_query->query( $args );

    return $posts;
}

function WWA_rest_kuaixun_metas( $object, $field_name, $request ){
    $metas = array();
    // 缩略图
    $img_url = WWA_thumbnail_url($object['id'], 'full');
    if($img_url) $metas['thumb'] = $img_url;
    return $metas;
}

function WWA_rest_special_metas( $object, $field_name, $request ){
    $metas = array();
    // 缩略图
    $img_url = get_term_meta( $object['id'], 'wpcom_thumb', true );
    $metas['thumb'] = $img_url;

    // 最新3篇文章
    if(isset($request['id']) && $request['id'] == $object['id']){
        $metas['banner'] = get_term_meta( $object['id'], 'wpcom_wwa_banner', true );
        $metas['banner'] = $metas['banner'] ?: $metas['thumb'];
        $metas['text_color'] = get_term_meta( $object['id'], 'wpcom_wwa_text_color', true );
    }else{
        $metas['posts'] = array();
        $args = array(
            'posts_per_page' => 3,
            'post_mime_type' => 'empty',
            'tax_query' => array(
                array(
                    'taxonomy' => 'special',
                    'field' => 'term_id',
                    'terms' => $object['id']
                )
            )
        );
        $posts_query  = new WP_Query();
        $postslist = $posts_query->query( $args );
        global $post;
        foreach($postslist as $post){ setup_postdata($post);
            $metas['posts'][] = array(
                'id' => $post->ID,
                'title' => get_the_title()
            );
        } wp_reset_postdata();
    }
    $metas['seo'] = WWA_seo('term', $object['id']);
    return $metas;
}

add_filter( 'rest_post_query', 'WWA_rest_post_query', 10, 2 );
function WWA_rest_post_query( $args, $request ){
    $options = WWA_options();
    $hide = isset($options['cats_hide']) && $options['cats_hide'] ? $options['cats_hide'] : array();
    if(isset($request['home']) && $request['home']=='true'){
        $exclude = isset($options['cats_exclude'])&&$options['cats_exclude'] ? $options['cats_exclude'] : array();
        $args['ignore_sticky_posts'] = 0;
        $args['home_sticky'] = 1;
        $args[ 'orderby' ] = 'menu_order date';
    }
    $args['category__not_in'] = isset($exclude) && $exclude ? array_merge($hide, $exclude) : $hide;

    $args['post_mime_type'] = 'empty';
    return $args;
}

add_filter('posts_where', 'WWA_posts_where', 10, 2);
function WWA_posts_where($where, $that){
    $where = str_replace(".post_mime_type LIKE 'empty/%'", ".post_mime_type = ''", $where);
    return $where;
}

add_action( 'pre_get_posts', 'WWA_sticky_posts_query', 50 );
function WWA_sticky_posts_query( $q ) {
    if($q->get('home_sticky')){
        $q->set('ignore_sticky_posts', 0);
    }
    return $q;
}

add_filter( 'pre_update_option_sticky_posts', 'wpcom_fix_sticky_posts' );
if ( ! function_exists( 'wpcom_fix_sticky_posts' ) ) :
    function wpcom_fix_sticky_posts( $stickies ) {
        if( !class_exists('SCPO_Engine') ) {
            global $wpdb;
            $menu_order = 1;
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE `post_type` = 'post' AND `menu_order` not IN (0,1)" );
            if( $count>0 ) {
                // 先预处理防止插件设置的menu_order，主要是SCPOrder插件
                $wpdb->update($wpdb->posts, array('menu_order' => 0), array('post_type' => 'post'));
            }
        }else{
            $menu_order = -1;
        }

        $old_stickies = array_diff( get_option( 'sticky_posts' ), $stickies );
        foreach( $stickies as $sticky )
            wp_update_post( array( 'ID' => $sticky, 'menu_order' => $menu_order ) );
        foreach( $old_stickies as $sticky )
            wp_update_post( array( 'ID' => $sticky, 'menu_order' => 0 ) );

        return $stickies;
    }
endif;

function WWA_is_rest(){
    $prefix = rest_get_url_prefix();
    $rest_url = wp_parse_url( site_url( $prefix ) );
    $current_url = wp_parse_url( add_query_arg( array( ) ) );
    $rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
    if(!$rest) $rest = isset($current_url['query']) && strpos( $current_url['query'], 'rest_route=', 0 ) === 0;
    return $rest;
}

function WWA_weapp_urlscheme($path, $query=''){
    $token = WWA_weapp_get_access_token();
    $url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $token;
    $params = array(
        'jump_wxa' => array(
            'path' => $path,
            'query' => $query
        ),
        'is_expire' => true,
        'expire_time' => time() + 24 * 60 * 60
    );
    $body = json_encode($params, JSON_UNESCAPED_UNICODE);
    $result = WWA_http_request($url, $body, 'POST');
    if(isset($result['errcode']) && $result['errcode'] == '0'){
        return $result['openlink'];
    }
}

function WWA_weapp_urllink($path, $query=''){
    $token = WWA_weapp_get_access_token();
    $url = 'https://api.weixin.qq.com/wxa/generate_urllink?access_token=' . $token;
    $params = array(
        'path' => $path,
        'query' => $query,
        'is_expire' => true,
        'expire_type' => 0,
        'expire_time' => time() + 24 * 60 * 60
    );
    $body = json_encode($params, JSON_UNESCAPED_UNICODE);
    $result = WWA_http_request($url, $body, 'POST');
    if(isset($result['errcode']) && $result['errcode'] == '0'){
        return $result['url_link'];
    }
}

add_action( 'pre_get_posts', 'WWA_pre_get_posts' );
function WWA_pre_get_posts( $query ) {
    if ( WWA_is_rest() ) {
        $tax_query = $query->get('tax_query');
        if($tax_query){
            $new_tax = array();
            foreach ($tax_query as $i => $tax) {
                if($tax['taxonomy']=='category'){
                    $tax['include_children'] = true;
                }
                $new_tax[$i] = $tax;
            }
            $query->set('tax_query', $new_tax);
        }

        if($query->get('post_type') == 'qa_post'){
            $orderby = 'menu_order ' . $query->get('orderby');
            $query->set('orderby', $orderby);
        }
    }
}

add_filter('get_next_post_where', 'WWA_prenext_where', 10);
add_filter('get_previous_post_where', 'WWA_prenext_where', 10);
function WWA_prenext_where($where){
    if ( WWA_is_rest() ) {
        return "{$where} AND p.post_mime_type = ''";
    }
    return $where;
}

add_filter( 'the_content', 'WWA_the_content', 99999 );
function WWA_the_content( $content ) {
    $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : '');
    if ( WWA_is_rest() && $type ) {
        $options = WWA_options();
        preg_match_all( '/<a [^>].*?>/i', $content, $matches );
        $search = array();
        $replace = array();
        foreach ( $matches[0] as $aHTML ) {
            if( in_array($aHTML, $search) ){ continue; }
            $replaceHTML = $aHTML;

            $url = preg_match('/<a .*?href=[\'"]?([^\'"\s]+)[\'"]?.*?>/i', $aHTML, $m);
            if($m && isset($m[1]) && $m[1]){
                $post_id = url_to_postid($m[1]);
                if($post_id){
                    $post = get_post($post_id);
                    $webview = get_post_meta($post_id, 'wpcom_webview', true);
                    $url = '';
                    if($webview){
                        $url = 'data-webview="'.$webview.'"';
                    }else{
                        if($post->post_type==='post'){
                            $url = 'pages/single/index?id='.$post_id;
                        }else if($post->post_type==='page'){
                            $url = 'pages/page/index?id='.$post_id;
                        }else if($post->post_type==='qa_post'){
                            $url = 'pages/question/single?id='.$post_id;
                        }
                        $url = 'data-miniurl="'.$url.'"';
                    }
                    if($url){
                        $replaceHTML = str_replace( '<a ', '<a '.$url.' ', $aHTML );
                        array_push( $search, $aHTML );
                        array_push( $replace, $replaceHTML );
                    }
                }else{
                    $p = parse_url($m[1]);
                    $list = isset($options['webview-list']) && $options['webview-list'] ? explode("\r\n", trim($options['webview-list'])) : array();
                    if(!empty($list) && $p && $p['host'] && in_array($p['host'], $list)){
                        $replaceHTML = str_replace( '<a ', '<a data-webview="'.$m[1].'" ', $aHTML );
                        array_push( $search, $aHTML );
                        array_push( $replace, $replaceHTML );
                    }
                }
            }
        }
        $content = str_replace( $search, $replace, $content );
    }
    return $content;
}

function WWA_rest_cat_metas($object, $field_name, $request){
    $metas = array();
    $metas['seo'] = WWA_seo('term', $object['id']);
    $metas['banner'] = get_term_meta( $object['id'], 'wpcom_wwa_banner', true );
    $metas['text_color'] = get_term_meta( $object['id'], 'wpcom_wwa_text_color', true );
    return $metas;
}

function WWA_rest_comment_reply_to($object, $field_name, $request){
    if($object['parent']){
        $parent = get_comment($object['parent']);
        return $parent->comment_author;
    }
}

function WWA_rest_comment_reply_post($object, $field_name, $request){
    if($object['post']){
        $post = get_post($object['post']);
        return get_the_title($post);
    }
}

add_filter( 'rest_comment_query', 'WWA_rest_comment_query', 10, 2 );
function WWA_rest_comment_query( $prepared_args, $request ){
    if (!$request['author'] && !$request['parent']) {
        $prepared_args['hierarchical'] = true;
        $prepared_args['wwa'] = true;
    }
    return $prepared_args;
}

add_action('parse_comment_query', 'WWA_rest_comment_query_for_qa');
function WWA_rest_comment_query_for_qa($query){
    if(isset($query->query_vars['wwa']) && $query->query_vars['wwa'] && $query->query_vars['type'] === 'answer' && $query->query_vars['parent__in']){
        $query->query_vars['type'] = 'qa_comment';
    }
}

add_filter('rest_prepare_comment', 'WWA_rest_comment_replace_username');
function WWA_rest_comment_replace_username($response){
    if($response && $response->data['author']){
        $user = get_user_by('ID', $response->data['author']);
        if($user && isset($user->ID) && $user->ID) $response->data['author_name'] = $user->display_name;
    }
    return $response;
}

function WWA_unique_username( $username ) {
    $username = sanitize_user( $username, true );
    static $i;
    if ( null === $i ) {
        $i = 1;
    } else {
        $i ++;
    }
    if ( ! username_exists( $username ) ) {
        return $username;
    }
    $new_username = sprintf( '%s%s', $username, $i );
    if ( ! username_exists( $new_username ) ) {
        return $new_username;
    } else {
        return call_user_func( __FUNCTION__, $username );
    }
}

function WWA_basic_auth_handler( $user ) {
    global $wp_json_basic_auth_error;

    $wp_json_basic_auth_error = null;

    // Don't authenticate twice
    if ( ! empty( $user ) ) {
        return $user;
    }

    // Check that we're trying to authenticate
    if ( isset($_SERVER['SERVER_SOFTWARE']) && $_SERVER['SERVER_SOFTWARE'] === 'Apache' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) {
        preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches);
        list($name, $password, $expire) = explode(':', base64_decode($matches[1]));
        $_SERVER['PHP_AUTH_USER'] = $name;
        $_SERVER['PHP_AUTH_PW'] = $password.':'.$expire;
    }else if(isset($_SERVER['HTTP_BASICAUTH']) && $_SERVER['HTTP_BASICAUTH']){
        list($name, $password, $expire) = explode(':', base64_decode($_SERVER['HTTP_BASICAUTH']));
        $_SERVER['PHP_AUTH_USER'] = $name;
        $_SERVER['PHP_AUTH_PW'] = $password.':'.$expire;
    }else if(!isset( $_SERVER['PHP_AUTH_USER'] )){
        return $user;
    }

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    /**
     * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
     * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
     * recursion and a stack overflow unless the current function is removed from the determine_current_user
     * filter during authentication.
     */
    remove_filter( 'determine_current_user', 'WWA_basic_auth_handler', 20 );

    $user = wp_authenticate( $username, $password );

    add_filter( 'determine_current_user', 'WWA_basic_auth_handler', 20 );

    if ( is_wp_error( $user ) ) {
        $wp_json_basic_auth_error = $user;
        return null;
    }

    $wp_json_basic_auth_error = true;

    return $user->ID;
}
add_filter( 'determine_current_user', 'WWA_basic_auth_handler', 20 );

function WWA_basic_auth_error( $error ) {
    // Passthrough other errors
    if ( ! empty( $error ) ) {
        return $error;
    }

    global $wp_json_basic_auth_error;

    return $wp_json_basic_auth_error;
}
add_filter( 'rest_authentication_errors', 'WWA_basic_auth_error' );


add_filter( 'authenticate', 'WWA_rest_authenticate', 100 );
function WWA_rest_authenticate($user){
    if( ($user == null || is_wp_error($user)) && isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_PW'] ){
        $auth_pw = explode(':', $_SERVER['PHP_AUTH_PW']);
        $password = isset($auth_pw[0]) ? $auth_pw[0] : '';
        $expire = isset($auth_pw[1]) ? $auth_pw[1] : '';

        if ($password && $expire && $expire > $_SERVER['REQUEST_TIME']) { // 未过期
            global $wp_hasher;
            if ( empty($wp_hasher) ) {
                require_once( ABSPATH . WPINC . '/class-phpass.php');
                $wp_hasher = new PasswordHash(8, true);
            }
            $get_user = get_user_by( 'ID', $_SERVER['PHP_AUTH_USER'] );
            if($wp_hasher->CheckPassword($_SERVER['PHP_AUTH_USER'] . ':' . $get_user->user_pass . md5($expire), $password)) $user = $get_user;
        }
    }
    return $user;
}


add_filter( 'rest_prepare_post', 'WWA_rest_prepare_post', 10, 3 );
add_filter( 'rest_prepare_qa_post', 'WWA_rest_prepare_post', 10, 3 );
function WWA_rest_prepare_post( $data, $post, $request ) {
    $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : '');
    if(!$type) return $data;

    $_data = $data->data;
    $params = $request->get_params();
    unset( $_data['featured_media'] );
    unset( $_data['format'] );
    unset( $_data['ping_status'] );
    unset( $_data['comment_status'] );
    unset( $_data['template'] );
    unset( $_data['categories'] );
    unset( $_data['guid'] );
    unset( $_data['link'] );
    unset( $_data['special'] );
    unset( $_data['slug'] );
    unset( $_data['tags'] );
    unset( $_data['modified'] );
    unset( $_data['meta'] );
    unset( $_data['date'] );
    if ( !$request['id'] ) { // 无ID则不显示内容
        unset( $_data['content'] );
    }

    foreach($data->get_links() as $_linkKey => $_linkVal) {
        $data->remove_link($_linkKey);
    }

    $data->data = $_data;
    return $data;
}

function WWA_options(){
    return get_option('wwa_options');
}

function WWA_thumbnail_url($post_id='', $size='full'){
    global $post;
    if(!$post_id) $post_id = isset($post->ID) && $post->ID ? $post->ID : '';
    $img = get_the_post_thumbnail_url($post_id, $size);
    if( !$img ){
        if( !$post || $post->ID!=$post_id){
            $post = get_post($post_id);
        }
        ob_start();
        the_content();
        $content = ob_get_contents();
        ob_end_clean();
        preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $content, $matches);
        if(isset($matches[1]) && isset($matches[1][0])) { // 文章有图片
            $img = $matches[1][0];
        }
    }
    return $img;
}

function WWA_http_request($url, $body=array(), $method='GET', $headers=array()){
    $result = wp_remote_request($url, array('method' => $method, 'body'=>$body, 'headers' => $headers));
    if( is_array($result) ){
        $json_r = json_decode($result['body'], true);
        if( !$json_r ){
            parse_str($result['body'], $json_r);
            if( count($json_r)==1 && current($json_r)==='' ) return $result['body'];
        }
        return $json_r;
    }
}

function WWA_weapp_get_access_token(){
    $options = WWA_options();
    $access_token = json_decode(get_option('weapp_access_token'), true);
    if($access_token && $access_token['expires_in'] > time()){
        return $access_token['access_token'];
    }else{
        $params = array(
            'appid' => isset($options['appid']) ? $options['appid'] : '',
            'secret' => isset($options['secret']) ? $options['secret'] : '',
            'grant_type' => 'client_credential'
        );
        $str = WWA_http_request('https://api.weixin.qq.com/cgi-bin/token', $params, 'GET');
        if($str && isset($str['access_token']) && $str['access_token']){
            $str['expires_in'] = $str['expires_in']+time();
            update_option('weapp_access_token', json_encode($str));
            return $str['access_token'];
        }
    }
}

function WWA_swan_get_access_token(){
    $options = WWA_options();
    $access_token = json_decode(get_option('swan_access_token'), true);
    if($access_token && $access_token['expires_in'] > time()){
        return $access_token['access_token'];
    }else{
        $params = array(
            'client_id' => isset($options['swan-key']) ? $options['swan-key'] : '',
            'client_secret' => isset($options['swan-secret']) ? $options['swan-secret'] : '',
            'grant_type' => 'client_credentials',
            'scope' => 'smartapp_snsapi_base'
        );
        $str = WWA_http_request('https://openapi.baidu.com/oauth/2.0/token', $params, 'GET');
        if($str && isset($str['access_token']) && $str['access_token']){
            $str['expires_in'] = $str['expires_in']+time();
            update_option('swan_access_token', json_encode($str));
            return $str['access_token'];
        }
    }
}

function WWA_qq_get_access_token(){
    $options = WWA_options();
    $access_token = json_decode(get_option('qq_access_token'), true);
    if($access_token && $access_token['expires_in'] > time()){
        return $access_token['access_token'];
    }else{
        $params = array(
            'appid' => isset($options['qq-appid']) ? $options['qq-appid'] : '',
            'secret' => isset($options['qq-secret']) ? $options['qq-secret'] : '',
            'grant_type' => 'client_credential'
        );
        $str = WWA_http_request('https://api.q.qq.com/api/getToken', $params, 'GET');
        if($str && isset($str['access_token']) && $str['access_token']){
            $str['expires_in'] = $str['expires_in']+time();
            update_option('qq_access_token', json_encode($str));
            return $str['access_token'];
        }
    }
}

function WWA_toutiao_get_access_token(){
    $options = WWA_options();
    $access_token = json_decode(get_option('toutiao_access_token'), true);
    if($access_token && $access_token['expires_in'] > time()){
        return $access_token['access_token'];
    }else{
        $params = array(
            'appid' => isset($options['toutiao-appid']) ? $options['toutiao-appid'] : '',
            'secret' => isset($options['toutiao-secret']) ? $options['toutiao-secret'] : '',
            'grant_type' => 'client_credential'
        );
        $str = WWA_http_request('https://developer.toutiao.com/api/apps/token', $params, 'GET');
        if($str && isset($str['access_token']) && $str['access_token']){
            $str['expires_in'] = $str['expires_in']+time();
            update_option('toutiao_access_token', json_encode($str));
            return $str['access_token'];
        }
    }
}

add_action('justweapp_options_updated', 'WWA_remove_token');
function WWA_remove_token(){
    $options = WWA_options();
    if(isset($options['appid']) && $options['appid']){
        update_option('weapp_access_token', '');
    }
    if(isset($options['swan-key']) && $options['swan-key']){
        update_option('swan_access_token', '');
    }
    if(isset($options['qq-appid']) && $options['qq-appid']){
        update_option('qq_access_token', '');
    }
    if(isset($options['toutiao-appid']) && $options['toutiao-appid']){
        update_option('toutiao_access_token', '');
    }
}

function WWA_weapp_msg_sec_check($content){
    $access_token = WWA_weapp_get_access_token();
    if($access_token){
        $params = array(
            'content' => strip_tags($content)
        );
        $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
        $str = WWA_http_request('https://api.weixin.qq.com/wxa/msg_sec_check?access_token='.$access_token, $params, 'POST');
        if($str && isset($str['errcode']) && $str['errcode'] == '87014'){
            return false;
        }
    }
    return true;
}

function WWA_weapp_wxacode($page, $scene){
    $access_token = WWA_weapp_get_access_token();
    if($access_token){
        $params = array(
            'scene' => $scene,
            'page' => $page
        );
        $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
        $str = WWA_http_request('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token, $params, 'POST');
        return $str;
    }
}

function WWA_swan_qrcode($page){
    $access_token = WWA_swan_get_access_token();
    if($access_token){
        $params = array(
            'path' => $page,
            'mf' => 1
        );
        $str = WWA_http_request('https://openapi.baidu.com/rest/2.0/smartapp/qrcode/getunlimited?access_token='.$access_token, $params, 'POST');
        return $str;
    }
}

function WWA_toutiao_qrcode($page){
    $access_token = WWA_toutiao_get_access_token();
    if($access_token){
        $params = array(
            'access_token' => $access_token,
            'path' => urlencode($page)
        );
        $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
        $str = WWA_http_request('https://developer.toutiao.com/api/apps/qrcode', $params, 'POST', array('content-type' => 'application/json'));
        return $str;
    }
}

function WWA_alipay_qrcode($page, $query) {
    $options = WWA_options();
    $params = array(
        'method' => 'alipay.open.app.qrcode.create',
        'format' => 'JSON',
        'charset' => 'utf-8',
        'sign_type' => 'RSA2',
        'timestamp' => date("Y-m-d H:i:s"),
        'version' => '1.0',
        'app_id' => isset($options['alipay-appid']) ? $options['alipay-appid'] : '',
        'biz_content' => json_encode(array(
            'url_param' => $page,
            'query_param' => $query,
            'describe' => $page . '?' . $query
        ))
    );
    $params['sign'] = WWA_alipay_sign(WWA_alipay_getSignContent($params), 'RSA2');
    $str = WWA_http_request('https://openapi.alipay.com/gateway.do', $params, 'GET');
    return isset($str['alipay_open_app_qrcode_create_response']) ? $str['alipay_open_app_qrcode_create_response'] : '';
}

function WWA_alipay_sign($data, $signType = "RSA") {
    $options = WWA_options();
    $priKey = isset($options['alipay-prikey']) ? $options['alipay-prikey'] : '';
    $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

    ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');

    if ("RSA2" == $signType) {
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
    } else {
        openssl_sign($data, $sign, $res);
    }

    $sign = base64_encode($sign);
    return $sign;
}

function WWA_alipay_getSignContent($params) {
    ksort($params);

    $stringToBeSigned = "";
    $i = 0;
    foreach ($params as $k => $v) {
        if (false === WWA_alipay_checkEmpty($v) && "@" != substr($v, 0, 1)) {
            if ($i == 0) {
                $stringToBeSigned .= "$k" . "=" . "$v";
            } else {
                $stringToBeSigned .= "&" . "$k" . "=" . "$v";
            }
            $i++;
        }
    }

    unset ($k, $v);
    return $stringToBeSigned;
}

function WWA_alipay_checkEmpty($value) {
    if (!isset($value))
        return true;
    if ($value === null)
        return true;
    if (trim($value) === "")
        return true;

    return false;
}

function WWA_qq_msg_sec_check($content){
    $access_token = WWA_qq_get_access_token();
    if($access_token){
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['qq-appid']) ? $options['qq-appid'] : '',
            'access_token' => $access_token,
            'content' => strip_tags($content)
        );
        $str = WWA_http_request('https://api.q.qq.com/api/json/security/MsgSecCheck?access_token='.$access_token, $params, 'POST');
        if($str && isset($str['errCode']) && $str['errCode'] == '87014'){
            return false;
        }
    }
    return true;
}

function WWA_toutiao_msg_sec_check($content){
    $access_token = WWA_toutiao_get_access_token();
    if($access_token){
        $options = WWA_options();
        $params = array(
            'tasks' => array(
                array(
                    'content' => strip_tags($content)
                )
            )
        );
        $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
        $str = WWA_http_request('https://developer.toutiao.com/api/v2/tags/text/antidirt', $params, 'POST', array('X-Token' => $access_token, 'content-type' => 'application/json'));
        if($str && isset($str['data']) && isset($str['data'][0]['predicts']) && $str['data'][0]['predicts'][0]['hit']){
            return false;
        }
    }
    return true;
}

function WWA_toutiao_img_sec_check($image){
    $access_token = WWA_toutiao_get_access_token();
    if($access_token){
        $options = WWA_options();
        $params = array(
            'app_id' => isset($options['toutiao-appid']) ? $options['toutiao-appid'] : '',
            'access_token' => $access_token,
            'image_data' => $image
        );
        $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
        $str = WWA_http_request('https://developer.toutiao.com/api/apps/censor/image', $params, 'POST', array('content-type' => 'application/json'));
        if($str && isset($str['error']) && $str['error'] == 0 && isset($str['predicts'])){
            foreach ($str['predicts'] as $key => $value) {
                if($value['hit']){
                    return false;
                }
            }
        }
    }
    return true;
}

function WWA_alipay_msg_sec_check($content) {
        $options = WWA_options();
        $params = array(
            'method' => 'alipay.security.risk.content.detect',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s"),
            'version' => '1.0',
            'app_id' => isset($options['alipay-appid']) ? $options['alipay-appid'] : '',
            'biz_content' => json_encode(array(
                'content' => strip_tags($content)
            ))
        );
        $params['sign'] = WWA_alipay_sign(WWA_alipay_getSignContent($params), 'RSA2');
        $str = WWA_http_request('https://openapi.alipay.com/gateway.do', $params, 'POST');
        $res = isset($str['alipay_security_risk_content_detect_response']) ? $str['alipay_security_risk_content_detect_response'] : array();
        if(isset($res['action']) && $res['action']==='PASSED'){
            return true;
        }
        return false;
    }

add_filter('rest_pre_insert_comment', 'WWA_pre_insert_comment');
function WWA_pre_insert_comment($comment){
    $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : '';
    $type = isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : $type;
    if($type){
        $comment['comment_agent'] = $type . '.' . 'app';
        if($type == 'qq'){
            $check = WWA_qq_msg_sec_check($comment['comment_content']);
        }else if($type == 'alipay'){
            $check = WWA_alipay_msg_sec_check($comment['comment_content']);
        }else if($type == 'toutiao'){
            $check = WWA_toutiao_msg_sec_check($comment['comment_content']);
        }else{
            $check = WWA_weapp_msg_sec_check($comment['comment_content']);
        }
        if(!$check){
            return new WP_Error( 'rest_comment_content_invalid', '抱歉，评论含有违法违规内容', array( 'status' => 200 ) );
        }
    }
    return $comment;
}

add_filter('get_usernumposts', 'WWA_get_usernumposts');
function WWA_get_usernumposts($count){
    if(WWA_is_rest()){
        $current_url = wp_parse_url( add_query_arg( array( ) ) );
        $path = isset($current_url['query']) ? $current_url['query'] : $current_url['path'];
        if(preg_match('/users/i', $path)) $count = $count ? $count : 1;
    }
    return $count;
}

function WWA_seo($type='', $id=''){
    global $options;
    $keywords = '';
    $description = '';
    $title = '';

    if(!isset($options['seo'])){
        $options['keywords'] = '';
        $options['description'] = '';
    }
    if ($type=='home') {
        $wwa_options = WWA_options();
        if(isset($wwa_options['seo_title'])){
            $title = $wwa_options['seo_title'];
            $description = $wwa_options['seo_description'];
            $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($wwa_options['seo_keywords']))));
        }
        if($title=='') $title = isset($options['home-title']) ? $options['home-title'] : '';
        if($description=='') $description = esc_attr(trim(strip_tags($options['description'])));
        if($keywords=='') $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($options['keywords']))));

        $image = isset($options['wx_thumb']) ? $options['wx_thumb'] : '';

        if($title=='') {
            $desc = get_bloginfo('description');
            if ($desc) {
                $title = get_option('blogname') . (isset($options['title_sep_home']) && $options['title_sep_home'] ? $options['title_sep_home'] : ' - ') . $desc;
            } else {
                $title = get_option('blogname');
            }
        }
    } else if ($type=='single' && $id) {
        global $post;
        $post = get_post($id);
        $keywords = str_replace('，', ',', esc_attr(trim(strip_tags(get_post_meta( $post->ID, 'wpcom_seo_keywords', true)))));
        if($keywords=='' && $post->post_type ==='post'){
            $post_tags = get_the_tags();
            if ($post_tags) {
                foreach ($post_tags as $tag) {
                    $keywords = $keywords . $tag->name . ",";
                }
            }
            $keywords = rtrim($keywords, ',');
        } else if($keywords=='' && $post->post_type ==='page') {
            $keywords = $post->post_title;
        }else if($post->post_type ==='product'){
            $product_tag = get_the_terms( $post->ID, 'product_tag' );
            if ($product_tag) {
                foreach ($product_tag as $tag) {
                    $keywords = $keywords . $tag->name . ",";
                }
            }
            $keywords = rtrim($keywords, ',');
        }
        $description = esc_attr(trim(strip_tags(get_post_meta( $post->ID, 'wpcom_seo_description', true))));
        if($description=='') {
            if ($post->post_excerpt) {
                $description = esc_attr(strip_tags($post->post_excerpt));
            } else {
                $content = preg_replace("/\[(\/?map.*?)\]/si", "", $post->post_content);

                $content = str_replace(' ', '', trim(strip_tags($content)));
                $content = preg_replace('/\\s+/', ' ', $content );

                $description = utf8_excerpt($content, 160);
            }
        }

        preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
        if(isset($matches[1]) && isset($matches[1][2])){
            $img_url = array(esc_url($matches[1][0]),esc_url($matches[1][1]),esc_url($matches[1][2]));
        } else {
            $img_url = WWA_thumbnail_url($post->ID, 'full');
            if(!$img_url && isset($matches[1]) && isset($matches[1][0])){
                $img_url = esc_url($matches[1][0]);
            }
        }
        $image = $img_url ? $img_url : (isset($options['wx_thumb']) ? $options['wx_thumb'] : '');
        $title = $post->post_title;
    } else if ($type=='term' && $id) {
        $term = get_term( $id );
        $keywords = get_term_meta( $term->term_id, 'wpcom_seo_keywords', true );
        $keywords = $keywords!='' ? $keywords : $term->name;
        $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($keywords))));

        $description = get_term_meta( $term->term_id, 'wpcom_seo_description', true );
        $description = $description!='' ? $description : term_description($id);
        $description = esc_attr(trim(strip_tags($description)));
        $title = $term->name;
        $image = get_term_meta( $term->term_id, 'wpcom_wwa_banner', true );
    }

    return array(
        'title' => $title,
        'keywords' => $keywords,
        'description' => $description,
        'image' => isset($image) && $image ? $image : ''
    );
}

if ( ! function_exists( 'utf8_excerpt' ) ) :
    function utf8_excerpt($str, $len){
        $str = strip_tags( str_replace( array( "\n", "\r" ), ' ', $str ) );
        if(function_exists('mb_substr')){
            $excerpt = mb_substr($str, 0, $len, 'utf-8');
        }else{
            preg_match_all("/[x01-x7f]|[xc2-xdf][x80-xbf]|xe0[xa0-xbf][x80-xbf]|[xe1-xef][x80-xbf][x80-xbf]|xf0[x90-xbf][x80-xbf][x80-xbf]|[xf1-xf7][x80-xbf][x80-xbf][x80-xbf]/", $str, $ar);
            $excerpt = join('', array_slice($ar[0], 0, $len));
        }

        if(trim($str)!=trim($excerpt)){
            $excerpt .= '...';
        }
        return $excerpt;
    }
endif;

add_action( 'transition_post_status', 'WWA_pre_submit', 10, 3 );
function WWA_pre_submit( $new_status, $old_status, $post ){
    if( $new_status!='publish' || $new_status==$old_status || !in_array($post->post_type, array('post', 'qa_post'))) return false;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return false;
    if(isset($post->ID)){
        global $wwa_pre_submit;
        $wwa_pre_submit = $post->ID;
    }
}

add_action( 'wp_insert_post', 'WWA_post_submit', 50, 2 );
function WWA_post_submit($post_ID, $post){
    global $wwa_pre_submit;
    if(isset($wwa_pre_submit) && $post->post_status=='publish' && $wwa_pre_submit==$post_ID){
        $args = array($post_ID, $post);
        // 10s后执行定时任务，避免post meta还未保存到数据库的情况
        wp_schedule_single_event( time() + 10, 'WWA_post_submit_cron', $args );
        $wwa_pre_submit = 0;
    }
}

add_action( 'WWA_post_submit_cron', 'WWA_post_submit_cron_fun', 10, 2 );
function WWA_post_submit_cron_fun( $post_ID, $post ) {
    $options = WWA_options();
    $cats_hide = isset($options['cats_hide']) && $options['cats_hide'] ? $options['cats_hide'] : array();
    if(!empty($cats_hide) && has_category($cats_hide, $post_ID)){ // 在排除分类下的文章不提交
        return false;
    }
    // 文章设置排除则不提交
    $hide = get_post_meta($post_ID, '_wwa_hide', true);
    if(isset($hide) && $hide == '1') return $hide;

    // 提交到百度
    if(isset($options['swan-key']) && $options['swan-key'] && $options['swan-secret']) {
        $access_token = WWA_swan_get_access_token();
        if($access_token){
            $type = isset($options['swan-submit-type']) ? $options['swan-submit-type'] : 1;
            $url = ($post->post_type === 'qa_post' ? '/pages/question/single?id=' : '/pages/single/index?id=') . $post_ID;
            $req1 = WWA_http_request('https://openapi.baidu.com/rest/2.0/smartapp/access/submitsitemap/api?access_token='.$access_token, array(
                'type' => $type,
                'url_list' => $url,
                'access_token' => $access_token
            ), 'POST');
            WWA_add_log($url . '@swan - ' . wp_json_encode($req1, JSON_UNESCAPED_UNICODE));
        }
    }

    // 提交到微信
    if(isset($options['appid']) && $options['appid'] && $options['secret']) {
        $weapp_token = WWA_weapp_get_access_token();
        if($weapp_token){
            $params = array(
                'pages' => array(
                    array(
                        'path' => $post->post_type === 'qa_post' ? 'pages/question/single' : 'pages/single/index',
                        'query' => 'id=' . $post_ID
                    )
                ),
                'access_token' => $weapp_token
            );
            $params = json_encode( $params, JSON_UNESCAPED_UNICODE );
            $req2 = WWA_http_request('https://api.weixin.qq.com/wxa/search/wxaapi_submitpages?access_token='.$weapp_token, $params, 'POST');
            WWA_add_log($post_ID . '@weapp - ' . wp_json_encode($req2, JSON_UNESCAPED_UNICODE));
        }
    }
}

if(!function_exists('wpcom_is_empty_mail')){
    add_filter( 'wp_mail', 'WWA_wp_mail', 10);
    function WWA_wp_mail($atts){
        // 邮件发送过滤系统填错邮箱，即未设置邮箱的用户
        if ( isset( $atts['to'] ) ) {
            if(is_array($atts['to'])){
                foreach ($atts['to'] as $k => $to){
                    if(WWA_is_empty_mail($to)){
                        unset($atts['to'][$k]);
                    }
                }
            }else if(WWA_is_empty_mail($atts['to'])){
                $atts['to'] = '';
            }
        }
        return $atts;
    }
    function WWA_is_empty_mail($mail){
        if(preg_match('/@email\.empty$/i', $mail) || preg_match('/@weixin\.qq$/i', $mail) || preg_match('/@(weapp|swan|alipay|toutiao|qq)\.app$/i', $mail)){
            return true;
        }
        return false;
    }
}

add_filter( 'get_avatar_url', 'WWA_replace_avatar_url', 50, 2 );
function WWA_replace_avatar_url($url, $id_or_email){
    global $pagenow, $options;
    if( $pagenow == 'options-discussion.php' ) return $url;

    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) {
        $user_id = absint( $id_or_email );
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $user = get_user_by( 'email', $id_or_email );
        if( isset($user->ID) && $user->ID ) $user_id = $user->ID;
    } elseif ( $id_or_email instanceof WP_User ) {
        $user_id = $id_or_email->ID;
    } elseif ( $id_or_email instanceof WP_Post ) {
        $user_id = $id_or_email->post_author;
    } elseif ( $id_or_email instanceof WP_Comment ) {
        $user_id = $id_or_email->user_id;
        if( !$user_id ){
            $user = get_user_by( 'email', $id_or_email->comment_author_email );
            if( isset($user->ID) && $user->ID ) $user_id = $user->ID;
        }
    }

    if ( $user_id && $avatar = get_user_meta( $user_id, 'wpcom_avatar', 1) ) {
        if(preg_match('/^(http|https|\/\/)/i', $avatar)){
            $url = $avatar;
        }else{
            $uploads = wp_upload_dir();
            $url = $uploads['baseurl'] . $avatar;
        }
    }else if( isset($options['member_avatar']) && $options['member_avatar'] ){
        $url = is_numeric($options['member_avatar']) ? wp_get_attachment_url( $options['member_avatar'] ) : esc_url($options['member_avatar']);
    }

    $url = preg_replace('/^(http|https):/i', '', $url);
    return preg_replace('/\/\/[0-9a-zA-Z]+\.gravatar\.com\/avatar/', '//cravatar.cn/avatar', $url);
}

add_filter('wpcom_exclude_post_metas', 'WWA_exclude_post_metas');
function WWA_exclude_post_metas($metas) {
    $metas += array('favorites', 'likes');
    return $metas;
}

add_action( 'admin_init', 'WWA_admin_setup' );
function WWA_admin_setup() {
    if (!wp_next_scheduled ( 'wpcom_sessions_clear' )) wp_schedule_event(time(), 'hourly', 'wpcom_sessions_clear');
}

add_action( 'wpcom_sessions_clear', array( 'WWA_Session', 'cron') );

function WWA_add_log($msg){
    $_dir = _wp_upload_dir();
    $folder = apply_filters('wpcom_static_cache_path', 'wpcom');
    $dir = $_dir['basedir'] . '/' . $folder;
    if(wp_mkdir_p($dir)) {
        @file_put_contents($dir . '/log-' . date('Ym') . '.log', '['.date('Y-m-d H:i:s') . ']: ' . $msg . "\r\n", FILE_APPEND);
    }
}

add_action('after_setup_theme', 'WWA_User_init', 50);
function WWA_User_init(){
    include_once WWA_DIR . 'includes/user.php';
    new WWA_User();
}

// 根据选项 _wwa_hide 同步更新文章隐藏状态
add_filter( 'add_post_metadata', 'WWA_set_hide_post', 20, 4 );
add_filter( 'update_post_metadata', 'WWA_set_hide_post', 20, 4 );
function WWA_set_hide_post($check, $object_id, $meta_key, $meta_value){
    global $wpdb;
    if($object_id && $meta_key === '_wwa_hide'){
        $data = array(
            'post_mime_type' => $meta_value =='1' ? 'wwa/hide' : ''
        );
        $wpdb->update( $wpdb->posts, $data, array( 'ID' => $object_id ) );
        clean_post_cache( $object_id );
    }
    return $check;
}

add_action('wp_ajax_wwa_get_wx_url', 'WWA_get_wx_url');
add_action('wp_ajax_nopriv_wwa_get_wx_url', 'WWA_get_wx_url');
function WWA_get_wx_url(){
    $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : 0;
    $is_weixin = strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
    $res = array();
    if($post_id){
        $url = $is_weixin ? WWA_weapp_urlscheme('/pages/single/index', 'id='.$post_id) : WWA_weapp_urllink('/pages/single/index', 'id='.$post_id);
        if($url){
            $res['url'] = $url;
        }
    }
    echo json_encode($res);
    wp_die();
}