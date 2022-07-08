<?php
/*
Plugin Name: MainWP Wordfence Extension
Plugin URI: https://mainwp.com
Description: The Wordfence Extension combines the power of your MainWP Dashboard with the popular WordPress Wordfence Plugin. It allows you to manage Wordfence settings, Monitor Live Traffic and Scan your child sites directly from your dashboard. Requires MainWP Dashboard plugin.
Version: 4.0.6
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/wordfence/
*/
if ( ! defined( 'MAINWP_WORDFENCE_EXT_PLUGIN_FILE' ) ) {
	define( 'MAINWP_WORDFENCE_EXT_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_WORDFENCE_PATH' ) ) {
	define( 'MAINWP_WORDFENCE_PATH', plugin_dir_path( MAINWP_WORDFENCE_EXT_PLUGIN_FILE ) );
}

if ( ! defined( 'MAINWP_BULK_NUMBER_SITES' ) ) {
	define( 'MAINWP_BULK_NUMBER_SITES', 200 );
}

class MainWP_Wordfence_Extension {
	public static $instance = null;
	public static $plugin_url;
	public static $plugin_translate = 'mainwp-wordfence-extension';
	public $plugin_slug;
	public static $plugin_dir;
	protected $option;
	protected $option_handle       = 'mainwp_wordfence_extension';
	public static $update_version  = '1.1';
	private static $script_version = '1.7';

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Wordfence_Extension();
		}

		return self::$instance;
	}

	public function __construct() {

		self::$plugin_dir  = plugin_dir_path( __FILE__ );
		self::$plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		$this->include_files();
		add_action( 'init', array( &$this, 'localization' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'check_update' ) );
		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_filter( 'mainwp_sync_extensions_options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );
		add_action( 'mainwp_applypluginsettings_mainwp-wordfence-extension', array( MainWP_Wordfence_Setting::get_instance(), 'mainwp_apply_plugin_settings' ) );

		MainWP_Wordfence_DB::get_instance()->install();
	}

	public function localization() {
		load_plugin_textdomain( 'mainwp-wordfence-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function include_files() {
		require_once MAINWP_WORDFENCE_PATH . '/libs/wfUtils.php';
		require_once MAINWP_WORDFENCE_PATH . '/libs/wfPersistenceController.php';
		require_once MAINWP_WORDFENCE_PATH . '/libs/wfView.php';
		require_once MAINWP_WORDFENCE_PATH . '/models/scanner/wfScanner.php';
		require_once MAINWP_WORDFENCE_PATH . '/models/common/wfTab.php';
	}

	public function init() {
		MainWP_Wordfence_Setting::init();
	}

	function check_update() {
		$update_version = get_option( 'mainwp_wordfence_update_version', false );
		if ( $update_version == self::$update_version ) {
			return; 
		}
		update_option( 'mainwp_wordfence_update_version', self::$update_version, 'yes' );
	}

	function mainwp_sync_extensions_options( $values = array() ) {
		$values['mainwp-wordfence-extension'] = array(
			'plugin_slug' => 'wordfence/wordfence.php',
			'plugin_name' => 'Wordfence Security',
		);
		return $values;
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

	function managesites_subpage( $subPage ) {

		$subPage[] = array(
			'title'       => __( 'Wordfence', 'mainwp-wordfence-extension' ),
			'slug'        => 'Wordfence',
			'sitetab'     => true,
			'menu_hidden' => true,
			'callback'    => array( 'MainWP_Wordfence', 'render' ),
		);

		return $subPage;
	}


	public function admin_init() {
		wp_enqueue_style( 'mainwp-wordfence-extension', self::getBaseURL() . 'css/mainwp-wordfence.css', '', self::$script_version );
		wp_enqueue_script( 'mainwp-wordfence-extension', self::getBaseURL() . 'js/mainwp-wordfence.js', array( 'jquery' ), self::$script_version );

		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Wordfence-Extension' == $_GET['page'] || ( 'managesites' == $_GET['page'] && isset( $_GET['scanid'] ) ) || 'ManageSitesWordfence' == $_GET['page'] ) ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			// wp_enqueue_style('mainwp-wordfence-main-style',  self::getBaseURL() . 'css/main.css', '', self::$script_version);
			wp_enqueue_style( 'mainwp-wordfence-main-style', self::getBaseURL() . 'css/wf-main.css', '', self::$script_version );
			wp_enqueue_style( 'mainwp-wordfence-adminbar-style', self::getBaseURL() . 'css/wf-adminbar.css', '', self::$script_version );
			wp_enqueue_style( 'mainwp-wordfence-ionicons-style', self::getBaseURL() . 'css/wf-ionicons.css', '', self::$script_version );

			// wp_enqueue_style( 'mainwp-wordfence-extension-colorbox-style', self::getBaseURL() . 'css/wf-colorbox.css' );
			wp_enqueue_style( 'mainwp-wordfence-extension-dttable-style', self::getBaseURL() . 'css/dt_table.css' );
			wp_enqueue_style( 'mainwp-wordfence-colorbox-style', self::getBaseURL() . 'css/wf-colorbox.css', '', self::$script_version );
			wp_enqueue_script( 'mainwp-wordfence-extension-admin-log', self::getBaseURL() . 'js/mainwp-wfc-log.js', array(), self::$script_version );
			wp_enqueue_script( 'mainwp-wordfence-extension-jquery-tmpl', self::getBaseURL() . 'js/jquery.tmpl.min.js', array( 'jquery' ) );
			// wp_enqueue_script( 'mainwp-wordfence-extension-jquery-colorbox', self::getBaseURL() . 'js/jquery.colorbox-min.js', array( 'jquery' ) );
			wp_enqueue_script( 'mainwp-wordfence-jquery-colorbox', self::getBaseURL() . 'js/jquery.colorbox.1517414961.js', array( 'jquery' ), self::$script_version );
			wp_enqueue_script( 'mainwp-wordfence-extension-jquery-dataTables', self::getBaseURL() . 'js/jquery.dataTables.min.js', array( 'jquery' ) );
		}

		if ( isset( $_GET['page'] ) && ( 'managesites' == $_GET['page'] || 'ManageSitesWordfence' == $_GET['page'] ) ) {
			wp_enqueue_script( 'mainwp-wordfence-extension-admin-log', self::getBaseURL() . 'js/mainwp-wfc-log.js', array(), self::$script_version );
		}

		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Wordfence-Extension' == $_GET['page'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'result' ) {
			wp_enqueue_script( 'jquery-ui-menu' );
		}

		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Wordfence-Extension' == $_GET['page'] || 'ManageSitesWordfence' == $_GET['page'] ) ) {
			self::activity_enqueue_style();
		}

		$wfc = new MainWP_Wordfence();
		$wfc->admin_init();
		$wfc_plugin = MainWP_Wordfence_Plugin::get_instance();
		$wfc_plugin->admin_init();
		$wfc_setting = MainWP_Wordfence_Setting::get_instance();
		$wfc_setting->admin_init();
	}

	public static function activity_enqueue_style() {

		$current_page = '';

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'traffic' ) {
			$current_page = 'traffic';
		}

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'network_traffic' || $_GET['tab'] == 'traffic' ) {
				$current_page = 'traffic';
			} elseif ( $_GET['tab'] == 'firewall' || $_GET['tab'] == 'network_firewall' ) {
				$current_page = 'firewall';
			}
		}

		if ( $current_page == 'traffic' ) {
			wp_enqueue_style( 'select2', self::getBaseURL() . 'css/select2.min.css', array(), self::$script_version );
			wp_enqueue_script( 'select2', self::getBaseURL() . 'js/select2.min.js', array( 'jquery' ), self::$script_version );
			wp_enqueue_style( 'mainwp-wfc-jquery-ui-theme-css', self::getBaseURL() . 'css/jquery-ui.theme.min.css', array(), self::$script_version );
			wp_enqueue_style( 'mainwp-wfc-jquery-ui-timepicker-css', self::getBaseURL() . 'css/jquery-ui-timepicker-addon.css', array(), self::$script_version );
			wp_enqueue_script( 'mainwp-wfc-timepicker-js', self::getBaseURL() . 'js/jquery-ui-timepicker-addon.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ), self::$script_version );
			wp_enqueue_script( 'mainwp-wfc-knockout-js', self::getBaseURL() . 'js/knockout-3.3.0.js', array(), self::$script_version );
			// wp_enqueue_script('mainwp-wfc-live-traffic-js', MainWP_Wordfence_Extension::getBaseURL() . 'js/admin.liveTraffic.js', array('jquery'), self::$script_version);
			wp_enqueue_script( 'mainwp-wordfence-live-traffic-js', self::getBaseURL() . 'js/admin.liveTraffic.1517414961.js', array( 'jquery', 'jquery-ui-tooltip' ), self::$script_version );
		} elseif ( $current_page == 'firewall' ) {
			wp_enqueue_style( 'mainwp-wfc-jquery-ui-timepicker-css', self::getBaseURL() . 'css/jquery-ui-timepicker-addon.css', array(), self::$script_version );
			wp_enqueue_script( 'mainwp-wfc-timepicker-js', self::getBaseURL() . 'js/jquery-ui-timepicker-addon.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ), self::$script_version );
			wp_enqueue_style( 'select2', self::getBaseURL() . 'css/select2.min.css', array(), self::$script_version );
			wp_enqueue_script( 'select2', self::getBaseURL() . 'js/select2.min.js', array( 'jquery' ), self::$script_version );
		}
	}

	public static function getBaseURL() {
		return self::$plugin_url;
	}
}

class MainWP_Wordfence_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-wordfence-extension';
	protected $product_id       = 'MainWP Wordfence Extension';
	protected $software_version = '4.0.6';

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

	function autoload( $class_name ) {
		$allowedLoadingTypes = array( 'class' );
		$class_name          = str_replace( '_', '-', strtolower( $class_name ) );
		foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
			$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
			if ( file_exists( $class_file ) ) {
				require_once $class_file;
			}
		}
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'           => __FILE__,
			'api'              => $this->plugin_handle,
			'mainwp'           => true,
			'callback'         => array( &$this, 'settings' ),
			'apiManager'       => true,
			'on_load_callback' => array( 'MainWP_Wordfence', 'on_load_general_page' ),
		);
		return $pArray;
	}

	public function get_metaboxes( $metaboxes ) {
		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}
		if ( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] ) {
			$metaboxes[] = array(
				'plugin'        => $this->childFile,
				'key'           => $this->childKey,
				'metabox_title' => __( 'Wordfence Status', 'mainwp-wordfence-extension' ),
				'callback'      => array( 'MainWP_Wordfence', 'render_metabox' ),
			);
		}

		return $metaboxes;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Wordfence::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-wordfence-extension' ) ) {
			return;
		}
		add_filter( 'mainwp_getmetaboxes', array( &$this, 'get_metaboxes' ) );
		new MainWP_Wordfence_Extension();
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
			echo '<div class="error"><p>MainWP Wordfence Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-wordfence-extension' ) . '</p></div>';
		}
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

global $mainWPWordfenceExtensionActivator;
$mainWPWordfenceExtensionActivator = new MainWP_Wordfence_Extension_Activator();
