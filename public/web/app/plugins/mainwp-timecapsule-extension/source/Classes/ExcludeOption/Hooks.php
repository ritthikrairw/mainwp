<?php

class MainWP_Wptc_Exclude_Hooks extends MainWP_Wptc_Base_Hooks {
	public $hooks_handler_obj;

	public function __construct() {
		$supposed_hooks_hanlder_class = get_class($this) . '_Handler';
		$this->hooks_handler_obj = MainWP_WPTC_Base_Factory::get($supposed_hooks_hanlder_class);
	}

	public function register_hooks() {
		//if (current_user_can('activate_plugins')) {
			$this->register_actions();
		//}
		$this->register_filters();
		$this->register_wptc_actions();
		$this->register_wptc_filters();
	}

	protected function register_actions() {                
		add_action('wp_ajax_mainwp_wptc_get_init_root_files', array($this->hooks_handler_obj, 'wptc_get_init_root_files'));
		add_action('wp_ajax_mainwp_wptc_get_init_files_by_key', array($this->hooks_handler_obj, 'wptc_get_init_files_by_key'));
		add_action('wp_ajax_mainwp_wptc_get_init_tables', array($this->hooks_handler_obj, 'wptc_get_init_tables'));
		
	}

	protected function register_filters() {
	}

	protected function register_wptc_actions() {

	}

	protected function register_wptc_filters() {
	}

}