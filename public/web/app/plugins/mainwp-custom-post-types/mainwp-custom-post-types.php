<?php
/*
Plugin Name: MainWP Custom Post Type
Plugin URI: https://mainwp.com
Description: Custom Post Types Extension is an extension for the MainWP Plugin that allows you to manage almost any custom post type on your child sites and that includes Publishing, Editing, and Deleting custom post type content.
Version: 4.0.3
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/custom-post-types/
*/

if ( ! defined( 'MAINWP_CUSTOM_POSTTYPE_LOG_PRIORITY_NUMBER' ) ) {
	define( 'MAINWP_CUSTOM_POSTTYPE_LOG_PRIORITY_NUMBER', 70 );
}

class mainpCustomPostTypeExtensionActivator {
	protected $mainwp_main_activated = false;
	protected $child_enabled         = false;
	protected $child_key             = false;
	protected $child_file;
	protected $plugin_handle    = 'mainwp-custom-post-types';
	protected $product_id       = 'MainWP Custom Post Types';
	protected $software_version = '4.0.3';
	protected $plugin           = null;
	public $plugin_dir;

	public function __construct() {
		$this->plugin_slug = plugin_basename( __FILE__ );
		$this->child_file  = __FILE__;

		spl_autoload_register( array( $this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		add_filter( 'mainwp_custom_post_types_get_post_connections', array( &$this, 'get_post_connections' ), 10, 3 );

		$this->mainwp_main_activated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwp_main_activated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

		MainWPCustomPostTypeDB::Instance()->install();

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
	}

	function autoload( $class_name ) {
		$allowedLoadingTypes = array( 'class', 'view' );
		foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
			$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
			if ( file_exists( $class_file ) ) {
				require_once $class_file;
			}
		}
	}

	public function get_post_connections( $input, $website_id, $child_post_ids = false ) {
		if ( is_array( $child_post_ids ) && count( $child_post_ids ) > 0 ) {
			return MainWPCustomPostTypeDB::Instance()->get_dash_post_ids_from_connections( $website_id, $child_post_ids );
		}
		return $input;
	}

	/**
	 * @param $plugin_meta
	 * @param $plugin_file
	 *
	 * @return array
	 *
	 * Add text inside wp-admin/plugins.php
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	public function getChildKey() {
		return $this->child_key;
	}

	public function getChildFile() {
		return $this->child_file;
	}

	/**
	 * @param $pArray
	 *
	 * @return array
	 *
	 * Callback to function which take care of this extension
	 */
	public function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);

		return $pArray;
	}

	/**
	 * Display settings page if plugin enabled
	 */
	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		if ( $this->child_enabled ) {
			$this->plugin->settings();
		} else {
			echo '<div class="mainwp_info-box-yellow"><strong>' . __( 'The Extension has to be enabled to change the settings' ) . '</strong></div>';
		}
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	public function activate_this_plugin() {
		$this->mainwp_main_activated = apply_filters( 'mainwp_activated_check', $this->mainwp_main_activated );
		$this->child_enabled         = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		if ( ! $this->child_enabled ) {
			return;
		}
		$this->child_key = $this->child_enabled['key'];

		$this->plugin = new MainWPCustomPostType();
	}


	/**
	 * Display notice if MainWP is not installed
	 */
	public function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwp_main_activated == false ) {
			echo '<div class="error"><p>MainWP Custom Post Type Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Plugin</a> first.' ) . '</p></div>';
		}
	}

	/**
	 * Activate plugin
	 */
	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

	/**
	 * Deactivate plugin
	 */
	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}

}

$mainwpCustomPostTypeExtensionActivator = new mainpCustomPostTypeExtensionActivator();
