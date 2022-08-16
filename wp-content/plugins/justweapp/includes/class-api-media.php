<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Media_Controller extends WP_REST_Attachments_Controller {
    public function __construct() {
        $this->post_type = 'attachment';
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'media';
        $this->meta = new WP_REST_Post_Meta_Fields( $this->post_type);
    }
    public function create_item( $request ) {

        if ( ! empty( $request['post'] ) && in_array( get_post_type( $request['post'] ), array( 'revision', 'attachment' ), true ) ) {
            return new WP_Error( 'rest_invalid_param', __( 'Invalid parent type.' ), array( 'status' => 400 ) );
        }

        // Get the file via $_FILES or raw data.
        $files   = $request->get_file_params();
        $headers = $request->get_headers();

        if ( ! empty( $files ) ) {
            $file = $this->upload_from_file( $files, $headers );
        } else {
            $file = $this->upload_from_data( $request->get_body(), $headers );
        }

        if ( is_wp_error( $file ) ) {
            return $file;
        }

        $name       = wp_basename( $file['file'] );
        $name_parts = pathinfo( $name );
        $name       = trim( substr( $name, 0, -( 1 + strlen( $name_parts['extension'] ) ) ) );

        $url  = $file['url'];
        $type = $file['type'];
        $file = $file['file'];

        // Include image functions to get access to wp_read_image_metadata().
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // use image exif/iptc data for title and caption defaults if possible
        $image_meta = wp_read_image_metadata( $file );

        if ( ! empty( $image_meta ) ) {
            if ( empty( $request['title'] ) && trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
                $request['title'] = $image_meta['title'];
            }

            if ( empty( $request['caption'] ) && trim( $image_meta['caption'] ) ) {
                $request['caption'] = $image_meta['caption'];
            }
        }

        $attachment                 = $this->prepare_item_for_database( $request );
        $attachment->post_mime_type = $type;
        $attachment->guid           = $url;

        $_file = file_get_contents($file);
        if(!WWA_toutiao_img_sec_check(base64_encode($_file))){
            @unlink($file);
            return new WP_Error('img_sec_check_fail', '图片安全检测未通过');
        }

        if ( empty( $attachment->post_title ) ) {
            $attachment->post_title = preg_replace( '/\.[^.]+$/', '', wp_basename( $file ) );
        }

        // $post_parent is inherited from $attachment['post_parent'].
        $id = wp_insert_attachment( wp_slash( (array) $attachment ), $file, 0, true );

        if ( is_wp_error( $id ) ) {
            if ( 'db_update_error' === $id->get_error_code() ) {
                $id->add_data( array( 'status' => 500 ) );
            } else {
                $id->add_data( array( 'status' => 400 ) );
            }
            return $id;
        }

        $attachment = get_post( $id );

        /**
         * Fires after a single attachment is created or updated via the REST API.
         *
         * @since 4.7.0
         *
         * @param WP_Post         $attachment Inserted or updated attachment
         *                                    object.
         * @param WP_REST_Request $request    The request sent to the API.
         * @param bool            $creating   True when creating an attachment, false when updating.
         */
        do_action( 'rest_insert_attachment', $attachment, $request, true );

        // Include admin function to get access to wp_generate_attachment_metadata().
        require_once ABSPATH . 'wp-admin/includes/media.php';

        wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

        if ( isset( $request['alt_text'] ) ) {
            update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
        }

        $fields_update = $this->update_additional_fields_for_object( $attachment, $request );

        if ( is_wp_error( $fields_update ) ) {
            return $fields_update;
        }

        $request->set_param( 'context', 'edit' );

        /**
         * Fires after a single attachment is completely created or updated via the REST API.
         *
         * @since 5.0.0
         *
         * @param WP_Post         $attachment Inserted or updated attachment object.
         * @param WP_REST_Request $request    Request object.
         * @param bool            $creating   True when creating an attachment, false when updating.
         */
        do_action( 'rest_after_insert_attachment', $attachment, $request, true );

        $response = $this->prepare_item_for_response( $attachment, $request );
        $response = rest_ensure_response( $response );
        if(!is_wp_error($response)) $response->set_status( 200 );
        return $response;
    }
}

class WWA_REST_Media2_Controller extends WWA_REST_Media_Controller {
    public function __construct() {
        $this->post_type = 'attachment';
        $this->namespace = 'wp/v2';
        $this->rest_base = 'media2';
        $this->meta = new WP_REST_Post_Meta_Fields( $this->post_type);
    }
}