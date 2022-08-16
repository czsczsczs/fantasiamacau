<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Comments_Controller extends WP_REST_Comments_Controller{
    public function __construct() {
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'comments';
        $this->meta = new WP_REST_Comment_Meta_Fields();
    }
    public function get_items( $request ) {
        $response = parent::get_items( $request );
        if(!is_wp_error($response) && !isset($request['user_id'])) $response->set_headers(array('Cache-Control' => 'max-age=600'));
        return $response;
    }
    public function get_item( $request ) {
        $response = parent::get_item( $request );
        if(!is_wp_error($response) && !isset($request['user_id'])) $response->set_headers(array('Cache-Control' => 'max-age=3600'));
        return $response;
    }
    public function get_items_permissions_check( $request ) {
        $type = isset($_SERVER['AppType']) ? $_SERVER['AppType'] : (isset($_SERVER['HTTP_APPTYPE']) ? $_SERVER['HTTP_APPTYPE'] : '');
        if(!$type) {
            return new WP_Error( 'rest_forbidden_param', '没有权限', array( 'status' => rest_authorization_required_code() ) );
        }
        if ( ! empty( $request['post'] ) ) {
            foreach ( (array) $request['post'] as $post_id ) {
                $post = get_post( $post_id );

                if ( ! empty( $post_id ) && $post && ! $this->check_read_post_permission( $post, $request ) ) {
                    return new WP_Error(
                        'rest_cannot_read_post',
                        __( 'Sorry, you are not allowed to read the post for this comment.' ),
                        array( 'status' => rest_authorization_required_code() )
                    );
                } elseif ( 0 === $post_id && ! current_user_can( 'moderate_comments' ) ) {
                    return new WP_Error(
                        'rest_cannot_read',
                        __( 'Sorry, you are not allowed to read comments without a post.' ),
                        array( 'status' => rest_authorization_required_code() )
                    );
                }
            }
        }

        if ( ! empty( $request['context'] ) && 'edit' === $request['context'] && ! current_user_can( 'moderate_comments' ) ) {
            return new WP_Error(
                'rest_forbidden_context',
                __( 'Sorry, you are not allowed to edit comments.' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            $protected_params = array('author_exclude', 'author_email', 'status' );
            $forbidden_params = array();
            $user = wp_get_current_user();

            foreach ( $protected_params as $param ) {
                if ( 'status' === $param ) {
                    if(!($user && $user->ID && $request['author'] && $user->ID == $request['author'][0])){
                        if ( 'approve' !== $request[ $param ] ) {
                            $forbidden_params[] = $param;
                        }
                    }
                } elseif ( ! empty( $request[ $param ] ) ) {
                    $forbidden_params[] = $param;
                }
            }

            if ( ! empty( $forbidden_params ) ) {
                return new WP_Error(
                    'rest_forbidden_param',
                    /* translators: %s: List of forbidden parameters. */
                    sprintf( __( 'Query parameter not permitted: %s' ), implode( ', ', $forbidden_params ) ),
                    array( 'status' => rest_authorization_required_code() )
                );
            }
        }

        return true;
    }
}

class WWA_REST_MYComments_Controller extends WWA_REST_Comments_Controller{
    public function __construct() {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'mycomments';
        $this->meta = new WP_REST_Comment_Meta_Fields();
    }
}