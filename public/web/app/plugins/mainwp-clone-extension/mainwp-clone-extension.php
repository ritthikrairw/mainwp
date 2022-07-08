<?php
/*
Plugin Name: MainWP Clone Extension
Plugin URI: https://mainwp.com
Description: MainWP Clone Extension is an extension for the MainWP plugin that enables you to clone your child sites with no technical knowledge required.
Version: 4.0.2
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/clone/
*/

if (!defined('MAINWP_CLONE_PLUGIN_FILE')) {
  define('MAINWP_CLONE_PLUGIN_FILE', __FILE__);
}

class MainWPCloneExtension {
  public static $instance = null;
  public $plugin_handle = "mainwp-clone-extension";
  protected $plugin_url;
  private $plugin_slug;
  private $mainwpCloneSite;

  static function Instance(){
    if ( MainWPCloneExtension::$instance == null )
      MainWPCloneExtension::$instance = new MainWPCloneExtension();
    return MainWPCloneExtension::$instance;
  }

  public function __construct() {

		$this->plugin_url = plugin_dir_url(__FILE__);
    $this->plugin_slug = plugin_basename(__FILE__);

    add_action( 'init', array( &$this, 'init' ) );
    add_action( 'admin_init', array( &$this, 'admin_init' ) );
    add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
    add_filter( 'mainwp_clone_enabled', array( &$this, 'mainwp_clone_enabled' ), 10, 2 );

    $this->mainwpCloneSite = new MainWPCloneSite();
    $this->mainwpCloneSite->init();
	}

  public function mainwp_clone_enabled( $output ) {
    return get_option( 'mainwp_clone_enabled' );
  }

  public function init() {

  }

  public function plugin_row_meta( $plugin_meta, $plugin_file ) {
    if ( $this->plugin_slug != $plugin_file )
      return $plugin_meta;

		$slug = basename( $plugin_file, ".php" );
		$api_data = get_option( $slug. '_APIManAdder' );

		if ( !is_array( $api_data ) || !isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || !isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

    $plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

    return $plugin_meta;
  }

  public function admin_init() {
    if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'Extensions-Mainwp-Clone-Extension' ) {
      wp_enqueue_style( 'mainwp-clone-extension-css', $this->plugin_url . 'css/mainwp-clone.css' );
      wp_enqueue_script( 'mainwp-clone-extension-js', $this->plugin_url . 'js/mainwp-clone.js' );
    }
  }

}


function mainwp_clone_extension_autoload( $class_name ) {
  $allowedLoadingTypes = array( 'class', 'page' );

  foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
    $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
    if ( file_exists( $class_file ) ) {
      require_once( $class_file );
    }
  }
}

class MainWPCloneExtensionActivator {
  protected $mainwpMainActivated = false;
  protected $childEnabled = false;
  protected $childKey = false;
  protected $childFile;
  protected $plugin_handle = "mainwp-clone-extension";
  protected $product_id = "MainWP Clone Extension";
  protected $software_version = "4.0.2";


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
      add_action('mainwp_activated', array(&$this, 'activate_this_plugin'));
    }
    add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
  }

  public function includes() {
      require_once( 'class/MainWPCloneSite.class.php');
  }

  function get_this_extension( $pArray ) {
    $pArray[] = array( 'plugin' => __FILE__, 'api' => $this->plugin_handle, 'mainwp' => true, 'callback' => array( &$this, 'settings' ), 'apiManager' => true );
    return $pArray;
  }

  function settings() {
    do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWPCloneSite::render();
    do_action( 'mainwp_pagefooter_extensions', __FILE__ );
  }

  function activate_this_plugin() {
    $this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
    $this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
    $this->childKey = $this->childEnabled['key'];
    if ( function_exists( "mainwp_current_user_can" )&& !mainwp_current_user_can( "extension", "mainwp-clone-extension" ) )
      return;
    new MainWPCloneExtension();
  }

  function mainwp_error_notice() {
    global $current_screen;
    if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
      echo '<div class="error"><p>MainWP Clone Extension ' . __('requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.') . '</p></div>';
    }
  }

  public function getChildKey() {
    return $this->childKey;
  }

  public function getChildFile() {
    return $this->childFile;
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

global $mainWPCloneExtensionActivator;
$mainWPCloneExtensionActivator = new MainWPCloneExtensionActivator();
