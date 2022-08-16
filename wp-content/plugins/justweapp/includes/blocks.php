<?php
defined( 'ABSPATH' ) || exit;

add_action('admin_init', 'WWA_gutenberg_blocks');
function WWA_gutenberg_blocks() {
    global $wp_version;
    wp_register_script('wwa-blocks', WWA_URI . '/js/blocks.js', array('wp-blocks', 'wp-element'), WWA_VERSION, true);
    wp_register_style('wwa-blocks', WWA_URI . '/css/blocks.css', array('wp-edit-blocks'), WWA_VERSION);
    wp_localize_script('wwa-blocks', '_wwa_blocks', apply_filters('wpcom_blocks_script', array('exclude' => array())));


    register_block_type('wpcom/wwa-blocks', array(
        'editor_script' => 'wwa-blocks',
        'editor_style' => 'wwa-blocks'
    ));
    if(version_compare($wp_version, '5.8.0') >= 0){
        add_filter( 'block_categories_all', 'WWA_gutenberg_block_categories', 5 );
    }else{
        add_filter( 'block_categories', 'WWA_gutenberg_block_categories', 5 );
    }
}

function WWA_gutenberg_block_categories( $categories ) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'wpcom',
                'title' => __( 'WPCOM扩展区块', 'wpcom' )
            ),
        )
    );
}

if (!function_exists('wpcom_gutenberg_blocks')) {
    add_filter('wpcom_blocks_script', 'WWA_block_exclude_for_widget');
    function WWA_block_exclude_for_widget($blocks) {
        $blocks['exclude_widgets'] = array('wpcom/hidden-content', 'wpcom/rewarded-content', 'wpcom/premium-content');
        return $blocks;
    }
}