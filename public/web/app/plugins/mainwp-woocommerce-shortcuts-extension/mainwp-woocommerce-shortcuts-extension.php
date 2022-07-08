<?php
/**
 * MainWP WooCommerce Shortcuts Extension
 *
 * MainWP WooCommerce Shortcuts provides you a quick access WooCommerce pages in your network. Requires MainWP Dashboard plugin.
 *
 * Plugin Name: MainWP WooCommerce Shortcuts Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP WooCommerce Shortcuts provides you a quick access WooCommerce pages in your network. Requires MainWP Dashboard plugin.
 * Version: 4.1.2
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/category/mainwp-extensions/woocommerce-shortcuts/
 *
 * @package MainWP/WooCommerce_Shortcuts
 */

namespace MainWP\WooCommerce_Shortcuts;

if ( ! defined( 'MAINWP_WOO_SHORTCUTS_PLUGIN_FILE' ) ) {
	define( 'MAINWP_WOO_SHORTCUTS_PLUGIN_FILE', __FILE__ );
}

/**
 * Class MainWP_WooCommerce_Shortcuts_Extension
 *
 * Initiate the WooCommerce Shortcuts Extension
 */
class MainWP_WooCommerce_Shortcuts_Extension {

	/**
	 * Protected variable to hold the plugin URL.
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * Public variable to hold the plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Protected variable to hold the plugin directory.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Protected variable to hold option value.
	 *
	 * @var string
	 */
	protected $option;

	/**
	 * Protected variable to hold option handle.
	 *
	 * @var string
	 */
	protected $option_handle = 'mainwp_woocommerce_shortcuts_extension';

	/**
	 * MainWP_WooCommerce_Shortcuts_Extension class constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		$this->option      = get_option( $this->option_handle );

		add_action( 'init', array( &$this, 'localization' ) );  // Loads the plugin's translated strings.
		add_action( 'admin_init', array( &$this, 'admin_init' ) ); // Loads required files on admin page initilization.

		/**
		 * The plugin_row_meta filter hook is used to add additional links below each plugin on the plugins page.
		 */
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );

		new MainWP_WooCommerce_Shortcuts();
	}

	/**
	 * Loads the extension’s translated strings.
	 *
	 * @uses load_plugin_textdomain() Loads a plugin’s translated strings.
	 * @see https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
	 */
	public function localization() {
		load_plugin_textdomain( 'mainwp-woocommerce-shortcuts-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Hook upate info to the plugin row in WP > Plugins > Installed Plugins page thorugh the plugin_row_meta filter.
	 *
	 * @param  array  $plugin_meta Array that contains the pulgin meta data.
	 * @param  string $plugin_file Plugin file (slug).
	 *
	 * @uses get_option() Retrieves an option value based on an option name.
	 * @see https://developer.wordpress.org/reference/functions/get_option/
	 *
	 * @used-by MainWP_WooCommerce_Shortcuts_Extension::__construct()
	 *
	 * @return array Updated array that contains the pulgin meta data.
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

		$plugin_meta[] = '<a href="' . esc_url( '?do=checkUpgrade' ) . '" title="Check for updates.">' . __( 'Check for updates now', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>';

		return $plugin_meta;
	}

	/**
	 * Enqueue styles and scripts through the admin_init hook.
	 *
	 * @uses wp_enqueue_style() Enqueue a CSS stylesheet.
	 * @see https://developer.wordpress.org/reference/functions/wp_enqueue_style/
	 *
	 * @uses get_plugin_data() Parses the plugin contents to retrieve plugin’s metadata.
	 * @see https://developer.wordpress.org/reference/functions/get_plugin_data/
	 *
	 * @used-by MainWP_WooCommerce_Shortcuts_Extension::__construct()
	 */
	public function admin_init() {
		$plugin_data     = get_plugin_data( __FILE__ );
		$current_version = $plugin_data['Version'];
		wp_enqueue_style( 'mainwp-woocommerce-shortcuts-extension', $this->plugin_url . 'css/mainwp-wc-shortcuts.css', array(), $current_version );
	}

}

/**
 * Class MainWP_WooCommerce_Shortcuts_Extension_Activator
 *
 * Initiate the WooCommerce Shortcuts activator.
 */
class MainWP_WooCommerce_Shortcuts_Extension_Activator {

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
	protected $plugin_handle = 'mainwp-woocommerce-shortcuts-extension';

	/**
	 * Protected variable containg the extension ID (product title).
	 *
	 * @var string
	 */
	protected $product_id = 'MainWP WooCommerce Shortcuts Extension';

	/**
	 * Protected variable containg the extension version number.
	 *
	 * @var string
	 */
	protected $software_version = '4.1.2';

	/**
	 * MainWP_WooCommerce_Shortcuts_Extension_Activator class constructor.
	 *
	 * @uses register_activation_hook() Set the activation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
	 *
	 * @uses register_deactivation_hook() Set the deactivation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
	 *
	 * @uses MainWP_WooCommerce_Shortcuts_Extension_Activator::includes()
	 * @uses MainWP_WooCommerce_Shortcuts_Extension_Activator::activate_this()
	 * @uses MainWP_WooCommerce_Shortcuts_Extension_Activator::mainwp_error_notice()
	 */
	public function __construct() {
		$this->childFile = __FILE__;

		$this->includes();
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) ); // This is a filter similar to adding the management page in WordPress. It calls the function 'get_this_extension', which adds to the $extensions array. This array is a list of all of the extensions MainWP uses, and the functions that it has to call to show settings for them. In this case, the function is 'settings'.
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false ); // This variable checks to see if mainwp is activated, and by default it will return false unless it finds in WordPress that it's on.

		if ( false !== $this->mainwpMainActivated ) {
			$this->activate_this(); // If MainWP is activated, then call the function 'activate_this'.
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this' ) );
		}
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) ); // Notices displayed near the top of admin pages.
	}

	/**
	 * Include extension files.
	 */
	public function includes() {
		require_once 'class/mainwp-woocommerce-shortcuts.class.php';
	}

	/**
	 * Activate the extension API license and initiate the extension.
	 */
	function activate_this() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ ); // If the plugin is not enabled this will return false, if the plugin is enabled, an array will be returned containing a key
		$this->childKey            = $this->childEnabled['key']; // Handle key of child.

		/**
		 * Check whether current user has capability or role to access the extension.
		 */
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-woocommerce-shortcuts-extension' ) ) {
			return;
		}
		/**
		 * This hook allows you to add metabox-widgets on the main dashboard via the 'mainwp-getmetaboxes' filter.
		 *
		 * @link https://codex.mainwp.com/#mainwp-getmetaboxes
		 */
		add_filter( 'mainwp_getmetaboxes', array( &$this, 'get_metaboxes' ) );

		/**
		 * Initialise the plugin
		 */
		new MainWP_WooCommerce_Shortcuts_Extension();
	}


	/**
	 * Add your extension to MainWP via the 'mainwp_getextensions' filter.
	 *
	 * @param array $pArray Array containing the extensions info.
	 *
	 * @return array $pArray Updated array containing the extensions info.
	 */
	public function get_this_extension( $pArray ) {
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
	 * Adds metabox (widget) on the MainWP Dashboard overview page via the 'mainwp_getmetaboxes' filter.
	 *
	 * @param array $metaboxes Array containing metaboxes data.
	 *
	 * @return array $metaboxes Updated array that contains metaboxes data.
	 */
	public function get_metaboxes( $metaboxes ) {
		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}
		if ( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] ) {
			$metaboxes[] = array(
				'plugin'        => $this->childFile,
				'key'           => $this->childKey,
				'metabox_title' => MainWP_WooCommerce_Shortcuts::get_name(),
				'callback'      => array( MainWP_WooCommerce_Shortcuts::class, 'render_woocommerce_shortcuts_widget' ),
			);
		}

		return $metaboxes;
	}

	/**
	 * Displays the extension page with adequate header and footer.
	 *
	 * @uses MainWP_WooCommerce_Shortcuts::render_settings()
	 */
	function settings() {
		/**
		 * This hook allows you to render the tabs on the Extensions screen via the 'mainwp-pageheader-extensions' filter.
		 *
		 * @link https://codex.mainwp.com/#mainwp-pageheader-extensions
		 */
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_WooCommerce_Shortcuts::render_settings(); // Displays the content in our extension
		/**
		 * This hook allows you to render the footer on the Extensions screen via the 'mainwp-pagefooter-extensions' filter.
		 *
		 * @link https://codex.mainwp.com/#mainwp-pagefooter-extensions
		 */
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * Render the warning notice if the MainWP Dashboard plugin is not activated.
	 */
	public function mainwp_error_notice() {
		global $current_screen;
		if ( 'plugins' == $current_screen->parent_base && false == $this->mainwpMainActivated ) {
			$string1 = __( 'MainWP WooCommerce Shortcuts Extension requires ', 'mainwp-woocommerce-shortcuts-extension' );
			$string2 = __( 'MainWP Dashboard Plugin', 'mainwp-woocommerce-shortcuts-extension' );
			$string3 = __( ' to be activated in order to work. Please install and activate ', 'mainwp-woocommerce-shortcuts-extension' );
			$string4 = __( 'MainWP Dashboard Plugin', 'mainwp-woocommerce-shortcuts-extension' );
			$string5 = __( ' first.', 'mainwp-woocommerce-shortcuts-extension' );
			printf( '<div class="error"><p>%s <a href="' . esc_url( 'http://mainwp.com/' ) . '" target="_blank">%s</a>%s<a href="' . esc_url( 'http://mainwp.com/' ) . '" target="_blank">%s</a>%s</p></div>', $string1, $string2, $string3, $string4, $string5 );
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

global $mainWPWooCommerceShortcutsExtensionActivator;
$mainWPWooCommerceShortcutsExtensionActivator = new MainWP_WooCommerce_Shortcuts_Extension_Activator();
