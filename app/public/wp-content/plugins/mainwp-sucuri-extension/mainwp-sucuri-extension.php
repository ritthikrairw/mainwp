<?php
/**
 * MainWP Sucuri Extension
 *
 * MainWP Sucuri Extension enables you to scan your child sites for various types of malware, spam injections, website errors, and much more. Requires the MainWP Dashboard.
 *
 * Plugin Name: MainWP Sucuri Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Sucuri Extension enables you to scan your child sites for various types of malware, spam injections, website errors, and much more. Requires the MainWP Dashboard.
 * Version: 4.0.11
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/sucuri/
 */

if ( ! defined( 'MAINWP_SUCURI_PLUGIN_FILE' ) ) {

	/**
	 * Define Plugin file Global Variable.
	 */
	define( 'MAINWP_SUCURI_PLUGIN_FILE', __FILE__ );
}


/**
 * Class MainWP_Sucuri_Extension
 */
class MainWP_Sucuri_Extension {

	/**
	 * Public static variable to hold the single instance of MainWP_Sucuri_Extension.
	 *
	 * @var mixed Default null
	 */
	public static $instance = null;

	/** @var string Plugin file. */
	public $plugin_handle = 'mainwp-sucuri-extension';

	/** @var string $plugin_url Plugin URL. */
	protected $plugin_url;

	/** @var string $plugin_slug Plugin slug. */
	public $plugin_slug;

	/** @var array $scan_results Sucuri scan results. */
	public $scan_results = null;

	/**
	 * Create a public static instance of MainWP_Sucuri_Extension.
	 *
	 * @return MainWP_Sucuri_Extension|mixed|null
	 */
	static function get_instance() {
		if ( null == MainWP_Sucuri_Extension::$instance ) {
			MainWP_Sucuri_Extension::$instance = new MainWP_Sucuri_Extension();
		}
		return MainWP_Sucuri_Extension::$instance;
	}

	/**
	 * MainWP_Sucuri_Extension constructor.
	 */
	public function __construct() {
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'localization' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'mainwp_delete_site', array( &$this, 'on_delete_site' ), 10, 1 );
		MainWP_Sucuri_DB::get_instance()->install();
		MainWP_Sucuri::get_instance()->init();
	}

	/**
	 * MainWP Sucuri Extension localization.
	 */
	public function localization() {
		load_plugin_textdomain( 'mainwp-sucuri-extension', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Plugin Meta row data.
	 *
	 * @param array  $plugin_meta Plugin meta data.
	 * @param string $plugin_file Plugin File.
	 *
	 * @return array $plugin_meta Plugin meta data array.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug = basename( $plugin_file, ".php" );
		$api_data = get_option( $slug. '_APIManAdder' );
		if ( !is_array( $api_data ) || !isset( $api_data['activated_key'] ) || 'Activated' != $api_data['activated_key'] || !isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	/**
	 * Initiate Admin.
	 */
	public function admin_init() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Sucuri-Extension' == $_GET['page'] || 'managesites' == $_GET['page'] ) ) {
			wp_enqueue_script( 'mainwp-securi-extension', $this->plugin_url . 'js/mainwp-sucuri.js', array(), '4.0' );
		}
		MainWP_Sucuri::get_instance()->handle_sites_screen_settings();
	}

	/**
	 * Delete Child Site.
	 *
	 * @param object $website Child Site array.
	 */
  public function on_delete_site( $website ) {
  	if ( $website ) {
			MainWP_Sucuri_DB::get_instance()->delete_sucuri_by_site_id( $website->id );
    }
	}

}

/**
 * Class MainWP_Sucuri_Extension_Activator
 */
class MainWP_Sucuri_Extension_Activator {

	/** @var bool TURE|FALSE. Whether or not MainWP Dashboard is activated. */
	protected $mainwpMainActivated = false;

	/** @var bool TRUE|FALSE. Whether or not MainWP Child Plugin is enabled. */
	protected $childEnabled = false;

	/**
	 * @var bool TRUE|FALSE. Whether or not there is a Child Key.
	 */
	protected $childKey = false;

	/** @var string Child Site File. */
	protected $childFile;

	/** @var string Plugin handle. */
	protected $plugin_handle = 'mainwp-sucuri-extension';

	/** @var string Product ID. */
	protected $product_id = 'MainWP Sucuri Extension';

	/** @var string Extension version. */
	protected $software_version = '4.0.11';

	/**
	 * MainWP_Sucuri_Extension_Activator constructor.
	 *
	 * @uses MainWP_Sucuri_Extension::includes()
	 * @uses MainWP_Sucuri_Extension::activate_this_plugin()
	 * @uses MainWP_Sucuri_WP_CLI_Command::init()
	 */
	public function __construct() {
		$this->childFile = __FILE__;
		$this->includes();
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( false !== $this->mainwpMainActivated ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			MainWP_Sucuri_WP_CLI_Command::init();
		}
	}

	/**
	 * MainWP Sucuri Extension included files.
	 */
	public function includes() {
		require_once( 'class/mainwp-sucuri-db.class.php' );
		require_once( 'class/mainwp-sucuri-wp-cli-command.class.php' );
		require_once( 'class/mainwp-sucuri.class.php' );
	}

	/**
	 * Get MainWP Sucuri Extension array.
	 *
	 * @param array $pArray Extension array.
	 * @return array $pArray This Extension array.
	 */
	public function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin' 		 => __FILE__,
			'api' 	 		 => $this->plugin_handle,
			'mainwp' 		 => true,
			'callback' 	 => array( &$this, 'settings' ),
			'apiManager' => true
		);
		return $pArray;
	}

	/**
	 * Render MainWP Sucuri Extension settings page.
	 *
	 * @uses MainWP_Sucuri::renderSettings()
	 */
	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Sucuri::renderSettings();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * Activate the MainWP Sucuri Extension.
	 *
	 * @uses MainWP_Sucuri_Extension()
	 */
	public function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-sucuri-extension' ) ) {
			return;
		}
		new MainWP_Sucuri_Extension();
	}

	/**
	 * MainWP Sucuri Error Notices.
	 *
	 * @uses MainWP_Sucuri_Extension::mainwpMainActivated()
	 */
	public function mainwp_error_notice() {

		/** @global string #current_screen Current page. */
		global $current_screen;

		if ( 'plugins' == $current_screen->parent_base && false == $this->mainwpMainActivated ) {
			echo '<div class="error"><p>MainWP Sucuri Extension ' . __( 'requires <a href="https://mainwp.com" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-sucuri-extension' ) . '</p></div>';
		}
	}

	/**
	 * Get Child Key.
	 *
	 * @return bool TRUE|FALSE.
	 */
	public function get_child_key() {
		return $this->childKey;
	}

	/**
	 * Get Child file.
	 *
	 * @return string Child File.
	 */
	public function get_child_file() {
		return $this->childFile;
	}

	/**
	 * Check if Child Plugin enabled.
	 *
	 * @return bool TRUE|FALSE. Whether or not the MainWP Child Plugin is enabled.
	 */
	public function is_enabled() {
		return $this->childFile ? true : false;
	}

	/**
	 * Update MainWP Sucuri Extension DB options.
	 *
	 * @param string $option_name Option name to update.
	 * @param string $option_value Option value to update.
	 * @return string $success Return success message.
	 */
	public function update_option($option_name, $option_value ) {
		$success = add_option( $option_name, $option_value, '', 'no' );

		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}

		return $success;
	}

	/**
	 * Activate MainWP Sucuri Extension.
	 */
	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle , $options );
	}

	/**
	 * De-activate MainWP Sucuri Extension.
	 */
	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

/** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
global $mainWPSucuriExtensionActivator;
$mainWPSucuriExtensionActivator = new MainWP_Sucuri_Extension_Activator();
