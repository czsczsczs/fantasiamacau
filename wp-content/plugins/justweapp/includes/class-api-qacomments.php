<?php
defined( 'ABSPATH' ) || exit;

class WP_REST_QAComment_Meta_Fields extends WP_REST_Comment_Meta_Fields {
    public function get_rest_field_type() {
        return 'qacomment';
    }
}

class WWA_REST_QAComments_Controller extends WP_REST_Comments_Controller{
    public function __construct() {
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'qacomments';
        $this->meta = new WP_REST_QAComment_Meta_Fields();
    }
    public function get_item_schema() {
        $schema = parent::get_item_schema();
        $schema['title'] = 'qacomment';
        return $schema;
    }
    public function get_items_permissions_check( $request ) {
        if ( ! empty( $request['post'] ) ) {
            foreach ( (array) $request['post'] as $post_id ) {
                $post = get_post( $post_id );

                if ( ! empty( $post_id ) && $post && ! $this->check_read_post_permission( $post, $request ) ) {
                    return new WP_Error( 'rest_cannot_read_post', __( 'Sorry, you are not allowed to read the post for this comment.' ), array( 'status' => rest_authorization_required_code() ) );
                } elseif ( 0 === $post_id && ! current_user_can( 'moderate_comments' ) ) {
                    return new WP_Error( 'rest_cannot_read', __( 'Sorry, you are not allowed to read comments without a post.' ), array( 'status' => rest_authorization_required_code() ) );
                }
            }
        }

        if ( ! empty( $request['context'] ) && 'edit' === $request['context'] && ! current_user_can( 'moderate_comments' ) ) {
            return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit comments.' ), array( 'status' => rest_authorization_required_code() ) );
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            $protected_params = array( 'author_exclude', 'author_email', 'status' );
            $forbidden_params = array();

            foreach ( $protected_params as $param ) {
                if ( 'status' === $param ) {
                    if ( 'approve' !== $request[ $param ] ) {
                        $forbidden_params[] = $param;
                    }
                } elseif ( 'type' === $param ) {
                    if ( 'comment' !== $request[ $param ] ) {
                        $forbidden_params[] = $param;
                    }
                } elseif ( ! empty( $request[ $param ] ) ) {
                    $forbidden_params[] = $param;
                }
            }

            if ( ! empty( $forbidden_params ) ) {
                return new WP_Error( 'rest_forbidden_param', sprintf( __( 'Query parameter not permitted: %s' ), implode( ', ', $forbidden_params ) ), array( 'status' => rest_authorization_required_code() ) );
            }
        }

        return true;
    }

    public function create_item( $request ) {
        if ( ! empty( $request['id'] ) ) {
            return new WP_Error( 'rest_comment_exists', __( 'Cannot create existing comment.' ), array( 'status' => 400 ) );
        }

        // Do not allow comments to be created with a non-default type.
        if ( ! empty( $request['type'] ) && 'answer' !== $request['type'] && 'qa_comment' !== $request['type'] ) {
            return new WP_Error( 'rest_invalid_comment_type', __( 'Cannot create a comment with that type.' ), array( 'status' => 400 ) );
        }

        $prepared_comment = $this->prepare_item_for_database( $request );
        if ( is_wp_error( $prepared_comment ) ) {
            return $prepared_comment;
        }

        $prepared_comment['comment_type'] = $request['type'];

        /*
         * Do not allow a comment to be created with missing or empty
         * comment_content. See wp_handle_comment_submission().
         */
        if ( empty( $prepared_comment['comment_content'] ) ) {
            return new WP_Error( 'rest_comment_content_invalid', __( 'Invalid comment content.' ), array( 'status' => 400 ) );
        }

        // Setting remaining values before wp_insert_comment so we can use wp_allow_comment().
        if ( ! isset( $prepared_comment['comment_date_gmt'] ) ) {
            $prepared_comment['comment_date_gmt'] = current_time( 'mysql', true );
        }

        // Set author data if the user's logged in.
        $missing_author = empty( $prepared_comment['user_id'] )
            && empty( $prepared_comment['comment_author'] )
            && empty( $prepared_comment['comment_author_email'] )
            && empty( $prepared_comment['comment_author_url'] );

        if ( is_user_logged_in() && $missing_author ) {
            $user = wp_get_current_user();

            $prepared_comment['user_id']              = $user->ID;
            $prepared_comment['comment_author']       = $user->display_name;
            $prepared_comment['comment_author_email'] = $user->user_email;
            $prepared_comment['comment_author_url']   = $user->user_url;
        }

        // Honor the discussion setting that requires a name and email address of the comment author.
        if ( get_option( 'require_name_email' ) ) {
            if ( empty( $prepared_comment['comment_author'] ) || empty( $prepared_comment['comment_author_email'] ) ) {
                return new WP_Error( 'rest_comment_author_data_required', __( 'Creating a comment requires valid author name and email values.' ), array( 'status' => 400 ) );
            }
        }

        if ( ! isset( $prepared_comment['comment_author_email'] ) ) {
            $prepared_comment['comment_author_email'] = '';
        }

        if ( ! isset( $prepared_comment['comment_author_url'] ) ) {
            $prepared_comment['comment_author_url'] = '';
        }

        if ( ! isset( $prepared_comment['comment_agent'] ) ) {
            $prepared_comment['comment_agent'] = '';
        }

        $check_comment_lengths = wp_check_comment_data_max_lengths( $prepared_comment );
        if ( is_wp_error( $check_comment_lengths ) ) {
            $error_code = $check_comment_lengths->get_error_code();
            return new WP_Error( $error_code, __( 'Comment field exceeds maximum length allowed.' ), array( 'status' => 400 ) );
        }

        $prepared_comment['comment_approved'] = wp_allow_comment( $prepared_comment, true );

        if ( is_wp_error( $prepared_comment['comment_approved'] ) ) {
            $error_code    = $prepared_comment['comment_approved']->get_error_code();
            $error_message = $prepared_comment['comment_approved']->get_error_message();

            if ( 'comment_duplicate' === $error_code ) {
                return new WP_Error( $error_code, $error_message, array( 'status' => 409 ) );
            }

            if ( 'comment_flood' === $error_code ) {
                return new WP_Error( $error_code, $error_message, array( 'status' => 400 ) );
            }

            return $prepared_comment['comment_approved'];
        }

        /**
         * Filters a comment before it is inserted via the REST API.
         *
         * Allows modification of the comment right before it is inserted via wp_insert_comment().
         * Returning a WP_Error value from the filter will shortcircuit insertion and allow
         * skipping further processing.
         *
         * @since 4.7.0
         * @since 4.8.0 `$prepared_comment` can now be a WP_Error to shortcircuit insertion.
         *
         * @param array|WP_Error  $prepared_comment The prepared comment data for wp_insert_comment().
         * @param WP_REST_Request $request          Request used to insert the comment.
         */
        $prepared_comment = apply_filters( 'rest_pre_insert_comment', $prepared_comment, $request );
        if ( is_wp_error( $prepared_comment ) ) {
            return $prepared_comment;
        }

        $comment_id = wp_insert_comment( wp_filter_comment( wp_slash( (array) $prepared_comment ) ) );

        if ( ! $comment_id ) {
            return new WP_Error( 'rest_comment_failed_create', __( 'Creating comment failed.' ), array( 'status' => 500 ) );
        }

        if ( isset( $request['status'] ) ) {
            $this->handle_status_param( $request['status'], $comment_id );
        }

        $comment = get_comment( $comment_id );

        /**
         * Fires after a comment is created or updated via the REST API.
         *
         * @since 4.7.0
         *
         * @param WP_Comment      $comment  Inserted or updated comment object.
         * @param WP_REST_Request $request  Request object.
         * @param bool            $creating True when creating a comment, false
         *                                  when updating.
         */
        do_action( 'rest_insert_comment', $comment, $request, true );

        $schema = $this->get_item_schema();

        if ( ! empty( $schema['properties']['meta'] ) && isset( $request['meta'] ) ) {
            $meta_update = $this->meta->update_value( $request['meta'], $comment_id );

            if ( is_wp_error( $meta_update ) ) {
                return $meta_update;
            }
        }

        $fields_update = $this->update_additional_fields_for_object( $comment, $request );

        if ( is_wp_error( $fields_update ) ) {
            return $fields_update;
        }

        $context = current_user_can( 'moderate_comments' ) ? 'edit' : 'view';
        $request->set_param( 'context', $context );

        /**
         * Fires completely after a comment is created or updated via the REST API.
         *
         * @since 5.0.0
         *
         * @param WP_Comment      $comment  Inserted or updated comment object.
         * @param WP_REST_Request $request  Request object.
         * @param bool            $creating True when creating a comment, false
         *                                  when updating.
         */
        do_action( 'rest_after_insert_comment', $comment, $request, true );

        $response = $this->prepare_item_for_response( $comment, $request );
        $response = rest_ensure_response( $response );

        if(!is_wp_error($response)) $response->set_status( 201 );
        if(!is_wp_error($response)) $response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $comment_id ) ) );

        return $response;
    }
}

class WWA_REST_QAComments2_Controller extends WWA_REST_QAComments_Controller{
    public function __construct() {
        $this->namespace = 'wp/v2';
        $this->rest_base = 'qacomments';
        $this->meta = new WP_REST_QAComment_Meta_Fields();
    }
}