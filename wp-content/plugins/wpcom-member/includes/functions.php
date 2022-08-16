<?php
defined('ABSPATH') || exit;

add_action('after_setup_theme', 'wpmx_init', 9);
function wpmx_init() {
    $wpmx_info = array(
        'slug' => 'wpcom-member',
        'name' => '用户中心',
        'ver' => WPMX_VERSION,
        'title' => '用户中心',
        'icon' => 'dashicons-wpcom-logo',
        'position' => 72,
        'key' => 'wpmx_options',
        'plugin_id' => 'wpmx',
        'basename' => plugin_basename(__FILE__)
    );

    require_once WPCOM_ADMIN_FREE_PATH . 'load.php';
    $GLOBALS['wpmx'] = new WPCOM_PLUGIN_PANEL_FREE($wpmx_info);
    $wpmx_options = get_option($wpmx_info['key']);
    $GLOBALS['wpmx_options'] = $wpmx_options;

    require_once WPMX_DIR . 'includes/member-functions.php';
    require_once WPMX_DIR . 'includes/form-validation.php';
    require_once WPMX_DIR . 'includes/class-member.php';
    $GLOBALS['wpcom_member'] = new WPCOM_Member();
    require_once WPMX_DIR . 'includes/link-template.php';

    require_once WPMX_DIR . 'includes/required.php';
    if(!class_exists('WPCOM_Session')) {
        require_once WPMX_DIR . 'includes/class-sesstion.php';
        WPCOM_Session::session_prefix();
        add_action( 'wpcom_sessions_clear', array( 'WPCOM_Session', 'cron') );
    }

    add_action('wp_enqueue_scripts', 'wpmx_scripts');
    add_action('admin_enqueue_scripts', 'wpmx_scripts');

    load_plugin_textdomain( 'wpcom', false, basename( WPMX_DIR ) . '/lang' );
}

add_filter('option_wpmx_options', 'wpmx_old_theme_options');
function wpmx_old_theme_options($value){
    // 设置选项需要兼容主题的设置选项
    if(function_exists('wpcom_setup') && !defined('WPCOM_MP_VERSION')){
        global $options, $wpcom_panel;
        $options = $options ?: array();
        // 插件原先有数据的话，则迁移到主题
        if(!empty($value) && is_array($value)){
            foreach($value as $k => $v){
                $options[$k] = $v;
            }
            // 清空插件的设置数据
            if($wpcom_panel->set_theme_options($options)){
                delete_option('wpmx_options');
            }
        }
        $value = $options;
    }else if(empty($value) && $options = get_option('izt_theme_options')){ // 设置选项为空，可能是刚安装插件，检查下是否有原来主题的设置选项
        if(is_string($options)) $options = json_decode($options, true);
        if(!empty($options)) $value = $options;
    }
    return $value;
}

add_action( 'admin_init', 'wpmx_admin_setup' );
function wpmx_admin_setup(){
    if (!class_exists('WPCOM_Session') && !wp_next_scheduled ( 'wpcom_sessions_clear' )) wp_schedule_event(time(), 'hourly', 'wpcom_sessions_clear');
}

add_action('admin_menu', 'wpmx_admin_menu', 20);
function wpmx_admin_menu(){
    // 移除用户中心免费版入口
    if(defined('WPCOM_MP_VERSION') || function_exists('wpcom_setup')) remove_menu_page('wpcom-member');
}

function wpmx_scripts(){
    $action = current_filter();
    if ($action === 'wp_enqueue_scripts') {
        global $wpmx_options;
        wp_enqueue_script('wpcom-member', WPMX_URI . 'js/index.js', array('jquery'), WPMX_VERSION, true);
        wp_enqueue_style('wpcom-member', WPMX_URI . 'css/style.css', array(), WPMX_VERSION);
        wp_register_script('wpcom-icons', WPMX_URI . 'js/icons-2.6.18.js', array(), WPMX_VERSION, true);
        wp_enqueue_script('wpcom-icons');

        if(!function_exists('wpcom_setup')){
            $color = $wpmx_options['member_color'] ?: '#206be7';
            $hover = $wpmx_options['member_hover_color'] ?: '#1162e8';
            if($color || $hover){
                $custom_css = ':root{'.($color?'--member-color: '.$color.';':'').($hover?'--member-hover: '.$hover.';':'').'}';
                wp_add_inline_style( 'wpcom-member', $custom_css );
            }
        }

        $script = array(
            'ajaxurl' => admin_url( 'admin-ajax.php'),
            'plugin_url' => WPMX_URI
        );
        if(is_singular()) $script['post_id'] = get_queried_object_id();
        if($wpmx_options && isset($wpmx_options['sl_wechat_follow']) && $wpmx_options['sl_wechat_follow']){
            foreach ($wpmx_options['sl_wechat_follow'] as $f){
                if($f) {
                    $script['wechat_follow'] = 1;
                    break;
                }
            }
        }
        $wpmx_js = apply_filters('wpmx_localize_script', $script);
        wp_localize_script( 'wpcom-member', '_wpmx_js', $wpmx_js );
    }else{
        wp_enqueue_style('wpcom-member', WPMX_URI . 'css/admin.css', array(), WPMX_VERSION);
    }
}

function wpmx_icon($name, $echo = true, $class='', $alt='icon'){
    if(class_exists('WPCOM')) return WPCOM::icon($name, $echo, $class, $alt);
    $_name = explode(':', $name);
    switch ($_name[0]){
        case 'mti':
            $name = preg_replace('/^mti:/i', '', $name);
            $str = '<i class="wpcom-icon material-icons'.($class?' '.$class:'').'">'.$name.'</i>';
            break;
        case 'if':
            $name = preg_replace('/^if:/i', '', $name);
            $str = '<i class="wpcom-icon'.($class?' '.$class:'').'"><svg aria-hidden="true"><use xlink:href="#icon-'.$name.'"></use></svg></i>';
            break;
        case 'fa':
            $name = preg_replace('/^fa:/i', '', $name);
            $str = '<i class="wpcom-icon fa fa-'.$name.($class?' '.$class:'').'"></i>';
            break;
        case 'ri':
            $name = preg_replace('/^ri:/i', '', $name);
            $str = '<i class="wpcom-icon ri-'.$name.($class?' '.$class:'').'"></i>';
            break;
        case 'http':
        case 'https':
            $str = '<i class="wpcom-icon'.($class?' '.$class:'').'"><img class="j-lazy" src="' . esc_url($name) . '" alt="' . esc_attr($alt) . '" /></i>';
            break;
        default:
            if(preg_match('/^\/\//', $name)){ // "//"开头的地址需要单独匹配
                $str = '<i class="wpcom-icon'.($class?' '.$class:'').'"><img class="j-lazy" src="' . esc_url($name) . '" alt="' . esc_attr($alt) . '" /></i>';
            }else{
                $str = '<i class="wpcom-icon wi'.($class?' '.$class:'').'"><svg aria-hidden="true"><use xlink:href="#wi-'.$name.'"></use></svg></i>';
            }
    }

    if($echo) {
        echo wp_kses( $str, wpmx_allowed_html() );
    } else {
        return wp_kses( $str, wpmx_allowed_html() );
    }
}

function wpmx_comment_status(){
    $status = false;
    if ( get_default_comment_status() === 'open' ) {
        $status = true;
        if(function_exists('wpcom_setup')){
            global $options;
            if( !(isset($options['comments_open']) && $options['comments_open']=='1') ){
                $status = false;
            }
        }
    }
    return  $status;
}

add_action( 'woocommerce_before_edit_account_address_form', 'wc_print_notices', 10 );
add_filter( 'woocommerce_account_menu_items', 'wpcom_woo_account_menu_items' );
function wpcom_woo_account_menu_items( $items ){
    $items['orders'] = __('Orders', 'wpcom');
    $items['downloads'] = __('Downloads', 'wpcom');
    $items['edit-address'] = __('Addresses', 'wpcom');
    unset($items['dashboard']);
    unset($items['edit-account']);
    unset($items['customer-logout']);
    return $items;
}

add_filter( 'woocommerce_get_cancel_order_url', 'wpcom_woo_cancel_order_url' );
function wpcom_woo_cancel_order_url( $url ){
    preg_match('/order_id=([\d]+)/i', $url, $matches);
    if(isset($matches[1]) && $matches[1]){
        $order    = wc_get_order( $matches[1] );
        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'cancel_order' => 'true',
                    'order' => $order->get_order_key(),
                    'order_id' => $order->get_id(),
                    'redirect' => wpcom_subpage_url('orders'),
                ), $order->get_cancel_endpoint()
            ), 'woocommerce-cancel_order'
        );
    }
    return $url;
}

add_filter( 'woocommerce_is_account_page', 'wpcom_wc_is_account_page' );
function wpcom_wc_is_account_page( $res ){
    return is_wpcom_member_page();
}

add_filter( 'wpcom_account_tabs', 'wpcom_woo_add_tabs' );
function wpcom_woo_add_tabs( $tabs ){
    if( !function_exists('is_woocommerce') ) return $tabs;

    $orders = get_option( 'woocommerce_myaccount_orders_endpoint', 'orders' );
    $downloads = get_option( 'woocommerce_myaccount_downloads_endpoint', 'downloads' );
    $edit_address = get_option( 'woocommerce_myaccount_edit_address_endpoint', 'edit-address' );
    $view_order = get_option( 'woocommerce_myaccount_view_order_endpoint', 'view-order' );

    if($orders) {
        $tabs[14] = array(
            'slug' => $orders,
            'title' => __('Orders', 'wpcom'),
            'icon' => 'order-circle'
        );
        add_action( 'wpcom_account_tabs_'.$orders, 'wpcom_account_tabs_orders' );
    }

    if($downloads) {
        $tabs[15] = array(
            'slug' => $downloads,
            'title' => __('Downloads', 'wpcom'),
            'icon' => 'download-circle'
        );
        add_action( 'wpcom_account_tabs_'.$downloads, 'wpcom_account_tabs_downloads' );
    }
    if($edit_address) {
        $tabs[16] = array(
            'slug' => $edit_address,
            'title' => __('Addresses', 'wpcom'),
            'icon' => 'address-circle'
        );
        add_action( 'wpcom_account_tabs_'.$edit_address, 'wpcom_account_tabs_address' );

    }
    if($view_order) {
        $tabs[9999] = array(
            'slug' => $view_order,
            'title' => __('Orders', 'wpcom'),
            'icon' => 'order-circle',
            'parent' => 'orders'
        );
        add_action( 'wpcom_account_tabs_'.$view_order, 'wpcom_account_tabs_view_order' );
    }

    return $tabs;
}

add_filter('woocommerce_get_view_order_url', 'wpcom_woo_order_url', 10, 2);
function wpcom_woo_order_url($url, $that){
    $permalink_structure = get_option('permalink_structure');
    if(!$permalink_structure){ // 默认链接规则
        $view_order = get_option( 'woocommerce_myaccount_view_order_endpoint', 'view-order' );
        $page = wpcom_subpage_url($view_order);
        $url =  add_query_arg( 'pageid', $that->get_id(), $page );
    }
    return $url;
}

function wpcom_account_tabs_orders() {
    $page = get_query_var('pageid') ? get_query_var('pageid') : 1;
    ?>
    <div class="woocommerce">
        <?php do_action( 'woocommerce_account_orders_endpoint', $page ); ?>
    </div>
<?php }

function wpcom_account_tabs_downloads() { ?>
    <div class="woocommerce">
        <?php do_action( 'woocommerce_account_downloads_endpoint' ); ?>
    </div>
<?php }

function wpcom_account_tabs_address() { ?>
    <div class="woocommerce">
        <?php do_action( 'woocommerce_account_edit-address_endpoint', 'billing' ); ?>
    </div>
<?php }

function wpcom_account_tabs_view_order() {
    $order_id = get_query_var('pageid') ? get_query_var('pageid') : 0; ?>
    <div class="woocommerce">
        <?php woocommerce_order_details_table($order_id); ?>
    </div>
<?php }

add_filter( 'woocommerce_get_myaccount_page_permalink', 'wpcom_woo_myaccount_page_permalink' );
function wpcom_woo_myaccount_page_permalink( $link ){
    global $wpmx_options;
    if( isset($wpmx_options['member_page_account']) && $wpmx_options['member_page_account'] ) {
        return wpcom_account_url();
    }
    return $link;
}

add_filter('wpcom_settings', 'wpmx_add_theme_options', 20);
function wpmx_add_theme_options($options){
    if(defined('WPCOM_MP_VERSION')) {
        $options = $options + array(
            array(
                'addto' => 'user',
                'options' => array(
                    array(
                        't' => 'a',
                        'style' => 'success',
                        's' => '<div style="text-align:center;padding: 20px;font-size: 16px;">您已启用<b>用户中心高级版</b>插件，用户中心相关设置请进入后台<a href="admin.php?page=wpcom-member-pro">用户中心</a>下面操作。</div>'
                    )
                )
            )
        );
    }else{
        $_options = wpmx_admin_options();
        $options = $options + array(
            array(
                'addto' => 'user',
                'options' => $_options
            )
        );
    }

    return $options;
}

add_filter('wpcom-member-pro_settings', 'wpmx_add_pro_options', 20);
function wpmx_add_pro_options($options){
    $_options = wpmx_admin_options();
    $options = array_merge($options, array(
        array(
            'title' => '常规设置',
            'icon' => 'gear',
            'index' => 0,
            'options' => $_options
        )
    ));
    if(!function_exists('wpcom_setup')){
        $options = array_merge($options, array(
            array(
                'title' => '风格样式',
                'icon' => 'magic',
                'options' => array(
                    array(
                        'name' => 'member_color',
                        'title' => '主色调',
                        'desc' => '网站主色调，比如链接颜色',
                        'type' => 'c'
                    ),
                    array(
                        'name' => 'member_hover_color',
                        'title' => '悬停颜色',
                        'desc' => '比图链接悬停颜色',
                        'type' => 'c'
                    )
                )
            )
        ));
    }
    return $options;
}

add_filter('wpcom-member_form_options', 'wpmx_add_form_options', 5);
function wpmx_add_form_options($options){
    if(defined('WPCOM_MP_VERSION') || function_exists('wpcom_setup')) return $options;
    $_options = wpmx_admin_options();
    $options = array(
        array(
            'title' => '常规设置',
            'icon' => 'gear',
            'options' => $_options
        ),
        array(
            'title' => '风格样式',
            'icon' => 'magic',
            'options' => array(
                array(
                    'name' => 'member_color',
                    'title' => '主色调',
                    'desc' => '网站主色调，比如链接颜色',
                    'type' => 'c'
                ),
                array(
                    'name' => 'member_hover_color',
                    'title' => '悬停颜色',
                    'desc' => '比图链接悬停颜色',
                    'type' => 'c'
                )
            )
        )
    );
    return $options;
}
function wpmx_admin_options(){
    $type = apply_filters( 'wpcom_member_show_profile', true );
    $options = array(
        array(
            'title' => '常规设置',
            'desc' => '用户中心常规功能设置',
            'type' => 'tt'
        )
    );
    $options = array_merge($options, array(
        array(
            'name' => 'member_avatar',
            'title' => '默认头像',
            'desc' => '用户默认头像，不设置则默认头像为wordpress系统默认头像。建议图片比例 1:1，例如 300 * 300 px',
            'type' => 'at'
        )
    ));
    if($type){
        $options = array_merge($options, array(
            array(
                'name' => 'member_cover',
                'title' => '默认封面',
                'desc' => '用户个人中心/资料卡默认封面图片，建议图片比例：2.7:1，例如 810*300 px',
                'type' => 'u'
            ),
            array(
                'name' => 'member_desc',
                'title' => '默认简介',
                'std' => '这个人很懒，什么都没有留下～'
            ),
            array(
                'name' => 'member_user_slug',
                'title' => '用户链接',
                'desc' => '个人中心页面链接地址格式',
                'std' => '2',
                'type' => 'r',
                'ux' => 1,
                'options' => array(
                    '1' => '用户名',
                    '2' => '用户ID'
                )
            )
        ));
    }

    $options = array_merge($options, array(
        array(
            'name' => '_member_page',
            'title' => '页面设置',
            'desc' => '用户中心常用页面设置',
            'type' => 'tt'
        ),
        array(
            'name' => 'member_page_register',
            'title' => '注册页面',
            'desc' => '需要新建一个页面，并添加短代码<b>[wpcom-member type="form" action="register"]</b>',
            'type' => 'p'
        ),
        array(
            'name' => 'member_page_login',
            'title' => '登录页面',
            'desc' => '需要新建一个页面，并添加短代码<b>[wpcom-member type="form" action="login"]</b>',
            'type' => 'p'
        ),
        array(
            'name' => 'member_page_lostpassword',
            'title' => '重置密码',
            'desc' => '需要新建一个页面，并添加短代码<b>[wpcom-member type="lostpassword"]</b>',
            'type' => 'p'
        ),
        array(
            'name' => 'member_page_account',
            'title' => '帐号设置页面',
            'desc' => '需要新建一个页面，并添加短代码<b>[wpcom-member type="account"]</b>',
            'type' => 'p'
        )
    ));
    if($type){
        $options = array_merge($options, array(
            array(
                'name' => 'member_page_profile',
                'title' => '个人中心页面',
                'desc' => '需要新建一个页面，并添加短代码<b>[wpcom-member type="profile"]</b>',
                'type' => 'p'
            )
        ));
    }
    $options = array_merge($options, array(
        array(
            'name' => 'member_page_terms',
            'title' => '服务条款页面',
            'desc' => '此页面可添加网站注册时的服务条款，可选',
            'type' => 'p'
        ),
        array(
            'title' => '注册登录选项',
            'desc' => '用户注册登录相关设置选项',
            'type' => 'tt'
        ),
        array(
            'name' => 'login_logo',
            'title' => '注册登录页LOGO',
            'desc' => '可选，不设置则使用网站LOGO',
            'type' => 'at'
        ),
        array(
            'name' => 'login_modal',
            'title' => '注册登录弹框',
            'desc' => '开启后点击注册登录会以弹窗方式弹出',
            'type' => 't'
        ),
        array(
            'name' => 'member_reg_active',
            'title' => '注册验证',
            'desc' => '注册后的验证方式，主要针对邮箱，手机注册默认会有验证码进行验证',
            'type' => 'r',
            'ux' => 1,
            'options' => array(
                '0' => '无需验证',
                '1' => '邮件激活验证',
                '2' => '后台管理员审核'
            )
        ),
        array(
            'name' => 'member_reg_notice',
            'title' => '验证提示文字',
            'desc' => '如果选择了注册验证，则会显示这个选项设置的提示文字',
            'std' => '感谢注册我们的网站。我们已经给你发送了一封激活邮件，你需要点击邮件中的激活链接来激活你的帐号，激活完成后即可正常登录使用。',
            'type' => 'ta',
            'r' => 5
        ),
        array(
            'name' => 'member_captcha',
            'title' => '人机验证方式',
            'desc' => '注册登录等表单人机验证方式',
            'type' => 's',
            'std' => '0',
            'options' => array(
                '0' => '腾讯云验证码/防水墙',
                '1' => '阿里云人机验证',
                '2' => 'hCaptcha（适合海外站点，中国大陆地区可使用）',
                '3' => 'Google reCAPTCHA v2（适合海外站点，中国大陆地区无法使用）',
            )
        ),
        array(
            'type' => 'w',
            'filter' => 'member_captcha:0',
            'options' => array(
                array(
                    'title' => '腾讯云验证码/防水墙',
                    'type' => 'tt'
                ),
                array(
                    'name' => 'tc_appid',
                    'title' => 'App ID',
                    'desc' => '申请地址：<a href="https://console.cloud.tencent.com/captcha" target="_blank">https://console.cloud.tencent.com/captcha</a>'
                ),
                array(
                    'name' => 'tc_appkey',
                    'title' => 'App Secret Key',
                    'desc' => '申请地址：<a href="https://console.cloud.tencent.com/captcha" target="_blank">https://console.cloud.tencent.com/captcha</a>'
                )
            )
        ),
        array(
            'type' => 'w',
            'filter' => 'member_captcha:1',
            'options' => array(
                array(
                    'title' => '阿里云人机验证',
                    'type' => 'tt'
                ),
                array(
                    'name' => 'nc_appkey',
                    'title' => 'App Key',
                    'desc' => '不填写则不开启，获取方法：阿里云后台【安全（云盾）>数据风控（业务安全）>人机验证】'
                ),
                array(
                    'name' => 'nc_access_id',
                    'title' => 'Access Key ID',
                    'desc' => '获取方法：阿里云后台右上角鼠标移到头像上，再点击accesskeys进入即可获取'
                ),
                array(
                    'name' => 'nc_access_secret',
                    'title' => 'Access Key Secret',
                    'desc' => '获取方法：阿里云后台右上角鼠标移到头像上，再点击accesskeys进入即可获取'
                )
            )
        ),
        array(
            'type' => 'w',
            'filter' => 'member_captcha:2',
            'options' => array(
                array(
                    'title' => 'hCaptcha',
                    'type' => 'tt'
                ),
                array(
                    'name' => 'hc_sitekey',
                    'title' => 'Sitekey',
                    'desc' => '接口请进入<a href="https://hcaptcha.com/?r=297b29f2b398" target="_blank">hCaptcha官网</a>申请'
                ),
                array(
                    'name' => 'hc_secret',
                    'title' => 'Secret key',
                    'desc' => '接口请进入<a href="https://hcaptcha.com/?r=297b29f2b398" target="_blank">hCaptcha官网</a>申请'
                )
            )
        ),
        array(
            'type' => 'w',
            'filter' => 'member_captcha:3',
            'options' => array(
                array(
                    'title' => 'reCAPTCHA',
                    'type' => 'tt'
                ),
                array(
                    'name' => 'gc_sitekey',
                    'title' => 'Sitekey / 网站密钥',
                    'desc' => '接口请进入<a href="https://developers.google.com/recaptcha" target="_blank">reCAPTCHA官网</a>申请'
                ),
                array(
                    'name' => 'gc_secret',
                    'title' => 'Secret key / 密钥',
                    'desc' => '接口请进入<a href="https://developers.google.com/recaptcha" target="_blank">reCAPTCHA官网</a>申请'
                )
            )
        ),
        array(
            'name' => 'login_redirect',
            'title' => '登录后跳转',
            'desc' => '用户登录后跳转页面的链接地址，留空则默认跳转到上一页，如果没有上一页，则跳转到帐号设置页面'
        ),
        array(
            'title' => '社交登录',
            'desc' => '社交登录接口信息配置',
            'type' => 'tt'
        ),
        array(
            'name' => 'social_login_on',
            'title' => '开启社交登录',
            'desc' => '是否开启此功能',
            'std' => 0,
            'type' => 't'
        ),
        array(
            'type' => 'w',
            'filter' => 'social_login_on:1',
            'options' => array(
                array(
                    'name' => 'social_login_page',
                    'title' => '社交绑定页面',
                    'desc' => '需要新建一个页面，并添加短代码<b>[wpcom-social-login]</b>',
                    'type' => 'p'
                ),
                array(
                    'type' => 'rp',
                    'title' => '登录方式',
                    'name' => '_social_login',
                    'options' => array(
                        array(
                            'name' => 'sl_type',
                            'title' => '登录方式',
                            'type' => 's',
                            'options' => array(
                                'qq' => '腾讯QQ',
                                'weibo' => '新浪微博',
                                'wechat' => '微信开放平台',
                                'wechat2' => '微信公众号平台',
                                'weapp' => '微信小程序（依赖JustWeapp小程序）',
                                'google' => 'Google',
                                'facebook' => 'Facebook',
                                'twitter' => 'Twitter',
                                'github' => 'Github',
                            )
                        ),
                        array(
                            'name' => 'sl_id',
                            'title' => 'ID',
                            'desc' => 'APP ID、Client ID等，微博则填写App Key'
                        ),
                        array(
                            'name' => 'sl_key',
                            'title' => 'Key',
                            'desc' => 'APP Key、 Secret KEY、App Secret等'
                        ),
                        array(
                            'name' => 'sl_weapp_type',
                            'filter' => 'sl_type:weapp',
                            'title' => '国内非个人主体',
                            'type' => 't',
                            'desc' => '国内非个人主体小程序可获取<b>更好的移动端登录体验</b>，支持手机浏览器唤醒微信登录，当然如果移动端希望使用公众号接口也可以直接关闭此选项'
                        )
                    )
                ),
                array(
                    'name' => 'social_login_target',
                    'title' => '新窗口打开',
                    'std' => '1',
                    'type' => 't'
                )
            )
        )
    ));
    return apply_filters('wpmx_admin_options', $options);
}

function wpmx_allowed_html(){
    require_once WPCOM_ADMIN_FREE_PATH . 'includes/class-utils.php';
    return WPCOM_ADMIN_UTILS_FREE::allowed_html();
}