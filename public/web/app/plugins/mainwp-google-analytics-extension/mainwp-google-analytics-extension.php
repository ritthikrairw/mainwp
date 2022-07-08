<?php
/*
Plugin Name: MainWP Google Analytics Extension
Plugin URI: https://mainwp.com
Description: MainWP Google Analytics Extension is an extension for the MainWP plugin that enables you to monitor detailed statistics about your child sites traffic. It integrates seamlessly with your Google Analytics account.
Version: 4.0.4
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/google-analytics/
*/

if ( ! defined( 'MAINWP_GA_PLUGIN_FILE' ) ) {
	define( 'MAINWP_GA_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_GA_PLUGIN_DIR' ) ) {
	define( 'MAINWP_GA_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'MAINWP_GA_PLUGIN_LOG_PRIORITY_NUMBER' ) ) {
	define( 'MAINWP_GA_PLUGIN_LOG_PRIORITY_NUMBER', 60 );
  }

class MainWPGoogleAnalyticsExtension {
	public static $instance = null;
	public $plugin_handle   = 'mainwp-google-analytics-extension';
	protected $plugin_url;
	protected $mainwpGA;
	private $plugin_slug;
	private $update_version = '1.0';

	static function Instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'mainwp_admin_footer', array( &$this, 'mainwp_admin_footer' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
	}

	public function init() {
		MainWPGADB::Instance()->install();
		new MainWPGA();
	}

	public function mainwp_admin_footer() {
		$lastupdate     = MainWPGADB::Instance()->getGASettingGlobal( 'lastupdate' );
		$ga_refreshrate = MainWPGADB::Instance()->getGASettingGlobal( 'refreshrate' );

		if ( ! MainWPGAUtility::ctype_digit( $ga_refreshrate ) || ( $ga_refreshrate == '' ) ) {
			$ga_refreshrate = 9;
		}

		if ( $lastupdate == 0 || ( time() - $lastupdate ) > 60 * 60 * $ga_refreshrate ) { // Never updated or longer then $ga_refreshrate hours ago
			MainWPGA::updateAvailableSites();
			MainWPGADB::Instance()->updateGASettingGlobal( 'lastupdate', time() );
		}
	}


	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}

	public function admin_enqueue_scripts() {
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'Extensions-Mainwp-Google-Analytics-Extension' || $_GET['page'] == 'mainwp_tab' || ( $_GET['page'] == 'managesites' ) && isset( $_GET['dashboard'] ) ) ) {
			wp_enqueue_script( 'mainwp-ga-chart', plugins_url( 'js/loader.js', __FILE__ ) );
		}
	}

	public function admin_init() {
		MainWPGA::to_ga_auth();
		wp_enqueue_style( 'mainwp-google-analytics-extension-css', $this->plugin_url . 'css/mainwp-google-analytics.css' );
		wp_enqueue_script( 'mainwp-google-analytics-extension-js', $this->plugin_url . 'js/mainwp-google-analytics.js' );
	}

}

class MainWPGoogleAnalyticsExtensionActivator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-google-analytics-extension';
	protected $product_id       = 'MainWP Google Analytics Extension';
	protected $software_version = '4.0.4';

	public function __construct() {
		$this->childFile = __FILE__;

		spl_autoload_register( array( $this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	public function autoload( $class_name ) {
		$allowedLoadingTypes = array( 'class' );

		foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
			$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
			if ( file_exists( $class_file ) ) {
				require_once $class_file;
			}
		}
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWPGA::renderSettings();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {

		if ( version_compare( phpversion(), '5.5' ) < 0 ) {
			add_action( 'mainwp_after_header', array( &$this, 'php_error_notice' ) );
			return;
		}

		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-google-analytics-extension' ) ) {
			return;
		}
		add_filter( 'mainwp_getmetaboxes', array( &$this, 'getMetaboxes' ) );
		add_filter( 'mainwp_widgets_screen_options', array( &$this, 'widgets_screen_options' ), 10, 1 );

		new MainWPGoogleAnalyticsExtension();
	}

  
	public function getMetaboxes( $metaboxes ) {
		if ( ! $this->childEnabled ) {
			return $metaboxes;
		}

		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}

		$metaboxes[] = array(
			'id'            => 'google-widget',
			'plugin'        => $this->childFile,
			'key'           => $this->childKey,
			'metabox_title' => MainWPGA::getName(),
			'callback'      => array( MainWPGA::getClassName(), 'render_metabox' ),
		);
		return $metaboxes;
  }
  
	function widgets_screen_options( $input ) {
		$input['advanced-google-widget'] = __( 'Google Analytics', 'advanced-uptime-monitor-extension' );
		return $input;
	}

	public function php_error_notice() {
		?>
	  <div class="ui segment">
		<div class="ui message red"><?php echo __( 'MainWP Google Analytics extension requires PHP 5.5 or higher. Activating the extension on older versions of PHP can cause fatal errors. Please contact your host support and have them update PHP for you.', 'mainwp-google-analytics-extension' ); ?></div>
	  </div>
		<?php
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Google Analytics Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function getChildKey() {
		return $this->childKey;
	}

	public function getChildFile() {
		return $this->childFile;
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
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPGoogleAnalyticsExtensionActivator;
$mainWPGoogleAnalyticsExtensionActivator = new MainWPGoogleAnalyticsExtensionActivator();
