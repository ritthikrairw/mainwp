<?php

class MainWP_ITSEC_Network_Brute_Force_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'network-brute-force';
	}
	
	protected function sanitize_settings() {
		$previous_settings = MainWP_ITSEC_Modules::get_settings( $this->get_id() );
		$this->settings = array_merge( $previous_settings, $this->settings );		
		
		if ( isset( $this->settings['email'] ) ) {
			$this->sanitize_setting( 'email', 'email', __( 'Email Address', 'l10n-mainwp-ithemes-security-extension' ) );
			$this->vars_to_skip_validate_matching_fields[] = 'email';
		}
		
		$this->sanitize_setting( 'bool', 'updates_optin', __( 'Receive Email Updates', 'l10n-mainwp-ithemes-security-extension' ) );
		
		$this->sanitize_setting( 'string', 'api_key', __( 'API Key', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'string', 'api_secret', __( 'API Secret', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'enable_ban', __( 'Ban Reported IPs', 'l10n-mainwp-ithemes-security-extension' ) );
	}
	
	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}		
		if ( $this->can_save() ) {
			// to fix
			if (!empty($this->settings['api_key'])) {
				unset( $this->settings['email'] );
			}
		}
		
		if ( isset( $this->settings['email'] ) ) {
			$this->settings['api_nag'] = false;	
			// general settings
			if (!MainWP_IThemes_Security::is_manage_site()) { 
				// set api_key and api_secret as enabled
				$this->settings['api_key'] = 1;
				$this->settings['api_secret'] = 1;							
			}
//			require_once( dirname( __FILE__ ) . '/utilities.php' );			
//			$key = MainWP_ITSEC_Network_Brute_Force_Utilities::get_api_key( $this->settings['email'], $this->settings['updates_optin'] );			
//			if ( is_wp_error( $key ) ) {
//				$this->set_can_save( false );
//				$this->add_error( $key );
//			} else {
//				$secret = MainWP_ITSEC_Network_Brute_Force_Utilities::activate_api_key( $key );
//				
//				if ( is_wp_error( $secret ) ) {
//					$this->set_can_save( false );
//					$this->add_error( $secret );
//				} else {
//					$this->settings['api_key'] = $key;
//					$this->settings['api_secret'] = $secret;
//
//					$this->settings['api_nag'] = false;
//
//					MainWP_ITSEC_Response::reload_module( $this->get_id() );
//				}
//			}
		}
		
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Network_Brute_Force_Validator() );
