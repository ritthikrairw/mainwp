<?php
/*
Plugin Name: MainWP Article Uploader Extension
Plugin URI: https://mainwp.com
Description: MainWP Article Uploader Extension allows you to bulk upload articles to your dashboard and publish to child sites.
Version: 4.0.1.1
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/article-uploader/
Icon URI:
*/

if ( ! defined( 'MAINWP_ARTICLE_UPLOADER_PLUGIN_FILE' ) ) {
	define( 'MAINWP_ARTICLE_UPLOADER_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_ARTICLE_UPLOADER_EXTENSION_DIR' ) ) {
	define( 'MAINWP_ARTICLE_UPLOADER_EXTENSION_DIR', plugin_dir_path( __FILE__ ) );
}

class MainWP_Article_Uploader_Extension {
	public static $instance = null;
	public  $plugin_handle = 'mainwp-article-uploader-extension';
	protected $plugin_url;
	private $plugin_slug;

	static function get_instance() {
		if ( null === MainWP_Article_Uploader_Extension::$instance ) {
			MainWP_Article_Uploader_Extension::$instance = new MainWP_Article_Uploader_Extension();
		}
		return MainWP_Article_Uploader_Extension::$instance;
	}

	public function __construct() {

		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'localization' ) );

		new MainWP_Article_Uploader();
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

	public function localization() {
		load_plugin_textdomain( 'mainwp-article-uploader-extension', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function admin_init() {
		if ( isset( $_REQUEST['page'] ) && 'Extensions-Mainwp-Article-Uploader-Extension' == $_REQUEST['page'] ) {
			wp_enqueue_style( 'mainwp-article-uploader-extension', $this->plugin_url . 'css/mainwp-article-uploader.css' );
			wp_enqueue_script( 'mainwp-article-uploader-extension', $this->plugin_url . 'js/mainwp-article-uploader.js' );
		}
		MainWP_Article_Uploader::get_instance()->admin_init();
	}

	static function create_folders() {

		$dir = apply_filters( 'mainwp_getspecificdir', 'article_uploader/import/' );

		if ( ! file_exists( $dir ) ) {
			@mkdir( $dir, 0777, true );
		}

		if ( ! file_exists( $dir . '/index.php' ) ) {
			@touch( $dir . '/index.php' );
		}

		$dir = apply_filters( 'mainwp_getspecificdir', 'article_uploader/upload/' );

		if ( ! file_exists( $dir ) ) {
			@mkdir( $dir, 0777, true );
		}

		if ( ! file_exists( $dir . '/index.php' ) ) {
			@touch( $dir . '/index.php' );
		}

		$dir = apply_filters( 'mainwp_getspecificdir', 'article_uploader/' );

		if ( ! file_exists( $dir . '/index.php' ) ) {
			@touch( $dir . '/index.php' );
		}

	}
}

class MainWP_Article_Uploader_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-article-uploader-extension';
	protected $product_id = 'MainWP Article Uploader Extension';
	protected $software_version = '4.0.1.1';

	public function __construct() {
		$this->childFile = __FILE__;

        spl_autoload_register( array( $this, 'autoload' ) );
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

    function autoload( $class_name ) {
        $allowedLoadingTypes = array( 'class' );
        $class_name = str_replace( '_', '-', strtolower( $class_name ) );
        foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
            $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
            if ( file_exists( $class_file ) ) {
                require_once( $class_file );
            }
        }
    }

	function get_this_extension( $pArray ) {

		$pArray[] = array(
			'plugin' => __FILE__,
			'api' => $this->plugin_handle,
			'mainwp' => true,
			'callback' => array( &$this, 'settings' ),
			'apiManager' => true
		);
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_Article_Uploader::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-article-uploader-extension' ) ) {
			return;
		}
		new MainWP_Article_Uploader_Extension();
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
			echo '<div class="error"><p>MainWP Article Uploader Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.', 'mainwp-article-uploader-extension' ) . '</p></div>';
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

global $mainWPArticleUploaderExtensionActivator;

$mainWPArticleUploaderExtensionActivator = new MainWP_Article_Uploader_Extension_Activator();
