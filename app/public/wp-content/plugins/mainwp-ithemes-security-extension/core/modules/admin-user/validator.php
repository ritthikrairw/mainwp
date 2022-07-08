<?php

final class MainWP_ITSEC_Admin_User_Validator extends MainWP_ITSEC_Validator {
	protected $run_validate_matching_fields = false;
	protected $run_validate_matching_types = false;


	public function get_id() {
		return 'admin-user';
	}

	protected function sanitize_settings() {
		// Only validate it if it exists
		if ( ! empty( $this->settings['new_username'] ) ) {
			$this->sanitize_setting( 'valid-username', 'new_username', __( 'New Admin Username', 'l10n-mainwp-ithemes-security-extension' ) );
		}

		// If the value wasn't sent for this, assume false (no change)
		if ( empty( $this->settings['change_id'] ) ) {
			$this->settings['change_id'] = false;
		} else {
			$this->sanitize_setting( 'bool', 'change_id', __( 'Change User ID 1', 'l10n-mainwp-ithemes-security-extension' ) );
		}
	}
	
	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}
		
		if ( empty( $this->settings['new_username'] ) || 'admin' === $this->settings['new_username'] ) {
			$this->settings['new_username'] = null;
		}
		
		if ( is_null( $this->settings['new_username'] ) && false === $this->settings['change_id'] ) {
			return;
		}
		
	}
	
	
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Admin_User_Validator() );
