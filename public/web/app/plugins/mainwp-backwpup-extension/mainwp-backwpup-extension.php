<?php
/**
 * Plugin Name: MainWP BackWPup Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP BackWPup Extension combines the power of your MainWP Dashboard with the popular WordPress BackWPup Plugin. It allows you to quickly back up your child sites.
 * Version: 4.0.5
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/backwpup/
 */

if ( ! defined( 'MAINWP_BACKWPUP_DEVELOPMENT' ) ) {
	define( 'MAINWP_BACKWPUP_DEVELOPMENT', true );
}

if ( ! defined( 'MAINWP_BACKWPUP_PLUGIN_DIR' ) ) {
	define( 'MAINWP_BACKWPUP_PLUGIN_DIR', dirname( __FILE__ ) );
}

function mainwp_backwpup_extension_autoload( $class_name ) {
	$allowedLoadingTypes = array( 'class', 'view', 'job', 'destination' );

	foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}
}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'mainwp_backwpup_extension_autoload' );
}

register_activation_hook( __FILE__, 'mainwp_backwpup_extension_activate' );
register_deactivation_hook( __FILE__, 'mainwp_backwpup_extension_deactivate' );

function mainwp_backwpup_extension_activate() {
	 $install = MainWPBackWPupDB::Instance()->install();
	if ( $install !== true ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 'BackWPUp Extension cannot be installed.' );
	} else {
		update_option( 'mainwp_backwpup_extension_activated', 'yes' );
		$extensionActivator = new MainWPBackWPupExtensionActivator();
		$extensionActivator->activate();
	}
}

function mainwp_backwpup_extension_deactivate() {
	$extensionActivator = new MainWPBackWPupExtensionActivator();
	$extensionActivator->deactivate();

	MainWPBackWPupDB::Instance()->uninstall();
}

class MainWPBackWPupExtensionActivator {

	protected $mainwp_main_activated = false;
	protected $child_enabled         = false;
	protected $child_key             = false;
	protected $child_file;
	protected $plugin_handle    = 'mainwp-backwpup-extension';
	protected $product_id       = 'MainWP BackWPup Extension';
	protected $software_version = '4.0.5';
	protected $plugin           = null;

	public function __construct() {
		 $this->child_file = __FILE__;
		$this->plugin_slug = plugin_basename( __FILE__ );
		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwp_main_activated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwp_main_activated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( $this, 'synced_site' ), 10, 2 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			MainWPBackWPupWPCliCommand::init();
		}
	}

	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$data['syncBackwpupData'] = 1;
		return $data;
	}

	public function synced_site( $pWebsite, $information = array() ) {
		if ( is_array( $information ) && isset( $information['syncBackwpupData'] ) ) {
			$data = $information['syncBackwpupData'];
			if ( is_array( $data ) ) {
				$update = array(
					'lastbackup' => $data['lastbackup'],
				);
				MainWPBackWPupDB::Instance()->insert_or_update_settings_fields_by_website_id( $pWebsite->id, $update );
			}
			unset( $information['syncBackwpupData'] );
		}
	}


	function admin_init() {
		if ( get_option( 'mainwp_backwpup_extension_activated' ) == 'yes' ) {
			delete_option( 'mainwp_backwpup_extension_activated' );
			wp_redirect( admin_url( 'admin.php?page=Extensions' ) );
			return;
		}
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
		$this->plugin->render_extension_page();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwp_main_activated = apply_filters( 'mainwp_activated_check', $this->mainwp_main_activated );
		$this->child_enabled         = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->child_key             = $this->child_enabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-backwpup-extension' ) ) {
				return;
		}
		$this->plugin = MainWPBackWPupExtension::get_instance();
	}

	public function getChildKey() {
		 return $this->child_key;
	}

	public function getChildFile() {
		return $this->child_file;
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwp_main_activated == false ) {
			echo '<div class="error"><p>MainWP BackWPup Extension' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'activated_key'    => 'Deactivated',
			'instance_id'      => apply_filters( 'mainwp_extensions_apigeneratepassword', 12, false ),
			'software_version' => $this->software_version,
		);

		update_option( $this->plugin_handle . '_APIManAdder', $options );
	}

	public function deactivate() {
		MainWPBackWPupDB::Instance()->uninstall();
		update_option( $this->plugin_handle . '_APIManAdder', '' );
	}
}

global $mainWPBackWPupExtensionActivator;
$mainWPBackWPupExtensionActivator = new MainWPBackWPupExtensionActivator();
