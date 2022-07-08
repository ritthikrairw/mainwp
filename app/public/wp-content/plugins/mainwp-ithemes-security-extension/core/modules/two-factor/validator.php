<?php

final class MainWP_ITSEC_Two_Factor_Validator extends MainWP_ITSEC_Validator {
	protected $run_validate_matching_fields = false;
	protected $run_validate_matching_types  = false;

	public function get_id() {
		return 'two-factor';
	}

	public function get_valid_methods() {
		return array(
			'all'       => __( 'All Methods (recommended)', 'l10n-mainwp-ithemes-security-extension' ),
			'not_email' => __( 'All Except Email', 'l10n-mainwp-ithemes-security-extension' ),
			'custom'    => __( 'Select Methods Manually', 'l10n-mainwp-ithemes-security-extension' ),
		);
	}

	protected function sanitize_settings() {

	}

	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}

	}


}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Two_Factor_Validator() );
