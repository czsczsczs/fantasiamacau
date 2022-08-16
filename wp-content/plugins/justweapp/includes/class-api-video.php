<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Video_Controller extends WP_REST_Controller{

    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'video';
    }

    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' )
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'type' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'url' => array(
                'default'           => '',
                'type'              => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function get_data($request) {
        $res = array();
        if(isset($request['type']) && isset($request['url']) && $request['type'] === 'bilibili' && $request['url']){
            preg_match('/player\.bilibili\.com.*bvid=([^&]+)&.*&?cid=([^&]+)/i', $request['url'], $match);
            if($match && isset($match[1]) && isset($match[2])){
                $url = 'https://api.bilibili.com/x/player/playurl?cid='.$match[2].'&bvid='.$match[1].'&qn=16&type=mp4&otype=json&platform=html5';
                $req = WWA_http_request($url);
                if(isset($req['data']) && isset($req['data']['durl']) && isset($req['data']['durl'][0])){
                    $res['src'] = $req['data']['durl'][0]['url'];
                }
            }else{
                preg_match('/player\.bilibili\.com.*cid=([^&]+)&.*&?aid=([^&]+)/i', $request['url'], $match2);
                if($match2 && isset($match2[1]) && isset($match2[2])){
                    $url = 'https://api.bilibili.com/x/player/playurl?avid='.$match2[2].'&cid='.$match2[1].'&qn=16&type=mp4&otype=json&platform=html5';
                    $req = WWA_http_request($url);
                    if(isset($req['data']) && isset($req['data']['durl']) && isset($req['data']['durl'][0])){
                        $res['src'] = $req['data']['durl'][0]['url'];
                    }
                }
            }
        }
        $res = rest_ensure_response($res);
        if(!is_wp_error($res)) $res->set_headers(array('Cache-Control' => 'max-age=300'));
        return $res;
    }
    function permission_check(){
        return true;
    }
}

class WWA_REST_Video2_Controller extends WWA_REST_Video_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'video';
    }
}