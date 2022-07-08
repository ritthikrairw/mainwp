<?php

class MainWP_Wptc_Analytics_Hooks extends MainWP_Wptc_Base_Hooks {
	public $hooks_handler_obj;

	public function __construct() {
		$supposed_hooks_hanlder_class = get_class($this) . '_Handler';
		$this->hooks_handler_obj = MainWP_WPTC_Base_Factory::get($supposed_hooks_hanlder_class);
	}

	public function register_hooks() {
		$this->register_actions();
		$this->register_filters();
		$this->register_wptc_actions();
		$this->register_wptc_filters();
	}

	protected function register_actions() {
	}

	protected function register_filters() {
	}

	protected function register_wptc_actions() {
		//add_action('starting_fresh_new_backup_pre_wptc_h', array($this->hooks_handler_obj, 'starting_fresh_new_backup_pre_wptc_h'));
		//add_action('just_starting_main_schedule_backup_wptc_h', array($this->hooks_handler_obj, 'just_starting_main_schedule_backup_wptc_h'));
		//add_action('inside_monitor_backup_pre_wptc_h', array($this->hooks_handler_obj, 'inside_monitor_backup_pre_wptc_h'));
		//add_action('just_completed_first_backup_wptc_h', array($this->hooks_handler_obj, 'just_completed_first_backup_wptc_h'));
		//add_action('just_completed_not_first_backup_wptc_h', array($this->hooks_handler_obj, 'just_completed_not_first_backup_wptc_h'));
		//add_action('send_basic_analytics', array($this->hooks_handler_obj, 'send_basic_analytics'));
		//add_action('send_database_size', array($this->hooks_handler_obj, 'send_database_size'));
		//add_action('reset_stats', array($this->hooks_handler_obj, 'reset_stats'));
	}

	protected function register_wptc_filters() {
	}

}