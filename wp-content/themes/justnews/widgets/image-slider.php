<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_image_slider_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_image_slider';
        $this->widget_description = '幻灯片轮播展示';
        $this->widget_id = 'image-slider';
        $this->widget_name = '#轮播滑块';
        $this->settings = array(
            'slides' => array(
                'type' => 'rp',
                'name' => '滑块',
                'o' => array(
                    'title' => array(
                        'name' => '标题',
                        'desc' => '可选，可用作图片alt'
                    ),
                    'img' => array(
                        'name' => '图片',
                        'desc' => '不同滑块的图片建议长宽比例保持一致',
                        'type' => 'at'
                    ),
                    'url' => array(
                        'name' => '链接',
                        'desc' => '可选',
                        'type' => 'url'
                    )
                )
            ),
            'show_title' => array(
                'name' => '是否显示标题',
                'value' => '0',
                't' => 't'
            )
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();

        $show_title = empty( $instance['show_title'] ) ? $this->settings['show_title']['value'] :  $instance['show_title'];
        $imgs = isset($instance['img']) && $instance['img'] ? $instance['img'] : array();
        $titles = isset($instance['title']) && $instance['title'] ? $instance['title'] : array();
        $urls = isset($instance['url']) && $instance['url'] ? $instance['url'] : array();
        $slides = array();

        if(!empty($imgs)){
            foreach($imgs as $i => $img){
                $slides[] = array(
                    'img' => $img ? wp_get_attachment_url($img) : '',
                    'title' => isset($titles[$i]) ? $titles[$i] : '',
                    'url' => isset($urls[$i]) ? $urls[$i] : '',
                );
            }
        }

        $this->widget_start( $args, $instance );
        if(!empty($slides)){ ?>
            <div class="wpcom-slider swiper-container<?php echo $show_title ? ' show-title' : '';?>">
                <ul class="swiper-wrapper">
                    <?php foreach($slides as $slide){ ?>
                        <li class="swiper-slide">
                            <?php if($slide['url']){ ?>
                                <a class="slide-post-inner" <?php echo WPCOM::url($slide['url']); ?> title="<?php echo esc_attr($slide['title']);?>">
                                    <img src="<?php echo esc_url($slide['img']);?>" alt="<?php echo esc_attr($slide['title']);?>">
                                    <?php if($show_title) {?><span class="slide-post-title"><?php echo $slide['title'];?></span><?php } ?>
                                </a>
                            <?php }else{ ?>
                                <img src="<?php echo esc_url($slide['img']);?>" alt="<?php echo esc_attr($slide['title']);?>">
                                <?php if($show_title) {?><span class="slide-post-title"><?php echo $slide['title'];?></span><?php } ?>
                            <?php } ?>
                        </li>
                    <?php } ?>
                </ul>
                <div class="swiper-pagination"></div>
                <!-- Add Navigation -->
                <div class="swiper-button-prev"><?php WPCOM::icon('arrow-left-3');?></div>
                <div class="swiper-button-next"><?php WPCOM::icon('arrow-right-3');?></div>
            </div>
        <?php
        }
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function register_wpcom_image_slider_widget() {
    register_widget( 'WPCOM_image_slider_widget' );
}
add_action( 'widgets_init', 'register_wpcom_image_slider_widget' );