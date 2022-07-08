<?php

final class MainWP_ITSEC_WordPress_Salts_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'wordpress-salts';
	}
	
	public function get_defaults() {
		return array(
			'last_generated' => 0,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_WordPress_Salts_Settings() );
