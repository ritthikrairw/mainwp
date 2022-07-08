<?php

final class MainWP_ITSEC_Admin_User_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'admin-user';
	}
	
	public function get_defaults() {
		return array();
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Admin_User_Settings() );
