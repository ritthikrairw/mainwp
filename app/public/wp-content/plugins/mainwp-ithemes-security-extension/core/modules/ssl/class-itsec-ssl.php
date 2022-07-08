<?php

class MainWP_ITSEC_SSL {
	private static $instance = false;

	private $config_hooks_added = false;
	private $http_site_url;
	private $https_site_url;


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
		
	}

}


MainWP_ITSEC_SSL::get_instance();
