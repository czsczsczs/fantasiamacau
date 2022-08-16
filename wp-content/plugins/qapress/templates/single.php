<?php
global $qa_options;
$current_user = wp_get_current_user();
?>
<div class="q-content q-single" data-id="<?php the_ID();?>">
    <div class="topic-header">
        <?php if($post->menu_order==1){ ?><span class="put-top"><?php _ex('TOP', 'qapress', 'wpcom') ;?></span><?php } ?>
        <h1 class="q-title"><?php the_title();?></h1>
        <div class="q-info">
            <?php if( current_user_can( 'manage_options' ) ){
                $edit_url = QAPress_edit_url($post->ID);?>
                <div class="pull-right qa-manage">
                    <?php if($post->post_status=='pending'){ ?><a class="j-approve" href="javascript:;"><?php _ex('Approve', 'qapress', 'wpcom');?></a><?php } ?>
                    <a href="<?php echo $edit_url;?>"><?php _ex('Edit', 'qapress', 'wpcom');?></a>
                    <a class="j-set-top" href="javascript:;"><?php echo $post->menu_order==1? _x('UN-sticky', 'qapress', 'wpcom') : _x('Sticky', 'qapress', 'wpcom');?></a>
                    <a class="j-del" href="javascript:;"><?php _ex('Delete', 'qapress', 'wpcom');?></a>
                </div>
            <?php }else if($current_user && $current_user->ID === $user->ID){
                $enable_edit = isset($qa_options['enable_edit']) && $qa_options['enable_edit']=='1';
                $enable_delete = isset($qa_options['enable_delete']) && $qa_options['enable_delete']=='1';
                $_html = '';
                if($enable_edit) {
                    $edit_url = QAPress_edit_url($post->ID);
                    $_html .= '<a href="'.$edit_url.'">'._x('Edit', 'qapress', 'wpcom').'</a>';
                }
                if($enable_delete) {
                    $edit_url = QAPress_edit_url($post->ID);
                    $_html .= '<a class="j-del" href="javascript:;">'._x('Delete', 'qapress', 'wpcom').'</a>';
                }
                if($_html){ ?>
                    <div class="pull-right qa-manage"><?php echo $_html;?></div>
                <?php }
            }

            $content = get_the_content(null, false, $post);
            $content = apply_filters( 'the_content', $content );
            $content = str_replace( ']]>', ']]&gt;', $content );
            ?>
            <span class="q-author"><?php echo $author_name;?></span>
            <time class="q-time published" datetime="<?php echo get_post_time( 'c', false, $post->ID );?>" pubdate>
                <i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-date"></use></svg></i><?php echo QAPress_format_date(get_post_time( 'U', false, $post->ID ));?>
            </time>
            <?php if(isset($cat->slug)){?><span class="q-cat"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-folder-open"></use></svg></i><a href="<?php echo QAPress_category_url($cat->slug);?>"><?php echo $cat->name;?></a></span><?php } ?>
            <?php if((isset($qa_options['show_views']) && $qa_options['show_views']) || !isset($qa_options['show_views'])){ ?><span class="q-cat"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-eye"></use></svg></i><?php echo $post->views?$post->views:0;?></span><?php } ?>
        </div>
    </div>
    <div class="q-entry entry-content"><?php echo $content;?></div>

    <?php do_action('qapress_echo_ad', 'ad_single_end');?>

    <div class="q-answer" id="answer">
        <div class="as-title">
            <h3><?php _ex('Comments', 'qapress', 'wpcom');?></h3>
            <?php if($post->comment_count){?><span><?php printf(_nx('%s comment', '%s comments', $post->comment_count, 'qapress', 'wpcom'), $post->comment_count) ?></span><?php } ?>
            <a class="as-to-reply" href="#as-form"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-quill-pen"></use></svg></i><?php _ex('Add comment', 'qapress', 'wpcom');?></a>
        </div>
        <?php wp_nonce_field( 'QAPress_comments_list', 'comments_list_nonce' );?>
        <ul class="as-list">
            <?php if($answers){
                global $comment;
                foreach ($answers as $comment) {
                    echo QAPress_template('comment', array('comment' => $comment));
                }
            }else{ ?>
                <li class="as-item-none">
                    <svg width="134" height="111" viewBox="0 0 134 111" xmlns="http://www.w3.org/2000/svg"><g transform="translate(-3 -8)" fill="none" fill-rule="evenodd"><path d="M8.868 80c.474 0 .857.384.857.857v4.292h4.424a.85.85 0 1 1 0 1.702l-4.424-.001v4.293a.857.857 0 1 1-1.714 0V86.85h-4.16a.85.85 0 0 1 0-1.7l4.16-.001v-4.292c0-.473.384-.857.857-.857z" fill="#DAE0E5"/><ellipse fill="#DAE0E5" cx="70" cy="115" rx="40" ry="4"/><path d="M22 15.88v-5.76a1.216 1.216 0 0 1 1.73-1.102l6.17 2.88a1.216 1.216 0 0 1 0 2.204l-6.17 2.88A1.216 1.216 0 0 1 22 15.88z" fill="#DAE0E5"/><circle stroke="#DAE0E5" stroke-width="1.824" cx="120" cy="92" r="5"/><path d="M130.868 11c.474 0 .857.384.857.857v4.292h4.424a.85.85 0 0 1 0 1.702l-4.424-.001v4.293a.857.857 0 0 1-1.714 0V17.85h-4.16a.85.85 0 0 1 0-1.7l4.16-.001v-4.292c0-.473.384-.857.857-.857z" fill="#DAE0E5"/><path d="M31.382 39C28.415 39 26 41.426 26 44.406v39.088c0 2.98 2.415 5.406 5.382 5.406h16.82l-.79 6.132c-.299 1.178-.088 2.38.597 3.34A3.906 3.906 0 0 0 51.184 100c.728 0 1.455-.203 2.1-.586.08-.047.158-.099.233-.155l13.97-10.36h24.128c2.97 0 5.385-2.425 5.385-5.405V44.406C97 41.426 94.585 39 91.615 39H31.382zM67 85.81c-.612 0-1.208.197-1.7.563l-13.924 9.112.822-6.42a2.91 2.91 0 0 0-.69-2.275 2.85 2.85 0 0 0-2.151-.98l-19.898.1-.05-22.14-.05-21.298 64.548-.1.098 43.437H67z" fill="#DAE0E5" fill-rule="nonzero"/><path d="M109.619 19l-53.43.075c-2.86 0-5.189 2.317-5.189 5.163v8.238l3.37-.075V22.184h56.598v37.423l-7.234.075c-.684.492-1.025 1.19-1.025 2.092 0 .903.341 1.645 1.025 2.226h5.925c2.861 0 4.341-1.472 4.341-4.318V24.238c0-2.846-1.52-5.238-4.381-5.238zM63.642 70v-.608c0-.859.177-1.646.566-2.362.317-.644.812-1.288 1.483-1.86 1.66-1.468 2.649-2.398 2.967-2.791C69.54 61.234 70 59.766 70 58.013c0-2.147-.706-3.864-2.12-5.117C66.469 51.608 64.632 51 62.37 51c-2.613 0-4.661.751-6.145 2.29C54.742 54.793 54 56.832 54 59.444h3.709c0-1.574.317-2.79.953-3.65.707-1.037 1.872-1.538 3.462-1.538 1.271 0 2.295.358 3.002 1.074.67.715 1.024 1.681 1.024 2.934 0 .93-.353 1.789-.989 2.612l-.6.68c-2.19 1.968-3.532 3.435-3.991 4.436-.495.93-.707 2.076-.707 3.4V70h3.78z" fill="#DAE0E5" fill-rule="nonzero"/><path fill="#DAE0E5" d="M59 72h4v4h-4z"/></g></svg>
                    <p><?php _ex('Comment list is empty', 'qapress', 'wpcom');?></p>
                </li>
            <?php } ?>

        </ul>

        <?php if($post->comment_count>$per_page){ ?>
            <div class="q-load-wrap"><div class="q-load-more" href="javascript:;"><?php _ex('Load more', 'qapress', 'wpcom');?></div></div>
        <?php }

        if($current_user->ID){
            $allow_img = isset($qa_options['answer_img']) && $qa_options['answer_img'] ? 1 : 0;
            $allow_link = isset($qa_options['answer_link']) && $qa_options['answer_link'] ? 1 : 0;
            ob_start();
            wp_editor( '', 'editor-answer', QAPress_editor_settings(array('textarea_name'=>'answer', 'allow_img'=>$allow_img, 'allow_link' => $allow_link)) );
            $editor_contents = ob_get_clean();
            $answer_html = '<form id="as-form" class="as-form" action="" method="post" enctype="multipart/form-data">
                    <h3 class="as-form-title">'._x('Add comment', 'qapress', 'wpcom').'</h3>
                    '.$editor_contents.'
                    <input type="hidden" name="id" value="'.$post->ID.'">
                    <div class="as-submit clearfix">
                        <div class="pull-right"><input class="btn-submit" type="submit" value="'._x('Submit', 'qapress comment', 'wpcom').'"></div>
                    </div>
                </form>';
        }else{
            $login_url = isset($qa_options['login_url']) && $qa_options['login_url'] ? $qa_options['login_url'] : wp_login_url();
            $register_url = isset($qa_options['register_url']) && $qa_options['register_url'] ? $qa_options['register_url'] : wp_registration_url();
            $answer_html = '<div id="as-form" class="as-login-notice">'.sprintf(_x('Please <a href="%s">login</a> or <a href="%s">register</a> to add comment.', 'qapress', 'wpcom'), $login_url, $register_url).'</div>';
        }

        echo $answer_html; ?>
    </div>

    <?php do_action('qapress_echo_ad', 'ad_comments_end');?>

    <?php if(isset($qa_options['enable_related']) && $qa_options['enable_related']) {
        $number = isset($qa_options['related_num']) && $qa_options['related_num'] ? $qa_options['related_num'] : 10;
        $related = QAPress_related($post->ID, $number);
        if($related){
            $related_title = isset($qa_options['related_title']) && $qa_options['related_title'] ? $qa_options['related_title'] : '相关问题'; ?>
            <div class="q-related">
                <h3 class="q-related-title"><?php echo $related_title;?></h3>
                <ul class="q-related-list">
                    <?php foreach ($related as $rp) { ?>
                        <li class="q-related-item">
                            <a href="<?php echo get_permalink($rp->ID); ?>" target="_blank">
                                <span class="q-related-ititle"><?php echo get_the_title($rp->ID);?></span>
                            </a>
                            <div class="q-related-info">
                                <span><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-time"></use></svg></i><?php echo QAPress_format_date(get_post_modified_time( 'U', false, $rp->ID ));?></span>
                                <span><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-comment"></use></svg></i><?php echo $rp->comment_count;?></span>
                                <?php if((isset($qa_options['show_views']) && $qa_options['show_views']) || !isset($qa_options['show_views'])){ ?><span><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-eye"></use></svg></i><?php $views = get_post_meta($rp->ID, 'views', true); echo $views ?: 0;?></span><?php } ?>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
    <?php } } ?>
</div>
