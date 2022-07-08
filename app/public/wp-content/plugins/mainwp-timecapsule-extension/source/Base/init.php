<?php

class MainWP_Wptc_Base {
	public function __construct() {
	}

	public function init() {
		if ($this->is_privileged()) {
			MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Base_Hooks')->register_hooks();
		}
	}

	public function is_privileged() {
		return true;
	}

}