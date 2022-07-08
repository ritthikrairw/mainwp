<?php

class MainWP_ITSEC_Strong_Passwords_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'strong-passwords';
	}
	
	protected function sanitize_settings() {
		$this->sanitize_setting( array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ), 'role', __( 'Select Role for Strong Passwords', 'l10n-mainwp-ithemes-security-extension' ) );
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Strong_Passwords_Validator() );
