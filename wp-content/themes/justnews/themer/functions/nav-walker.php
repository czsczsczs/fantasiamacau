<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * @see Walker::start_lvl()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param int $depth Depth of page. Used for padding.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
        $class_names = 'dropdown-menu';
        if ( $depth===0 ) {
            $class_names .= ' menu-item-wrap';
            if( isset($args->child_count) && $args->child_count > 1 ) $class_names .= ' menu-item-col-' . ($args->child_count<6?$args->child_count:5);
        }
		$output .= "\n$indent<ul class=\"".$class_names."\">\n";
	}

	/**
	 * @see Walker::start_el()
	 * @since 3.0.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $item Menu item data object.
	 * @param int $depth Depth of menu item. Used for padding.
	 * @param int $current_page Menu item ID.
	 * @param object $args
	 */
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        if ( in_array( 'current-menu-item', $classes ) ||  in_array( 'current-menu-ancestor', $classes ) ||  in_array( 'current-post-ancestor', $classes )) {
            $classes[] = 'active';
        }

        $unset_classes = array('current-menu-item', 'current-menu-ancestor', 'current_page_ancestor', 'current_page_item', 'current_page_parent', 'current-menu-parent');
        foreach( $classes as $k => $class ){
            if( in_array($class, $unset_classes) ) unset($classes[$k]);
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );

        if ( $depth===0 && ! empty( $item->style ) ) {
            $class_names .= ' menu-item-style menu-item-style' . esc_attr($item->style);
        }

        if ( ! empty( $item->image ) ) {
            $class_names .= ' menu-item-has-image';
        }

        if ( $args->has_children )
            $class_names .= ' dropdown';

        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $output .= $indent . '<li' . $class_names .'>';

        $atts = array();
        $atts['target'] = ! empty( $item->target )	? $item->target	: '';
        $atts['rel']    = ! empty( $item->xfn )		? $item->xfn	: '';

        // If item has_children add atts to a.
        if ( $args->has_children && $depth === 0 ) {
            $atts['href'] = ! empty( $item->url ) ? $item->url : '';
            $atts['class']			= 'dropdown-toggle';
        } else {
            $atts['href'] = ! empty( $item->url ) ? $item->url : '';
        }

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        $item_output = $args->before;

        /*
         * Glyphicons
         * ===========
         * Since the the menu item is NOT a Divider or Header we check the see
         * if there is a value in the attr_title property. If the attr_title
         * property is NOT null we apply it as the class name for the glyphicon.
         */
        if( trim($item->title) != '0' ) {
            if (!empty($item->attr_title))
                $item_output .= '<a' . $attributes . ' title="'.esc_attr($item->attr_title).'">';
            else
                $item_output .= '<a' . $attributes . '>';

            if (!empty($item->image)) {
                if(preg_match('/^(http:|https:|\/\/)/i', $item->image)){
                    $item_output .= wpcom_lazyimg($item->image, $item->title, '', '', 'menu-item-image');
                }else{
                    $item_output .= WPCOM::icon($item->image, false, 'menu-item-icon');
                }
            }

            $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
            $item_output .= '</a>';
        }

        $item_output .= $args->after;

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	/**
	 * Traverse elements to create list from elements.
	 *
	 * Display one element if the element doesn't have any children otherwise,
	 * display the element and its children. Will only traverse up to the max
	 * depth and no ignore elements under that depth.
	 *
	 * This method shouldn't be called directly, use the walk() method instead.
	 *
	 * @see Walker::start_el()
	 * @since 2.5.0
	 *
	 * @param object $element Data object
	 * @param array $children_elements List of elements to continue traversing.
	 * @param int $max_depth Max depth to traverse.
	 * @param int $depth Depth of current element.
	 * @param array $args
	 * @param string $output Passed by reference. Used to append additional content.
	 * @return null Null on failure with no changes to parameters.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        if ( ! $element )
            return;

        $id_field = $this->db_fields['id'];

        // Display this element.
        if ( is_object( $args[0] ) ) {
            $args[0]->has_children = !empty($children_elements[$element->$id_field]);
            if( $depth==0 && $args[0]->has_children ){
                $args[0]->child_count = count($children_elements[$element->$id_field ]);
            }
        }

        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
    }

	/**
	 * Menu Fallback
	 * =============
	 * If this function is assigned to the wp_nav_menu's fallback_cb variable
	 * and a manu has not been assigned to the theme location in the WordPress
	 * menu manager the function with display nothing to a non-logged in user,
	 * and will add a link to the WordPress menu manager if logged in as an admin.
	 *
	 * @param array $args passed from the wp_nav_menu function.
	 *
	 */
	public static function fallback( $args ) {
		if ( current_user_can( 'manage_options' ) ) {

			extract( $args );

			$fb_output = null;

			if ( $container ) {
				$fb_output = '<' . $container;

				if ( $container_id )
					$fb_output .= ' id="' . $container_id . '"';

				if ( $container_class )
					$fb_output .= ' class="' . $container_class . '"';

				$fb_output .= '>';
			}

			$fb_output .= '<ul';

			if ( $menu_id )
				$fb_output .= ' id="' . $menu_id . '"';

			if ( $menu_class )
				$fb_output .= ' class="' . $menu_class . '"';

			$fb_output .= '>';
			$fb_output .= '<li><a href="' . admin_url( 'nav-menus.php' ) . '">设置导航</a></li>';
			$fb_output .= '</ul>';

			if ( $container )
				$fb_output .= '</' . $container . '>';

			echo $fb_output;
		}
	}
}


add_filter( 'nav_menu_css_class', 'wpcom_nav_menu_css_class' );
function wpcom_nav_menu_css_class( $classes ){
    if($classes){
        $unset_classes = array('menu-item-type-post_type', 'menu-item-object-page', 'menu-item-object-category', 'menu-item-type-taxonomy', 'menu-item-object-custom', 'menu-item-type-custom', 'menu-item-has-children', 'page_item', 'menu-item-home');
        foreach( $classes as $k => $class ){
            if( in_array($class, $unset_classes) ) unset($classes[$k]);
        }
    }
    return $classes;
}


/**
 * 以下代码均用于后台菜单编辑
 */

add_filter( 'wp_edit_nav_menu_walker', 'wpcom_nav_walke_fun', 10 );
add_action( 'wp_update_nav_menu_item', 'wpcom_update_nav_menu_item', 20, 2 );
function wpcom_nav_walke_fun($walker){
    global $wpcom_panel;
    include_once FRAMEWORK_PATH . '/includes/nav-walker-edit.php';
    if($wpcom_panel->get_demo_config()) $walker = 'WPCOM_Nav_Walker_Edit';
    return $walker;
}

function wpcom_update_nav_menu_item( $menu_id, $menu_item_db_id ){
    if ( isset($_POST['menu-item-image']) && is_array( $_POST['menu-item-image']) ) {
        $image = isset($_POST['menu-item-image'][$menu_item_db_id]) ? $_POST['menu-item-image'][$menu_item_db_id] : '';
        update_post_meta( $menu_item_db_id, 'wpcom_image', $image );
    }
    if ( isset($_POST['menu-item-target']) && is_array( $_POST['menu-item-target']) ) {
        $target = isset($_POST['menu-item-target'][$menu_item_db_id]) ? $_POST['menu-item-target'][$menu_item_db_id] : '';
        update_post_meta( $menu_item_db_id, 'target', $target ? '_blank' : '' );
    }
    if ( isset($_POST['menu-item-style']) && is_array( $_POST['menu-item-style']) ) {
        $style = isset($_POST['menu-item-style'][$menu_item_db_id]) ? $_POST['menu-item-style'][$menu_item_db_id] : '';
        update_post_meta( $menu_item_db_id, 'wpcom_style', $style );
    }
}

add_filter( 'wp_setup_nav_menu_item', 'wpcom_setup_nav_menu_item', 20 );
function wpcom_setup_nav_menu_item( $menu_item ){
    global $hook_suffix;
    if($menu_item){
        if ( isset($_POST['menu-item-image']) && isset($_POST['menu-item-image'][$menu_item->ID]) ) {
            $menu_item->image = $_POST['menu-item-image'][$menu_item->ID];
        }else{
            $menu_item->image = get_post_meta( $menu_item->ID, 'wpcom_image', true );
        }
        if ( isset( $_POST['menu-item-target']) && isset($_POST['menu-item-target'][$menu_item->ID]) ) {
            $menu_item->target = $_POST['menu-item-target'][$menu_item->ID];
        }else if($hook_suffix==='nav-menus.php'){
            $menu_item->target = $menu_item->target ? '1' : '';
        }
        if ( isset( $_POST['menu-item-style']) && isset($_POST['menu-item-style'][$menu_item->ID]) ) {
            $menu_item->style = $_POST['menu-item-style'][$menu_item->ID];
        }else{
            $menu_item->style = get_post_meta( $menu_item->ID, 'wpcom_style', true );
        }
    }
    return $menu_item;
}

add_filter( 'wp_nav_menu_args', 'wpcom_nav_menu_args' );
function wpcom_nav_menu_args( $args ){
    if( isset($args['advanced_menu']) && $args['advanced_menu'] ){
        if( isset($args['menu_class']) && $args['menu_class'] ){
            $args['menu_class'] .= ' wpcom-adv-menu';
        }else{
            $args['menu_class'] = 'wpcom-adv-menu';
        }
    }
    return $args;
}

add_action('admin_enqueue_scripts', 'wpcom_menu_panel_scripts');
function wpcom_menu_panel_scripts(){
    global $pagenow;
    if($pagenow === 'nav-menus.php'){
        WPCOM::panel_script();
    }
}

add_action('admin_print_footer_scripts-nav-menus.php', 'wpcom_menu_panel_options');
function wpcom_menu_panel_options(){ ?>
    <script>_panel_options = <?php echo wpcom_init_menu_options();?>;</script>
    <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
<?php }

function wpcom_init_menu_options(){
    $settings = array(
        'url' => array(
            'f' => 'object:custom',
            'name' => 'URL'
        ),
        'title' => array(
            'name' => '导航标签'
        ),
        'image' => array(
            'name' => '图标/图片',
            'type' => 'icon',
            'img' => 1
        ),
        'target' => array(
            'name' => '在新标签页中打开链接',
            'type' => 't'
        ),
        'style' => array(
            'f' => 'level:0',
            'name' => '下拉菜单风格',
            'type' => 'r',
            'ux' => 2,
            'o' => array(
                array('' => '默认风格||/themer/menu-style-0.png'),
                array('1' => '高级风格||/themer/menu-style-1.png'),
                array('2' => '图文#图片居左||/themer/menu-style-2.png'),
                array('3' => '图文#图片居上||/themer/menu-style-3.png')
            )
        ),
        'classes' => array(
            'name' => 'CSS类',
            'desc' => '可选，即class属性'
        ),
        'xfn' => array(
            'name' => '链接关系（XFN）',
            'desc' => '可选，rel属性，可设置nofollow'
        )
    );
    $res = array('type' => 'menu');
    $res['ver'] = THEME_VERSION;
    $res['theme-id'] = THEME_ID;
    $res['settings'] = $settings;
    $res['framework_url'] = FRAMEWORK_URI;
    $res['framework_ver'] = FRAMEWORK_VERSION;
    $res['assets_ver'] = defined('ASSETS_VERSION')?ASSETS_VERSION:'';
    $res = apply_filters('wpcom_menu_panel_options', $res);
    return json_encode($res);
}

add_filter('manage_nav-menus_columns', 'wpcom_nav_menus_columns', 20);
function wpcom_nav_menus_columns(){
    return array();
}

/**
 * 默认禁止提交，为按钮添加disabled，前端渲染完成后再移除disabled
 */
add_action('load-nav-menus.php', 'wpcom_menu_btn_replace_start');
function wpcom_menu_btn_replace_start(){
    //开启缓冲
    ob_start("wpcom_menu_btn_replace");
}

add_action('admin_print_footer_scripts-nav-menus.php', 'wpcom_menu_btn_replace_end');
function wpcom_menu_btn_replace_end(){
    // 关闭缓冲
    if (ob_get_level() > 0) ob_end_flush();
}

function wpcom_menu_btn_replace($str){
    $regexp = "/<(input|button)[^<>]+name=\"save_menu\"[^<>]+>/i";
    $str = preg_replace_callback($regexp, "wpcom_menu_btn_replace_callback", $str);
    return $str;
}

function wpcom_menu_btn_replace_callback($matches){
    return preg_replace('/name=\"save_menu\"/i', 'name="save_menu" disabled', $matches[0]);
}