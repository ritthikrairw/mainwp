<?php

class MainWP_ITSEC_SSL_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'ssl';
	}
	
	protected function sanitize_settings() {
        $this->sanitize_setting( array( 'disabled', 'enabled', 'advanced' ), 'require_ssl', esc_html__( 'Require SSL', 'l10n-mainwp-ithemes-security-extension' ) );		
		$this->sanitize_setting( 'positive-int', 'frontend', __( 'Front End SSL Mode', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( array( 0, 1, 2 ), 'frontend', __( 'Front End SSL Mode', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'admin', __( 'SSL for Dashboard', 'l10n-mainwp-ithemes-security-extension' ) );
	}
	
	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}
		
		
		$previous_settings = MainWP_ITSEC_Modules::get_settings( $this->get_id() );
		
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_SSL_Validator() );
