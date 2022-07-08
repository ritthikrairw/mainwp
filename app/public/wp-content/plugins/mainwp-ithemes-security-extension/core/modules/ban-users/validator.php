<?php

class MainWP_ITSEC_Ban_Users_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'ban-users';
	}
	
	protected function sanitize_settings() {
		$this->sanitize_setting( 'bool', 'default', __( 'Default Ban List', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'enable_ban_lists', __( 'Ban Lists', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'server_config_limit', __( 'Limit Banned IPs in Server Config', 'l10n-mainwp-ithemes-security-extension' ) );
		
		$this->sanitize_setting( array( $this, 'sanitize_agent_list_entry' ), 'agent_list', __( 'Ban User Agents', 'l10n-mainwp-ithemes-security-extension' ) );
	}
	
	protected function sanitize_agent_list_entry( $entry ) {
		return trim( sanitize_text_field( $entry ) );
	}
	
	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}
		
		
		$previous_settings = MainWP_ITSEC_Modules::get_settings( $this->get_id() );
		
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Ban_Users_Validator() );
