<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Terms_Controller extends WP_REST_Terms_Controller{
    public function __construct($taxonomy){
        parent::__construct($taxonomy);
        $this->namespace = 'wpcom/v1';
    }
    public function get_items( $request ) {
        $response = parent::get_items( $request );
        if(!is_wp_error($response)) $response->set_headers(array('Cache-Control' => 'max-age=3600'));
        return $response;
    }
    public function get_item( $request ) {
        $response = parent::get_item( $request );
        if(!is_wp_error($response)) $response->set_headers(array('Cache-Control' => 'max-age=600'));
        return $response;
    }
}