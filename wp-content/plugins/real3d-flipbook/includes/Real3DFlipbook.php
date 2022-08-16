<?php

/*plugin class*/
class Real3DFlipbook {

	public $PLUGIN_VERSION;
	public $PLUGIN_DIR_URL;
	public $PLUGIN_DIR_PATH;

	// Singleton
	private static $instance = null;
	
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	protected function __construct() {
		$this->add_actions();
		register_activation_hook($this->my_plugin_basename(), array( $this, 'activation_hook' ) );
	}

	public function activation_hook($network_wide) {
	}
	
	public function enqueue_scripts() {

		wp_register_script("real3d-flipbook", $this->PLUGIN_DIR_URL."js/flipbook.min.js", array('jquery', 'real3d-flipbook-iscroll'),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-book3", $this->PLUGIN_DIR_URL."js/flipbook.book3.min.js", array('real3d-flipbook'),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-bookswipe", $this->PLUGIN_DIR_URL."js/flipbook.swipe.min.js", array('real3d-flipbook'),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-iscroll", $this->PLUGIN_DIR_URL."js/iscroll.min.js", array(),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-threejs", $this->PLUGIN_DIR_URL."js/three.min.js", array(),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-webgl", $this->PLUGIN_DIR_URL."js/flipbook.webgl.min.js", array('real3d-flipbook', 'real3d-flipbook-threejs' ),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-pdfjs", $this->PLUGIN_DIR_URL."js/pdf.min.js", array(),$this->PLUGIN_VERSION);
     	wp_register_script("real3d-flipbook-pdfworkerjs", $this->PLUGIN_DIR_URL."js/pdf.worker.min.js", array(),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-pdfservice", $this->PLUGIN_DIR_URL."js/flipbook.pdfservice.min.js", array(),$this->PLUGIN_VERSION);

     	wp_register_script("real3d-flipbook-embed", $this->PLUGIN_DIR_URL."js/embed.js", array('real3d-flipbook'),$this->PLUGIN_VERSION);

     	wp_register_style( 'real3d-flipbook-style', $this->PLUGIN_DIR_URL."css/flipbook.style.css" , array(),$this->PLUGIN_VERSION);
     	wp_register_style( 'real3d-flipbook-font-awesome', $this->PLUGIN_DIR_URL."css/font-awesome.css" , array(),$this->PLUGIN_VERSION);
		
	}

	public function admin_enqueue_scripts() {

     	wp_register_script( 'alpha-color-picker', $this->PLUGIN_DIR_URL. 'js/alpha-color-picker.js', array( 'jquery', 'wp-color-picker' ),$this->PLUGIN_VERSION, true );
		wp_register_style( 'alpha-color-picker', $this->PLUGIN_DIR_URL. 'css/alpha-color-picker.css', array( 'wp-color-picker' ), $this->PLUGIN_VERSION );

     	wp_register_script( "real3d-flipbook-admin", $this->PLUGIN_DIR_URL. "js/edit_flipbook.js", array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'real3d-flipbook-pdfjs', 'alpha-color-picker', 'common', 'wp-lists', 'postbox' ),$this->PLUGIN_VERSION); 

     	wp_register_script( "real3d-flipbook-settings", $this->PLUGIN_DIR_URL. "js/general.js", array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'alpha-color-picker', 'common', 'wp-lists', 'postbox' ),$this->PLUGIN_VERSION); 

     	wp_register_script( "real3d-flipbook-flipbooks", $this->PLUGIN_DIR_URL. "js/flipbooks.js", array( 'jquery', 'common', 'wp-lists', 'postbox' ),$this->PLUGIN_VERSION); 

		wp_register_style( 'real3d-flipbook-admin-css', $this->PLUGIN_DIR_URL. "css/flipbook-admin.css",array(), $this->PLUGIN_VERSION ); 
		
	}
	
	protected function get_translation_array() {
		return Array(
            'objectL10n' => array(
                'loading' => esc_html__('Loading...', 'transitionslider')
               
            ));
	}

	public function admin_link($links) {
		array_unshift($links, '<a href="' . get_admin_url() . 'options-general.php?page=sliders">Admin</a>');
		return $links;
	}

	public function init() {

		$this->enqueue_scripts();

		add_filter('widget_text', 'do_shortcode');
		add_shortcode( 'real3dflipbook', array($this, 'on_shortcode') );
	}

	public function plugins_loaded() {
		// load_plugin_textdomain( 'transitionslider', false, dirname($this->my_plugin_basename()).'/lang/' );
	}

	protected function add_actions() {

		// add_action('plugins_loaded', array($this, 'plugins_loaded') );

		add_action('init', array($this, 'init') );
		
		// add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		if (is_admin()) {
			include_once( plugin_dir_path(__FILE__).'plugin-admin.php' );
			add_filter("plugin_action_links_" . plugin_basename(__FILE__), array($this,"admin_link"));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );


			add_action("admin_menu", array($this, "admin_menu"));
			// add_action('admin_menu', array($this, 'admin_menu'));
	 //        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'admin_link'));
  //           add_action( 'wp_ajax_transitionslider_save', array($this, 'transitionslider_save_callback') );
  //           add_action( 'wp_ajax_nopriv_transitionslider_save', array($this, 'transitionslider_save_callback') );

		}
	}

	public function admin_menu(){

		add_options_page(
			"Real 3D Flipbook Admin", 
			"Real3D Flipbook", 
			"publish_posts", 
			"real3d_flipbook_admin", 
			array($this,"admin")
		); 

		add_menu_page(
			'Real3D Flipbook', 
			'Real3D Flipbook', 
			'publish_posts', 
			'real3d_flipbook_admin',
			array($this,"admin"),
			'dashicons-book' 
		);

		add_submenu_page( 
			'real3d_flipbook_admin', 
			'Flipbooks', 
			'Flipbooks',
		    'publish_posts', 
		    'real3d_flipbook_admin',
		    array($this,"admin")
		);

		add_submenu_page( 
			'real3d_flipbook_admin', 
			'Settings',
			'Settings',
		    'publish_posts', 
		    'real3d_flipbook_settings',
		    array($this,"settings")
		);

		add_submenu_page( 
			'real3d_flipbook_admin', 
			'Add new', 
			'Add new',
		    'publish_posts', 
		    'real3d_flipbook_add_new',
		    array($this,"add_new")
		);

		if (function_exists('register_block_type')) {

			// // Register block, and explicitly define the attributes we accept.
			register_block_type( 'r3dfb/embed', array(
				// 'attributes' => array(
				// 	'id' => array(
				// 		'type' => 'string',
				// 	)
				// ),
				// 'render_callback' => 'slidertx_render_callback',
			) );

			add_action( 'enqueue_block_assets', array($this,'enqueue_block_assets'));
			add_action( 'enqueue_block_editor_assets', array($this,'enqueue_block_editor_assets'));

		}

		do_action('real3d_flipbook_menu');
		
	}

	public function enqueue_block_assets(){

	}

	public function enqueue_block_editor_assets(){

		wp_enqueue_script(
			'r3dfb-block-js', // Unique handle.
			$this->PLUGIN_DIR_URL."js/blocks.js", 
			array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' ), // Dependencies, defined above.
			$this->PLUGIN_VERSION
		);

		$r3dfb_ids = get_option('real3dflipbooks_ids');

		$books = array();

		foreach ($r3dfb_ids as $id) {
	      	
	      	$fb = get_option('real3dflipbook_'.$id);
	      	$book = array();
	      	$book["id"] = $fb["id"];
	      	$book["name"] = $fb["name"];
	      	if(isset($fb["mode"]))
	      		$book["mode"] = $fb["mode"];
	      	if(isset($fb["pdfUrl"]))
	      		$book["pdfUrl"] = $fb["pdfUrl"];
	      	array_push($books, $book);

	     }

		wp_localize_script( 'r3dfb-block-js','r3dfb', json_encode($books) );
		
	}

	public function admin(){

		include_once( plugin_dir_path(__FILE__).'admin-actions.php' );

    }

    public function settings(){

		include_once( plugin_dir_path(__FILE__).'general.php' );

    }

    public function add_new(){

		$_GET['action'] = "add_new";
		$this->admin();
		
	}


	public function on_shortcode($atts, $content=null) {

		$args = shortcode_atts( 
			array(
				'id'   => '-1',
				'name' => '-1',
				'pdf' => '-1',
				'mode' => '-1',
				'viewmode' => '-1',
				'lightboxopened' => '-1',
				'lightboxfullscreen' => '-1',
				'lightboxtext' => '-1',
				'lightboxcssclass' => '-1',
				'lightboxthumbnail' => '-1',
				'lightboxthumbnailurl' => '-1',
				'hidemenu' => '-1',
				'autoplayonstart' => '-1',
				'autoplayinterval' => '-1',
				'zoom' => '-1',
				'zoomdisabled' => '-1',
				'btndownloadpdfurl' => '-1',
				'aspect' => '-1',

				'thumb' => '-1',
				'thumbcss' => '-1',
				'containercss' => '-1',

				'tilt' => '-1',
				'tiltmin' => '-1',
				'tiltmax' => '-1',
				'lights' => '-1',
				'shadows' => '-1',
				'pageroughness' => '-1',
				'pagemetalness' => '-1',
				'pagehardness' => '-1',
				'coverhardness' => '-1',

				'singlepage' => '-1',
				'startpage' => '-1',

				'deeplinkingprefix' => '-1'

			), 
			$atts
		);

		if($args['id'] == "all"){

			$output = '<div></div>';

			$real3dflipbooks_ids = get_option('real3dflipbooks_ids');

			foreach ($real3dflipbooks_ids as $id) {

				$shortcode = '[real3dflipbook id="'.$id.'" mode="lightbox"';

				if($args['thumbcss'] != -1)
					$shortcode .= ' thumbcss="'.$args['thumbcss'].'"';

				if($args['containercss'] != -1)
					$shortcode .= ' containercss="'.$args['containercss'].'"';

				$shortcode .= ']';

				$output .= do_shortcode($shortcode);

			}

			return $output;

		}
		
		$id = (int) $args['id'];
		$name = $args['name'];

		if($name != -1){
			$real3dflipbooks_ids = get_option('real3dflipbooks_ids');
			foreach ($real3dflipbooks_ids as $id) {
				$book = get_option('real3dflipbook_'.$id);
				if($book && $book['name'] == $name){
					$flipbook = $book;
					$id = $flipbook['id'];
					break;
				}
			}
		}else if($id != -1){
			$flipbook = get_option('real3dflipbook_'.$id);
		}else{
			$flipbook = array();
			$id = '0';
		}
		
		$bookId = $id .'_'.uniqid();

		foreach ($args as $key => $val) {
			if($val != -1){

				if($key == 'mode') $key = 'mode';
				if($key == 'viewmode') $key = 'viewMode';
				
				if($key == 'pdf' && $val != "") $key = 'pdfUrl';
				if($key == 'btndownloadpdfurl') $key = 'btnDownloadPdfUrl';
				
				if($key == 'hidemenu') $key = 'hideMenu';
				if($key == 'autoplayonstart') $key = 'autoplayOnStart';
				if($key == 'autoplayinterval') $key = 'autoplayInterval';
				if($key == 'zoom') $key = 'zoomLevels';
				if($key == 'zoomisabled') $key = 'zoomDisabled';

				if($key == 'lightboxtext') $key = 'lightboxText';
				if($key == 'lightboxcssclass') $key = 'lightboxCssClass';
				if($key == 'lightboxthumbnailurl') $key = 'lightboxThumbnailUrl';
				if($key == 'lightboxopened') $key = 'lightBoxOpened';
				if($key == 'lightboxfullscreen') $key = 'lightBoxFullscreen';

				if($key == 'tiltmin') $key = 'tiltMin';
				if($key == 'tiltmax') $key = 'tiltMax';
				if($key == 'pageroughness') $key = 'pageRoughness';
				if($key == 'pagemetalness') $key = 'pageMetalness';
				if($key == 'pagehardness') $key = 'pageHardness';
				if($key == 'coverhardness') $key = 'coverHardness';

				if($key == 'aspect') {

					$key = 'aspectRatio';
					$flipbook['responsiveHeight'] = 'true';

				}

				if($key == 'thumb') $key = 'lightboxThumbnailUrl';
				if($key == 'thumbcss') $key = 'lightboxThumbnailUrlCSS';
				if($key == 'containercss') $key = 'lightboxContainerCSS';

				if($key == 'singlepage') $key = 'singlePageMode';

				if($key == 'startpage') $key = 'startPage';

				if($key == 'deeplinkingprefix') {
					$flipbook['deeplinkingEnabled'] = 'true';
					$flipbook['deeplinkingPrefix'] = $val;
					unset($flipbook['deeplinking']);
				}

		    	$flipbook[$key] = $val;
			}
		}

		$flipbook['rootFolder'] = $this->PLUGIN_DIR_URL;
		$flipbook['uniqueId'] = $bookId;

		$flipbook_global_options = get_option("real3dflipbook_global", array());

		$flipbook = array_merge($flipbook_global_options, $flipbook);

		$test = "0";
		if (wp_script_is( 'real3d_flipbook_embed', 'registered' )) {
			$test = "1";
		}

		$output = '<div class="real3dflipbook" id="'.$bookId.'" style="position:absolute;" data-flipbook-options="'.htmlspecialchars(json_encode($flipbook)).'"></div>';

		// trace($flipbook);

		// trace(json_encode($flipbook));
		// trace(htmlspecialchars(json_encode($flipbook)));


		if (!wp_script_is( 'real3d-flipbook', 'enqueued' )) {
	     	wp_enqueue_script("real3d-flipbook");
	     }

	     if (!wp_script_is( 'real3d-flipbook-book3', 'enqueued' )) {
	     	wp_enqueue_script("real3d-flipbook-book3");
	     }

	     if (!wp_script_is( 'real3d-flipbook-bookswipe', 'enqueued' )) {
	     	wp_enqueue_script("real3d-flipbook-bookswipe");
	     }

	     if (!wp_script_is( 'real3d-flipbook-iscroll', 'enqueued' )) {
	     	wp_enqueue_script("real3d-flipbook-iscroll");
	     }

	     if($flipbook['viewMode'] == 'webgl'){
		     if (!wp_script_is( 'real3d-flipbook-threejs', 'enqueued' )) {
		     	wp_enqueue_script("real3d-flipbook-threejs");
		     }
		     if (!wp_script_is( 'real3d-flipbook-webgl', 'enqueued' )) {
		     	wp_enqueue_script("real3d-flipbook-webgl");
		     }
	     }

	     if(isset($flipbook['pdfUrl']) && $flipbook['pdfUrl'] != -1 || isset($flipbook['type']) && $flipbook['type'] == 'pdf'){

		     if (!wp_script_is( 'real3d-flipbook-pdfjs', 'enqueued' )) {
		     	wp_enqueue_script("real3d-flipbook-pdfjs");
		     }

		     if (!wp_script_is( 'real3d-flipbook-pdfservice', 'enqueued' )) {
		     	wp_enqueue_script("real3d-flipbook-pdfservice");
		     }

	     }

	     if (!wp_script_is( 'real3d-flipbook-embed', 'enqueued' )) {
	     	wp_enqueue_script("real3d-flipbook-embed");
	     }

	     // wp_localize_script('real3d-flipbook-embed', 'real3dflipbook_'.$bookId, json_encode($flipbook));

	     if (!wp_style_is( 'real3d-flipbook-style', 'enqueued' )) {
	     	wp_enqueue_style("real3d-flipbook-style");
	     }

	     $useFontAwesome5 = !isset($flipbook_global_options['useFontAwesome5']) || $flipbook_global_options['useFontAwesome5'] == 'true';

	     if (!wp_style_is( 'real3d-flipbook-font-awesome', 'enqueued' ) && $useFontAwesome5) {
	     	wp_enqueue_style("real3d-flipbook-font-awesome");
	     }



		return $output;
	}

	protected function my_plugin_basename() {
		$basename = plugin_basename(__FILE__);
		if ('/'.$basename == __FILE__) { // Maybe due to symlink
			$basename = basename(dirname(__FILE__)).'/'.basename(__FILE__);
		}
		return $basename;
	}
	
	protected function my_plugin_url() {
		$basename = plugin_basename(__FILE__);
		if ('/'.$basename == __FILE__) { // Maybe due to symlink
			return plugins_url().'/'.basename(dirname(__FILE__)).'/';
		}
		// Normal case (non symlink)
		return plugin_dir_url( __FILE__ );
	}
}