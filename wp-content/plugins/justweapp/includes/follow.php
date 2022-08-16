<?php
defined( 'ABSPATH' ) || exit;

class WWA_Follow{
    function __construct(){
        if(!class_exists('WPCOM_Follow')){
            add_action( 'wpcom_follow_user', array($this, 'update_count'), 10, 2 );
            add_action( 'wpcom_unfollow_user', array($this, 'update_count'), 10, 2 );
            add_filter( 'wpcom_followers_count', array($this, 'get_followers_count'), 5, 2);
        }
        add_filter( 'wpcom_follows_count', array($this, 'get_follows_count'), 5, 2);
    }

    function follow($followed, $user=''){
        global $wpdb;
        $user = $user ? $user : get_current_user_id();
        $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
        if($user && $followed && is_numeric($user) && is_numeric($followed) && !$this->is_followed($followed, $user)){
            $res = add_user_meta($user, $option_name, $followed);
            if($res){
                do_action('wpcom_follow_user', $user, $followed);
            }
            return $res;
        }
        return false;
    }

    function unfollow($followed, $user=''){
        global $wpdb;
        $user = $user ? $user : get_current_user_id();
        $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
        if($user && $followed && is_numeric($user) && is_numeric($followed) && $this->is_followed($followed, $user)){
            $res = delete_user_meta( $user, $option_name, $followed );
            if($res){
                do_action('wpcom_unfollow_user', $user, $followed);
            }
            return $res;
        }
        return false;
    }

    function update_count($user, $followed){
        global $wpdb;
        $table = _get_meta_table('user');
        $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM $table WHERE meta_key = %s AND meta_value = %d", $option_name, $followed));
        if(is_wp_error($count)){
            $filter = current_filter();
            $count = get_user_meta($followed, $wpdb->get_blog_prefix() . 'followers_count', true);
            $count = $count ? $count : 0;
            if($filter==='wpcom_follow_user'){
                $count += 1;
            }else if($count>0){
                $count -= 1;
            }
        }
        update_user_option($followed, 'followers_count', $count);
        return $count;
    }

    function get_followers_count($count){
        if($count==='') $count = 0;
        return $count;
    }

    function get_follows_count($count){
        if($count==='') $count = 0;
        return $count;
    }

    function is_followed($followed, $user=''){
        global $wpdb;
        $user = $user ? $user : get_current_user_id();
        if($user && $followed && is_numeric($user) && is_numeric($followed)) {
            $table = _get_meta_table('user');
            $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
            if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE meta_key = %s AND user_id = %d AND meta_value = %d", $option_name, $user, $followed))) {
                return true;
            }
        }
        return false;
    }

    function get_follows($user, $number=-1, $paged=1){
        global $wpdb;
        $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
        $ids = get_user_meta($user, $option_name);
        if($ids) {
            if($number>0){
                $ids = array_slice(array_reverse($ids), $number*($paged-1), $number);
            }
            if($ids){
                $users = get_users(array('include' => $ids, 'orderby' => 'include'));
                if(!is_wp_error($users)) return $users;
            }
        }
        return false;
    }

    function get_followers($user, $number=-1, $paged=1){
        global $wpdb;
        $table = _get_meta_table( 'user' );
        $option_name = $wpdb->get_blog_prefix() . '_wpcom_follow';
        $limit = '';
        if($number>0) $limit = 'LIMIT ' . ($number*($paged-1)) . ', '.$number;
        $meta_list = $wpdb->get_results( "SELECT user_id FROM $table WHERE meta_key = '$option_name' AND meta_value = '$user' ORDER BY umeta_id DESC $limit" );
        $ids = array();
        if($meta_list){
            foreach ($meta_list as $meta){
                if($meta->user_id && !in_array($meta->user_id, $ids)) $ids[] = $meta->user_id;
            }
        }
        if($ids) {
            $users = get_users(array('include' => $ids, 'orderby' => 'include'));
            if(!is_wp_error($users)) return $users;
        }
        return false;
    }
}

$GLOBALS['_wwa_follow'] = NEW WWA_Follow();