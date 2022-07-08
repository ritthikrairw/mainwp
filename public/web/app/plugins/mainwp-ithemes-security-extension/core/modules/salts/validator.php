<?php

class MainWP_ITSEC_WordPress_Salts_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'wordpress-salts';
	}

	protected function sanitize_settings() {
		$previous_settings = MainWP_ITSEC_Modules::get_settings( $this->get_id() );

		if ( ! isset( $this->settings['last_generated'] ) ) {
			$this->settings['last_generated'] = $previous_settings['last_generated'];
		}

		$this->sanitize_setting( 'bool', 'regenerate', __( 'Change WordPress Salts', 'l10n-mainwp-ithemes-security-extension' ), false );
		$this->sanitize_setting( 'positive-int', 'last_generated', __( 'Last Generated', 'l10n-mainwp-ithemes-security-extension' ), false );

		$this->vars_to_skip_validate_matching_fields[] = 'regenerate';
	}

	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}

		if ( ! $this->settings['regenerate'] ) {
			unset( $this->settings['regenerate'] );

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST['module'] ) && $this->get_id() === $_POST['module'] ) {
				// Request to modify just this module.

				$this->set_can_save( false );

				if ( MainWP_ITSEC_Modules::get_setting( 'global', 'write_files' ) ) {
					$this->add_error( new WP_Error( 'itsec-wordpress-salts-skipping-regeneration-empty-checkbox', __( 'You must check the Change WordPress Salts checkbox in order to change the WordPress salts.', 'l10n-mainwp-ithemes-security-extension' ) ) );
				} else {
					$this->add_error( new WP_Error( 'itsec-wordpress-salts-skipping-regeneration-write-files-disabled', __( 'The "Write to Files" setting is disabled in Global Settings. In order to use this feature, you must enable the "Write to Files" setting.', 'l10n-mainwp-ithemes-security-extension' ) ) );
				}
			}

			return;
		}

		unset( $this->settings['regenerate'] );
		$this->settings['last_generated'] = MainWP_ITSEC_Core::get_current_time_gmt();
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_WordPress_Salts_Validator() );
