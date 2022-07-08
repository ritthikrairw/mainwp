<?php
/**
 * MainWP Virusdie Extension
 *
 * @package MainWP/Extensions
 *
 * MainWP Virusdie Extension uses Virusdie, a powerful, user-friendly, and professional-grade antivirus for your websites. It helps you monitor all your sites directly from your MainWP Dashboard.
 *
 * Plugin Name: MainWP Virusdie Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Virusdie Extension uses Virusdie, a powerful, user-friendly, and professional-grade antivirus for your websites. It helps you monitor all your sites directly from your MainWP Dashboard.
 * Version: 4.0.0
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/virusdie/
 * Icon URI: https://mainwp.com/wp-content/uploads/2021/02/virusdie.png
 */

namespace MainWP\Extensions\Virusdie;

if ( ! defined( 'MAINWP_VIRUSDIE_URL' ) ) {
	define( 'MAINWP_VIRUSDIE_URL', plugins_url( '', __FILE__ ) );
}

/**
 * Class MainWP_Virusdie_Extension_Activator
 */
class MainWP_Virusdie_Extension_Activator {

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
	protected $plugin_handle = 'mainwp-virusdie-extension';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Protected variable containg the extension ID (product title).
	 *
	 * @var string
	 */
	protected $product_id = 'MainWP Virusdie Extension';

	/**
	 * Protected variable containg the extension version number.
	 *
	 * @var string
	 */
	protected $software_version = '4.0.0';

	/**
	 * MainWP_Virusdie_Extension_Activator class constructor.
	 *
	 * @uses register_activation_hook() Set the activation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
	 *
	 * @uses register_deactivation_hook() Set the deactivation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
	 *
	 * @return void
	 */
	public function __construct() {
		$this->plugin_slug = plugin_basename( __FILE__ );

		$this->childFile = __FILE__;
		$this->includes();
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( false !== $this->mainwpMainActivated ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			MainWP_Virusdie_WP_CLI_Command::init();
		}

		// includes rest api work.
		require 'class/class-mainwp-virusdie-rest-api.php';
		Rest_Api::instance()->init();
	}

	/**
	 * Includes required files.
	 *
	 * @return void
	 */
	public function includes() {
		require_once 'class/class-mainwp-virusdie-extension.php';
		require_once 'class/class-mainwp-virusdie-db.php';
		require_once 'class/class-mainwp-virusdie-wp-cli-command.php';
		require_once 'class/class-mainwp-virusdie-settings-base.php';
		require_once 'class/class-mainwp-virusdie-api.php';
		require_once 'class/class-mainwp-virusdie.php';
		require_once 'class/class-mainwp-virusdie-reports-data.php';
	}


	/**
	 * Sets the plugin meta row data.
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

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );
		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || 'Activated' != $api_data['activated_key'] || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
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
	 *
	 * @uses MainWP_Virusdie::render_pages()
	 */
	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Virusdie::get_instance()->render_pages();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * Activate the extension API license and initiate the extension.
	 */
	public function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-virusdie-extension' ) ) {
			return;
		}
		new MainWP_Virusdie_Extension();
	}

	/**
	 * Render the warning notice if the MainWP Dashboard plugin is not activated.
	 */
	public function mainwp_error_notice() {

		/**
		 * Current screen
		 *
		 * @global string
		 */
		global $current_screen;

		if ( 'plugins' == $current_screen->parent_base && false == $this->mainwpMainActivated ) {
			echo '<div class="error"><p>MainWP Virusdie Extension ' . __( 'requires <a href="https://mainwp.com" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-virusdie-extension' ) . '</p></div>';
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

/**
 * Extension object
 *
 * @global object
 */
global $mainWPVirusdieExtensionActivator;
$mainWPVirusdieExtensionActivator = new MainWP_Virusdie_Extension_Activator();
