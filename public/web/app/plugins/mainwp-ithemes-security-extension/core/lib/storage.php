<?php

final class MainWP_ITSEC_Storage {
	private $option = 'mainwp-itsec-storage';
	
	private static $instance = false;
	private $changed = false;
	private $cache;
	private $shutdown_done = false;
	
	private function __construct() {
		$this->load();
		
		add_action( 'shutdown', array( $this, 'shutdown' ) );
		add_action( 'mainwp-itsec-lib-clear-caches', array( $this, 'save' ), -20 );
		add_action( 'mainwp-itsec-lib-clear-caches', array( $this, 'load' ), -10 );
	}
	
	private static function get_instance() {
		if ( false === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public static function get( $name ) {
		$data = self::get_instance();
		
		if ( isset( $data->cache[$name] ) ) {
			return $data->cache[$name];
		}
		
		return null;
	}
	
	public static function get_all() {
		$data = self::get_instance();
		
		return $data->cache;
	}
	
	public static function set( $name, $value ) {
		$data = self::get_instance();
		$data->cache[$name] = $value;
		$data->changed = true;
		
		if ( $data->shutdown_done ) {
			self::save();
		}
	}
	
	public static function set_all( $value ) {
		$data = self::get_instance();
		$data->cache = $value;
		$data->changed = true;
		
		if ( $data->shutdown_done ) {
			self::save();
		}
	}
	
	public static function save() {
		$data = self::get_instance();
		
		if ( ! $data->changed ) {
			return true;
		}
		
		$data->changed = false;
				
		if (MainWP_IThemes_Security::is_manage_site()) {                                    
			if ($site_id = MainWP_IThemes_Security::get_manage_site_id()) {
				MainWP_IThemes_Security::update_itheme_settings($data->cache, $site_id);				
			}
		} else {
			MainWP_IThemes_Security::update_itheme_settings($data->cache);			
		}
				
		//return update_site_option( $data->option, $data->cache );
	}
	
	public static function reload() {
		$data = self::get_instance();
		$data->load();
	}
	
	public function load() {		
		$site_id = false;
		if (MainWP_IThemes_Security::is_manage_site()) {   
			$site_id = MainWP_IThemes_Security::get_manage_site_id();			
		}
		$itheme_settings = MainWP_IThemes_Security::get_itheme_settings($site_id);
						
		$this->cache = $itheme_settings; 
		if ( ! is_array( $this->cache ) ) {
			$this->cache = array();
		}
	}
	
	public function shutdown() {
		self::save();
		
		$this->shutdown_done = true;
	}
}
