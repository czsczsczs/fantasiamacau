<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Like_Controller extends WP_REST_Controller{
    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'like';
    }
    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'set_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' )
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'id' => array(
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function set_data($request) {
        $user = wp_get_current_user();
        $res = array();
        if(!$user->ID) {
            $res['code'] = -1;
            $res['msg'] = '请登录后操作！';
        } else if(!$request['id']) {
            $res['code'] = -2;
            $res['msg'] = '文章参数错误！';
        } else {
            $post = get_post($request['id']);
            if($post){
                // 用户关注的文章
                $u_favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
                $u_favorites = $u_favorites ? $u_favorites : array();
                // 文章关注人数
                $p_favorite = get_post_meta($post->ID, 'wpcom_favorites', true);
                $p_favorite = $p_favorite ? $p_favorite : 0;
                if(in_array($post->ID, $u_favorites)){ // 用户是否关注本文
                    $res['code'] = 1;
                    $nu_favorites = array();
                    foreach($u_favorites as $uf){
                        if($uf != $post->ID){
                            $nu_favorites[] = $uf;
                        }
                    }
                    $p_favorite -= 1;
                }else{
                    $res['code'] = 0;
                    $u_favorites[] = $post->ID;
                    $nu_favorites = $u_favorites;
                    $p_favorite += 1;
                }
                $p_favorite = $p_favorite<0 ? 0 : $p_favorite;
                $u = update_user_meta($user->ID, 'wpcom_favorites', $nu_favorites);
                update_post_meta($post->ID, 'wpcom_favorites', $p_favorite);
                $res['likes'] = $p_favorite;
            }else{
                $res['code'] = -3;
                $res['msg'] = '文章信息查询失败！';
            }
        }
        return rest_ensure_response($res);
    }
    public function get_data($request) {
        $user = wp_get_current_user();
        $res = array();
        if(!$user->ID) {
            $res['code'] = -1;
        } else if(!$request['id']) {
            $res['code'] = -2;
        } else {
            $post = get_post($request['id']);
            if($post){
                $res['code'] = 0;
                // 用户关注的文章
                $u_favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
                $u_favorites = $u_favorites ? $u_favorites : array();
                // 文章关注人数
                $p_favorite = get_post_meta($post->ID, 'wpcom_favorites', true);
                $p_favorite = $p_favorite ? $p_favorite : 0;
                $res['likes'] = $p_favorite;

                if(in_array($post->ID, $u_favorites)){ // 用户是否关注本文
                    $res['code'] = 1;
                }
            }else{
                $res['code'] = -3;
            }
        }
        return rest_ensure_response($res);
    }
    public function permission_check(){
        if ( get_current_user_id() ) {
            return true;
        } else {
            return new WP_Error( 'rest_user_cannot_view', '请登录后操作！', array( 'status' => rest_authorization_required_code() ) );
        }
    }
}

class WWA_REST_Like2_Controller extends WWA_REST_Like_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'like';
    }
}