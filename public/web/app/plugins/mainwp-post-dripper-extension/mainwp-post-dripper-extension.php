<?php
/*
 * Plugin Name: MainWP Post Dripper Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Post Dripper Extension allows you to deliver posts or pages to your network of sites over a pre-scheduled period of time. Requires MainWP Dashboard plugin.
 * Version: 4.0.4
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/post-dripper/
 *
 * @package MainWP/Extensions/Post Dripper
 */

if ( ! defined( 'MAINWP_POST_DRIPPER_PLUGIN_FILE' ) ) {
	define( 'MAINWP_POST_DRIPPER_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_POST_DRIPPER_LOG_PRIORITY_NUMBER' ) ) {
	define( 'MAINWP_POST_DRIPPER_LOG_PRIORITY_NUMBER', 52 );
}

/**
 * Class MainWP_Dripper_Extension
 *
 * Initiates main extension functins.
 */
class MainWP_Dripper_Extension {

	/**
	 * @var object $instance
	 */
	public static $instance = null;

	/**
	 * @var string $plugin_handle
	 */
	public $plugin_handle   = 'mainwp-post-dripper-extension';

	/**
	 * @var string $plugin_url
	 */
	protected $plugin_url;

	/**
	 * @var string $plugin_slug
	 */
	private $plugin_slug;

	/**
	 * @var string $plugin_dir
	 */
	protected $plugin_dir;

	/**
	 * @var null $plugin_dir
	 */
	private $mainWPDripper = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return object Class instance.
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Dripper_Extension();
		}
		return self::$instance;
	}

	/**
	 * Class construtor.
	 */
	public function __construct() {
		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'mainwp_admin_menu', array( &$this, 'init_menu' ), 9 );
		$this->mainWPDripper = new MainWP_Dripper();
	}

	/**
	 * Hooks into the init.
	 */
	public function init() {
		$this->mainWPDripper->init();
	}

	/**
	 * Hooks into the mainwp_admin_menu.
	 */
	public function init_menu() {
		$this->mainWPDripper->init_menu();
	}

	/**
	 * Renders the plugin row customizations.
	 *
	 * @param array  $plugin_meta Plugin meta data.
	 * @param string $plugin_file Plugin file.
	 *
	 * @return array $plugin_meta Plugin meta data.
	 */
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

	/**
	 * Enqueues Styles and Scripts.
	 */
	public function admin_init() {
		wp_enqueue_style( 'mainwp-dripper-extension', $this->plugin_url . 'css/mainwp-dripper.css' );
		if ( isset( $_REQUEST['page'] ) && 'Extensions-Mainwp-Post-Dripper-Extension' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'mainwp-dripper-extension', $this->plugin_url . 'js/mainwp-dripper.js' );
		}

		add_action( 'mainwp_bulkpost_edit', array( &$this, 'dripper_metabox' ), 10, 2 );

		$this->mainWPDripper->admin_init();
	}

	/**
	 * Includes the Post Ripper metabox.
	 *
	 * @param array  $post      Array conaining post data.
	 * @param string $post_type Post type.
	 *
	 * @return void
	 */
	public function dripper_metabox( $post, $post_type ) {
		global $post;
		include $this->plugin_dir . '/includes/dripper-metabox.php';
	}
}

/**
 * Class MainWP_Dripper_Extension_Activator
 *
 * Initiate the Extension activator.
 */
class MainWP_Dripper_Extension_Activator {

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
	protected $childEnabled        = false;

	/**
	 * Protected variable containg the Extension key.
	 *
	 * @var bool|string
	 */
	protected $childKey            = false;

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
	protected $plugin_handle    = 'mainwp-post-dripper-extension';

	/**
	 * Protected variable containg the extension ID (product title).
	 *
	 * @var string
	 */
	protected $product_id       = 'MainWP Post Dripper Extension';

	/**
	 * Protected variable containg the extension version number.
	 *
	 * @var string
	 */
	protected $software_version = '4.0.4';

	/**
	 * MainWP_Dripper_Extension_Activator class constructor.
	 *
	 * @uses register_activation_hook() Set the activation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_activation_hook/
	 *
	 * @uses register_deactivation_hook() Set the deactivation hook for a plugin.
	 * @see https://developer.wordpress.org/reference/functions/register_deactivation_hook/
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
	}

	/**
	 * Includes the Post Dripper class.
	 *
	 * @return void
	 */
	public function includes() {
		require_once 'class/mainwp-dripper.class.php';
	}

	/**
	 * Add your extension to MainWP via the 'mainwp_getextensions' filter.
	 *
	 * @param array $args Array containing the extensions info.
	 *
	 * @return array $args Updated array containing the extensions info.
	 */
	public function get_this_extension( $args ) {
		$args[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);
		return $args;
	}

	/**
	 * Displays the extension page with adequate header and footer.
	 *
	 * @uses MainWP_Dripper::render();
	 */
	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Dripper::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * Activate the extension API license and initiate the extension.
	 */
	public function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-post-dripper-extension' ) ) {
			return;
		}
		new MainWP_Dripper_Extension();
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
		if ( 'plugins' == $current_screen->parent_base && false == $this->mainwpMainActivated ) {
			echo '<div class="error"><p>MainWP Post Dripper Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
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

global $mainwp_DripperExtensionActivator;
$mainwp_DripperExtensionActivator = new MainWP_Dripper_Extension_Activator();
