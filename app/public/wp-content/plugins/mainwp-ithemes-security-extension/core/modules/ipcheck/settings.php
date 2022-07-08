<?php

final class MainWP_ITSEC_Network_Brute_Force_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'network-brute-force';
	}

	public function get_defaults() {
		return array(
			'api_key'       => '',
			'api_secret'    => '',
			'enable_ban'    => true,
			'updates_optin' => true,
			'api_nag'       => true,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Network_Brute_Force_Settings() );
