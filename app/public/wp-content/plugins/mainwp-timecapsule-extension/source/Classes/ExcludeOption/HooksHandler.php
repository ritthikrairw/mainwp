<?php

class MainWP_Wptc_Exclude_Hooks_Handler extends MainWP_Wptc_Base_Hooks_Handler {
	protected $config;

	public function __construct() {
		$this->backup_obj = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Backup');
		$this->ExcludeOption = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_ExcludeOption');
	}

}