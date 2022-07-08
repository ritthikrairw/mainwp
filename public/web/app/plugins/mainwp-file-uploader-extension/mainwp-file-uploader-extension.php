<?php
/*
 * Plugin Name: MainWP File Uploader Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP File Uploader Extension gives you an simple way to upload files to your child sites.
 * Version: 4.1
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/file-uploader/
 */

if ( ! defined( 'MAINWP_FILE_UPLOADER_PLUGIN_FILE' ) ) {
	define( 'MAINWP_FILE_UPLOADER_PLUGIN_FILE', __FILE__ );
}

class MainWP_Uploader_Extension {
	public static $instance = null;
	public  $plugin_handle = 'mainwp-upload-extension';
	protected $plugin_url;
	private $plugin_slug;

	protected $mainWPUploader;

	static function get_instance() {
		if ( null == MainWP_Uploader_Extension::$instance ) {
			MainWP_Uploader_Extension::$instance = new MainWP_Uploader_Extension();
		}
		return MainWP_Uploader_Extension::$instance;
	}

	public function __construct() {

		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );

		MainWP_Uploader::get_instance()->init();

		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	public function init() {

	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug = basename( $plugin_file, ".php" );
		$api_data = get_option( $slug. '_APIManAdder' );

		if ( !is_array( $api_data ) || !isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || !isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	public function admin_init() {
		if ( isset( $_REQUEST['page'] ) && 'Extensions-Mainwp-File-Uploader-Extension' == $_REQUEST['page'] ) {
			wp_enqueue_style( 'mainwp-uploader-extension', $this->plugin_url . 'css/mainwp-uploader.css' );
			wp_enqueue_script( 'mainwp-uploader-extension', $this->plugin_url . 'js/mainwp-uploader.js', array(), '2.0' );
		}
		MainWP_Uploader::get_instance()->admin_init();
	}

	static function create_folders() {
		$dir = apply_filters( 'mainwp_getspecificdir', 'uploader/' );
		if ( ! file_exists( $dir ) ) {
			@mkdir( $dir, 0777, true );
		}
	}
}

class MainWP_Uploader_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-file-uploader-extension';
	protected $product_id = 'MainWP File Uploader Extension';
	protected $software_version = '4.0.1';

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
        require_once('class/mainwp-uploader.class.php');
        require_once('class/mainwp-uploader-utility.class.php');
    }


	function get_this_extension( $pArray ) {

		$pArray[] = array( 'plugin' => __FILE__, 'api' => $this->plugin_handle, 'mainwp' => true, 'callback' => array( &$this, 'settings' ), 'apiManager' => true );
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Uploader::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' )&& ! mainwp_current_user_can( 'extension', 'mainwp-file-uploader-extension' ) ) {
			return;
		}
		new MainWP_Uploader_Extension();
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
			echo '<div class="error"><p>MainWP File Uploader Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function activate() {
        MainWP_Uploader_Extension::create_folders();
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

global $mainWPUploaderExtensionActivator;
$mainWPUploaderExtensionActivator = new MainWP_Uploader_Extension_Activator();
