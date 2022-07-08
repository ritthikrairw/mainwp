<?php

class MainWP_Wptc_InitialSetup_Hooks extends MainWP_Wptc_Base_Hooks {
	public $hooks_handler_obj;

	public function __construct() {
		$supposed_hooks_hanlder_class = get_class($this) . '_Handler';
		$this->hooks_handler_obj = MainWP_WPTC_Base_Factory::get($supposed_hooks_hanlder_class);
	}

	public function register_hooks() {		
		$this->register_filters();
		$this->register_wptc_actions();
		$this->register_wptc_filters();
	}

	protected function register_actions() {

	}

	protected function register_filters() {

	}

	protected function register_wptc_actions() {

	}

	protected function register_wptc_filters() {
	}

}