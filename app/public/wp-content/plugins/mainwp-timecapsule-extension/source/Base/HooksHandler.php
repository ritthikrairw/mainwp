<?php

class MainWP_Wptc_Base_Hooks_Handler {
	public function __construct() {
	}

	public function action_hanlder($arg1 = '', $arg2 = null, $arg3 = null, $arg4 = null) {
	}

	public function filter_hanlder($data, $dets1 = null, $dets2 = null, $dets3 = null) {
		return $data;
	}

	//WPTC's specific hooks start

	public function just_initialized_wptc_h($arg1 = '', $arg2 = null, $arg3 = null, $arg4 = null) {
		mainwp_wptc_init_flags();
		MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Common')->init();
		MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Analytics')->init();
		MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Exclude')->init();
	}

	//WPTC's specific hooks end

}