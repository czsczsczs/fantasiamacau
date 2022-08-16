<?php defined( 'ABSPATH' ) || exit;
if( !class_exists('WPCOM_PLUGIN_PANEL') ) {
    define( 'WPCOM_ADMIN_VERSION', '2.6.21' );
    define( 'WPCOM_ASSETS_VERSION', '' );
    require WPCOM_ADMIN_PATH . 'includes/class-plugin-panel.php';
}