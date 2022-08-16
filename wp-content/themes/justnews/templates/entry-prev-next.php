<?php global $options, $post;
if(!isset($options['post_nextprev']) || (isset($options['post_nextprev']) && $options['post_nextprev'])){ ?>
    <div class="entry-page">
        <?php
        $in_same_term = isset($options['post_nextprev']) && $options['post_nextprev'] == '2';
        $pre = get_previous_post($in_same_term);
        $next = get_next_post($in_same_term);
        $use_rand = 0;
        if(($pre && !$next) || (!$pre && $next)){
            $parg = array(
                'post__not_in' => array($post->ID, $pre ? $pre->ID : $next->ID),
                'posts_per_page' => 1,
                'post_status' => 'publish',
                'orderby' => 'rand',
                'ignore_sticky_posts' => 1
            );
            // 缓存支持
            $cache_key = md5(maybe_serialize($parg));
            $cache = wp_cache_get( $cache_key, 'prev_next_post' );
            if($cache){
                $rand = $cache;
            }else{
                if($in_same_term){ // 同分类文章
                    $cat_list = array();
                    $categories = get_the_category($post->ID);
                    if($categories) {
                        foreach ($categories as $category) {
                            $cid = $category->term_id;
                            if (!in_array($cid, $cat_list)) {
                                $cat_list[] = $cid;
                            }
                        }
                    }
                    $parg['cat'] = join(',', $cat_list);
                }
                $posts = get_posts( $parg );
                if( $posts && isset($posts[0]) ) {
                    $rand = $posts[0];
                    wp_cache_set( $cache_key, $rand, 'prev_next_post', DAY_IN_SECONDS );
                }
            }

            if( isset($rand) ) {
                if(!$pre) {
                    $pre = $rand;
                    $use_rand = 1;
                }else{
                    $next = $rand;
                    $use_rand = 2;
                }
            }
        }
        if($pre){ $pbg = get_the_post_thumbnail_url($pre); ?>
            <div <?php echo $pbg ? wpcom_lazybg($pbg, 'entry-page-prev') : 'class="entry-page-prev entry-page-nobg"'; ?>>
                <a href="<?php echo get_the_permalink($pre);?>" title="<?php echo esc_attr(get_the_title($pre));?>" rel="prev">
                    <span><?php echo get_the_title($pre);?></span>
                </a>
                <div class="entry-page-info">
                    <span class="pull-left"><?php wpcom::icon($use_rand==1?'refresh':'arrow-left-double');?> <?php echo _x( 'Previous', 'single', 'wpcom' );?></span>
                    <span class="pull-right"><?php echo wpcom_format_date(get_post_time( 'U', false, $pre ));?></span>
                </div>
            </div>
        <?php } ?>
        <?php if($next){ $nbg = get_the_post_thumbnail_url($next); ?>
            <div <?php echo $nbg ? wpcom_lazybg($nbg, 'entry-page-next') : 'class="entry-page-next entry-page-nobg"'; ?>>
                <a href="<?php echo get_the_permalink($next);?>" title="<?php echo esc_attr(get_the_title($next));?>" rel="next">
                    <span><?php echo get_the_title($next);?></span>
                </a>
                <div class="entry-page-info">
                    <span class="pull-right"><?php echo _x( 'Next', 'single', 'wpcom' );?> <?php wpcom::icon($use_rand==2?'refresh':'arrow-right-double');?></span>
                    <span class="pull-left"><?php echo wpcom_format_date(get_post_time( 'U', false, $next ));?></span>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>