<?php

final class MainWP_ITSEC_System_Tweaks {
	private static $instance = false;
	
	private $hooks_added = false;
	
	
	private function __construct() {
		$this->add_hooks();
	}
	
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	public static function activate() {
		$self = self::get_instance();
		
		$self->add_hooks();
	
	}
	
	public static function deactivate() {
		$self = self::get_instance();
		
		$self->remove_hooks();
	
	}
	
	public function add_hooks() {
		if ( $this->hooks_added ) {
			return;
		}
		$this->hooks_added = true;
	}
	
	public function remove_hooks() {
		$this->hooks_added = false;
	}
	
}


MainWP_ITSEC_System_Tweaks::get_instance();
