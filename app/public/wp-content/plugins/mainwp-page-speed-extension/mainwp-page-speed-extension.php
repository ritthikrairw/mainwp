<?php
/*
  Plugin Name: MainWP Page Speed Extension
  Plugin URI: https://mainwp.com
  Description: MainWP Page Speed Extension enables you to use Google Page Speed insights to monitor website performance across your network. Requires MainWP Dashboard plugin.
  Version: 4.0.2
  Author: MainWP
  Author URI: https://mainwp.com
  Documentation URI: https://mainwp.com/help/category/mainwp-extensions/page-speed/
 */

if ( ! defined( 'MAINWP_PAGE_SPEED_PLUGIN_FILE' ) ) {
	define( 'MAINWP_PAGE_SPEED_PLUGIN_FILE', __FILE__ );
}

class MainWP_Page_Speed_Extension {

	public $plugin_handle = 'mainwp-pagespeed-extension';
	public static $plugin_url;
	public $plugin_slug;
	public $plugin_dir;
	public $version = '1.2';

  public function __construct() {
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		self::$plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'init', array( &$this, 'localization' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp_sync_extensions_options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );
		add_action( 'mainwp_applypluginsettings_mainwp-page-speed-extension', array( Mainwp_Page_Speed::get_instance(), 'mainwp_apply_plugin_settings' ) );
    add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );

		MainWP_Page_Speed_DB::get_instance()->install();
	}

	public function init() {
		Mainwp_Page_Speed::get_instance()->init();
		Mainwp_Page_Speed::get_instance()->init_cron();
	}

	public function localization() {
		load_plugin_textdomain( 'mainwp-page-speed-extension', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	function mainwp_sync_extensions_options( $values = array() ) {
		$values['mainwp-page-speed-extension'] = array(
			'plugin_slug' => 'google-pagespeed-insights/google-pagespeed-insights.php',
			'plugin_name' => 'Google Pagespeed Insights'
		);
		return $values;
	}

  function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title' 				 	 => __( 'Page Speed', 'mainwp-page-speed-extension' ),
			'slug' 						 => 'PageSpeed',
			'sitetab' 				 => true,
			'menu_hidden'		   => true,
			'callback' 				 => array( 'Mainwp_Page_Speed', 'render_tabs' ),
		);
		return $subPage;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug = basename( $plugin_file, ".php" );
		$api_data = get_option( $slug. '_APIManAdder');

		if ( !is_array( $api_data ) || !isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || !isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}


		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	public function admin_init() {
		if (isset($_GET['page']) && ($_GET['page'] == 'Extensions-Mainwp-Page-Speed-Extension' || $_GET['page'] == 'managesites' )) {
			wp_enqueue_style( 'mainwp-pagespeed-extension', self::$plugin_url . 'css/mainwp-pagespeed.css', array(), $this->version );
			wp_enqueue_script( 'mainwp-pagespeed-extension', self::$plugin_url . 'js/mainwp-pagespeed.js', array(), $this->version );
			wp_localize_script('mainwp-pagespeed-extension', 'mainwpPagespeed', array( 'nonce' => wp_create_nonce( 'mwp_pagespeed_nonce' ), ) );
		}		
		MainWP_Page_Speed_Dashboard::get_instance()->admin_init();
	}
}

class MainWP_Page_Speed_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-page-speed-extension';
	protected $product_id = 'MainWP Page Speed Extension';
	protected $software_version = '4.0.2';

	public function __construct() {
		$this->childFile = __FILE__;

    spl_autoload_register( array( $this, 'autoload' ) );
    register_activation_hook( __FILE__, array($this, 'activate') );
    register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}


  function autoload( $class_name ) {
    $allowedLoadingTypes = array( 'class' );
    $class_name = str_replace( '_', '-', strtolower( $class_name ) );
    foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
      $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
      if ( file_exists( $class_file ) ) {
        require_once( $class_file );
      }
    }
  }

	function get_this_extension( $pArray ) {
		$pArray[] = array(
      'plugin' 						=> __FILE__,
      'api' 							=> $this->plugin_handle,
      'mainwp' 						=> true,
      'callback' 					=> array( &$this, 'settings' ),
      'apiManager'			  => true,
    );
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		Mainwp_Page_Speed::render_tabs();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	public function get_metaboxes( $metaboxes ) {
		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}
		$metaboxes[] = array(
			'plugin' 				=> $this->childFile,
			'key' 					=> $this->childKey,
			'metabox_title' => "MainWP Page Speed",
			'callback' 			=> array( 'Mainwp_Page_Speed', 'render_metabox' )
		);
		return $metaboxes;
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-page-speed-extension' ) ) {
			return;
		}
		add_filter( 'mainwp_getmetaboxes', array( &$this, 'get_metaboxes' ) );
		new MainWP_Page_Speed_Extension();
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Page Speed Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-pagespeed-extension' ) . '</p></div>';
		}
	}

	public function update_option( $option_name, $option_value ) {
		$success = add_option( $option_name, $option_value, '', 'no' );

		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}

		return $success;
	}

	public function activate() {
		 $options = array(
      'product_id' => $this->product_id,
			'software_version' => $this->software_version,
		);
    do_action( 'mainwp_activate_extention', $this->plugin_handle , $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPPageSpeedExtensionActivator;
$mainWPPageSpeedExtensionActivator = new MainWP_Page_Speed_Extension_Activator();
