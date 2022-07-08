<?php
/*
Plugin Name: MainWP WP Compress Extension
Plugin URI: https://mainwp.com
Description: MainWP WP Compress Extension allows you to claim 30% discount for the WP Compress service
Version: 4.0.1
Author: MainWP
Author URI: https://mainwp.com
Icon URI:
Documentation URI: http://mainwp.com/help/docs/wp-compress-extension
*/

class MainWP_WP_Compress_Extension {

	/**
	 * This function automatically called once an object is created.
	 */
	public function __construct() {

	}

	public static function render_page() {
    global $mainWPWPCompressExtensionActivator;

    $dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPWPCompressExtensionActivator->get_child_file(), $mainWPWPCompressExtensionActivator->get_child_key(), array(), array() );
    ?>
		<div class="ui segment">
			<div class="ui message">
				<?php _e( 'MainWP has partnered with WP Compress to bring you an automatic image optimization for all your MainWP Child sites at an exclusive discount of 30% off their normal prices.', 'mainwp-wp-compress-extension' ); ?>
			</div>
			<?php if ( is_array( $dbwebsites ) && count( $dbwebsites ) > 0 ) : ?>
				<div class="ui yellow message"><?php _e( 'You need to have at least one connected child site in order to see the coupon.', 'mainwp-wp-compress-extension' ); ?></div>
			<?php else : ?>
				<div class="ui hidden divider"></div>
				<div class="ui hidden divider"></div>
				<h2 class="ui icon header">
				  <i class="icon"><img src="/wp-content/plugins/mainwp-wp-compress-extension/images/wp-compress-logo.png" class="ui medium centered image" alt="WP Compress Logo" title="WP Compress Logo" /></i>
				  <div class="content">
				    <span class="ui big green basic label">MainWP</span>
						<div class="ui hidden divider"></div>
				    <div class="sub header"><?php _e( 'Use the "MainWP" coupon code to get the 30% off WP Compress normal prices for life.', 'mainwp-wp-compress-extension' ); ?></div>
						<div class="ui hidden divider"></div>
						<a href="https://wpcompress.com/pricing/" class="ui big green button" target="_blank"><?php _e( 'WP Compress Pricing Plans', 'mainwp-wp-compress-extension' ); ?></a>
				  </div>
				</h2>
				<div class="ui hidden divider"></div>
				<div class="ui hidden divider"></div>
			<?php endif; ?>
		</div>
  <?php
	}
}

class MainWP_WP_Compress_Extension_Activator {

	/**
	 * Handle if MainWP plugin is activated or not and by default its false.
	 * @var bool $mainwpMainActivated
	 */
	protected $mainwpMainActivated = false;

	/**
	 * Handle if child site is enabled or not and by default its false.
	 * @var bool $childEnabled
	 */
	protected $childEnabled = false;

	/**
	 * Holds the child site key and by default its false.
	 * @var string $childKey
	 */
	protected $childKey = false;

	/**
	 * Holds the child file path.
	 * @var string $childFile
	 */
	protected $childFile;

	/**
	 * Handle plugin name.
	 * @var string $plugin_handle
	 */
	protected $plugin_handle = 'mainwp-wp-compress-extension';

	/**
	 * Handle product id.
	 * @var string $product_id
	 */
	protected $product_id = 'MainWP WP Compress Extension';

	/**
	 * The software version.
	 * @var string $software_version
	 */
	protected $software_version = '4.0.1';

	/**
	 * This function will automatically called once an object is created.
	 */
	public function __construct() {
		$this->childFile = __FILE__;

        register_activation_hook( __FILE__, array($this, 'activate') );
        register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		/**
		 * This hook allows you to add your extension to MainWP via the 'mainwp-getextensions' filter.
		 * @link https://codex.mainwp.com/#mainwp-getextensions
		 *
		 */
		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) ); // This is a filter similar to adding the management page in WordPress. It calls the function 'get_this_extension', which adds to the $extensions array. This array is a list of all of the extensions MainWP uses, and the functions that it has to call to show settings for them. In this case, the function is 'settings'.
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false ); //This variable checks to see if mainwp is activated, and by default it will return false unless it finds in WordPress that it's on.

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin(); // If MainWP is activated, then call the function 'activate_this_plugin'.
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) ); //Notices displayed near the top of admin pages.
	}


	/**
	 * This function allows you to add your extension to MainWP via the 'mainwp-getextensions' filter.
	 * @link https://codex.mainwp.com/#mainwp-getextensions
	 *
	 * @param array $pArray
	 *
	 * @return array
	 */
	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true
		);

		//this just adds the plugin's extension settings page to the array $extensions so it knows what to call.
		return $pArray;
	}


	/**
	 * This function displays content on our extensions screen with mainwp header and footer.
	 * @return void
	 */
	function settings() {
		/**
		 * This hook allows you to render the tabs on the Extensions screen via the 'mainwp-pageheader-extensions' filter.
		 * @link https://codex.mainwp.com/#mainwp-pageheader-extensions
		 *
		 */
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_WP_Compress_Extension::render_page(); // Displays the content in our extension
		/**
		 * This hook allows you to render the footer on the Extensions screen via the 'mainwp-pagefooter-extensions' filter.
		 * @link https://codex.mainwp.com/#mainwp-pagefooter-extensions
		 *
		 */
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	/**
	 * This function "activate_this_plugin" is called when the main is initialized.
	 * @return void
	 */
	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ ); //If the plugin is not enabled this will return false, if the plugin is enabled, an array will be returned containing a key
		$this->childKey            = $this->childEnabled['key'];// Handle key of child
		/**
		 * Check whether current user has capability or role to access the extension.
		 */
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-wp-compress-extension' ) ) {
			return;
		}
		new MainWP_WP_Compress_Extension();
	}

	/**
	 * This function displays notices near the top of admin pages
	 * @return void
	 */
	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			$string1 = __( 'MainWP WP Compress Extension requires ', 'mainwp-wp-compress-extension' );
			$string2 = __( 'MainWP Dashboard Plugin', 'mainwp-wp-compress-extension' );
			$string3 = __( ' to be activated in order to work. Please install and activate ', 'mainwp-wp-compress-extension' );
			$string4 = __( 'MainWP Dashboard Plugin', 'mainwp-wp-compress-extension' );
			$string5 = __( ' first.', 'mainwp-wp-compress-extension' );
			printf( '<div class="error"><p>%s <a href="' . esc_url( 'http://mainwp.com/' ) . '" target="_blank">%s</a>%s<a href="' . esc_url( 'http://mainwp.com/' ) . '" target="_blank">%s</a>%s</p></div>', $string1, $string2, $string3, $string4, $string5 );
		}
	}

	/**
	 * This function gets the key of child site
	 * @return string
	 */
	public function get_child_key() {
		return $this->childKey;
	}

	/**
	 * This function gets the child file
	 * @return string
	 */
	public function get_child_file() {
		return $this->childFile;
	}

	/**
	 * This function will automatically called at the time of activation of plugin.
	 * @return void
	 */
	public function activate() {
		 $options = array(
            'product_id' => $this->product_id,
			'software_version' => $this->software_version,
		);
        do_action( 'mainwp_activate_extention', $this->plugin_handle , $options );
	}

	/**
	 * This function will automatically called at the time of deactivation of plugin.
	 * @return void
	 */
	public function deactivate() {
        do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPWPCompressExtensionActivator;
$mainWPWPCompressExtensionActivator = new MainWP_WP_Compress_Extension_Activator();
