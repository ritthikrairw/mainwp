<?php
final class MainWP_ITSEC_Security_Check_Pro_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'security-check-pro';
	}
	
	public function get_defaults() {
		return array(			
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Security_Check_Pro_Settings() );
