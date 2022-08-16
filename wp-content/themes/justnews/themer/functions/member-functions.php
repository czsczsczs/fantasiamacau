<?php
defined( 'ABSPATH' ) || exit;

if(!function_exists('wpcom_sms_code_items')){
    add_filter( 'wpcom_sms_code_items', 'wpcom_sms_code_items' );
    function wpcom_sms_code_items($items){
        $items += array(
            10 => array(
                'type' => 'text',
                'label' => _x('Phone number', 'label', 'wpcom'),
                'icon' => 'phone',
                'name' => 'user_phone',
                'require' => true,
                'validate' => 'phone',
                'placeholder' => _x('Phone number', 'placeholder', 'wpcom'),
            ),
            20 => array(
                'type' => wpcom_member_captcha_type()
            ),
            30 => array(
                'type' => 'smsCode',
                'label' => _x('Verification code', 'label', 'wpcom'),
                'name' => 'sms_code',
                'icon' => 'shield-check',
                'validate' => 'sms_code:user_phone',
                'target' => 'user_phone',
                'require' => true,
                'placeholder' => _x('Please enter your verification code', 'placeholder', 'wpcom')
            )
        );
        return $items;
    }

    add_action( 'wp_ajax_wpcom_send_sms_code', 'wpcom_send_sms_code' );
    add_action( 'wp_ajax_nopriv_wpcom_send_sms_code', 'wpcom_send_sms_code' );
    function wpcom_send_sms_code(){
        $res = array();
        $res['result'] = 1; // 0：发送失败；1：发送成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
        $res['error'] = '';

        $errors = apply_filters( 'wpcom_member_errors', array() );

        $msg = array(
            '0' => __( 'Failed to send', 'wpcom' ),
            '1' => __('Send success', 'wpcom'),
            '-1' => $errors['nonce'],
            '-2' => $errors['captcha_fail'],
            '-3' => $errors['captcha_verify']
        );

        if( (isset($_POST['member_form_accountbind_nonce']) && isset($_POST['user_email'])) ||
            (isset($_POST['member_form_account_change_bind_nonce']) && isset($_POST['type']) && $_POST['type']=='email') ){
            $filter = 'wpcom_email_code_items';
        }else{
            $filter = 'wpcom_sms_code_items';
        }
        $items = apply_filters($filter, array());
        $target = 'user_phone';
        if($items){
            foreach ($items as $item){
                if($item['type']==='smsCode'){
                    $target = $item['target'];
                    break;
                }
            }
        }

        if( isset($_POST['member_form_smscode_nonce'])){ // 找回密码的验证短信
            $_POST[$target] = WPCOM_Session::get('lost_password_phone');
        }

        if( isset($_POST['member_form_account_change_bind_nonce'])){ // 更换绑定安全验证短信
            $user = wp_get_current_user();
            $_POST[$target] = isset($_POST['type']) && $_POST['type']=='phone' ? $user->mobile_phone : $user->user_email;
        }

        $res = wpcom_form_validate( $res, 'send_sms_code', $filter );

        if ($res['result'] == 1) {
            if(is_email($_POST[$target])){
                if(!wpcom_send_email_code($_POST[$target])){
                    $res['result'] = 0;
                    $res['error'] = __('Failed to send email', 'wpcom');
                }
            }else{
                $send = wpcom_sms_code_sender($_POST[$target], isset($_POST['user_phone_country']) ? $_POST['user_phone_country'] : '');
                if($send->result!==0){ // 发送失败
                    $res['result'] = 0;
                    $res['error'] = $send->errmsg;
                }
            }
            if($res['result'] == 1){
                if(isset($_POST['ticket'])){
                    $ticket = $_POST['ticket'];
                    $randstr = $_POST['randstr'];
                    $last_ticket = $ticket . '+' . $randstr;
                }else if(isset($_POST['csessionid'])){
                    $csessionid = $_POST['csessionid'];
                    $token = $_POST['token'];
                    $sig = $_POST['sig'];
                    $scene = $_POST['scene'];
                    $last_ticket = $csessionid . '+' . $token . '+' . $sig . '+' . $scene;
                }else if(isset($_POST['h-captcha-response'])){
                    $last_ticket = $_POST['h-captcha-response'];
                }else if(isset($_POST['g-recaptcha-response'])){
                    $last_ticket = $_POST['g-recaptcha-response'];
                }
                if(isset($last_ticket)) WPCOM_Session::set('last_ticket', $last_ticket);
            }
        }

        if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

        echo json_encode($res);
        exit;
    }

    // 找回密码的短信验证码验证
    add_action( 'wp_ajax_wpcom_smscode', 'wpcom_smscode' );
    add_action( 'wp_ajax_nopriv_wpcom_smscode', 'wpcom_smscode' );
    function wpcom_smscode(){
        $res = array();
        $res['result'] = 1; // 0：验证失败；1：验证成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
        $res['error'] = '';

        $errors = apply_filters( 'wpcom_member_errors', array() );

        $msg = array(
            '0' => __('verification failed', 'wpcom'),
            '1' => __('Verified successfully', 'wpcom'),
            '-1' => $errors['nonce'],
            '-2' => $errors['captcha_fail'],
            '-3' => $errors['captcha_verify']
        );

        $items = apply_filters('wpcom_sms_code_items', array());
        $target = 'user_phone';
        if($items){
            foreach ($items as $item){
                if($item['type']==='smsCode'){
                    $target = $item['target'];
                    break;
                }
            }
        }

        if( isset($_POST['member_form_smscode_nonce'])){ // 找回密码的验证短信
            $_POST[$target] = WPCOM_Session::get('lost_password_phone');
        }

        $res = wpcom_form_validate( $res, 'member_form_smscode', 'wpcom_sms_code_items' );
        $res = apply_filters( 'wpcom_smscode_form_validate', $res );

        if ($res['result'] == 1) {
            WPCOM_Session::delete('', 'code_'.$_POST[$target]);
            WPCOM_Session::delete('', 'lost_password_phone');

            $args = array(
                'meta_key'     => 'mobile_phone',
                'meta_value'   => $_POST[$target],
            );
            $users = get_users($args);
            $user = $users[0];
            $user_login = $user->user_login;
            $key = get_password_reset_key( $user );

            $url = add_query_arg( array(
                'subpage' => 'reset',
                'key' => $key,
                'login' => rawurlencode( $user_login )
            ), wpcom_lostpassword_url() );

            $res['redirect_to'] = $url;
        }

        if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

        echo json_encode($res);
        exit;
    }
}

if(!function_exists('wpcom_sms_code_sender')){
    function wpcom_sms_code_sender( $phone, $nationCode='86' ){
        $options = $GLOBALS['wpmx_options'];
        if($phone){
            $api = isset($options['sms_api']) && $options['sms_api'] ? $options['sms_api'] : 0;
            $code = wpcom_generate_sms_code($phone);
            $nationCode = preg_replace('/^\+/', '', $nationCode);

            /**
             * 可通过此 hook 自定义短信发送接口
             * @param string $phone 手机号码
             * @param string $code 验证码
             * @param string $nationCode 国家代码
             *
             * @return object {result:0, errmsg: '发送成功'} result = 0 表示发送成功，如发送失败可通过 errmsg 返回错误信息
             */
            $sender = apply_filters('wpcom_sms_code_sender', null, $phone, $code, $nationCode);
            if($sender !== null) return $sender;

            if($api=='0'){
                include_once FRAMEWORK_PATH . '/member/qcloudsms/index.php';
                $params = array($code, 10);
                return wpcom_qcloud_sms_sender($phone, $params, $nationCode);
            }else if($api=='1'){
                include_once FRAMEWORK_PATH . '/member/aliyun-php-sdk/dysmsapi.php';
                $sms = new Dysmsapi();
                return $sms->send($phone, $code, $nationCode);
            }
        }
    }
}

add_filter('wpcom_is_login', 'wpcom_add_profile_menus');
function wpcom_add_profile_menus($res){
    if($res && isset($res['menus']) && !empty($res['menus'])){
        $options = $GLOBALS['wpmx_options'];
        $current_user = wp_get_current_user();
        if( isset($options['member_messages']) && $options['member_messages']=='1' && file_exists(FRAMEWORK_PATH . '/includes/messages.php') ) {
            $unread_messages = apply_filters('wpcom_unread_messages_count', 0, $current_user->ID);
            $menu = array(
                'url' => wpcom_subpage_url('messages'),
                'title' => __('Messages', 'wpcom') . ($unread_messages ? '<span class="num-count">'.$unread_messages.'</span>' : '')
            );
            array_splice($res['menus'], -2, 0, array($menu));
            $res['messages'] = $unread_messages;
        }

        if( isset($options['member_notify']) && $options['member_notify']=='1' && file_exists(FRAMEWORK_PATH . '/includes/notifications.php') ) {
            $unread_messages = apply_filters('wpcom_unread_notifications_count', 0, $current_user->ID);
            $menu = array(
                'url' => wpcom_subpage_url('notifications'),
                'title' => __('Notifications', 'wpcom') . ($unread_messages ? '<span class="num-count">'.$unread_messages.'</span>' : '')
            );
            array_splice($res['menus'], -2, 0, array($menu));
            $res['notifications'] = $unread_messages;
        }
    }
    return $res;
}

if(!function_exists('wpcom_get_scan_login_info')){
    function wpcom_get_scan_login_info(){
        $options = $GLOBALS['wpmx_options'];
        if(wp_is_mobile()) return false;
        $scan_login = isset($options['scan_login']) && $options['scan_login'] ? $options['scan_login'] : 0;
        if($scan_login){
            $socials = apply_filters( 'wpcom_socials', array() );
            $method = '';
            foreach ($socials as $s){
                if(isset($s['icon']) && $s['icon'] === 'wechat'){
                    $method = $s;
                    break;
                }
            }
            if($method){
                return array(
                    'type' => $scan_login,
                    'api' => $method
                );
            }
        }
        return false;
    }
}

add_filter('wpmx_admin_options', 'wpcom_add_member_options', 10);
function wpcom_add_member_options($options){
    if($options){
        $_options = array();
        $type = apply_filters( 'wpcom_member_show_profile', true );
        foreach($options as $option){
            if($type && isset($option['name']) && $option['name'] === '_member_page'){
                $_options[]= array(
                    'name' => 'member_group_on',
                    'title' => '开启用户分组',
                    'desc' => '是否启用用户分组功能',
                    'std' => '1',
                    'type' => 't'
                );
                $_options[]= array(
                    'name' => 'member_group',
                    'filter' => 'member_group_on:1',
                    'title' => '默认分组',
                    'desc' => '用户注册后默认的用户分组，用户分组可以到后台【用户>用户分组】下创建管理',
                    'type' => 'cs',
                    'tax' => 'user-groups'
                );
                if(file_exists(FRAMEWORK_PATH . '/includes/follow.php')){
                    $_options[] = array(
                        'name' => 'member_follow',
                        'title' => '用户关注',
                        'std' => '1',
                        'type' => 't'
                    );
                }
                if(file_exists(FRAMEWORK_PATH . '/includes/messages.php')){
                    $_options[] = array(
                        'name' => 'member_messages',
                        'title' => '私信功能',
                        'std' => '1',
                        'type' => 't'
                    );
                }
                if(file_exists(FRAMEWORK_PATH . '/includes/notifications.php')){
                    $_options[] = array(
                        'name' => 'member_notify',
                        'title' => '系统通知',
                        'std' => '1',
                        'type' => 't'
                    );
                }
                if(file_exists(FRAMEWORK_PATH . '/includes/user-card.php')){
                    $_options[] = array(
                        'name' => 'user_card',
                        'title' => '用户资料卡',
                        'desc' => '鼠标移入用户昵称可弹出资料卡',
                        'std' => '1',
                        'type' => 't'
                    );
                }
                $_options[] = $option;
            }else if(isset($option['name']) && $option['name'] === 'login_redirect'){
                $_options[] = array(
                    'name' => 'member_login_bg',
                    'title' => '注册登录背景',
                    'desc' => '注册登录页面的背景图片',
                    'type' => 'u'
                );
                $_options[] = $option;
                $_options = array_merge($_options, array(
                    array(
                        'title' => '页头用户下拉菜单',
                        'desc' => '页面公共头部的用户下拉菜单设置',
                        'type' => 'tt'
                    ),
                    array(
                        'type' => "rp",
                        'title' => '链接',
                        'options' => array(
                            array(
                                'name' => 'profile_menu_title',
                                'title' => '链接文字',
                                'desc' => '菜单选项的链接标题'
                            ),
                            array(
                                'name' => 'profile_menu_url',
                                'title' => '链接地址',
                                'desc' => '菜单选项的链接地址'
                            )
                        )
                    ),
                    array(
                        'title' => '手机注册',
                        'type' => 'tt'
                    ),
                    array(
                        'name' => 'enable_phone',
                        'title' => '开启手机注册',
                        'type' => 't'
                    ),
                    array(
                        'type' => 'w',
                        'filter' => 'enable_phone:1',
                        'options' => array(
                            array(
                                'name' => 'sms_login',
                                'title' => '手机快捷登录',
                                'desc' => '使用手机短信验证码快捷登录',
                                'type' => 'r',
                                'ux' => 1,
                                'std' => '0',
                                'options' => array(
                                    '0' => '不启用',
                                    '1' => '启用',
                                    '2' => '启用并优先使用快捷登录'
                                )
                            ),
                            array(
                                'name' => 'sms_api',
                                'title' => '短信接口',
                                'type' => 'r',
                                'ux' => 1,
                                'std' => '0',
                                'options' => array(
                                    '0' => '腾讯云',
                                    '1' => '阿里云'
                                )
                            ),
                            array(
                                'type' => 'w',
                                'filter' => 'sms_api:0',
                                'options' => array(
                                    array(
                                        'title' => '腾讯云短信接口',
                                        'type' => 'tt'
                                    ),
                                    array(
                                        'name' => 'qcloud_sms_appid',
                                        'title' => 'AppID'
                                    ),
                                    array(
                                        'name' => 'qcloud_sms_appkey',
                                        'title' => 'App Key'
                                    ),
                                    array(
                                        'name' => 'qcloud_sms_tid',
                                        'title' => '短信模板ID',
                                        'desc' => '短信正文模板的ID，短信正文有两个参数，分别是<b>{1}：验证码，{2}：验证码有效分钟数</b>，如果参数错误会提示<b>package format error, template params error</b>'
                                    ),
                                    array(
                                        'name' => 'qcloud_sms_sign',
                                        'title' => '短信签名'
                                    )
                                )
                            ),
                            array(
                                'type' => 'w',
                                'filter' => 'sms_api:1',
                                'options' => array(
                                    array(
                                        'title' => '阿里云短信接口',
                                        'type' => 'tt'
                                    ),
                                    array(
                                        'name' => 'aliyun_sms_keyid',
                                        'title' => 'AccessKey Id'
                                    ),
                                    array(
                                        'name' => 'aliyun_sms_secret',
                                        'title' => 'AccessKey Secret'
                                    ),
                                    array(
                                        'name' => 'aliyun_sms_tcode',
                                        'title' => '模版CODE',
                                        'desc' => '短信模版内容有1个参数：<b>${code}</b>，表示验证码'
                                    ),
                                    array(
                                        'name' => 'aliyun_sms_sign',
                                        'title' => '签名名称'
                                    )
                                )
                            )
                        )
                    ),
                ));
            }else if(isset($option['filter']) && $option['filter'] === 'social_login_on:1'){
                $option['options'][] = array(
                    'name' => 'scan_login',
                    'title' => '扫码登录',
                    'std' => '0',
                    'type' => 'r',
                    'ux' => 1,
                    'desc' => '登录框右上角可切换扫码登录，此功能基于<b>微信登录</b>实现，请确保社交登录方式有微信登录接口',
                    'options' => array(
                        '0' => '不开启',
                        '1' => '开启',
                        '2' => '开启并设为默认'
                    )
                );
                foreach($option['options'] as $x => $o){
                    if(isset($o['name']) && $o['name'] === '_social_login' && isset($option['options'][$x]['options'])){
                        $option['options'][$x]['options'][] = array(
                            'type' => 'w',
                            'filter' => 'sl_type:wechat2',
                            'name'=> 'sl_w',
                            'options' => array(
                                array(
                                    'name' => 'sl_wechat_follow',
                                    'title' => '扫码关注登录',
                                    'desc' => '需认证服务号，开启将使用扫码关注公众号的方式登录；<b>此方式无法获取昵称、头像，请谨慎开启</b>',
                                    'type' => 't'
                                ),
                                array(
                                    'name' => 'sl_wechat2_aeskey',
                                    'filter' => 'sl_wechat_follow:1',
                                    'title' => '消息加解密密钥(EncodingAESKey)',
                                    'desc' => '可在公众号后台基本配置>服务器配置下设置获取'
                                ),
                                array(
                                    'name' => 'sl_wechat2_welc',
                                    'filter' => 'sl_wechat_follow:1',
                                    'title' => '公众号端登录提示',
                                    'type' => 'ta',
                                    'desc' => '如需添加链接请使用a标签添加，例如：&lt;a href="链接地址">链接文本&lt;/a>'
                                )
                            )
                        );
                    }
                }
                $_options[] = $option;
            }else{
                $_options[] = $option;
            }
        }
        $options = $_options;
    }
    return $options;
}

add_filter( 'wpcom_socials', 'wpcom_socials_wechat_follow' );
function wpcom_socials_wechat_follow( $social ){
    $options = $GLOBALS['wpmx_options'];
    if(!empty($social )){
        $_social = array();
        foreach($social as $item){
            if($item && isset($item['name']) && $item['name'] === 'wechat2'){
                $i = $item['index'];
                if(isset($options['sl_wechat_follow']) && isset($options['sl_wechat_follow'][$i]) && $options['sl_wechat_follow'][$i]){
                    $item['follow'] = 1;
                    $item['aeskey'] = $options['sl_wechat2_aeskey'][$i];
                    $item['welcome'] = isset($options['sl_wechat2_welc'][$i]) ? $options['sl_wechat2_welc'][$i] : '';
                }
            }
            $_social[] = $item;
        }
        $social = $_social;
    }
    return $social;
}