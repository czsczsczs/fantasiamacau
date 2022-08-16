<?php

class QAPress_SQL {
    function __construct(){
        global $QAPress;
        add_action('activate_'. $QAPress->basename, array($this, 'flush_rewrite_rules'));
    }

    function flush_rewrite_rules(){
        flush_rewrite_rules( true );
    }

    function get_questions_total( $cat=0 ){
        global $wp_questions;
        if( $wp_questions ) return $wp_questions->found_posts;
        $arg = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post'
        );
        if( $cat ){
            $arg['tax_query'] = array(
                array(
                    'taxonomy' => 'qa_cat',
                    'terms'    => $cat,
                )
            );
        }

        $wp_questions = new WP_Query;
        $wp_questions->query($arg);
        return $wp_questions->found_posts;
    }

    function get_questions_total_by_user( $user=0 ){
        global $wp_questions_by_user;
        if( $wp_questions_by_user ) return $wp_questions_by_user->found_posts;
        $arg = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post',
            'author' => $user
        );
        $wp_questions_by_user = new WP_Query;
        $wp_questions_by_user->query($arg);
        return $wp_questions_by_user->found_posts;
    }

    function get_questions( $num=20, $paged=1, $cat=0 ){
        global $wp_questions;
        $arg = array(
            'posts_per_page' => $num,
            'paged' => $paged,
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post',
            'orderby' => 'menu_order modified'
        );
        if( $cat ){
            $arg['tax_query'] = array(
                array(
                    'taxonomy' => 'qa_cat',
                    'terms'    => $cat,
                )
            );
        }
        if( $wp_questions ) $wp_questions->query($arg);

        $wp_questions = new WP_Query;
        return $wp_questions->query($arg);
    }

    function get_questions_by_user( $user, $num=20, $paged=1 ){
        global $wp_questions_by_user;
        $arg = array(
            'posts_per_page' => $num,
            'paged' => $paged,
            'post_status' => array( 'publish' ),
            'post_type' => 'qa_post',
            'author' => $user,
            'orderby' => 'modified'
        );

        if( $wp_questions_by_user ) $wp_questions_by_user->query($arg);

        $wp_questions_by_user = new WP_Query;
        return $wp_questions_by_user->query($arg);
    }

    function get_question( $id ){
        if($id){
            $post = get_post( $id );
            if( $post && $post->post_type =='qa_post' ) return $post;
        }
    }

    function delete_question( $id ){
        if($id){
            return wp_delete_post($id);
        }
    }

    function insert_question($question){
        if(isset($question['ID'])){
            $update = wp_update_post($question);
            if($update) { //更新成功
                return $question['ID'];
            }else{
                return false;
            }
        }else{
            if($id = wp_insert_post($question)){ //插入成功
                return $id;
            }else{
                return false;
            }
        }
    }

    function add_views($id){
        $views = get_post_meta($id, 'views', true);
        if( !function_exists('the_views') ){
            $views = $views ? $views + 1 : 1;
            update_post_meta($id, 'views', $views);
        }
        return $views;
    }

    function get_answers( $id, $num=20, $paged=1, $order='ASC' ){
        if($id){
            $args = array(
                'parent' => 0,
                'post_id' => $id,
                'number' => $num,
                'paged' => $paged,
                'order' => $order,
                'order_by' => 'comment_date',
                'type' => 'answer',
                'status' => 'approve'
            );
            return get_comments( $args );
        }
    }

    function get_answers_by_user( $user, $num=20, $paged=1, $order='DESC' ){
        if($user){
            $args = array(
                'parent' => 0,
                'user_id' => $user,
                'number' => $num,
                'paged' => $paged,
                'order' => $order,
                'order_by' => 'comment_date',
                'type' => 'answer',
                'status' => 'approve'
            );
            return get_comments( $args );
        }
    }

    function get_answers_total_by_user( $user ){
        if($user){
            $args = array(
                'parent' => 0,
                'user_id' => $user,
                'count'   => true,
                'type' => 'answer',
                'status' => 'approve'
            );
            return get_comments( $args );
        }
    }

    function delete_answers( $question ){
        if($question){
            global $wpdb;
            return $wpdb->delete($wpdb->comments, array('comment_post_ID' => $question));
        }
    }

    function delete_answer( $id ){
        if($id) wp_delete_comment($id);
    }

    function get_comments($id){
        if($id){
            $args = array(
                'parent' => $id,
            );
            return get_comments( $args );
        }
    }

    function delete_comments( $answer ){
        if($answer){
            global $wpdb;
            return $wpdb->delete($wpdb->comments, array('comment_parent' => $answer));
        }
    }

    function delete_comment( $id ){
        if($id) wp_delete_comment($id);
    }

    function insert_comment($comment){
        global $wpdb;
        $cid = wp_insert_comment($comment);
        if($cid){ //插入成功
            return $cid;
        }else{
            return false;
        }
    }

    function insert_answer($answer){
        if($answer){
            $id = wp_insert_comment($answer);
            return $id;
        }
    }

    function set_top( $question ){
        if($question){
            global $wpdb;
            $row = $wpdb->get_row("SELECT menu_order, post_modified, post_modified_gmt FROM `$wpdb->posts` WHERE ID = '$question'");
            $flag = $row->menu_order=='1' ? 0 : 1;
            $update = wp_update_post(array('ID' => $question, 'menu_order'=>$flag));
            $wpdb->update($wpdb->posts, array('post_modified'=>$row->post_modified, 'post_modified_gmt'=>$row->post_modified_gmt), array('ID' => $question));
            return $update;

        }
    }

    function get_comment_vote($comment, $user){
        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM `$wpdb->commentmeta` WHERE comment_id = '$comment' AND meta_value = '$user' AND (meta_key = 'upvote' OR meta_key = 'downvote')");
        if($row) return $row;
    }

    function update_comment_vote($comment, $user, $type){
        global $wpdb;
        $option = @$wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$wpdb->commentmeta` WHERE comment_id = %s AND meta_value = %s AND (meta_key = 'upvote' OR meta_key = 'downvote')", $comment, $user ) );
        if(null !== $option) {
            $res = $wpdb->update( $wpdb->commentmeta, array('meta_key' => $type), array('meta_id' => $option->meta_id) );
        }else{
            $res = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->commentmeta` (`comment_id`, `meta_key`, `meta_value`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `comment_id` = VALUES(`comment_id`), `meta_key` = VALUES(`meta_key`), `meta_value` = VALUES(`meta_value`)", $comment, $type, $user ) );
        }
        return $res;
    }
}

$wpcomqadb = new QAPress_SQL();
