<?php
/*
Plugin Name: MainWP iThemes Security Extension
Plugin URI: https://mainwp.com
Description: The iThemes Security Extension combines the power of your MainWP Dashboard with the popular iThemes Security Plugin. It allows you to manage iThemes Security plugin settings directly from your dashboard. Requires MainWP Dashboard plugin.
Version: 4.1.1
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/ithemes-security/
*/

if ( ! defined( 'MAINWP_ITHEME_PLUGIN_FILE' ) ) {
	define( 'MAINWP_ITHEME_PLUGIN_FILE', __FILE__ );
}

class MainWP_IThemes_Security_Extension {

	public static $plugin_url;
	public $plugin_slug;
	public $plugin_dir;
	public $itheme_sites_info = null;

	public function __construct() {

		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		self::$plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'init', array( &$this, 'localization' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'sitestable_getcolumns' ), 10 );
		add_filter( 'mainwp_sitestable_item', array( $this, 'sitestable_item' ), 10 );
		add_filter( 'mainwp_managesites_column_url', array( &$this, 'managesites_column_url' ), 10, 2 );
		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_filter( 'mainwp_sync_extensions_options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );
		add_action( 'mainwp_applypluginsettings_mainwp-ithemes-security-extension', array( MainWP_IThemes_Security::get_instance(), 'mainwp_apply_plugin_settings' ) );

		require_once dirname( __FILE__ ) . '/core/class-itsec-core.php';
		require_once dirname( __FILE__ ) . '/core/class-itsec-core.php';
		$itheme_core = MainWP_ITSEC_Core::get_instance();
		$itheme_core->init( __FILE__, __( 'iThemes Security', 'l10n-mainwp-ithemes-security-extension' ) );

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

	function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title'            => __( 'iThemes Security', 'l10n-mainwp-ithemes-security-extension' ),
			'slug'             => 'iThemes',
			'sitetab'          => true,
			'menu_hidden'      => true,
			'callback'         => array( 'MainWP_IThemes_Security', 'render' ),
			'on_load_callback' => array( 'MainWP_ITSEC_Admin_Page_Loader', 'load' ),
		);
		return $subPage;
	}

	public function sitestable_getcolumns( $columns ) {
		$columns['itheme_status']       = __( 'iTheme Status', 'l10n-mainwp-ithemes-security-extension' );
		$columns['itheme_banned_users'] = __( 'Banned users', 'l10n-mainwp-ithemes-security-extension' );
		$columns['itheme_lockouts']     = __( 'Lockouts', 'l10n-mainwp-ithemes-security-extension' );
		return $columns;
	}

	public function sitestable_item( $item ) {

		if ( null === $this->itheme_sites_info ) {
			$this->itheme_sites_info = MainWP_IThemes_Security_DB::get_instance()->get_settings_field_array();
		}

		$site_id = $item['id'];
		if ( $this->itheme_sites_info && isset( $this->itheme_sites_info[ $site_id ] ) ) {
			$site_status   = $this->itheme_sites_info[ $site_id ]['site_status'];
			$lockout_count = is_array( $site_status ) && isset( $site_status['lockout_count'] ) ? $site_status['lockout_count'] : '';
			$scan_info     = is_array( $site_status ) && isset( $site_status['scan_info'] ) ? $site_status['scan_info'] : '';
			$count_bans    = is_array( $site_status ) && isset( $site_status['count_bans'] ) ? $site_status['count_bans'] : '';

			$item['itheme_status']       = MainWP_IThemes_Security_Plugin::render_status( $scan_info );
			$item['itheme_banned_users'] = $count_bans;
			$item['itheme_lockouts']     = $lockout_count;
		} else {
			$item['itheme_status']       = '';
			$item['itheme_banned_users'] = '';
			$item['itheme_lockouts']     = '';
		}

		return $item;
	}

	public function managesites_column_url( $actions, $websiteid ) {
		$actions['itheme'] = sprintf( '<a href="admin.php?page=ManageSitesiThemes&id=%1$s">' . __( 'iThemes Security', 'l10n-mainwp-ithemes-security-extension' ) . '</a>', $websiteid );
		return $actions;
	}

	public function init() {

	}

	public function localization() {
		load_plugin_textdomain( 'l10n-mainwp-ithemes-security-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function admin_init() {
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'ManageSitesiThemes' || $_GET['page'] == 'Extensions-Mainwp-Ithemes-Security-Extension' ) ) {
			wp_enqueue_style( 'mainwp-ithemes-extension', self::$plugin_url . 'css/mainwp-ithemes.css' );
			wp_enqueue_script( 'mainwp-ithemes-extension', self::$plugin_url . 'js/mainwp-ithemes.js' );
		}
		MainWP_IThemes_Security::get_instance()->admin_init();
		MainWP_IThemes_Security_Plugin::get_instance()->admin_init();

	}

	function mainwp_sync_extensions_options( $values = array() ) {
		$values['mainwp-ithemes-security-extension'] = array(
			'plugin_slug' => 'better-wp-security/better-wp-security.php',
			'plugin_name' => 'iThemes Security',
		);
		return $values;
	}
}


function mainwp_ithemes_security_extension_autoload( $class_name ) {
	$allowedLoadingTypes = array( 'class' );
	$class_name          = str_replace( '_', '-', strtolower( $class_name ) );
	foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}
}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'mainwp_ithemes_security_extension_autoload' );
}

register_activation_hook( __FILE__, 'mainwp_ithemes_security_extension_activate' );
register_deactivation_hook( __FILE__, 'mainwp_ithemes_security_extension_deactivate' );

function mainwp_ithemes_security_extension_activate() {

	update_option( 'mainwp_ithemes_security_extension_activated', 'yes' );
	$extensionActivator = new MainWP_IThemes_Security_Extension_Activator();
	$extensionActivator->activate();
}

function mainwp_ithemes_security_extension_deactivate() {

	$extensionActivator = new MainWP_IThemes_Security_Extension_Activator();
	$extensionActivator->deactivate();
}

class MainWP_IThemes_Security_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-ithemes-security-extension';
	protected $product_id       = 'MainWP Security Extension';
	protected $software_version = '4.1.1';

	public function __construct() {

		$this->childFile = __FILE__;
		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
		MainWP_IThemes_Security_DB::get_instance()->install();
	}

	function admin_init() {
		if ( get_option( 'mainwp_ithemes_security_extension_activated' ) == 'yes' ) {
			delete_option( 'mainwp_ithemes_security_extension_activated' );
			wp_redirect( admin_url( 'admin.php?page=Extensions' ) );
			return;
		}
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'           => __FILE__,
			'api'              => $this->plugin_handle,
			'mainwp'           => true,
			'callback'         => array( &$this, 'settings' ),
			'apiManager'       => true,
			'on_load_callback' => array( 'MainWP_ITSEC_Admin_Page_Loader', 'load' ),
		);
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_IThemes_Security::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {

		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-ithemes-security-extension' ) ) {
			return;
		}
		new MainWP_IThemes_Security_Extension();
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
			echo '<div class="error"><p>MainWP iThemes Security Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function update_option( $option_name, $option_value ) {

		$success = add_option( $option_name, $option_value, '', 'no' );

		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}

		 return $success;
	}

	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'activated_key'    => 'Deactivated',
			'instance_id'      => apply_filters( 'mainwp_extensions_apigeneratepassword', 12, false ),
			'software_version' => $this->software_version,
		);
		$this->update_option( $this->plugin_handle . '_APIManAdder', $options );
	}

	public function deactivate() {
		$this->update_option( $this->plugin_handle . '_APIManAdder', '' );
	}
}

global $mainWPIThemesSecurityExtensionActivator;
$mainWPIThemesSecurityExtensionActivator = new MainWP_IThemes_Security_Extension_Activator();
