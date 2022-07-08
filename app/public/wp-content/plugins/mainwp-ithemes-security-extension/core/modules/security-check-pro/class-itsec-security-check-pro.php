<?php

final class MainWP_ITSEC_Security_Check_Pro {

	private static $instance = false;

	private function __construct() {

	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		$self = self::get_instance();
	}

}

MainWP_ITSEC_Security_Check_Pro::get_instance();