<?php

class MainWP_Wptc_InitialSetup_Config extends MainWP_Wptc_Base_Config {
	protected $used_options;
	protected $used_wp_options;

	public function __construct() {
		$this->init();
	}

	private function init() {
		$this->set_used_options();
	}

	protected function set_used_options() {
		$this->used_options = array(
			'main_account_email' => 'retainable',
			'as3_access_key' => 'retainable',
			'as3_secure_key' => 'retainable',
			'as3_bucket_region' => 'retainable',
			'as3_bucket_name' => 'retainable',
			'oauth_state_g_drive' => 'retainable',
			'gdrive_old_token' => 'retainable',
			'main_account_login_last_error' => 'retainable',
			'last_cloud_error' => 'retainable',
			'wptc_main_acc_email_temp' => 'retainable',
			'wptc_main_acc_pwd_temp' => 'retainable',
			'wptc_token' => 'retainable',
			'privileges_wptc' => 'retainable',
			'signed_in_repos' => 'retainable',
			'default_repo' => 'retainable',
			'backup_type_setting' => 'retainable',
			'connected_sites_count' => 'retainable',
			'dropbox_access_token' => 'retainable',
			'dropbox_oauth_state' => 'retainable',
		);
		$this->used_wp_options = array(
			//
		);
	}
}