<?php

class MainWP_Wptc_Common {
	protected $config;
	protected $logger;
	protected $cron_curl;

	public function __construct() {
		$this->config = MainWP_WPTC_Factory::get('config');
		$this->logger = MainWP_WPTC_Factory::get('logger');

		$this->cron_curl = MainWP_WPTC_Base_Factory::get('Wptc_Cron_Server_Curl_Wrapper');
	}

	public function init() {
		if ($this->is_privileged()) {
			$supposed_hooks_class = get_class($this) . '_Hooks';
			MainWP_WPTC_Base_Factory::get($supposed_hooks_class)->register_hooks();
		}
	}

	public function is_privileged() {
		return true;
	}
}