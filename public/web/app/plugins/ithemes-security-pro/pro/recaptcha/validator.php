<?php

use iThemesSecurity\Config_Validator;

class ITSEC_Recaptcha_Validator extends Config_Validator {

	protected function sanitize_settings() {
		parent::sanitize_settings();

		if (
			$this->settings['type'] !== $this->previous_settings['type'] ||
			$this->settings['site_key'] !== $this->previous_settings['site_key'] ||
			$this->settings['secret_key'] !== $this->previous_settings['secret_key']
		) {
			$this->settings['validated'] = false;
			$this->settings['last_error'] = '';
		}
	}

	protected function validate_settings() {
		parent::validate_settings();

		if ( ! $this->can_save() ) {
			return;
		}

		if ( ITSEC_Core::doing_data_upgrade() ) {
			return;
		}

		if ( empty( $this->settings['site_key'] ) && empty( $this->settings['secret_key'] ) ) {
			$this->add_error( esc_html__( 'The reCAPTCHA feature will not be fully functional until you provide a Site Key and Secret Key.', 'it-l10n-ithemes-security-pro' ) );
		} else if ( empty( $this->settings['site_key'] ) ) {
			$this->add_error( esc_html__( 'The reCAPTCHA feature will not be fully functional until you provide a Site Key.', 'it-l10n-ithemes-security-pro' ) );
		} else if ( empty( $this->settings['secret_key'] ) ) {
			$this->add_error( esc_html__( 'The reCAPTCHA feature will not be fully functional until you provide a Secret Key.', 'it-l10n-ithemes-security-pro' ) );
		}
	}
}

ITSEC_Modules::register_validator( new ITSEC_Recaptcha_Validator( ITSEC_Modules::get_config( 'recaptcha' ) ) );
