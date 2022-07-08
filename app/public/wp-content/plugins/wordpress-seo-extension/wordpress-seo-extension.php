<?php
/*
Plugin Name: MainWP WordPress SEO Extension
Plugin URI: https://mainwp.com
Description: MainWP WordPress SEO extension by MainWP enables you to manage all your Yoast SEO plugins across your network. Create and quickly set settings templates from one central dashboard. Requires MainWP Dashboard plugin.
Version: 4.0.2
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/wordpress-seo/
*/

if ( ! defined( 'MAINWP_WP_SEO_EXT_PLUGIN_FILE' ) ) {
	define( 'MAINWP_WP_SEO_EXT_PLUGIN_FILE', __FILE__ );
}

class Wordpress_Seo_Extension {

	public $plugin_handle = 'mainwp-wpseo-extension';
	protected $plugin_url;
	private $plugin_slug;
	protected $plugin_dir;

	protected $option;
	protected $option_handle = 'mainwp_wpseo_extension';

	public function __construct() {

		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		$this->option      = get_option( $this->option_handle );

		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'mainwp_before_header', array( &$this, 'wpseo_error_notice' ) );
		add_filter( 'wpseo_accessible_post_types', array( &$this, 'accessible_post_types' ), 10, 1 );
		add_filter( 'wpseo_always_register_metaboxes_on_admin', array( &$this, 'register_metaboxes_on_admin' ), 10, 1 );

		MainWP_WPSeo_DB::get_instance()->install();
	}

	public function init() {
		MainWP_WPSeo::get_instance()->init();
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta; }

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );
		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}


	public function admin_init() {
		wp_enqueue_style( 'mainwp-wpseo-extension', $this->plugin_url . 'css/mainwp-wpseo.css' );
		wp_enqueue_script( 'mainwp-wpseo-extension', $this->plugin_url . 'js/mainwp-wpseo.js' );
		$translation_array = array( 'dashboard_sitename' => get_bloginfo( 'name' ) );
		wp_localize_script( 'mainwp-wpseo-metabox', 'mainwpWPSeoMetaboxLocalize', $translation_array );
		MainWP_WPSeo::get_instance()->admin_init();
	}

	public static function isMainWP_Pages() {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->base, 'mainwp_' ) !== false ) {
			return true;
		}

		return false;
	}

	function wpseo_error_notice() {
		if ( ! self::isMainWP_Pages() ) {
			return;
		}

		if ( ! class_exists( 'WPSEO_Admin' ) ) {
			?>
			<div class="ui red message">
				<div class="header"><?php echo __( 'The Yoast SEO plugin is not detected on the Dashboard site!', 'wordpress-seo-extension' ); ?></div>
				<?php echo __( 'The MainWP WordPress SEO Extension requires the Yoast SEO plugin to be installed and activated on your MainWP Dashboard site.', 'wordpress-seo-extension' ); ?>
			</div>
			<?php
		}
	}

	function accessible_post_types( $post_types = array() ) {
		if ( is_array( $post_types ) ) {
			$post_types = array();
		}

		$post_types['bulkpost'] = 'bulkpost';
		$post_types['bulkpage'] = 'bulkpage';
		return $post_types;
	}

	function register_metaboxes_on_admin( $value ) {

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'PostBulkAdd' || $_GET['page'] == 'PostBulkEdit' || $_GET['page'] == 'PageBulkAdd' || $_GET['page'] == 'PageBulkEdit' ) ) {
			if ( $_GET['page'] == 'PostBulkEdit' || $_GET['page'] == 'PageBulkEdit' ) {
				if ( isset( $_GET['post_id'] ) ) {
					$post_custom = get_post_custom( $_GET['post_id'] );
					if ( is_array( $post_custom ) ) {
						$wpseo_installed = false;
						foreach ( $post_custom as $key => $value ) {
							if ( false !== strpos( $key, '_yoast_wpseo_' ) ) {
								$wpseo_installed = true;
								break;
							}
						}
						if ( ! $wpseo_installed ) {
							return false;
						}
					}
				}
			}
			return true;
		}

		return $value;
	}

}


class Wordpress_Seo_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'wordpress-seo-extension';
	protected $product_id       = 'MainWP Wordpress SEO Extension';
	protected $software_version = '4.0.2';

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

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_WPSeo::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'wordpress-seo-extension' ) ) {
			return;
		}
		new Wordpress_Seo_Extension();
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
			echo '<div class="error"><p>WordPress SEO Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
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

global $wordpressSeoExtensionActivator;
$wordpressSeoExtensionActivator = new Wordpress_Seo_Extension_Activator();
