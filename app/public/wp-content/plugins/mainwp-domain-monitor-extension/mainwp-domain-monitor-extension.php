<?php
/**
 * Plugin Name: MainWP Domain Monitor Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Domain Monitor Extension lets you keep a watchful eye on your domains. It alerts you via email when monitored domains are nearing expiration.
 * Version: 4.0.1
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://kb.mainwp.com/docs/mainwp-domain-monitor-extension/
 * Icon URI: https://mainwp.com/wp-content/uploads/2021/12/domain-monitor.png
 */

namespace MainWP\Extensions\Domain_Monitor;

if ( ! defined( 'MAINWP_DOMAIN_MONITOR_PLUGIN_FILE' ) ) {
	define( 'MAINWP_DOMAIN_MONITOR_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_DOMAIN_MONITOR_PLUGIN_DIR' ) ) {
	define( 'MAINWP_DOMAIN_MONITOR_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'MAINWP_DOMAIN_MONITOR_PLUGIN_URL' ) ) {
	define( 'MAINWP_DOMAIN_MONITOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'MAINWP_DOMAIN_MONITOR_PLUGIN_SLUG' ) ) {
	define( 'MAINWP_DOMAIN_MONITOR_PLUGIN_SLUG', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'MAINWP_DOMAIN_MONITOR_LOG_PRIORITY_NUMBER' ) ) {
	define( 'MAINWP_DOMAIN_MONITOR_LOG_PRIORITY_NUMBER', 65 );
}


class MainWP_Domain_Monitor_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-domain-monitor-extension';
	protected $product_id       = 'MainWP Domain Monitor Extension';
	protected $software_version = '4.0.1';

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

		// includes rest api work.
		require_once 'class/class-mainwp-domain-monitor-rest-api.php';
		Rest_Api::instance()->init();
	}


	/**
	 * Loads class automatically.
	 *
	 * @param string $class_name Class name.
	 *
	 * @return void
	 */
	public function autoload( $class_name ) {
		if ( 0 === strpos( $class_name, 'MainWP\Extensions\Domain_Monitor' ) ) {
			// trip the namespace prefix: MainWP\Extensions\Domain_Monitor\.
			$class_name = substr( $class_name, 33 );
		}
		if ( 0 !== strpos( $class_name, 'MainWP_Domain_Monitor_' ) ) {
			return;
		}
		$class_name = strtolower( str_replace( '_', '-', $class_name ) );
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'class' . DIRECTORY_SEPARATOR . 'class-' . $class_name . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}

	/**
	 * Add your extension to MainWP via the 'mainwp_getextensions' filter.
	 *
	 * @param array $params Array containing the extensions info.
	 *
	 * @return array $params Updated array containing the extensions info.
	 */
	public function get_this_extension( $params ) {
		$params[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);
		return $params;
	}

	/**
	 * Displays the extension page with adequate header and footer.
	 */
	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Domain_Monitor_Admin::render_extension_page();
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
			'id'            => 'domain-monitor-widget',
			'plugin'        => $this->childFile,
			'key'           => $this->childKey,
			'metabox_title' => __( 'Domain Monitor', 'mainwp-domain-monitor-extension' ),
			'callback'      => array( MainWP_Domain_Monitor_Admin::class, 'render_metabox' ),
		);
		return $metaboxes;
	}

	/**
	 * Activate the extension API license and initiate the extension.
	 */
	public function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-domain-monitor-extension' ) ) {
			return;
		}

		add_filter( 'mainwp_getmetaboxes', array( &$this, 'get_metaboxes' ) );
		add_filter( 'mainwp_widgets_screen_options', array( MainWP_Domain_Monitor_Admin::get_instance(), 'widgets_screen_options' ), 10, 1 );

		MainWP_Domain_Monitor_Admin::get_instance();
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
	 * Render the warning notice if the MainWP Dashboard plugin is not activated.
	 */
	public function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Domain Monitor Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-domain-monitor-extension' ) . '</p></div>';
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

global $mainWPDomainMonitorExtensionActivator;
$mainWPDomainMonitorExtensionActivator = new MainWP_Domain_Monitor_Extension_Activator();
