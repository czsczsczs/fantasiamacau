<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Posts_Controller extends WP_REST_Posts_Controller{
    public function __construct($post_type){
        parent::__construct($post_type);
        $this->namespace = 'wpcom/v1';
        add_filter('rest_post_query', array($this, 'sitemap_query'), 10, 2);
    }
    public function get_items( $request ) {
        $response = parent::get_items( $request );
        $cache = '600';
        if($this->post_type === 'kuaixun') {
            $cache = '60';
        }else if($this->post_type === 'qa_post'){
            $cache = '120';
        }
        if(!is_wp_error($response) && !isset($request['user_id'])) {
            $headers = $response->get_headers();
            $headers = $headers ?: array();
            $headers['Cache-Control'] = 'max-age='.$cache;
            $response->set_headers($headers);
        }
        return $response;
    }
    public function get_item( $request ) {
        $response = parent::get_item( $request );
        if(!is_wp_error($response) && !isset($request['user_id'])) $response->set_headers(array('Cache-Control' => 'max-age=86400'));
        return $response;
    }
    public function sitemap_query($args, $request){
        if(isset($request['sitemap']) && $request['sitemap']){
            $args['post_type'] = array('post', 'qa_post');
        }
        return $args;
    }
}