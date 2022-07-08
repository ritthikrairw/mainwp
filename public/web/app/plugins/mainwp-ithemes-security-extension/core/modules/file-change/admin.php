<?php

final class MainWP_ITSEC_File_Change_Admin {
	private $script_version = 1;
	private $dismiss_nonce;
	
	
	public function __construct() {
		if ( ! MainWP_ITSEC_Modules::get_setting( 'file-change', 'show_warning' ) ) {
			return;
		}
		
		add_action( 'init', array( $this, 'init' ) );
	}
	
	public function init() {
		global $blog_id;
		
		if ( ( is_multisite() && ( 1 != $blog_id || ! current_user_can( 'manage_network_options' ) ) ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		
	
		add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
		$this->dismiss_nonce = wp_create_nonce( 'itsec-file-change-dismiss-warning' );

	}
	
	public function add_scripts() {
		$vars = array(
			'ajax_action' => 'itsec_file_change_dismiss_warning',
			'ajax_nonce'  => $this->dismiss_nonce
		);
		
		wp_enqueue_script( 'mainwp-itsec-file-change-script', plugins_url( 'js/script.js', __FILE__ ), array(), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-file-change-script', 'itsec_file_change', $vars );
	}
		
	
}

new MainWP_ITSEC_File_Change_Admin();
