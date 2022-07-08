<?php

class MainWP_Wptc_Analytics_Hooks_Handler extends MainWP_Wptc_Base_Hooks_Handler {
	protected $config;

	public function __construct() {
		$this->backup_obj = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Backup');
		$this->backup_analytics = MainWP_WPTC_Base_Factory::get('Wptc_Backup_Analytics');
	}

}