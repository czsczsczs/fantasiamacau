<?php
defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'WWA_rest_routes', 100 );
function WWA_rest_routes(){
    global $WWA;
    if($WWA->is_active()){
    	require_once WWA_DIR . 'includes/class-api-config.php';
    	$config = new WWA_REST_Config_Controller();
        $config->register_routes();
        $config2 = new WWA_REST_Config2_Controller();
        $config2->register_routes();

        require_once WWA_DIR . 'includes/class-api-posts.php';
    	$posts = new WWA_REST_Posts_Controller('post');
        $posts->register_routes();
        $pages = new WWA_REST_Posts_Controller('page');
        $pages->register_routes();
        if(post_type_exists('qa_post')){
            $qa_posts = new WWA_REST_Posts_Controller('qa_post');
            $qa_posts->register_routes();
        }
        if(post_type_exists('kuaixun')){
            $kuaixun = new WWA_REST_Posts_Controller('kuaixun');
            $kuaixun->register_routes();
        }

        require_once WWA_DIR . 'includes/class-api-terms.php';
    	$category = new WWA_REST_Terms_Controller('category');
        $category->register_routes();
        if(taxonomy_exists('special')){
            $special = new WWA_REST_Terms_Controller('special');
            $special->register_routes();
        }
        if(post_type_exists('qa_post')){
            $qa_cat = new WWA_REST_Terms_Controller('qa_cat');
            $qa_cat->register_routes();
        }

    	require_once WWA_DIR . 'includes/class-api-login.php';
        $login = new WWA_REST_Login_Controller();
        $login->register_routes();
        $login2 = new WWA_REST_Login2_Controller();
        $login2->register_routes();

        require_once WWA_DIR . 'includes/class-api-like.php';
        $like = new WWA_REST_Like_Controller();
        $like->register_routes();
        $like2 = new WWA_REST_Like2_Controller();
        $like2->register_routes();

        require_once WWA_DIR . 'includes/class-api-post-likes.php';
        $likes = new WWA_REST_Post_Likes_Controller();
        $likes->register_routes();
        $likes2 = new WWA_REST_Post_Likes2_Controller();
        $likes2->register_routes();

        require_once WWA_DIR . 'includes/class-api-post-info.php';
        $post_info = new WWA_REST_Post_Info_Controller();
        $post_info->register_routes();
        $post_info2 = new WWA_REST_Post_Info2_Controller();
        $post_info2->register_routes();

        require_once WWA_DIR . 'includes/class-api-zan.php';
        $zan = new WWA_REST_Zan_Controller();
        $zan->register_routes();
        $zan2 = new WWA_REST_Zan2_Controller();
        $zan2->register_routes();

        require_once WWA_DIR . 'includes/class-api-comments.php';
        $myc = new WWA_REST_Comments_Controller();
        $myc->register_routes();
        $myc2 = new WWA_REST_MYComments_Controller();
        $myc2->register_routes();

        require_once WWA_DIR . 'includes/class-api-qacomments.php';
        $qac = new WWA_REST_QAComments_Controller();
        $qac->register_routes();
        $qac2 = new WWA_REST_QAComments2_Controller();
        $qac2->register_routes();

        require_once WWA_DIR . 'includes/class-api-media.php';
        $media = new WWA_REST_Media_Controller();
        $media->register_routes();
        $media2 = new WWA_REST_Media2_Controller();
        $media2->register_routes();

        require_once WWA_DIR . 'includes/class-api-poster.php';
        $poster = new WWA_REST_Poster_Controller();
        $poster->register_routes();
        $poster2 = new WWA_REST_Poster2_Controller();
        $poster2->register_routes();

        require_once WWA_DIR . 'includes/class-api-follow.php';
        $follow = new WWA_REST_Follow_Controller();
        $follow->register_routes();
        $follow2 = new WWA_REST_Follow2_Controller();
        $follow2->register_routes();

        require_once WWA_DIR . 'includes/class-api-video.php';
        $video = new WWA_REST_Video_Controller();
        $video->register_routes();
        $video2 = new WWA_REST_Video2_Controller();
        $video2->register_routes();

        require_once WWA_DIR . 'includes/class-api-siteinfo.php';
        $siteinfo = new WWA_REST_Siteinfo_Controller();
        $siteinfo->register_routes();
        $siteinfo2 = new WWA_REST_Siteinfo2_Controller();
        $siteinfo2->register_routes();
    }
}