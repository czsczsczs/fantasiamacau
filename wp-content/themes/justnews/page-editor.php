<?php
// TEMPLATE NAME: 文章投稿

$is_submit_page = 1;
$current_user = wp_get_current_user();
if(!$current_user->ID){
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

global $options;
$d = apply_filters('wpcom_update_post', array());
$post_id = isset($_GET['post_id'])?$_GET['post_id']:'';

if(!$post_id && !(isset($options['tougao_on']) && $options['tougao_on']=='1')) {
    wp_redirect(get_option('home'));
}

$item = $post_id ? get_post($post_id) : '';
$post_title = '';
$post_excerpt = '';
$post_content = '';
$post_category = array();
$post_tags = array();
$post_thumbnail_id = '';
$post_thumb = '';
if($item && isset($item->ID)){
    $post_title = $item->post_title;
    $post_excerpt = $item->post_excerpt;
    $post_content = $item->post_content;
    $tags = get_the_tags($item->ID);
    if($tags) {
        foreach ($tags as $tag) {
            $post_tags[] = $tag->name;
        }
    }
    $cats = get_the_category($item->ID);
    if($cats) {
        foreach ($cats as $cat) {
            $post_category[] = $cat->term_id;
        }
    }
    $post_thumbnail_id = get_post_thumbnail_id( $item->ID );
    $post_thumb = get_the_post_thumbnail($item->ID, 'full').'<div class="thumb-remove j-thumb-remove">×</div>';
}
get_header();?>
    <div class="wrap container">
        <?php if(!isset($_GET['post_id']) || ($post_id && $item && ($item->post_status=='draft' || $item->post_status=='pending' || ($item->post_status=='publish' && current_user_can('edit_published_posts')))) && $item->post_author==$current_user->ID){ ?>
        <form method="post" class="post-form" id="j-form">
            <?php if((isset($_POST['post-title']) || (isset($_GET['submit']) && $_GET['submit']=='true')) && $item && isset($item->ID)){ ?>
            <div style="margin: -10px 30px 20px;padding: 10px 20px 10px 15px;" class="alert alert-success alert-dismissible fade in" role="alert">
                <div style="right: -10px;top:0;" class="close" data-dismiss="alert" aria-label="Close"><?php WPCOM::icon('close');?></div>
                提交成功<?php echo $item->post_status=='pending'?'，請等待審核':'';?>！您可以<a target="_blank" href="<?php echo get_permalink($item->ID);?>">點擊此處</a>查看預覽或者返回<a
                    target="_blank" href="<?php echo get_author_posts_url( $current_user->ID );?>">我的文章列表</a>。
            </div>
            <?php } wp_nonce_field( 'wpcom_update_post', 'wpcom_update_post_nonce' ); ?>
            <input type="hidden" name="ID" value="<?php echo $post_id;?>">
            <div class="post-form-main">
                    <div class="pf-item row">
                        <div class="pf-item-label col-xs-24 col-sm-2"><label for="post-title">標題</label></div>
                        <div class="pf-item-input col-xs-24 col-sm-22">
                            <input type="text" class="form-control" maxlength="200" id="post-title" name="post-title" placeholder="在此輸入標題" value="<?php echo $post_title;?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="pf-item row">
                        <div class="pf-item-label col-xs-24 col-sm-2"><label for="post-content">摘要</label></div>
                        <div class="pf-item-input col-xs-24 col-sm-22">
                            <textarea id="post-excerpt" name="post-excerpt" class="form-control" rows="3" placeholder="摘要可選填"><?php echo $post_excerpt;?></textarea>
                        </div>
                    </div>
                    <div class="pf-item row">
                        <div class="pf-item-label col-xs-24 col-sm-2"><label for="post-content">正文</label></div>
                        <div class="pf-item-input col-xs-24 col-sm-22">
                            <?php wp_editor( $post_content, 'post-content', post_editor_settings(array('textarea_name'=>'post-content')) );?>
                        </div>
                    </div>
            </div>
            <div class="post-form-sidebar">
                <div class="pf-submit-wrap">
                    <button type="submit" class="btn btn-primary btn-block btn-lg pf-submit"><?php echo $post_id?'提交更新':'提交發布';?></button>
                </div>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3>分類</h3></div>
                    <div class="pf-side-input">
                        <?php
                        $cats = isset($options['tougao_cats']) && $options['tougao_cats'] ? $options['tougao_cats'] : array();
                        $dropdown = wp_dropdown_categories(array(
                            'include' => $cats,
                            'hierarchical' => 1,
                            'name' => 'post-category[]',
                            'id'=>'post-category',
                            'class' => 'form-control',
                            'select' => empty($post_category) ? '' : $post_category[0],
                            'echo' => false,
                            'hide_empty' => 0
                        ));
                        $dropdown = str_replace('<select ', '<select multiple="multiple" size="5" ', $dropdown);
                        if(empty($cats)){
                            $cats = array();
                            foreach(WPCOM::category() as $cid=>$name){
                                $cats[] = $cid;
                            }
                        }
                        if(empty($post_category) && !empty($cats)) $post_category[] = $cats[0];
                        foreach($cats as $cid){
                            if(in_array($cid, $post_category)) $dropdown = str_replace('value="'.$cid.'"', 'value="'.$cid.'" selected', $dropdown);
                        }
                        echo $dropdown;
                        ?>
                    </div>
                </div>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3>標籤</h3></div>
                    <div class="pf-side-input">
                        <ul id="tag-container"></ul>
                        <p class="pf-notice">即文章關鍵詞，使用回車換行鍵確定，可選填</p>
                    </div>
                </div>
                <?php if(current_user_can('upload_files')){ ?>
                <div class="pf-side-item">
                    <div class="pf-side-label"><h3>縮略圖</h3></div>
                    <div class="pf-side-input">
                        <div id="j-thumb-wrap" class="thumb-wrap"><?php echo $post_thumb;?></div>
                        <a class="thumb-selector j-thumb" href="javascript:;">設置縮略圖片</a>
                        <p class="pf-notice">文章縮略圖會顯示在文章列表，建議設置一下縮略圖</p>
                    </div>
                    <input type="hidden" name="_thumbnail_id" id="_thumbnail_id" value="<?php echo $post_thumbnail_id;?>">
                </div>
                <?php } ?>
            </div>
        </form>
        <?php }else{ ?>
            <div class="hentry">
                <p style="text-align:center;padding: 15px 0;font-size:16px;color:#999;">您無權限訪問此頁面！</p>
            </div>
        <?php } ?>
    </div>
    <script>
        var postTags = <?php echo json_encode($post_tags);?>;
    </script>
<?php get_footer();?>