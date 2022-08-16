<?php
/*
 * Plugin Name: WPCOM Member 用户中心
 * Description: WordPress用户中心插件
 * Version: 1.0.1
 * Author: WPCOM
 * Author URI: https://www.wpcom.cn
*/

defined('ABSPATH') || exit;

define( 'WPMX_VERSION', '1.0.1' );
define( 'WPMX_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPMX_URI', plugins_url( '/', __FILE__ ) );

if (!defined('WPCOM_ADMIN_FREE_PATH')) {
    define('WPCOM_ADMIN_FREE_PATH', is_dir($framework_path = plugin_dir_path(__FILE__) . '/admin/') ? $framework_path : plugin_dir_path(__DIR__) . '/Themer-free/admin/');
    define('WPCOM_ADMIN_FREE_URI', is_dir($framework_path) ? plugins_url('/admin/', __FILE__) : plugins_url('/Themer-free/admin/', __DIR__));
}

require_once WPMX_DIR . 'includes/functions.php';

add_action( 'wpmx_cron_flush_rewrite_rules', 'flush_rewrite_rules' );
register_activation_hook( __FILE__, 'wpmx_plugin_activate' );
function wpmx_plugin_activate(){
    $args = array();
    $args[] = mt_rand(1000, 99999) . '_' . time();
    wp_schedule_single_event( time() + 2, 'wpmx_cron_flush_rewrite_rules', $args );
}