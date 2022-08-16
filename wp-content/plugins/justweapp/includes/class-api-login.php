<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Login_Controller extends WP_REST_Controller{
    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'login';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'callback'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' )
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'method' => array(
                'default'           => 'openid', // username, openid, phone, bind, web
                'type'              => 'string',
                'enum'              => array('username', 'openid', 'phone', 'bind', 'web'),
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'username' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'password' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'code' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'type' => array(
                'default'           => 'weapp',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'nickname' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'avatar' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'uuid' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function callback($request){
        $res = '';
        switch ($request['method']) {
            case 'username':
                $res = $this->login_by_username($request);
                break;
            case 'phone':
                $res = $this->login_by_phone($request);
                break;
            case 'bind':
                $res = $this->bind_user($request);
                break;
            case 'web':
                $res = $this->set_web_login($request);
                break;
            case 'openid':
            default:
                $res = $this->login_by_openid($request);
                break;
        }
        return $res;
    }
    function login_by_username($request){
        $user_login = $request['username'];
        $user_password = $request['password'];
        do_action_ref_array( 'wp_authenticate', array( &$user_login, &$user_password ) );
        $user = wp_authenticate( $user_login, $user_password );
        if ( is_wp_error( $user ) ) {
            return $user;
        }
        $this->do_login($user);
        return $this->do_return($user);
    }
    function login_by_phone($request){
        global $wpdb;
        $options = WWA_options();
        include_once WWA_DIR . 'includes/decrypt.php';

        switch ($request['type']) {
            case 'swan':
                $str = $this->swan($request);
                $openid = isset($str['openid']) && $str['openid'] ? $str['openid'] : '';
                $meta_key = 'social_type_swan';
                $appid = isset($options['swan-key']) ? $options['swan-key'] : '';
                break;
            case 'alipay':
                $str = $this->alipay($request);
                if(is_array($str)) $str['session_key'] = isset($options['alipay-aeskey']) ? $options['alipay-aeskey'] : '';
                $openid = isset($str['user_id']) && $str['user_id'] ? $str['user_id'] : '';
                $meta_key = 'social_type_alipay';
                $appid = isset($options['alipay-appid']) ? $options['alipay-appid'] : '';
                break;
            case 'qq':
                $str = $this->qq($request);
                $openid = isset($str['unionid']) && $str['unionid'] ? $str['unionid'] : $str['openid'];
                $meta_key = isset($str['unionid']) && $str['unionid'] ? 'social_type_qq' : 'social_type_qqxcx';
                $appid = isset($options['qq-appid']) ? $options['qq-appid'] : '';
                break;
            case 'toutiao':
                $str = $this->toutiao($request);
                $openid = isset($str['openid']) && $str['openid'] ? $str['openid'] : '';
                $meta_key = 'social_type_toutiao';
                $appid = isset($options['toutiao-appid']) ? $options['toutiao-appid'] : '';
                break;
            case 'weapp':
            default:
                $str = $this->weapp($request);
                $openid = isset($str['unionid']) && $str['unionid'] ? $str['unionid'] : $str['openid'];
                $meta_key = isset($str['unionid']) && $str['unionid'] ? 'social_type_wechat' : 'social_type_wxxcx';
                $appid = isset($options['appid']) ? $options['appid'] : '';
                break;
        }
        if($str && isset($str['session_key'])){
            $_str = array(
                'session_key' => $str['session_key'],
                'openid' => $openid,
                'meta_key' => $meta_key
            );
            WWA_Session::set($request['type'] . '_' . $request['code'] . '_session_str', json_encode($_str));
            $session_key = $str['session_key'];
        }else{
            $session_str = WWA_Session::get($request['type'] . '_' . $request['code'] . '_session_str');
            $session_str = json_decode($session_str, true);
            $session_key = $session_str['session_key'];
            $openid = $session_str['openid'];
            $meta_key = $session_str['meta_key'];
        }

        if($session_key){
            $pc = new WAA_DataCrypt($appid, $session_key, $request['type']);
            if($request['type'] === 'alipay'){
                $errCode = $pc->decryptData($request['username'], '', $res );
            }else{
                $data = json_decode($request['username'], true);
                $errCode = $pc->decryptData($data['encryptedData'], $data['iv'], $res );
            }

            $res = $res ? json_decode($res, true) : '';
            if ($errCode == 0 && (isset($res['phoneNumber']) || isset($res['mobile']))) { // 手机号信息解密成功
                if(isset($res['phoneNumber'])){
                    $phone = $res['phoneNumber'];
                }else if(isset($res['mobile'])){
                    $phone = $res['mobile'];
                }
                $args = array(
                    'meta_key'     => 'mobile_phone',
                    'meta_value'   => $phone,
                );
                $users = get_users($args);
                if($users && $users[0]->ID ) { // 用户存在
                    $user = $users[0];
                    $this->do_login($user);
                    return $this->do_return($user);
                }else{ // 手机用户不存在
                    // 检查openid是否注册，已经注册则直接绑定关联手机号
                    $blog_prefix = $wpdb->get_blog_prefix();
                    $users = get_users(
                        array(
                            'meta_key' => $blog_prefix . $meta_key,
                            'meta_value' => $openid,
                            'number' => 1
                        )
                    );
                    $user = $users && isset($users[0]) ? $users[0] : '';
                    if(!$user && isset($str['unionid']) && $str['unionid']) {
                    // 用户不存在，并且有unionid，则向下兼容查询openid是否存在
                        $users = get_users(
                            array(
                                'meta_key' => $blog_prefix . 'social_type_'.($request['type']=='qq'?'qq':'wx').'xcx',
                                'meta_value' => $str['openid'],
                                'number' => 1
                            )
                        );
                        $user = $users && isset($users[0]) ? $users[0] : '';
                    }

                    if($user && $user->ID){ // openid用户已存在，绑定手机号码
                        // 检查用户是否缓存用户
                        if($user->user_status=='-1'){
                            wp_update_user(array('ID' => $$user->ID, 'user_status' => '0'));
                        }
                        update_user_meta($user->ID, 'mobile_phone', $phone);

                        $this->do_login($user);
                    }else{ // 新注册
                        $userdata = array(
                            'user_pass' => wp_generate_password(),
                            'user_login' => WWA_unique_username($request['type'] . $openid),
                            'user_email' => $openid . rand(100, 999) . '@' . $request['type'].'.app',
                            'nickname' => '用户' . substr($phone, -4),
                            'display_name' => '用户' . substr($phone, -4)
                        );

                        if(!function_exists('wp_insert_user')){
                            include_once( ABSPATH . WPINC . '/registration.php' );
                        }
                        $user_id = wp_insert_user($userdata);

                        if(!is_wp_error( $user_id )){
                            $role = get_option('default_role');
                            wp_update_user( array( 'ID' => $user_id, 'role' => $role ?: 'contributor' ) );
                            update_user_meta($user_id, 'mobile_phone', $phone);

                            update_user_option($user_id, $meta_key, $openid);
                            // 开放平台使用unionid的话，需要保存social_type_wxxcx字段方便验证用户
                            if($meta_key=='social_type_wechat') update_user_option($user_id, 'social_type_wxxcx', $openid);
                            if($meta_key=='social_type_qq') update_user_option($user_id, 'social_type_qqxcx', $openid);
                            $user = get_user_by( 'ID', $user_id );
                            $this->do_login($user);
                            return $this->do_return($user);
                        }
                    }
                }
            }
        }
        return array('code' => -1);
    }
    function login_by_openid($request) {
        global $wpdb;
        $options = WWA_options();
        $request['type'] = isset($request['type']) ? $request['type'] : 'weapp';
        switch ($request['type']) {
            case 'swan':
                $str = $this->swan($request);
                $openid = isset($str['openid']) && $str['openid'] ? $str['openid'] : '';
                $meta_key = 'social_type_swan';
                $session_key = 'swan_session_key';
                break;
            case 'alipay':
                $str = $this->alipay($request);
                $openid = isset($str['user_id']) && $str['user_id'] ? $str['user_id'] : '';
                $meta_key = 'social_type_alipay';
                $session_key = 'alipay_session_key';
                break;
            case 'qq':
                $str = $this->qq($request);
                $openid = isset($str['unionid']) && $str['unionid'] ? $str['unionid'] : $str['openid'];
                $meta_key = isset($str['unionid']) && $str['unionid'] ? 'social_type_qq' : 'social_type_qqxcx';
                $session_key = 'qq_session_key';
                break;
            case 'toutiao':
                $str = $this->toutiao($request);
                $openid = isset($str['openid']) && $str['openid'] ? $str['openid'] : '';
                $meta_key = 'social_type_toutiao';
                $session_key = 'toutiao_session_key';
                break;
            case 'weapp':
            default:
                $str = $this->weapp($request);
                $openid = isset($str['unionid']) && $str['unionid'] ? $str['unionid'] : $str['openid'];
                $meta_key = isset($str['unionid']) && $str['unionid'] ? 'social_type_wechat' : 'social_type_wxxcx';
                $session_key = 'xcx_session_key';
                break;
        }
        if($openid && $meta_key){
            $blog_prefix = $wpdb->get_blog_prefix();
            $users = get_users(
                array(
                    'meta_key' => $blog_prefix . $meta_key,
                    'meta_value' => $openid,
                    'number' => 1
                )
            );
            $user = $users && isset($users[0]) ? $users[0] : '';
            if(!$user && isset($str['unionid']) && $str['unionid']) {
            // 用户不存在，并且有unionid，则向下兼容查询openid是否存在
                $users = get_users(
                    array(
                        'meta_key' => $blog_prefix . 'social_type_'.($request['type']=='qq'?'qq':'wx').'xcx',
                        'meta_value' => $str['openid'],
                        'number' => 1
                    )
                );
                $user = $users && isset($users[0]) ? $users[0] : '';
            }
            if($user && $user->ID){ // 用户已存在
                update_user_option($user->ID, $session_key, $str['session_key']);
                // 检查用户是否缓存用户
                if($user->user_status=='-1'){
                    // 跳到社交绑定页面
                    $res = array(
                        'display_name' => $user->display_name,
                        'avatar' => $user->social_avatar,
                        'temp_id' => $user->ID
                    );
                    return rest_ensure_response($res);
                }
                // 开放平台使用unionid的话，需要保存social_type_wxxcx字段方便验证用户
                if($meta_key=='social_type_wechat') update_user_option($user->ID, 'social_type_wxxcx', $openid);
                if($meta_key=='social_type_qq') update_user_option($user->ID, 'social_type_qqxcx', $openid);
                // 有unionid返回，并且meta_key是小程序，则为向下兼容处理查询的用户，需保存social_type_wechat的unionid，更新social_type_wxxcx为unionid
                if($meta_key=='social_type_wxxcx' && isset($str['unionid']) && $str['unionid']){
                    update_user_option($user->ID, 'social_type_wechat', $openid);
                    update_user_option($user->ID, 'social_type_wxxcx', $openid);
                }else if($meta_key=='social_type_qqxcx' && isset($str['unionid']) && $str['unionid']){
                    update_user_option($user->ID, 'social_type_qq', $openid);
                    update_user_option($user->ID, 'social_type_qqxcx', $openid);
                }
                if($meta_key=='social_type_wechat' || $meta_key=='social_type_wxxcx'){
                    update_user_option($user->ID, 'social_type_wxxcx_name', $user->display_name);
                    update_user_option($user->ID, 'social_type_wechat_name', $user->display_name);
                }
                $this->do_login($user);
            }else{
                $userdata = array(
                    'openid' => $openid,
                    'nickname' => $request['nickname'],
                    'avatar' => $request['avatar'],
                    'meta_key' => $meta_key
                );

                WWA_Session::set($request['type'] . '_' . $request['code'] . '_session_user', json_encode($userdata));

                // 跳到社交绑定页面
                $res = array(
                    'display_name' => $request['nickname'],
                    'avatar' => $request['avatar'],
                    'code' => $request['code']
                );
                return rest_ensure_response($res);
            }
        }else if(isset($str)){
            return rest_ensure_response($str);
        }

        return $this->do_return($user);
    }

    function set_web_login($request){
        $request['type'] = isset($request['type']) ? $request['type'] : 'weapp';
        if($request['type'] === 'weapp'){
            $str = $this->weapp($request);
            $userdata = array(
                'nickname' => $request['nickname'],
                'display_name' => $request['nickname'],
                'avatar' => $request['avatar'],
                'type' => 'weapp',
                'openid' => $str['openid'],
            );
            if( isset($str['unionid']) ) $userdata['unionid'] = $str['unionid'];
            WWA_Session::set('_' . $request['uuid'], json_encode($userdata));
            $res = array(
                'result' => isset($str['openid']) ? 0 : -1
            );
            return rest_ensure_response($res);
        }
    }

    function bind_user($request){
        $userdata = WWA_Session::get($request['type'] . '_' . $request['code'] . '_session_user');
        $userdata = $userdata ? json_decode($userdata, true) : '';
        $openid = isset($userdata['openid']) ? $userdata['openid'] : '';
        $meta_key = isset($userdata['meta_key']) ? $userdata['meta_key'] : '';

        if($request['username'] === '' && $request['password'] === ''){ // 新用户
            $_user = array(
                'user_pass' => wp_generate_password(),
                'user_login' => $request['type'] . $openid,
                'user_email' => $openid . '@' . $request['type'].'.app',
                'nickname' => $userdata['nickname'],
                'display_name' => $userdata['nickname']
            );

            if(!function_exists('wp_insert_user')){
                include_once( ABSPATH . WPINC . '/registration.php' );
            }
            $user_id = wp_insert_user($_user);

            if(!is_wp_error( $user_id ) || (is_wp_error( $user_id ) && isset($user_id->errors['existing_user_login'])) ){
                if(is_wp_error( $user_id )) {
                    $user = get_user_by( 'email', $openid . '@' . $request['type'].'.app' );
                    if(!$user->ID) return false;
                    $user_id = $user->ID;
                }
                $role = get_option('default_role');
                wp_update_user( array( 'ID' => $user_id, 'role' => $role ?: 'contributor' ) );

                update_user_option($user_id, $meta_key, $openid);
                // 开放平台使用unionid的话，需要保存social_type_wxxcx字段方便验证用户
                if($meta_key=='social_type_wechat') update_user_option($user_id, 'social_type_wxxcx', $openid);
                if($meta_key=='social_type_qq') update_user_option($user_id, 'social_type_qqxcx', $openid);
                $this->set_avatar($user_id, $userdata['avatar']);

                $user = get_user_by( 'ID', $user_id );
                do_action('wpcom_social_new_user', $user_id);
                $this->do_login($user);
                return $this->do_return($user);
            }
        } else { // 绑定已有用户
            $user_login = $request['username'];
            $user_password = $request['password'];
            do_action_ref_array( 'wp_authenticate', array( &$user_login, &$user_password ) );
            $user = wp_authenticate( $user_login, $user_password );
            if ( is_wp_error( $user ) ) {
                return $user;
            }else{ // 登录成功，绑定openid
                update_user_option($user->ID, $meta_key, $openid);
                // 开放平台使用unionid的话，需要保存social_type_wxxcx字段方便验证用户
                if($meta_key=='social_type_wechat') update_user_option($user->ID, 'social_type_wxxcx', $openid);
                if($meta_key=='social_type_qq') update_user_option($user->ID, 'social_type_qqxcx', $openid);
            }
            $this->do_login($user);
            return $this->do_return($user);
        }

        return array('code' => -1);
    }

    function do_login($user){
        $expire = time() + 24 * HOUR_IN_SECONDS;
        $auth_cookie = $user->ID . ':' . wp_hash_password( $user->ID . ':' . $user->user_pass . md5($expire) );
        setcookie('wpcom_rest_token', $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, 1, true);
    }

    function do_return($user){
        $res = array();
        if($user && isset($user->ID)){
            $res['id'] = $user->ID;
            $res['nickname'] = $user->display_name;
            $res['avatar'] = get_avatar_url( $user->ID );
            $res['description'] = $user->description;
            $res['wpcom_metas'] = WWA_rest_user_metas(array('id' => $user->ID), '', '');
        }
        return rest_ensure_response($res);
    }

    function permission_check(){
        return true;
    }

    function weapp($request) {
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['appid']) ? $options['appid'] : '',
            'secret' => isset($options['secret']) ? $options['secret'] : '',
            'js_code' => $request['code'],
            'grant_type' => 'authorization_code'
        );
        $str = WWA_http_request('https://api.weixin.qq.com/sns/jscode2session', $params, 'POST');
        return $str;
    }

    function qq($request) {
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['qq-appid']) ? $options['qq-appid'] : '',
            'secret' => isset($options['qq-secret']) ? $options['qq-secret'] : '',
            'js_code' => $request['code'],
            'grant_type' => 'authorization_code'
        );
        $str = WWA_http_request('https://api.q.qq.com/sns/jscode2session', $params, 'GET');
        return $str;
    }

    function toutiao($request) {
        $options = WWA_options();
        $params = array(
            'appid' => isset($options['toutiao-appid']) ? $options['toutiao-appid'] : '',
            'secret' => isset($options['toutiao-secret']) ? $options['toutiao-secret'] : '',
            'code' => $request['code']
        );
        $str = WWA_http_request('https://developer.toutiao.com/api/apps/jscode2session', $params, 'GET');
        return $str;
    }

    function swan($request) {
        $options = WWA_options();
        $params = array(
            'client_id' => isset($options['swan-key']) ? $options['swan-key'] : '',
            'sk' => isset($options['swan-secret']) ? $options['swan-secret'] : '',
            'code' => $request['code']
        );
        $str = WWA_http_request('https://spapi.baidu.com/oauth/jscode2sessionkey', $params, 'POST');
        return $str;
    }

    function alipay($request) {
        $options = WWA_options();
        $params = array(
            'method' => 'alipay.system.oauth.token',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s"),
            'version' => '1.0',
            'app_id' => isset($options['alipay-appid']) ? $options['alipay-appid'] : '',
            'code' => $request['code'],
            'grant_type' => 'authorization_code'
        );
        $params['sign'] = WWA_alipay_sign(WWA_alipay_getSignContent($params), 'RSA2');
        $str = WWA_http_request('https://openapi.alipay.com/gateway.do', $params, 'POST');
        return isset($str['alipay_system_oauth_token_response']) ? $str['alipay_system_oauth_token_response'] : array();
    }

    function set_avatar($user, $img){
        if(!$user || !$img) return false;

        // 判断是否已经上传头像
        $avatar = get_user_meta( $user, 'wpcom_avatar', 1);
        if ( $avatar != '' ){ //已经设置头像
            return false;
        }

        //Fetch and Store the Image
        $http_options = array(
            'timeout' => 20,
            'redirection' => 20,
            'sslverify' => FALSE
        );

        $get = wp_remote_head( $img, $http_options );
        $response_code = wp_remote_retrieve_response_code ( $get );

        if (200 == $response_code) { // 图片状态需为 200
            $type = $get ['headers'] ['content-type'];

            $mime_to_ext = array(
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/bmp' => 'bmp',
                'image/tiff' => 'tif'
            );

            $file_ext = isset($mime_to_ext[$type]) ? $mime_to_ext[$type] : '';

            $allowed_filetype = array('jpg', 'gif', 'png', 'bmp');

            if (in_array($file_ext, $allowed_filetype)) { // 仅保存图片格式 'jpg','gif','png', 'bmp'
                $http = wp_remote_get($img, $http_options);
                if (!is_wp_error($http) && 200 === $http ['response'] ['code']) { // 请求成功

                    $GLOBALS['image_type'] = 0;

                    $filename = substr(md5($user), 5, 16) . '.' . time() . '.jpg';
                    $mirror = wp_upload_bits( $filename, '', $http ['body'], '1234/06' );

                    if ( !$mirror['error'] ) {
                        $uploads = wp_upload_dir();
                        update_user_meta($user, 'wpcom_avatar', str_replace($uploads['baseurl'], '', $mirror['url']));
                        // 基于wp_generate_attachment_metadata钩子，兼容云储存插件同步
                        $mirror['file'] = str_replace($uploads['basedir']. '/', '', $mirror['file']);
                        apply_filters ( 'wp_generate_attachment_metadata', $mirror, 0 );
                        return $mirror;
                    }
                }
            }
        }
    }
}

class WWA_REST_Login2_Controller extends WWA_REST_Login_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'login';
    }
}