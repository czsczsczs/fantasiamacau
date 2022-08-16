<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Siteinfo_Controller extends WP_REST_Controller{
    public function __construct() {
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'siteinfo';
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
        global $options;
        $res = array();
        $res['icon'] = isset($options['fav']) && is_numeric($options['fav']) ? wp_get_attachment_image_url( $options['fav'], 'full' ) : $options['fav'];
        $res['title'] = get_bloginfo('name');
        $res = rest_ensure_response($res);
        if(!is_wp_error($res)) $res->set_headers(array('Cache-Control' => 'max-age=86400'));
        return $res;
    }
    function permission_check(){
        return true;
    }
}

class WWA_REST_Siteinfo2_Controller extends WWA_REST_Siteinfo_Controller{
    public function __construct() {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'siteinfo';
    }
}