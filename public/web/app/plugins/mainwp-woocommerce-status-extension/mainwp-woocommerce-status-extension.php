<?php
/**
 * Plugin Name: MainWP WooCommerce Status Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP WooCommerce Status provides you a quick overview of your WooCommerce stores in your network. Requires MainWP Dashboard plugin.
 * Version: 4.0.8
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/category/mainwp-extensions/woocommerce-status/
 */

if ( ! defined( 'MAINWP_WOO_STATUS_PLUGIN_FILE' ) ) {
	define( 'MAINWP_WOO_STATUS_PLUGIN_FILE', __FILE__ );
}

class MainWP_WooCommerce_Status_Extension {

	public static $instance = null;
	protected $plugin_dir;
	protected $plugin_url;
	private $plugin_slug;
	protected $option;
	protected $option_handle = 'mainwp_woocommerce_status_extension';

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_WooCommerce_Status_Extension();
		}
		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		$this->option      = get_option( $this->option_handle );

		add_action( 'init', array( &$this, 'localization' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'after_plugin_row', array( &$this, 'after_plugin_row' ), 10, 3 );
		new MainWP_WooCommerce_Status();
	}

	public function localization() {
		load_plugin_textdomain( 'mainwp-woocommerce-status-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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

	public function after_plugin_row( $plugin_file, $plugin_data, $status ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || 'Activated' != $api_data['activated_key'] ) {
			if ( ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
				?>
		<tr class="plugin-update-tr active">
					<td colspan="3" class="plugin-update colspanchange">
						<div class="update-message api-deactivate">
							<?php echo ( sprintf( __( 'API not activated check your %1$sMainWP account%2$s for updates. For automatic update notification please activate the API.', 'mainwp' ), '<a href="https://mainwp.com/my-account" target="_blank">', '</a>' ) ); ?>
			</div>
					</td>
				</tr>
				<?php
			}
		}
	}

	public function admin_init() {
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'Extensions-Mainwp-Woocommerce-Status-Extension' ) {
			wp_enqueue_style( 'mainwp-woocommerce-status-extension', $this->plugin_url . 'css/mainwp-wc-status.css' );
			wp_enqueue_script( 'mainwp-woocommerce-status-extension', $this->plugin_url . 'js/mainwp-wc-status.js' );
		}
		MainWP_WooCommerce_Status_DB::get_instance()->install();
	}

	public function get_option( $key, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}
}

class MainWP_WooCommerce_Status_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-woocommerce-status-extension';
	protected $product_id       = 'MainWP WooCommerce Status Extension';
	protected $software_version = '4.0.8';

	public function __construct() {
		$this->childFile = __FILE__;

		spl_autoload_register( array( $this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );
		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this' ) );
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
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);
		return $pArray;
	}

	public function get_metaboxes( $metaboxes ) {
		if ( ! $this->childEnabled ) {
			return $metaboxes;
		}

		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}

		$metaboxes[] = array(
			'id'            => 'woocommerce-status',
			'plugin'        => $this->childFile,
			'key'           => $this->childKey,
			'metabox_title' => MainWP_WooCommerce_Status::get_name(),
			'callback'      => array( 'MainWP_WooCommerce_Status', 'render_metabox' ),
		);
		return $metaboxes;
	}

	function widgets_screen_options( $input ) {
		$input['advanced-woocommerce-status'] = __( 'WooCommerce Status', 'mainwp-woocommerce-status-extension' );
		return $input;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_WooCommerce_Status::render_settings();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-woocommerce-status-extension' ) ) {
			return;
		}

		add_filter( 'mainwp_getmetaboxes', array( &$this, 'get_metaboxes' ) );
		add_filter( 'mainwp_widgets_screen_options', array( &$this, 'widgets_screen_options' ), 10, 1 );

		new MainWP_WooCommerce_Status_Extension();
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP WooCommerce Status Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
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

global $mainWPWooCommerceStatusExtensionActivator;

$mainWPWooCommerceStatusExtensionActivator = new MainWP_WooCommerce_Status_Extension_Activator();
