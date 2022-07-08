<?php

class MainWP_ITSEC_Backup_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'backup';
	}

	protected function sanitize_settings() {
		$previous_settings = MainWP_ITSEC_Modules::get_settings( $this->get_id() );

		if ( ! isset( $this->settings['interval'] ) ) {
			$this->settings['interval'] = $previous_settings['interval'];
		}
		if ( ! isset( $this->settings['last_run'] ) ) {
			$this->settings['last_run'] = $previous_settings['last_run'];
		}
		
		if (isset($this->settings['use_individual_location'])) {
			$this->vars_to_skip_validate_matching_fields[] = 'use_individual_location';
			$this->vars_to_skip_validate_matching_fields[] = 'location';
			$this->sanitize_setting( 'bool', 'use_individual_location', __( 'Backup Location', 'l10n-mainwp-ithemes-security-extension' ) );
		}
		
		if (isset($this->settings['use_individual_exclude'])) {
			$this->vars_to_skip_validate_matching_fields[] = 'use_individual_exclude';
			$this->vars_to_skip_validate_matching_fields[] = 'exclude';
			$this->sanitize_setting( 'bool', 'use_individual_exclude', __( 'Exclude Tables', 'l10n-mainwp-ithemes-security-extension' ) );
		} else if (!isset($this->settings['exclude'])) {
			// individual settings
			$this->settings['exclude'] = array();
			//$this->vars_to_skip_validate_matching_fields[] = 'exclude'; // to fix			
		}
		
		$this->sanitize_setting( 'positive-int', 'method', __( 'Backup Method', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( array( 0, 1, 2 ), 'method', __( 'Backup Method', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'writable-directory', 'location', __( 'Backup Location', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'retain', __( 'Backups to Retain', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'zip', __( 'Compress Backup Files', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'newline-separated-array', 'exclude', __( 'Exclude Tables', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'enabled', __( 'Schedule Database Backups', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'interval', __( 'Backup Interval', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'last_run', __( 'Last Run', 'l10n-mainwp-ithemes-security-extension' ), false );
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Backup_Validator() );
