<?php
global $qa_options, $wpcomqadb;
$user = get_user_by('ID', $comment->user_id);
$author_name = $comment->comment_author;
$avatar = get_avatar( $comment->user_id ? (int)$comment->user_id : $comment->comment_author_email, '60', '', $author_name );
if($user){
    $author_name = isset($user->display_name) ? $user->display_name : $user->user_nicename;
    if(class_exists('WPCOM_Member') && apply_filters( 'wpcom_member_show_profile' , true )){
        $url = get_author_posts_url( $user->ID );
        $author_name = '<a class="j-user-card" data-user="'.$user->ID.'" href="'.$url.'" target="_blank">'.$author_name.'</a>';
        $avatar = '<a class="j-user-card" data-user="'.$user->ID.'" href="'.$url.'" target="_blank">'.get_avatar( (int)$comment->user_id, '60', '', $user->display_name ).'</a>';
    }
} ?>
<li id="as-<?php echo $comment->comment_ID;?>" class="as-item" data-aid="<?php echo $comment->comment_ID;?>">
    <div class="as-head">
        <div class="as-avatar"><?php echo $avatar;?></div>
        <div class="as-user">
            <?php echo $author_name;?>
            <?php if($user && $user->description){ ?><div class="as-desc"><?php echo $user->description;?></div><?php } ?>
             <span class="as-reply"><a class="j-reply" href="javascript:;"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-quill-pen"></use></svg></i><?php _ex('Reply', 'qapress', 'wpcom');?></a></span>
        </div>
    </div>
    <div class="as-main">
        <div class="as-content entry-content"><?php comment_text();?></div>
        <div class="as-action">
            <?php if((isset($qa_options['enable_zan']) && $qa_options['enable_zan']) || !isset($qa_options['enable_zan'])){
                $upvote = get_comment_meta($comment->comment_ID, 'upvote_count', true); $upvote = $upvote ?: 0;
                $user_id = get_current_user_id();
                $vote = $user_id ? $wpcomqadb->get_comment_vote($comment->comment_ID, $user_id) : null;
                $vote_type = $vote && $vote->meta_key ? $vote->meta_key : ''; ?>
                <div class="as-action-vote">
                <button aria-label="<?php _e('Upvote', 'wpcom');?>" data-vote="<?php echo $upvote;?>" type="button" class="btn-vote btn-vote-up<?php echo $vote_type==='upvote'?' active':'';?>">
                    <svg class="vote-icon" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M13.792 3.681c-.781-1.406-2.803-1.406-3.584 0l-7.79 14.023c-.76 1.367.228 3.046 1.791 3.046h15.582c1.563 0 2.55-1.68 1.791-3.046l-7.79-14.023z"/></svg><?php $upvote > 0 ? printf(__('Upvote %s', 'wpcom'), $upvote) : _e('Upvote', 'wpcom');?>
                </button>
                <button aria-label="<?php _e('Downvote', 'wpcom');?>" type="button" class="btn-vote btn-vote-down<?php echo $vote_type==='downvote'?' active':'';?>">
                    <svg class="vote-icon" width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M13.792 20.319c-.781 1.406-2.803 1.406-3.584 0L2.418 6.296c-.76-1.367.228-3.046 1.791-3.046h15.582c1.563 0 2.55 1.68 1.791 3.046l-7.79 14.023z"/></svg>
                </button>
                </div>
            <?php } ?>
            <div class="as-action-right">
                <span class="as-time"><?php echo QAPress_format_date(get_comment_date( 'U', $comment->comment_ID ));?></span>
                <span class="as-reply-count"><a class="j-reply-list" href="javascript:;"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-comment"></use></svg></i><?php $comment->comment_karma ? printf(_n('%s comment', '%s comments', $comment->comment_karma, 'wpcom'), $comment->comment_karma) : _ex('No comments', 'qapress, 0 comment', 'wpcom');?></a></span>
                <?php if( current_user_can( 'manage_options' ) ) { ?><span class="as-del"><a class="j-answer-del" href="javascript:;"><?php _ex('Delete', 'qapress', 'wpcom');?></a></span><?php } ?>
            </div>
        </div>
        <?php if(isset($qa_options['show_qa_comment']) && $qa_options['show_qa_comment'] && $comment->comment_karma){
        $comments = $wpcomqadb->get_comments($comment->comment_ID);
        if($comments){
            $del = current_user_can( 'manage_options' ) ? 1 : 0;?>
            <div class="as-comments">
                <ul class="as-comments-list">
                <?php foreach ($comments as $com) {
                    $_user = get_user_by('ID', $com->user_id);
                    $author_name = '<span class="as-comment-author">' . ($_user->display_name ? $_user->display_name : $_user->user_nicename) . '</span>';
                    if(class_exists('WPCOM_Member') && apply_filters( 'wpcom_member_show_profile' , true )){
                        $url = get_author_posts_url( $_user->ID );
                        $author_name = '<a class="as-comment-url j-user-card" data-user="'.$_user->ID.'" href="'.$url.'" target="_blank">'.$author_name.'</a>';
                    }
                    ?>
                    <li class="as-comments-item" data-id="<?php echo esc_attr($com->comment_ID);?>">
                        <div class="as-comment-name"><?php echo $author_name;?> <span><?php echo $com->comment_date;?></span>
                            <?php if($del) { ?><span><a class="j-del-comment" href="javascript:;"><?php _ex('Delete', 'qapress', 'wpcom');?></a></span><?php } ?>
                        </div>
                        <div class="as-comment-content"><?php echo wpautop($com->comment_content);?></div>
                    </li>
                <?php } ?>
                </ul>
            </div>
        <?php  }
    } ?>
    </div>
</li>