<?php
/**
 * MainWP Virusdie Extension Activator
 *
 * This file handles Activator.
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

/**
 * Class MainWP_Virusdie_Extension
 */
class MainWP_Virusdie_Extension {

	/**
	 * Public static variable to hold the single instance of MainWP_Virusdie_Extension.
	 *
	 * @var mixed Default null
	 */
	public static $instance = null;

	/**
	 * Plugin handle.
	 *
	 * @var string
	 */
	public $plugin_handle = 'mainwp-virusdie-extension';

	/**
	 * Scan results.
	 *
	 * @var array
	 */
	public $scan_results = null;

	/**
	 * Creates a public static instance of MainWP_Virusdie_Extension.
	 *
	 * @return mixed
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Virusdie_Extension();
		}
		return self::$instance;
	}

	/**
	 * Extension constructor.
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'localization' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'on_delete_site' ), 10, 1 );
		MainWP_Virusdie_DB::get_instance()->install();
		MainWP_Virusdie::get_instance()->init();
	}

	/**
	 * Sets the extension localization.
	 */
	public function localization() {
		load_plugin_textdomain( 'mainwp-virusdie-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Sets the Virusdie reports page to the site-specific section.
	 *
	 * @param array $subPage Subpages array.
	 *
	 * @return array $subPage Updated subpages array.
	 */
	public function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title'       => __( 'Virusdie', 'mainwp' ),
			'slug'        => 'Virusdie', // create ManageSitesVirusdie page.
			'sitetab'     => true,
			'menu_hidden' => true,
			'callback'    => array( &$this, 'render_report' ),
		);
		return $subPage;
	}


	/**
	 * Renders Virusdie report for a child site.
	 *
	 * @return void
	 */
	public function render_report() {
		do_action( 'mainwp_pageheader_sites', 'Virusdie' );

		$site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		/**
		 * Extension object
		 *
		 * @global object
		 */
		global $mainWPVirusdieExtensionActivator;
		if ( $site_id ) {
			$website = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $site_id );
			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}
		}

		if ( ! $website ) {
			esc_html_e( 'Site not found.', 'mainwp-virusdie-extension' );
		} else {
			MainWP_Virusdie::get_instance()->render_report( $website );
		}

		do_action( 'mainwp_pagefooter_sites', 'Virusdie' );
	}

	/**
	 * Initiates admin_init hook to enqueue extension scripts and post saving.
	 *
	 * @return void
	 */
	public function admin_init() {
		MainWP_Virusdie::get_instance()->handle_main_post_saving();
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Virusdie-Extension' == $_GET['page'] || 'ManageSitesVirusdie' == $_GET['page'] ) ) {
			wp_enqueue_style( 'mainwp-virusdie-extension', MAINWP_VIRUSDIE_URL . '/css/mainwp-virusdie.css', array(), '4.0' );
			wp_enqueue_script( 'mainwp-virusdie-extension', MAINWP_VIRUSDIE_URL . '/js/mainwp-virusdie.js', array(), '4.2', true );
		}
	}


	/**
	 * Handles data cleanup after deleting a child site.
	 *
	 * @param object $website Child Site array.
	 */
	public function on_delete_site( $website ) {
		if ( $website ) {
			MainWP_Virusdie_DB::get_instance()->delete_virusdie_by_site_id( $website->id );
		}
	}

}
