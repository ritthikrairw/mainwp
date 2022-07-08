<?php

final class MainWP_ITSEC_WordPress_Tweaks {
	private static $instance = false;
	
	private $config_hooks_added = false;
	private $settings;
	private $first_xmlrpc_credentials;
	
	
	private function __construct() {
		$this->init();
	}
	
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public static function activate() {
		$self = self::get_instance();
		
		
	}
	
	public static function deactivate() {
		$self = self::get_instance();		
	}
	
	
	
	public function init() {
		
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// Don't risk blocking anything with WP_CLI.
			return;
		}

		$this->settings = MainWP_ITSEC_Modules::get_settings( 'wordpress-tweaks' );
	}
	
}


MainWP_ITSEC_WordPress_Tweaks::get_instance();
