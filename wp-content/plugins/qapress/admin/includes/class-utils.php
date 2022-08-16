<?php defined( 'ABSPATH' ) || exit;
class WPCOM_ADMIN_UTILS{
    public static function get_all_pages(){
        $pages = get_pages(array('post_type' => 'page','post_status' => 'publish'));
        $res = array();
        if($pages){
            foreach ($pages as $page) {
                $p = array(
                    'ID' => $page->ID,
                    'title' => $page->post_title
                );
                $res[] = $p;
            }
        }
        return $res;
    }

    public static function panel_script(){
        global $pagenow;
        // Load CSS
        wp_enqueue_style('plugin-panel', WPCOM_ADMIN_URI . 'css/panel.css', false, WPCOM_ADMIN_VERSION, 'all');

        // Load JS
        wp_enqueue_script('vue', WPCOM_ADMIN_URI . "js/vue.min.js", array(), WPCOM_ADMIN_VERSION, true);
        wp_enqueue_script("vue-select", WPCOM_ADMIN_URI . "js/vue-select.js", array('vue'), WPCOM_ADMIN_VERSION, true);
        wp_enqueue_script("plugin-panel", WPCOM_ADMIN_URI . "js/panel.js", array('jquery', 'vue-select'), WPCOM_ADMIN_VERSION, true);
        do_action('setup_plugin_panel_scripts');
        if($pagenow!=='post.php' && $pagenow!=='post-new.php') wp_enqueue_media();

        $settings = function_exists('wp_get_code_editor_settings') ? wp_get_code_editor_settings( array( 'type' => 'text/html' ) ) : false;

        wp_enqueue_script( 'code-editor' );
        wp_enqueue_style( 'code-editor' );
        wp_enqueue_script( 'csslint' );
        wp_enqueue_script( 'htmlhint' );
        wp_enqueue_script( 'jshint' );
        wp_enqueue_script( 'jsonlint' );

        wp_add_inline_script( 'code-editor', sprintf( 'codemirrorSettings = %s', wp_json_encode( $settings ) ) );
    }

    public static function editor_settings($args = array()){
        add_filter( 'user_can_richedit' , '__return_true', 100 );
        return array(
            'textarea_name' => $args['textarea_name'],
            'textarea_rows' => isset($args['textarea_rows']) ? $args['textarea_rows'] : 3,
            'tinymce'       => array(
                'height'        => 150,
                'toolbar1' => 'formatselect,fontsizeselect,bold,italic,blockquote,forecolor,alignleft,aligncenter,alignright,link,bullist,numlist,wpcomimg,wpcomdark,wpcomtext',
                'toolbar2' => '',
                'toolbar3' => '',
                'plugins' => 'colorpicker,hr,lists,media,paste,textcolor,wordpress,wpautoresize,wpeditimage,wplink,wpdialogs,wptextpattern,image,wpcomimg,wpcomdark,wpcomtext',
                'statusbar' => false,
                'content_css' => WPCOM_ADMIN_URI . 'css/tinymce-style.css?ver=' . WPCOM_ADMIN_VERSION,
                'external_plugins' => "{wpcomimg: '" . WPCOM_ADMIN_URI . "js/tinymce-img.js', wpcomdark: '" . WPCOM_ADMIN_URI . "js/tinymce-dark.js', wpcomtext: '" . WPCOM_ADMIN_URI . "js/tinymce-text.js'}"
            )
        );
    }

    public static function framework_version($plugin = ''){
        if( function_exists('file_get_contents') && $plugin ){
            $path = WP_PLUGIN_DIR . '/' . strtolower($plugin);
            $files = @file_get_contents( $path . '/admin/load.php' );
            preg_match('/define\s*?\(\s*?[\'|"]WPCOM_ADMIN_VERSION[\'|"],\s*?[\'|"](.*)[\'|"].*?\)/i', $files, $matches);
            if( isset($matches[1]) && $matches[1] ){
                return trim($matches[1]);
            }
        }
        return WPCOM_ADMIN_VERSION;
    }

    public static function category( $tax = 'category' ){
        $categories = get_terms( array(
            'taxonomy' => $tax,
            'hide_empty' => false,
        ) );

        $cats = array();

        if( $categories && !is_wp_error($categories) ) {
            foreach ($categories as $cat) {
                $cats[$cat->term_id] = $cat->name;
            }
        }

        return $cats;
    }
}