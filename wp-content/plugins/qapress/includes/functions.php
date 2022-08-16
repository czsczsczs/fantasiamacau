<?php

add_action( 'wp_enqueue_scripts', 'QAPress_scripts', 20 );
function QAPress_scripts() {
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    wp_enqueue_style( 'QAPress', QAPress_URI . 'css/style.css', array(), QAPress_VERSION );

    $color = isset($qa_options['color']) && $qa_options['color'] ? $qa_options['color'] : '#4285f4';
    $hover = isset($qa_options['color_hover']) && $qa_options['color_hover'] ? $qa_options['color_hover'] : '#3380ff';
    $custom_css = ':root{--qa-color: '.$color.';--qa-hover: '.$hover.';}';
    wp_add_inline_style( 'QAPress', $custom_css );

    // 载入js文件, 未注册jquery则注册，避免部分主题的奇葩行为
    if(!wp_script_is('jquery')) wp_register_script('jquery', includes_url('js/jquery/jquery.min.js'), array(), QAPress_VERSION);
    wp_enqueue_script( 'QAPress-js', QAPress_URI . 'js/scripts.js', array( 'jquery' ), QAPress_VERSION, true );

    $max_upload_size = isset($qa_options['max_upload_size']) && $qa_options['max_upload_size'] ? floatval($qa_options['max_upload_size']) : '2';
    $max_upload_size = $max_upload_size ? $max_upload_size * 1024 * 1024 : 0;
    $max_upload_size = $max_upload_size && $max_upload_size < wp_max_upload_size() ? $max_upload_size : wp_max_upload_size();
    $compress_img_size = 0;
    if(isset($qa_options['compress_img']) && $qa_options['compress_img'] && isset($qa_options['compress_img_size']) && $qa_options['compress_img_size']){
        $compress_img_size = $qa_options['compress_img_size'] ? intval($qa_options['compress_img_size']) : $compress_img_size;
    }

    wp_localize_script( 'QAPress-js', 'QAPress_js', array(
        'ajaxurl' => admin_url( 'admin-ajax.php'),
        'ajaxloading' => QAPress_URI . 'images/loading.gif',
        'max_upload_size' => $max_upload_size,
        'compress_img_size' => $compress_img_size,
        'lang' => array(
            'delete' => _x('Delete', 'qapress', 'wpcom'),
            'nocomment' => _x('No comments', 'qapress comment', 'wpcom'),
            'nocomment2' => _x('No comments', 'qapress', 'wpcom'),
            'addcomment' => _x('Add comment', 'qapress', 'wpcom'),
            'submit' => _x('Submit', 'qapress', 'wpcom'),
            'loading' => _x('Loading...', 'qapress', 'wpcom'),
            'error1' => _x('Parameter error, please try again later', 'qapress', 'wpcom'),
            'error2' => _x('Request failed, please try again later', 'qapress', 'wpcom'),
            'confirm' => _x('The delete action cannot be restored, are you sure you want to delete it?', 'qapress comment', 'wpcom'),
            'confirm2' => _x('The delete action cannot be restored, are you sure you want to delete it?', 'qapress', 'wpcom'),
            'confirm3' => _x('The delete action cannot be restored, are you sure you want to delete it?', 'qapress question', 'wpcom'),
            'deleting' => _x('Deleting...', 'qapress', 'wpcom'),
            'success' => _x('Success!', 'qapress', 'wpcom'),
            'denied' => _x('Permission denied!', 'qapress', 'wpcom'),
            'error3' => _x('Delete error, please try again later', 'qapress', 'wpcom'),
            'empty' => _x('Comment content cannot be empty', 'qapress', 'wpcom'),
            'submitting' => _x('Submitting...', 'qapress', 'wpcom'),
            'success2' => _x('Submit successfully!', 'qapress', 'wpcom'),
            'ncomment' => _x('No comments', 'qapress, 0 comment', 'wpcom'),
            'login' => _x('Sorry, you need to login first', 'qapress', 'wpcom'),
            'error4' => _x('Submission failed, please try again later', 'qapress', 'wpcom'),
            'need_title' => _x('Please enter your title', 'qapress', 'wpcom'),
            'need_cat' => _x('Please select your category', 'qapress', 'wpcom'),
            'need_content' => _x('Please enter your content', 'qapress', 'wpcom'),
            'success3' => _x('Updated successfully!', 'qapress', 'wpcom'),
            'success4' => _x('The question was posted successfully!', 'qapress', 'wpcom'),
            'need_all' => _x('Title, category and content cannot be empty', 'qapress', 'wpcom'),
            'length' => _x('The content length cannot be less than 10 characters', 'qapress', 'wpcom'),
            'load_done' => _x('Comments are all loaded', 'qapress', 'wpcom'),
            'load_fail' => _x('Load failed, please try again later', 'qapress', 'wpcom'),
            'load_more' => _x('Load more', 'qapress', 'wpcom'),
            'approve' => _x('Are you sure to set the current question as approved?', 'qapress', 'wpcom'),
            'end' => _x('- END -', 'qapress', 'wpcom'),
            'upload_fail' => _x('Image upload error, please try again later!', 'qapress', 'wpcom'),
            'file_types' => _x('Only support jpg, png, gif file types', 'qapress', 'wpcom'),
            'file_size' => sprintf(_x('Image size cannot exceed %sM', 'qapress', 'wpcom'), intval($max_upload_size / (1024 * 1024) * 100) / 100 ),
            'uploading' => _x('Uploading...', 'qapress', 'wpcom'),
            'upload' => _x('Insert Image', 'qapress', 'wpcom')
        )
    ) );
    wp_register_script('wpcom-icons', QAPress_URI . 'js/icons-2.6.18.js', array(), QAPress_VERSION, true);
    wp_enqueue_script('wpcom-icons');
}

add_action( 'init', 'QAPress_register_types' );
function QAPress_register_types() {
    global $QAPress, $qa_slug, $qa_options, $pagenow, $wp_version;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    load_plugin_textdomain( 'wpcom', false, basename( QAPress_DIR ) . '/lang' );

    if(!isset($qa_slug) || !$qa_slug ){
        $qa_page_id = isset($qa_options['list_page']) ? $qa_options['list_page'] : '';
        $qa_page = get_post($qa_page_id);
        $qa_slug = isset($qa_page->ID) ? $qa_page->post_name : '';
    }
    $labels = array(
        'name' => '问题',
        'singular_name' => '问题',
        'add_new' => '添加',
        'add_new_item' => '添加',
        'edit_item' => '编辑',
        'new_item' => '添加',
        'view_item' => '查看',
        'search_items' => '查找',
        'not_found' => '没有内容',
        'not_found_in_trash' => '回收站为空',
        'parent_item_colon' => ''
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'capability_type' => 'qa_post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => $qa_slug, 'with_front' => 0),
        'show_in_rest' => true,
        'show_in_menu' => $QAPress->is_active() && current_user_can('manage_options') ? 'QAPress' : (current_user_can('edit_others_qa_posts') ? true : ''),
        'supports' => array('title', 'editor', 'author', 'comments')
    );
    register_post_type('qa_post', $args);

    $is_hierarchical = $pagenow === 'edit.php' || ($pagenow === 'admin-ajax.php' && isset($_POST['action']) && $_POST['action'] === 'inline-save');
    register_taxonomy( 'qa_cat', null,
        array(
            'labels' => array(
                'add_new_item' => '添加分类',
                'edit_item' => '编辑分类',
                'update_item' => '更新分类'
            ),
            'public' => false,
            'show_ui' => true,
            'label' => '问答分类',
            'capabilities'          => array(
                'manage_terms' => 'manage_qa_cats',
                'edit_terms'   => 'edit_qa_cats',
                'delete_terms' => 'delete_qa_cats',
                'assign_terms' => 'assign_qa_cats',
            ),
            'show_in_rest' => true,
            'rewrite' => array(
                'slug' => $qa_slug
            ),
            'meta_box_cb' => 'post_categories_meta_box',
            'hierarchical' => $is_hierarchical || version_compare($wp_version, '5.1', '<') ? true : false
        )
    );

    register_taxonomy_for_object_type( 'qa_cat', 'qa_post' );
}

add_action('_admin_menu', 'QAPress_capabilities');
function QAPress_capabilities() {
    global $wp_roles;
    if ( isset($wp_roles) ) {
        $wp_roles->add_cap( 'administrator', 'edit_qa_post' );
        $wp_roles->add_cap( 'administrator', 'read_qa_post' );
        $wp_roles->add_cap( 'administrator', 'delete_qa_post' );
        $wp_roles->add_cap( 'administrator', 'publish_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_others_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_private_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'edit_published_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_published_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_private_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'delete_others_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'read_private_qa_posts' );
        $wp_roles->add_cap( 'administrator', 'manage_qa_cats' );
        $wp_roles->add_cap( 'administrator', 'edit_qa_cats' );
        $wp_roles->add_cap( 'administrator', 'delete_qa_cats' );
        $wp_roles->add_cap( 'administrator', 'assign_qa_cats' );

        $wp_roles->add_cap( 'editor', 'edit_qa_post' );
        $wp_roles->add_cap( 'editor', 'read_qa_post' );
        $wp_roles->add_cap( 'editor', 'delete_qa_post' );
        $wp_roles->add_cap( 'editor', 'publish_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_others_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_private_qa_posts' );
        $wp_roles->add_cap( 'editor', 'edit_published_qa_posts' );
        $wp_roles->add_cap( 'editor', 'delete_qa_posts' );
        $wp_roles->add_cap( 'editor', 'delete_published_qa_posts' );
        $wp_roles->add_cap( 'editor', 'delete_others_qa_posts' );
        $wp_roles->add_cap( 'editor', 'read_private_qa_posts' );
        $wp_roles->add_cap( 'editor', 'manage_qa_cats' );
        $wp_roles->add_cap( 'editor', 'edit_qa_cats' );
        $wp_roles->add_cap( 'editor', 'delete_qa_cats' );
        $wp_roles->add_cap( 'editor', 'assign_qa_cats' );

        $wp_roles->add_cap( 'author', 'edit_qa_post' );
        $wp_roles->add_cap( 'author', 'read_qa_post' );
        $wp_roles->add_cap( 'author', 'delete_qa_post' );
        $wp_roles->add_cap( 'author', 'publish_qa_posts' );
        $wp_roles->add_cap( 'author', 'edit_qa_posts' );
        $wp_roles->add_cap( 'author', 'assign_qa_cats' );

        $wp_roles->add_cap( 'contributor', 'edit_qa_post' );
        $wp_roles->add_cap( 'contributor', 'read_qa_post' );
        $wp_roles->add_cap( 'contributor', 'publish_qa_posts' );
        $wp_roles->add_cap( 'contributor', 'edit_qa_posts' );
        $wp_roles->add_cap( 'contributor', 'assign_qa_cats' );
        $wp_roles->add_cap( 'contributor', 'upload_files' );


        $wp_roles->add_cap( 'subscriber', 'edit_qa_post' );
        $wp_roles->add_cap( 'subscriber', 'read_qa_post' );
        $wp_roles->add_cap( 'subscriber', 'publish_qa_posts' );
        $wp_roles->add_cap( 'subscriber', 'edit_qa_posts' );
        $wp_roles->add_cap( 'subscriber', 'assign_qa_cats' );
        $wp_roles->add_cap( 'subscriber', 'upload_files' );
    }
}

add_filter('rest_pre_insert_qa_post', 'QAPress_pre_insert_qa_post');
function QAPress_pre_insert_qa_post($post){
    global $qa_options, $wpcomqadb;
    $post->post_status = 'publish';
    // 判断是否需要审核
    if( !current_user_can( 'edit_others_posts' ) ){
        $moderation = isset($qa_options['question_moderation']) ? $qa_options['question_moderation'] : 0;
        if( $moderation == '1' ){ // 第一次审核
            $user =  wp_get_current_user();
            $user_total = $wpcomqadb->get_questions_total_by_user($user->ID);
            $post->post_status = $user_total ? 'publish' : 'pending';
        }else if( $moderation == '2' ){ // 全部需要审核
            $post->post_status = 'pending';
        }
    }
    return $post;
}

add_filter('wp_insert_post_data', 'QAPress_insert_qa_post');
function QAPress_insert_qa_post($post){
    if($post && $post['post_type'] === 'qa_post' && $post['post_status'] === 'publish' && !current_user_can( 'edit_others_posts' )){
        global $qa_options, $wpcomqadb;
        $moderation = isset($qa_options['question_moderation']) ? $qa_options['question_moderation'] : 0;
        if( $moderation == '1' ){ // 第一次审核
            $user =  wp_get_current_user();
            $user_total = $wpcomqadb->get_questions_total_by_user($user->ID);
            $post['post_status'] = $user_total ? 'publish' : 'pending';
        }else if( $moderation == '2' ){ // 全部需要审核
            $post['post_status'] = 'pending';
        }
    }
    return $post;
}

add_filter('rest_prepare_qa_cat', 'QAPress_cat_for_editor', 10, 3);
function QAPress_cat_for_editor($response, $item, $request){
    if(isset($request['_fields']) && $request['_fields'] && $response->data && !isset($response->data['parent'])){
        $response->data['parent'] = $item->parent;
    }
    return $response;
}

add_filter( 'rest_prepare_taxonomy', 'QAPress_prepare_taxonomy', 10, 3 );
function QAPress_prepare_taxonomy( $response, $taxonomy, $request ){
    $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
    if( $context === 'edit' && $taxonomy->name == 'qa_cat' && $taxonomy->hierarchical === false ){
        $data_response = $response->get_data();
        $data_response['hierarchical'] = true;
        $response->set_data( $data_response );
    }
    return $response;
}

add_action( 'admin_menu', 'QAPress_cat_menu');
function QAPress_cat_menu(){
    global $QAPress;
    if($QAPress->is_active() && current_user_can('manage_options')){
        add_submenu_page('QAPress', '问题分类', '问题分类', 'edit_theme_options', 'edit-tags.php?taxonomy=qa_cat', null);
        add_submenu_page('QAPress', '问答设置', '问答设置', 'edit_theme_options', 'admin.php?page=QAPress', null);
    }
}

add_filter('manage_edit-qa_cat_columns', 'QAPress_remove_column' );
function QAPress_remove_column( $columns ){
    unset($columns['posts']);
    return $columns;
}

add_filter('body_class', 'QAPress_body_class' );
function QAPress_body_class( $classes ){
    global $qa_options, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(isset($qa_options['list_page']) && $qa_options['list_page'] && is_page($qa_options['list_page'])){
        if( isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' ){
            $classes[] = 'qapress qapress-single';
        }else{
            $classes[] = 'qapress qapress-list';
        }
    }else if(isset($qa_options['new_page']) && $qa_options['new_page'] && is_page($qa_options['new_page'])){
        $classes[] = 'qapress qapress-new';
    }
    return $classes;
}

// add_action('admin_head', 'QAPress_remove_cat_fileds');
// function QAPress_remove_cat_fileds(){
//     remove_all_actions( 'qa_cat_add_form_fields' );
//     remove_all_actions( 'qa_cat_edit_form_fields' );
//     remove_all_actions( 'created_qa_cat' );
//     remove_all_actions( 'edited_qa_cat' );
// }

add_filter( 'parent_file', 'QAPress_parent_file' );
function QAPress_parent_file( $parent_file='' ){
    global $pagenow;
    if ( !empty($_GET['taxonomy']) && ($_GET['taxonomy'] == 'qa_cat') && ($pagenow == 'edit-tags.php'||$pagenow == 'term.php') ) {
        $parent_file = 'QAPress';
    }
    return $parent_file;
}

add_filter( 'submenu_file', 'QAPress_submenu_file' );
function QAPress_submenu_file( $submenu_file='' ){
    global $pagenow;
    $screen = get_current_screen();
    if ( $pagenow == 'admin.php' && $screen->base == 'toplevel_page_QAPress' ) {
        $submenu_file = 'admin.php?page=QAPress';
    }
    return $submenu_file;
}

add_filter( 'wpcom_init_plugin_options', 'QAPress_cats' );
function QAPress_cats($res){
    require_once WPCOM_ADMIN_PATH . 'includes/class-utils.php';
    if(isset($res['plugin-slug']) && $res['plugin-slug'] == $GLOBALS['QAPress']->plugin_slug){
        $res['qa_cat'] = WPCOM_ADMIN_UTILS::category('qa_cat');
    }
    return $res;
}

add_filter( 'get_avatar_comment_types', 'QAPress_avatar_comment_types' );
function QAPress_avatar_comment_types($types){
    $types[] = 'answer';
    $types[] = 'qa_comment';
    return $types;
}

add_action('wp_insert_comment', 'QAPress_update_count', 10, 2);
function QAPress_update_count($id, $comment){
    global $wpdb, $wpcomqadb;
    if($id && $comment->comment_type === 'qa_comment'){
        $cms_total = count($wpcomqadb->get_comments($comment->comment_parent));
        $wpdb->update($wpdb->comments, array( 'comment_karma' => $cms_total ), array('comment_ID' => $comment->comment_parent));
    }else if($comment->comment_type === '' && $comment->comment_parent && wp_doing_ajax() && isset($_POST['action']) && ($_POST['action'] === 'replyto-comment'||$_POST['action'] === 'delete-comment'||$_POST['action'] === 'dim-comment')){
        $p = get_comment($comment->comment_parent);
        if($p->comment_type==='answer'){
            $cms_total = count($wpcomqadb->get_comments($comment->comment_parent));
            $wpdb->update($wpdb->comments, array( 'comment_karma' => $cms_total ), array('comment_ID' => $comment->comment_parent));
        }
    }
}

add_action( 'transition_comment_status', 'QAPress_update_count_delete', 10, 3 );
function QAPress_update_count_delete($new_status, $old_status, $comment){
    QAPress_update_count($comment->comment_ID, $comment);
}

function QAPress_format_date($time){
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    if(isset($qa_options['time_format']) && $qa_options['time_format']=='0'){
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $time);
    }

    $t = current_time('timestamp') - $time;
    $f=array(
        '31536000'=>'y',
        '2592000'=>'m',
        '604800'=>'w',
        '86400'=>'d',
        '3600'=>'h',
        '60'=>'f',
        '1'=>'s'
    );
    if($t<=0){
        return __('1 second ago', 'wpcom');
    }
    foreach ($f as $k=>$v){
        if (0 !=$c=floor($t/(int)$k)) {
            break;
        }
    }
    $types = array(
        'y' => sprintf( _n( '%s year ago', '%s years ago', $c, 'wpcom' ), $c ),
        'm' => sprintf( _n( '%s month ago', '%s months ago', $c, 'wpcom' ), $c ),
        'w' => sprintf( _n( '%s week ago', '%s weeks ago', $c, 'wpcom' ), $c ),
        'd' => sprintf( _n( '%s day ago', '%s days ago', $c, 'wpcom' ), $c ),
        'h' => sprintf( _n( '%s hour ago', '%s hours ago', $c, 'wpcom' ), $c ),
        'f' => sprintf( _n( '%s min ago', '%s mins ago', $c, 'wpcom' ), $c ),
        's' => sprintf( _n( '%s second ago', '%s seconds ago', $c, 'wpcom' ), $c ),
    );
    if($v) return $types[$v];
}

function QAPress_category( $post ){
    $cats = get_the_terms($post->ID, 'qa_cat');

    if($cats){
        return $cats[0]->name;
    }
}

function QAPress_categorys(){
    // WP 4.5+
    $terms = get_terms( array(
            'taxonomy' => 'qa_cat',
            'hide_empty' => false
        )
    );

    return $terms;
}


add_filter( 'wp_title_parts', 'QAPress_title_parts', 5 );
function QAPress_title_parts( $parts ){
    global $qa_options, $current_cat;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(isset($qa_options['list_page']) && $qa_options['list_page'] && is_page($qa_options['list_page'])){
        global $wp_query, $post, $wpcomqadb;
        if( is_singular('qa_post') ){
            $parts[] = $post->post_title;
        }else if(isset($wp_query->query['qa_cat']) && $wp_query->query['qa_cat']){
            if(!$current_cat) $current_cat = get_term_by('slug', $wp_query->query['qa_cat'], 'qa_cat');
            $seo_title = $current_cat ? get_term_meta($current_cat->term_id, 'wpcom_seo_title', true) : '';
            if($seo_title){
                $parts = array($seo_title);
            }else{
                $parts[] = $current_cat ? $current_cat->name : '';
            }
        }

        if(isset($wp_query->query['qa_page']) && $wp_query->query['qa_page']){
            array_unshift($parts, sprintf(__('Page %s', 'wpcom'), $wp_query->query['qa_page']));
        }
    }
    return $parts;
}

// 兼容 Yoast SEO 插件
add_filter( 'wpseo_replacements', 'QAPress_wpseo_cat_title' );
function QAPress_wpseo_cat_title($replacements){
    global $qa_options, $wp_query, $current_cat;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(isset($qa_options['list_page']) && $qa_options['list_page'] && is_page($qa_options['list_page']) && isset($wp_query->query['qa_cat']) && $wp_query->query['qa_cat']){
        if(!$current_cat) $current_cat = get_term_by('slug', $wp_query->query['qa_cat'], 'qa_cat');
        if($current_cat && isset($replacements['%%title%%'])) $replacements['%%title%%'] = $current_cat->name;
    }
    return $replacements;
}

function QAPress_editor_settings($args = array()){
    add_filter( 'user_can_richedit' , '__return_true', 100 );
    $allow_img = isset($args['allow_img']) && $args['allow_img'] ? 1 : 0;
    $allow_link = isset($args['allow_link']) && $args['allow_link'] ? 1 : 0;
    return array(
        'textarea_name' => $args['textarea_name'],
        'media_buttons' => false,
        'quicktags' => false,
        'tinymce' => array(
            'statusbar' => false,
            'height'        => isset($args['height']) ? $args['height'] : 120,
            'toolbar1' => 'bold,italic,underline,blockquote,bullist,numlist'.($allow_link?',link':'').($allow_img?',QAImg':''),
            'toolbar2' => '',
            'toolbar3' => '',
            'paste_as_text' => true,
            'content_css' => QAPress_URI . 'css/tinymce-style.css?ver=' . QAPress_VERSION,
            'external_plugins' => $allow_img ? '{QAImg: "' . QAPress_URI . 'js/QAImg.js"}' : '{}'
        )
    );
}

function QAPress_mail( $to, $subject, $content ){
    $html = '<p>'. __('Dear User,', 'wpcom') .'</p>';
    $html .= $content;
    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($to, $subject, $html, $headers);
}

add_filter( 'wpcom_profile_tabs', 'QAPress_add_profile_tabs' );
function QAPress_add_profile_tabs( $tabs ){
    $tabs += array(
        25 => array(
            'slug' => 'questions',
            'title' => __('Q&A', 'wpcom')
        )
    );
    return $tabs;
}

add_action( 'pre_get_comments', 'QAPress_pre_get_comments', 10 );
function QAPress_pre_get_comments( $q ) {
    if( !(is_admin() && ! wp_doing_ajax()) && !$q->query_vars['type'] && !$q->query_vars['parent'] ){
        $q->query_vars['type__not_in'] = array('answer', 'qa_comment');
    }
    return $q;
}

add_action('wpcom_profile_tabs_questions', 'QAPress_questions');
function QAPress_questions() {
    global $profile, $wpcomqadb, $current_user;
    $all_cats = QAPress_categorys();
    $questions = $wpcomqadb->get_questions_by_user($profile->ID, 10, 1);
    $q_total = $wpcomqadb->get_questions_total_by_user($profile->ID);
    $q_numpages = ceil($q_total/10);

    $answers = $wpcomqadb->get_answers_by_user($profile->ID, 10, 1);
    $a_total = $wpcomqadb->get_answers_total_by_user($profile->ID);
    $a_numpages = ceil($a_total/10);

    $is_user = isset($current_user) && isset($current_user->ID) && $current_user->ID == $profile->ID;

    $empty_icon = function_exists('wpcom_empty_icon') ? wpcom_empty_icon('qa') : '';

    if($questions){
        $users_id = array();
        foreach($questions as $p){
            if(!in_array($p->user, $users_id)) $users_id[] = $p->user;
            if(!in_array($p->last_answer, $users_id)) $users_id[] = $p->last_answer;
        }

        $user_array = get_users(array('include'=>$users_id));
        $users = array();
        foreach($user_array as $u){
            $users[$u->ID] = $u;
        }
    }
    ?>
    <div class="profile-tab" data-user="<?php echo $profile->ID;?>">
        <div class="profile-tab-item active"><?php _e('Questions', 'wpcom');?></div>
        <div class="profile-tab-item"><?php _e('Answers', 'wpcom');?></div>
    </div>
    <div class="profile-tab-content active">
        <?php if($questions){ global $post; ?>
            <div class="q-content q-profile-list">
            <?php foreach ($questions as $post) {
                echo QAPress_template('list-item', array('post'=> $post));
            } ?>
            </div>
            <?php if($q_numpages>1) { ?><div class="load-more-wrap"><div class="btn load-more j-user-questions"><?php _ex('Load more', 'qapress', 'wpcom');?></div></div><?php } ?>
        <?php }else{ ?>
            <div class="profile-no-content">
                <?php echo $empty_icon; $is_user ? _e('You have not created any questions.', 'wpcom') : _e('This user has not created any questions.', 'wpcom');?>
            </div>
        <?php } ?>
    </div>
    <div class="profile-tab-content">
    <?php if($answers){ global $post;?>
        <ul class="profile-comments-list">
        <?php foreach($answers as $answer){ $post = $wpcomqadb->get_question($answer->comment_post_ID);?>
            <li class="comment-item">
                <div class="comment-item-meta">
                    <span><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-comments-fill"></use></svg></i><?php echo QAPress_format_date(strtotime($answer->comment_date));?> <?php _e('answered', 'wpcom');?> <a target="_blank" href="<?php echo get_permalink($post->ID);?>"><?php echo get_the_title($post->ID);?></a></span>
                </div>
                <div class="comment-item-link">
                    <a target="_blank" href="<?php echo esc_url(get_permalink($post->ID));?>#answer">
                        <?php $excerpt = wp_trim_words( $answer->comment_content, 150, '...' ); echo $excerpt ? $excerpt : __('(Filtered content)', 'wpcom'); ?>
                    </a>
                </div>
            </li>
        <?php } ?>
        </ul>
        <?php if($a_numpages>1) { ?>
            <div class="load-more-wrap"><div class="btn load-more j-user-answers"><?php _ex('Load more', 'qapress', 'wpcom');?></div></div>
        <?php } ?>
        <?php }else{ ?>
            <div class="profile-no-content">
                <?php echo $empty_icon; $is_user ? _e('You have not answered any questions.', 'wpcom') : _e('This user has not answered any questions.', 'wpcom');?>
            </div>
        <?php } ?>
    </div>
<?php }

add_action( 'wp_ajax_QAPress_user_questions', 'QAPress_user_questions' );
add_action( 'wp_ajax_nopriv_QAPress_user_questions', 'QAPress_user_questions' );
function QAPress_user_questions(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
        global $wpcomqadb;
        $page = $_POST['page'];
        $page = $page ? $page : 1;
        $all_cats = QAPress_categorys();
        $questions = $wpcomqadb->get_questions_by_user($user->ID, 10, $page);
        if($questions){
            global $post;
            foreach($questions as $post){
                echo QAPress_template('list-item', array('post'=> $post));
            }
        }else{ echo 0; }
    }
    exit;
}

add_action( 'wp_ajax_QAPress_user_answers', 'QAPress_user_answers' );
add_action( 'wp_ajax_nopriv_QAPress_user_answers', 'QAPress_user_answers' );
function QAPress_user_answers(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
        global $wpcomqadb;
        $page = $_POST['page'];
        $page = $page ? $page : 1;
        $answers = $wpcomqadb->get_answers_by_user($user->ID, 10, $page);

        if($answers){
            global $post;
            foreach($answers as $answer){ $post = $wpcomqadb->get_question($answer->comment_post_ID);?>
                <li class="comment-item">
                    <div class="comment-item-meta">
                        <span><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-comments-fill"></use></svg></i><?php echo QAPress_format_date(strtotime($answer->comment_date));?> <?php _e('answered', 'wpcom');?> <a target="_blank" href="<?php echo get_permalink($post->ID);?>"><?php echo get_the_title($post->ID);?></a></span>
                    </div>
                    <div class="comment-item-link">
                        <a target="_blank" href="<?php echo esc_url(get_permalink($post->ID));?>#answer">
                            <?php $excerpt = wp_trim_words( $answer->comment_content, 150, '...' ); echo $excerpt ? $excerpt :  __('(Filtered content)', 'wpcom');?>
                        </a>
                    </div>
                </li>
            <?php }
        }else{ echo 0; }
    }
    exit;
}

add_filter( 'user_can_richedit', 'wpcom_can_richedit' );
if ( ! function_exists( 'wpcom_can_richedit' ) ) {
    function wpcom_can_richedit( $wp_rich_edit ){
        global $is_IE;
        if( !$wp_rich_edit && $is_IE && !is_admin() ){
            $wp_rich_edit = 1;
        }
        return $wp_rich_edit;
    }
}

add_filter( 'pre_wp_update_comment_count_now', 'QAPress_update_comment_count', 10, 3 );
function QAPress_update_comment_count( $count, $old, $post_id ){
    global $wpdb;
    if ( !$post = get_post($post_id) ) return $count;
    if($post->post_type=='qa_post'){
        $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_approved = '1' AND comment_parent = '0'", $post_id ) );
    }
    return $count;
}

// 用于关闭主题默认的评论框
add_filter( 'comments_open', 'QAPress_single_comments_open' );
function QAPress_single_comments_open( $open ) {
    global $qa_options, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if( (isset($qa_options['list_page']) && is_page($qa_options['list_page'])) || (isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post') ) {
        $open = false;
    }
    return $open;
}

// 用于head结束后将wp_query设置为问答页面，主要用于面包屑导航、边栏等的获取与问答列表页面一致
add_action( 'wp_head', 'QAPress_single_use_page_tpl', 99999 );
function QAPress_single_use_page_tpl(){
    global $qa_options, $post, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if( $wp_query->is_main_query() && is_singular('qa_post') ) {
        $post = get_post($qa_options['list_page']);
        $wp_query->is_page = 1;
        $wp_query->is_single = 0;
        $wp_query->query['qa_id'] = $wp_query->queried_object_id;
        $wp_query->queried_object_id = $qa_options['list_page'];
        $wp_query->queried_object = $post;
        $wp_query->posts[0] = $post;
    }
}

// 用于问题正文，重置$post为问题本身
add_action( 'loop_start', 'QAPress_loop_start' );
function QAPress_loop_start(){
    global $qa_options, $post, $wp_query;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if( $wp_query->is_main_query() && isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' ) {
        $qa_page_id = $qa_options['list_page'];
        $post = get_post($qa_page_id);
    }
}

// 用于重置工具条编辑链接
add_action( 'wp_footer', 'QAPress_wp_footer', 1 );
function QAPress_wp_footer(){
    global $wp_query, $post;
    if ( isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' && isset($wp_query->query['qa_id']) ) {
        remove_filter('the_content', 'QAPress_single_content', 1);
        $post = get_post($wp_query->query['qa_id']);
        $wp_query->is_page = 0;
        $wp_query->is_single = 1;
        $wp_query->queried_object = $post;
        $wp_query->queried_object_id = $wp_query->query['qa_id'];
        $wp_query->posts[0] = $post;
    }
}

// 后台按时间排序
add_action('pre_get_posts', 'QAPress_admin_order');
function QAPress_admin_order( $q ) {
    if(is_admin() && function_exists('get_current_screen')){
        $s = get_current_screen();
        if ( isset($s->base) && $s->base === 'edit' && isset($s->post_type) && $s->post_type === 'qa_post' && $q->is_main_query() ) {
            if( !isset($_GET[ 'orderby' ]) ) {
                $q->set('orderby', 'date');
                $q->set('order', 'desc');
            }
        }
    }
}

add_filter('the_comments', 'QAPress_admin_comments' );
function QAPress_admin_comments($comments){
    global $pagenow;
    if( is_admin() && $pagenow=='index.php' ){
        if($comments){
            foreach ($comments as $k => $comment) {
                if( $comment->comment_type=='answer' || $comment->comment_type=='qa_comment' ){
                    $comments[$k]->comment_type = '';
                }
            }
        }
    }
    return $comments;
}

add_action('wp_insert_comment', 'QAPress_comments_count', 10, 2);
function QAPress_comments_count($comment_ID, $comment){
    if($comment_ID && $comment->user_id) {
        if($comment->comment_type==='answer'){
            if($comment->comment_approved == 1){
                global $wpdb;
                $last_modified = current_time('mysql');
                $last_modified_gmt = get_gmt_from_date($last_modified);
                $wpdb->query("UPDATE $wpdb->posts SET post_mime_type = '$comment->user_id', post_modified = '$last_modified', post_modified_gmt = '$last_modified_gmt'  WHERE ID = $comment->comment_post_ID" );
            }
        }
    }
}

add_action('transition_comment_status', 'QAPress_comments_count_status', 10, 3);
function QAPress_comments_count_status($new_status, $old_status, $comment){
    global $wpcomqadb, $wpdb;
    if($comment->comment_type==='answer' && $comment->user_id){
        $answers = $wpcomqadb->get_answers($comment->comment_post_ID, 1, 1, 'DESC');
        if($answers && isset($answers[0]->user_id)){
            $last_answer = $answers[0]->user_id;
            $last_modified = $answers[0]->comment_date;
        }else{
            $q = get_post($comment->comment_post_ID);
            $last_answer = '';
            $last_modified = $q->post_date;
        }

        $last_modified_gmt = get_gmt_from_date($last_modified);
        $wpdb->query("UPDATE $wpdb->posts SET post_mime_type = '$last_answer', post_modified = '$last_modified', post_modified_gmt = '$last_modified_gmt'  WHERE ID = $comment->comment_post_ID" );

    }
}

function QAPress_http_request($url, $body=array(), $method='GET'){
    $result = wp_remote_request($url, array('method' => $method, 'body'=>$body));
    if( is_array($result) ){
        $json_r = json_decode($result['body'], true);
        if( !$json_r ){
            parse_str($result['body'], $json_r);
            if( count($json_r)==1 && current($json_r)==='' ) return $result['body'];
        }
        return $json_r;
    }
}

add_action('save_post', 'QAPress_auto_keyword', 10, 3);
function QAPress_auto_keyword($post_ID, $post, $update){
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    if(isset($qa_options['related_by']) && $qa_options['related_by'] =='1' &&
        isset($qa_options['nlp_sid']) && $qa_options['nlp_sid'] && $qa_options['nlp_skey'] &&
        $update && $post->post_type === 'qa_post' && $post->post_status === 'publish'
    ){
        $data = QAPress_formatRequestData('KeywordsExtraction', array('Text' => get_the_title($post->ID) . '。' . $post->post_content), 'post');
        $tags = QAPress_http_request('https://nlp.tencentcloudapi.com/', $data, 'POST');
        $_tags = array();
        if(isset($tags['Response']) && isset($tags['Response']['Keywords']) && $tags['Response']['Keywords']){
            foreach ($tags['Response']['Keywords'] as $word) {
                $_tags[] = $word['Word'];
            }
        }
        update_post_meta($post_ID, '_qa_tags', !empty($_tags) ? implode(',', $_tags) : '0');
    }
}

function QAPress_related($id, $num = 10){
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    $related_by = $qa_options['related_by'] ? $qa_options['related_by'] : 0;
    if($related_by){ // 按标签匹配
        $tags = get_post_meta($id, '_qa_tags', true);
        if($tags){
            global $wpdb;
            $tags = explode(',', $tags);
            $sql = "SELECT * FROM $wpdb->posts  WHERE 1=1  AND ID NOT IN ($id) AND post_type='qa_post' AND post_status='publish' AND (";
            foreach ($tags as $i => $tag) {
                $sql .= ($i===0?'':'OR ') . "post_title LIKE '%$tag%' OR post_content LIKE '%$tag%' ";
            }
            $sql .= ") order by rand() limit $num";
            $posts = $wpdb->get_results($sql);
            return $posts;
        }else if($tags === ''){
            $post = get_post($id);
            QAPress_auto_keyword($id, $post, true);
        }
    }else{ // 按分类匹配
        $cat = get_the_terms($id, 'qa_cat');
        $cat = isset($cat[0]) ? $cat[0] : '';
        $args = array(
            'post_type' => 'qa_post',
            'post__not_in' => array($id),
            'showposts' => $num,
            'ignore_sticky_posts' => 1,
            'orderby' => 'rand',
            'post_status' => 'publish'
        );
        if($cat){
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'qa_cat',
                    'field'    => 'slug',
                    'terms'    => $cat->slug,
                )
            );
        }
        $posts = get_posts($args);
        return $posts;
    }
}

function QAPress_formatRequestData($action, $request, $reqMethod){
    global $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');
    $param = $request;
    $param["Action"] = ucfirst($action);
    $param["RequestClient"] = 'SDK_PHP_3.0.229';
    $param["Nonce"] = rand();
    $param["Timestamp"] = time();
    $param["Version"] = '2019-04-08';

    $param["SecretId"] = isset($qa_options['nlp_sid']) ? $qa_options['nlp_sid'] : '';

    $param["Region"] = 'ap-guangzhou';


    $signStr = QAPress_formatSignString('nlp.tencentcloudapi.com', '/', $param,  $reqMethod);
    $param["Signature"] = base64_encode(hash_hmac('SHA1', $signStr, isset($qa_options['nlp_skey']) ? $qa_options['nlp_skey'] : '', true));
    return $param;
}

function QAPress_formatSignString($host, $uri, $param, $requestMethod){
    $tmpParam = [];
    ksort($param);
    foreach ($param as $key => $value) {
        array_push($tmpParam, $key . "=" . $value);
    }
    $strParam = join ("&", $tmpParam);
    $signStr = strtoupper($requestMethod) . $host . $uri ."?".$strParam;
    return $signStr;
}

function QAPress_template( $template, $atts = array() ) {
    if (file_exists(STYLESHEETPATH . '/qapress/' . $template . '.php')) {
        $file = STYLESHEETPATH . '/qapress/' . $template . '.php';;
    }else{
        $file = QAPress_DIR . 'templates/' . $template . '.php';
    }

    if ( file_exists( $file ) ) {
        extract($atts);
        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}

add_action( 'qapress_echo_ad', 'QAPress_echo_ad', 10, 1);
function QAPress_echo_ad($id){
    if($id) {
        global $qa_options;
        if(!isset($qa_options)) $qa_options = get_option('qa_options');
        $html = '';
        if( wp_is_mobile() && isset($qa_options[$id.'_mobile']) && $qa_options[$id.'_mobile']!=='' ) {
            if(trim($qa_options[$id.'_mobile'])){
                $html = '<div class="qa_ad_wrap '.$id.'">';
                $html .= $qa_options[$id.'_mobile'];
                $html .= '</div>';
            }
        } else if ( isset($qa_options[$id]) && $qa_options[$id] ) {
            $html = '<div class="qa_ad_wrap '.$id.'">';
            $html .= $qa_options[$id];
            $html .= '</div>';
        }

        echo $html;
    }
}

// 2.0 数据迁移
add_action( 'admin_menu', 'QAPress_post_2_0' );
function QAPress_post_2_0(){
    global $wpdb;
    $table_q = $wpdb->prefix.'wpcom_questions';
    $table_a = $wpdb->prefix.'wpcom_answers';
    $table_c = $wpdb->prefix.'wpcom_comments';

    if( $wpdb->get_var("SHOW TABLES LIKE '$table_q'") != $table_q ) return false;

    $sql = "SELECT * FROM `$table_q` WHERE `flag` > -1 OR `flag` is null";
    $questions = $wpdb->get_results($sql);

    if($questions){
        foreach ($questions as $question) {
            $post = array(
                'post_author' => $question->user,
                'post_date' => $question->date,
                'post_modified' => $question->modified,
                'post_content' => $question->content,
                'post_title' => $question->title,
                'menu_order' => $question->flag ? $question->flag : 0,
                'comment_count' => $question->answers,
                'post_mime_type' => $question->last_answer,
                'post_status' => 'publish',
                'post_type' => 'qa_post',
                'comment_status' => 'open',
            );
            // 插入文章
            $pid = wp_insert_post($post);
            // 插入文章信息
            if($pid){
                update_post_meta($pid, 'views', $question->views);
                wp_set_object_terms( $pid, array( (int)$question->category ), 'qa_cat' );

                // 插入回答信息
                $answers = $wpdb->get_results("SELECT * FROM `$table_a` WHERE `question` = '$question->ID'");
                if($answers){
                    foreach ($answers as $answer) {
                        $user = get_user_by('ID', $answer->user);
                        $data = array(
                            'comment_post_ID' => $pid,
                            'comment_content' => $answer->content,
                            'comment_type' => 'answer',
                            'comment_parent' => 0,
                            'user_id' => $answer->user,
                            'comment_author_email' => $user->user_email,
                            'comment_author' => $user->display_name,
                            'comment_date' => $answer->date,
                            'comment_approved' => 1,
                            'comment_karma' => $answer->comments
                        );

                        $answer_id = wp_insert_comment($data);

                        // 插入评论信息
                        if($answer_id){
                            $comments = $wpdb->get_results("SELECT * FROM `$table_c` WHERE `answer` = '$answer->ID'");
                            if($comments){
                                foreach ($comments as $comment) {
                                    $cuser = get_user_by('ID', $comment->user);
                                    $data = array(
                                        'comment_post_ID' => $pid,
                                        'comment_content' => $comment->content,
                                        'comment_type' => 'qa_comment',
                                        'comment_parent' => $answer_id,
                                        'user_id' => $comment->user,
                                        'comment_author_email' => $cuser->user_email,
                                        'comment_author' => $cuser->display_name,
                                        'comment_date' => $comment->date,
                                        'comment_approved' => 1
                                    );

                                    wp_insert_comment($data);
                                }
                            }
                        }
                    }
                }
                $wpdb->update($table_q, array('flag' => -($pid)), array('ID' => $question->ID));
            }
        }
    }
}

// 2.3 评论字段修改
add_action( 'admin_menu', 'QAPress_comment_2_3' );
function QAPress_comment_2_3(){
    global $wpdb;
    if(get_option('_QAPress_2_3')) return false;

    $table_c = $wpdb->prefix.'comments';
    $sql = "SELECT * FROM `$table_c` WHERE `comment_type`='comment' AND `comment_parent`>0 AND `comment_approved`=1";
    $comments = $wpdb->get_results($sql);
    if($comments){
        foreach ($comments as $comment) {
            if($comment->comment_post_ID && $post = get_post($comment->comment_post_ID)){
                if($post->post_type=='qa_post'){
                    $wpdb->update($table_c, array('comment_type' => 'qa_comment'), array('comment_ID' => $comment->comment_ID));
                }
            }
        }
        update_option('_QAPress_2_3', '1');
    }
}