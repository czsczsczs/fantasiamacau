<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Follow_Controller extends WP_REST_Users_Controller{
    protected $meta;
    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'follow';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_items'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'no_check' )
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'follow'),
                'args'                => $this->follow_params(),
                'permission_callback' => array( $this, 'permission_check' )
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    function get_collection_params() {
        return array(
            'user' => array(
                'required'          => true,
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'type' => array(
                'required'          => false,
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'page' => array(
                'required'          => false,
                'default'           => 1,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'required'          => false,
                'default'           => 10,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    function follow_params(){
        return array(
            'user' => array(
                'required'          => true,
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    function get_items($request) {
        global $_wwa_follow;
        $this->meta = new WP_REST_User_Meta_Fields();
        $users = array();
        if($request['type']){
            $_users = $_wwa_follow->get_followers($request['user'], $request['per_page'], $request['page']);
        }else{
            $_users = $_wwa_follow->get_follows($request['user'], $request['per_page'], $request['page']);
        }
        if($_users){
            foreach ( $_users as $user ) {
                $data    = $this->prepare_item_for_response( $user, $request );
                $users[] = $this->prepare_response_for_collection( $data );
            }
        }
        $response = rest_ensure_response( $users );
        return $response;
    }
    function follow($request){
        global $_wwa_follow;
        $res = array(
            'result' => 0
        );
        $uid = get_current_user_id();
        if($uid === $request['user']){ // 自己不能关注自己
            $res['result'] = -2;
        }else{
            if($_wwa_follow->is_followed($request['user'])){
                $action = $_wwa_follow->unfollow($request['user']);
            }else{
                $action = $_wwa_follow->follow($request['user']);
            }
            if($action){
                $is_follow = $_wwa_follow->is_followed($request['user']);
                $is_follow = $is_follow ? 1 : 0;
                if($uid && $is_follow===1 && $_wwa_follow->is_followed($uid, $request['user'])){ // 互相关注
                    $is_follow = 2;
                }
                $res['is_follow'] = $is_follow;
            }else{
                $res['result'] = -1;
            }
        }
        return rest_ensure_response($res);
    }
    function no_check(){
        return true;
    }
    function permission_check(){
        if ( get_current_user_id() ) {
            return true;
        } else {
            return new WP_Error( 'rest_user_cannot_view', '请登录后操作！', array( 'status' => rest_authorization_required_code() ) );
        }
    }
}

class WWA_REST_Follow2_Controller extends WWA_REST_Follow_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'follow';
    }
}