<?php
/**
 * Plugin Name: Advanced Uptime Monitor Extension
 *
 * Description: MainWP Extension for real-time uptime monitoring.
 *
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Plugin URI: https://mainwp.com/
 * Version:  5.2.2
 * Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/advanced-uptime-monitor/
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

if ( ! defined( 'MAINWP_MONITOR_PLUGIN_FILE' ) ) {
	define( 'MAINWP_MONITOR_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_MONITOR_PLUGIN_PATH' ) ) {
	define( 'MAINWP_MONITOR_PLUGIN_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'MAINWP_MONITOR_API_LIMIT_PER_PAGE' ) ) {
	define( 'MAINWP_MONITOR_API_LIMIT_PER_PAGE', 50 );
}

require_once MAINWP_MONITOR_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';

/**
 * Class MainWP_AUM_Extension_Activator
 *
 * Initiate the Extension activator.
 */
class MainWP_AUM_Extension_Activator {

	/**
	 * Protected variable containg information about MainWP plugin status.
	 *
	 * @var bool
	 */
	protected $mainwpMainActivated = false;

	/**
	 * Protected variable containg information about the Extension status.
	 *
	 * @var bool
	 */
	protected $childEnabled = false;

	/**
	 * Protected variable containg the Extension key.
	 *
	 * @var bool|string
	 */
	protected $childKey = false;

	/**
	 * Protected variable containg extension file.
	 *
	 * @var string
	 */
	protected $childFile;

	/**
	 * Protected variable containg the extension handle.
	 *
	 * @var string
	 */
	protected $plugin_handle = 'advanced-uptime-monitor-extension';

	/**
	 * Protected variable containg the extension ID (product title).
	 *
	 * @var string
	 */
	protected $product_id = 'Advanced Uptime Monitor Extension';

	/**
	 * Protected variable containg the extension version number.
	 *
	 * @var string
	 */
	protected $software_version = '5.2.2';

	/**
	 * MainWP_AUM_Extension_Activator class constructor.
	 *
	 * @uses register_activation_hook() Set the activation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
	 *
	 * @uses register_deactivation_hook() Set the deactivation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
	 */
	public function __construct() {
		$this->childFile = __FILE__;
		spl_autoload_register( array( &$this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );

		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );
		if ( false !== $this->mainwpMainActivated ) {
			$this->activate_this();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this' ) );
		}
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_filter( 'mainwp_aum_get_data', array( MainWP_AUM_Settings_Page::get_instance(), 'aum_get_data' ), 10, 3 ); // ok
	}

	/**
	 * Loads class automatically.
	 *
	 * @param string $class_name Class name.
	 *
	 * @return void
	 */
	public function autoload( $class_name ) {

		if ( 0 === strpos( $class_name, 'MainWP\Extensions\AUM' ) ) {
			// trip the namespace prefix: MainWP\Extensions\AUM\.
			$class_name = substr( $class_name, 22 );
		}

		if ( 0 !== strpos( $class_name, 'MainWP_AUM_' ) ) {
			return;
		}

		$autoload_types = array(
			'class' => 'class',
		);

		foreach ( $autoload_types as $type => $prefix ) {
			$autoload_dir  = \trailingslashit( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $type );
			$autoload_path = sprintf( '%s%s-%s.php', $autoload_dir, $prefix, strtolower( str_replace( '_', '-', $class_name ) ) );
			if ( file_exists( $autoload_path ) ) {
				require_once $autoload_path;
				break;
			}
		}
	}

	/**
	 * Get the extenion key.
	 *
	 * @return string
	 */
	public function get_child_key() {
		return $this->childKey;
	}

	/**
	 * Get the extension file.
	 *
	 * @return string
	 */
	public function get_child_file() {
		return $this->childFile;
	}

	/**
	 * Add your extension to MainWP via the 'mainwp_getextensions' filter.
	 *
	 * @param array $pArray Array containing the extensions info.
	 *
	 * @return array $pArray Updated array containing the extensions info.
	 */
	public function get_this_extension( $params ) {
		$params[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( $this, 'render_settings' ),
			'apiManager' => true,
		);
		return $params;
	}

	/**
	 * Displays the extension page with adequate header and footer.
	 *
	 * @uses MainWP_AUM_Settings_Page::render_tabs();
	 */
	public function render_settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_AUM_Settings_Page::get_instance()->render_tabs();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * Adds metabox (widget) on the MainWP Dashboard overview page via the 'mainwp_getmetaboxes' filter.
	 *
	 * @param array $metaboxes Array containing metaboxes data.
	 *
	 * @return array $metaboxes Updated array that contains metaboxes data.
	 */
	public function get_metaboxes( $metaboxes ) {
		if ( ! $this->childEnabled ) {
			return $metaboxes;
		}

		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}
		$metaboxes[] = array(
			'id'            => 'aum-widget',
			'plugin'        => $this->childFile,
			'key'           => $this->childKey,
			'metabox_title' => __( 'Monitors', 'advanced-uptime-monitor-extension' ),
			'callback'      => array( MainWP_AUM_Main::get_instance(), 'render_metabox' ),
		);
		return $metaboxes;
	}

	/**
	 * Activate the extension API license and initiate the extension.
	 */
	public function activate_this() {

		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-aum-extension' ) ) {
			return;
		}

		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey     = $this->childEnabled['key'];

		$main = MainWP_AUM_Main::get_instance();
		add_filter( 'mainwp_getmetaboxes', array( &$this, 'get_metaboxes' ) );
		add_filter( 'mainwp_widgets_screen_options', array( $main, 'widgets_screen_options' ), 10, 1 );
	}

	/**
	 * Method admin_init()
	 *
	 * Enqueue Styles and Scripts.
	 */
	public function admin_init() {
		if ( isset( $_GET['page'] ) ) {
			if ( 'Extensions-Advanced-Uptime-Monitor-Extension' == $_GET['page'] || 'managesites' == $_GET['page'] ) {
				wp_enqueue_style( 'mainwp-aum', plugins_url( 'css/mainwp-aum.css', __FILE__ ), array(), '4.0' );
				wp_enqueue_script( 'mainwp-aum', plugins_url( 'js/mainwp-aum.js', __FILE__ ), array(), '4.0', true );
			}
			if ( $_GET['page'] == 'Extensions-Advanced-Uptime-Monitor-Extension' ) {
				wp_enqueue_script( 'mainwp-aum-chart', plugins_url( 'js/loader.js', __FILE__ ), true );
			}
		}
		MainWP_AUM_Main_Controller::instance()->admin_init();
	}

	/**
	 * Render the warning notice if the MainWP Dashboard plugin is not activated.
	 */
	public function mainwp_error_notice() {
		global $current_screen;
		if ( 'plugins' == $current_screen->parent_base && false == $this->mainwpMainActivated ) {
			echo '<div class="error"><p>Advanced Uptime Monitor Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	/**
	 * Activate the extension license.
	 */
	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

	/**
	 * Deactivate the extension license.
	 */
	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainwpAUMExtensionActivator;
$mainwpAUMExtensionActivator = new \MainWP\Extensions\AUM\MainWP_AUM_Extension_Activator();
