<?php

class MainWP_ITSEC_Brute_Force_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'brute-force';
	}
	
	protected function sanitize_settings() {
		$this->sanitize_setting( 'positive-int', 'max_attempts_host', __( 'Max Login Attempts Per Host', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'max_attempts_user', __( 'Max Login Attempts Per User', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'check_period', __( 'Minutes to Remember Bad Login (check period)', 'l10n-mainwp-ithemes-security-extension' ) );
		
		$this->sanitize_setting( 'bool', 'auto_ban_admin', __( 'Automatically ban "admin" user', 'l10n-mainwp-ithemes-security-extension' ) );
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Brute_Force_Validator() );
