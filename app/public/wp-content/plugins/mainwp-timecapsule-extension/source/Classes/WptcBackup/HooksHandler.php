<?php

class MainWP_Wptc_Backup_Hooks_Handler extends MainWP_Wptc_Base_Hooks_Handler {
	protected $config;
	protected $backup_controller;
	protected $backup_obj;
	protected $current_backup_id;

	public function __construct() {
		$this->config = MainWP_WPTC_Factory::get('config');
		$this->backup_obj = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Backup');
	}

	//WPTC's specific hooks start

	//WPTC's specific hooks end

}