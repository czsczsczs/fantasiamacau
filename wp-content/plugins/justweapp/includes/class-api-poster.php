<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Poster_Controller extends WP_REST_Controller{

    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'poster';
    }

    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'get_data'),
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
            )
        );
    }
    public function get_data($request) {
        global $post, $options;
        $res = array();
        if($request['id'] && $post = get_post($request['id'])){
            $_options = WWA_options();
            setup_postdata( $post );
            $img_url = WWA_thumbnail_url($post->ID);
            $share_head = $img_url ? $img_url : ( isset($_options['share_thumb']) && $_options['share_thumb'] ? $_options['share_thumb'] : (isset($options['wx_thumb']) ? $options['wx_thumb'] : ''));
            $share_head = is_numeric($share_head) ? wp_get_attachment_image_url( $share_head, 'full' ) : $share_head;
            $share_logo = isset($_options['share_logo']) && $_options['share_logo'] ? $_options['share_logo'] : (isset($options['mobile_share_logo']) && $options['mobile_share_logo'] ? $options['mobile_share_logo'] : $options['logo']);
            $share_logo = is_numeric($share_logo) ? wp_get_attachment_image_url( $share_logo, 'full' ) : $share_logo;
            $excerpt = rtrim( trim( strip_tags( apply_filters( 'the_excerpt', get_the_excerpt() ) ) ), '[原文链接]');
            $excerpt = preg_replace('/\\s+/', ' ', $excerpt );
            $type = isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : 'weapp';
            if($type==='swan'){
                $qrcode = WWA_swan_qrcode('pages/single/index?id='.$request['id']);
                $qrcode = 'data:image/png;base64,' . base64_encode($qrcode);
            }else if($type==='alipay'){
                $qrcode = WWA_alipay_qrcode('pages/single/index', 'id='.$request['id']);
                if($qrcode && $qrcode['qr_code_url']) $qrcode = $this->image_to_base64($qrcode['qr_code_url']);
            }else if($type==='qq'){
                $appid = isset($_options['qq-appid']) ? $_options['qq-appid'] : '';
                $qrcode = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.urlencode('https://m.q.qq.com/a/p/'.$appid.'?s='.urlencode('pages/single/index?id='.$request['id']));
                $qrcode = $this->image_to_base64($qrcode);
            }else if($type==='toutiao'){
                $qrcode = WWA_toutiao_qrcode('pages/single/index?id='.$request['id']);
                $qrcode = 'data:image/png;base64,' . base64_encode($qrcode);
            }else{
                $qrcode = WWA_weapp_wxacode('pages/single/index', '999900'.$request['id']);
                $qrcode = 'data:image/jpeg;base64,' . base64_encode($qrcode);
            }
            if($type!=='alipay' && $type !=='qq'){
                $qrcode = array(
                    'size' => getimagesize($qrcode),
                    'url' => $qrcode
                );
            }
            $res = array(
                'head' => $this->image_to_base64($share_head),
                'logo' => $this->image_to_base64($share_logo),
                'qrcode' => $qrcode,
                'title' => $post->post_title,
                'excerpt' => $excerpt,
                'timestamp' => get_post_time('U', true)
            );
            wp_reset_postdata();
        }
        $res = rest_ensure_response($res);
        if(!is_wp_error($res)) $res->set_headers(array('Cache-Control' => 'max-age=180'));
        return $res;
    }

    function image_to_base64( $image ){
        $http_options = array(
            'timeout' => 20,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36',
            'headers' => array(
                'referer' => home_url()
            )
        );
        if(preg_match('/^\/\//i', $image)) $image = 'http:' . $image;
        $get = wp_remote_get($image, $http_options);
        if (!is_wp_error($get) && 200 === $get ['response'] ['code']) {
            if($get['headers']['content-type'] === 'application/octet-stream'){
                $img_base64 = 'data:image/jpeg;base64,' . base64_encode($get ['body']);
            }else{
                $img_base64 = 'data:' . $get['headers']['content-type'] . ';base64,' . base64_encode($get ['body']);
            }
            return array(
                'size' => getimagesize($img_base64),
                'url' => $img_base64
            );
        }
    }
    function permission_check(){
        return true;
    }
}

class WWA_REST_Poster2_Controller extends WWA_REST_Poster_Controller{
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'poster';
    }
}