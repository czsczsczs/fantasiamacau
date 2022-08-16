<?php
defined( 'ABSPATH' ) || exit;

class WWA_User{
    function __construct(){
        if(defined('FRAMEWORK_VERSION') && apply_filters( 'wpcom_member_show_profile' , true )){
            global $options;
            if(isset($options['member_follow']) && ($options['member_follow']=='1' || $options['user_card']=='1')) {
                return false;
            }
        }

        add_action('save_post_post', array($this, 'posts_count'), 10, 2);
        add_action('save_post_qa_post', array($this, 'qa_posts_count'), 10, 2);
        add_action('transition_comment_status', array($this, 'comments_count_status'), 10, 3);
        add_action('wp_insert_comment', array($this, 'comments_count'), 10, 2);

        add_filter( 'wpcom_posts_count', array($this, 'get_posts_count'), 5, 2);
        add_filter( 'wpcom_comments_count', array($this, 'get_comments_count'), 5, 2);
        if(defined('QAPress_VERSION')) {
            add_filter('wpcom_questions_count', array($this, 'get_questions_count'), 5, 2);
            add_filter('wpcom_answers_count', array($this, 'get_answers_count'), 5, 2);
        }
    }

    function get_posts_count($count, $user){
        if($count==='') $count = $this->update_post_count($user);
        return $count ?: 0;
    }

    function get_comments_count($count, $user){
        if($count==='') $count = $this->update_comment_count($user);
        return $count ?: 0;
    }

    function get_questions_count($count, $user){
        if($count==='') $count = $this->update_question_count($user);
        return $count ?: 0;
    }

    function get_answers_count($count, $user){
        if($count==='') $count = $this->update_answer_count($user);
        return $count ?: 0;
    }

    function posts_count($postid, $post){
        if($postid) $this->update_post_count($post->post_author);
    }

    function qa_posts_count($postid, $post){
        if($postid) $this->update_question_count($post->post_author);
    }

    function comments_count($comment_ID, $comment){
        if($comment_ID && $comment->user_id) {
            if($comment->comment_type==='' || $comment->comment_type==='comment'){
                $this->update_comment_count($comment->user_id);
            }else if($comment->comment_type==='answer'){
                $this->update_answer_count($comment->user_id);
            }
        }
    }

    function comments_count_status($new_status, $old_status, $comment){
        if($comment->user_id) {
            if($comment->comment_type==='' || $comment->comment_type==='comment'){
                $this->update_comment_count($comment->user_id);
            }else if($comment->comment_type==='answer'){
                $this->update_answer_count($comment->user_id);
            }
        }
    }

    function update_post_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND post_author = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'posts_count', $count);
            return $count;
        }
    }

    function update_comment_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->comments} WHERE (comment_type = '' OR comment_type = 'comment') AND comment_approved = 1 AND user_id = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'comments_count', $count);
            return $count;
        }
    }

    function update_question_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'qa_post' AND post_status = 'publish' AND post_author = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'questions_count', $count);
            return $count;
        }
    }

    function update_answer_count($user){
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT( * ) FROM {$wpdb->comments} WHERE comment_type = 'answer' AND comment_approved = 1 AND user_id = %d", $user));
        if(!is_wp_error($count)) {
            update_user_option($user, 'answers_count', $count);
            return $count;
        }
    }
}