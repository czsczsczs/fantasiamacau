<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Zan_Controller extends WP_REST_Controller{

    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'dianzan';
    }

    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
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
                'validate_callback' => 'rest_validate_request_arg'
            ),
            'liked' => array(
                'default'           => false,
                'type'              => 'boolean',
                'validate_callback' => 'rest_validate_request_arg'
            )
        );
    }
    public function set_data($request) {
        $res = array();
        if(!$request['id']) {
            $res['code'] = -2;
            $res['msg'] = '文章参数错误！';
        } else {
            $post = get_post($request['id']);
            if($post){
                $res['code'] = 0;
                $likes = get_post_meta($post->ID, 'wpcom_likes', true);
                $likes = $likes ? $likes : 0;
                if($request['liked'] && $likes > 0) {
                    $likes = $likes - 1;
                }else if(!$request['liked']){
                    $likes = $likes + 1;
                }
                $res['likes'] = $likes;
                // 数据库增加一个喜欢数量
                update_post_meta( $post->ID, 'wpcom_likes', $res['likes'] );
            }else{
                $res['code'] = -3;
                $res['msg'] = '文章信息查询失败！';
            }
        }
        return rest_ensure_response($res);
    }
    function permission_check(){
        return true;
    }
}

class WWA_REST_Zan2_Controller extends WWA_REST_Zan_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'dianzan';
    }
}