<?php
/*
  Plugin Name: MainWP Clean and Lock Extension
  Plugin URI: https://mainwp.com
  Description: MainWP Clean and Lock Extension enables you to remove unwanted WordPress pages from your dashboard site and to control access to your dashboard admin area.
  Version: 4.0.1.2
  Author: MainWP
  Author URI: https://mainwp.com
  Documentation URI: https://mainwp.com/help/category/mainwp-extensions/clean-and-lock/
  Icon URI:
 */

if ( ! defined( 'MAINWP_CLEAN_AND_LOCK_PLUGIN_FILE' ) ) {
	define( 'MAINWP_CLEAN_AND_LOCK_PLUGIN_FILE', __FILE__ );
}

class MainWP_Clean_And_Lock_Extension {

	public static $instance = null;
	public $plugin_handle   = 'mainwp-secure-and-clean-dashboard-extension';
	protected $plugin_url;
	private $plugin_slug;
	protected $plugin_dir;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Clean_And_Lock_Extension(); }
		return self::$instance;
	}

	public function __construct() {
		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'localization' ) );
		new MainWP_Clean_And_Lock(); // to init
		add_filter( 'mainwp_perform_install_data', array( $this, 'hook_perform_install_data' ) );

		add_filter( 'mainwp_clean_and_lock_auth_basic', array( $this, 'hook_auth_basic' ), 10, 2 );
	}

	public function localization() {
		load_plugin_textdomain( 'mainwp-clean-and-lock-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
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
		if ( isset( $_REQUEST['page'] ) && 'Extensions-Mainwp-Clean-And-Lock-Extension' == $_REQUEST['page'] ) {
			wp_enqueue_style( 'mainwp-secure-and-clean-dashboard-extension', $this->plugin_url . 'css/mainwp-secure-clean-dashboard.css' );
			wp_enqueue_script( 'mainwp-secure-and-clean-dashboard-extension', $this->plugin_url . 'js/mainwp-secure-clean-dashboard.js' );
		}
	}

	public function hook_perform_install_data( $opts = array() ) {

		$basic_user = MainWP_Clean_And_Lock::get_instance()->get_option( 'wpadmin_user' );
		$basic_pass = MainWP_Clean_And_Lock::get_instance()->get_option( 'wpadmin_passwd' );

		if ( ! empty( $basic_user ) && ! empty( $basic_pass ) ) {
			$opts['wpadmin_user']   = $basic_user;
			$opts['wpadmin_passwd'] = $basic_pass;
		}

		return $opts;
	}

	public function hook_auth_basic( $false, $opts = array() ) {

		if ( ! is_array( $opts ) ) {
			$opts = array();
		}

		$basic_user = MainWP_Clean_And_Lock::get_instance()->get_option( 'wpadmin_user' );
		$basic_pass = MainWP_Clean_And_Lock::get_instance()->get_option( 'wpadmin_passwd' );

		if ( ! empty( $basic_user ) && ! empty( $basic_pass ) ) {
			$opts['wpadmin_user']   = $basic_user;
			$opts['wpadmin_passwd'] = $basic_pass;
		}

		return $opts;
	}

}

class MainWP_Clean_And_Lock_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-clean-and-lock-extension';
	protected $product_id       = 'MainWP Clean and Lock Extension';
	protected $software_version = '4.0.1.2';

	public function __construct() {
		$this->childFile = __FILE__;

		$this->includes();
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

	function includes() {
		require_once 'class/mainwp-clean-and-lock.class.php';
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
		MainWP_Clean_And_Lock::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-clean-and-lock-extension' ) ) {
			return;
		}
		new MainWP_Clean_And_Lock_Extension();
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
			echo '<div class="error"><p>MainWP Clean and Lock Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-clean-and-lock-extension' ) . '</p></div>';
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

global $mainWPCleanAndLockExtensionActivator;

$mainWPCleanAndLockExtensionActivator = new MainWP_Clean_And_Lock_Extension_Activator();
