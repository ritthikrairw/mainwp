<?php

final class MainWP_ITSEC_Strong_Passwords_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'strong-passwords';
	}
	
	public function get_defaults() {
		return array(
			'role' => 'administrator',
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Strong_Passwords_Settings() );
