<?php

class MainWP_Wptc_Settings {
    public function __construct(){
		$this->config = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_Settings_Config');
	}

	public function get_account_email(){
		return $this->config->get_option('main_account_email');
	}

	public function get_anonymouse_report_settings(){
		return $this->config->get_option('anonymous_datasent');
	}

	public function get_current_timezone(){
		$current_timezone = $this->config->get_option('wptc_timezone');
		return empty($current_timezone) ? 'UTC' : $current_timezone ;
	}

	public function get_gdrive_old_token(){
		return htmlspecialchars($this->config->get_option('gdrive_old_token'));
	}


	public function admin_footer_text( $admin_footer_text ) {

		return $admin_footer_text;
	}

	public function update_footer( $update_footer_text ) {

		return $update_footer_text;
	}

}