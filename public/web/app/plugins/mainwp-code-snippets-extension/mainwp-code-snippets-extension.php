<?php
/*
Plugin Name: MainWP Code Snippets Extension
Plugin URI: https://mainwp.com
Description: The MainWP Code Snippets Extension is a powerful PHP platform that enables you to execute php code and scripts on your child sites and view the output on your Dashboard. Requires the MainWP Dashboard plugin.
Version: 4.0.1
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/code-snippets/
*/

if ( ! defined( 'MAINWP_CODE_SNIPPETS_PLUGIN_FILE' ) ) {
	define( 'MAINWP_CODE_SNIPPETS_PLUGIN_FILE', __FILE__ );
}

class MainWP_CS_Extension {
	public static $instance = null;
	protected $plugin_url;
	public $plugin_slug;

	static function get_instance() {

		if ( null === MainWP_CS_Extension::$instance ) {
			MainWP_CS_Extension::$instance = new MainWP_CS_Extension();
		}
		return MainWP_CS_Extension::$instance;
	}

	public function __construct() {
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );


		MainWP_CS_DB::get_instance()->install();
		MainWP_CS::get_instance()->init();
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
        if ( isset( $_GET['page'] ) &&( 'Extensions-Mainwp-Code-Snippets-Extension' == $_GET['page'] ) ) {
            wp_enqueue_style( 'mainwp-cs-extension-codemirror', $this->plugin_url . 'libs/codemirror/lib/codemirror.css' );
            wp_enqueue_style( 'mainwp-cs-extension-codemirror-night', $this->plugin_url . 'libs/codemirror/theme/night.css' );
            wp_enqueue_style( 'mainwp-cs-extension-codemirror-xq-dark', $this->plugin_url . 'libs/codemirror/theme/xq-dark.css' );
            wp_enqueue_style( 'mainwp-cs-extension-codemirror-the-matrix', $this->plugin_url . 'libs/codemirror/theme/the-matrix.css' );
            wp_enqueue_style( 'mainwp-cs-extension-codemirror-erlang-dark', $this->plugin_url . 'libs/codemirror/theme/erlang-dark.css' );
            wp_enqueue_script( 'mainwp-cs-extension-codemirror', $this->plugin_url . 'libs/codemirror/lib/codemirror.js' );
            wp_enqueue_script( 'mainwp-cs-extension-addon-matchbrackets', $this->plugin_url . 'libs/codemirror/addon/edit/matchbrackets.js' );
            wp_enqueue_script( 'mainwp-cs-extension-addon-active-line', $this->plugin_url . 'libs/codemirror/addon/selection/active-line.js' );
            wp_enqueue_script( 'mainwp-cs-extension-mode-htmlmixed', $this->plugin_url . 'libs/codemirror/mode/htmlmixed/htmlmixed.js' );
            wp_enqueue_script( 'mainwp-cs-extension-mode-xml', $this->plugin_url . 'libs/codemirror/mode/xml/xml.js' );
            wp_enqueue_script( 'mainwp-cs-extension-mode-javascript', $this->plugin_url . 'libs/codemirror/mode/javascript/javascript.js' );
            wp_enqueue_script( 'mainwp-cs-extension-mode-css', $this->plugin_url . 'libs/codemirror/mode/css/css.js' );
            wp_enqueue_script( 'mainwp-cs-extension-mode-clike', $this->plugin_url . 'libs/codemirror/mode/clike/clike.js' );
            wp_enqueue_script( 'mainwp-cs-extension-mode-php', $this->plugin_url . 'libs/codemirror/mode/php/php.js' );

            wp_enqueue_script( 'mainwp-cs-extension', $this->plugin_url . 'js/mainwp-codesnippets.js' );
            wp_enqueue_style( 'mainwp-cs-extension', $this->plugin_url . 'css/mainwp-codesnippets.css' );
        }
    }

}

class MainWP_CS_Extension_Activator {
    protected $mainwpMainActivated = false;
    protected $childEnabled = false;
    protected $childKey = false;
    protected $childFile;
    protected $plugin_handle = 'mainwp-code-snippets-extension';
    protected $product_id = 'MainWP Code Snippets Extension';
    protected $software_version = '4.0.1';

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
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

    public function autoload( $class_name ) {

        $allowedLoadingTypes = array( 'class' );
        $class_name = str_replace( '_', '-', strtolower( $class_name ) );
        foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
            $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
            if ( file_exists( $class_file ) ) {
                require_once( $class_file );
            }
        }
    }

	function admin_init() {
		if ( get_option( 'mainwp_code_snippets_extension_activated' ) == 'yes' ) {
			delete_option( 'mainwp_code_snippets_extension_activated' );
			wp_redirect( admin_url( 'admin.php?page=Extensions' ) );
			return;
		}
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
		MainWP_CS::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
			if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-code-snippets-extension' ) ) {
			return;
		}
		new MainWP_CS_Extension();
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Code Snippets Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
	}

	public function update_option( $option_name, $option_value ) {
		$success = add_option( $option_name, $option_value, '', 'no' );
		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}
		 return $success;
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

global $mainWPCSExtensionActivator;
$mainWPCSExtensionActivator = new MainWP_CS_Extension_Activator();
