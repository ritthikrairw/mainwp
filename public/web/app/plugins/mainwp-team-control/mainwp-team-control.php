<?php
/*
Plugin Name: MainWP Team Control
Plugin URI: https://mainwp.com
Description: MainWP Team Control extension allows you to create a custom roles for your dashboard site users and limiting their access to MainWP features. Requires MainWP Dashboard plugin.
Version: 4.0.2
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/team-control/
*/

if ( ! defined( 'MAINWP_TEAMCONTROL_PLUGIN_FILE' ) ) {
	define( 'MAINWP_TEAMCONTROL_PLUGIN_FILE', __FILE__ );
}

define( 'MWP_TEAMCONTROL_PLUGIN_SLUG', 'mainwp-team-control/mainwp-team-control.php' );

class MainWP_Team_Control_Extension {
	public static $instance = null;
	public static $plugin_url;
	public $plugin_slug;
	public $plugin_dir;
	protected $option;
	protected $option_handle = 'mainwp_teamcontrol_extension';

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Team_Control_Extension();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		self::$plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		$this->option      = get_option( $this->option_handle );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		global $mainWPTeamControlRole;
		$mainWPTeamControlRole = MainWP_Team_Control_Role::get_instance();
		add_action( 'set_current_user', 'MainWP_Team_Control_Role::team_control_set_current_user' );
		add_filter( 'mainwp_currentusercan', 'MainWP_Team_Control_Role::team_control_current_user_can', 10, 3 );
		add_action( 'mainwp_added_new_site', array( $mainWPTeamControlRole, 'added_new_site' ), 10, 1 );
		add_action( 'mainwp_added_new_group', array( $mainWPTeamControlRole, 'added_new_group' ), 10, 1 );
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
		wp_enqueue_style( 'mainwp-teamcon-extension', self::$plugin_url . 'css/mainwp-teamcontrol.css' );
		wp_enqueue_script( 'mainwp-teamcon-extension', self::$plugin_url . 'js/mainwp-teamcontrol.js' );
		wp_localize_script(
			'mainwp-teamcon-extension',
			'mainwp_teamcon_data',
			array(
				'wp_nonce' => wp_create_nonce( 'mainwp-teamcon-nonce' ),
			)
		);
		$mwp_team_control = new MainWP_Team_Control();
		$mwp_team_control->admin_init();
		MainWP_Team_Control_DB::get_instance()->install();
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

class MainWP_Team_Control_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-team-control';
	protected $product_id       = 'MainWP Team Control';
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

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		if ( ! function_exists( 'mainwp_current_user_can' ) || mainwp_current_user_can( 'extension', 'mainwp-team-control' ) ) {
			$mwp_team_control = new MainWP_Team_Control();
			$mwp_team_control->render();
		}
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		new MainWP_Team_Control_Extension();
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
			echo '<div class="error"><p>MainWP Team Control ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard plugin</a> first.', 'mainwp-team-control' ) . '</p></div>';
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

global $mainWPTeamControlExtensionActivator;
$mainWPTeamControlExtensionActivator = new MainWP_Team_Control_Extension_Activator();
