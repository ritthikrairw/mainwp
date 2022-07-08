<?php
/**
 * Plugin Name: MainWP Custom Dashboard Extension
 * Plugin URI: https://mainwp.com
 * Description: The purpose of this plugin is to contain your customisation snippets for your MainWP Dashboard.
 * Version: 4.0.2
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/category/mainwp-extensions/custom-dashboard/
 * Icon URI: https://mainwp.com/wp-content/uploads/2019/09/custom-dashboard-1.png
 */

if ( ! defined( 'MAINWP_CUSTOM_DASHBOARD_PLUGIN_FILE' ) ) {
	define( 'MAINWP_CUSTOM_DASHBOARD_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_CUSTOM_DASHBOARD_PLUGIN_URL' ) ) {
	define( 'MAINWP_CUSTOM_DASHBOARD_PLUGIN_URL', plugin_dir_url( MAINWP_CUSTOM_DASHBOARD_PLUGIN_FILE ) );
}

/**
 * Main MainWP_Custom_Dashboard_Extension Class
 *
 * @class MainWP_Custom_Dashboard_Extension
 * @version 1.0
 * @since 1.0
 * @package MainWP_Custom_Dashboard_Extension
 */


class MainWP_Custom_Dashboard_Extension {

	public static $instance = null;
	protected $plugin_url;
	public $plugin_slug;
	protected $plugin_dir;


	/**
	 * Set up the plugin
	 */
	public function __construct() {
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		MainWP_Custom_Dashboard::get_instance();
	}

	public function init() {
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

	public function admin_init() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Custom-Dashboard-Extension' == $_GET['page'] ) ) {
			wp_enqueue_style( 'mainwp-custom-dashboard-extension-codemirror', $this->plugin_url . 'libs/codemirror/lib/codemirror.css' );
			wp_enqueue_style( 'mainwp-custom-dashboard-extension-codemirror-night', $this->plugin_url . 'libs/codemirror/theme/night.css' );
			wp_enqueue_style( 'mainwp-custom-dashboard-extension-codemirror-xq-dark', $this->plugin_url . 'libs/codemirror/theme/xq-dark.css' );
			wp_enqueue_style( 'mainwp-custom-dashboard-extension-codemirror-the-matrix', $this->plugin_url . 'libs/codemirror/theme/the-matrix.css' );
			wp_enqueue_style( 'mainwp-custom-dashboard-extension-codemirror-erlang-dark', $this->plugin_url . 'libs/codemirror/theme/erlang-dark.css' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-codemirror', $this->plugin_url . 'libs/codemirror/lib/codemirror.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-addon-matchbrackets', $this->plugin_url . 'libs/codemirror/addon/edit/matchbrackets.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-addon-active-line', $this->plugin_url . 'libs/codemirror/addon/selection/active-line.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-mode-htmlmixed', $this->plugin_url . 'libs/codemirror/mode/htmlmixed/htmlmixed.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-mode-xml', $this->plugin_url . 'libs/codemirror/mode/xml/xml.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-mode-javascript', $this->plugin_url . 'libs/codemirror/mode/javascript/javascript.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-mode-css', $this->plugin_url . 'libs/codemirror/mode/css/css.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-mode-clike', $this->plugin_url . 'libs/codemirror/mode/clike/clike.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension-mode-php', $this->plugin_url . 'libs/codemirror/mode/php/php.js' );
			wp_enqueue_script( 'mainwp-custom-dashboard-extension', $this->plugin_url . 'js/mainwp-custom-dashboard-admin.js' );
			wp_enqueue_style( 'mainwp-custom-dashboard-extension', $this->plugin_url . 'css/mainwp-custom-dashboard.css' );
		}
	}

} // End Class



class MainWP_Custom_Dashboard_Activator {

		/**
		 * Handle if MainWP plugin is activated or not and by default its false.
		 *
		 * @var bool $mainwpMainActivated
		 */
	protected $mainwpMainActivated = false;

		/**
		 * Handle if child site is enabled or not and by default its false.
		 *
		 * @var bool $childEnabled
		 */
	protected $childEnabled = false;

		/**
		 * Holds the child site key and by default its false.
		 *
		 * @var bool|string
		 */
	protected $childKey = false;

		/**
		 * Holds the child file path.
		 *
		 * @var string $childFile
		 */
	protected $childFile;

		/**
		 * Handle plugin name.
		 *
		 * @var string $plugin_handle
		 */
	protected $plugin_handle = 'mainwp-custom-dashboard-extension';

		/**
		 * Handle product id.
		 *
		 * @var string $product_id
		 */
	protected $product_id = 'MainWP Custom Dashboard Extension';

		/**
		 * The software version.
		 *
		 * @var string $software_version
		 */
	protected $software_version = '4.0.2';

		/**
		 * This function automatically called once an object is created.
		 */
	public function __construct() {

			$this->childFile = __FILE__;

			$this->includes();

			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			/**
			 * This hook allows you to add your extension to MainWP via the 'mainwp-getextensions' filter.
			 *
			 * @link https://codex.mainwp.com/#mainwp-getextensions
			 */
			add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) ); // This is a filter similar to adding the management page in WordPress. It calls the function 'get_this_extension', which adds to the $extensions array. This array is a list of all of the extensions MainWP uses, and the functions that it has to call to show settings for them. In this case, the function is 'settings'.

			$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false ); // This variable checks to see if mainwp is activated, and by default it will return false unless it finds in WordPress that it's on.

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin(); // If MainWP is activated, then call the function 'activate_this_plugin'
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
			add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) ); // Notices displayed near the top of admin pages.
	}

	function includes() {
		require_once 'class/mainwp-custom-dashboard.class.php';
	}

		/**
		 * This function allows you to add your extension to MainWP via the 'mainwp-getextensions' filter.
		 *
		 * @link https://codex.mainwp.com/#mainwp-getextensions
		 *
		 * @param array $pArray
		 *
		 * @return array
		 */
	function get_this_extension( $pArray ) {
			$pArray[] = array(
				'plugin'     => __FILE__,
				'api'        => $this->plugin_handle,
				'mainwp'     => true,
				'callback'   => array( &$this, 'settings' ),
				'apiManager' => true,
			);

			// this just adds the plugin's extension settings page to the array $extensions so it knows what to call.
			return $pArray;
	}

		/**
		 * This function displays content on our extensions screen with mainwp header and footer.
		 *
		 * @return void
		 */
	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Custom_Dashboard::get_instance()->render_settings(); // Displays the content in our extension
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

		/**
		 * This function "activate_this_plugin" is called when the main is initialized.
		 *
		 * @return void
		 */
	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];

		/**
		 * Initialise the plugin
		 */
		new MainWP_Custom_Dashboard_Extension();
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Custom Dashboard Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

		/**
		 * This function gets the key of child site
		 *
		 * @return string
		 */
	public function get_child_key() {
		return $this->childKey;
	}

		/**
		 * This function gets the child file
		 *
		 * @return string
		 */
	public function get_child_file() {
		return $this->childFile;
	}

		/**
		 * This function will automatically called at the time of activation of plugin.
		 *
		 * @return void
		 */
	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

		/**
		 * This function will automatically called at the time of deactivation of plugin.
		 *
		 * @return void
		 */
	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}

}

global $mainWPCustomisationsActivator;
$mainWPCustomisationsActivator = new MainWP_Custom_Dashboard_Activator();
