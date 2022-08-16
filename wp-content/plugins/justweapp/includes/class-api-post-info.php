<?php
defined( 'ABSPATH' ) || exit;

class WWA_REST_Post_Info_Controller extends WP_REST_Controller {
    public function __construct(){
        $this->namespace = 'wpcom/v1';
        $this->rest_base = 'post-info';
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
            'id' => array(
                'default'           => 0,
                'type'              => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            )
        );
    }
    public function get_data($request) {
        global $post;
        $wwa_options = WWA_options();
        $user = wp_get_current_user();
        $post_id = $request['id'];
        $post = get_post($post_id);

        $metas = array();
        $metas['is_like'] = 0;

        // 用户关注的文章
        if($user->ID){
            $u_favorites = get_user_meta($user->ID, 'wpcom_favorites', true);
            $u_favorites = $u_favorites ? $u_favorites : array();

            if(in_array($post_id, $u_favorites)){ // 用户是否关注本文
                $metas['is_like'] = 1;
            }
        }


        // 上下篇文章
        $prenext = !isset($wwa_options['prenext']) || (isset($wwa_options['prenext']) && $wwa_options['prenext']=='1');
        $pre = $prenext ? get_previous_post(true) : 0;
        $next = $prenext ? get_next_post(true) : 0;
        $metas['previous'] = $pre ? array(
            'id' => $pre->ID,
            'title' => get_the_title($pre->ID),
            'thumb' => WWA_thumbnail_url($pre->ID, 'post-thumbnail'),
            'date_gmt' => $pre->post_date_gmt
        ) : array();
        $metas['next'] = $next ? array(
            'id' => $next->ID,
            'title' => get_the_title($next->ID),
            'thumb' => WWA_thumbnail_url($next->ID, 'post-thumbnail'),
            'date_gmt' => $next->post_date_gmt
        ) : array();

        // 相关文章
        $related = WWA_get_related_posts($post_id, isset($wwa_options['related_num']) ? $wwa_options['related_num'] : 5);
        if( $related ) {
            $metas['related'] = array();
            global $post, $options;
            foreach ( $related as $post ) { setup_postdata($post);
                $arr = array(
                    'id' => $post->ID,
                    'title' => get_the_title(),
                    'thumb' => WWA_thumbnail_url($post->ID, 'post-thumbnail'),
                    'date_gmt' => $post->post_date_gmt,
                    'comments' => $post->comment_count,
                    'excerpt' => get_the_excerpt()
                );

                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', apply_filters( 'the_content', $post->post_content ), $matches);
                $multimage = get_post_meta($post_id, 'wpcom_item_style', true);
                $multimage = $multimage=='' ? (isset($wwa_options['list_multimage']) ? $wwa_options['list_multimage'] : '') : $multimage;

                // 小程序未指定列表风格，则获取主题的设置
                if($multimage=='' && function_exists('wpcom_setup')){
                    $multimage = get_post_meta($post_id, 'wpcom_multimage', true);
                    $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;
                }

                $arr['item_style'] = $multimage;
                if(isset($matches[1]) && isset($matches[1][3])  && ($multimage==1||$multimage==3)) {
                    $arr['thumbs'] = array_slice($matches[1], 0, 5);
                }

                $category = get_the_category();
                $cat = $category ? $category[0] : '';
                if($cat) {
                    $arr['cat'] = array(
                        'id' => $cat->cat_ID,
                        'name' => $cat->name
                    );
                }

                if(function_exists('the_views')) {
                    $views = get_post_meta($post->ID, 'views', true);
                    $views = $views ? $views : 1;
                    $arr['views'] = $views;
                }
                $metas['related'][] = $arr;
            }
        }

        // 点赞
        $likes = get_post_meta($post_id, 'wpcom_likes', true);
        $metas['likes'] = $likes ?: 0;

        $res = rest_ensure_response($metas);
        if(!is_wp_error($res) && !isset($request['user_id'])) $res->set_headers(array('Cache-Control' => 'max-age=600'));
        return $res;
    }
    public function permission_check(){
        return true;
    }
}

class WWA_REST_Post_Info2_Controller extends WWA_REST_Post_Info_Controller {
    public function __construct(){
        $this->namespace = 'wp/v2';
        $this->rest_base = 'post-info';
    }
}