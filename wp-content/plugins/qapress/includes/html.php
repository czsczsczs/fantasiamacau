<?php
add_shortcode("QAPress", "QAPress_render");
function QAPress_render(){
    global $wp_query, $current_cat;
    if( isset($wp_query->query['post_type']) && $wp_query->query['post_type'] == 'qa_post' ){
        return QAPress_single();
    }else{
        $page = isset($wp_query->query['qa_page']) && $wp_query->query['qa_page'] ? $wp_query->query['qa_page'] : 1;
        return QAPress_list($page);
    }
}

function QAPress_list( $page = 1 ){
    global $wp_query, $wpcomqadb, $qa_options, $current_cat;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    $per_page = isset($qa_options['question_per_page']) && $qa_options['question_per_page'] ? $qa_options['question_per_page'] : 20;
    $qa_cats = isset($qa_options['category']) && $qa_options['category'] ? $qa_options['category'] : array();
    $cat = isset($wp_query->query['qa_cat']) && $wp_query->query['qa_cat'] ? $wp_query->query['qa_cat'] : '';
    if(!$current_cat) $current_cat = $cat ? get_term_by('slug', $cat, 'qa_cat') : null;

    $list = $wpcomqadb->get_questions($per_page, $page, $current_cat?$current_cat->term_id:0);

    $new_page_id = $qa_options['new_page'];
    $new_url = get_permalink($new_page_id);

    $args = array(
        'current_cat' => $current_cat,
        'cats' => $qa_cats,
        'list' => $list,
        'per_page' => $per_page,
        'page' => $page,
        'new_url' => $new_url
    );
    return QAPress_template('list', $args);
}

function QAPress_single(){
    global $wpcomqadb, $wp_query, $qa_options, $post;
    $post_id = isset($wp_query->query['p']) ? $wp_query->query['p'] : $wp_query->query['qa_id'];
    if(!$post_id) return;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    $answers_per_page = isset($qa_options['answers_per_page']) && $qa_options['answers_per_page'] ? $qa_options['answers_per_page'] : 20;

    $post = get_post($post_id);

    if( ! ( $post && isset($post->ID) ) ){
        exit();
    }

    remove_filter('the_content', 'QAPress_single_content', 1);
    remove_filter('the_content', 'the_content_filter_images', 100 );

    $user = get_user_by('ID', $post->post_author);
    $author_name = $user->display_name ? $user->display_name : $user->user_nicename;
    if(class_exists('WPCOM_Member') && apply_filters( 'wpcom_member_show_profile' , true )){
        $url = get_author_posts_url( $user->ID );
        $author_name = '<a class="j-user-card" data-user="'.$user->ID.'" href="'.$url.'" target="_blank">'.$author_name.'</a>';
    }
    $answers_order = isset($qa_options['answers_order']) && $qa_options['answers_order']=='1' ? 'DESC' : 'ASC';
    $answers = $wpcomqadb->get_answers($post->ID, $answers_per_page, 1, $answers_order);
    $cat = get_the_terms($post->ID, 'qa_cat');
    $cat = isset($cat[0]) ? $cat[0] : '';

    $args = array(
        'post' => $post,
        'author_name' => $author_name,
        'cat' => $cat,
        'user' => $user,
        'answers' => $answers,
        'per_page' => $answers_per_page
    );
    return QAPress_template('single', $args);
}

add_action('template_redirect', 'QAPress_pre_process_shortcode');
function QAPress_pre_process_shortcode() {
    if (!is_singular('page')) return;
    global $post, $qa_options;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    if ( isset($qa_options['new_page']) && $qa_options['new_page'] == $post->ID && !is_user_logged_in() ) {
        $login_url = isset($qa_options['login_url']) && $qa_options['login_url'] ? $qa_options['login_url'] : wp_login_url();
        wp_redirect($login_url);
        exit;
    }
}

add_shortcode("QAPress-new", "QAPress_new_question");
function QAPress_new_question(){
    global $wpcomqadb, $qa_options, $pagenow;
    if($pagenow == 'post.php') return false;
    if(!isset($qa_options)) $qa_options = get_option('qa_options');

    $current_user =  wp_get_current_user();

    $category = '';
    $id = 0;
    $title = '';
    $content = '';

    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $id = isset($_GET['id']) ? $_GET['id'] : 0;

    $is_allowed = 1;
    if($type=='edit'){
        $question = $id ? $wpcomqadb->get_question($id) : '';
        if($question && ( $question->post_author==$current_user->ID || $current_user->has_cap( 'edit_others_posts' ) ) ) { // 问题存在，并比对用户权限
            $title = $question->post_title;
            $category = get_the_terms($question->ID, 'qa_cat');
            $category = $category[0]->term_id;
            $content = $question->post_content;
        }else{
            // 无权限
            $is_allowed = 0;
        }
    }

    if($is_allowed){
        $allow_img = isset($qa_options['question_img']) && $qa_options['question_img'] ? 1 : 0;
        $allow_link = isset($qa_options['question_link']) && $qa_options['question_link'] ? 1 : 0;
        ob_start();
        wp_editor( $content, 'editor-question', QAPress_editor_settings(array('textarea_name'=>'content', 'height'=>350, 'allow_img'=> $allow_img, 'allow_link'=> $allow_link)) );
        $editor_contents = ob_get_clean();

        $args = array(
            'id' => $id,
            'title' => $title,
            'category' => $category,
            'qa_cats' => QAPress_categorys(),
            'editor_contents' => $editor_contents
        );

        $html = QAPress_template('ask', $args);
    }else{
        $html = '<div style="text-align:center;padding: 50px 0;font-sisze: 14px;color:#666;">'.__('You do not have permission to access this page', 'wpcom').'</div>';
    }
    return $html;
}

function QAPress_pagination($per_page=20, $page=1, $cat=null){
    global $wpcomqadb;
    $total_q = $wpcomqadb->get_questions_total($cat?$cat->term_id:0);
    $numpages = ceil($total_q/$per_page);
    $range = 9;

    if($numpages>1){
        $cat_slug = $cat ? $cat->slug : '';

        $html = '<div class="q-pagination clearfix">';
            $prev = $page - 1;
            if ( $prev > 0 ) {
                $html .= '<a class="prev" href="'.QAPress_category_url($cat_slug, $prev).'"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-arrow-left"></use></svg></i></a>';
            }

            if($numpages > $range){
                if($page < $range){
                    for($i = 1; $i <= ($range + 1); $i++){
                        if($i==$page){
                            $html .= '<a class="current" href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                        } else {
                            $html .= '<a href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                        }
                    }
                } elseif($page >= ($numpages - ceil(($range/2)))){
                    for($i = $numpages - $range; $i <= $numpages; $i++){
                        if($i==$page){
                            $html .= '<a class="current" href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                        } else {
                            $html .= '<a href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                        }
                    }
                } elseif($page >= $range && $page < ($numpages - ceil(($range/2)))){
                    for($i = ($page - ceil($range/2)); $i <= ($page + ceil(($range/2))); $i++){
                        if($i==$page){
                            $html .= '<a class="current" href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                        } else {
                            $html .= '<a href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                        }
                    }
                }
            }else{
                for ( $i = 1; $i <= $numpages; $i++ ) {
                    if($i==$page){
                        $html .= '<a class="current" href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                    } else {
                        $html .= '<a href="'.QAPress_category_url($cat_slug, $i).'">' . $i . "</a>";
                    }
                }
            }

            $next = $page + 1;
            if ( $next <= $numpages ) {
                $html .= '<a class="next" href="'.QAPress_category_url($cat_slug, $next).'"><i class="wpcom-icon wi"><svg aria-hidden="true"><use xlink:href="#wi-arrow-right"></use></svg></i></a>';
            }
            $html .= '</div>';
        return $html;
    }
}
