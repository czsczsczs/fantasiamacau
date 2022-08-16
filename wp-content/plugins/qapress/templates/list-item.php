<?php
global $qa_options;
?>
<div class="q-topic-item">
    <a class="user-avatar" href="javascript:;" data-user="<?php echo $post->post_author;?>">
        <?php $user = get_user_by('ID', $post->post_author); echo get_avatar( $user->ID, '100', '', $user->display_name );?>
        <span class="user-avatar-name">
            <span class="user-name"><?php echo $user->display_name;?></span>
            <time class="topic-time published" datetime="<?php echo get_post_modified_time( 'c', false, $post->ID );?>" pubdate>
                <?php echo QAPress_format_date(get_post_modified_time( 'U', false, $post->ID ));?>
            </time>
        </span>
    </a>
    <div class="topic-content">
        <a class="topic-title" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
            <?php if($post->menu_order==1) echo '<span class="put-top">'. _x('TOP', 'qapress', 'wpcom') . '</span>';?>
            <?php the_title();?>
        </a>
        <?php
            preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
            if (isset($matches[1]) && isset($matches[1][0])) {
                $thumbs = array_slice($matches[1], 0, 3);
                if(count($thumbs) === 3){ ?>
                    <a class="topic-images" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                        <?php foreach($thumbs as $img){ ?>
                            <img class="topic-images-item" src="<?php echo esc_url($img);?>" alt="<?php echo esc_attr(get_the_title());?>">
                        <?php } ?>
                    </a>
                <?php }else{ ?>
                    <a class="topic-image" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                        <img class="topic-image-item" src="<?php echo esc_url($thumbs[0]);?>" alt="<?php echo esc_attr(get_the_title());?>">
                    </a>
                <?php }
            } ?>
        <div class="topic-meta">
            <?php $post_cat = get_the_terms($post->ID, 'qa_cat');
            if($post_cat){ $_cat = $post_cat[0]; ?>
                <a class="topic-cat" href="<?php echo QAPress_category_url($_cat->slug);?>"><?php echo $_cat->name;?></a>
            <?php } ?>
            <time class="topic-time published" datetime="<?php echo get_post_modified_time( 'c', false, $post->ID );?>" pubdate>
                <?php echo QAPress_format_date(get_post_modified_time( 'U', false, $post->ID ));?>
            </time>
            <?php if($post->post_mime_type){
                $_user = get_user_by( 'id', $post->post_mime_type ); ?>
                <span class="topic-last-reply"><?php _e('Last reply from ', 'wpcom');?> <a href="<?php the_permalink();?>#answer" target="_blank"><?php echo $_user->display_name;?></a></span>
            <?php } ?>
            <div class="reply-count">
                <?php if((isset($qa_options['show_views']) && $qa_options['show_views']) || !isset($qa_options['show_views'])){ ?>
                <span class="count-of-visits"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-eye"></use></svg></i><?php echo $post->views?$post->views:0;?></span><?php } ?>
                <span class="count-of-replies"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-comment"></use></svg></i><?php echo $post->comment_count;?></span>
            </div>
        </div>
    </div>
</div>