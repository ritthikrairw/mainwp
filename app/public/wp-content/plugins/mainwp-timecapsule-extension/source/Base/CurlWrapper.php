<?php

abstract class MainWP_Wptc_Base_Curl_Wrapper {
	protected $domain_url;
	protected $post_type;
	protected $cron_posts_always_needed;

	public function __construct() {
		$this->init();
	}

	private function init() {

	}

	protected abstract function set_defaults();

}