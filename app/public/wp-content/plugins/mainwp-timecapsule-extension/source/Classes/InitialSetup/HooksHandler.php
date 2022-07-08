<?php

class MainWP_Wptc_InitialSetup_Hooks_Handler extends MainWP_Wptc_Base_Hooks_Handler {
	protected $settings;

	public function __construct() {
		$this->settings = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_InitialSetup');
	}
}