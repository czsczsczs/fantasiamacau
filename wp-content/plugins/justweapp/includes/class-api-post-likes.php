<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Post_Likes_Controller extends WP_REST_Controller {
    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'post-likes';
    }
    public function register_routes(){
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_data'),
                'args'                => $this->get_collection_params(),
                'permission_callback' => array( $this, 'permission_check' ),
            ),
            'schema' => array($this, 'get_public_item_schema'),
        ));
    }
    public function get_collection_params() {
        return array(
            'page' => array(
                'default'           => 1,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'default'           => 10,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function get_data($request) {
        $user = wp_get_current_user();
        $posts = array();
        if($user->ID) {
            // 用户关注的文章
            $favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
            $favorites = $favorites ? $favorites : array();
            if($favorites) {
                add_filter('posts_orderby', array($this, 'favorites_posts_orderby'));
                $arg = array(
                    'post_type' => 'post',
                    'posts_per_page' => $request['per_page'],
                    'post__in' => $favorites,
                    'paged' => $request['page'],
                    'ignore_sticky_posts' => 1
                );

                $query_result = new WP_Query($arg);
                global $post;
                while ( $query_result->have_posts() ) {
                    $query_result->the_post();
                    $data = array();
                    $data['id'] = $post->ID;
                    if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
                        $post_date_gmt = get_gmt_from_date( $post->post_date );
                    } else {
                        $post_date_gmt = $post->post_date_gmt;
                    }
                    $data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );
                    $data['title'] = array(
                        'rendered' => get_the_title( $post->ID ),
                    );
                    $excerpt = apply_filters( 'the_excerpt', apply_filters( 'get_the_excerpt', $post->post_excerpt, $post ) );
                    $data['excerpt'] = array(
                        'rendered'  => post_password_required( $post ) ? '' : $excerpt,
                        'protected' => (bool) $post->post_password,
                    );
                    $data['author'] = (int) $post->post_author;
                    $data['sticky'] = is_sticky( $post->ID );
                    $data['type'] = $post->post_type;
                    $data['wpcom_metas'] = WWA_rest_post_metas($data, '', $request);
                    $posts[] = $data;
                }
            }
        }
        return rest_ensure_response($posts);
    }
    public function permission_check(){
        if ( get_current_user_id() ) {
            return true;
        } else {
            return new WP_Error( 'rest_user_cannot_view', '请登录后操作！', array( 'status' => rest_authorization_required_code() ) );
        }
    }
    public function favorites_posts_orderby(){
        global $wpdb, $profile;
        $favorites = get_user_meta( get_current_user_id(), 'wpcom_favorites', true );
        if($favorites)
            return "FIELD(".$wpdb->posts.".ID, ".implode(',', $favorites).") DESC";
    }
    protected function prepare_date_response( $date_gmt, $date = null ) {
        // Use the date if passed.
        if ( isset( $date ) ) {
            return mysql_to_rfc3339( $date );
        }

        // Return null if $date_gmt is empty/zeros.
        if ( '0000-00-00 00:00:00' === $date_gmt ) {
            return null;
        }

        // Return the formatted datetime.
        return mysql_to_rfc3339( $date_gmt );
    }
}

class WWA_REST_Post_Likes2_Controller extends WWA_REST_Post_Likes_Controller {
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'post-likes';
    }
}