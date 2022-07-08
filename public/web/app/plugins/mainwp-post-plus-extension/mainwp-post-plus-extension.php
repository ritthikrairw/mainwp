<?php
/*
Plugin Name: MainWP Post Plus Extension
Plugin URI: https://mainwp.com
Description: Enhance your MainWP publishing experience. The MainWP Post Plus Extension allows you to save work in progress as Post and Page drafts. That is not all, it allows you to use random authors, dates and categories for your posts and pages. Requires the MainWP Dashboard plugin.
Version: 4.0.3
Author: MainWP
Author URI: https://mainwp.com
Icon URI:
Documentation URI: http://docs.mainwp.com/category/mainwp-extensions/mainwp-post-plus-extension/
*/

if ( ! defined( 'MAINWP_POST_PLUS_PLUGIN_FILE' ) ) {
	define( 'MAINWP_POST_PLUS_PLUGIN_FILE', __FILE__ );
}


if ( ! defined( 'MAINWP_POST_PLUS_PLUGIN_DIR' ) ) {
	define( 'MAINWP_POST_PLUS_PLUGIN_DIR', plugin_dir_path( MAINWP_POST_PLUS_PLUGIN_FILE ) );
}

class MainWP_Post_Plus_Extension {
	public static $instance = null;
	public  $plugin_handle = 'mainwp-postplus-extension';
	protected $plugin_url;
	private $plugin_slug;
	protected $option;
	protected $option_handle = 'mainwp_postplus_extension';

	static function get_instance() {

		if ( null === MainWP_Post_Plus_Extension::$instance ) {
			MainWP_Post_Plus_Extension::$instance = new MainWP_Post_Plus_Extension();
		}
		return MainWP_Post_Plus_Extension::$instance;
	}

	public function __construct() {

		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		$this->option = get_option( $this->option_handle );
		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp_getsubpages_post', array( MainWP_Post_Plus::get_instance(), 'get_sub_pages_post' ), 9, 1 );
		add_filter( 'mainwp_getsubpages_page', array( MainWP_Post_Plus::get_instance(), 'get_sub_pages_page' ), 9, 1 );

	}

	public function init() {

	}

	public function admin_init() {
		wp_enqueue_style( 'mainwp-pplus-extension', $this->plugin_url . 'css/mainwp-postplus.css' );
		wp_enqueue_script( 'mainwp-pplus-extension', $this->plugin_url . 'js/mainwp-postplus.js' );
		MainWP_Post_Plus::get_instance()->admin_init();
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug = basename( $plugin_file, ".php" );

		$api_data = get_option( $slug. '_APIManAdder');

		if ( !is_array($api_data) || !isset($api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || !isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}


//	public function extension_enabled() {
//		return true;
//	}

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

function mainwp_postplus_extension_autoload( $class_name ) {

	$allowedLoadingTypes = array( 'class' );
	$class_name = str_replace( '_', '-', strtolower( $class_name ) );

	foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
		if ( file_exists( $class_file ) ) {
			require_once( $class_file );
		}
	}
}


class MainWP_Post_Plus_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-post-plus-extension';
	protected $product_id = 'MainWP Post Plus Extension';
	protected $software_version = '4.0.3';

	public function __construct() {
		$this->childFile = __FILE__;

        $this->includes();
        register_activation_hook( __FILE__, array($this, 'activate') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );


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
		require_once('class/mainwp-post-plus.class.php');
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin' 			=> __FILE__,
			'api' 				=> $this->plugin_handle,
			'mainwp' 			=> true,
			'callback' 		=> array( &$this, 'settings' ),
			'apiManager'  => true
		);
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Post_Plus::render_all_drafts_list();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-post-plus-extension' ) ) {
			return;
		}
		new MainWP_Post_Plus_Extension();
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
			echo '<div class="error"><p>MainWP Post Plus Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function activate() {
		$options = array(
            'product_id' => $this->product_id,
			'software_version' => $this->software_version,
		);
        do_action( 'mainwp_activate_extention', $this->plugin_handle , $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPPostPlusExtensionActivator;
$mainWPPostPlusExtensionActivator = new MainWP_Post_Plus_Extension_Activator();
