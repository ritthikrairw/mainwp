<?php

final class MainWP_ITSEC_SSL_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'ssl';
	}
	
	public function get_defaults() {
		return array(
            'require_ssl' => 'disabled',
			'frontend' => 0,
			'admin'    => false,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_SSL_Settings() );
